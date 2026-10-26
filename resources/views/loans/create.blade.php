@extends('layouts.app')

@section('title', 'Ajukan Peminjaman BMN Baru')
@section('content')
<div class="space-y-6" x-data="{
    step: 1,
    selectedAssets: {{ $selectedAssetId ? json_encode([(string)$selectedAssetId]) : '[]' }},
    loanType: 'SHORT_TERM',
    startDate: '{{ now()->toDateString() }}',
    endDate: '{{ now()->addDays(3)->toDateString() }}',
    homeAddress: '{{ addslashes($employee->home_address ?? '') }}',
    unlocked: false,
    fullName: '{{ addslashes($employee->full_name ?? '') }}',
    nip: '{{ addslashes($employee->formatted_nip ?? $employee->nip ?? '') }}',
    rankGrade: '{{ addslashes($employee->rank_grade ?? '') }}',
    position: '{{ addslashes($employee->position ?? '') }}',
    phoneNumber: '{{ addslashes($employee->phone_number ?? '') }}',

    setLoanType(type) {
        this.loanType = type;
        if (type === 'ANNUAL') {
            const yr = this.startDate ? new Date(this.startDate).getFullYear() : new Date().getFullYear();
            this.endDate = `${yr}-12-31`;
        } else if (type === 'SHORT_TERM') {
            if (this.startDate) {
                const d = new Date(this.startDate);
                d.setDate(d.getDate() + 3);
                this.endDate = d.toISOString().split('T')[0];
            }
        }
        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
    },

    // Search, Filter & Tampilan
    searchQuery: '',
    selectedCategory: '',
    hideUnavailable: false,
    showSelectedOnly: false,

    // Paginasi (Pilihan 25, 50, 100)
    page: 1,
    perPage: 25,

    // Data Master Aset
    allAssets: {{ Js::from($assetsData) }},

    // Getter Filtered Assets
    get filteredAssets() {
        return this.allAssets.filter(asset => {
            if (this.showSelectedOnly && !this.selectedAssets.includes(asset.id)) {
                return false;
            }
            if (this.hideUnavailable && !asset.is_selectable) {
                return false;
            }
            if (this.selectedCategory && asset.category_code !== this.selectedCategory) {
                return false;
            }
            if (this.searchQuery && this.searchQuery.trim()) {
                const q = this.searchQuery.toLowerCase().trim();
                const searchable = ((asset.item_name || '') + ' ' + (asset.brand_model || '') + ' ' + (asset.nup || '') + ' ' + (asset.plate_number || '') + ' ' + (asset.category_label || '')).toLowerCase();
                if (!searchable.includes(q)) {
                    return false;
                }
            }
            return true;
        });
    },

    get totalPages() {
        return Math.max(1, Math.ceil(this.filteredAssets.length / this.perPage));
    },

    get paginatedAssets() {
        const start = (this.page - 1) * this.perPage;
        return this.filteredAssets.slice(start, start + this.perPage);
    },

    goToPage(p) {
        if (p >= 1 && p <= this.totalPages) {
            this.page = p;
            const el = document.getElementById('asset-selection-top');
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        }
    },

    getPageNumbers() {
        const current = this.page;
        const total = this.totalPages;
        if (total <= 7) {
            return Array.from({ length: total }, (_, i) => i + 1);
        }
        if (current <= 4) {
            return [1, 2, 3, 4, 5, '...', total];
        }
        if (current >= total - 3) {
            return [1, '...', total - 4, total - 3, total - 2, total - 1, total];
        }
        return [1, '...', current - 1, current, current + 1, '...', total];
    },

    toggleCategory(code) {
        if (this.selectedCategory === code) {
            this.selectedCategory = '';
        } else {
            this.selectedCategory = code;
        }
        this.page = 1;
    },

    clearFilters() {
        this.searchQuery = '';
        this.selectedCategory = '';
        this.hideUnavailable = false;
        this.showSelectedOnly = false;
        this.page = 1;
    },

    toggleAsset(id) {
        if (this.selectedAssets.includes(id)) {
            this.selectedAssets = this.selectedAssets.filter(item => item !== id);
        } else {
            this.selectedAssets.push(id);
        }
    },

    isSelected(id) {
        return this.selectedAssets.includes(id);
    },

    getSelectedAssetObjects() {
        return this.allAssets.filter(a => this.selectedAssets.includes(a.id));
    },

    validateStep1() {
        if (this.selectedAssets.length === 0) {
            alert('Pilih minimal 1 barang BMN untuk melanjutkan.');
            return false;
        }
        return true;
    },
    validateStep2() {
        if (!this.startDate || !this.endDate) {
            alert('Tanggal peminjaman dan pengembalian wajib diisi.');
            return false;
        }
        if (this.endDate < this.startDate) {
            alert('Tanggal pengembalian tidak boleh sebelum tanggal peminjaman.');
            return false;
        }
        return true;
    },
    validateStep3() {
        if (!this.homeAddress.trim()) {
            alert('Alamat rumah wajib diisi untuk kelengkapan dokumen resmi.');
            return false;
        }
        return true;
    }
}"
x-init="$watch('searchQuery', () => page = 1); $watch('perPage', () => page = 1); $watch('hideUnavailable', () => page = 1); $watch('showSelectedOnly', () => page = 1);">
    <!-- Top Bar / Back to List Navigation -->
    <div class="flex items-center justify-between gap-3">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('loans.index') }}"
           onclick="if (document.referrer && document.referrer.indexOf(window.location.host) !== -1) { history.back(); return false; }"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 hover:text-slate-900 hover:bg-slate-50 transition shadow-2xs">
            <i data-lucide="arrow-left" class="w-4 h-4 text-slate-500"></i>
            <span class="hidden sm:inline">Kembali ke Daftar Pengajuan</span>
            <span class="sm:hidden">Kembali</span>
        </a>
        <span class="text-xs text-slate-500 font-semibold">
            Langkah <span x-text="step" class="font-black text-[#0B63B5]"></span> dari 4
        </span>
    </div>

    <!-- Wizard Header -->
    <div class="text-center sm:text-left">
        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Formulir Pengajuan Peminjaman BMN</h1>
        <p class="text-xs sm:text-sm text-slate-500 mt-1">
            Isi formulir pengajuan peminjaman barang inventaris kantor BBLSDM Makassar secara berurutan.
        </p>
    </div>

    <!-- Stepper Progress Bar -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm">
        <div class="grid grid-cols-4 gap-2 text-center text-xs">
            <div :class="step >= 1 ? 'text-[#0B63B5] font-bold' : 'text-slate-400 font-medium'" class="flex flex-col items-center gap-1">
                <div :class="step >= 1 ? 'bg-[#0B63B5] text-white shadow-sm' : 'bg-slate-100 text-slate-400'"
                     class="w-7 h-7 rounded-full flex items-center justify-center font-bold text-xs transition">1</div>
                <span class="hidden sm:inline">Pilih Barang</span>
            </div>
            <div :class="step >= 2 ? 'text-[#0B63B5] font-bold' : 'text-slate-400 font-medium'" class="flex flex-col items-center gap-1">
                <div :class="step >= 2 ? 'bg-[#0B63B5] text-white shadow-sm' : 'bg-slate-100 text-slate-400'"
                     class="w-7 h-7 rounded-full flex items-center justify-center font-bold text-xs transition">2</div>
                <span class="hidden sm:inline">Periode & Keperluan</span>
            </div>
            <div :class="step >= 3 ? 'text-[#0B63B5] font-bold' : 'text-slate-400 font-medium'" class="flex flex-col items-center gap-1">
                <div :class="step >= 3 ? 'bg-[#0B63B5] text-white shadow-sm' : 'bg-slate-100 text-slate-400'"
                     class="w-7 h-7 rounded-full flex items-center justify-center font-bold text-xs transition">3</div>
                <span class="hidden sm:inline">Data Diri</span>
            </div>
            <div :class="step >= 4 ? 'text-[#0B63B5] font-bold' : 'text-slate-400 font-medium'" class="flex flex-col items-center gap-1">
                <div :class="step >= 4 ? 'bg-[#0B63B5] text-white shadow-sm' : 'bg-slate-100 text-slate-400'"
                     class="w-7 h-7 rounded-full flex items-center justify-center font-bold text-xs transition">4</div>
                <span class="hidden sm:inline">Tinjau & Kirim</span>
            </div>
        </div>
        <div class="w-full bg-slate-100 h-1.5 rounded-full mt-3 overflow-hidden">
            <div class="bg-[#0B63B5] h-full transition-all duration-300"
                 :style="'width: ' + (step === 1 ? '25%' : (step === 2 ? '50%' : (step === 3 ? '75%' : '100%')))"></div>
        </div>
    </div>

    <!-- MAIN FORM (POST) -->
    <form method="POST" action="{{ route('loans.store') }}" id="loanForm"
          class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm space-y-6">
        @csrf

        <!-- STEP 1: PILIH BARANG BMN -->
        <div x-show="step === 1" class="space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Langkah 1: Pilih Barang BMN yang Tersedia</h2>
                    <p class="text-xs text-slate-500">Cari dan centang satu atau beberapa barang inventaris yang ingin dipinjam.</p>
                </div>
                <div class="flex items-center gap-3 text-xs font-semibold text-slate-500">
                    <span class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>Tersedia</span>
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                        <span>Sedang Dipinjam</span>
                    </span>
                </div>
            </div>

            <!-- SEARCH BAR & CATEGORY FILTER -->
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-2.5">
                <!-- Search Input -->
                <div class="sm:col-span-8 relative">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    <input type="text" x-model="searchQuery" placeholder="Cari nama barang, merk/tipe, NUP, plat nomor..."
                           class="w-full pl-9 pr-8 py-2.5 bg-slate-50 hover:bg-white focus:bg-white rounded-xl border border-slate-200 focus:border-[#0B63B5] focus:ring-2 focus:ring-blue-100 text-xs font-medium outline-none transition">
                    <button type="button" x-show="searchQuery" @click="searchQuery = ''" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                    </button>
                </div>

                <!-- Dropdown Kategori -->
                <div class="sm:col-span-4">
                    <select x-model="selectedCategory"
                            class="w-full px-3 py-2.5 bg-slate-50 hover:bg-white focus:bg-white rounded-xl border border-slate-200 focus:border-[#0B63B5] focus:ring-2 focus:ring-blue-100 text-xs font-semibold text-slate-700 outline-none transition cursor-pointer">
                        <option value="">Semua Kategori</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->code }}">{{ $cat->label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- QUICK FILTER BUTTONS (5 JENIS PALING SERING DIPINJAM SECARA DINAMIS) -->
            <div class="space-y-2">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-[11px]">
                    <span class="font-bold text-slate-500 dark:text-slate-400 flex items-center gap-1">
                        <i data-lucide="flame" class="w-3.5 h-3.5 text-amber-500"></i>
                        <span>Filter Cepat Terpopuler:</span>
                    </span>

                    <div class="flex items-center gap-3 self-end sm:self-auto">
                        <!-- Checklist Filter: Sembunyikan Barang yang Sedang Digunakan -->
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none font-bold text-slate-700 dark:text-slate-300 hover:text-[#0B63B5] dark:hover:text-blue-400 transition bg-slate-50 dark:bg-slate-800/80 px-2.5 py-1 rounded-xl border border-slate-200 dark:border-slate-700">
                            <input type="checkbox" x-model="hideUnavailable" class="w-3.5 h-3.5 rounded text-[#0B63B5] border-slate-300 dark:border-slate-600 focus:ring-blue-500 bg-white dark:bg-slate-700 cursor-pointer">
                            <span>Sembunyikan barang sedang dipinjam</span>
                        </label>

                        <button type="button" x-show="selectedCategory || searchQuery || hideUnavailable" @click="clearFilters()" class="text-[11px] text-[#0B63B5] dark:text-blue-400 hover:underline font-bold whitespace-nowrap">
                            Reset Filter
                        </button>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" @click="selectedCategory = ''"
                            :class="selectedCategory === '' ? 'bg-[#0B63B5] text-white border-[#0B63B5] shadow-xs' : 'bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700'"
                            class="px-3 py-1.5 rounded-xl border text-xs font-bold transition flex items-center gap-1.5 select-none cursor-pointer">
                        <span>Semua</span>
                        <span class="text-[10px] opacity-80 tabular-nums">({{ $availableAssets->count() }})</span>
                    </button>

                    @php $popularCodes = $popularCategories->pluck('code')->toArray(); @endphp
                    @foreach($popularCategories as $pop)
                        @php $catCount = $availableAssets->where('category_code', $pop->code)->count(); @endphp
                        <button type="button" @click="toggleCategory('{{ $pop->code }}')"
                                :class="selectedCategory === '{{ $pop->code }}' ? 'bg-[#0B63B5] text-white border-[#0B63B5] shadow-xs ring-2 ring-blue-200' : 'bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700'"
                                class="px-3 py-1.5 rounded-xl border text-xs font-bold transition flex items-center gap-1.5 select-none cursor-pointer">
                            <span>{{ $pop->label }}</span>
                            <span class="text-[10px] px-1.5 py-0.5 rounded-full tabular-nums"
                                  :class="selectedCategory === '{{ $pop->code }}' ? 'bg-white/20 text-white' : 'bg-slate-200/80 dark:bg-slate-700 text-slate-600 dark:text-slate-300'">
                                {{ $catCount }}
                            </span>
                        </button>
                    @endforeach

                    <!-- Indikator saat kategori yang dipilih lewat dropdown di luar 5 tombol terpopuler -->
                    <template x-if="selectedCategory && !{{ json_encode($popularCodes) }}.includes(selectedCategory)">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-blue-50 dark:bg-blue-950/70 border border-blue-200 dark:border-blue-800 text-[#0B63B5] dark:text-blue-400 text-xs font-bold shadow-2xs">
                            <span class="text-slate-400 text-[10px] uppercase font-bold">Kategori:</span>
                            <span x-text="document.querySelector('select[x-model=selectedCategory] option:checked')?.textContent || selectedCategory"></span>
                            <button type="button" @click="selectedCategory = ''" class="hover:text-red-600 p-0.5" title="Hapus filter kategori">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <!-- ANCHOR SCROLL PAGINASI -->
            <div id="asset-selection-top"></div>

            <!-- PAGINATION TOOLBAR -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50 dark:bg-slate-800/60 p-3.5 rounded-2xl border border-slate-200/80 dark:border-slate-700/80 text-xs">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-slate-500 dark:text-slate-400">Menampilkan</span>
                    <span class="font-extrabold text-slate-800 dark:text-slate-100 tabular-nums"
                          x-text="filteredAssets.length === 0 ? '0' : (((page - 1) * perPage + 1) + ' - ' + Math.min(page * perPage, filteredAssets.length))"></span>
                    <span class="text-slate-500 dark:text-slate-400">dari</span>
                    <span class="font-extrabold text-slate-800 dark:text-slate-100 tabular-nums" x-text="filteredAssets.length"></span>
                    <span class="text-slate-500 dark:text-slate-400">barang</span>

                    <!-- Quick Filter: Tampilkan Hanya yang Terpilih -->
                    <template x-if="selectedAssets.length > 0">
                        <button type="button" @click="showSelectedOnly = !showSelectedOnly; page = 1"
                                :class="showSelectedOnly ? 'bg-[#0B63B5] text-white shadow-xs' : 'bg-blue-50 dark:bg-blue-950/70 text-[#0B63B5] dark:text-blue-300 border border-blue-200 dark:border-blue-800'"
                                class="ml-1 sm:ml-2 px-2.5 py-1 rounded-lg font-bold text-[11px] transition flex items-center gap-1.5 cursor-pointer">
                            <span class="w-1.5 h-1.5 rounded-full" :class="showSelectedOnly ? 'bg-white' : 'bg-[#0B63B5]'"></span>
                            <span x-text="showSelectedOnly ? 'Tampilkan Semua Barang' : ('Lihat Terpilih Saja (' + selectedAssets.length + ')')"></span>
                        </button>
                    </template>
                </div>

                <!-- Kontrol Navigasi & Ukuran Paginasi Ringkas -->
                <div class="flex items-center gap-3 self-end sm:self-auto">
                    <!-- Quick Page Switcher -->
                    <div x-show="totalPages > 1" class="flex items-center gap-1.5 border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 py-0.5 rounded-lg shadow-2xs">
                        <button type="button" @click="goToPage(page - 1)" :disabled="page === 1"
                                class="p-1 rounded text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer"
                                title="Halaman Sebelumnya">
                            <i data-lucide="chevron-left" class="w-3.5 h-3.5"></i>
                        </button>
                        <span class="text-[11px] font-extrabold text-slate-800 dark:text-slate-200 tabular-nums">
                            <span x-text="page"></span>/<span x-text="totalPages"></span>
                        </span>
                        <button type="button" @click="goToPage(page + 1)" :disabled="page === totalPages"
                                class="p-1 rounded text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer"
                                title="Halaman Selanjutnya">
                            <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>

                    <!-- Per-Page Selector (25 / 50 / 100) -->
                    <div class="flex items-center gap-1.5">
                        <label for="perPageSelect" class="text-slate-500 dark:text-slate-400 text-[11px] font-medium">Tampil:</label>
                        <select id="perPageSelect" x-model.number="perPage"
                                class="px-2.5 py-1 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 font-bold text-slate-700 dark:text-slate-200 text-xs outline-none focus:ring-2 focus:ring-[#0B63B5]/20 focus:border-[#0B63B5] cursor-pointer">
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- ASSET CARDS GRID (PAGINATED & KETINGGIAN TERBATAS SEPERTI TADI) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5 max-h-[500px] overflow-y-auto pr-1">
                <template x-for="asset in paginatedAssets" :key="asset.id">
                    <div @click="if(asset.is_selectable) toggleAsset(asset.id)"
                         :class="isSelected(asset.id) ? 'border-[#0B63B5] dark:border-blue-500 bg-blue-50/50 dark:bg-blue-950/40 shadow-sm ring-1 ring-[#0B63B5]/30' : (asset.is_selectable ? 'border-slate-200 dark:border-slate-700/80 hover:border-slate-300 dark:hover:border-slate-600 bg-white dark:bg-[#1E293B]' : 'border-slate-200 dark:border-slate-800 bg-slate-100/90 dark:bg-[#0F172A]/90 text-slate-500 dark:text-slate-400 cursor-not-allowed')"
                         :style="asset.is_selectable ? 'cursor: pointer;' : 'cursor: not-allowed;'"
                         class="p-4 rounded-2xl border transition flex items-start gap-3 select-none h-full">

                        <!-- Checkbox -->
                        <input type="checkbox" :checked="isSelected(asset.id)" :disabled="!asset.is_selectable"
                               class="mt-1 w-4 h-4 text-[#0B63B5] rounded border-slate-300 pointer-events-none shrink-0"
                               :class="asset.is_selectable ? 'focus:ring-blue-500' : 'opacity-60 bg-slate-200 dark:bg-slate-800 cursor-not-allowed'">

                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2.5">
                                <!-- Kolom Kiri: Nama Barang & Spesifikasi -->
                                <div class="min-w-0 flex-1">
                                    <h3 class="font-bold text-sm truncate"
                                        :class="!asset.is_selectable ? 'text-slate-700 dark:text-slate-300' : 'text-slate-900 dark:text-white'"
                                        :title="asset.item_name" x-text="asset.item_name">
                                    </h3>
                                    <p class="text-xs font-medium mt-0.5 truncate"
                                       :class="!asset.is_selectable ? 'text-slate-500 dark:text-slate-400' : 'text-slate-700 dark:text-slate-300'"
                                       :title="asset.brand_model" x-text="asset.brand_model || '-'">
                                    </p>

                                    <div class="mt-2 text-[11px] text-slate-500 dark:text-slate-400 space-y-0.5">
                                        <template x-if="asset.plate_number">
                                            <div>Plat: <strong class="text-slate-700 dark:text-slate-200" x-text="asset.plate_number"></strong></div>
                                        </template>
                                        <template x-if="asset.nup">
                                            <div>NUP: <strong class="text-slate-700 dark:text-slate-200" x-text="asset.nup"></strong> &bull; Thn: <span x-text="asset.acquisition_year"></span></div>
                                        </template>
                                        <div>Nilai: <span class="tabular-nums font-semibold" :class="!asset.is_selectable ? 'text-slate-700 dark:text-slate-300' : 'text-slate-800 dark:text-slate-200'" x-text="asset.formatted_value"></span></div>
                                    </div>
                                </div>

                                <!-- Kolom Kanan: Kategori (Atas) & Status Ketersediaan / Peminjaman (Bawah) -->
                                <div class="shrink-0 flex flex-col items-end gap-1.5 text-right">
                                    <!-- 1. Kategori Barang -->
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-transparent dark:border-slate-700/60 truncate max-w-[130px]"
                                          :title="asset.category_label" x-text="asset.category_label">
                                    </span>

                                    <!-- 2. Status & Informasi Tanggal -->
                                    <!-- PENDING_APPROVAL -->
                                    <template x-if="asset.op_code === 'PENDING_APPROVAL'">
                                        <span class="inline-flex items-center gap-1 text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-950/70 text-[#0B63B5] dark:text-blue-300 border border-blue-200 dark:border-blue-800/80 shadow-2xs">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#0B63B5]"></span>
                                            <span>Dalam proses pengajuan</span>
                                        </span>
                                    </template>

                                    <!-- ON_LOAN -->
                                    <template x-if="asset.op_code === 'ON_LOAN'">
                                        <div class="flex flex-col items-end gap-0.5">
                                            <template x-if="asset.is_overdue">
                                                <div class="flex flex-col items-end">
                                                    <span class="inline-flex items-center gap-1 text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-rose-100 dark:bg-rose-950/70 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/80 shadow-2xs">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                                                        <span>Belum Kembali</span>
                                                    </span>
                                                    <div class="text-[10px] text-rose-600 dark:text-rose-400 font-semibold leading-tight whitespace-nowrap mt-0.5" x-text="'Tenggat: ' + asset.loan_end_date"></div>
                                                </div>
                                            </template>
                                            <template x-if="!asset.is_overdue">
                                                <div class="flex flex-col items-end">
                                                    <span class="inline-flex items-center gap-1 text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950/70 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800/80 shadow-2xs">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                        <span>Sedang Dipinjam</span>
                                                    </span>
                                                    <template x-if="asset.loan_period">
                                                        <div class="text-[10px] text-slate-500 dark:text-slate-400 font-medium leading-tight whitespace-nowrap mt-0.5" x-text="asset.loan_period"></div>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    <!-- AVAILABLE -->
                                    <template x-if="asset.op_code === 'AVAILABLE'">
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-800/80">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            <span>Tersedia</span>
                                        </span>
                                    </template>

                                    <!-- OTHER (MAINTENANCE, RUSAK, DLL) -->
                                    <template x-if="asset.op_code !== 'PENDING_APPROVAL' && asset.op_code !== 'ON_LOAN' && asset.op_code !== 'AVAILABLE'">
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200/80 dark:border-rose-800/80" x-text="asset.op_label"></span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- EMPTY STATE JIKA PENCARIAN / FILTER KOSONG -->
            <template x-if="filteredAssets.length === 0">
                <div class="p-8 text-center bg-slate-50 dark:bg-slate-900/40 rounded-2xl border border-dashed border-slate-300 dark:border-slate-700 space-y-2">
                    <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto">
                        <i data-lucide="search-x" class="w-5 h-5"></i>
                    </div>
                    <div class="text-xs font-bold text-slate-700 dark:text-slate-300">Tidak ada barang BMN yang cocok</div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Silakan ubah kata kunci pencarian atau reset filter kategori.</p>
                    <button type="button" @click="clearFilters()" class="text-xs font-bold text-[#0B63B5] dark:text-blue-400 hover:underline pt-1 inline-block">
                        Reset Semua Filter
                    </button>
                </div>
            </template>

            <!-- PAGINATION CONTROLS (BAWAH) -->
            <div x-show="totalPages > 1" class="pt-2 select-none">
                <!-- Tampilan Mobile (<640px): Stepper Kompak & Rata -->
                <div class="flex sm:hidden items-center justify-between w-full gap-2">
                    <button type="button" @click="goToPage(page - 1)" :disabled="page === 1"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-200 transition disabled:opacity-40 disabled:cursor-not-allowed active:scale-95 shadow-2xs">
                        <i data-lucide="chevron-left" class="w-3.5 h-3.5"></i>
                        <span>Sebelumnya</span>
                    </button>

                    <div class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 rounded-xl border border-slate-200/80 dark:border-slate-700 text-xs font-extrabold text-slate-800 dark:text-slate-200 tabular-nums">
                        <span x-text="page"></span> <span class="text-slate-400 font-normal">/</span> <span x-text="totalPages"></span>
                    </div>

                    <button type="button" @click="goToPage(page + 1)" :disabled="page === totalPages"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-200 transition disabled:opacity-40 disabled:cursor-not-allowed active:scale-95 shadow-2xs">
                        <span>Berikutnya</span>
                        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                    </button>
                </div>

                <!-- Tampilan Desktop (>=640px): Lengkap dengan Nomor Halaman -->
                <div class="hidden sm:flex sm:items-center justify-between gap-3">
                    <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                        Halaman <strong class="text-slate-800 dark:text-slate-200" x-text="page"></strong> dari <strong class="text-slate-800 dark:text-slate-200" x-text="totalPages"></strong>
                    </div>

                    <div class="flex items-center gap-1">
                        <!-- Tombol Halaman Pertama -->
                        <button type="button" @click="goToPage(1)" :disabled="page === 1"
                                class="px-2 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold transition disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200"
                                title="Halaman Pertama">
                            &laquo;
                        </button>

                        <!-- Tombol Halaman Sebelumnya -->
                        <button type="button" @click="goToPage(page - 1)" :disabled="page === 1"
                                class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold transition disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200">
                            Sebelumnya
                        </button>

                        <!-- Angka Halaman -->
                        <div class="flex items-center gap-1">
                            <template x-for="p in getPageNumbers()" :key="'page-' + p">
                                <div>
                                    <template x-if="p === '...'">
                                        <span class="px-2 py-1 text-xs text-slate-400 font-bold select-none">&hellip;</span>
                                    </template>
                                    <template x-if="p !== '...'">
                                        <button type="button" @click="goToPage(p)"
                                                :class="page === p ? 'bg-[#0B63B5] text-white border-[#0B63B5] shadow-xs' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700'"
                                                class="w-8 h-8 rounded-lg border text-xs font-extrabold transition flex items-center justify-center cursor-pointer"
                                                x-text="p">
                                        </button>
                                    </template>
                                </div>
                            </template>
                        </div>

                        <!-- Tombol Halaman Selanjutnya -->
                        <button type="button" @click="goToPage(page + 1)" :disabled="page === totalPages"
                                class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold transition disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200">
                            Selanjutnya
                        </button>

                        <!-- Tombol Halaman Terakhir -->
                        <button type="button" @click="goToPage(totalPages)" :disabled="page === totalPages"
                                class="px-2 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold transition disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200"
                                title="Halaman Terakhir">
                            &raquo;
                        </button>
                    </div>
                </div>
            </div>

            <!-- Bottom Selected Status -->
            <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/80 text-xs text-slate-600 dark:text-slate-300 flex items-center justify-between">
                <span>Barang terpilih: <strong x-text="selectedAssets.length" class="text-[#0B63B5] dark:text-blue-400 font-extrabold"></strong> item</span>
                <button type="button" x-show="selectedAssets.length > 0" @click="selectedAssets = []" class="text-[11px] text-red-600 hover:underline font-bold">
                    Hapus Pilihan
                </button>
            </div>

            <!-- Hidden Inputs for Selected Asset IDs Form Submission -->
            <template x-for="id in selectedAssets" :key="'submit-asset-' + id">
                <input type="hidden" name="asset_ids[]" :value="id">
            </template>
        </div>

        <!-- STEP 2: PERIODE & KEPERLUAN -->
        <div x-show="step === 2" class="space-y-5" x-cloak>
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-base font-bold text-slate-900">Langkah 2: Tentukan Periode & Keperluan Peminjaman</h2>
                <p class="text-xs text-slate-500">Pilih jenis penugasan, tanggal mulai s.d. selesai, serta uraian tugas.</p>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Jenis Peminjaman</label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="p-3.5 rounded-2xl border cursor-pointer flex items-center gap-3 transition"
                           :class="loanType === 'SHORT_TERM' ? 'border-[#0B63B5] bg-blue-50/50 ring-2 ring-blue-500/20' : 'border-slate-200 hover:bg-slate-50'">
                        <input type="radio" name="loan_type" value="SHORT_TERM" x-model="loanType" @change="setLoanType('SHORT_TERM')" class="text-[#0B63B5]">
                        <div>
                            <span class="block text-xs font-bold text-slate-900">Sementara (SHORT_TERM)</span>
                            <span class="text-[11px] text-slate-500">Kegiatan dinas harian / perjalanan luar kota</span>
                        </div>
                    </label>

                    <label class="p-3.5 rounded-2xl border cursor-pointer flex items-center gap-3 transition"
                           :class="loanType === 'ANNUAL' ? 'border-[#0B63B5] bg-blue-50/50 ring-2 ring-blue-500/20' : 'border-slate-200 hover:bg-slate-50'">
                        <input type="radio" name="loan_type" value="ANNUAL" x-model="loanType" @change="setLoanType('ANNUAL')" class="text-[#0B63B5]">
                        <div>
                            <span class="block text-xs font-bold text-slate-900">Penugasan Tahunan (ANNUAL)</span>
                            <span class="text-[11px] text-slate-500">Laptop/HP dinas selama 1 tahun anggaran berjalan</span>
                        </div>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tanggal Peminjaman</label>
                    <input type="date" name="start_date" x-model="startDate" required
                           @change="if (loanType === 'ANNUAL') setLoanType('ANNUAL')"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-[#0B63B5] focus:ring-2 focus:ring-blue-100 outline-none text-sm font-medium">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tanggal Pengembalian</label>
                    <input type="date" name="end_date" x-model="endDate" required
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-[#0B63B5] focus:ring-2 focus:ring-blue-100 outline-none text-sm font-medium">
                    <template x-if="loanType === 'ANNUAL'">
                        <p class="text-[11px] text-[#0B63B5] font-semibold mt-1.5 flex items-center gap-1.5">
                            <i data-lucide="info" class="w-3.5 h-3.5 shrink-0"></i>
                            <span>Otomatis diset hingga akhir tahun anggaran berjalan (31 Desember) untuk re-inventarisasi aset.</span>
                        </p>
                    </template>
                </div>
            </div>

            <!-- Purpose Inputs Per Selected Asset (Hanya Render Barang yang Terpilih) -->
            <div class="space-y-3 pt-2">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Uraian Keperluan Peminjaman</label>
                <template x-for="asset in getSelectedAssetObjects()" :key="'purpose-' + asset.id">
                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-1.5">
                        <div class="text-xs font-bold text-slate-800" x-text="asset.item_name + ' • ' + (asset.brand_model || '-')"></div>
                        <input type="text" :name="'purposes[' + asset.id + ']'"
                               placeholder="Contoh: Operasional pelatihan teknis / koordinasi dinas"
                               value="Pelaksanaan tugas kedinasan di dalam dan luar kantor"
                               class="w-full px-3 py-2 rounded-xl bg-white border border-slate-200 focus:border-[#0B63B5] outline-none text-xs font-medium">
                    </div>
                </template>
            </div>
        </div>

        <!-- STEP 3: DATA DIRI PEMINJAM -->
        <div x-show="step === 3" class="space-y-4" x-cloak>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Langkah 3: Konfirmasi Data Diri Peminjam (Snapshot BR-07)</h2>
                    <p class="text-xs text-slate-500">Data di bawah ini akan dicetak langsung pada Surat Peminjaman dan Berita Acara resmi.</p>
                </div>
                <!-- Tombol Buka Gembok -->
                <button type="button" @click="unlocked = !unlocked"
                        class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 self-start sm:self-auto shrink-0 shadow-2xs"
                        :class="unlocked ? 'bg-amber-50 text-amber-700 border-2 border-amber-300 hover:bg-amber-100' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300'">
                    <i :data-lucide="unlocked ? 'lock-open' : 'lock'" class="w-4 h-4" :class="unlocked ? 'text-amber-600' : 'text-slate-500'"></i>
                    <span x-text="unlocked ? 'Kunci Kembali Formulir' : 'Buka Kunci untuk Mengisi/Ubah Data'"></span>
                </button>
            </div>

            <div x-show="unlocked" class="p-3 rounded-xl bg-amber-50/70 border border-amber-200 text-amber-900 text-xs flex items-center gap-2.5 animate-in fade-in">
                <i data-lucide="info" class="w-4 h-4 text-amber-600 shrink-0"></i>
                <span>Mode edit aktif: Perubahan data diri di bawah ini akan sekaligus memperbarui data profil akun peminjam Anda.</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase mb-1" :class="unlocked ? 'text-slate-700' : 'text-slate-500'">
                        Nama Lengkap & Gelar <span x-show="unlocked" class="text-red-500">*</span>
                    </label>
                    <input type="text" name="full_name" x-model="fullName" :readonly="!unlocked"
                           class="w-full px-3.5 py-2 rounded-xl text-sm font-bold transition outline-none"
                           :class="unlocked ? 'bg-white border-2 border-amber-300 text-slate-900 focus:border-[#0B63B5] focus:ring-2 focus:ring-blue-100' : 'bg-slate-100 border border-slate-200 text-slate-800 cursor-not-allowed'">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase mb-1" :class="unlocked ? 'text-slate-700' : 'text-slate-500'">
                        NIP (18 Digit) <span x-show="unlocked" class="text-red-500">*</span>
                    </label>
                    <input type="text" name="nip" x-model="nip" :readonly="!unlocked"
                           class="w-full px-3.5 py-2 rounded-xl text-sm font-medium tabular-nums transition outline-none"
                           :class="unlocked ? 'bg-white border-2 border-amber-300 text-slate-900 focus:border-[#0B63B5] focus:ring-2 focus:ring-blue-100' : 'bg-slate-100 border border-slate-200 text-slate-800 cursor-not-allowed'">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase mb-1" :class="unlocked ? 'text-slate-700' : 'text-slate-500'">
                        Pangkat / Golongan Ruang
                    </label>
                    <input type="text" name="rank_grade" x-model="rankGrade" :readonly="!unlocked" placeholder="Contoh: Penata Muda Tk. I, III/b"
                           class="w-full px-3.5 py-2 rounded-xl text-sm font-medium transition outline-none"
                           :class="unlocked ? 'bg-white border-2 border-amber-300 text-slate-900 focus:border-[#0B63B5] focus:ring-2 focus:ring-blue-100' : 'bg-slate-100 border border-slate-200 text-slate-800 cursor-not-allowed'">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase mb-1" :class="unlocked ? 'text-slate-700' : 'text-slate-500'">
                        Jabatan
                    </label>
                    <input type="text" name="position" x-model="position" :readonly="!unlocked" placeholder="Contoh: Pranata Komputer Ahli Pertama"
                           class="w-full px-3.5 py-2 rounded-xl text-sm font-medium transition outline-none"
                           :class="unlocked ? 'bg-white border-2 border-amber-300 text-slate-900 focus:border-[#0B63B5] focus:ring-2 focus:ring-blue-100' : 'bg-slate-100 border border-slate-200 text-slate-800 cursor-not-allowed'">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold uppercase mb-1" :class="unlocked ? 'text-slate-700' : 'text-slate-500'">
                        Nomor Kontak / WhatsApp
                    </label>
                    <input type="text" name="phone_number" x-model="phoneNumber" :readonly="!unlocked" placeholder="Contoh: 081234567890"
                           class="w-full px-3.5 py-2 rounded-xl text-sm font-medium transition outline-none"
                           :class="unlocked ? 'bg-white border-2 border-amber-300 text-slate-900 focus:border-[#0B63B5] focus:ring-2 focus:ring-blue-100' : 'bg-slate-100 border border-slate-200 text-slate-800 cursor-not-allowed'">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">
                    Alamat Rumah Peminjam <span class="text-red-500">* (Wajib dicetak pada dokumen)</span>
                </label>
                <textarea name="home_address" x-model="homeAddress" rows="3" required
                          placeholder="Masukkan alamat rumah lengkap peminjam..."
                          class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-[#0B63B5] focus:ring-2 focus:ring-blue-100 outline-none text-xs font-medium leading-relaxed"></textarea>
            </div>
        </div>

        <!-- STEP 4: REVIEW & PRATINJAU -->
        <div x-show="step === 4" class="space-y-4" x-cloak>
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-base font-bold text-slate-900">Langkah 4: Tinjau & Simpan Draf</h2>
                <p class="text-xs text-slate-500">Periksa kembali ringkasan pengajuan sebelum disimpan sebagai draf peminjaman BMN.</p>
            </div>

            <!-- Review Summary Card -->
            <div class="p-5 rounded-2xl bg-blue-50/60 border border-blue-200/80 space-y-3">
                <div class="flex justify-between items-center text-xs pb-2 border-b border-blue-200/60">
                    <span class="text-slate-500">Peminjam:</span>
                    <strong class="text-slate-900" x-text="fullName"></strong>
                </div>
                <div class="flex justify-between items-center text-xs pb-2 border-b border-blue-200/60">
                    <span class="text-slate-500">Periode:</span>
                    <strong class="text-slate-900 tabular-nums"><span x-text="startDate"></span> s.d. <span x-text="endDate"></span></strong>
                </div>
                <div class="flex justify-between items-center text-xs pb-2 border-b border-blue-200/60">
                    <span class="text-slate-500">Alamat Rumah Tercetak:</span>
                    <strong class="text-slate-900 text-right max-w-xs" x-text="homeAddress"></strong>
                </div>
                <div class="text-xs">
                    <span class="text-slate-500 block mb-1">Barang yang Diajukan:</span>
                    <span class="font-bold text-[#0B63B5]" x-text="selectedAssets.length + ' item barang BMN'"></span>
                </div>
            </div>

            <!-- Legal Commitment Note from DOCUMENT_TEMPLATES.md -->
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs text-slate-600 space-y-2 leading-relaxed">
                <p>
                    <strong>Pernyataan Sesuai Template Dokumen Resmi:</strong><br>
                    "Atas peminjaman barang inventaris tersebut saya menyatakan akan menggunakan barang sesuai peruntukan, menjaga dengan baik, dan mengembalikan sesuai waktu peminjaman atau barang diminta kembali oleh Satker."
                </p>
                <p>
                    "Apabila terjadi kehilangan atau kerusakan atas barang inventaris tersebut yang menimbulkan kerugian negara, saya bertanggung jawab mutlak dan tunduk pada peraturan yang berlaku, seperti yang telah diatur dalam <strong>Undang-Undang RI No. 1 Tahun 2004 tanggal 14 Januari 2004</strong>."
                </p>
            </div>

            <!-- Alur Draf & Pengiriman Informasi -->
            <div class="p-4 rounded-2xl bg-blue-50/80 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800/60 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-blue-400 flex items-center justify-center shrink-0">
                        <i data-lucide="file-text" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="text-xs font-black text-blue-900 dark:text-blue-200 flex items-center gap-1.5">
                            <span>Disimpan Sebagai Draf Peminjaman</span>
                        </div>
                        <p class="text-[11px] text-blue-700 dark:text-blue-300 font-medium mt-0.5">
                            Pengajuan akan diarahkan ke halaman detail pengajuan. Anda dapat menandatangani surat dan mengirim pengajuan secara resmi ke Pengelola BMN di halaman detail.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- WIZARD NAVIGATION CONTROLS -->
        <div class="pt-4 border-t border-slate-100 flex items-center justify-between gap-3">
            <button type="button" x-show="step > 1" @click="step--"
                    class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50 transition flex items-center gap-1.5">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Kembali</span>
            </button>
            <div x-show="step === 1">
                <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('loans.index') }}"
                   onclick="if (document.referrer && document.referrer.indexOf(window.location.host) !== -1) { history.back(); return false; }"
                   class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50 transition flex items-center gap-1.5 shadow-2xs">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    <span>Batal & Kembali</span>
                </a>
            </div>

            <div>
                <button type="button" x-show="step === 1" @click="if(validateStep1()) step = 2"
                        class="px-6 py-2.5 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white text-xs font-bold rounded-xl shadow-sm transition flex items-center gap-1.5">
                    <span>Lanjutkan: Periode</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>

                <button type="button" x-show="step === 2" @click="if(validateStep2()) step = 3"
                        class="px-6 py-2.5 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white text-xs font-bold rounded-xl shadow-sm transition flex items-center gap-1.5">
                    <span>Lanjutkan: Data Diri</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>

                <button type="button" x-show="step === 3" @click="if(validateStep3()) step = 4"
                        class="px-6 py-2.5 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white text-xs font-bold rounded-xl shadow-sm transition flex items-center gap-1.5">
                    <span>Lanjutkan: Tinjau Dokumen</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>

                <button type="submit" x-show="step === 4"
                        class="px-8 py-3 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white text-xs font-black rounded-xl shadow-md shadow-blue-500/25 transition flex items-center gap-2">
                    <i data-lucide="file-plus-2" class="w-4 h-4"></i>
                    <span>Simpan Draf Pengajuan</span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
