<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>@yield('title', 'Peminjaman BMN') — BBLSDM Komdigi Makassar</title>

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: #F8FAFC;
        }
        .tabular-nums {
            font-variant-numeric: tabular-nums;
        }
        /* Custom thin scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #E2E8F0;
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #CBD5E1;
        }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full flex flex-col text-slate-800 antialiased"
      x-data="{
          sidebarCollapsed: localStorage.getItem('bmn_sidebar_collapsed') === 'true',
          sidebarMobileOpen: false,
          toggleCollapse() {
              this.sidebarCollapsed = !this.sidebarCollapsed;
              localStorage.setItem('bmn_sidebar_collapsed', this.sidebarCollapsed);
              this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
          }
      }">

    <!-- MOBILE BACKDROP OVERLAY -->
    <div x-show="sidebarMobileOpen"
         @click="sidebarMobileOpen = false"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         x-cloak
         class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-40 lg:hidden"></div>

    <!-- LEFT SIDEBAR NAVIGATION -->
    <aside class="fixed top-0 bottom-0 left-0 z-40 bg-white border-r border-slate-200 flex flex-col transition-all duration-300 ease-in-out shadow-sm select-none overflow-visible"
           :class="{
               'w-64': !sidebarCollapsed,
               'w-20': sidebarCollapsed,
               'translate-x-0 w-72': sidebarMobileOpen,
               '-translate-x-full lg:translate-x-0': !sidebarMobileOpen
           }">

        <!-- Desktop Collapse / Expand Arrow Button (Di Antara Logo dan Beranda) -->
        <button type="button"
                @click="toggleCollapse()"
                class="hidden lg:flex absolute -right-3.5 top-[50px] lg:top-[66px] z-50 w-7 h-7 bg-white border border-slate-200 hover:border-[#0B63B5] text-slate-500 hover:text-[#0B63B5] rounded-full shadow-md items-center justify-center transition-all duration-200 focus:outline-none hover:scale-110 active:scale-95 cursor-pointer"
                :title="sidebarCollapsed ? 'Perlebar Sidebar (Expand)' : 'Perkecil Sidebar (Minimize)'">
            <i data-lucide="chevron-left"
               class="w-4 h-4 transition-transform duration-300"
               :class="sidebarCollapsed ? 'rotate-180' : ''"></i>
        </button>

        <!-- Sidebar Header (Brand Logo) -->
        <div class="h-16 lg:h-20 px-4 border-b border-slate-100 flex items-center shrink-0 relative transition-all duration-300"
             :class="sidebarCollapsed && !sidebarMobileOpen ? 'justify-center px-0' : 'justify-between px-4'">
            <!-- Brand Logo -->
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 overflow-hidden">
                <div class="w-10 h-10 lg:w-11 lg:h-11 rounded-xl bg-gradient-to-tr from-[#0B63B5] to-[#12A8E0] flex items-center justify-center text-white font-black text-xl shadow-md shadow-blue-500/20 shrink-0">
                    B
                </div>
                <div x-show="!sidebarCollapsed || sidebarMobileOpen"
                     x-transition:enter="transition-opacity duration-150 delay-75"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition-opacity duration-75"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="truncate whitespace-nowrap">
                    <span class="font-extrabold text-slate-900 text-base lg:text-lg tracking-tight flex items-center gap-1">
                        BMN <span class="text-[#0B63B5]">Makassar</span>
                    </span>
                    <span class="text-[10px] lg:text-[11px] uppercase tracking-wider text-slate-500 font-semibold block -mt-0.5 truncate">
                        BBLSDM Komdigi
                    </span>
                </div>
            </a>

            <!-- Mobile Close Button -->
            <button type="button" @click="sidebarMobileOpen = false" class="lg:hidden p-1.5 rounded-xl hover:bg-slate-100 text-slate-400 hover:text-slate-700">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Sidebar Navigation Items (Scrollable) -->
        <nav class="flex-1 px-3 py-4 space-y-5 custom-scrollbar"
             :class="sidebarCollapsed && !sidebarMobileOpen ? 'overflow-visible' : 'overflow-y-auto'">

            <!-- GROUP 1: MENU UTAMA -->
            <div class="space-y-1">
                <div x-show="!sidebarCollapsed || sidebarMobileOpen"
                     x-transition:enter="transition-opacity duration-150 delay-75"
                     x-transition:leave="transition-opacity duration-75"
                     class="px-3 py-1 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 truncate whitespace-nowrap">
                    Menu Utama
                </div>

                <!-- Beranda -->
                <a href="{{ route('dashboard') }}"
                   class="flex items-center rounded-xl h-10 transition-colors group relative select-none {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-[#0B63B5] font-extrabold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-bold' }}"
                   :class="sidebarCollapsed && !sidebarMobileOpen ? 'justify-center px-0' : 'px-3 gap-3'">
                    <div class="w-6 h-6 flex items-center justify-center shrink-0">
                        <i data-lucide="layout-dashboard" class="w-5 h-5 {{ request()->routeIs('dashboard') ? 'text-[#0B63B5]' : 'text-slate-500 group-hover:text-slate-800' }}"></i>
                    </div>
                    <span x-show="!sidebarCollapsed || sidebarMobileOpen"
                          x-transition:enter="transition-opacity duration-150 delay-100"
                          x-transition:enter-start="opacity-0"
                          x-transition:enter-end="opacity-100"
                          x-transition:leave="transition-opacity duration-75"
                          x-transition:leave-start="opacity-100"
                          x-transition:leave-end="opacity-0"
                          class="truncate whitespace-nowrap text-xs">
                        Beranda
                    </span>

                    <!-- Floating Tooltip (Hanya tampil saat navbar di-minimize) -->
                    <div x-show="sidebarCollapsed && !sidebarMobileOpen"
                         x-cloak
                         class="absolute left-full top-1/2 -translate-y-1/2 ml-3.5 px-3 py-1.5 bg-slate-900/95 text-white text-xs font-bold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 translate-x-1 group-hover:translate-x-0 transition-all duration-150 z-50 flex items-center gap-1.5">
                        <span class="absolute -left-1 top-1/2 -translate-y-1/2 w-2 h-2 bg-slate-900/95 rotate-45"></span>
                        <span class="relative z-10">Beranda</span>
                    </div>
                </a>

                <!-- Katalog BMN -->
                <a href="{{ route('assets.index') }}"
                   class="flex items-center rounded-xl h-10 transition-colors group relative select-none {{ request()->routeIs('assets.*') ? 'bg-blue-50 text-[#0B63B5] font-extrabold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-bold' }}"
                   :class="sidebarCollapsed && !sidebarMobileOpen ? 'justify-center px-0' : 'px-3 gap-3'">
                    <div class="w-6 h-6 flex items-center justify-center shrink-0">
                        <i data-lucide="package" class="w-5 h-5 {{ request()->routeIs('assets.*') ? 'text-[#0B63B5]' : 'text-slate-500 group-hover:text-slate-800' }}"></i>
                    </div>
                    <span x-show="!sidebarCollapsed || sidebarMobileOpen"
                          x-transition:enter="transition-opacity duration-150 delay-100"
                          x-transition:enter-start="opacity-0"
                          x-transition:enter-end="opacity-100"
                          x-transition:leave="transition-opacity duration-75"
                          x-transition:leave-start="opacity-100"
                          x-transition:leave-end="opacity-0"
                          class="truncate whitespace-nowrap text-xs">
                        Katalog BMN
                    </span>

                    <!-- Floating Tooltip -->
                    <div x-show="sidebarCollapsed && !sidebarMobileOpen"
                         x-cloak
                         class="absolute left-full top-1/2 -translate-y-1/2 ml-3.5 px-3 py-1.5 bg-slate-900/95 text-white text-xs font-bold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 translate-x-1 group-hover:translate-x-0 transition-all duration-150 z-50 flex items-center gap-1.5">
                        <span class="absolute -left-1 top-1/2 -translate-y-1/2 w-2 h-2 bg-slate-900/95 rotate-45"></span>
                        <span class="relative z-10">Katalog BMN</span>
                    </div>
                </a>

                <!-- Ajukan Peminjaman (Quick Action) -->
                <a href="{{ route('loans.create') }}"
                   class="flex items-center rounded-xl h-10 transition-colors group relative select-none {{ request()->routeIs('loans.create') ? 'bg-blue-50 text-[#0B63B5] font-extrabold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-bold' }}"
                   :class="sidebarCollapsed && !sidebarMobileOpen ? 'justify-center px-0' : 'px-3 gap-3'">
                    <div class="w-6 h-6 flex items-center justify-center shrink-0">
                        <i data-lucide="plus-circle" class="w-5 h-5 {{ request()->routeIs('loans.create') ? 'text-[#0B63B5]' : 'text-slate-500 group-hover:text-slate-800' }}"></i>
                    </div>
                    <span x-show="!sidebarCollapsed || sidebarMobileOpen"
                          x-transition:enter="transition-opacity duration-150 delay-100"
                          x-transition:enter-start="opacity-0"
                          x-transition:enter-end="opacity-100"
                          x-transition:leave="transition-opacity duration-75"
                          x-transition:leave-start="opacity-100"
                          x-transition:leave-end="opacity-0"
                          class="truncate whitespace-nowrap text-xs">
                        Ajukan BMN Baru
                    </span>

                    <!-- Floating Tooltip -->
                    <div x-show="sidebarCollapsed && !sidebarMobileOpen"
                         x-cloak
                         class="absolute left-full top-1/2 -translate-y-1/2 ml-3.5 px-3 py-1.5 bg-slate-900/95 text-white text-xs font-bold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 translate-x-1 group-hover:translate-x-0 transition-all duration-150 z-50 flex items-center gap-1.5">
                        <span class="absolute -left-1 top-1/2 -translate-y-1/2 w-2 h-2 bg-slate-900/95 rotate-45"></span>
                        <span class="relative z-10">Ajukan BMN Baru</span>
                    </div>
                </a>
            </div>

            <!-- GROUP 2: PEMINJAM -->
            @if(auth()->user()->isPeminjam() || auth()->user()->isAdmin())
            <div class="space-y-1">
                <div x-show="!sidebarCollapsed || sidebarMobileOpen"
                     x-transition:enter="transition-opacity duration-150 delay-75"
                     x-transition:leave="transition-opacity duration-75"
                     class="px-3 py-1 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 truncate whitespace-nowrap">
                    Layanan Peminjam
                </div>

                <a href="{{ route('loans.index') }}"
                   class="flex items-center rounded-xl h-10 transition-colors group relative select-none {{ request()->routeIs('loans.index') ? 'bg-blue-50 text-[#0B63B5] font-extrabold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-bold' }}"
                   :class="sidebarCollapsed && !sidebarMobileOpen ? 'justify-center px-0' : 'px-3 gap-3'">
                    <div class="w-6 h-6 flex items-center justify-center shrink-0">
                        <i data-lucide="file-text" class="w-5 h-5 {{ request()->routeIs('loans.index') ? 'text-[#0B63B5]' : 'text-slate-500 group-hover:text-slate-800' }}"></i>
                    </div>
                    <span x-show="!sidebarCollapsed || sidebarMobileOpen"
                          x-transition:enter="transition-opacity duration-150 delay-100"
                          x-transition:enter-start="opacity-0"
                          x-transition:enter-end="opacity-100"
                          x-transition:leave="transition-opacity duration-75"
                          x-transition:leave-start="opacity-100"
                          x-transition:leave-end="opacity-0"
                          class="truncate whitespace-nowrap text-xs">
                        Pengajuan Saya
                    </span>

                    <!-- Floating Tooltip -->
                    <div x-show="sidebarCollapsed && !sidebarMobileOpen"
                         x-cloak
                         class="absolute left-full top-1/2 -translate-y-1/2 ml-3.5 px-3 py-1.5 bg-slate-900/95 text-white text-xs font-bold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 translate-x-1 group-hover:translate-x-0 transition-all duration-150 z-50 flex items-center gap-1.5">
                        <span class="absolute -left-1 top-1/2 -translate-y-1/2 w-2 h-2 bg-slate-900/95 rotate-45"></span>
                        <span class="relative z-10">Pengajuan Saya</span>
                    </div>
                </a>

                <!-- Data Diri Peminjaman & Profil -->
                <a href="{{ route('profile.show') }}"
                   class="flex items-center rounded-xl h-10 transition-colors group relative select-none {{ request()->routeIs('profile.*') ? 'bg-blue-50 text-[#0B63B5] font-extrabold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-bold' }}"
                   :class="sidebarCollapsed && !sidebarMobileOpen ? 'justify-center px-0' : 'px-3 gap-3'">
                    <div class="w-6 h-6 flex items-center justify-center shrink-0">
                        <i data-lucide="user-pen" class="w-5 h-5 {{ request()->routeIs('profile.*') ? 'text-[#0B63B5]' : 'text-slate-500 group-hover:text-slate-800' }}"></i>
                    </div>
                    <span x-show="!sidebarCollapsed || sidebarMobileOpen"
                          x-transition:enter="transition-opacity duration-150 delay-100"
                          x-transition:enter-start="opacity-0"
                          x-transition:enter-end="opacity-100"
                          x-transition:leave="transition-opacity duration-75"
                          x-transition:leave-start="opacity-100"
                          x-transition:leave-end="opacity-0"
                          class="truncate whitespace-nowrap text-xs">
                        Data Diri Peminjaman
                    </span>

                    <!-- Floating Tooltip -->
                    <div x-show="sidebarCollapsed && !sidebarMobileOpen"
                         x-cloak
                         class="absolute left-full top-1/2 -translate-y-1/2 ml-3.5 px-3 py-1.5 bg-slate-900/95 text-white text-xs font-bold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 translate-x-1 group-hover:translate-x-0 transition-all duration-150 z-50 flex items-center gap-1.5">
                        <span class="absolute -left-1 top-1/2 -translate-y-1/2 w-2 h-2 bg-slate-900/95 rotate-45"></span>
                        <span class="relative z-10">Data Diri Peminjaman</span>
                    </div>
                </a>
            </div>
            @endif

            <!-- GROUP 3: PENGELOLA BMN -->
            @if(auth()->user()->isPengelola())
            <div class="space-y-1">
                <div x-show="!sidebarCollapsed || sidebarMobileOpen"
                     x-transition:enter="transition-opacity duration-150 delay-75"
                     x-transition:leave="transition-opacity duration-75"
                     class="px-3 py-1 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 truncate whitespace-nowrap">
                    Pengelolaan BMN
                </div>

                <a href="{{ route('kelola.queue') }}"
                   class="flex items-center justify-between rounded-xl h-10 transition-colors group relative select-none {{ request()->routeIs('kelola.*') ? 'bg-blue-50 text-[#0B63B5] font-extrabold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-bold' }}"
                   :class="sidebarCollapsed && !sidebarMobileOpen ? 'justify-center px-0' : 'px-3'">
                    <div class="flex items-center gap-3 truncate">
                        <div class="w-6 h-6 flex items-center justify-center shrink-0">
                            <i data-lucide="inbox" class="w-5 h-5 {{ request()->routeIs('kelola.*') ? 'text-[#0B63B5]' : 'text-slate-500 group-hover:text-slate-800' }}"></i>
                        </div>
                        <span x-show="!sidebarCollapsed || sidebarMobileOpen"
                              x-transition:enter="transition-opacity duration-150 delay-100"
                              x-transition:enter-start="opacity-0"
                              x-transition:enter-end="opacity-100"
                              x-transition:leave="transition-opacity duration-75"
                              x-transition:leave-start="opacity-100"
                              x-transition:leave-end="opacity-0"
                              class="truncate whitespace-nowrap text-xs">
                            Antrean Telaah
                        </span>
                    </div>

                    @php $pendingCount = \App\Models\LoanRequest::whereIn('status', ['SUBMITTED', 'RETURN_REQUESTED'])->count(); @endphp
                    @if($pendingCount > 0)
                        <span x-show="!sidebarCollapsed || sidebarMobileOpen"
                              class="px-1.5 py-0.5 text-[10px] bg-amber-500 text-white rounded-full font-bold tabular-nums">
                            {{ $pendingCount }}
                        </span>
                    @endif

                    <!-- Floating Tooltip -->
                    <div x-show="sidebarCollapsed && !sidebarMobileOpen"
                         x-cloak
                         class="absolute left-full top-1/2 -translate-y-1/2 ml-3.5 px-3 py-1.5 bg-slate-900/95 text-white text-xs font-bold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 translate-x-1 group-hover:translate-x-0 transition-all duration-150 z-50 flex items-center gap-2">
                        <span class="absolute -left-1 top-1/2 -translate-y-1/2 w-2 h-2 bg-slate-900/95 rotate-45"></span>
                        <span class="relative z-10">Antrean Telaah</span>
                        @if($pendingCount > 0)
                            <span class="px-1.5 py-0.2 text-[10px] bg-amber-500 text-white rounded-full font-bold tabular-nums">{{ $pendingCount }}</span>
                        @endif
                    </div>
                </a>

                <!-- Unggah Data Excel (.xlsx) -->
                <a href="{{ route('kelola.import.index') }}"
                   class="flex items-center rounded-xl h-10 transition-colors group relative select-none {{ request()->routeIs('kelola.import.*') ? 'bg-blue-50 text-[#0B63B5] font-extrabold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-bold' }}"
                   :class="sidebarCollapsed && !sidebarMobileOpen ? 'justify-center px-0' : 'px-3 gap-3'">
                    <div class="w-6 h-6 flex items-center justify-center shrink-0">
                        <i data-lucide="file-spreadsheet" class="w-5 h-5 {{ request()->routeIs('kelola.import.*') ? 'text-[#0B63B5]' : 'text-slate-500 group-hover:text-slate-800' }}"></i>
                    </div>
                    <span x-show="!sidebarCollapsed || sidebarMobileOpen"
                          x-transition:enter="transition-opacity duration-150 delay-100"
                          x-transition:enter-start="opacity-0"
                          x-transition:enter-end="opacity-100"
                          x-transition:leave="transition-opacity duration-75"
                          x-transition:leave-start="opacity-100"
                          x-transition:leave-end="opacity-0"
                          class="truncate whitespace-nowrap text-xs">
                        Unggah Data (.xlsx)
                    </span>

                    <!-- Floating Tooltip -->
                    <div x-show="sidebarCollapsed && !sidebarMobileOpen"
                         x-cloak
                         class="absolute left-full top-1/2 -translate-y-1/2 ml-3.5 px-3 py-1.5 bg-slate-900/95 text-white text-xs font-bold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 translate-x-1 group-hover:translate-x-0 transition-all duration-150 z-50 flex items-center gap-1.5">
                        <span class="absolute -left-1 top-1/2 -translate-y-1/2 w-2 h-2 bg-slate-900/95 rotate-45"></span>
                        <span class="relative z-10">Unggah Data (.xlsx)</span>
                    </div>
                </a>

                <!-- Audit Aktivitas Peminjaman (Pengelola) -->
                <a href="{{ route('audit.loans') }}"
                   class="flex items-center rounded-xl h-10 transition-colors group relative select-none {{ request()->routeIs('audit.*') ? 'bg-blue-50 text-[#0B63B5] font-extrabold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-bold' }}"
                   :class="sidebarCollapsed && !sidebarMobileOpen ? 'justify-center px-0' : 'px-3 gap-3'">
                    <div class="w-6 h-6 flex items-center justify-center shrink-0">
                        <i data-lucide="clipboard-check" class="w-5 h-5 {{ request()->routeIs('audit.*') ? 'text-[#0B63B5]' : 'text-slate-500 group-hover:text-slate-800' }}"></i>
                    </div>
                    <span x-show="!sidebarCollapsed || sidebarMobileOpen"
                          x-transition:enter="transition-opacity duration-150 delay-100"
                          x-transition:enter-start="opacity-0"
                          x-transition:enter-end="opacity-100"
                          x-transition:leave="transition-opacity duration-75"
                          x-transition:leave-start="opacity-100"
                          x-transition:leave-end="opacity-0"
                          class="truncate whitespace-nowrap text-xs">
                        Audit Peminjaman
                    </span>

                    <!-- Floating Tooltip -->
                    <div x-show="sidebarCollapsed && !sidebarMobileOpen"
                         x-cloak
                         class="absolute left-full top-1/2 -translate-y-1/2 ml-3.5 px-3 py-1.5 bg-slate-900/95 text-white text-xs font-bold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 translate-x-1 group-hover:translate-x-0 transition-all duration-150 z-50 flex items-center gap-1.5">
                        <span class="absolute -left-1 top-1/2 -translate-y-1/2 w-2 h-2 bg-slate-900/95 rotate-45"></span>
                        <span class="relative z-10">Audit Peminjaman BMN</span>
                    </div>
                </a>
            </div>
            @endif

            <!-- GROUP 4: PERSETUJUAN ASET -->
            @if(auth()->user()->isKetuaTim())
            <div class="space-y-1">
                <div x-show="!sidebarCollapsed || sidebarMobileOpen"
                     x-transition:enter="transition-opacity duration-150 delay-75"
                     x-transition:leave="transition-opacity duration-75"
                     class="px-3 py-1 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 truncate whitespace-nowrap">
                    Persetujuan
                </div>

                <a href="{{ route('persetujuan.index') }}"
                   class="flex items-center justify-between rounded-xl h-10 transition-colors group relative select-none {{ request()->routeIs('persetujuan.*') ? 'bg-blue-50 text-[#0B63B5] font-extrabold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-bold' }}"
                   :class="sidebarCollapsed && !sidebarMobileOpen ? 'justify-center px-0' : 'px-3'">
                    <div class="flex items-center gap-3 truncate">
                        <div class="w-6 h-6 flex items-center justify-center shrink-0">
                            <i data-lucide="check-square" class="w-5 h-5 {{ request()->routeIs('persetujuan.*') ? 'text-[#0B63B5]' : 'text-slate-500 group-hover:text-slate-800' }}"></i>
                        </div>
                        <span x-show="!sidebarCollapsed || sidebarMobileOpen"
                              x-transition:enter="transition-opacity duration-150 delay-100"
                              x-transition:enter-start="opacity-0"
                              x-transition:enter-end="opacity-100"
                              x-transition:leave="transition-opacity duration-75"
                              x-transition:leave-start="opacity-100"
                              x-transition:leave-end="opacity-0"
                              class="truncate whitespace-nowrap text-xs">
                            Persetujuan BMN
                        </span>
                    </div>

                    @php $approvalCount = \App\Models\LoanRequest::where('status', 'UNDER_APPROVAL')->count(); @endphp
                    @if($approvalCount > 0)
                        <span x-show="!sidebarCollapsed || sidebarMobileOpen"
                              class="px-1.5 py-0.5 text-[10px] bg-blue-600 text-white rounded-full font-bold tabular-nums">
                            {{ $approvalCount }}
                        </span>
                    @endif

                    <!-- Floating Tooltip -->
                    <div x-show="sidebarCollapsed && !sidebarMobileOpen"
                         x-cloak
                         class="absolute left-full top-1/2 -translate-y-1/2 ml-3.5 px-3 py-1.5 bg-slate-900/95 text-white text-xs font-bold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 translate-x-1 group-hover:translate-x-0 transition-all duration-150 z-50 flex items-center gap-2">
                        <span class="absolute -left-1 top-1/2 -translate-y-1/2 w-2 h-2 bg-slate-900/95 rotate-45"></span>
                        <span class="relative z-10">Persetujuan BMN</span>
                        @if($approvalCount > 0)
                            <span class="px-1.5 py-0.2 text-[10px] bg-blue-500 text-white rounded-full font-bold tabular-nums">{{ $approvalCount }}</span>
                        @endif
                    </div>
                </a>

                <!-- Audit Aktivitas Peminjaman (Ketua Tim) -->
                <a href="{{ route('audit.loans') }}"
                   class="flex items-center rounded-xl h-10 transition-colors group relative select-none {{ request()->routeIs('audit.*') ? 'bg-blue-50 text-[#0B63B5] font-extrabold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-bold' }}"
                   :class="sidebarCollapsed && !sidebarMobileOpen ? 'justify-center px-0' : 'px-3 gap-3'">
                    <div class="w-6 h-6 flex items-center justify-center shrink-0">
                        <i data-lucide="clipboard-check" class="w-5 h-5 {{ request()->routeIs('audit.*') ? 'text-[#0B63B5]' : 'text-slate-500 group-hover:text-slate-800' }}"></i>
                    </div>
                    <span x-show="!sidebarCollapsed || sidebarMobileOpen"
                          x-transition:enter="transition-opacity duration-150 delay-100"
                          x-transition:enter-start="opacity-0"
                          x-transition:enter-end="opacity-100"
                          x-transition:leave="transition-opacity duration-75"
                          x-transition:leave-start="opacity-100"
                          x-transition:leave-end="opacity-0"
                          class="truncate whitespace-nowrap text-xs">
                        Audit Peminjaman
                    </span>

                    <!-- Floating Tooltip -->
                    <div x-show="sidebarCollapsed && !sidebarMobileOpen"
                         x-cloak
                         class="absolute left-full top-1/2 -translate-y-1/2 ml-3.5 px-3 py-1.5 bg-slate-900/95 text-white text-xs font-bold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 translate-x-1 group-hover:translate-x-0 transition-all duration-150 z-50 flex items-center gap-1.5">
                        <span class="absolute -left-1 top-1/2 -translate-y-1/2 w-2 h-2 bg-slate-900/95 rotate-45"></span>
                        <span class="relative z-10">Audit Peminjaman BMN</span>
                    </div>
                </a>
            </div>
            @endif

            <!-- GROUP 5: ADMINISTRASI SISTEM (ADMIN) -->
            @if(auth()->user()->isAdmin())
            <div class="space-y-1">
                <div x-show="!sidebarCollapsed || sidebarMobileOpen"
                     x-transition:enter="transition-opacity duration-150 delay-75"
                     x-transition:leave="transition-opacity duration-75"
                     class="px-3 py-1 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 truncate whitespace-nowrap">
                    Administrasi & Audit
                </div>

                <!-- 1. Audit Aktivitas Peminjaman (Khusus Peminjaman BMN Tanpa Data Sensitif) -->
                <a href="{{ route('audit.loans') }}"
                   class="flex items-center justify-between rounded-xl h-10 transition-colors group relative select-none {{ request()->routeIs('audit.*') ? 'bg-blue-50 text-[#0B63B5] font-extrabold border border-blue-200/80 shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-bold' }}"
                   :class="sidebarCollapsed && !sidebarMobileOpen ? 'justify-center px-0' : 'px-3'">
                    <div class="flex items-center gap-3 truncate">
                        <div class="w-6 h-6 flex items-center justify-center shrink-0">
                            <i data-lucide="clipboard-check" class="w-5 h-5 {{ request()->routeIs('audit.*') ? 'text-[#0B63B5]' : 'text-slate-500 group-hover:text-slate-800' }}"></i>
                        </div>
                        <span x-show="!sidebarCollapsed || sidebarMobileOpen"
                              x-transition:enter="transition-opacity duration-150 delay-100"
                              x-transition:enter-start="opacity-0"
                              x-transition:enter-end="opacity-100"
                              x-transition:leave="transition-opacity duration-75"
                              x-transition:leave-start="opacity-100"
                              x-transition:leave-end="opacity-0"
                              class="truncate whitespace-nowrap text-xs">
                            Audit Peminjaman
                        </span>
                    </div>
                    <span x-show="!sidebarCollapsed || sidebarMobileOpen" class="px-1.5 py-0.5 text-[9px] bg-blue-100 text-[#0B63B5] rounded-md font-extrabold uppercase">BMN</span>

                    <!-- Floating Tooltip -->
                    <div x-show="sidebarCollapsed && !sidebarMobileOpen"
                         x-cloak
                         class="absolute left-full top-1/2 -translate-y-1/2 ml-3.5 px-3 py-1.5 bg-slate-900/95 text-white text-xs font-bold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 translate-x-1 group-hover:translate-x-0 transition-all duration-150 z-50 flex items-center gap-1.5">
                        <span class="absolute -left-1 top-1/2 -translate-y-1/2 w-2 h-2 bg-slate-900/95 rotate-45"></span>
                        <span class="relative z-10">Audit Aktivitas Peminjaman BMN</span>
                    </div>
                </a>

                <!-- 2. Log Aktivitas Sistem (Forensik Teknis IT Lengkap) -->
                <a href="{{ route('admin.activity-logs') }}"
                   class="flex items-center justify-between rounded-xl h-10 transition-colors group relative select-none {{ request()->routeIs('admin.activity-logs') || request()->routeIs('admin.audit-logs') ? 'bg-blue-50 text-[#0B63B5] font-extrabold border border-blue-200/80 shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-bold' }}"
                   :class="sidebarCollapsed && !sidebarMobileOpen ? 'justify-center px-0' : 'px-3'">
                    <div class="flex items-center gap-3 truncate">
                        <div class="w-6 h-6 flex items-center justify-center shrink-0">
                            <i data-lucide="activity" class="w-5 h-5 {{ request()->routeIs('admin.activity-logs') || request()->routeIs('admin.audit-logs') ? 'text-[#0B63B5]' : 'text-slate-500 group-hover:text-slate-800' }}"></i>
                        </div>
                        <span x-show="!sidebarCollapsed || sidebarMobileOpen"
                              x-transition:enter="transition-opacity duration-150 delay-100"
                              x-transition:enter-start="opacity-0"
                              x-transition:enter-end="opacity-100"
                              x-transition:leave="transition-opacity duration-75"
                              x-transition:leave-start="opacity-100"
                              x-transition:leave-end="opacity-0"
                              class="truncate whitespace-nowrap text-xs">
                            Log Aktivitas Sistem
                        </span>
                    </div>
                    <span x-show="!sidebarCollapsed || sidebarMobileOpen" class="px-1.5 py-0.5 text-[9px] bg-slate-200/80 text-slate-700 rounded-md font-extrabold uppercase">Sistem</span>

                    <!-- Floating Tooltip -->
                    <div x-show="sidebarCollapsed && !sidebarMobileOpen"
                         x-cloak
                         class="absolute left-full top-1/2 -translate-y-1/2 ml-3.5 px-3 py-1.5 bg-slate-900/95 text-white text-xs font-bold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 translate-x-1 group-hover:translate-x-0 transition-all duration-150 z-50 flex items-center gap-1.5">
                        <span class="absolute -left-1 top-1/2 -translate-y-1/2 w-2 h-2 bg-slate-900/95 rotate-45"></span>
                        <span class="relative z-10">Log Aktivitas Sistem (Forensik IT)</span>
                    </div>
                </a>

                <!-- Manajemen Pengguna -->
                <a href="{{ route('admin.users') }}"
                   class="flex items-center rounded-xl h-10 transition-colors group relative select-none {{ request()->routeIs('admin.users') ? 'bg-blue-50 text-[#0B63B5] font-extrabold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-bold' }}"
                   :class="sidebarCollapsed && !sidebarMobileOpen ? 'justify-center px-0' : 'px-3 gap-3'">
                    <div class="w-6 h-6 flex items-center justify-center shrink-0">
                        <i data-lucide="users" class="w-5 h-5 {{ request()->routeIs('admin.users') ? 'text-[#0B63B5]' : 'text-slate-500 group-hover:text-slate-800' }}"></i>
                    </div>
                    <span x-show="!sidebarCollapsed || sidebarMobileOpen"
                          x-transition:enter="transition-opacity duration-150 delay-100"
                          x-transition:enter-start="opacity-0"
                          x-transition:enter-end="opacity-100"
                          x-transition:leave="transition-opacity duration-75"
                          x-transition:leave-start="opacity-100"
                          x-transition:leave-end="opacity-0"
                          class="truncate whitespace-nowrap text-xs">
                        Manajemen Pengguna
                    </span>

                    <!-- Floating Tooltip -->
                    <div x-show="sidebarCollapsed && !sidebarMobileOpen"
                         x-cloak
                         class="absolute left-full top-1/2 -translate-y-1/2 ml-3.5 px-3 py-1.5 bg-slate-900/95 text-white text-xs font-bold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 translate-x-1 group-hover:translate-x-0 transition-all duration-150 z-50 flex items-center gap-1.5">
                        <span class="absolute -left-1 top-1/2 -translate-y-1/2 w-2 h-2 bg-slate-900/95 rotate-45"></span>
                        <span class="relative z-10">Manajemen Pengguna</span>
                    </div>
                </a>

                <!-- Pejabat Penandatangan -->
                <a href="{{ route('admin.signatories') }}"
                   class="flex items-center rounded-xl h-10 transition-colors group relative select-none {{ request()->routeIs('admin.signatories') ? 'bg-blue-50 text-[#0B63B5] font-extrabold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-bold' }}"
                   :class="sidebarCollapsed && !sidebarMobileOpen ? 'justify-center px-0' : 'px-3 gap-3'">
                    <div class="w-6 h-6 flex items-center justify-center shrink-0">
                        <i data-lucide="feather" class="w-5 h-5 {{ request()->routeIs('admin.signatories') ? 'text-[#0B63B5]' : 'text-slate-500 group-hover:text-slate-800' }}"></i>
                    </div>
                    <span x-show="!sidebarCollapsed || sidebarMobileOpen"
                          x-transition:enter="transition-opacity duration-150 delay-100"
                          x-transition:enter-start="opacity-0"
                          x-transition:enter-end="opacity-100"
                          x-transition:leave="transition-opacity duration-75"
                          x-transition:leave-start="opacity-100"
                          x-transition:leave-end="opacity-0"
                          class="truncate whitespace-nowrap text-xs">
                        Pejabat Penandatangan
                    </span>

                    <!-- Floating Tooltip -->
                    <div x-show="sidebarCollapsed && !sidebarMobileOpen"
                         x-cloak
                         class="absolute left-full top-1/2 -translate-y-1/2 ml-3.5 px-3 py-1.5 bg-slate-900/95 text-white text-xs font-bold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 translate-x-1 group-hover:translate-x-0 transition-all duration-150 z-50 flex items-center gap-1.5">
                        <span class="absolute -left-1 top-1/2 -translate-y-1/2 w-2 h-2 bg-slate-900/95 rotate-45"></span>
                        <span class="relative z-10">Pejabat Penandatangan</span>
                    </div>
                </a>

                <!-- Pengaturan Satker -->
                <a href="{{ route('admin.settings') }}"
                   class="flex items-center rounded-xl h-10 transition-colors group relative select-none {{ request()->routeIs('admin.settings') ? 'bg-blue-50 text-[#0B63B5] font-extrabold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-bold' }}"
                   :class="sidebarCollapsed && !sidebarMobileOpen ? 'justify-center px-0' : 'px-3 gap-3'">
                    <div class="w-6 h-6 flex items-center justify-center shrink-0">
                        <i data-lucide="settings" class="w-5 h-5 {{ request()->routeIs('admin.settings') ? 'text-[#0B63B5]' : 'text-slate-500 group-hover:text-slate-800' }}"></i>
                    </div>
                    <span x-show="!sidebarCollapsed || sidebarMobileOpen"
                          x-transition:enter="transition-opacity duration-150 delay-100"
                          x-transition:enter-start="opacity-0"
                          x-transition:enter-end="opacity-100"
                          x-transition:leave="transition-opacity duration-75"
                          x-transition:leave-start="opacity-100"
                          x-transition:leave-end="opacity-0"
                          class="truncate whitespace-nowrap text-xs">
                        Pengaturan Satker
                    </span>

                    <!-- Floating Tooltip -->
                    <div x-show="sidebarCollapsed && !sidebarMobileOpen"
                         x-cloak
                         class="absolute left-full top-1/2 -translate-y-1/2 ml-3.5 px-3 py-1.5 bg-slate-900/95 text-white text-xs font-bold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 translate-x-1 group-hover:translate-x-0 transition-all duration-150 z-50 flex items-center gap-1.5">
                        <span class="absolute -left-1 top-1/2 -translate-y-1/2 w-2 h-2 bg-slate-900/95 rotate-45"></span>
                        <span class="relative z-10">Pengaturan Satker</span>
                    </div>
                </a>
            </div>
            @endif

        </nav>

        <!-- Sidebar Footer (User Info) -->
        <div class="p-3 border-t border-slate-100 shrink-0 bg-slate-50/50">
            <!-- User Mini Profile -->
            <a href="{{ route('profile.show') }}"
               class="flex items-center gap-2.5 p-2 rounded-xl bg-white border border-slate-200/80 shadow-2xs group relative select-none hover:border-[#0B63B5]/50 hover:bg-blue-50/30 transition cursor-pointer"
                 :class="sidebarCollapsed && !sidebarMobileOpen ? 'justify-center' : ''">
                <div class="w-8 h-8 rounded-lg bg-blue-100 text-[#0B63B5] font-black flex items-center justify-center text-xs shrink-0 border border-blue-200">
                    {{ substr(auth()->user()->name, 0, 1) }}
                </div>
                <div x-show="!sidebarCollapsed || sidebarMobileOpen"
                     x-transition:enter="transition-opacity duration-150 delay-100"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition-opacity duration-75"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="truncate min-w-0 flex-1 whitespace-nowrap">
                    <span class="font-bold text-slate-800 text-xs block truncate">{{ auth()->user()->name }}</span>
                    <span class="text-[10px] font-semibold text-[#0B63B5] uppercase block truncate">{{ str_replace('_', ' ', auth()->user()->role) }}</span>
                </div>

                <!-- Floating Tooltip for User Profile -->
                <div x-show="sidebarCollapsed && !sidebarMobileOpen"
                     x-cloak
                     class="absolute left-full bottom-2 ml-3.5 px-3 py-2 bg-slate-900/95 text-white text-xs rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 translate-x-1 group-hover:translate-x-0 transition-all duration-150 z-50">
                    <span class="absolute -left-1 bottom-3 w-2 h-2 bg-slate-900/95 rotate-45"></span>
                    <span class="font-bold block">{{ auth()->user()->name }}</span>
                    <span class="text-[10px] text-blue-300 uppercase font-semibold block">{{ str_replace('_', ' ', auth()->user()->role) }}</span>
                </div>
            </a>
        </div>
    </aside>

    <!-- CONTENT AREA WRAPPER (Offsets fixed sidebar) -->
    <div class="flex-1 flex flex-col min-h-screen transition-all duration-300 ease-in-out"
         :class="sidebarCollapsed ? 'lg:pl-20' : 'lg:pl-64'">

        <!-- TOP HEADER BAR -->
        <header class="sticky top-0 z-30 bg-white/95 backdrop-blur-md border-b border-slate-200 h-16 lg:h-20 w-full transition-all duration-300 ease-in-out">
            <div class="max-w-7xl mx-auto h-full px-4 sm:px-6 lg:px-10 xl:px-12 flex items-center justify-between gap-4">

                <!-- Left: Satker Context Title (Tanpa burger menu) -->
                <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                    <!-- Mobile Toggle with Arrow Icon (Bukan Burger Menu) -->
                    <button type="button"
                            @click="sidebarMobileOpen = !sidebarMobileOpen"
                            class="lg:hidden p-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition focus:outline-none flex items-center gap-1.5 shrink-0"
                            title="Buka Menu Navigasi">
                        <i data-lucide="chevron-right" class="w-4 h-4 text-[#0B63B5]"></i>
                        <span class="text-xs font-bold text-slate-700">Menu</span>
                    </button>

                    <!-- Satker Context Title (Sesuai Permintaan: Hanya "Sistem Layanan Tata Kelola & Peminjaman BMN") -->
                    <div class="hidden sm:flex items-center min-w-0 py-1">
                        <span class="text-xs sm:text-sm lg:text-base font-extrabold text-slate-900 tracking-tight truncate">
                            Sistem Layanan Tata Kelola & Peminjaman BMN
                        </span>
                    </div>
                </div>

                <!-- Right: Actions, Demo Switcher, & User Menu -->
                <div class="flex items-center gap-3 shrink-0">
                    <a href="{{ route('loans.create') }}" class="hidden sm:inline-flex items-center gap-2 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white text-xs lg:text-sm font-bold px-3.5 lg:px-4 py-2 lg:py-2.5 rounded-xl shadow-sm transition shrink-0">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        <span>Ajukan BMN</span>
                    </a>

                    <!-- Profile Dropdown (Alpine.js) -->
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" type="button" class="flex items-center gap-2.5 p-1.5 lg:p-2 rounded-xl hover:bg-slate-100 transition text-left">
                            <div class="w-8 h-8 lg:w-9 lg:h-9 rounded-lg lg:rounded-xl bg-blue-100 text-[#0B63B5] font-black flex items-center justify-center text-xs lg:text-sm border border-blue-200 shrink-0">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                            <div class="hidden md:block text-xs lg:text-sm">
                                <span class="font-bold text-slate-800 block truncate max-w-[120px] lg:max-w-[160px] xl:max-w-[220px]">{{ auth()->user()->name }}</span>
                                <span class="text-[10px] lg:text-[11px] font-bold text-[#0B63B5] uppercase block truncate">{{ str_replace('_', ' ', auth()->user()->role) }}</span>
                            </div>
                            <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 shrink-0"></i>
                        </button>

                        <div x-show="open" @click.outside="open = false" x-cloak
                             class="absolute right-0 mt-2 w-64 bg-white rounded-2xl shadow-xl border border-slate-200 py-2 z-50 animate-in fade-in slide-in-from-top-2 duration-150">
                            <div class="px-4 py-2.5 border-b border-slate-100">
                                <p class="text-[11px] text-slate-400 font-medium">Masuk sebagai:</p>
                                <p class="text-sm font-bold text-slate-900 truncate">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-slate-500 truncate">{{ auth()->user()->email }}</p>
                                <span class="mt-1.5 inline-block px-2 py-0.5 text-[10px] font-bold tracking-wide rounded-md bg-blue-50 text-[#0B63B5] border border-blue-200">
                                    Peran: {{ auth()->user()->role }}
                                </span>
                            </div>

                            <!-- Link ke Profil & Tanda Tangan -->
                            <div class="p-1 border-b border-slate-100">
                                <a href="{{ route('profile.show') }}" class="w-full text-left px-3 py-2 text-xs text-slate-700 hover:text-[#0B63B5] hover:bg-blue-50/80 rounded-xl font-bold transition flex items-center gap-2 group">
                                    <i data-lucide="signature" class="w-4 h-4 text-[#0B63B5]"></i>
                                    <span>Profil & Tanda Tangan Saya</span>
                                </a>
                            </div>

                            <!-- Demo Role Quick Switcher -->
                            <div class="px-3 py-2 bg-slate-50/80 border-b border-slate-100">
                                <span class="text-[10px] font-bold tracking-wider text-slate-400 uppercase block mb-1.5">Ganti Akun Demo (Uji Alur SOP):</span>
                                <div class="grid grid-cols-2 gap-1.5">
                                    <a href="{{ route('login.quick', 'PEMINJAM') }}" class="text-[11px] font-semibold p-1.5 rounded-lg bg-white border border-slate-200 text-slate-700 hover:border-blue-500 hover:text-blue-600 text-center transition">
                                        Peminjam
                                    </a>
                                    <a href="{{ route('login.quick', 'PENGELOLA_BMN') }}" class="text-[11px] font-semibold p-1.5 rounded-lg bg-white border border-slate-200 text-slate-700 hover:border-blue-500 hover:text-blue-600 text-center transition">
                                        Pengelola
                                    </a>
                                    <a href="{{ route('login.quick', 'KETUA_TIM_ASET') }}" class="text-[11px] font-semibold p-1.5 rounded-lg bg-white border border-slate-200 text-slate-700 hover:border-blue-500 hover:text-blue-600 text-center transition">
                                        Ketua Tim
                                    </a>
                                    <a href="{{ route('login.quick', 'ADMIN') }}" class="text-[11px] font-semibold p-1.5 rounded-lg bg-white border border-slate-200 text-slate-700 hover:border-blue-500 hover:text-blue-600 text-center transition">
                                        Admin
                                    </a>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('logout') }}" class="p-1">
                                @csrf
                                <button type="submit" class="w-full text-left px-3 py-2 text-xs text-red-600 hover:bg-red-50 rounded-xl font-bold transition flex items-center gap-2">
                                    <i data-lucide="log-out" class="w-4 h-4"></i>
                                    <span>Keluar Sistem</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- FLASH MESSAGES -->
        <div class="w-full mt-4">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-10 xl:px-12">
                @if(session('success'))
                    <div class="p-4 mb-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-start gap-3 shadow-sm animate-in fade-in">
                        <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5"></i>
                        <div class="text-sm font-semibold">{{ session('success') }}</div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="p-4 mb-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 flex items-start gap-3 shadow-sm animate-in fade-in">
                        <i data-lucide="alert-circle" class="w-5 h-5 text-red-600 shrink-0 mt-0.5"></i>
                        <div class="text-sm font-semibold">{{ session('error') }}</div>
                    </div>
                @endif

                @if(session('warning'))
                    <div class="p-4 mb-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-800 flex items-start gap-3 shadow-sm animate-in fade-in">
                        <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-600 shrink-0 mt-0.5"></i>
                        <div class="text-sm font-semibold">{{ session('warning') }}</div>
                    </div>
                @endif

                @if(isset($errors) && $errors->any())
                    <div class="p-4 mb-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 shadow-sm animate-in fade-in">
                        <div class="flex items-center gap-2 font-bold text-sm mb-1">
                            <i data-lucide="alert-octagon" class="w-5 h-5 text-red-600"></i>
                            <span>Terdapat beberapa kesalahan:</span>
                        </div>
                        <ul class="list-disc list-inside text-xs text-red-700 space-y-0.5 ml-2 font-medium">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>

        <!-- MAIN CONTENT -->
        <main class="flex-1 py-4 sm:py-6 pb-20 lg:pb-8 flex flex-col w-full">
            <div class="max-w-7xl mx-auto w-full flex-1 px-4 sm:px-6 lg:px-10 xl:px-12">
                @yield('content')
            </div>
        </main>
    </div>

    <!-- MOBILE BOTTOM NAVIGATION (Zone jempol mobile) -->
    <div class="lg:hidden fixed bottom-0 left-0 right-0 z-30 bg-white/95 backdrop-blur-md border-t border-slate-200 px-2 py-2 flex items-center justify-around shadow-lg">
        <a href="{{ route('dashboard') }}" class="flex flex-col items-center gap-1 py-1 px-3 rounded-lg text-xs font-semibold {{ request()->routeIs('dashboard') ? 'text-[#0B63B5]' : 'text-slate-500' }}">
            <i data-lucide="home" class="w-5 h-5"></i>
            <span class="text-[10px]">Beranda</span>
        </a>

        <a href="{{ route('assets.index') }}" class="flex flex-col items-center gap-1 py-1 px-3 rounded-lg text-xs font-semibold {{ request()->routeIs('assets.*') ? 'text-[#0B63B5]' : 'text-slate-500' }}">
            <i data-lucide="grid" class="w-5 h-5"></i>
            <span class="text-[10px]">Katalog</span>
        </a>

        <!-- Highlighted Action Button -->
        <a href="{{ route('loans.create') }}" class="flex flex-col items-center justify-center -mt-5">
            <div class="w-12 h-12 rounded-full bg-[#0B63B5] text-white flex items-center justify-center shadow-lg shadow-blue-500/30 border-2 border-white">
                <i data-lucide="plus" class="w-6 h-6"></i>
            </div>
            <span class="text-[10px] font-bold text-[#0B63B5] mt-0.5">Ajukan</span>
        </a>

        @if(auth()->user()->isPengelola())
        <a href="{{ route('kelola.queue') }}" class="flex flex-col items-center gap-1 py-1 px-3 rounded-lg text-xs font-semibold relative {{ request()->routeIs('kelola.*') ? 'text-[#0B63B5]' : 'text-slate-500' }}">
            <i data-lucide="inbox" class="w-5 h-5"></i>
            <span class="text-[10px]">Antrean</span>
        </a>
        @elseif(auth()->user()->isKetuaTim())
        <a href="{{ route('persetujuan.index') }}" class="flex flex-col items-center gap-1 py-1 px-3 rounded-lg text-xs font-semibold relative {{ request()->routeIs('persetujuan.*') ? 'text-[#0B63B5]' : 'text-slate-500' }}">
            <i data-lucide="check-square" class="w-5 h-5"></i>
            <span class="text-[10px]">Persetujuan</span>
        </a>
        @elseif(auth()->user()->isAdmin())
        <a href="{{ route('audit.loans') }}" class="flex flex-col items-center gap-1 py-1 px-3 rounded-lg text-xs font-semibold relative {{ request()->routeIs('audit.*') ? 'text-[#0B63B5]' : 'text-slate-500' }}">
            <i data-lucide="clipboard-check" class="w-5 h-5"></i>
            <span class="text-[10px]">Audit BMN</span>
        </a>
        @else
        <a href="{{ route('loans.index') }}" class="flex flex-col items-center gap-1 py-1 px-3 rounded-lg text-xs font-semibold {{ request()->routeIs('loans.index') ? 'text-[#0B63B5]' : 'text-slate-500' }}">
            <i data-lucide="file-text" class="w-5 h-5"></i>
            <span class="text-[10px]">Riwayat</span>
        </a>
        @endif
    </div>

    <!-- FLOATING TOAST POPUP NOTIFICATIONS -->
    <div x-data="toastManager()"
         x-on:show-toast.window="addToast($event.detail)"
         class="fixed top-5 right-5 z-50 flex flex-col gap-2.5 max-w-sm w-full pointer-events-none px-4 sm:px-0">
        <template x-for="t in toasts" :key="t.id">
            <div x-show="t.visible"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="pointer-events-auto p-4 rounded-2xl bg-white shadow-xl border flex items-start gap-3 transition"
                 :class="{
                     'border-emerald-200': t.type === 'success',
                     'border-red-200': t.type === 'error',
                     'border-amber-200': t.type === 'warning',
                     'border-blue-200': t.type === 'info'
                 }">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0"
                     :class="{
                         'bg-emerald-100 text-emerald-600': t.type === 'success',
                         'bg-red-100 text-red-600': t.type === 'error',
                         'bg-amber-100 text-amber-600': t.type === 'warning',
                         'bg-blue-100 text-[#0B63B5]': t.type === 'info'
                     }">
                    <template x-if="t.type === 'success'">
                        <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                    </template>
                    <template x-if="t.type === 'error'">
                        <i data-lucide="alert-octagon" class="w-4 h-4"></i>
                    </template>
                    <template x-if="t.type === 'warning'">
                        <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                    </template>
                    <template x-if="t.type === 'info'">
                        <i data-lucide="info" class="w-4 h-4"></i>
                    </template>
                </div>

                <div class="flex-1 pt-0.5">
                    <p class="text-xs font-bold text-slate-900" x-text="t.title"></p>
                    <p class="text-xs text-slate-600 mt-0.5 leading-relaxed font-medium" x-text="t.message"></p>
                </div>

                <button type="button" @click="removeToast(t.id)" class="text-slate-400 hover:text-slate-600 p-1">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        </template>
    </div>

    <!-- GLOBAL CONFIRMATION POPUP MODAL -->
    <div x-data="confirmModalComponent()"
         x-on:open-confirm-modal.window="openModal($event.detail)"
         x-show="isOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div x-show="isOpen"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>

        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div x-show="isOpen"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 @click.outside="close()"
                 class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-slate-100 p-6 sm:p-7">

                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl mb-4"
                     :class="{
                         'bg-emerald-100 text-emerald-600': type === 'success',
                         'bg-red-100 text-red-600': type === 'danger',
                         'bg-amber-100 text-amber-600': type === 'warning',
                         'bg-blue-100 text-[#0B63B5]': type === 'primary'
                     }">
                    <template x-if="type === 'success'">
                        <i data-lucide="check-circle-2" class="w-7 h-7"></i>
                    </template>
                    <template x-if="type === 'danger'">
                        <i data-lucide="alert-octagon" class="w-7 h-7"></i>
                    </template>
                    <template x-if="type === 'warning'">
                        <i data-lucide="alert-triangle" class="w-7 h-7"></i>
                    </template>
                    <template x-if="type === 'primary'">
                        <i data-lucide="help-circle" class="w-7 h-7"></i>
                    </template>
                </div>

                <div class="text-center">
                    <h3 class="text-lg font-black text-slate-900 tracking-tight" id="modal-title" x-text="title"></h3>
                    <div class="mt-2">
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed font-medium" x-text="message"></p>
                    </div>
                </div>

                <div class="mt-6 flex flex-col-reverse sm:flex-row items-center justify-end gap-2.5">
                    <button type="button" @click="close()"
                            class="w-full sm:w-auto px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 font-bold text-xs hover:bg-slate-50 transition">
                        <span x-text="cancelText"></span>
                    </button>
                    <button type="button" @click="confirm()"
                            class="w-full sm:w-auto px-6 py-2.5 rounded-xl font-bold text-xs text-white shadow-md transition flex items-center justify-center gap-1.5"
                            :class="{
                                'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-600/25': type === 'success',
                                'bg-red-600 hover:bg-red-700 shadow-red-600/25': type === 'danger',
                                'bg-amber-600 hover:bg-amber-700 shadow-amber-600/25': type === 'warning',
                                'bg-[#0B63B5] hover:bg-[#0B4F8A] shadow-blue-600/25': type === 'primary'
                            }">
                        <span x-text="confirmText"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Alpine Toast Component
        function toastManager() {
            return {
                toasts: [],
                addToast(detail) {
                    const id = Date.now();
                    const titleMap = {
                        success: 'Berhasil',
                        error: 'Terjadi Kesalahan',
                        warning: 'Peringatan',
                        info: 'Informasi'
                    };
                    const toast = {
                        id,
                        title: detail.title || titleMap[detail.type] || 'Notifikasi',
                        message: detail.message,
                        type: detail.type || 'info',
                        visible: true
                    };
                    this.toasts.push(toast);
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });

                    setTimeout(() => {
                        this.removeToast(id);
                    }, 4500);
                },
                removeToast(id) {
                    const idx = this.toasts.findIndex(t => t.id === id);
                    if (idx !== -1) {
                        this.toasts[idx].visible = false;
                        setTimeout(() => {
                            this.toasts = this.toasts.filter(t => t.id !== id);
                        }, 300);
                    }
                }
            }
        }

        // Alpine Confirmation Modal Component
        function confirmModalComponent() {
            return {
                isOpen: false,
                title: '',
                message: '',
                confirmText: 'Ya, Lanjutkan',
                cancelText: 'Batal',
                type: 'primary',
                callback: null,
                openModal(detail) {
                    this.title = detail.title || 'Konfirmasi Tindakan';
                    this.message = detail.message || 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
                    this.confirmText = detail.confirmText || 'Ya, Lanjutkan';
                    this.cancelText = detail.cancelText || 'Batal';
                    this.type = detail.type || 'primary';
                    this.callback = detail.callback || null;
                    this.isOpen = true;
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                },
                close() {
                    this.isOpen = false;
                    this.callback = null;
                },
                confirm() {
                    const cb = this.callback;
                    this.close();
                    if (cb && typeof cb === 'function') {
                        cb();
                    }
                }
            }
        }

        // Global Event Listener for declarative [data-confirm] buttons
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('[data-confirm]');
            if (!btn) return;

            e.preventDefault();
            const form = btn.closest('form');
            const message = btn.getAttribute('data-confirm');
            const title = btn.getAttribute('data-confirm-title') || 'Konfirmasi Tindakan';
            const confirmText = btn.getAttribute('data-confirm-btn') || 'Ya, Lanjutkan';
            const cancelText = btn.getAttribute('data-confirm-cancel') || 'Batal';
            const type = btn.getAttribute('data-confirm-type') || 'primary';

            window.dispatchEvent(new CustomEvent('open-confirm-modal', {
                detail: {
                    title,
                    message,
                    confirmText,
                    cancelText,
                    type,
                    callback: () => {
                        if (btn.name && btn.value && form) {
                            const hidden = document.createElement('input');
                            hidden.type = 'hidden';
                            hidden.name = btn.name;
                            hidden.value = btn.value;
                            form.appendChild(hidden);
                        }
                        if (form) {
                            form.submit();
                        } else if (btn.tagName === 'A' && btn.href) {
                            window.location.href = btn.href;
                        }
                    }
                }
            }));
        });

        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                lucide.createIcons();
            }

            @if(session('success'))
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: "{{ addslashes(session('success')) }}", type: 'success' }
                }));
            @endif

            @if(session('error'))
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: "{{ addslashes(session('error')) }}", type: 'error' }
                }));
            @endif

            @if(session('warning'))
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: "{{ addslashes(session('warning')) }}", type: 'warning' }
                }));
            @endif
        });
    </script>
</body>
</html>
