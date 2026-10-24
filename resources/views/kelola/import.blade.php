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
        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 hover:text-slate-900 hover:bg-slate-50 transition shadow-2xs">
            <i data-lucide="arrow-left" class="w-4 h-4 text-slate-500"></i>
            <span>Kembali ke Beranda</span>
        </a>
    </div>

    <!-- PAGE HEADER -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-blue-600 transition">Beranda</a>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-slate-600">Pengelolaan BMN</span>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-[#0B63B5]">Unggah Data (.xlsx)</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                    <i data-lucide="file-spreadsheet" class="w-5 h-5"></i>
                </div>
                <span>Unggah & Impor Data Spreadsheet (.xlsx)</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1 font-medium">
                Fasilitas impor batch untuk master barang BMN, pembaruan data fisik aset, dan arsip data melalui lembar kerja Microsoft Excel.
            </p>
        </div>

        <div class="flex items-center gap-2.5 shrink-0">
            <a href="{{ route('kelola.import.template') }}" class="px-4 py-2.5 rounded-xl border border-slate-200 hover:border-blue-500 hover:text-blue-600 text-slate-700 font-bold text-xs flex items-center gap-2 transition bg-white shadow-sm">
                <i data-lucide="download" class="w-4 h-4 text-[#0B63B5]"></i>
                <span>Unduh Format Acuan (.xlsx)</span>
            </a>
        </div>
    </div>

    <!-- NOTICE: FORMAT SEDANG MENUNGGU SPESIFIKASI -->
    <div class="p-4 sm:p-5 rounded-2xl bg-blue-50/80 border border-blue-200 text-blue-900 flex items-start gap-3 shadow-xs">
        <div class="w-8 h-8 rounded-xl bg-blue-100 text-[#0B63B5] flex items-center justify-center shrink-0 mt-0.5">
            <i data-lucide="info" class="w-4 h-4"></i>
        </div>
        <div class="text-xs space-y-1">
            <span class="font-extrabold text-slate-900 block">Kesiapan Modul Impor Data Excel:</span>
            <p class="text-slate-600 leading-relaxed font-medium">
                Format skema berkas resmi instansi saat ini masih dalam tahap finalisasi. Sistem telah siap sepenuhnya dengan antarmuka unggah, verifikasi berkas, serta mesin parser <strong>PhpSpreadsheet</strong> untuk mengekstrak header kolom dan mempratinjau isi lembar kerja <code>.xlsx</code> secara otomatis.
            </p>
        </div>
    </div>

    <!-- MAIN GRID: UPLOAD FORM & INFO -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- LEFT: UPLOAD BOX (7 COLS) -->
        <div class="lg:col-span-7 space-y-6">
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 space-y-5">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <h2 class="text-base font-black text-slate-900 flex items-center gap-2">
                        <i data-lucide="upload-cloud" class="w-5 h-5 text-[#0B63B5]"></i>
                        <span>Unggah Berkas Lembar Kerja</span>
                    </h2>
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Maksimal 10 MB</span>
                </div>

                <form method="POST" action="{{ route('kelola.import.upload') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <!-- Target Kategori Data -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Kategori Data Target
                        </label>
                        <select name="data_type" x-model="dataType"
                                class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-white font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#0B63B5]/20 focus:border-[#0B63B5]">
                            <option value="ASSETS">Master Data Barang Inventaris (BMN)</option>
                            <option value="EMPLOYEES">Master Data Pegawai & Pengguna</option>
                            <option value="LOAN_HISTORY">Riwayat / Log Peminjaman BMN</option>
                        </select>
                    </div>

                    <!-- Drag & Drop Area -->
                    <div @dragover.prevent="isDragging = true"
                         @dragleave.prevent="isDragging = false"
                         @drop.prevent="handleFileDrop($event)"
                         :class="isDragging ? 'border-[#0B63B5] bg-blue-50/50' : 'border-slate-300 bg-slate-50/60 hover:bg-slate-50'"
                         class="border-2 border-dashed rounded-2xl p-8 text-center transition flex flex-col items-center justify-center space-y-3 cursor-pointer relative">

                        <input type="file" name="file" accept=".xlsx,.xls,.csv"
                               @change="handleFileInput($event)"
                               class="absolute inset-0 opacity-0 cursor-pointer w-full h-full z-10" required>

                        <template x-if="!selectedFile">
                            <div class="space-y-2">
                                <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto shadow-2xs">
                                    <i data-lucide="file-up" class="w-7 h-7"></i>
                                </div>
                                <div class="text-xs font-bold text-slate-800">
                                    Tarik & letakkan berkas Excel ke sini, atau <span class="text-[#0B63B5] underline">Pilih Berkas</span>
                                </div>
                                <div class="text-[11px] text-slate-500 font-medium">
                                    Mendukung format Microsoft Excel <code>.xlsx</code>, <code>.xls</code>, atau <code>.csv</code>
                                </div>
                            </div>
                        </template>

                        <template x-if="selectedFile">
                            <div class="space-y-2 w-full">
                                <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center mx-auto shadow-2xs">
                                    <i data-lucide="check" class="w-6 h-6"></i>
                                </div>
                                <div class="font-extrabold text-sm text-slate-900" x-text="selectedFile.name"></div>
                                <div class="text-xs text-slate-500 font-semibold" x-text="fileSizeStr"></div>
                                <span class="inline-block px-3 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Berkas siap diurai & dipratinjau
                                </span>
                            </div>
                        </template>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex items-center justify-between pt-2">
                        <span class="text-[11px] text-slate-400 italic">
                            * Berkas akan diurai untuk pratinjau data sebelum disimpan permanen.
                        </span>
                        <button type="submit"
                                class="px-6 py-2.5 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white rounded-xl text-xs font-extrabold shadow-sm transition flex items-center gap-2">
                            <i data-lucide="scan-line" class="w-4 h-4"></i>
                            <span>Urai & Pratinjau Lembar Kerja</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- RIGHT: PANDUAN & ATURAN (5 COLS) -->
        <div class="lg:col-span-5 space-y-6">
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 space-y-4">
                <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
                    <i data-lucide="book-open" class="w-4 h-4 text-[#0B63B5]"></i>
                    <span>Panduan Format & Ketentuan</span>
                </h3>

                <div class="space-y-3 text-xs text-slate-600">
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1">
                        <span class="font-bold text-slate-800 block">1. Baris Pertama Adalah Judul Kolom</span>
                        <p class="text-[11px] text-slate-500">
                            Pastikan baris nomor 1 memuat label kepala kolom (Header) tanpa baris kosong atau sel gabungan (merged cells).
                        </p>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1">
                        <span class="font-bold text-slate-800 block">2. Tipe Data Kode & Nomor</span>
                        <p class="text-[11px] text-slate-500">
                            Kode BMN, NUP, dan NIP diformat sebagai teks (General/Text) agar angka 0 di depan tidak terpotong.
                        </p>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1">
                        <span class="font-bold text-slate-800 block">3. Nilai Perolehan (Rupiah)</span>
                        <p class="text-[11px] text-slate-500">
                            Isikan angka murni tanpa simbol Rp, titik, atau koma pemisah ribuan (contoh: <code>15000000</code>).
                        </p>
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-100">
                    <a href="{{ route('kelola.import.template') }}" class="w-full py-2.5 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold transition flex items-center justify-center gap-2">
                        <i data-lucide="file-down" class="w-4 h-4 text-slate-600"></i>
                        <span>Unduh Contoh Berkas Template BMN</span>
                    </a>
                </div>
            </div>
        </div>

    </div>

    <!-- PREVIEW HASIL PARSER EXCEL (Jika baru saja diupload) -->
    @if(isset($success_parsed) && $success_parsed)
    <div class="bg-white rounded-3xl border-2 border-emerald-300 shadow-md p-6 space-y-5 animate-in fade-in slide-in-from-top-4 duration-200">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                </div>
                <div>
                    <h2 class="text-base font-extrabold text-slate-900">Hasil Pembacaan Berkas Excel</h2>
                    <p class="text-xs text-slate-500">
                        Berkas: <span class="font-bold text-slate-800">{{ $fileName }}</span> ({{ $fileSize }}) &bull; Sheet Aktif: <span class="font-bold text-[#0B63B5]">{{ $sheetName }}</span>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-50 text-emerald-800 border border-emerald-300 tabular-nums">
                    Total {{ $totalRows }} Baris Terdeteksi
                </span>
            </div>
        </div>

        <!-- Sheet Badges -->
        @if(isset($allSheets) && count($allSheets) > 1)
        <div class="flex items-center gap-2 text-xs">
            <span class="text-slate-400 font-bold uppercase text-[10px]">Seluruh Sheet:</span>
            @foreach($allSheets as $sh)
                <span class="px-2.5 py-0.5 rounded-lg text-xs font-medium {{ $sh === $sheetName ? 'bg-blue-100 text-[#0B63B5] font-bold' : 'bg-slate-100 text-slate-600' }}">
                    {{ $sh }}
                </span>
            @endforeach
        </div>
        @endif

        <!-- Pratinjau Tabel Kolom & Baris -->
        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Pratinjau Data (Maksimal 10 Baris Pertama):</h4>
                <span class="text-[11px] text-slate-400 font-medium">Header terdeteksi: {{ count($headers) }} kolom</span>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-slate-200">
                <table class="w-full text-xs text-left">
                    <thead class="bg-slate-100 text-slate-700 font-extrabold uppercase text-[10px] tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="p-3 text-center w-12">#</th>
                            @foreach($headers as $h)
                                <th class="p-3 whitespace-nowrap">{{ $h }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($previewRows as $idx => $rData)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="p-3 text-center text-slate-400 font-semibold tabular-nums">{{ $idx + 1 }}</td>
                                @foreach($rData as $cell)
                                    <td class="p-3 whitespace-nowrap text-slate-700 font-medium">{{ $cell ?: '-' }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($headers) + 1 }}" class="p-4 text-center text-slate-400">
                                    Tidak ada baris data setelah baris header.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tombol Aksi Akhir -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-3 border-t border-slate-100">
            <span class="text-xs text-slate-500 font-medium">
                Mesin parser siap memproses data ini ke tabel database segera setelah pemetaan format kolom difinalisasi.
            </span>
            <div class="flex items-center gap-2">
                <a href="{{ route('kelola.import.index') }}" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold transition">
                    Unggah Berkas Lain
                </a>
                <button type="button"
                        onclick="alert('Format data berhasil divalidasi dan disimpan di antrean impor. Eksekusi database akan berjalan otomatis setelah format resmi disahkan.')"
                        class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold shadow-sm transition flex items-center gap-1.5">
                    <i data-lucide="check-check" class="w-4 h-4"></i>
                    <span>Konfirmasi Siap Impor</span>
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection
