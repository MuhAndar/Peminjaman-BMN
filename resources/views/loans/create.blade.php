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
    searchQuery: '',
    selectedCategory: '',

    filterAsset(name, brandModel, nup, categoryCode, categoryLabel, plateNumber) {
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
    <!-- Top Bar / Back to List Navigation -->
    <div class="flex items-center justify-between gap-3">
        <a href="{{ route('loans.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 hover:text-slate-900 hover:bg-slate-50 transition shadow-2xs">
            <i data-lucide="arrow-left" class="w-4 h-4 text-slate-500"></i>
            <span>Kembali ke Daftar Pengajuan</span>
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
    <form method="POST" action="{{ route('loans.store') }}" id="loanForm" class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm space-y-6">
        @csrf

        <!-- STEP 1: PILIH BARANG BMN -->
        <div x-show="step === 1" class="space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Langkah 1: Pilih Barang BMN yang Tersedia</h2>
                    <p class="text-xs text-slate-500">Cari dan centang satu atau beberapa barang inventaris yang ingin dipinjam.</p>
                </div>
                <div class="text-xs font-semibold text-slate-500 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>Hanya kondisi Baik & Tersedia</span>
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
            <div class="space-y-1.5">
                <div class="flex items-center justify-between text-[11px]">
                    <span class="font-bold text-slate-500 flex items-center gap-1">
                        <i data-lucide="flame" class="w-3.5 h-3.5 text-amber-500"></i>
                        <span>Filter Cepat Terpopuler:</span>
                    </span>
                    <button type="button" x-show="selectedCategory || searchQuery" @click="clearFilters()" class="text-[11px] text-[#0B63B5] hover:underline font-bold">
                        Reset Filter
                    </button>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" @click="selectedCategory = ''"
                            :class="selectedCategory === '' ? 'bg-[#0B63B5] text-white border-[#0B63B5] shadow-xs' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200'"
                            class="px-3 py-1.5 rounded-xl border text-xs font-bold transition flex items-center gap-1.5 select-none cursor-pointer">
                        <span>Semua</span>
                        <span class="text-[10px] opacity-80 tabular-nums">({{ $availableAssets->count() }})</span>
                    </button>

                    @php $popularCodes = $popularCategories->pluck('code')->toArray(); @endphp
                    @foreach($popularCategories as $pop)
                        @php $catCount = $availableAssets->where('category_code', $pop->code)->count(); @endphp
                        <button type="button" @click="toggleCategory('{{ $pop->code }}')"
                                :class="selectedCategory === '{{ $pop->code }}' ? 'bg-[#0B63B5] text-white border-[#0B63B5] shadow-xs ring-2 ring-blue-200' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200'"
                                class="px-3 py-1.5 rounded-xl border text-xs font-bold transition flex items-center gap-1.5 select-none cursor-pointer">
                            <span>{{ $pop->label }}</span>
                            <span class="text-[10px] px-1.5 py-0.5 rounded-full tabular-nums"
                                  :class="selectedCategory === '{{ $pop->code }}' ? 'bg-white/20 text-white' : 'bg-slate-200/80 text-slate-600'">
                                {{ $catCount }}
                            </span>
                        </button>
                    @endforeach

                    <!-- Indikator saat kategori yang dipilih lewat dropdown di luar 5 tombol terpopuler -->
                    <template x-if="selectedCategory && !{{ json_encode($popularCodes) }}.includes(selectedCategory)">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-blue-50 border border-blue-200 text-[#0B63B5] text-xs font-bold shadow-2xs">
                            <span class="text-slate-400 text-[10px] uppercase font-bold">Kategori:</span>
                            <span x-text="document.querySelector('select[x-model=selectedCategory] option:checked')?.textContent || selectedCategory"></span>
                            <button type="button" @click="selectedCategory = ''" class="hover:text-red-600 p-0.5" title="Hapus filter kategori">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <!-- ASSET CARDS GRID -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5 max-h-[500px] overflow-y-auto pr-1">
                @foreach($availableAssets as $asset)
                    <div x-show="filterAsset('{{ addslashes($asset->item_name) }}', '{{ addslashes($asset->brand_model_combined) }}', '{{ $asset->nup }}', '{{ $asset->category_code }}', '{{ addslashes($asset->category->label ?? '') }}', '{{ addslashes($asset->plate_number ?? '') }}')"
                         @click="toggleAsset('{{ $asset->id }}')"
                         :class="isSelected('{{ $asset->id }}') ? 'border-[#0B63B5] bg-blue-50/50 shadow-sm ring-1 ring-[#0B63B5]/30' : 'border-slate-200 hover:border-slate-300'"
                         class="p-4 rounded-2xl border cursor-pointer transition flex items-start gap-3 select-none">
                        <input type="checkbox" name="asset_ids[]" value="{{ $asset->id }}"
                               :checked="isSelected('{{ $asset->id }}')"
                               class="mt-1 w-4 h-4 text-[#0B63B5] rounded border-slate-300 focus:ring-blue-500 pointer-events-none">
                        <div class="flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-bold text-sm text-slate-900">{{ $asset->item_name }}</span>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                                    {{ $asset->category->label ?? '-' }}
                                </span>
                            </div>
                            <p class="text-xs font-medium text-slate-700 mt-0.5">{{ $asset->brand_model_combined }}</p>
                            <div class="mt-2 text-[11px] text-slate-500 space-y-0.5">
                                @if($asset->plate_number)
                                    <div>Plat: <strong>{{ $asset->plate_number }}</strong></div>
                                @endif
                                @if($asset->nup)
                                    <div>NUP: <strong>{{ $asset->nup }}</strong> &bull; Tahun: {{ $asset->acquisition_year }}</div>
                                @endif
                                <div>Nilai: <span class="tabular-nums font-semibold">{{ $asset->formatted_value }}</span></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Bottom Selected Status -->
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-600 flex items-center justify-between">
                <span>Barang terpilih: <strong x-text="selectedAssets.length" class="text-[#0B63B5]"></strong> item</span>
                <button type="button" x-show="selectedAssets.length > 0" @click="selectedAssets = []" class="text-[11px] text-red-600 hover:underline font-bold">
                    Hapus Pilihan
                </button>
            </div>
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
                    <label class="p-3.5 rounded-2xl border cursor-pointer flex items-center gap-3"
                           :class="loanType === 'SHORT_TERM' ? 'border-[#0B63B5] bg-blue-50/50' : 'border-slate-200'">
                        <input type="radio" name="loan_type" value="SHORT_TERM" x-model="loanType" class="text-[#0B63B5]">
                        <div>
                            <span class="block text-xs font-bold text-slate-900">Sementara (SHORT_TERM)</span>
                            <span class="text-[11px] text-slate-500">Kegiatan dinas harian / perjalanan luar kota</span>
                        </div>
                    </label>

                    <label class="p-3.5 rounded-2xl border cursor-pointer flex items-center gap-3"
                           :class="loanType === 'ANNUAL' ? 'border-[#0B63B5] bg-blue-50/50' : 'border-slate-200'">
                        <input type="radio" name="loan_type" value="ANNUAL" x-model="loanType" class="text-[#0B63B5]">
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
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-[#0B63B5] focus:ring-2 focus:ring-blue-100 outline-none text-sm font-medium">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tanggal Pengembalian</label>
                    <input type="date" name="end_date" x-model="endDate" required
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-[#0B63B5] focus:ring-2 focus:ring-blue-100 outline-none text-sm font-medium">
                </div>
            </div>

            <!-- Purpose Inputs Per Selected Asset -->
            <div class="space-y-3 pt-2">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Uraian Keperluan Peminjaman</label>
                @foreach($availableAssets as $asset)
                    <div x-show="isSelected('{{ $asset->id }}')" class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-1.5">
                        <div class="text-xs font-bold text-slate-800">
                            {{ $asset->item_name }} &bull; {{ $asset->brand_model_combined }}
                        </div>
                        <input type="text" name="purposes[{{ $asset->id }}]"
                               placeholder="Contoh: Operasional pelatihan teknis / koordinasi dinas"
                               value="Pelaksanaan tugas kedinasan di dalam dan luar kantor"
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

        <!-- STEP 4: REVIEW & PRATINJAU -->
        <div x-show="step === 4" class="space-y-4" x-cloak>
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-base font-bold text-slate-900">Langkah 4: Tinjau & Pernyataan Tanggung Jawab Mutlak</h2>
                <p class="text-xs text-slate-500">Periksa kembali ringkasan pengajuan sebelum dikirimkan ke Pengelola BMN.</p>
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
        </div>

        <!-- WIZARD NAVIGATION CONTROLS -->
        <div class="pt-4 border-t border-slate-100 flex items-center justify-between gap-3">
            <button type="button" x-show="step > 1" @click="step--"
                    class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50 transition flex items-center gap-1.5">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Kembali</span>
            </button>
            <div x-show="step === 1">
                <a href="{{ route('loans.index') }}" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50 transition flex items-center gap-1.5 shadow-2xs">
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
                    <i data-lucide="send" class="w-4 h-4"></i>
                    <span>Kirim Pengajuan Peminjaman</span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
