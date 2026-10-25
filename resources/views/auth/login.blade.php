<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Masuk — Sistem Peminjaman BMN BBLSDM Komdigi Makassar</title>
    
    <!-- Typography: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            -webkit-tap-highlight-color: transparent;
        }
        [x-cloak] { display: none !important; }
        
        /* Subtle animated gradient background for left brand panel */
        .brand-mesh {
            background-color: #071E3D;
            background-image: 
                radial-gradient(at 15% 15%, rgba(11, 99, 181, 0.45) 0px, transparent 55%),
                radial-gradient(at 85% 85%, rgba(18, 168, 224, 0.35) 0px, transparent 50%),
                radial-gradient(at 50% 50%, rgba(7, 36, 72, 0.95) 0px, transparent 100%);
        }
    </style>
</head>
<body class="h-full bg-slate-50 dark:bg-[#0B1220] text-slate-900 dark:text-slate-100 antialiased selection:bg-[#0B63B5] selection:text-white"
      x-data="loginPage()">

    <div class="min-h-screen w-full flex flex-col lg:grid lg:grid-cols-12">
        
        <!-- ==========================================
             LEFT BRAND SHOWCASE PANEL (Desktop/Laptop)
             ========================================== -->
        <div class="hidden lg:flex lg:col-span-6 xl:col-span-7 brand-mesh relative overflow-hidden flex-col justify-between p-10 xl:p-14 select-none">
            
            <!-- Ambient Decorative Elements -->
            <div class="absolute -top-24 -left-24 w-96 h-96 bg-blue-500/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-cyan-400/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute top-1/2 left-1/3 w-64 h-64 bg-[#0B63B5]/30 rounded-full blur-2xl pointer-events-none"></div>

            <!-- Top Header on Left Panel -->
            <div class="relative z-10 flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-[#0B63B5] to-[#12A8E0] text-white font-black text-xl flex items-center justify-center shadow-lg shadow-blue-500/30 border border-white/20 shrink-0">
                    B
                </div>
                <div>
                    <span class="text-xs font-extrabold uppercase tracking-widest text-cyan-300 block">Kementerian Komunikasi dan Digital RI</span>
                    <span class="text-sm font-bold text-white tracking-tight block">BBLSDM Komdigi Makassar</span>
                </div>
            </div>

            <!-- Center Showcase Content -->
            <div class="relative z-10 my-auto py-10 space-y-6 max-w-xl">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 backdrop-blur-md border border-white/15 text-cyan-200 text-xs font-semibold">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Sistem Tata Kelola & Peminjaman BMN Terpadu</span>
                </div>

                <h1 class="text-3xl xl:text-4xl 2xl:text-5xl font-black text-white tracking-tight leading-[1.18]">
                    Pengelolaan Barang Milik Negara yang Akuntabel & Presisi
                </h1>

                <p class="text-sm xl:text-base text-slate-300 font-normal leading-relaxed">
                    Layanan digital terintegrasi untuk pencatatan inventaris kantor, pengajuan peminjaman barang dinas, penerbitan Surat Peminjaman, dan Berita Acara Serah Terima (BAST) otomatis sesuai SOP B-489 BBLSDM Komdigi Makassar.
                </p>

                <!-- Value Pillars / SOP Guarantees -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 pt-4">
                    <div class="p-3.5 rounded-2xl bg-white/5 backdrop-blur-sm border border-white/10 hover:border-white/20 transition space-y-1.5">
                        <div class="w-7 h-7 rounded-lg bg-blue-500/20 text-cyan-300 flex items-center justify-center">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        </div>
                        <h4 class="text-xs font-bold text-white">SOP B-489</h4>
                        <p class="text-[11px] text-slate-300 leading-snug">Alur 7 tahap telaah, disposisi, hingga BAST resmi.</p>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-white/5 backdrop-blur-sm border border-white/10 hover:border-white/20 transition space-y-1.5">
                        <div class="w-7 h-7 rounded-lg bg-blue-500/20 text-cyan-300 flex items-center justify-center">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        </div>
                        <h4 class="text-xs font-bold text-white">Nomor Otomatis</h4>
                        <p class="text-[11px] text-slate-300 leading-snug">Penomoran dokumen atomik tanpa risiko bentrok.</p>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-white/5 backdrop-blur-sm border border-white/10 hover:border-white/20 transition space-y-1.5">
                        <div class="w-7 h-7 rounded-lg bg-blue-500/20 text-cyan-300 flex items-center justify-center">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m9 12 2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
                        </div>
                        <h4 class="text-xs font-bold text-white">Tanda Tangan Digital</h4>
                        <p class="text-[11px] text-slate-300 leading-snug">Pencetakan berkas hukum ber-TTD spesimen sah.</p>
                    </div>
                </div>
            </div>

            <!-- Bottom Footer on Left Panel -->
            <div class="relative z-10 pt-6 border-t border-white/10 flex items-center justify-between text-xs text-slate-400 font-medium">
                <span>© 2026 BBLSDM Komunikasi dan Digital Makassar</span>
                <span class="inline-flex items-center gap-1.5 text-cyan-400">
                    <svg class="w-3.5 h-3.5 text-emerald-400" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/></svg>
                    <span>Server Aktif (WITA)</span>
                </span>
            </div>
        </div>

        <!-- ==========================================
             RIGHT AUTHENTICATION FORM PANEL
             ========================================== -->
        <div class="col-span-12 lg:col-span-6 xl:col-span-5 flex flex-col justify-between p-6 sm:p-10 xl:p-12 min-h-screen bg-white dark:bg-[#0B1220] transition-colors">
            
            <!-- Mobile Brand Header -->
            <div class="flex items-center justify-between pb-6 border-b border-slate-100 dark:border-slate-800 lg:border-none">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-[#0B63B5] to-[#12A8E0] text-white font-extrabold text-lg flex items-center justify-center shadow-md shadow-blue-500/25 shrink-0">
                        B
                    </div>
                    <div>
                        <h2 class="text-sm font-extrabold text-slate-900 dark:text-white leading-tight">Peminjaman BMN</h2>
                        <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium block">BBLSDM Komdigi Makassar</span>
                    </div>
                </div>

                <!-- SOP Badge -->
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-blue-50 dark:bg-blue-950/60 text-[#0B63B5] dark:text-cyan-400 border border-blue-200/70 dark:border-blue-900">
                        SOP B-489
                    </span>
                </div>
            </div>

            <!-- Central Form Container -->
            <div class="my-auto py-8 max-w-md w-full mx-auto space-y-6">
                
                <div>
                    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                        Masuk ke Akun
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 font-medium mt-1.5">
                        Gunakan email pegawai dan kata sandi Anda untuk mengakses layanan BMN.
                    </p>
                </div>

                <!-- Error Alert Box -->
                @if($errors->any())
                    <div class="p-3.5 rounded-2xl bg-red-50 dark:bg-red-950/50 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-xs font-semibold flex items-start gap-2.5 shadow-2xs">
                        <svg class="w-4 h-4 text-red-600 dark:text-red-400 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                @if(session('success'))
                    <div class="p-3.5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 text-xs font-semibold flex items-start gap-2.5 shadow-2xs">
                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                <!-- Standard Manual Login Form -->
                <form method="POST" action="{{ route('login.post') }}" class="space-y-4" autocomplete="on">
                    @csrf

                    <!-- EMAIL INPUT -->
                    <div>
                        <label for="email" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                            Email Pegawai
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            </div>
                            <input id="email"
                                   type="email"
                                   name="email"
                                   x-model="emailInput"
                                   value="{{ old('email', 'peminjam@komdigi.go.id') }}"
                                   required
                                   autofocus
                                   placeholder="nama@komdigi.go.id"
                                   class="w-full pl-10 pr-3.5 py-3 rounded-2xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 focus:border-[#0B63B5] dark:focus:border-blue-400 focus:ring-4 focus:ring-blue-100 dark:focus:ring-blue-950/60 outline-none text-base sm:text-sm font-medium text-slate-900 dark:text-white placeholder-slate-400 transition shadow-2xs">
                        </div>
                    </div>

                    <!-- PASSWORD INPUT WITH SHOW/HIDE TOGGLE -->
                    <div x-data="{ showPass: false }">
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                                Kata Sandi
                            </label>
                            <span class="text-[11px] text-slate-400 font-medium">Bantuan login: Admin TI</span>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            </div>
                            <input id="password"
                                   :type="showPass ? 'text' : 'password'"
                                   name="password"
                                   x-model="passwordInput"
                                   value="password"
                                   required
                                   placeholder="Masukkan kata sandi"
                                   class="w-full pl-10 pr-11 py-3 rounded-2xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 focus:border-[#0B63B5] dark:focus:border-blue-400 focus:ring-4 focus:ring-blue-100 dark:focus:ring-blue-950/60 outline-none text-base sm:text-sm font-medium text-slate-900 dark:text-white placeholder-slate-400 transition shadow-2xs">
                            <button type="button"
                                    @click="showPass = !showPass"
                                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition cursor-pointer"
                                    title="Tampilkan / Sembunyikan Kata Sandi">
                                <svg x-show="!showPass" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg x-show="showPass" x-cloak class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- REMEMBER ME & PROTOCOL -->
                    <div class="flex items-center justify-between text-xs pt-1">
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none text-slate-600 dark:text-slate-400">
                            <input type="checkbox" name="remember" class="w-4 h-4 rounded text-[#0B63B5] focus:ring-blue-500 border-slate-300 dark:border-slate-700 dark:bg-slate-900">
                            <span class="font-medium">Ingat perangkat ini</span>
                        </label>
                        <span class="text-slate-400">Protokol Keamanan SOP</span>
                    </div>

                    <!-- SUBMIT BUTTON -->
                    <button type="submit"
                            class="w-full py-3.5 px-6 rounded-2xl bg-[#0B63B5] hover:bg-[#0B4F8A] active:scale-[0.98] text-white font-extrabold text-sm shadow-md shadow-blue-600/20 hover:shadow-lg transition-all duration-150 flex items-center justify-center gap-2 cursor-pointer select-none mt-2">
                        <span>Masuk ke Sistem</span>
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </button>
                </form>

                <!-- ========================================================
                     TESTING CREDENTIALS HELPER (Manual Login Reference)
                     Sesuai Permintaan: Menyediakan informasi 4 role testing
                     secara elegan tanpa tombol pintas demo otomatis.
                     ======================================================== -->
                <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
                    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-900/60 p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 text-xs font-bold text-slate-800 dark:text-slate-200">
                                <span class="flex h-5 w-5 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-950 text-[#0B63B5] dark:text-blue-400 font-black text-[11px]">
                                    i
                                </span>
                                <span>Akun Pengujian (Testing 4 Role)</span>
                            </div>
                            <span class="text-[10px] font-semibold text-slate-400">Kata Sandi: <code class="font-mono font-bold text-slate-700 dark:text-slate-300">password</code></span>
                        </div>

                        <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed font-medium">
                            Klik tombol salin di samping untuk mengisi email ke form secara manual:
                        </p>

                        <!-- 4 Accounts List -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                            
                            <!-- 1. Peminjam (Budi) -->
                            <div class="p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-between gap-2 shadow-2xs">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                        <span class="font-bold text-slate-800 dark:text-white truncate">Peminjam</span>
                                    </div>
                                    <span class="text-[10px] text-slate-500 dark:text-slate-400 block truncate">Budi (Instruktur)</span>
                                    <span class="text-[10px] font-mono text-slate-400 block truncate">peminjam@komdigi.go.id</span>
                                </div>
                                <button type="button"
                                        @click="fillEmail('peminjam@komdigi.go.id')"
                                        class="px-2 py-1 rounded-lg bg-slate-100 hover:bg-blue-50 text-slate-700 hover:text-[#0B63B5] dark:bg-slate-700 dark:hover:bg-blue-950 dark:text-slate-300 dark:hover:text-cyan-300 text-[10px] font-bold transition shrink-0 cursor-pointer"
                                        title="Gunakan email ini">
                                    Pakai
                                </button>
                            </div>

                            <!-- 2. Pengelola BMN (Agung) -->
                            <div class="p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-between gap-2 shadow-2xs">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        <span class="font-bold text-slate-800 dark:text-white truncate">Pengelola BMN</span>
                                    </div>
                                    <span class="text-[10px] text-slate-500 dark:text-slate-400 block truncate">Muhammad Agung</span>
                                    <span class="text-[10px] font-mono text-slate-400 block truncate">pengelola@komdigi.go.id</span>
                                </div>
                                <button type="button"
                                        @click="fillEmail('pengelola@komdigi.go.id')"
                                        class="px-2 py-1 rounded-lg bg-slate-100 hover:bg-blue-50 text-slate-700 hover:text-[#0B63B5] dark:bg-slate-700 dark:hover:bg-blue-950 dark:text-slate-300 dark:hover:text-cyan-300 text-[10px] font-bold transition shrink-0 cursor-pointer"
                                        title="Gunakan email ini">
                                    Pakai
                                </button>
                            </div>

                            <!-- 3. Ketua Tim Aset (Olga) -->
                            <div class="p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-between gap-2 shadow-2xs">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                                        <span class="font-bold text-slate-800 dark:text-white truncate">Ketua Tim Aset</span>
                                    </div>
                                    <span class="text-[10px] text-slate-500 dark:text-slate-400 block truncate">Olga Olivia, SE</span>
                                    <span class="text-[10px] font-mono text-slate-400 block truncate">ketuatim@komdigi.go.id</span>
                                </div>
                                <button type="button"
                                        @click="fillEmail('ketuatim@komdigi.go.id')"
                                        class="px-2 py-1 rounded-lg bg-slate-100 hover:bg-blue-50 text-slate-700 hover:text-[#0B63B5] dark:bg-slate-700 dark:hover:bg-blue-950 dark:text-slate-300 dark:hover:text-cyan-300 text-[10px] font-bold transition shrink-0 cursor-pointer"
                                        title="Gunakan email ini">
                                    Pakai
                                </button>
                            </div>

                            <!-- 4. Administrator Sistem -->
                            <div class="p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-between gap-2 shadow-2xs">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                                        <span class="font-bold text-slate-800 dark:text-white truncate">Admin Sistem</span>
                                    </div>
                                    <span class="text-[10px] text-slate-500 dark:text-slate-400 block truncate">Administrator</span>
                                    <span class="text-[10px] font-mono text-slate-400 block truncate">admin@komdigi.go.id</span>
                                </div>
                                <button type="button"
                                        @click="fillEmail('admin@komdigi.go.id')"
                                        class="px-2 py-1 rounded-lg bg-slate-100 hover:bg-blue-50 text-slate-700 hover:text-[#0B63B5] dark:bg-slate-700 dark:hover:bg-blue-950 dark:text-slate-300 dark:hover:text-cyan-300 text-[10px] font-bold transition shrink-0 cursor-pointer"
                                        title="Gunakan email ini">
                                    Pakai
                                </button>
                            </div>

                        </div>
                    </div>
                </div>

            </div>

            <!-- Bottom Mobile/Desktop Clean Footer -->
            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 text-center">
                <p class="text-xs text-slate-400 dark:text-slate-500 font-medium">
                    Balai Besar Pelatihan Sumber Daya Manusia Komunikasi dan Digital Makassar
                </p>
                <p class="text-[11px] text-slate-400/80 dark:text-slate-600 font-normal mt-0.5">
                    Jl. Prof. Abdurrahman Basalamah II No 25 Makassar • SOP B-489
                </p>
            </div>

        </div>

    </div>

    <!-- Alpine.js helper for interactive email population -->
    <script>
        function loginPage() {
            return {
                emailInput: '{{ old('email', 'peminjam@komdigi.go.id') }}',
                passwordInput: 'password',
                fillEmail(email) {
                    this.emailInput = email;
                    this.passwordInput = 'password';
                    const el = document.getElementById('email');
                    if (el) {
                        el.focus();
                    }
                }
            };
        }
    </script>
</body>
</html>
