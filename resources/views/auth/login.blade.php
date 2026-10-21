<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — Sistem Peminjaman BMN BBLSDM Makassar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; background-color: #F8FAFC; }
    </style>
</head>
<body class="min-h-full flex items-center justify-center p-4 sm:p-6 bg-slate-50">
    <div class="w-full max-w-md bg-white rounded-3xl shadow-xl border border-slate-200/80 p-6 sm:p-8">
        <!-- Logo Header -->
        <div class="text-center mb-6">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-[#0B63B5] to-[#12A8E0] flex items-center justify-center text-white font-extrabold text-2xl mx-auto shadow-lg shadow-blue-500/25 mb-3">
                B
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Sistem BMN Makassar</h1>
            <p class="text-xs text-slate-500 font-medium mt-1">
                Balai Besar Pelatihan SDM Komunikasi & Digital Makassar
            </p>
        </div>

        @if($errors->any())
            <div class="mb-5 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs font-semibold">
                {{ $errors->first() }}
            </div>
        @endif

        <!-- Quick Demo Switcher -->
        <div class="mb-6 p-3.5 rounded-2xl bg-blue-50/70 border border-blue-200/70">
            <div class="flex items-center gap-1.5 text-xs font-bold text-[#0B63B5] mb-2">
                <i data-lucide="zap" class="w-4 h-4 text-amber-500"></i>
                <span>Masuk Cepat Demo (Sesuai Peran SOP):</span>
            </div>
            <div class="grid grid-cols-2 gap-2 text-xs">
                <a href="{{ route('login.quick', 'PEMINJAM') }}" class="p-2.5 rounded-xl bg-white border border-blue-200 hover:border-[#0B63B5] hover:shadow-sm text-slate-800 transition font-semibold text-center block">
                    <span class="block text-slate-900">Peminjam</span>
                    <span class="text-[10px] text-slate-500 font-normal">Andi Pratama</span>
                </a>
                <a href="{{ route('login.quick', 'PENGELOLA_BMN') }}" class="p-2.5 rounded-xl bg-white border border-blue-200 hover:border-[#0B63B5] hover:shadow-sm text-slate-800 transition font-semibold text-center block">
                    <span class="block text-slate-900">Pengelola BMN</span>
                    <span class="text-[10px] text-slate-500 font-normal">Muhammad Agung</span>
                </a>
                <a href="{{ route('login.quick', 'KETUA_TIM_ASET') }}" class="p-2.5 rounded-xl bg-white border border-blue-200 hover:border-[#0B63B5] hover:shadow-sm text-slate-800 transition font-semibold text-center block">
                    <span class="block text-slate-900">Ketua Tim</span>
                    <span class="text-[10px] text-slate-500 font-normal">Olga Olivia</span>
                </a>
                <a href="{{ route('login.quick', 'ADMIN') }}" class="p-2.5 rounded-xl bg-white border border-blue-200 hover:border-[#0B63B5] hover:shadow-sm text-slate-800 transition font-semibold text-center block">
                    <span class="block text-slate-900">Admin</span>
                    <span class="text-[10px] text-slate-500 font-normal">Administrator</span>
                </a>
            </div>
        </div>

        <div class="relative my-5">
            <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-slate-200"></div></div>
            <div class="relative flex justify-center text-xs uppercase"><span class="bg-white px-2 text-slate-400 font-semibold">Atau Masuk Biasa</span></div>
        </div>

        <!-- Form Login Standar -->
        <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Email Pegawai</label>
                <input id="email" type="email" name="email" value="{{ old('email', 'peminjam@komdigi.go.id') }}" required autofocus
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-[#0B63B5] focus:ring-2 focus:ring-blue-100 outline-none text-sm font-medium transition">
            </div>

            <div>
                <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Kata Sandi</label>
                <input id="password" type="password" name="password" value="password" required
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-[#0B63B5] focus:ring-2 focus:ring-blue-100 outline-none text-sm font-medium transition">
            </div>

            <button type="submit" class="w-full mt-2 py-3 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white font-bold rounded-xl shadow-md shadow-blue-500/20 text-sm transition">
                Masuk ke Aplikasi
            </button>
        </form>

        <p class="mt-6 text-center text-xs text-slate-400 font-medium">
            Sistem Informasi Pengelolaan & Peminjaman BMN &bull; 2026
        </p>
    </div>

    <script>lucide.createIcons();</script>
</body>
</html>
