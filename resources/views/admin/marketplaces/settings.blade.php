@extends('layouts.admin')
@section('title', 'Muntehas - Pazaryeri Yönetimi & API Entegrasyonu')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

@section('content')
<div class="container-fluid px-4 py-4" style="background-color: #f8fafc; min-height: 100vh;">
    
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-3 border-bottom gap-3">
        <div>
            <h1 class="h3 fw-bold text-dark mb-0">
                <i class="fa-solid fa-network-wired text-primary me-2"></i>Pazaryeri Yönetimi & API Entegrasyonu
            </h1>
            <p class="text-muted small mb-0 mt-1">Trendyol, Hepsiburada ve Amazon API ayarlarını yapılandırın, fiyat kâr oranlarını yönetin ve XML senkronizasyonunu gerçekleştirin.</p>
        </div>
        <div class="d-flex align-items-center gap-2 mb-1">
                
            @foreach($suppliers as $supplier)
    @php
        // Senkronizasyon durumunu ve 1 günden eski olup olmadığını kontrol ediyoruz
        $isSynced = !is_null($supplier->last_synced_at);
        $isRecent = $isSynced && \Carbon\Carbon::parse($supplier->last_synced_at)->greaterThan(now()->subDay());
    @endphp

    <div class="flex items-center justify-between p-4 rounded-2xl border transition-all duration-300 
        {{ !$isSynced || !$isRecent ? 'bg-red-50 border-red-200' : 'bg-emerald-50 border-emerald-200' }}" 
        id="supplier-card-{{ $supplier->id }}">
        
        <div>
            <h4 class="font-bold text-gray-900">{{ $supplier->name }}</h4>
            
            <span class="text-[10px] block mt-1 {{ !$isSynced || !$isRecent ? 'text-red-600' : 'text-emerald-700' }}">
                Son Eşitleme: 
                <strong class="{{ !$isSynced || !$isRecent ? 'text-red-700' : 'text-emerald-800' }} sync-time-text-{{ $supplier->id }}">
                    @if($isSynced)
                        <strong class="{{ !$isSynced || !$isRecent ? 'text-red-700' : 'text-emerald-800' }}">
                            {{ \Carbon\Carbon::parse($supplier->last_synced_at)->timezone('Europe/Istanbul')->locale('tr')->translatedFormat('d F Y, H:i') }}
                        </strong>
                        <span class="{{ !$isSynced || !$isRecent ? 'text-red-500' : 'text-emerald-600' }}">
                            ({{ \Carbon\Carbon::parse($supplier->last_synced_at)->timezone('Europe/Istanbul')->locale('tr')->diffForHumans() }})
                        </span>
                    @else
                        <span class="text-red-500 font-semibold">Henüz senkronize edilmedi.</span>
                    @endif
                </strong>
            </span>

            {{-- 1 günden uzun süredir senkronize edilmediyse veya hiç yapılmadıysa uyarı metni gösterilir --}}
            @if(!$isSynced || !$isRecent)
                <div class="mt-2 flex items-center gap-1.5 text-red-600 text-[11px] font-medium">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>Entegrasyon sisteminizi kontrol ediniz! (1 günden uzun süredir veri alınamadı)</span>
                </div>
            @endif
        </div>

        {{-- Sağ taraftaki sabit durum noktası (Yanıp sönmeyen statik renk) --}}
        <div class="flex items-center gap-2">
            <span class="flex h-3 w-3">
              @if($isSynced && $isRecent)
                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500" title="Sistem Aktif ve Güncel"></span>
              @else
                <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500" title="Entegrasyon Hatası / Gecikme"></span>
              @endif
            </span>
        </div>

    </div>
