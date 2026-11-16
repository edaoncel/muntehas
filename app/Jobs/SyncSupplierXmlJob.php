<?php

namespace App\Jobs;

use App\Models\Supplier;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use XMLReader;

class SyncSupplierXmlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $supplier;

    // Timeout süresini artırıyoruz (Binlerce ürün uzun sürebilir - Örn: 15 dakika)
    public $timeout = 900; 

    public function __construct(Supplier $supplier)
    {
        $this->supplier = $supplier;
    }

    public function handle(): void
    {
        if (empty($this->supplier->xml_url)) {
            return;
        }

        $reader = new XMLReader();
        
        // XML URL'sini açıyoruz (RAM'i şişirmeden parça parça okuyacak)
        if (!$reader->open($this->supplier->xml_url)) {
            Log::error("Tedarikçi XML adresine ulaşılamadı: {$this->supplier->name}");
            return;
        }

        try {
            $batchData = [];
            $batchSize = 500; // Her seferinde veritabanına 500'erli toplu kayıt (Bulk Insert) atacağız

            // XML içerisindeki ürün etiketinin adını XML yapınıza göre değiştirebilirsiniz (Örn: 'item', 'product', 'Urun')
            $productNodeName = 'item'; 

            while ($reader->read()) {
                if ($reader->nodeType == XMLReader::ELEMENT && $reader->name === $productNodeName) {
                    
                    // Bu düğümü SimpleXMLElement olarak güvenle alıyoruz
                    $node = new \SimpleXMLElement($reader->readOuterXML());

                    // XML'den gelen alanları kendi veritabanı sütunlarınıza göre düzenleyin:
                    $sku = (string)($node->sku ?? $node->product_code ?? $node->stok_kodu ?? '');
                    if (empty($sku)) continue;

                    $batchData[] = [
                        'supplier_id' => $this->supplier->id,
                        'sku'         => $sku,
                        'name'        => (string)($node->name ?? $node->title ?? $node->urun_adi ?? ''),
                        'price'       => (float)($node->price ?? $node->fiyat ?? 0),
                        'stock'       => (int)($node->stock ?? $node->stok ?? 0),
                        'updated_at'  => now(),
                        'created_at'  => now(),
                    ];

                    // Belirlenen paket boyutuna (500 adet) ulaşıldığında veritabanına toplu kayıt atıp RAM'i boşaltıyoruz
                    if (count($batchData) >= $batchSize) {
                        $this->saveBatch($batchData);
                        $batchData = []; // Diziyi sıfırla
                    }
                }
            }

            // Döngü sonunda artan kalan ürünler varsa onları da kaydedelim
            if (!empty($batchData)) {
                $this->saveBatch($batchData);
            }

            $reader->close();

            // Senkronizasyon başarılı, zamanı güncelleyelim
            $this->supplier->update([
                'last_synced_at' => now()
            ]);

            Log::info("Tedarikçi ve ürünleri başarıyla senkronize edildi: {$this->supplier->name}");

        } catch (\Exception $e) {
            $reader->close();
            Log::error("XML Senkronizasyon Hatası ({$this->supplier->name}): " . $e->getMessage());
        }
    }

    /**
     * Ürünleri veritabanına toplu olarak kaydeder (Upsert: Varsa güncelle, yoksa ekle)
     */
    protected function saveBatch(array $data)
    {
        Product::upsert(
            $data,
            ['supplier_id', 'sku'], // Benzersiz anahtar (Aynı tedarikçi ve SKU varsa güncelle)
            ['name', 'price', 'stock', 'updated_at'] // Güncellenecek sütunlar
        );
    }
}