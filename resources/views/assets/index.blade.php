@extends('layouts.app')

@section('title', 'Katalog Barang Milik Negara (BMN)')

@section('content')
<div class="space-y-6">
    <!-- Top Bar Navigation -->
    <div class="flex items-center justify-between gap-3">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}"
           onclick="if (document.referrer && document.referrer.indexOf(window.location.host) !== -1) { history.back(); return false; }"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-200 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-700/60 transition shadow-2xs">
            <i data-lucide="arrow-left" class="w-4 h-4 text-slate-500 dark:text-slate-400"></i>
            <span>Kembali</span>
        </a>

        @if(auth()->check() && (auth()->user()->isPengelola() || auth()->user()->isKetuaTim() || auth()->user()->isAdmin()))
            <a href="{{ route('assets.export') }}" hx-boost="false" download="Katalog_Inventaris_BMN_Makassar.xlsx"
               class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-700/60 text-emerald-800 dark:text-emerald-300 font-extrabold text-xs hover:bg-emerald-100 dark:hover:bg-emerald-900/40 transition shadow-xs">
                <i data-lucide="download" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400"></i>
                <span>Ekspor Katalog (.xlsx)</span>
            </a>
        @endif
    </div>

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Katalog Barang Milik Negara</h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                Daftar aset BMN aktif yang tersedia untuk dipinjam dalam rangka pelaksanaan tugas kedinasan.
            </p>
        </div>

        <form method="GET" action="{{ route('assets.index') }}" class="flex items-center gap-2">
            @if($selectedCategory)
                <input type="hidden" name="category" value="{{ $selectedCategory }}">
            @endif
            <div class="relative">
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari merk, tipe, NUP..."
                       class="pl-9 pr-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-medium text-slate-800 dark:text-slate-100 focus:border-[#0B63B5] dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-100 dark:focus:ring-blue-900/40 outline-none w-56 sm:w-64 transition">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-2.5"></i>
            </div>
            <button type="submit" class="px-3.5 py-2 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white text-xs font-bold rounded-xl transition cursor-pointer">
                Cari
            </button>
        </form>
    </div>

    <!-- Category Filter Chips -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
        <a href="{{ route('assets.index', ['search' => $search]) }}"
           class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition {{ !$selectedCategory ? 'bg-[#0B63B5] text-white shadow-sm' : 'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/60' }}">
            Semua Kategori
        </a>
        @foreach($categories as $cat)
            <a href="{{ route('assets.index', ['category' => $cat->code, 'search' => $search]) }}"
               class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition {{ $selectedCategory === $cat->code ? 'bg-[#0B63B5] text-white shadow-sm' : 'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/60' }}">
                {{ $cat->label }}
            </a>
        @endforeach
    </div>

    <!-- Assets Grid -->
    @if($assets->isEmpty())
        <div class="bg-white dark:bg-[#151D2F] rounded-3xl border border-slate-200/80 dark:border-slate-800 p-12 text-center">
            <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto mb-3">
                <i data-lucide="package-x" class="w-6 h-6"></i>
            </div>
            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">Tidak ada barang ditemukan</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Coba sesuaikan kata kunci pencarian atau kategori Anda.</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($assets as $asset)
                @php
                    $isAvailable = $asset->isAvailable();
                @endphp
                <div class="bg-white dark:bg-[#151D2F] rounded-2xl border border-slate-200/80 dark:border-slate-800 hover:border-blue-400 dark:hover:border-blue-500 hover:shadow-md transition p-5 flex flex-col justify-between group">
                    <div>
                        <!-- Header Card -->
                        <div class="flex items-start justify-between gap-2 mb-3">
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                @if($asset->category_code === 'LAPTOP')
                                    <i data-lucide="laptop" class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400"></i>
                                @elseif($asset->category_code === 'CAR')
                                    <i data-lucide="car" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400"></i>
                                @elseif($asset->category_code === 'MOTORCYCLE')
                                    <i data-lucide="bike" class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400"></i>
                                @else
                                    <i data-lucide="package" class="w-3.5 h-3.5 text-purple-600 dark:text-purple-400"></i>
                                @endif
                                {{ $asset->category->label ?? $asset->item_name }}
                            </span>

                            @php
                                $opStatus = $asset->operational_status;
                            @endphp
                            @if($opStatus['code'] === 'AVAILABLE')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                    Tersedia
                                </span>
                            @elseif($opStatus['code'] === 'PENDING_APPROVAL')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 dark:bg-blue-950/70 text-[#0B63B5] dark:text-blue-300 border border-blue-200 dark:border-blue-800/80">
                                    Dalam proses pengajuan
                                </span>
                            @elseif($opStatus['code'] === 'ON_LOAN')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 dark:bg-amber-950/70 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800/80">
                                    Sedang Dipinjam
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                    {{ $opStatus['label'] }}
                                </span>
                            @endif
                        </div>

                        <!-- Item Title -->
                        <h3 class="font-extrabold text-slate-900 dark:text-white text-base group-hover:text-[#0B63B5] dark:group-hover:text-blue-400 transition">
                            {{ $asset->item_name }}
                        </h3>
                        <p class="text-xs font-semibold text-slate-600 dark:text-slate-300 mt-0.5">
                            {{ $asset->brand_model_combined }}
                        </p>

                        <!-- Asset Specs Table -->
                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 text-xs space-y-1.5 text-slate-500 dark:text-slate-400 font-medium">
                            @if($asset->plate_number)
                                <div class="flex justify-between">
                                    <span>Plat Nomor:</span>
                                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ $asset->plate_number }}</span>
                                </div>
                            @endif
                            @if($asset->nup)
                                <div class="flex justify-between">
                                    <span>NUP:</span>
                                    <span class="font-bold text-slate-800 dark:text-slate-200 tabular-nums">{{ $asset->nup }}</span>
                                </div>
                            @endif
                            @if($asset->bmn_code)
                                <div class="flex justify-between">
                                    <span>Kode BMN:</span>
                                    <span class="font-medium text-slate-800 dark:text-slate-200 tabular-nums">{{ $asset->bmn_code }}</span>
                                </div>
                            @endif
                            <div class="flex justify-between">
                                <span class="text-slate-500">Tahun / Nilai:</span>
                                <span class="text-slate-800 dark:text-slate-200 font-semibold tabular-nums">{{ $asset->acquisition_year }} &bull; {{ $asset->formatted_value }}</span>
                            </div>
                        </div>

                        @if($asset->notes)
                            <p class="mt-3 text-[11px] text-slate-400 dark:text-slate-500 italic bg-slate-50 dark:bg-slate-900/60 p-2 rounded-xl">
                                "{{ $asset->notes }}"
                            </p>
                        @endif
                    </div>

                    <!-- Action Button -->
                    <div class="mt-5 pt-3 border-t border-slate-100 dark:border-slate-800">
                        @if($asset->isAvailable())
                            <a href="{{ route('loans.create', ['asset_id' => $asset->id]) }}"
                               class="w-full py-2 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-sm">
                                <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                                <span>Ajukan Peminjaman</span>
                            </a>
                        @else
                            <button disabled class="w-full py-2 bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 rounded-xl text-xs font-bold cursor-not-allowed">
                                {{ $opStatus['label'] }}
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $assets->links() }}
        </div>
    @endif
</div>
@endsection
