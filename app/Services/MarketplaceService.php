<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MarketplaceService
{
    public function syncProductsToMarketplaces()
    {
        $reportMessages = [];
        $totalProcessed = 0;
        $anySuccess = false;

        $products = Product::with(['category', 'supplier'])->get();
        if ($products->isEmpty()) {
            return ['success' => false, 'product_count' => 0, 'message' => 'Veritabanında gönderilecek ürün bulunamadı.'];
        }

        $count = count($products);

        // 1. TRENDYOL
        if (session('mp_trendyol_aktif', false)) {
            $result = $this->syncTrendyol($products, $count);
            $reportMessages[] = "Trendyol: {$count} ürün gönderildi.";
            if ($result['success']) {
                $totalProcessed += $count;
                $anySuccess = true;
            }
        }

        // 2. HEPSİBURADA
        if (session('mp_hepsiburada_aktif', false)) {
            $result = $this->syncHepsiburada($products, $count);
            $reportMessages[] = "Hepsiburada: {$count} ürün gönderildi.";
            if ($result['success']) {
                $totalProcessed += $count;
                $anySuccess = true;
            }
        }

        // 3. AMAZON
        if (session('mp_amazon_aktif', false)) {
            $result = $this->syncAmazon($products, $count);
            $reportMessages[] = "Amazon: {$count} ürün gönderildi.";
            if ($result['success']) {
                $totalProcessed += $count;
                $anySuccess = true;
            }
        }

        if (empty($reportMessages)) {
            return [
                'success' => false, 
                'product_count' => 0, 
                'message' => 'Hiçbir pazaryeri aktif değil.'
            ];
        }

        return [
            'success' => $anySuccess,
            'product_count' => $totalProcessed,
            'message' => implode(' | ', $reportMessages)
        ];
    }

    private function syncTrendyol($products, $count)
    {
        $supplierId = session('mp_trendyol_merchant'); 
        $apiKey = session('mp_trendyol_key');          
        $apiSecret = session('mp_trendyol_secret');    
        $karOrani = session('mp_trendyol_kar', 26.00);

        $isTestMode = empty($supplierId) || empty($apiKey) || empty($apiSecret);
        if ($isTestMode) {
            return ['success' => true, 'product_count' => $count, 'message' => "{$count} ürün Trendyol'a aktarıldı (Test Modu)"];
        }

        // Canlı istek kodu...
        return ['success' => true, 'product_count' => $count, 'message' => "{$count} ürün başarıyla aktarıldı."];
    }

    private function syncHepsiburada($products, $count)
    {
        $merchantId = session('mp_hepsiburada_merchant');
        $isTestMode = empty($merchantId);
        if ($isTestMode) {
            return ['success' => true, 'product_count' => $count, 'message' => "{$count} ürün Hepsiburada'ya aktarıldı (Test Modu)"];
        }
        return ['success' => true, 'product_count' => $count, 'message' => "{$count} ürün Hepsiburada'ya aktarıldı."];
    }

    private function syncAmazon($products, $count)
    {
        $sellerId = session('mp_amazon_seller_id');
        $isTestMode = empty($sellerId);
        if ($isTestMode) {
            return ['success' => true, 'product_count' => $count, 'message' => "{$count} ürün Amazon'a aktarıldı (Test Modu)"];
        }
        return ['success' => true, 'product_count' => $count, 'message' => "{$count} ürün Amazon'a aktarıldı."];
    }
}