<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;
use App\Models\Supplier;
use App\Services\SupplierSyncService;
use App\Services\MarketplaceService;

// 1. Tedarikçi XML'lerini Her 5 Saniyede Bir Senkronize Et (Test Amaçlı)
Schedule::call(function () {
    $syncService = new SupplierSyncService();
    $suppliers = Supplier::where('is_active', true)->get();
    
    if ($suppliers->isEmpty()) return;

    $totalAddedGlobal = 0;
    $totalUpdatedGlobal = 0;
    $detailedReport = [];

    foreach ($suppliers as $supplier) {
        if (empty($supplier->xml_url)) continue;

        try {
            $result = $syncService->sync($supplier);
            $added = $result['added'] ?? 0;
            $updated = $result['updated'] ?? 0;

            $totalAddedGlobal += $added;
            $totalUpdatedGlobal += $updated;
            $detailedReport[] = "{$supplier->name}: {$added} eklendi, {$updated} güncellendi";
        } catch (\Exception $e) {
            Log::error("Otomatik Senkronizasyon Hatası ({$supplier->name}): " . $e->getMessage());
        }
    }

    Setting::updateOrCreate(
        ['key' => 'last_xml_sync_stats'],
        ['value' => json_encode([
            'last_sync_at'   => now()->toDateTimeString(),
            'total_added'    => $totalAddedGlobal,
            'total_updated'  => $totalUpdatedGlobal,
            'details'        => $detailedReport,
            'status'         => 'Success'
        ])]
    );
})->everyThirtyMinutes()->name('coklu-xml-senkronizasyonu')->onOneServer()->withoutOverlapping();


// 2. Güncellenen Ürünleri Her 5 Saniyede Bir Pazaryerlerine Gönder (Test Amaçlı)
// Güncellenen Ürünleri Pazaryerlerine Gönder ve Detaylı İstatistik Kaydet
Schedule::call(function () {
    $marketplaceService = new MarketplaceService();
    
    try {
        $result = $marketplaceService->syncProductsToMarketplaces();
        $message = $result['message'] ?? 'Pazaryeri senkronizasyonu tamamlandı.';
        $isSuccess = $result['success'] ?? true;
        $productCount = $result['product_count'] ?? 0;

        // 🟢 Kaç ürün gönderildiğini ve durumunu detaylıca kaydediyoruz
        Setting::updateOrCreate(
            ['key' => 'last_marketplace_sync_stats'],
            ['value' => json_encode([
                'last_sync_at'  => now()->toDateTimeString(),
                'product_count' => $productCount,
                'message'       => $message,
                'status'        => $isSuccess ? 'Success' : 'Error'
            ])]
        );

        Log::info("Otomatik Pazaryeri Gönderim Raporu: {$productCount} ürün işlendi - " . $message);
    } catch (\Exception $e) {
        Log::error("Otomatik Pazaryeri Gönderim Hatası: " . $e->getMessage());

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
})->everyThirtyMinutes()->name('otomatik-pazaryeri-gonderimi')->onOneServer()->withoutOverlapping();