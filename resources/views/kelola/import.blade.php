@extends('layouts.app')

@section('title', 'Unggah Data Excel (.xlsx) — BBLSDM Komdigi')

@section('content')
<div class="space-y-6" x-data="{
    isDragging: false,
    selectedFile: null,
    fileSizeStr: '',
    dataType: 'ASSETS',
    handleFileDrop(e) {
        this.isDragging = false;
        if (e.dataTransfer.files.length > 0) {
            this.handleFileSelected(e.dataTransfer.files[0]);
        }
    },
    handleFileInput(e) {
        if (e.target.files.length > 0) {
            this.handleFileSelected(e.target.files[0]);
        }
    },
    handleFileSelected(file) {
        this.selectedFile = file;
        this.fileSizeStr = (file.size / 1024).toFixed(1) + ' KB';
    }
}">

    <!-- Top Bar Navigation -->
    <div class="flex items-center justify-between gap-3">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}"
           onclick="if (document.referrer && document.referrer.indexOf(window.location.host) !== -1) { history.back(); return false; }"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-200 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-700/60 transition shadow-2xs">
            <i data-lucide="arrow-left" class="w-4 h-4 text-slate-500 dark:text-slate-400"></i>
            <span>Kembali</span>
        </a>
    </div>

    <!-- PAGE HEADER -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white dark:bg-[#151D2F] p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition">Beranda</a>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-slate-600 dark:text-slate-400">Pengelolaan BMN</span>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-[#0B63B5] dark:text-blue-400">Unggah Data (.xlsx)</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-2xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 flex items-center justify-center shrink-0">
                    <i data-lucide="file-spreadsheet" class="w-5 h-5"></i>
                </div>
                <span>Unggah & Impor Data Spreadsheet (.xlsx)</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">
                Fasilitas impor batch untuk master barang BMN, pembaruan data fisik aset, dan arsip data melalui lembar kerja Microsoft Excel.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <a href="{{ route('kelola.import.export') }}" hx-boost="false" download="Katalog_Inventaris_BMN_Makassar.xlsx" class="px-4 py-2.5 rounded-xl border border-emerald-300 dark:border-emerald-700/60 bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-100 dark:hover:bg-emerald-900/40 text-emerald-800 dark:text-emerald-300 font-bold text-xs flex items-center gap-2 transition shadow-xs">
                <i data-lucide="table" class="w-4 h-4 text-emerald-600 dark:text-emerald-400"></i>
                <span>Ekspor Seluruh Inventaris (.xlsx)</span>
            </a>
            <a href="{{ route('kelola.import.template') }}" hx-boost="false" download="Template_Entry_Form_Peminjaman.xlsx" class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-blue-500 hover:text-blue-600 dark:hover:text-blue-400 text-slate-700 dark:text-slate-200 font-bold text-xs flex items-center gap-2 transition bg-white dark:bg-slate-800 shadow-xs">
                <i data-lucide="file-spreadsheet" class="w-4 h-4 text-[#0B63B5] dark:text-blue-400"></i>
                <span>Unduh Format Acuan Template (.xlsx)</span>
            </a>
        </div>
    </div>

    <!-- NOTICE: PANDUAN PENGELOLAAN SPREADSHEET BMN -->
    <div class="p-4 sm:p-5 rounded-2xl bg-blue-50/80 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-900/60 text-blue-950 dark:text-blue-100 flex items-start gap-3 shadow-xs">
        <div class="w-8 h-8 rounded-xl bg-blue-100 dark:bg-blue-900/70 text-[#0B63B5] dark:text-blue-400 flex items-center justify-center shrink-0 mt-0.5">
            <i data-lucide="info" class="w-4 h-4"></i>
        </div>
        <div class="text-xs space-y-1">
            <span class="font-extrabold text-slate-900 dark:text-white block text-sm">Panduan Fitur Impor & Ekspor Data Spreadsheet BMN:</span>
            <p class="text-slate-600 dark:text-slate-300 leading-relaxed font-medium">
                Modul ini memfasilitasi <strong>pembaruan massal inventaris</strong> dan <strong>pencocokan berkala data fisik aset</strong> dengan format standar SIMAN DJKN / SAKTI. Anda dapat mengunduh <strong>Format Acuan (.xlsx)</strong> sebagai standar pengisian berkas baru, atau memilih <strong>Ekspor Seluruh Inventaris (.xlsx)</strong> untuk mengunduh rekap master barang BMN terkini.
            </p>
        </div>
    </div>

    <!-- MAIN UPLOAD FORM -->
    <div class="w-full space-y-6">
        <div class="bg-white dark:bg-[#151D2F] rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-6 sm:p-8 space-y-6">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                <h2 class="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="upload-cloud" class="w-5 h-5 text-[#0B63B5] dark:text-blue-400"></i>
                    <span>Unggah Berkas Lembar Kerja</span>
                </h2>
                <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Maksimal 10 MB</span>
            </div>

            <form method="POST" action="{{ route('kelola.import.upload') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf

                <!-- Target Kategori Data -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                        Kategori Data Target
                    </label>
                    <select name="data_type" x-model="dataType"
                            class="w-full max-w-md px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 font-semibold text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#0B63B5]/20 focus:border-[#0B63B5]">
                        <option value="ASSETS">Master Data Barang Inventaris (BMN)</option>
                        <option value="EMPLOYEES">Master Data Pegawai & Pengguna</option>
                        <option value="LOAN_HISTORY">Riwayat / Log Peminjaman BMN</option>
                    </select>
                </div>

                <!-- Drag & Drop Area -->
                <div @dragover.prevent="isDragging = true"
                     @dragleave.prevent="isDragging = false"
                     @drop.prevent="handleFileDrop($event)"
                     :class="isDragging ? 'border-[#0B63B5] dark:border-blue-400 bg-blue-50/50 dark:bg-blue-950/20' : 'border-slate-300 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-900/40 hover:bg-slate-50 dark:hover:bg-slate-900/60'"
                     class="border-2 border-dashed rounded-2xl p-10 text-center transition flex flex-col items-center justify-center space-y-3 cursor-pointer relative">

                    <input type="file" name="file" accept=".xlsx,.xls,.csv"
                           @change="handleFileInput($event)"
                           class="absolute inset-0 opacity-0 cursor-pointer w-full h-full z-10" required>

                    <template x-if="!selectedFile">
                        <div class="space-y-2">
                            <div class="w-14 h-14 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto shadow-2xs">
                                <i data-lucide="file-up" class="w-7 h-7"></i>
                            </div>
                            <div class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                Tarik & letakkan berkas Excel ke sini, atau <span class="text-[#0B63B5] dark:text-blue-400 underline">Pilih Berkas</span>
                            </div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">
                                Mendukung format Microsoft Excel <code>.xlsx</code>, <code>.xls</code>, atau <code>.csv</code>
                            </div>
                        </div>
                    </template>

                    <template x-if="selectedFile">
                        <div class="space-y-2 w-full">
                            <div class="w-12 h-12 rounded-2xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 flex items-center justify-center mx-auto shadow-2xs">
                                <i data-lucide="check" class="w-6 h-6"></i>
                            </div>
                            <div class="font-extrabold text-sm text-slate-900 dark:text-white" x-text="selectedFile.name"></div>
                            <div class="text-xs text-slate-500 dark:text-slate-400 font-semibold" x-text="fileSizeStr"></div>
                            <span class="inline-block px-3 py-1 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                Berkas siap diurai & dipratinjau
                            </span>
                        </div>
                    </template>
                </div>

                <!-- Submit Button -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2">
                    <span class="text-[11px] text-slate-400 dark:text-slate-500 italic">
                        * Berkas akan diurai untuk pratinjau data sebelum disimpan permanen.
                    </span>
                    <button type="submit"
                            class="px-6 py-2.5 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white rounded-xl text-xs font-extrabold shadow-sm transition flex items-center justify-center gap-2 cursor-pointer">
                        <i data-lucide="scan-line" class="w-4 h-4"></i>
                        <span>Urai & Pratinjau Lembar Kerja</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- PREVIEW HASIL PARSER EXCEL (Jika baru saja diupload) -->
    @if(isset($success_parsed) && $success_parsed)
    <div class="bg-white dark:bg-[#151D2F] rounded-3xl border-2 border-emerald-400 dark:border-emerald-600 shadow-md p-6 space-y-5 animate-in fade-in slide-in-from-top-4 duration-200">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 flex items-center justify-center shrink-0">
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                </div>
                <div>
                    <h2 class="text-base font-extrabold text-slate-900 dark:text-white">Hasil Pembacaan Berkas Excel</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Berkas: <span class="font-bold text-slate-800 dark:text-slate-200">{{ $fileName }}</span> ({{ $fileSize }}) &bull; Sheet Aktif: <span class="font-bold text-[#0B63B5] dark:text-blue-400">{{ $sheetName }}</span>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700 tabular-nums">
                    Total {{ $totalRows }} Baris Terdeteksi
                </span>
            </div>
        </div>

        <!-- Sheet Badges -->
        @if(isset($allSheets) && count($allSheets) > 1)
        <div class="flex items-center gap-2 text-xs">
            <span class="text-slate-400 dark:text-slate-500 font-bold uppercase text-[10px]">Seluruh Sheet:</span>
            @foreach($allSheets as $sh)
                <span class="px-2.5 py-0.5 rounded-lg text-xs font-medium {{ $sh === $sheetName ? 'bg-blue-100 dark:bg-blue-900/50 text-[#0B63B5] dark:text-blue-300 font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">
                    {{ $sh }}
                </span>
            @endforeach
        </div>
        @endif

        <!-- Pratinjau Tabel Kolom & Baris -->
        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Pratinjau Data (Maksimal 10 Baris Pertama):</h4>
                <span class="text-[11px] text-slate-400 dark:text-slate-500 font-medium">Header terdeteksi: {{ count($headers) }} kolom</span>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800">
                <table class="w-full text-xs text-left">
                    <thead class="bg-slate-100 dark:bg-slate-900/70 text-slate-700 dark:text-slate-300 font-extrabold uppercase text-[10px] tracking-wider border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="p-3 text-center w-12">#</th>
                            @foreach($headers as $h)
                                <th class="p-3 whitespace-nowrap">{{ $h }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($previewRows as $idx => $rData)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-900/40 transition">
                                <td class="p-3 text-center text-slate-400 dark:text-slate-500 font-semibold tabular-nums">{{ $idx + 1 }}</td>
                                @foreach($rData as $cell)
                                    <td class="p-3 whitespace-nowrap text-slate-700 dark:text-slate-300 font-medium">{{ $cell ?: '-' }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($headers) + 1 }}" class="p-4 text-center text-slate-400 dark:text-slate-500">
                                    Tidak ada baris data setelah baris header.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tombol Aksi Akhir -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
            <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                Klik tombol konfirmasi untuk langsung memproses dan menyimpan data inventaris ke database BMN.
            </span>
            <div class="flex items-center gap-2">
                <a href="{{ route('kelola.import.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-bold transition">
                    Unggah Berkas Lain
                </a>
                <form method="POST" action="{{ route('kelola.import.commit') }}">
                    @csrf
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white text-xs font-extrabold shadow-sm hover:shadow-md active:scale-95 transition flex items-center gap-1.5 cursor-pointer">
                        <i data-lucide="check-check" class="w-4 h-4"></i>
                        <span>Konfirmasi & Simpan ke Database</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL POP UP SUKSES IMPOR DATA -->
    @if(session('import_success'))
    <div x-data="{ open: true }" x-show="open" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="open = false"></div>

            <!-- Modal Content -->
            <div class="relative transform overflow-hidden rounded-3xl bg-white dark:bg-[#151D2F] border border-slate-200/80 dark:border-slate-800 p-6 sm:p-8 text-left shadow-2xl transition-all sm:max-w-lg w-full space-y-6">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                        <i data-lucide="check-circle" class="w-7 h-7"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-slate-900 dark:text-white">
                            Impor Inventaris BMN Berhasil!
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Seluruh data lembar kerja telah disinkronkan langsung ke basis data BMN.
                        </p>
                    </div>
                </div>

                <!-- Statistik Hasil Impor -->
                <div class="grid grid-cols-3 gap-3">
                    <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 text-center">
                        <span class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Total Diproses</span>
                        <span class="text-xl font-black text-slate-900 dark:text-white tabular-nums">{{ session('import_success')['total'] }}</span>
                        <span class="text-[10px] text-slate-500 block">Baris data</span>
                    </div>
                    <div class="p-3.5 rounded-2xl bg-emerald-50/60 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 text-center">
                        <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase block mb-1">Barang Baru</span>
                        <span class="text-xl font-black text-emerald-600 dark:text-emerald-400 tabular-nums">+{{ session('import_success')['imported'] }}</span>
                        <span class="text-[10px] text-emerald-600/80 dark:text-emerald-400/80 block">Aset terdaftar</span>
                    </div>
                    <div class="p-3.5 rounded-2xl bg-blue-50/60 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 text-center">
                        <span class="text-[10px] font-bold text-[#0B63B5] dark:text-blue-400 uppercase block mb-1">Diperbarui</span>
                        <span class="text-xl font-black text-[#0B63B5] dark:text-blue-400 tabular-nums">{{ session('import_success')['updated'] }}</span>
                        <span class="text-[10px] text-slate-500 block">Aset disesuaikan</span>
                    </div>
                </div>

                <!-- Info Berkas -->
                <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 text-xs">
                    <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                        <span class="font-bold text-slate-900 dark:text-white">Berkas Sumber:</span>
                        <span class="font-mono text-slate-500 dark:text-slate-400">{{ session('import_success')['file'] }}</span>
                    </div>
                    @if(isset(session('import_success')['skipped']) && session('import_success')['skipped'] > 0)
                        <div class="mt-1 text-[11px] text-amber-600 dark:text-amber-400 font-medium">
                            * {{ session('import_success')['skipped'] }} baris dilewati karena nama aset kosong.
                        </div>
                    @endif
                </div>

                <!-- Tombol Aksi Modal -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="open = false"
                            class="px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs transition cursor-pointer">
                        Tutup
                    </button>
                    <a href="{{ route('assets.index') }}"
                       class="px-5 py-2.5 rounded-xl bg-[#0B63B5] hover:bg-[#0B4F8A] text-white font-extrabold text-xs flex items-center gap-2 transition shadow-sm cursor-pointer">
                        <i data-lucide="package" class="w-4 h-4"></i>
                        <span>Lihat Katalog BMN</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL POP UP ERROR IMPOR DATA -->
    @if(session('error'))
    <div x-data="{ open: true }" x-show="open" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="open = false"></div>

            <!-- Modal Content -->
            <div class="relative transform overflow-hidden rounded-3xl bg-white dark:bg-[#151D2F] border border-rose-200 dark:border-rose-900/60 p-6 sm:p-8 text-left shadow-2xl transition-all sm:max-w-md w-full space-y-5">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                        <i data-lucide="alert-triangle" class="w-7 h-7"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-rose-900 dark:text-rose-300">
                            Gagal Memproses Berkas
                        </h3>
                        <p class="text-xs text-rose-600 dark:text-rose-400 mt-0.5">
                            {{ session('error') }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="open = false"
                            class="px-5 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs transition cursor-pointer">
                        Mengerti & Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection
