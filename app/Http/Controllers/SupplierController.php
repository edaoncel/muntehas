<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\SupplierSyncService;
use App\Models\Setting;

class SupplierController extends Controller
{
    // 1. Tedarikçileri Listeleme
    public function index()
    {
        $suppliers = Supplier::withCount('products')->latest()->get();
        return view('admin.suppliers.index', compact('suppliers'));
    }

    // 2. Yeni Tedarikçi Kaydetme
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                  => 'required|string|max:255',
            'xml_url'               => 'nullable|url',
            'default_profit_margin' => 'required|numeric|min:0',
        ]);

        // Checkbox işaretlenmemişse false gönderilir
        $validated['is_active'] = $request->has('is_active') ? true : false;

        Supplier::create($validated);

        return redirect()->back()->with('success', 'Tedarikçi başarıyla eklendi.');
    }

    // 3. Tedarikçi Bilgilerini Güncelleme
    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name'                  => 'required|string|max:255',
            'xml_url'               => 'nullable|url',
            'default_profit_margin' => 'required|numeric|min:0',
        ]);

        $validated['is_active'] = $request->has('is_active') ? true : false;

        $supplier->update($validated);

        return redirect()->back()->with('success', 'Tedarikçi bilgileri güncellendi.');
    }

    // 4. Tedarikçi Silme
    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return redirect()->back()->with('success', 'Tedarikçi sistemden kaldırıldı.');
    }

    public function syncNow(Supplier $supplier, SupplierSyncService $syncService)
    {
        try {
            // Servisten dönen dizi: ['added' => X, 'updated' => Y]
            $result = $syncService->sync($supplier);
            
            $added = $result['added'] ?? 0;
            $updated = $result['updated'] ?? 0;

            $successMessage = "Senkronizasyon tamamlandı! {$added} yeni ürün eklendi, {$updated} ürün güncellendi.";

            if (request()->expectsJson()) {
                $supplier->refresh(); // Güncel tarihi taze alalım
                return response()->json([
                    'success' => true,
                    'message' => $successMessage,
                    'formatted_date' => $supplier->last_synced_at ? $supplier->last_synced_at->timezone('Europe/Istanbul')->translatedFormat('d F Y, H:i') : '-',
                    'human_date' => $supplier->last_synced_at ? $supplier->last_synced_at->timezone('Europe/Istanbul')->diffForHumans() : '',
                ]);
            }

            return redirect()->back()->with('success', $successMessage);

        } catch (\Exception $e) {
            Log::error("Manuel Tedarikçi Senkronizasyon Hatası ({$supplier->name}): " . $e->getMessage());
            
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Hata oluştu: ' . $e->getMessage()], 500);
            }

            return redirect()->back()->with('error', 'Hata oluştu: ' . $e->getMessage());
        }
    }

    public function syncAllActive(SupplierSyncService $syncService, \App\Services\MarketplaceService $marketplaceService)
    {
        $suppliers = Supplier::where('is_active', true)->get();
        $reportDetails = [];
        $totalAdded = 0;
        $totalUpdated = 0;

        foreach ($suppliers as $supplier) {
            if (!empty($supplier->xml_url)) {
                try {
                    $result = $syncService->sync($supplier);
                    $added = $result['added'] ?? 0;
                    $updated = $result['updated'] ?? 0;

                    $totalAdded += $added;
                    $totalUpdated += $updated;

                    $reportDetails[] = "📦 <strong>{$supplier->name}</strong>: {$added} eklendi, {$updated} güncellendi.";
                } catch (\Exception $e) {
                    Log::error("Toplu Senkronizasyon Hatası ({$supplier->name}): " . $e->getMessage());
                    $reportDetails[] = "❌ <strong>{$supplier->name}</strong> markasında hata oluştu.";
                }
            }
        }

        // 1. XML Senkronizasyon İstatistiklerini Veritabanına Kaydedelim
        Setting::updateOrCreate(
            ['key' => 'last_xml_sync_stats'],
            ['value' => json_encode([
                'last_sync_at'   => now()->toDateTimeString(),
                'total_added'    => $totalAdded,
                'total_updated'  => $totalUpdated,
                'details'        => $reportDetails,
                'status'         => 'Success'
            ])]
        );

        // 2. Şimdi de Güncellenen Ürünleri Pazaryerine Gönderelim ve Raporlayalım
        // 2. Şimdi de Güncellenen Ürünleri Pazaryerine Gönderelim ve Raporlayalım
        try {
            $mpResult = $marketplaceService->syncProductsToMarketplaces();
            $mpCount = $mpResult['product_count'] ?? 0;
            $mpMessage = $mpResult['message'] ?? 'Pazaryeri aktarımı tamamlandı.';
            $mpSuccess = $mpResult['success'] ?? true;

            // Pazaryeri istatistiklerini de kaydediyoruz
            Setting::updateOrCreate(
                ['key' => 'last_marketplace_sync_stats'],
                ['value' => json_encode([
                    'last_sync_at'  => now()->toDateTimeString(),
                    'product_count' => $mpCount,
                    'message'       => $mpMessage,
                    'status'        => $mpSuccess ? 'Success' : 'Error'
                ])]
            );

            // 🌟 DÜZELTME BURADA: Genel toplam yerine servisden gelen detaylı pazaryeri mesajını yazdırıyoruz
            if ($mpSuccess) {
                $reportDetails[] = "🚀 <strong>Pazaryeri Aktarımı:</strong> {$mpMessage}";
            } else {
                $reportDetails[] = "⚠️ <strong>Pazaryeri Uyarı:</strong> {$mpMessage}";
            }

        } catch (\Exception $e) {
            Log::error("Manuel Toplu Pazaryeri Gönderim Hatası: " . $e->getMessage());
            $reportDetails[] = "❌ <strong>Pazaryeri Hatası:</strong> " . $e->getMessage();

            Setting::updateOrCreate(
                ['key' => 'last_marketplace_sync_stats'],
                ['value' => json_encode([
                    'last_sync_at'  => now()->toDateTimeString(),
                    'product_count' => 0,
                    'message'       => 'Hata: ' . $e->getMessage(),
                    'status'        => 'Error'
                ])]
            );
        }

        $finalMessage = implode('<br>', $reportDetails);
        if (empty($finalMessage)) {
            $finalMessage = "Senkronize edilecek aktif tedarikçi bulunamadı.";
        }

        return response()->json([
            'success' => true,
            'message' => $finalMessage
        ]);
    }

