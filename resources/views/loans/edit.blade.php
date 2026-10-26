@extends('layouts.app')

@section('title', 'Edit Draf Pengajuan BMN #' . substr($loan->id, 0, 8))
@section('content')
@php
    $selectedAssetIds = $loan->items->pluck('asset_id')->map(fn($id) => (string)$id)->toArray();
    $existingPurposes = $loan->items->pluck('purpose', 'asset_id')->toArray();
@endphp
<div class="space-y-6" x-data="{
    step: 1,
    selectedAssets: {{ json_encode($selectedAssetIds) }},
    loanType: '{{ $loan->loan_type }}',
    startDate: '{{ \Carbon\Carbon::parse($loan->start_date)->toDateString() }}',
    endDate: '{{ \Carbon\Carbon::parse($loan->end_date)->toDateString() }}',
    homeAddress: '{{ addslashes($loan->snap_home_address ?? $employee->home_address ?? '') }}',
    unlocked: false,
    fullName: '{{ addslashes($loan->snap_full_name ?? $employee->full_name ?? '') }}',
    nip: '{{ addslashes($employee->formatted_nip ?? $loan->snap_nip ?? '') }}',
    rankGrade: '{{ addslashes($loan->snap_rank_grade ?? $employee->rank_grade ?? '') }}',
    position: '{{ addslashes($loan->snap_position ?? $employee->position ?? '') }}',
    phoneNumber: '{{ addslashes($employee->phone_number ?? $employee->phone ?? '') }}',
    searchQuery: '',
    selectedCategory: '',
    hideUnavailable: false,

    filterAsset(name, brandModel, nup, categoryCode, categoryLabel, plateNumber, isLoaned) {
        if (this.hideUnavailable && isLoaned) {
            return false;
        }
        if (this.selectedCategory && categoryCode !== this.selectedCategory) {
            return false;
        }
        if (this.searchQuery && this.searchQuery.trim()) {
            const q = this.searchQuery.toLowerCase().trim();
            const searchable = ((name || '') + ' ' + (brandModel || '') + ' ' + (nup || '') + ' ' + (plateNumber || '') + ' ' + (categoryLabel || '')).toLowerCase();
            if (!searchable.includes(q)) {
                return false;
            }
        }
        return true;
    },
    toggleCategory(code) {
        if (this.selectedCategory === code) {
            this.selectedCategory = '';
        } else {
            this.selectedCategory = code;
        }
    },
    clearFilters() {
        this.searchQuery = '';
        this.selectedCategory = '';
        this.hideUnavailable = false;
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
}">
    <!-- Top Bar / Back Navigation -->
    <div class="flex items-center justify-between gap-3">
        <a href="{{ route('loans.show', $loan->id) }}"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 hover:text-slate-900 hover:bg-slate-50 transition shadow-2xs">
            <i data-lucide="arrow-left" class="w-4 h-4 text-slate-500"></i>
            <span class="hidden sm:inline">Kembali ke Detail Pengajuan</span>
            <span class="sm:hidden">Kembali</span>
        </a>
        <span class="text-xs text-slate-500 font-semibold">
            Langkah <span x-text="step" class="font-black text-[#0B63B5]"></span> dari 4
        </span>
    </div>

    <!-- Header -->
    <div class="space-y-2">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Edit Draf Pengajuan Peminjaman BMN</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                    ID Pengajuan: <span class="font-mono font-bold text-slate-800">{{ $loan->id }}</span>
                </p>
            </div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border {{ $loan->status === 'NEEDS_REVISION' ? 'bg-amber-100 text-amber-900 border-amber-300' : 'bg-slate-100 text-slate-800 border-slate-300' }} self-start sm:self-auto">
                <i data-lucide="{{ $loan->status === 'NEEDS_REVISION' ? 'undo-2' : 'file-edit' }}" class="w-3.5 h-3.5"></i>
                <span>{{ $loan->status === 'NEEDS_REVISION' ? 'Perlu Revisi Dokumen' : 'Draf Pengajuan' }}</span>
            </span>
        </div>

        @if($loan->status === 'NEEDS_REVISION' && !empty($loan->review_note))
            <div class="p-4 rounded-2xl bg-amber-50/90 border border-amber-200 text-xs space-y-1.5">
                <div class="flex items-center gap-1.5 font-bold text-amber-900 uppercase tracking-wider text-[11px]">
                    <i data-lucide="message-square" class="w-4 h-4 text-amber-600"></i>
                    <span>Catatan Revisi dari Pengelola BMN:</span>
                </div>
                <p class="text-slate-800 font-medium whitespace-pre-line leading-relaxed pl-5">
                    "{{ $loan->review_note }}"
                </p>
            </div>
        @endif
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
                <span class="hidden sm:inline">Tinjau & Simpan</span>
            </div>
        </div>
        <div class="w-full bg-slate-100 h-1.5 rounded-full mt-3 overflow-hidden">
            <div class="bg-[#0B63B5] h-full transition-all duration-300"
                 :style="'width: ' + (step === 1 ? '25%' : (step === 2 ? '50%' : (step === 3 ? '75%' : '100%')))"></div>
        </div>
    </div>

    <!-- MAIN FORM (PUT) -->
    <form method="POST" action="{{ route('loans.update', $loan->id) }}" id="loanForm"
          class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm space-y-6">
        @csrf
        @method('PUT')

        <!-- STEP 1: PILIH BARANG BMN -->
        <div x-show="step === 1" class="space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Langkah 1: Sesuaikan Barang BMN yang Diajukan</h2>
                    <p class="text-xs text-slate-500">Centang atau hapus barang inventaris yang ingin diajukan dalam draf ini.</p>
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
                        @foreach($categories as $category)
                            <option value="{{ $category->code }}">{{ $category->label }} ({{ $category->assets_count }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- QUICK FILTER PILLS & HIDE UNAVAILABLE TOGGLE -->
            <div class="flex flex-wrap items-center justify-between gap-2.5 pt-1">
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mr-1">Populer:</span>
                    <button type="button" @click="selectedCategory = ''"
                            :class="selectedCategory === '' ? 'bg-[#0B63B5] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="px-2.5 py-1 rounded-lg text-xs font-bold transition shadow-2xs">
                        Semua
                    </button>
                    @foreach($popularCategories as $pop)
                        <button type="button" @click="toggleCategory('{{ $pop->code }}')"
                                :class="selectedCategory === '{{ $pop->code }}' ? 'bg-[#0B63B5] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                class="px-2.5 py-1 rounded-lg text-xs font-semibold transition shadow-2xs">
                            {{ $pop->label }}
                        </button>
                    @endforeach
                </div>

                <label class="inline-flex items-center gap-2 cursor-pointer select-none text-xs text-slate-600 hover:text-slate-900">
                    <input type="checkbox" x-model="hideUnavailable" class="rounded border-slate-300 text-[#0B63B5] focus:ring-[#0B63B5]">
                    <span class="font-medium">Hanya barang tersedia</span>
                </label>
            </div>

            <!-- ASSET CARDS GRID -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-[440px] overflow-y-auto p-1 border border-slate-100 rounded-2xl">
                @forelse($availableAssets as $asset)
                    @php
                        $isCurrentItem = in_array((string)$asset->id, $selectedAssetIds);
                        $isLoaned = $asset->is_currently_loaned && !$isCurrentItem;
                        $activeLoan = $asset->active_loan_info;
                    @endphp
                    <div x-show="filterAsset('{{ addslashes($asset->item_name) }}', '{{ addslashes($asset->brand_model_combined) }}', '{{ $asset->nup }}', '{{ $asset->category_code }}', '{{ addslashes($asset->category->label ?? '') }}', '{{ addslashes($asset->plate_number ?? '') }}', {{ $isLoaned ? 'true' : 'false' }})"
                         @click="{{ $isLoaned ? '' : "toggleAsset('" . $asset->id . "')" }}"
                         :class="{
                             'border-[#0B63B5] bg-blue-50/40 shadow-xs ring-2 ring-blue-500/20': isSelected('{{ $asset->id }}'),
                             'border-slate-200 hover:border-slate-300 bg-white': !isSelected('{{ $asset->id }}') && !{{ $isLoaned ? 'true' : 'false' }},
                             'border-slate-200 bg-slate-50/70 opacity-75 cursor-not-allowed': {{ $isLoaned ? 'true' : 'false' }}
                         }"
                         class="p-4 rounded-2xl border transition duration-150 relative flex flex-col justify-between gap-3 {{ $isLoaned ? '' : 'cursor-pointer' }}">

                        <div class="flex items-start justify-between gap-3">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase {{ $isLoaned ? 'bg-slate-200 text-slate-700' : 'bg-blue-100 text-[#0B63B5]' }}">
                                        {{ $asset->category->label ?? 'Inventaris' }}
                                    </span>
                                    <span class="text-xs font-mono text-slate-500">NUP: {{ $asset->nup }}</span>
                                </div>
                                <h3 class="font-bold text-sm text-slate-900 leading-snug">{{ $asset->item_name }}</h3>
                                <p class="text-xs text-slate-600 font-medium">{{ $asset->brand_model_combined }}</p>
                                @if($asset->plate_number)
                                    <span class="inline-block px-2 py-0.5 rounded-md bg-slate-100 text-slate-800 text-[11px] font-mono font-bold">
                                        {{ $asset->plate_number }}
                                    </span>
                                @endif
                            </div>

                            <!-- Checkbox Indicator -->
                            <div class="shrink-0">
                                @if($isLoaned)
                                    <span class="w-6 h-6 rounded-lg bg-slate-200 text-slate-500 flex items-center justify-center" title="Barang sedang dipinjam">
                                        <i data-lucide="lock" class="w-3.5 h-3.5"></i>
                                    </span>
                                @else
                                    <div :class="isSelected('{{ $asset->id }}') ? 'bg-[#0B63B5] border-[#0B63B5] text-white' : 'border-slate-300 bg-white'"
                                         class="w-6 h-6 rounded-lg border flex items-center justify-center transition">
                                        <i data-lucide="check" class="w-4 h-4" x-show="isSelected('{{ $asset->id }}')"></i>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Status & Period Tag -->
                        <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full {{ $isLoaned ? 'bg-slate-400' : 'bg-emerald-500' }}"></span>
                                <span class="font-semibold {{ $isLoaned ? 'text-slate-500' : 'text-emerald-700' }}">
                                    @if($isLoaned)
                                        Sedang Dipinjam
                                    @else
                                        Tersedia di Gudang
                                    @endif
                                </span>
                            </div>
                            @if($isLoaned && $activeLoan)
                                <span class="text-[11px] text-slate-500 tabular-nums">
                                    s.d. {{ \Carbon\Carbon::parse($activeLoan->end_date)->format('d M Y') }}
                                </span>
                            @else
                                <span class="text-[11px] text-slate-500 font-medium">Kondisi: Baik</span>
                            @endif
                        </div>

                        <!-- Hidden Form Input Checkbox -->
                        <input type="checkbox" name="asset_ids[]" value="{{ $asset->id }}"
                               :checked="isSelected('{{ $asset->id }}')" class="hidden">
                    </div>
                @empty
                    <div class="col-span-2 p-8 text-center text-slate-500 text-xs">
                        Tidak ada barang inventaris yang terdaftar.
                    </div>
                @endforelse
            </div>

            <!-- Counter Selected Assets -->
            <div class="p-3 bg-blue-50/70 rounded-xl border border-blue-100 flex items-center justify-between text-xs font-semibold text-[#0B63B5]">
                <span class="flex items-center gap-2">
                    <i data-lucide="package-check" class="w-4 h-4"></i>
                    <span>Total Barang Dipilih: <strong x-text="selectedAssets.length" class="text-base font-black"></strong> unit</span>
                </span>
                <span x-show="selectedAssets.length === 0" class="text-red-500 text-[11px]">Pilih minimal 1 barang</span>
            </div>
        </div>

        <!-- STEP 2: PERIODE & KEPERLUAN -->
        <div x-show="step === 2" class="space-y-4" x-cloak>
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-base font-bold text-slate-900">Langkah 2: Tentukan Periode & Keperluan Peminjaman</h2>
                <p class="text-xs text-slate-500">Pilih skema penugasan dan masukkan tanggal peminjaman barang.</p>
            </div>

            <!-- Jenis Penugasan / Loan Type Selector -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-2">Skema Penugasan Peminjaman</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="p-4 rounded-2xl border cursor-pointer transition flex items-start gap-3"
                           :class="loanType === 'SHORT_TERM' ? 'border-[#0B63B5] bg-blue-50/40 text-slate-900 ring-2 ring-blue-500/20' : 'border-slate-200 hover:bg-slate-50 text-slate-600'">
                        <input type="radio" name="loan_type" value="SHORT_TERM" x-model="loanType" class="mt-1 text-[#0B63B5] focus:ring-[#0B63B5]">
                        <div>
                            <strong class="block text-xs font-bold text-slate-900">Peminjaman Sementara (SHORT_TERM)</strong>
                            <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                                Untuk penugasan insidental, dinas luar, atau kebutuhan harian dengan jangka waktu tertentu.
                            </p>
                        </div>
                    </label>

                    <label class="p-4 rounded-2xl border cursor-pointer transition flex items-start gap-3"
                           :class="loanType === 'ANNUAL' ? 'border-[#0B63B5] bg-blue-50/40 text-slate-900 ring-2 ring-blue-500/20' : 'border-slate-200 hover:bg-slate-50 text-slate-600'">
                        <input type="radio" name="loan_type" value="ANNUAL" x-model="loanType" class="mt-1 text-[#0B63B5] focus:ring-[#0B63B5]">
                        <div>
                            <strong class="block text-xs font-bold text-slate-900">Peminjaman Tahunan (ANNUAL)</strong>
                            <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                                Untuk barang operasional rutin pemegang inventaris (laptop jabatan, kendaraan operasional dinas tahunan).
                            </p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Date Range Selector -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">
                        Tanggal Mulai Peminjaman <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="start_date" x-model="startDate" required
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-[#0B63B5] focus:ring-2 focus:ring-blue-100 outline-none text-xs font-semibold">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">
                        Tanggal Rencana Pengembalian <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="end_date" x-model="endDate" required
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-[#0B63B5] focus:ring-2 focus:ring-blue-100 outline-none text-xs font-semibold">
                </div>
            </div>

            <!-- Purposes for Each Selected Asset -->
            <div class="space-y-3 pt-2">
                <label class="block text-xs font-bold text-slate-700 uppercase">
                    Keperluan Penggunaan Barang BMN <span class="text-red-500">* (Wajib dicetak pada dokumen)</span>
                </label>
                @foreach($availableAssets as $asset)
                    <div x-show="isSelected('{{ $asset->id }}')" class="p-3 bg-slate-50 rounded-2xl border border-slate-200 space-y-1.5">
                        <div class="font-bold text-xs text-slate-800">
                            {{ $asset->item_name }} &bull; {{ $asset->brand_model_combined }}
                        </div>
                        <input type="text" name="purposes[{{ $asset->id }}]"
                               placeholder="Contoh: Operasional pelatihan teknis / koordinasi dinas"
                               value="{{ $existingPurposes[$asset->id] ?? 'Pelaksanaan tugas kedinasan di dalam dan luar kantor' }}"
                               class="w-full px-3 py-2 rounded-xl bg-white border border-slate-200 focus:border-[#0B63B5] outline-none text-xs font-medium">
                    </div>
                @endforeach
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

        <!-- STEP 4: REVIEW & SIMPAN PERUBAHAN -->
        <div x-show="step === 4" class="space-y-4" x-cloak>
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-base font-bold text-slate-900">Langkah 4: Tinjau & Simpan Perubahan Draf</h2>
                <p class="text-xs text-slate-500">Periksa kembali data yang telah disesuaikan sebelum menyimpan perubahan draf.</p>
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

            <!-- Info Draf -->
            <div class="p-4 rounded-2xl bg-blue-50/80 border border-blue-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 text-[#0B63B5] flex items-center justify-center shrink-0">
                        <i data-lucide="info" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="text-xs font-black text-blue-900">
                            Perubahan Disimpan ke Draf
                        </div>
                        <p class="text-[11px] text-blue-700 font-medium mt-0.5">
                            Setelah menyimpan perubahan, Anda akan kembali ke halaman detail pengajuan untuk menandatangani dan mengirimkan pengajuan ke Pengelola BMN.
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
                <a href="{{ route('loans.show', $loan->id) }}"
                   class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50 transition flex items-center gap-1.5 shadow-2xs">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    <span>Batal & Kembali ke Detail</span>
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
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span>Simpan Perubahan Draf</span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
