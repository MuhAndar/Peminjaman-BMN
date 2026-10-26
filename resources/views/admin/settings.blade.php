@extends('layouts.app')

@section('title', 'Pengaturan Website & Identitas Sistem — Admin BMN')

@section('content')
<div class="space-y-6" x-data="settingsManager({
    initialLogo: '{{ $logoUrl ?? '' }}',
    initialFavicon: '{{ $faviconUrl ?? '' }}',
    hasCustomLogo: {{ $hasCustomLogo ? 'true' : 'false' }},
    hasCustomFavicon: {{ $hasCustomFavicon ? 'true' : 'false' }}
})">
    <!-- Top Bar Navigation -->
    <div class="flex items-center justify-between gap-3">
        <a href="{{ route('dashboard') }}"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs font-bold text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800 transition shadow-2xs">
            <i data-lucide="arrow-left" class="w-4 h-4 text-slate-500"></i>
            <span>Kembali ke Beranda</span>
        </a>

        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-blue-50 dark:bg-blue-950/60 text-[#0B63B5] dark:text-blue-400 border border-blue-200 dark:border-blue-900/60">
                <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                <span>Akses Administrator</span>
            </span>
        </div>
    </div>

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-blue-600 transition">Beranda</a>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-slate-600 dark:text-slate-300">Administrasi Sistem</span>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-[#0B63B5] dark:text-blue-400">Pengaturan Website</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-2xl bg-blue-100 dark:bg-blue-950/80 text-[#0B63B5] dark:text-blue-400 flex items-center justify-center shrink-0">
                    <i data-lucide="sliders" class="w-5 h-5"></i>
                </div>
                <span>Pengaturan Identitas & Tampilan Website</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">
                Kustomisasi logo ikon aplikasi, favicon browser, nama website, identitas satker, dan format dokumen dinas BMN.
            </p>
        </div>
    </div>

    <!-- Main Settings Form -->
    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <!-- LEFT COLUMN: BRANDING, LOGO & FAVICON -->
            <div class="lg:col-span-6 space-y-6">
                
                <!-- Card 1: Visual Branding & Assets -->
                <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-5">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                        <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <i data-lucide="palette" class="w-5 h-5 text-[#0B63B5] dark:text-blue-400"></i>
                            <span>Ikon & Visual Branding Website</span>
                        </h2>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Aset Visual</span>
                    </div>

                    <!-- 1.1 LOGO APLIKASI -->
                    <div class="space-y-3">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-200">
                            Logo / Ikon Aplikasi
                            <span class="text-slate-400 font-normal ml-1">(Tampil di Sidebar, Header, & Halaman Login)</span>
                        </label>
                        
                        <div class="flex flex-col sm:flex-row items-center gap-4 p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700">
                            <!-- Preview Box -->
                            <div class="w-20 h-20 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 flex items-center justify-center p-2 shrink-0 shadow-2xs overflow-hidden relative group">
                                <template x-if="logoPreview">
                                    <img :src="logoPreview" alt="Logo Preview" class="w-full h-full object-contain">
                                </template>
                                <template x-if="!logoPreview">
                                    <div class="w-full h-full rounded-xl bg-gradient-to-tr from-[#0B63B5] to-[#12A8E0] flex items-center justify-center text-white font-black text-2xl shadow-inner">
                                        {{ substr($appName, 0, 1) }}
                                    </div>
                                </template>
                            </div>

                            <div class="flex-1 space-y-2 text-center sm:text-left w-full">
                                <div class="flex flex-wrap items-center gap-2 justify-center sm:justify-start">
                                    <label class="px-3 py-1.5 rounded-xl bg-[#0B63B5] hover:bg-[#0B4F8A] text-white text-xs font-bold cursor-pointer transition shadow-xs inline-flex items-center gap-1.5">
                                        <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                                        <span>Pilih Berkas Logo</span>
                                        <input type="file" name="logo_file" accept="image/png,image/jpeg,image/svg+xml,image/webp" class="hidden" @change="handleLogoChange($event)">
                                    </label>

                                    <template x-if="hasCustomLogo">
                                        <button type="button" @click="resetAsset('logo')" class="px-3 py-1.5 rounded-xl border border-red-200 dark:border-red-900/60 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40 text-xs font-bold transition inline-flex items-center gap-1.5">
                                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                                            <span>Reset ke Default</span>
                                        </button>
                                    </template>
                                </div>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                    Format: PNG, SVG, JPG, WEBP. Maksimal 2 MB. Disarankan rasio 1:1 persegi atau logo berlatar transparan.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 1.2 FAVICON BROWSER -->
                    <div class="space-y-3 pt-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-200">
                            Favicon Tab Browser
                            <span class="text-slate-400 font-normal ml-1">(Ikon kecil di tab browser & bookmark)</span>
                        </label>
                        
                        <div class="flex flex-col sm:flex-row items-center gap-4 p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700">
                            <!-- Preview Box Browser Simulation -->
                            <div class="w-20 h-20 rounded-2xl bg-slate-900 border border-slate-800 flex flex-col items-center justify-center p-2 shrink-0 shadow-2xs">
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter mb-1">Tab Preview</span>
                                <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center p-1 border border-white/20">
                                    <template x-if="faviconPreview">
                                        <img :src="faviconPreview" alt="Favicon Preview" class="w-5 h-5 object-contain">
                                    </template>
                                    <template x-if="!faviconPreview">
                                        <div class="w-5 h-5 rounded bg-[#0B63B5] text-white flex items-center justify-center text-[10px] font-black">
                                            B
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div class="flex-1 space-y-2 text-center sm:text-left w-full">
                                <div class="flex flex-wrap items-center gap-2 justify-center sm:justify-start">
                                    <label class="px-3 py-1.5 rounded-xl bg-slate-800 dark:bg-slate-700 hover:bg-slate-900 text-white text-xs font-bold cursor-pointer transition shadow-xs inline-flex items-center gap-1.5">
                                        <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                                        <span>Pilih Berkas Favicon</span>
                                        <input type="file" name="favicon_file" accept=".ico,image/png,image/svg+xml" class="hidden" @change="handleFaviconChange($event)">
                                    </label>

                                    <template x-if="hasCustomFavicon">
                                        <button type="button" @click="resetAsset('favicon')" class="px-3 py-1.5 rounded-xl border border-red-200 dark:border-red-900/60 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40 text-xs font-bold transition inline-flex items-center gap-1.5">
                                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                                            <span>Reset ke Default</span>
                                        </button>
                                    </template>
                                </div>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                    Format: .ICO, .PNG, .SVG. Maksimal 1 MB. Ukuran optimal 32x32 atau 64x64 piksel.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 1.3 NAMA WEBSITE & SUB-NAMA -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-200">
                                Nama Website / Judul Aplikasi <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="app_name" value="{{ old('app_name', $appName) }}" required
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs font-bold focus:ring-2 focus:ring-blue-500/20 focus:border-[#0B63B5] outline-none transition"
                                   placeholder="Contoh: Sistem Peminjaman BMN">
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-200">
                                Singkatan / Subnama Instansi <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="app_short_name" value="{{ old('app_short_name', $appShortName) }}" required
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs font-bold focus:ring-2 focus:ring-blue-500/20 focus:border-[#0B63B5] outline-none transition"
                                   placeholder="Contoh: BBLSDM Komdigi Makassar">
                        </div>
                    </div>
                </div>

                <!-- Card 2: SLA Waktu Layanan SOP -->
                <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                        <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <i data-lucide="timer" class="w-5 h-5 text-[#0B63B5] dark:text-blue-400"></i>
                            <span>Target SLA Waktu Layanan SOP</span>
                        </h2>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Durasi Menit</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="p-3.5 rounded-2xl bg-blue-50/70 dark:bg-blue-950/40 border border-blue-200/70 dark:border-blue-900/60 space-y-1.5">
                            <label class="block text-xs font-bold text-blue-900 dark:text-blue-300">
                                Target Telaah Pengelola BMN
                            </label>
                            <div class="flex items-center gap-2">
                                <input type="number" name="sla_telaah" min="1" max="1440"
                                       value="{{ old('sla_telaah', $slaMinutes['step1'] ?? 60) }}"
                                       class="w-28 px-3 py-1.5 rounded-xl border border-blue-300 dark:border-blue-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-900 dark:text-white outline-none">
                                <span class="text-xs text-blue-700 dark:text-blue-400 font-semibold">Menit</span>
                            </div>
                            <p class="text-[10px] text-blue-600 dark:text-blue-400">Batas toleransi sejak diajukan peminjam.</p>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-purple-50/70 dark:bg-purple-950/40 border border-purple-200/70 dark:border-purple-900/60 space-y-1.5">
                            <label class="block text-xs font-bold text-purple-900 dark:text-purple-300">
                                Target Persetujuan Ketua Tim
                            </label>
                            <div class="flex items-center gap-2">
                                <input type="number" name="sla_persetujuan" min="1" max="1440"
                                       value="{{ old('sla_persetujuan', $slaMinutes['step2'] ?? 120) }}"
                                       class="w-28 px-3 py-1.5 rounded-xl border border-purple-300 dark:border-purple-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-900 dark:text-white outline-none">
                                <span class="text-xs text-purple-700 dark:text-purple-400 font-semibold">Menit</span>
                            </div>
                            <p class="text-[10px] text-purple-600 dark:text-purple-400">Batas toleransi sejak diteruskan telaah.</p>
                        </div>
                    </div>
                </div>

            </div>

            <!-- RIGHT COLUMN: SATKER IDENTITY & OFFICIAL LETTERHEAD -->
            <div class="lg:col-span-6 space-y-6">
                
                <!-- Card 3: Identitas Satuan Kerja & Kop Surat -->
                <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-5">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                        <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <i data-lucide="building-2" class="w-5 h-5 text-[#0B63B5] dark:text-blue-400"></i>
                            <span>Identitas Satuan Kerja & Kop Dokumen</span>
                        </h2>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Format Resmi</span>
                    </div>

                    <div class="space-y-4">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-200">
                                Kode Satuan Kerja (Satker) <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="satker_code" value="{{ old('satker_code', $satkerCode) }}" required
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white font-mono text-xs font-bold focus:ring-2 focus:ring-blue-500/20 focus:border-[#0B63B5] outline-none transition"
                                   placeholder="Contoh: BBLSDM.73">
                            <p class="text-[10px] text-slate-400">Digunakan pada struktur penomoran dokumen resmi BMN.</p>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-200">
                                Nama Lengkap Satker / Unit Pelaksana Teknis <span class="text-red-500">*</span>
                            </label>
                            <textarea name="satker_name" rows="2" required
                                      class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs font-bold focus:ring-2 focus:ring-blue-500/20 focus:border-[#0B63B5] outline-none transition"
                                      placeholder="Nama instansi lengkap...">{{ old('satker_name', $satkerName) }}</textarea>
                            <p class="text-[10px] text-slate-400">Dicetak pada judul dan kalimat pembuka seluruh dokumen PDF BMN.</p>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-200">
                                Alamat Resmi Kop Dokumen & Kontak <span class="text-red-500">*</span>
                            </label>
                            <textarea name="satker_address" rows="3" required
                                      class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs font-medium focus:ring-2 focus:ring-blue-500/20 focus:border-[#0B63B5] outline-none transition"
                                      placeholder="Alamat kantor, nomor jalan, kode pos...">{{ old('satker_address', $satkerAddress) }}</textarea>
                            <p class="text-[10px] text-slate-400">Dicetak pada bagian kepala surat (kop dinas) Surat Peminjaman dan BAST.</p>
                        </div>

                        <div class="space-y-1.5 pt-2">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-200">
                                Teks Hak Cipta Footer Aplikasi
                            </label>
                            <input type="text" name="app_footer_text" value="{{ old('app_footer_text', $appFooterText) }}"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs font-medium focus:ring-2 focus:ring-blue-500/20 focus:border-[#0B63B5] outline-none transition"
                                   placeholder="Contoh: © 2026 BBLSDM Komunikasi dan Digital Makassar.">
                        </div>
                    </div>
                </div>

                <!-- Card 4: Preview Dokumen Resmi -->
                <div class="p-5 rounded-3xl bg-slate-50 dark:bg-slate-800/40 border border-dashed border-slate-300 dark:border-slate-700 space-y-3">
                    <div class="flex items-center gap-2 text-xs font-bold text-slate-700 dark:text-slate-200">
                        <i data-lucide="file-check" class="w-4 h-4 text-emerald-600"></i>
                        <span>Efek Langsung ke Dokumen Cetak</span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                        Seluruh pembaruan nama satker, alamat kop surat, dan kode satker di atas akan secara otomatis memperbarui penomoran dan isi kop cetak dari 3 dokumen resmi: <strong>Surat Peminjaman</strong>, <strong>BAST Peminjaman</strong>, dan <strong>BAST Pengembalian</strong>.
                    </p>
                </div>

            </div>

        </div>

        <!-- Sticky / Fixed Bottom Action Bar -->
        <div class="p-4 sm:p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 font-medium">
                <i data-lucide="info" class="w-4 h-4 text-[#0B63B5] shrink-0"></i>
                <span>Perubahan konfigurasi akan langsung berlaku secara global di seluruh akun pengguna.</span>
            </div>

            <div class="flex items-center gap-3 w-full sm:w-auto">
                <a href="{{ route('dashboard') }}" class="w-full sm:w-auto px-5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs hover:bg-slate-50 dark:hover:bg-slate-800 transition text-center">
                    Batal
                </a>
                <button type="submit" class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-[#0B63B5] hover:bg-[#0B4F8A] text-white font-bold text-xs shadow-md shadow-blue-500/20 transition flex items-center justify-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span>Simpan Perubahan Pengaturan</span>
                </button>
            </div>
        </div>

    </form>

    <!-- Hidden Form for Resetting Logo / Favicon Asset -->
    <form id="resetAssetForm" action="{{ route('admin.settings.reset-asset') }}" method="POST" class="hidden">
        @csrf
        <input type="hidden" name="type" id="resetAssetType" value="">
    </form>

</div>

<script>
    function settingsManager(config) {
        return {
            logoPreview: config.initialLogo,
            faviconPreview: config.initialFavicon,
            hasCustomLogo: config.hasCustomLogo,
            hasCustomFavicon: config.hasCustomFavicon,

            handleLogoChange(event) {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.logoPreview = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            },

            handleFaviconChange(event) {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.faviconPreview = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            },

            resetAsset(type) {
                const label = type === 'logo' ? 'Logo Aplikasi' : 'Favicon Browser';
                if (confirm(`Apakah Anda yakin ingin mereset ${label} ke aset bawaan?`)) {
                    document.getElementById('resetAssetType').value = type;
                    document.getElementById('resetAssetForm').submit();
                }
            }
        };
    }
</script>
@endsection
