<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use XMLReader;

class SupplierSyncService
{
    public function sync(Supplier $supplier): array
    {
        if (empty($supplier->xml_url)) {
            throw new \Exception("Bu tedarikçinin XML adresi tanımlı değil.");
        }

        $url = $supplier->xml_url;
        $reader = new XMLReader();

        // 🟢 Akıllı Yol Tespiti (Yerel loopback / artisan serve kilitlenmesini önler)
        if (str_contains($url, '127.0.0.1') || str_contains($url, 'localhost')) {
            $fileName = basename(parse_url($url, PHP_URL_PATH)); // Örn: bakgelal.xml
            $localPath = public_path($fileName);
            
            if (!file_exists($localPath)) {
                $localPath = public_path('uploads/' . $fileName);
            }

            if (file_exists($localPath)) {
                if (!$reader->open($localPath)) {
                    throw new \Exception("Yerel XML dosyası açılamadı: {$localPath}");
                }
            } else {
                throw new \Exception("Yerel XML dosyası sunucuda bulunamadı: {$fileName}");
            }
        } else {
            // Gerçek canlı dış kaynak URL'si
            $xmlPath = $url . '?t=' . time();
            if (!$reader->open($xmlPath)) {
                throw new \Exception("Tedarikçinin XML kaynağına dış bağlantı kurulamadı.");
            }
        }

        $firma_kar = floatval($supplier->default_profit_margin ?? 26.00);
        $addedCount = 0;
        $updatedCount = 0;
        
        $xmlProducts = [];
        $skusInXml = [];

        // 1. AŞAMA: XML'deki verileri okuyalım
        while ($reader->read()) {
            if ($reader->nodeType == XMLReader::ELEMENT && in_array($reader->localName, ['product', 'Urun', 'item'])) {
                
                $node = new \SimpleXMLElement($reader->readOuterXML());
                
                $stok_kodu = trim((string)($node->sku ?? $node->StokKodu ?? ''));
                if (empty($stok_kodu)) continue;

                $skusInXml[] = $stok_kodu;

                $urun_adi = trim((string)($node->name ?? $node->UrunAdi ?? 'İsimsiz'));
                $alis_fiyati = floatval($node->price ?? $node->Fiyat ?? 0);
                $stok = intval($node->stock ?? $node->Stok ?? 10);
                $aciklama = (string)($node->description ?? $node->Aciklama ?? '');
                $marka = (string)($node->brand ?? $node->Marka ?? '');
                $model_kodu = (string)($node->model_code ?? $node->ModelKodu ?? $stok_kodu);
                $barkod = (string)($node->barcode ?? $node->Barkod ?? '');
                $renk = (string)($node->color ?? $node->Renk ?? '');

                // Kategori adını esnek ve akıllı bulma
                $kategoriAdi = 'Diğer';
                $categoryTags = [
                    'category', 'kategori', 'kategoriadi', 'categoryname', 'cat', 
                    'urunkategorisi', 'kategoripath', 'maincategory', 'anahategori'
                ];

                foreach ($node->children() as $childName => $childValue) {
                    $lowerName = strtolower($childName);
                    if (in_array($lowerName, $categoryTags)) {
                        if ($childValue->count() > 0) {
                            foreach ($childValue as $subChild) {
                                $subVal = trim((string)$subChild);
                                if (!empty($subVal)) {
                                    $kategoriAdi = $subVal;
                                    break 2;
                                }
                            }
                        } else {
                            $val = trim((string)$childValue);
                            if (!empty($val)) {
                                $kategoriAdi = $val;
                                break;
                            }
                        }
                    }
                }

                $gorseller = [];
                if (isset($node->images->image)) {
                    foreach ($node->images->image as $img) {
                        $gorseller[] = (string)$img;
                    }
                }

                // 🟢 DÜZELTİLDİ: Çarpı işareti eklendi
                $ham_fiyat = $alis_fiyati * (1 + ($firma_kar / 100));
                $satis_fiyati = floatval(number_format($ham_fiyat, 2, '.', ''));
                $satis_fiyati = $satis_fiyati > 0 ? $satis_fiyati : 100.00;

                $xmlProducts[$stok_kodu] = [
                    'sku'         => $stok_kodu,
                    'name'        => $urun_adi,
                    'price'       => $satis_fiyati,
                    'stock'       => $stok,
                    'description' => $aciklama,
                    'brand'       => $marka,
                    'model_code'  => $model_kodu,
                    'barcode'     => $barkod,
                    'color'       => $renk,
                    'category'    => $kategoriAdi,
                    'images'      => $gorseller
                ];
            }
        }
        $reader->close();

        if (empty($xmlProducts)) {
            return ['added' => 0, 'updated' => 0];
        }

        // 2. AŞAMA: Mevcut ürünleri ve kategorileri çekelim
        $existingProducts = Product::whereIn('sku', $skusInXml)->get()->keyBy('sku');
        $categoryNames = collect($xmlProducts)->pluck('category')->unique();
        $existingCategories = Category::whereIn('name', $categoryNames)->get()->keyBy('name');

        $toInsertOrUpdate = [];
        $batchSize = 500;
        $time = now()->format('H:i');

        foreach ($xmlProducts as $sku => $xmlData) {
            $kategoriAdi = $xmlData['category'];
            if (!isset($existingCategories[$kategoriAdi])) {
                $categoryRecord = Category::create([
                    'name' => $kategoriAdi,
                    'slug' => Str::slug($kategoriAdi),
                    'show_in_menu' => 0,
                ]);
                $existingCategories[$kategoriAdi] = $categoryRecord;
            }
            $categoryId = $existingCategories[$kategoriAdi]->id;

            $actionNote = null;

            if (isset($existingProducts[$sku])) {
                $dbProduct = $existingProducts[$sku];

                // Manuel düzenlendiyse XML ile ezme!
                if ($dbProduct->is_manual_edited) {
                    continue; 
                }

                $changes = [];

                if (trim($dbProduct->name) !== trim($xmlData['name'])) {
                    $changes[] = 'Ürün adı değiştirildi';
                }
                if (intval($dbProduct->category_id) !== intval($categoryId)) {
                    $changes[] = 'Kategori değiştirildi';
                }
                if (floatval(number_format(floatval($dbProduct->price), 2, '.', '')) !== floatval(number_format(floatval($xmlData['price']), 2, '.', ''))) {
                    $changes[] = 'Fiyat güncellendi';
                }
                if (intval($dbProduct->stock) !== intval($xmlData['stock'])) {
                    $changes[] = 'Stok adedi güncellendi';
                }
                if (trim($dbProduct->brand ?? '') !== trim($xmlData['brand'])) {
                    $changes[] = 'Marka güncellendi';
                }
                if (trim($dbProduct->barcode ?? '') !== trim($xmlData['barcode'])) {
                    $changes[] = 'Barkod değiştirildi';
                }

                $hasChanged = !empty($changes);

                if ($hasChanged) {
                    $updatedCount++;
                    $actionText = implode(', ', $changes);
                    $actionNote = "{$actionText} - {$time}";
                } else {
                    continue; 
                }
            } else {
                $addedCount++;
                $actionNote = "Yeni ürün eklendi - {$time}";
            }

            $toInsertOrUpdate[] = [
                'sku'               => $sku,
                'supplier_id'       => $supplier->id,
                'category_id'       => $categoryId,
                'name'              => $xmlData['name'],
                'slug'              => Str::slug($xmlData['name']) . '-' . rand(1000, 9999),
                'price'             => $xmlData['price'],
                'stock'             => $xmlData['stock'],
                'description'       => $xmlData['description'],
                'brand'             => $xmlData['brand'],
                'model_code'        => $xmlData['model_code'],
                'barcode'           => $xmlData['barcode'],
                'color'             => $xmlData['color'],
                'images'            => json_encode($xmlData['images']),
                'is_manual_edited'  => false,
                'last_action_note'  => $actionNote,
                'created_at'        => now(),
                'updated_at'        => now()
            ];

            if (count($toInsertOrUpdate) >= $batchSize) {
                Product::upsert(
                    $toInsertOrUpdate,
                    ['sku'], 
                    ['supplier_id', 'category_id', 'name', 'price', 'stock', 'description', 'brand', 'model_code', 'barcode', 'color', 'images', 'last_action_note', 'updated_at']
                );
                $toInsertOrUpdate = [];
            }
        }

        if (!empty($toInsertOrUpdate)) {
            Product::upsert(
                $toInsertOrUpdate,
                ['sku'], 
                ['supplier_id', 'category_id', 'name', 'price', 'stock', 'description', 'brand', 'model_code', 'barcode', 'color', 'images', 'last_action_note', 'updated_at']
            );
        }

        $supplier->update(['last_synced_at' => now()]);

        return [
            'added'   => $addedCount,
            'updated' => $updatedCount
        ];
    }
}