public function settings()
{
    // 1. XML Senkronizasyon İstatistiklerini Al (Eğer varsa veritabanından, yoksa varsayılan)
    $lastXmlSetting = Setting::where('key', 'last_xml_sync_stats')->first();
    $xmlStats = [
        'status' => 'Bekliyor',
        'total_added' => 0,
        'total_updated' => 0,
        'last_sync_at' => 'Henüz çalışmadı'
    ];

    if ($lastXmlSetting && !empty($lastXmlSetting->value)) {
        $decodedXml = json_decode($lastXmlSetting->value, true);
        if (is_array($decodedXml)) {
            $xmlStats = array_merge($xmlStats, $decodedXml);
        }
    }

    // 2. Pazaryeri Gönderim İstatistiklerini Al (Eğer varsa veritabanından, yoksa varsayılan)
    $lastMarketplaceSetting = Setting::where('key', 'last_marketplace_sync_stats')->first();
    $marketplaceStats = [
        'status' => 'Pending',
        'product_count' => 0,
        'message' => 'İşlem bekleniyor...',
        'last_sync_at' => '-'
    ];

    if ($lastMarketplaceSetting && !empty($lastMarketplaceSetting->value)) {
        $decodedMp = json_decode($lastMarketplaceSetting->value, true);
        if (is_array($decodedMp)) {
            $marketplaceStats = array_merge($marketplaceStats, $decodedMp);
        }
    }

    $lastSyncTimestamp = !empty($xmlStats['last_sync_at']) && $xmlStats['last_sync_at'] !== 'Henüz çalışmadı' 
        ? strtotime($xmlStats['last_sync_at']) 
        : 0;

    return view('admin.marketplaces.settings', compact('lastSyncTimestamp', 'xmlStats', 'marketplaceStats'));
}
    }