@endforeach

               <script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll('.sync-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const supplierId = this.getAttribute('data-supplier-id');
                const submitBtn = this.querySelector('.sync-btn');
                const btnText = this.querySelector('.btn-text');
                const timeContainer = document.querySelector(`.sync-time-text-${supplierId}`);

                // Eğer elementler bulunamadıysa hata almamak için kontrol ekleyelim
                if (!submitBtn || !btnText) return;

                // Butonu yükleniyor durumuna al
                submitBtn.disabled = true;
                const originalBtnText = btnText.textContent;
                btnText.textContent = 'Eşitleniyor...';

                fetch(this.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // 1. Tarihi anında ekranda güncelle (Eğer DOM'da o element varsa)
                        if (timeContainer) {
                            timeContainer.innerHTML = `${data.formatted_date} (${data.human_date})`;
                        }
                        
                        // 2. Global Bildirim Kutusu varsa orada göster, yoksa alert kullan
                        const globalBox = document.getElementById('global-sync-notification');
                        const globalText = document.getElementById('global-sync-notification-text');
                        
                        if (globalBox && globalText) {
                            globalText.innerHTML = data.message;
                            globalBox.style.display = 'block';
                            
                            setTimeout(() => {
                                globalBox.style.display = 'none';
                            }, 6000);
                        } else {
                            alert(data.message);
                        }

                        // Opsiyonel: Vitrin grafiklerini güncel tutmak için sayfayı 1.5 saniye sonra yenileyebilir veya verileri tazeleyebilirsiniz
                        // window.location.reload(); 
                    } else {
                        alert(data.message || 'Senkronizasyon sırasında bir sorun oluştu.');
                    }
                })
                .catch(error => {
                    console.error('Hata:', error);
                    alert('Senkronizasyon sırasında ağ hatası oluştu.');
                })
                .finally(() => {
                    // Butonu eski haline getir
                    submitBtn.disabled = false;
                    btnText.textContent = originalBtnText;
                });
            });
        });
    });
