<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Muntehas Admin Panel')</title>
    <link rel="icon" type="image/png" href="{{ asset('img/logo/link_logo.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brandBlue: '#0ea5e9',
                        brandDark: '#1e293b',
                        brandOrange: '#f97316'
                    }
                }
            }
        }
    </script>

    <!-- 🕒 3 Dakika (180 Saniye) Sonra Bildirimi Otomatik Kapatan Script -->
    <script>
        setTimeout(function() {
            let alertElement = document.getElementById('auto-dismiss-alert');
            if (alertElement) {
                let bsAlert = new bootstrap.Alert(alertElement);
                bsAlert.close();
            }
        }, 180000); // 180 saniye = 3 dakika
    </script>
    <!-- GLOBAL BİLDİRİM KUTUSU (Her sayfada çalışır) -->
    <div id="global-sync-notification" class="fixed top-5 right-5 z-50 max-w-md" style="display: none;">
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl shadow-lg flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="flex-1 text-sm" id="global-sync-notification-text"></div>
            <button type="button" onclick="document.getElementById('global-sync-notification').style.display='none'" class="text-emerald-500 hover:text-emerald-700">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const TOTAL_TIME = 1800; // 30 dakika = 1800 saniye
        const now = Math.floor(Date.now() / 1000);
        let targetTime = localStorage.getItem('sync_target_time');

        if (!targetTime || parseInt(targetTime) <= now || parseInt(targetTime) > (now + TOTAL_TIME + 5)) {
            targetTime = now + TOTAL_TIME;
            localStorage.setItem('sync_target_time', targetTime);
        } else {
            targetTime = parseInt(targetTime);
        }

        const fnFormatTime = (seconds) => {
            let minutes = Math.floor(seconds / 60);
            let remainingSeconds = seconds % 60;
            return minutes.toString().padStart(2, '0') + ':' + remainingSeconds.toString().padStart(2, '0');
        };

        const updateGlobalTimer = () => {
            const currentTime = Math.floor(Date.now() / 1000);
            let timeLeft = targetTime - currentTime;

            if (timeLeft <= 0) {
                timeLeft = 0;
                clearInterval(window.globalSyncInterval);
                
                targetTime = Math.floor(Date.now() / 1000) + TOTAL_TIME;
                localStorage.setItem('sync_target_time', targetTime);

                // Otomatik senkronizasyonu tetikle
                fetch('{{ route("admin.suppliers.syncAll") }}', {
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
                    // Bildirimi global kutuda göster
                    const notificationBox = document.getElementById('global-sync-notification');
                    const notificationText = document.getElementById('global-sync-notification-text');
                    
                    if (notificationBox && notificationText && data.message) {
                        notificationText.innerHTML = data.message;
                        notificationBox.style.display = 'block';

                        // 8 saniye sonra bildirimi otomatik kapat
                        setTimeout(() => {
                            notificationBox.style.display = 'none';
                        }, 8000);
                    }

                    window.globalSyncInterval = setInterval(updateGlobalTimer, 1000);
                })
                .catch(error => {
                    console.error('Arka plan senkronizasyon hatası:', error);
                    window.globalSyncInterval = setInterval(updateGlobalTimer, 1000);
                });

                return;
            }

            // Sadece ayarlar sayfasındaysak sayaç elementlerini güncelle
            const timerDisplay = document.getElementById('countdown-timer');
            const progressBar = document.getElementById('progress-bar');

            if (timerDisplay) {
                timerDisplay.textContent = fnFormatTime(timeLeft);
                timerDisplay.style.visibility = 'visible';
            }
            if (progressBar) {
                let progressPercent = (timeLeft / TOTAL_TIME) * 100;
                progressBar.style.width = progressPercent + '%';
            }
        };

        updateGlobalTimer();
        window.globalSyncInterval = setInterval(updateGlobalTimer, 1000);
    });
