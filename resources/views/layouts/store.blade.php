<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Muntehas')</title>
    <link rel="icon" type="image/png" href="{{ asset('img/logo/link_logo.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        greenPrimary: '#059669',
                        greenDark: '#022c22',
                        greenLight: '#ecfdf5'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-[#f8faf8] min-h-screen text-slate-800 antialiased flex flex-col justify-between selection:bg-emerald-700 selection:text-white" x-data="{ mobileMenu: false, mobileCatOpen: false }">

<nav class="bg-white/90 backdrop-blur-md border-b border-emerald-100 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-6 sm:px-12 h-20 flex justify-between items-center">
            <a href="{{ url('/') }}" class="flex items-center">
                <img src="{{ asset('img/logo/logo.png') }}" alt="Muntehas" class="h-10 sm:h-14 w-auto object-contain">
            </a>

<!-- Masaüstü Menü -->
<div class="hidden md:flex items-center space-x-6 text-xs font-semibold tracking-widest text-slate-700 uppercase">
    <a href="{{ url('/') }}" class="text-emerald-700 hover:text-emerald-900 transition">ANASAYFA</a>

    @php
        $navCategories = \App\Models\Category::orderBy('name')->get();
        $menuCategories = $navCategories->where('show_in_menu', true);
    @endphp

    <!-- Tüm Kategoriler Dropdown Menü -->
    @if($navCategories->count() > 0)
        <div class="relative" x-data="{ catDropdown: false }" @mouseleave="catDropdown = false">
            <button @mouseover="catDropdown = true" @click="catDropdown = !catDropdown" class="flex items-center gap-1.5 hover:text-emerald-600 transition py-2 focus:outline-none">
                <span>KATEGORİLER</span>
                <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="catDropdown ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <!-- Açılır Kategori Kutusu -->
            <div x-show="catDropdown" 
                 x-cloak 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 translate-y-2"
                 class="absolute top-full left-0 w-56 max-h-80 overflow-y-auto bg-white border border-slate-200 rounded-2xl shadow-xl py-3 px-2 space-y-1 z-50">
                @foreach($navCategories as $navCat)
                    <a href="{{ url('/?category=' . $navCat->slug . '#urunler') }}" 
                       class="block px-3.5 py-2 rounded-xl text-[11px] font-semibold text-slate-600 hover:bg-emerald-50 hover:text-emerald-700 transition">
                        @if($navCat->show_in_menu) ✨ @endif {{ $navCat->name }}
                    </a>
                @endforeach
            </div>
        </div> <!-- ✅ Eksik olan dropdown kapsayıcı kapanışı buraya eklendi -->
    @endif

    <!-- Özel Vurgulu Menü Kategorileri -->
    @foreach($menuCategories as $menuCat)
        <a href="{{ url('/?category=' . $menuCat->slug . '#urunler') }}" 
           class="group relative inline-flex items-center gap-2 px-3.5 py-1.5 rounded-2xl bg-gradient-to-r from-amber-500/10 via-rose-500/10 to-amber-500/10 border border-amber-300 text-amber-900 font-extrabold hover:shadow-md hover:shadow-amber-500/20 hover:scale-105 transition-all duration-300">

            <!-- Yanıp Sönen Sinyal Noktası -->
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-600"></span>
            </span>

            <span>{{ $menuCat->name }}</span>

            <!-- Minik Parlayan "ÖZEL" Rozeti -->
            <span class="bg-gradient-to-r from-rose-600 to-amber-600 text-white text-[8px] font-black px-1.5 py-0.5 rounded-full shadow-xs animate-pulse">
                ÖZEL
            </span>
        </a>
    @endforeach

    <a href="{{ route('contact') }}" class="hover:text-emerald-700 transition">İLETİŞİM</a>

    @auth('admin')
        <a href="{{ route('admin.dashboard') }}" class="bg-emerald-800 text-white px-5 py-2.5 rounded-full hover:bg-emerald-900 transition text-[11px]">Panel</a>
    @else
        <a href="{{ route('admin.login') }}" class="border border-emerald-700 text-emerald-800 px-5 py-2.5 rounded-full hover:bg-emerald-800 hover:text-white transition text-[11px]">Giriş Yap</a>
    @endauth
</div>
            <!-- Mobil Hamburger Butonu -->
            <div class="md:hidden flex items-center">
                <button @click="mobileMenu = !mobileMenu" class="p-2.5 rounded-2xl bg-emerald-50 text-emerald-900 border border-emerald-200 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path x-show="!mobileMenu" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        <path x-show="mobileMenu" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- MOBİL AÇILIR MENÜ -->
        <div x-show="mobileMenu" x-cloak 
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 -translate-y-2"
     x-transition:enter-end="opacity-100 translate-y-0"
     class="md:hidden bg-white border-b border-emerald-100 px-6 py-6 space-y-4 shadow-2xl">
    
    <a href="{{ url('/') }}" class="block text-sm font-bold text-emerald-800">ANASAYFA</a>

    @php
        $allMobileCategories = \App\Models\Category::orderBy('name')->get();
        $menuCategories = $allMobileCategories->where('show_in_menu', true);
    @endphp

    <!-- Tüm Kategoriler (Açılır / Kapanır) -->
    @if($allMobileCategories->count() > 0)
        <div x-data="{ mobileCatOpen: false }" class="border-y border-slate-100 py-2">
            <button @click="mobileCatOpen = !mobileCatOpen" class="w-full flex items-center justify-between text-sm font-bold text-slate-700 py-1 focus:outline-none">
                <span>KATEGORİLER</span>
                <svg class="w-4 h-4 transition-transform duration-200" :class="mobileCatOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="mobileCatOpen" x-cloak class="mt-2 space-y-2 max-h-60 overflow-y-auto pl-2">
                @foreach($allMobileCategories as $cat)
                    <a href="{{ url('/?category=' . $cat->slug . '#urunler') }}" 
                       @click="mobileMenu = false"
                       class="block text-xs font-semibold text-slate-600 hover:text-emerald-700 py-1">
                        @if($cat->show_in_menu) ✨ @endif {{ $cat->name }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Özel Menü Kategorileri -->
    @if($menuCategories->count() > 0)
        <div class="border border-amber-100 py-3 my-2 bg-amber-50/40 px-3 rounded-xl">
            <span class="text-[10px] font-bold text-amber-700 uppercase tracking-wider block mb-2">✨ Özel Menü Kategorileri</span>
            <div class="space-y-2">
                @foreach($menuCategories as $menuCat)
                    <a href="{{ url('/?category=' . $menuCat->slug . '#urunler') }}" 
                       @click="mobileMenu = false"
                       class="block text-xs font-bold text-slate-700 hover:text-amber-600 pl-2">
                        • {{ $menuCat->name }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <a href="{{ route('contact') }}" class="block text-sm font-bold text-slate-700">İLETİŞİM</a>

    <div class="pt-2 border-t border-slate-100">
        @auth('admin')
            <a href="{{ route('admin.dashboard') }}" class="block text-center bg-emerald-800 text-white py-3 rounded-2xl text-xs font-bold uppercase tracking-wider shadow-md">Panel</a>
        @else
            <a href="{{ route('admin.login') }}" class="block text-center bg-emerald-50 text-emerald-900 border border-emerald-200 py-3 rounded-2xl text-xs font-bold uppercase tracking-wider">Giriş Yap</a>
        @endauth
    </div>

</div>
    </nav>

    <!-- SAYFA İÇERİĞİ (Veriler ve Blade Yapısı Kesinlikle Korundu) -->
    <main class="flex-1">
        @yield('content')
    </main>

<footer class="bg-emerald-950 text-emerald-200/80 pt-16 pb-12 border-t border-emerald-900 mt-20">
    <div class="max-w-7xl mx-auto px-6 sm:px-12 grid grid-cols-1 md:grid-cols-4 gap-10 mb-12">
        <div class="space-y-3">
            <h3 class="text-white font-bold text-xs tracking-widest uppercase">MUNTEHAS</h3>
            <p class="text-xs font-light leading-relaxed">
                Seçkin ürün kataloğumuz ve güvenilir e-ticaret partnerlerimizle hizmetinizdeyiz.
            </p>
        </div>

        <div>
            <h4 class="text-white font-bold text-xs uppercase tracking-widest mb-4">Hızlı Bağlantılar</h4>
            <ul class="space-y-2 text-xs font-light">
                <li><a href="{{ url('/') }}" class="hover:text-white transition">Anasayfa</a></li>
                <li><a href="{{ url('/#urunler') }}" class="hover:text-white transition">Tüm Ürünler</a></li>
                <li><a href="{{ route('contact') }}" class="hover:text-white transition">İletişim</a></li>
            </ul>
        </div>

        <div>
            <h4 class="text-white font-bold text-xs uppercase tracking-widest mb-4">Kategoriler</h4>
            <ul class="space-y-2 text-xs font-light">
                @if(isset($menuCategories) && count($menuCategories) > 0)
                    @foreach($menuCategories as $menuCat)
                        <li>
                            <a href="{{ url('/?category=' . $menuCat->slug . '#urunler') }}" class="hover:text-white transition">
                                {{ $menuCat->name }}
                            </a>
                        </li>
                    @endforeach
                @else
                    <li class="text-slate-400">Özel kategori bulunmuyor.</li>
                @endif
            </ul>
        </div>
    <div>
            <h3 class="font-bold text-xs uppercase tracking-widest mb-4 text-white">Sosyal Medya Hesaplarımız</h3>
                <ul>
                <!-- 1. INSTAGRAM -->
                <li>
                    <a href="https://www.instagram.com/muntehaas?vrfl=ZzhvMXU3MzcxMm1p" target="_blank" class="hover:text-white transition flex items-center gap-2 group">
                        <svg class="w-4 h-4 text-brandOrange group-hover:scale-110 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <rect width="20" height="20" x="2" y="2" rx="5" ry="5"/>
                            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/>
                            <line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/>
                        </svg>
                        <span>Instagram (@muntehaas)</span>
                    </a>
                </li>

                <!-- 2. FACEBOOK -->
                <li class="mt-2">
                    <a href="https://www.facebook.com/profile.php?id=61596509737357" target="_blank" class="hover:text-white transition flex items-center gap-2 group">
                        <svg class="w-4 h-4 text-brandOrange group-hover:scale-110 transition" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                        <span>Facebook (Munte Has)</span>
                    </a>
                </li>

                <!-- 3. TIKTOK -->
                <!-- <li class="mt-2">
                    <a href="https://www.tiktok.com/@muntehas.tr" target="_blank" class="hover:text-white transition flex items-center gap-2 group">
                        <svg class="w-4 h-4 text-brandOrange group-hover:scale-110 transition" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.13z"/>
                        </svg>
                        <span>TikTok (@muntehas.tr)</span>
                    </a>
                </li> -->
            </ul>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-6 sm:px-12 pt-8 border-t border-emerald-900/60 flex flex-col sm:flex-row items-center justify-between text-xs font-light">
        <p>&copy; {{ date('Y') }} Muntehas. Tüm hakları saklıdır.</p>
        <a href="https://edaoncel.github.io/edaoncell/project.html" target="_blank" rel="noopener noreferrer" class="mt-4 hover:text-white transition duration-300 flex items-center gap-2 group">
            <span class="text-gray-300">Muntehas Tasarım & Geliştirme:</span>
            <span class="font-bold text-white underline underline-offset-4 decoration-brandOrange group-hover:text-brandOrange transition tracking-wider">Eda Öncel</span>
        </a>
    </div>
</footer>

</body>
</html>