</script>
</div>
        <div class="d-flex align-items-center gap-2">
            <div class="bg-white p-2 px-3 rounded-4 shadow-sm border d-flex align-items-center gap-3">
                <div class="position-relative">
                    <span class="spinner-grow spinner-grow-sm text-success" role="status"></span>
                    <span class="position-absolute top-0 start-0 spinner-grow spinner-grow-sm text-success opacity-50"></span>
                </div>
                <div>
                    <div class="small fw-bold text-dark lh-1">Sistem Aktif</div>
                    <div class="text-muted opacity-75" style="font-size: 11px;">API Bağlantıları Canlı</div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center" role="alert">
            <div class="bg-success text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                <i class="fa-solid fa-check"></i>
            </div>
            <div>{!! session('success') !!}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center" role="alert">
            <div class="bg-danger text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                <i class="fa-solid fa-exclamation"></i>
            </div>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- HIZLI KONTROL VE TETİKLEME PANELİ -->
    <div class="card border-0 shadow-sm rounded-4 bg-white p-4 mb-4">
        <div class="row align-items-center justify-content-between g-3">
            <!-- Sol Taraf: Bilgilendirme ve Geri Sayım -->
            <div class="col-md-8">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-3 bg-primary bg-opacity-10 text-primary">
                        <i class="fa-solid fa-sync fa-lg"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold text-dark mb-1">Otomatik Senkronizasyon Paneli</h4>
                        <p class="text-muted small mb-0">Tedarikçi XML ve pazaryeri entegrasyonları arka planda her 30 dakikada bir otomatik güncellenir.</p>
                    </div>
                </div>
            </div>

            <!-- Sağ Taraf: Sayaç ve Şimdi Senkronize Et Butonu -->
            <div class="col-md-4 text-md-end">
                <div class="d-flex flex-column align-items-md-end align-items-start gap-2">
                    <div>
                        <span class="text-xs text-muted me-1">Sonraki Otomatik Çalışma:</span>
                        <span id="countdown-timer" class="text-sm font-mono fw-bold text-indigo-600" style="visibility: hidden;">--:--</span>
                    </div>

                    <div class="d-flex align-items-center gap-3">
                        <span class="text-muted small">Aktif Mağaza: <strong>{{ (session('mp_trendyol_aktif', false) ? 1 : 0) + (session('mp_hepsiburada_aktif', false) ? 1 : 0) + (session('mp_amazon_aktif', false) ? 1 : 0) }} / 3</strong></span>
                        
                        <form action="{{ route('admin.marketplaces.push') }}" method="POST" class="m-0">
                            @csrf
                            <button type="submit" class="btn btn-success fw-semibold py-2 px-3 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                                <i class="fa-solid fa-rotate"></i> Pazaryerlerine Senkronize Et
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- İlerleme Çubuğu -->
        <div class="w-full bg-light rounded-pill h-2 overflow-hidden mt-4">
            <div id="progress-bar" class="bg-primary h-2 transition-all duration-1000" style="width: 100%;"></div>
        </div>
    </div>
    <!-- PAZARYERİ KUTULARI (Trendyol, Hepsiburada, Amazon) -->
    <div class="row g-4 mb-4">
        
        <!-- 1. Trendyol Kutusu -->
        <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white overflow-hidden transition-all">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="rounded-3 p-2.5 me-3 fw-bold text-white shadow-sm d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: linear-gradient(135deg, #f97316, #ea580c);">
                                <i class="fa-solid fa-bag-shopping fa-lg"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold text-dark mb-0">Trendyol</h5>
                                <span class="text-muted small">Marketplace API</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">
                    <form action="{{ route('admin.marketplaces.muntehas.kar') }}" method="POST">
                        @csrf
                        <input type="hidden" name="pazaryeri" value="trendyol">
                        
                        <!-- Aktif/Pasif Switch -->
                        <div class="mb-3 form-check form-switch ps-0 d-flex justify-content-between align-items-center bg-light p-2.5 rounded-3">
                            <label class="form-check-label small fw-bold text-dark ms-2" for="trendyolAktif">Mağaza Durumu</label>
                            <input class="form-check-input style-switch me-0" type="checkbox" role="switch" id="trendyolAktif" name="aktif" value="1" {{ session('mp_trendyol_aktif', true) ? 'checked' : '' }}>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Kâr Oranı (%)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0 text-muted"><i class="fa-solid fa-percent"></i></span>
                                <input type="number" step="0.01" name="varsayilan_kar" class="form-control bg-light border-0 fw-bold text-dark" value="{{ session('mp_trendyol_kar', 26.00) }}" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Tedarikçi (Supplier) ID</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0 text-muted"><i class="fa-solid fa-id-card"></i></span>
                                <input type="text" name="merchant_id" class="form-control bg-light border-0" placeholder="Örn: 123456" value="{{ session('mp_trendyol_merchant') }}">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-semibold text-secondary">API Secret</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0 text-muted"><i class="fa-solid fa-key"></i></span>
                                <input type="password" name="api_secret" class="form-control bg-light border-0" placeholder="••••••••••••••••" value="{{ session('mp_trendyol_secret') }}">
                            </div>
                        </div>

                        <button type="submit" class="btn w-100 fw-semibold text-white py-2.5 rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2" style="background-color: #f97316; border: none;">
                            <i class="fa-solid fa-floppy-disk"></i> Trendyol Ayarlarını Kaydet
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- 2. Hepsiburada Kutusu -->
        <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white overflow-hidden">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="rounded-3 p-2.5 me-3 fw-bold text-white shadow-sm d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: linear-gradient(135deg, #ff6000, #d45000);">
                                <i class="fa-solid fa-store fa-lg"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold text-dark mb-0">Hepsiburada</h5>
                                <span class="text-muted small">Merchant API</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">
                    <form action="{{ route('admin.marketplaces.muntehas.kar') }}" method="POST">
                        @csrf
                        <input type="hidden" name="pazaryeri" value="hepsiburada">

                        <!-- Aktif/Pasif Switch -->
                        <div class="mb-3 form-check form-switch ps-0 d-flex justify-content-between align-items-center bg-light p-2.5 rounded-3">
                            <label class="form-check-label small fw-bold text-dark ms-2" for="hbAktif">Mağaza Durumu</label>
                            <input class="form-check-input style-switch me-0" type="checkbox" role="switch" id="hbAktif" name="aktif" value="1" {{ session('mp_hepsiburada_aktif', false) ? 'checked' : '' }}>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Kâr Oranı (%)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0 text-muted"><i class="fa-solid fa-percent"></i></span>
                                <input type="number" step="0.01" name="varsayilan_kar" class="form-control bg-light border-0 fw-bold text-dark" value="{{ session('mp_hepsiburada_kar', 28.00) }}" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Merchant Username</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0 text-muted"><i class="fa-solid fa-user"></i></span>
                                <input type="text" name="merchant_id" class="form-control bg-light border-0" placeholder="HB Mağaza Kullanıcı Adı" value="{{ session('mp_hepsiburada_merchant') }}">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-semibold text-secondary">API Şifresi / Token</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0 text-muted"><i class="fa-solid fa-lock"></i></span>
                                <input type="password" name="api_secret" class="form-control bg-light border-0" placeholder="••••••••••••••••" value="{{ session('mp_hepsiburada_secret') }}">
                            </div>
                        </div>

                        <button type="submit" class="btn w-100 fw-semibold text-white py-2.5 rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2" style="background-color: #ff6000; border: none;">
                            <i class="fa-solid fa-floppy-disk"></i> Hepsiburada Ayarlarını Kaydet
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- 3. Amazon Kutusu -->
        <div class="col-xl-4 col-md-12">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white overflow-hidden">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="rounded-3 p-2.5 me-3 fw-bold text-dark shadow-sm d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: linear-gradient(135deg, #ff9900, #e68a00);">
                                <i class="fa-brands fa-amazon fa-xl"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold text-dark mb-0">Amazon</h5>
                                <span class="text-muted small">SP-API Entegrasyonu</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">
                    <form action="{{ route('admin.marketplaces.muntehas.kar') }}" method="POST">
                        @csrf
                        <input type="hidden" name="pazaryeri" value="amazon">

                        <!-- Aktif/Pasif Switch -->
                        <div class="mb-3 form-check form-switch ps-0 d-flex justify-content-between align-items-center bg-light p-2.5 rounded-3">
                            <label class="form-check-label small fw-bold text-dark ms-2" for="amzAktif">Mağaza Durumu</label>
                            <input class="form-check-input style-switch me-0" type="checkbox" role="switch" id="amzAktif" name="aktif" value="1" {{ session('mp_amazon_aktif', false) ? 'checked' : '' }}>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Kâr Oranı (%)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0 text-muted"><i class="fa-solid fa-percent"></i></span>
                                <input type="number" step="0.01" name="varsayilan_kar" class="form-control bg-light border-0 fw-bold text-dark" value="{{ session('mp_amazon_kar', 30.00) }}" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Seller ID</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0 text-muted"><i class="fa-solid fa-store"></i></span>
                                <input type="text" name="merchant_id" class="form-control bg-light border-0" placeholder="Amazon Satıcı ID" value="{{ session('mp_amazon_merchant') }}">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-semibold text-secondary">SP-API Refresh Token</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0 text-muted"><i class="fa-solid fa-shield-halved"></i></span>
                                <input type="password" name="api_secret" class="form-control bg-light border-0" placeholder="••••••••••••••••" value="{{ session('mp_amazon_secret') }}">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-dark w-100 fw-semibold py-2.5 rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2">
                            <i class="fa-solid fa-floppy-disk"></i> Amazon Ayarlarını Kaydet
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>


<div class="container-fluid px-4 py-4">
    
    <!-- Başlık ve Yeni Ekle Butonu -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark">📦 Tedarikçi Yönetimi</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
            <i class="fas fa-plus me-1"></i> Yeni Tedarikçi Ekle
        </button>
    </div>

    <!-- Başarı / Hata Mesajları -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Tedarikçiler Tablosu -->
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#ID</th>
                            <th>Tedarikçi Adı</th>
                            <th>XML Adresi</th>
                            <th>Kâr Marjı (%)</th>
                            <th>Ürün Sayısı</th>
                            <th>Durum</th>
                            <th>Son Senkronizasyon</th>
                            <th class="text-end">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($suppliers as $supplier)
                            <tr>
                                <td>{{ $supplier->id }}</td>
                                <td class="fw-semibold">{{ $supplier->name }}</td>
                                <td>
                                    <a href="{{ $supplier->xml_url }}" target="_blank" class="text-truncate d-inline-block" style="max-width: 200px;">
                                        {{ $supplier->xml_url ?: 'Tanımsız' }}
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-info text-dark">%{{ $supplier->default_profit_margin }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $supplier->products_count }} Ürün</span>
                                </td>
                                <td>
                                    @if($supplier->is_active)
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-danger">Pasif</span>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted">
                                        {{ $supplier->last_synced_at ? \Carbon\Carbon::parse($supplier->last_synced_at)->diffForHumans() : 'Hiç Senkronize Edilmedi' }}
                                    </small>
                                </td>
                                <td class="text-end">
                                    <form action="{{ route('admin.suppliers.sync', $supplier->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-warning text-white">
                                            Senkronize Et
                                        </button>
                                    </form>

                                    <!-- Düzenle Butonu -->
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editSupplierModal{{ $supplier->id }}">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <!-- Sil Butonu -->
                                    <form action="{{ route('admin.suppliers.destroy', $supplier->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bu tedarikçiyi silmek istediğinize emin misiniz?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Sil">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>

                            <!-- Düzenleme Modalı (Her tedarikçi için ayrı) -->
                            <div class="modal fade" id="editSupplierModal{{ $supplier->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="{{ route('admin.suppliers.update', $supplier->id) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-header">
                                                <h5 class="modal-title">Tedarikçi Düzenle: {{ $supplier->name }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body text-start">
                                                <div class="mb-3">
                                                    <label class="form-label">Tedarikçi Adı</label>
                                                    <input type="text" name="name" class="form-control" value="{{ $supplier->name }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">XML URL Adresi</label>
                                                    <input type="url" name="xml_url" class="form-control" value="{{ $supplier->xml_url }}">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Varsayılan Kâr Marjı (%)</label>
                                                    <input type="number" step="0.01" name="default_profit_margin" class="form-control" value="{{ $supplier->default_profit_margin }}" required>
                                                </div>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActiveEdit{{ $supplier->id }}" {{ $supplier->is_active ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="isActiveEdit{{ $supplier->id }}">Tedarikçi Aktif (XML Çekimlerine Dahil Et)</label>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                                                <button type="submit" class="btn btn-primary">Değişiklikleri Kaydet</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">Sistemde kayıtlı tedarikçi bulunmuyor.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Yeni Tedarikçi Ekleme Modalı -->
<div class="modal fade" id="addSupplierModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.suppliers.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Tedarikçi Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start">
                    <div class="mb-3">
                        <label class="form-label">Tedarikçi Adı</label>
                        <input type="text" name="name" class="form-control" placeholder="Örn: X Tedarikçisi A.Ş." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">XML URL Adresi</label>
                        <input type="url" name="xml_url" class="form-control" placeholder="https://ornek.com/feed.xml">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Varsayılan Kâr Marjı (%)</label>
                        <input type="number" step="0.01" name="default_profit_margin" class="form-control" value="26.00" required>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActiveAdd" checked>
                        <label class="form-check-label" for="isActiveAdd">Tedarikçi Aktif</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Tedarikçiyi Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>
    <!-- ÜRÜN LİSTESİ TABLOSU -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
    <div class="card-header bg-white py-3 px-4 border-bottom border-light d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-boxes-stacked text-primary"></i>
            <h5 class="mb-0 fw-bold text-dark">Senkronize Ürün Listesi</h5>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border px-3 py-2 rounded-pill small fw-semibold me-2">
                Toplam: {{ isset($urunler) ? count($urunler) : 0 }} Ürün
            </span>
            
            <!-- DARALT / GENİŞLET BUTONLARI -->
            <button class="btn btn-sm btn-outline-danger px-3 py-2 rounded-pill fw-semibold" type="button" id="btnDaralt" style="display: none;">
                <i class="fa-solid fa-chevron-up me-1"></i> Daralt
            </button>
            <button class="btn btn-sm btn-outline-success px-3 py-2 rounded-pill fw-semibold" type="button" id="btnGenişlet">
                <i class="fa-solid fa-chevron-down me-1"></i> Genişlet
            </button>
        </div>
    </div>

    <!-- Başlangıçta gizli (kapalı) gelmesi için style="display: none;" eklendi -->
    <div id="urunlerTablosuContent" style="display: none;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap">
                    <thead class="bg-light text-uppercase fs-7 text-muted border-bottom">
                        <tr>
                            <th class="ps-4 py-3">SKU / Stok Kodu</th>
                            <th class="py-3">Ürün Adı</th>
                            <th class="py-3">Satış Fiyatı</th>
                            <th class="py-3">Stok Durumu</th>
                            <th class="pe-4 py-3 text-end">Son Güncelleme</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($urunler) && count($urunler) > 0)
                            @foreach($urunler as $row)
                                <tr>
                                    <td class="ps-4">
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border font-monospace px-2.5 py-1.5 rounded">
                                            {{ $row->sku ?? 'SKU-YOK' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $row->name }}</div>
                                    </td>
                                    <td>
                                        <span class="text-success fw-bold">
                                            {{ number_format($row->price, 2) }} ₺
                                        </span>
                                    </td>
                                    <td>
                                        @if($row->stock > 10)
                                            <span class="badge bg-success bg-opacity-10 text-success fw-bold px-3 py-1.5 rounded-pill">
                                                <i class="fa-solid fa-check me-1"></i> {{ $row->stock }} Adet
                                            </span>
                                        @elseif($row->stock > 0)
                                            <span class="badge bg-warning bg-opacity-10 text-warning fw-bold px-3 py-1.5 rounded-pill">
                                                <i class="fa-solid fa-triangle-exclamation me-1"></i> {{ $row->stock }} Adet (Kritik)
                                            </span>
                                        @else
                                            <span class="badge bg-danger bg-opacity-10 text-danger fw-bold px-3 py-1.5 rounded-pill">
                                                <i class="fa-solid fa-xmark me-1"></i> Stok Tükendi
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-muted small pe-4 text-end">
                                        <i class="fa-regular fa-clock me-1"></i>
                                        {{ $row->updated_at ? $row->updated_at->format('d.m.Y H:i') : '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <div class="py-4">
                                        <div class="rounded-circle bg-light d-inline-flex p-4 mb-3">
                                            <i class="fa-solid fa-box-open fa-3x text-muted opacity-50"></i>
                                        </div>
                                        <h6 class="fw-bold text-dark mb-1">Henüz Aktarılmış Ürün Bulunmuyor</h6>
                                        <p class="small text-muted mb-0">Yukarıdaki XML senkronizasyon alanını kullanarak ürünlerinizi yönetebilirsiniz.</p>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Butonların çalışmasını sağlayan ufak JavaScript kod parçacığı -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const content = document.getElementById('urunlerTablosuContent');
        const btnDaralt = document.getElementById('btnDaralt');
        const btnGenişlet = document.getElementById('btnGenişlet');

        btnGenişlet.addEventListener('click', function () {
            content.style.display = 'block';
            btnGenişlet.style.display = 'none';
            btnDaralt.style.display = 'inline-block';
        });

        btnDaralt.addEventListener('click', function () {
            content.style.display = 'none';
            btnDaralt.style.display = 'none';
            btnGenişlet.style.display = 'inline-block';
        });
    });
</script>

</div>

<!-- ÖZEL STİLLER -->
<style>
    .border-dashed {
        border-style: dashed !important;
    }
    .style-dropzone {
        border: 2px dashed #cbd5e1;
        transition: all 0.2s ease-in-out;
    }
    .style-dropzone:hover {
        border-color: #0d6efd;
        background-color: #f1f5f9 !important;
    }
    .style-switch {
        width: 2.75em !important;
        height: 1.4em !important;
        cursor: pointer;
    }
    .transition-all {
        transition: transform 0.2s ease, shadow 0.2s ease;
    }
    .transition-all:hover {
        transform: translateY(-2px);
    }
</style>

<!-- Saf JavaScript Kontrol Scriptleri -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // 1. Aktif/Pasif switch butonlarına tıklandığında formu otomatik kaydetme
        const switches = document.querySelectorAll('input[name="aktif"]');
        switches.forEach(function (sw) {
            sw.addEventListener('change', function () {
                this.closest('form').submit();
            });
        });

        // 2. Daralt ve Genişlet butonları için çakışmasız özel kontrol
        const tabloContent = document.getElementById('urunlerTablosuContent');

        document.getElementById('btnDaralt').addEventListener('click', function () {
            tabloContent.style.display = 'none';
        });

        document.getElementById('btnGenişlet').addEventListener('click', function () {
            tabloContent.style.display = 'block';
        });
    });
</script>
@endsection