</script>
</head>
<body class="bg-slate-100 min-h-screen text-gray-800 antialiased" x-data="{ sidebarOpen: false }">

    <div class="min-h-screen flex">
        
        <aside class="hidden md:flex flex-col w-64 bg-brandDark text-white flex-shrink-0 sticky top-0 h-screen">
            
            <div class="h-16 flex items-center justify-between px-6 bg-slate-900/50 border-b border-slate-800">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                    <img src="{{ asset('img/logo/logo.png') }}" alt="Muntehas" class="h-14 w-auto">
                </a>
            </div>

            <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto">
                <a href="{{ route('admin.dashboard') }}" 
                @if(isset($updatedTodayCount) && $updatedTodayCount > 0) onclick="playBeep(event)" @endif
                class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.dashboard') ? 'bg-brandOrange text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    
                    <span>Panel Anasayfa</span>
                    
                    @if(isset($updatedTodayCount) && $updatedTodayCount > 0)
                        <span class="bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full animate-pulse shadow-sm ml-auto">
                            {{ $updatedTodayCount }}
                        </span>
                    @endif
                </a>

                {{-- Eğer bu script layoutunuzda (şablonun altında) zaten ekliyse tekrar eklemenize gerek yoktur --}}
                @if(isset($updatedTodayCount) && $updatedTodayCount > 0)
                <script>
                    function playBeep(event) {
                        try {
                            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                            const oscillator = audioCtx.createOscillator();
                            const gainNode = audioCtx.createGain();

                            // Tiz ve dikkat çekici bip sesi (1200 Hz)
                            oscillator.type = 'sine'; 
                            oscillator.frequency.setValueAtTime(1200, audioCtx.currentTime); 

                            // Ses seviyesi ve süresi (0.3 saniye)
                            gainNode.gain.setValueAtTime(0.2, audioCtx.currentTime); 
                            gainNode.gain.exponentialRampToValueAtTime(0.00001, audioCtx.currentTime + 0.3); 

                            oscillator.connect(gainNode);
                            gainNode.connect(audioCtx.destination);

                            oscillator.start();
                            oscillator.stop(audioCtx.currentTime + 0.3);
                        } catch (e) {
                            console.error("Audio Context başlatılamadı:", e);
                        }
                    }
                </script>
                @endif

                <a href="{{ route('admin.products.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.products.*') ? 'bg-brandOrange text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    Ürün Yönetimi
                </a>

                <a href="{{ route('admin.categories.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.categories.*') ? 'bg-brandOrange text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 11h.01M7 15h.01M11 7h8M11 11h8M11 15h8"></path></svg>
                    Kategori Yönetimi
                </a>

                <a href="{{ route('admin.marketplaces.settings') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.marketplaces.*') ? 'bg-brandOrange text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                    Pazaryeri Yönetimi
                </a>

                <a href="{{ route('admin.admins.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.admins.*') ? 'bg-brandOrange text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Yöneticiler
                </a>

                @php
                    $unreadCount = \App\Models\ContactMessage::where('is_read', false)->count();
                @endphp

                <a href="{{ route('admin.messages.index') }}" 
                @if($unreadCount > 0) onclick="playMessageBeep(event)" @endif
                class="flex items-center justify-between px-4 py-3 rounded-xl hover:bg-slate-800 transition text-sm font-semibold text-slate-300 hover:text-white">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <span>Gelen Mesajlar</span>
                    </div>
                    
                    @if($unreadCount > 0)
                        <span class="bg-rose-500 text-white text-[10px] font-black px-2.5 py-0.5 rounded-full shadow-sm animate-pulse">
                            {{ $unreadCount }}
                        </span>
                    @endif
                </a>

                {{-- Mesajlar için özel, daha tok ve farklı tonlu bildirim sesi (Frekans: 600Hz) --}}
                @if($unreadCount > 0)
                <script>
                    function playMessageBeep(event) {
                        try {
                            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                            const oscillator = audioCtx.createOscillator();
                            const gainNode = audioCtx.createGain();

                            // Ürün bildiriminden farklı olarak daha tok/kalın bir ses tonu (600 Hz)
                            oscillator.type = 'sine'; 
                            oscillator.frequency.setValueAtTime(600, audioCtx.currentTime); 

                            // Ses seviyesi ve süresi (0.4 saniye - biraz daha uzun ve tok)
                            gainNode.gain.setValueAtTime(0.25, audioCtx.currentTime); 
                            gainNode.gain.exponentialRampToValueAtTime(0.00001, audioCtx.currentTime + 0.4); 

                            oscillator.connect(gainNode);
                            gainNode.connect(audioCtx.destination);

                            oscillator.start();
                            oscillator.stop(audioCtx.currentTime + 0.4);
                        } catch (e) {
                            console.error("Audio Context başlatılamadı:", e);
                        }
                    }
                </script>
                @endif

                {{-- Mesajlar için özel, daha tok ve farklı tonlu bildirim sesi (Frekans: 600Hz) --}}
                @if($unreadCount > 0)
                <script>
                    function playMessageBeep(event) {
                        try {
                            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                            const oscillator = audioCtx.createOscillator();
                            const gainNode = audioCtx.createGain();

                            // Ürün bildiriminden farklı olarak daha tok/kalın bir ses tonu (600 Hz)
                            oscillator.type = 'sine'; 
                            oscillator.frequency.setValueAtTime(600, audioCtx.currentTime); 

                            // Ses seviyesi ve süresi (0.4 saniye - biraz daha uzun ve tok)
                            gainNode.gain.setValueAtTime(0.25, audioCtx.currentTime); 
                            gainNode.gain.exponentialRampToValueAtTime(0.00001, audioCtx.currentTime + 0.4); 

                            oscillator.connect(gainNode);
                            gainNode.connect(audioCtx.destination);

                            oscillator.start();
                            oscillator.stop(audioCtx.currentTime + 0.4);
                        } catch (e) {
                            console.error("Audio Context başlatılamadı:", e);
                        }
                    }
                </script>
                @endif

                <a href="{{ route('admin.visitors') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.visitors') ? 'bg-brandOrange text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    Ziyaretçi Logları
                </a>

                <div class="pt-4 border-t border-slate-800 my-2"></div>

                <a href="{{ url('/') }}" target="_blank" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold text-slate-300 hover:bg-slate-800 hover:text-white transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    Siteye Git
                </a>
            </nav>

            <div class="p-4 bg-slate-900/50 border-t border-slate-800 flex items-center justify-between">
                <div class="text-xs text-slate-400 truncate">
                    <span class="block font-bold text-white">{{ Auth::guard('admin')->user()->name ?? 'Yönetici' }}</span>
                    Muntehas Panel v1.0
                </div>
                <form action="{{ route('admin.logout') }}" method="POST">
                    @csrf
                    <button type="submit" title="Çıkış Yap" class="p-2 bg-slate-800 hover:bg-red-600 text-slate-300 hover:text-white rounded-xl transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    </button>
                </form>
            </div>
        </aside>

        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 bg-black/50 z-40 md:hidden backdrop-blur-sm"></div>

        <aside x-show="sidebarOpen" x-cloak 
               x-transition:enter="transition ease-out duration-300 transform" 
               x-transition:enter-start="-translate-x-full" 
               x-transition:enter-end="translate-x-0" 
               x-transition:leave="transition ease-in duration-300 transform" 
               x-transition:leave-start="translate-x-0" 
               x-transition:leave-end="-translate-x-full" 
               class="fixed inset-y-0 left-0 w-64 bg-brandDark text-white z-50 md:hidden flex flex-col shadow-2xl">
            <div class="h-16 flex items-center justify-between px-6 bg-slate-900/50 border-b border-slate-800">
                <span class="font-bold text-white text-lg">Muntehas Panel</span>
                <button @click="sidebarOpen = false" class="text-slate-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto">
                <a href="{{ route('admin.dashboard') }}" 
                @if(isset($updatedTodayCount) && $updatedTodayCount > 0) onclick="playBeep(event)" @endif
                class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.dashboard') ? 'bg-brandOrange text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    
                    <span>Panel Anasayfa</span>
                    
                    @if(isset($updatedTodayCount) && $updatedTodayCount > 0)
                        <span class="bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full animate-pulse shadow-sm ml-auto">
                            {{ $updatedTodayCount }}
                        </span>
                    @endif
                </a>

                {{-- Eğer bu script layoutunuzda (şablonun altında) zaten ekliyse tekrar eklemenize gerek yoktur --}}
                @if(isset($updatedTodayCount) && $updatedTodayCount > 0)
                <script>
                    function playBeep(event) {
                        try {
                            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                            const oscillator = audioCtx.createOscillator();
                            const gainNode = audioCtx.createGain();

                            // Tiz ve dikkat çekici bip sesi (1200 Hz)
                            oscillator.type = 'sine'; 
                            oscillator.frequency.setValueAtTime(1200, audioCtx.currentTime); 

                            // Ses seviyesi ve süresi (0.3 saniye)
                            gainNode.gain.setValueAtTime(0.2, audioCtx.currentTime); 
                            gainNode.gain.exponentialRampToValueAtTime(0.00001, audioCtx.currentTime + 0.3); 

                            oscillator.connect(gainNode);
                            gainNode.connect(audioCtx.destination);

                            oscillator.start();
                            oscillator.stop(audioCtx.currentTime + 0.3);
                        } catch (e) {
                            console.error("Audio Context başlatılamadı:", e);
                        }
                    }
                </script>
                @endif

                <a href="{{ route('admin.products.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.products.*') ? 'bg-brandOrange text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    Ürün Yönetimi
                </a>

                <a href="{{ route('admin.categories.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.categories.*') ? 'bg-brandOrange text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 11h.01M7 15h.01M11 7h8M11 11h8M11 15h8"></path></svg>
                    Kategori Yönetimi
                </a>

                <a href="{{ route('admin.marketplaces.settings') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.marketplaces.*') ? 'bg-brandOrange text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                    Pazaryeri Yönetimi
                </a>

                <a href="{{ route('admin.admins.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.admins.*') ? 'bg-brandOrange text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Yöneticiler
                </a>

                <a href="{{ route('admin.messages.index') }}" class="flex items-center justify-between px-4 py-3 rounded-xl hover:bg-slate-800 transition text-sm font-semibold text-slate-300 hover:text-white">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <span>Gelen Mesajlar</span>
                    </div>
                    @if($unreadCount > 0)
                        <span class="bg-rose-500 text-white text-[10px] font-black px-2.5 py-0.5 rounded-full shadow-sm animate-pulse">
                            {{ $unreadCount }}
                        </span>
                    @endif
                </a>

                <a href="{{ route('admin.visitors') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.visitors') ? 'bg-brandOrange text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    Ziyaretçi Logları
                </a>

                <div class="pt-4 border-t border-slate-800 my-2"></div>

                <a href="{{ url('/') }}" target="_blank" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold text-slate-300 hover:bg-slate-800 hover:text-white transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    Siteye Git
                </a>
            </nav>
        </aside>

        <div class="flex-1 flex flex-col min-w-0">
            <header class="md:hidden h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 sticky top-0 z-30 shadow-sm">
                <button @click="sidebarOpen = true" class="p-2 text-gray-600 hover:text-gray-900 rounded-lg focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <img src="{{ asset('img/logo/logo.png') }}" alt="Muntehas" class="h-14 w-auto">
                
                <form action="{{ route('admin.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-xs font-bold text-red-500 p-2">Çıkış</button>
                </form>
            </header>

            <main class="flex-1 p-4 sm:p-8">
                @yield('content')
            </main>
        </div>

    </div>

</body>
</html>