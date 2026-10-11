@extends('layouts.app')

@section('title', 'Ajukan Peminjaman BMN Baru')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="{
    step: 1,
    selectedAssets: {{ $selectedAssetId ? json_encode([(string)$selectedAssetId]) : '[]' }},
    loanType: 'SHORT_TERM',
    startDate: '{{ now()->toDateString() }}',
    endDate: '{{ now()->addDays(3)->toDateString() }}',
    homeAddress: '{{ addslashes($employee->home_address ?? '') }}',

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
    <!-- Wizard Header -->
    <div class="text-center sm:text-left">
        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Formulir Pengajuan Peminjaman BMN</h1>
        <p class="text-xs sm:text-sm text-slate-500 mt-1">
            Isi formulir pengajuan peminjaman barang inventaris kantor BBPSDMP Makassar secara berurutan.
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
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-base font-bold text-slate-900">Langkah 1: Pilih Barang BMN yang Tersedia</h2>
                <p class="text-xs text-slate-500">Centang satu atau beberapa barang inventaris yang ingin dipinjam.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[460px] overflow-y-auto pr-1">
                @foreach($availableAssets as $asset)
                    <div @click="toggleAsset('{{ $asset->id }}')"
                         :class="isSelected('{{ $asset->id }}') ? 'border-[#0B63B5] bg-blue-50/50 shadow-sm' : 'border-slate-200 hover:border-slate-300'"
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

            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-600 flex items-center justify-between">
                <span>Barang terpilih: <strong x-text="selectedAssets.length" class="text-[#0B63B5]"></strong> item</span>
                <span class="text-slate-400 text-[11px]">Hanya barang kondisi Baik & Tersedia</span>
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
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-base font-bold text-slate-900">Langkah 3: Konfirmasi Data Diri Peminjam (Snapshot BR-07)</h2>
                <p class="text-xs text-slate-500">Data di bawah ini akan dicetak langsung pada Surat Peminjaman dan Berita Acara resmi.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nama Lengkap & Gelar</label>
                    <input type="text" value="{{ $employee->full_name }}" readonly
                           class="w-full px-3.5 py-2 rounded-xl bg-slate-100 border border-slate-200 text-sm font-bold text-slate-800 cursor-not-allowed">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">NIP (18 Digit)</label>
                    <input type="text" value="{{ $employee->formatted_nip }}" readonly
                           class="w-full px-3.5 py-2 rounded-xl bg-slate-100 border border-slate-200 text-sm font-medium text-slate-800 cursor-not-allowed tabular-nums">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Pangkat / Golongan Ruang</label>
                    <input type="text" value="{{ $employee->rank_grade ?? '-' }}" readonly
                           class="w-full px-3.5 py-2 rounded-xl bg-slate-100 border border-slate-200 text-sm font-medium text-slate-800 cursor-not-allowed">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Jabatan</label>
                    <input type="text" value="{{ $employee->position ?? '-' }}" readonly
                           class="w-full px-3.5 py-2 rounded-xl bg-slate-100 border border-slate-200 text-sm font-medium text-slate-800 cursor-not-allowed">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">
                    Alamat Rumah Peminjam <span class="text-red-500">* (Dapat disesuaikan jika pindah)</span>
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
                    <strong class="text-slate-900">{{ $employee->full_name }}</strong>
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
            <div x-show="step === 1"></div>

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
