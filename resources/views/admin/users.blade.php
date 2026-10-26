@extends('layouts.app')

@section('title', 'Manajemen Pengguna — BBLSDM Komdigi')

@section('content')
<div class="space-y-6" x-data="{
    addUserModalOpen: false,
    selectedRole: 'PEMINJAM'
}">
    <!-- Top Bar Navigation -->
    <div class="flex items-center justify-between gap-3">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}"
           onclick="if (document.referrer && document.referrer.indexOf(window.location.host) !== -1) { history.back(); return false; }"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-700 transition shadow-2xs">
            <i data-lucide="arrow-left" class="w-4 h-4 text-slate-500"></i>
            <span>Kembali</span>
        </a>

        <!-- Tambah Pengguna CTA -->
        <button type="button"
                @click="addUserModalOpen = true; $nextTick(() => { if (window.lucide) lucide.createIcons(); })"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-[#0B63B5] hover:bg-[#0B4F8A] text-white font-bold text-xs shadow-md shadow-blue-600/25 transition shrink-0 cursor-pointer btn-tactile-press">
            <i data-lucide="user-plus" class="w-4 h-4"></i>
            <span>Tambah Pengguna</span>
        </button>
    </div>

    <!-- Page Header Card -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition">Beranda</a>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-slate-600 dark:text-slate-300">Administrasi Sistem</span>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-[#0B63B5] dark:text-blue-400 font-bold">Manajemen Pengguna</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-2xl bg-blue-50 dark:bg-blue-950/60 text-[#0B63B5] dark:text-blue-400 flex items-center justify-center shrink-0">
                    <i data-lucide="users" class="w-5 h-5"></i>
                </div>
                <span>Manajemen Pengguna & Hak Akses (RBAC)</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">
                Daftar akun kedinasan pegawai dan penetapan peran hak akses (Pengelola BMN, Ketua Tim Aset, Peminjam).
            </p>
        </div>

        <div class="flex items-center gap-2">
            <button type="button"
                    @click="addUserModalOpen = true; $nextTick(() => { if (window.lucide) lucide.createIcons(); })"
                    class="hidden md:inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#0B63B5] hover:bg-[#0B4F8A] text-white font-bold text-xs shadow-md shadow-blue-600/25 transition shrink-0 cursor-pointer">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                <span>Tambah Pengguna Baru</span>
            </button>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xs">
        <form method="GET" action="{{ route('admin.users') }}" class="flex flex-col sm:flex-row items-center gap-3">
            <div class="relative flex-1 w-full">
                <i data-lucide="search" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Cari berdasarkan nama, email, NIP, atau jabatan..."
                       class="w-full pl-10 pr-4 py-2 rounded-xl text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-hidden focus:ring-2 focus:ring-[#0B63B5]">
            </div>

            <div class="w-full sm:w-auto flex items-center gap-2 shrink-0">
                <select name="role"
                        class="w-full sm:w-auto px-3 py-2 rounded-xl text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 font-semibold focus:outline-hidden focus:ring-2 focus:ring-[#0B63B5]">
                    <option value="">Semua Peran (Role)</option>
                    <option value="PEMINJAM" {{ request('role') === 'PEMINJAM' ? 'selected' : '' }}>Peminjam</option>
                    <option value="PENGELOLA_BMN" {{ request('role') === 'PENGELOLA_BMN' ? 'selected' : '' }}>Pengelola BMN</option>
                    <option value="KETUA_TIM_ASET" {{ request('role') === 'KETUA_TIM_ASET' ? 'selected' : '' }}>Ketua Tim Aset</option>
                    <option value="ADMIN" {{ request('role') === 'ADMIN' ? 'selected' : '' }}>Administrator</option>
                </select>

                <button type="submit"
                        class="px-4 py-2 rounded-xl bg-slate-900 dark:bg-slate-800 text-white font-bold text-xs hover:bg-slate-800 transition shadow-2xs shrink-0">
                    Filter
                </button>

                @if(request()->hasAny(['search', 'role']))
                    <a href="{{ route('admin.users') }}"
                       class="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-semibold transition shrink-0"
                       title="Reset Filter">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Users Table -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/80 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider text-[11px]">
                        <th class="py-3.5 px-4">Nama & Email</th>
                        <th class="py-3.5 px-4">NIP / Pegawai</th>
                        <th class="py-3.5 px-4">Jabatan & Pangkat</th>
                        <th class="py-3.5 px-4">Peran (Role)</th>
                        <th class="py-3.5 px-4">Tanda Tangan</th>
                        <th class="py-3.5 px-4">Status Akun</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium text-slate-700 dark:text-slate-300">
                    @forelse($users as $user)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                        <td class="py-3.5 px-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-xl bg-blue-100 dark:bg-blue-950/80 text-[#0B63B5] dark:text-blue-400 font-black flex items-center justify-center text-xs shrink-0 border border-blue-200 dark:border-blue-800">
                                    {{ substr($user->name, 0, 1) }}
                                </div>
                                <div class="min-w-0">
                                    <span class="font-bold text-slate-900 dark:text-white block truncate">{{ $user->name }}</span>
                                    <span class="text-[11px] text-slate-400 block truncate">{{ $user->email }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="py-3.5 px-4 font-mono text-[11px]">
                            {{ $user->employee && $user->employee->nip ? \App\Helpers\FormatHelper::nip($user->employee->nip) : '-' }}
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="block font-semibold text-slate-800 dark:text-slate-200">{{ $user->employee->position ?? '-' }}</span>
                            @if(!empty($user->employee->rank_grade))
                                <span class="text-[11px] text-slate-400 block">{{ $user->employee->rank_grade }}</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold border inline-block
                                @if($user->role === 'ADMIN') bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/50 dark:text-purple-300 dark:border-purple-800
                                @elseif($user->role === 'PENGELOLA_BMN') bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-800
                                @elseif($user->role === 'KETUA_TIM_ASET') bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/50 dark:text-indigo-300 dark:border-indigo-800
                                @else bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/50 dark:text-blue-300 dark:border-blue-800 @endif">
                                {{ str_replace('_', ' ', $user->role) }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4">
                            @if($user->signature_path)
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-600 dark:text-emerald-400">
                                    <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                                    <span>Tersimpan</span>
                                </span>
                            @else
                                <span class="text-[10px] text-slate-400 font-semibold italic">Belum Ada</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400 font-bold text-[10px] border border-emerald-200 dark:border-emerald-800">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                <span>{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-400">
                            <i data-lucide="user-x" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                            <p class="font-bold">Tidak ada data pengguna yang sesuai dengan kriteria pencarian.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40">
            {{ $users->links() }}
        </div>
        @endif
    </div>

    <!-- MODAL: TAMBAH PENGGUNA BARU (ROLE KECUALI ADMIN) -->
    <div x-show="addUserModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-add-user-title"
         role="dialog"
         aria-modal="true">
        <!-- Backdrop -->
        <div x-show="addUserModalOpen"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="addUserModalOpen = false"
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"></div>

        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div x-show="addUserModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative transform overflow-hidden rounded-3xl bg-white dark:bg-slate-900 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-xl border border-slate-200 dark:border-slate-800">
                
                <form action="{{ route('admin.users.store') }}" method="POST">
                    @csrf
                    
                    <!-- Modal Header -->
                    <div class="px-6 pt-6 pb-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-blue-100 dark:bg-blue-950/80 text-[#0B63B5] dark:text-blue-400 flex items-center justify-center shrink-0">
                                <i data-lucide="user-plus" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-black text-slate-900 dark:text-white" id="modal-add-user-title">
                                    Tambah Pengguna Baru
                                </h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                                    Buat akun kedinasan untuk pegawai atau pengelola BMN
                                </p>
                            </div>
                        </div>

                        <button type="button"
                                @click="addUserModalOpen = false"
                                class="w-8 h-8 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>

                    <!-- Modal Body Form -->
                    <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                        <!-- Security Info Alert -->
                        <div class="p-3.5 rounded-2xl bg-blue-50/60 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-900/50 flex items-start gap-2.5 text-xs text-blue-900 dark:text-blue-300">
                            <i data-lucide="shield-check" class="w-4 h-4 text-[#0B63B5] shrink-0 mt-0.5"></i>
                            <div class="leading-relaxed">
                                <span class="font-bold">Keamanan RBAC:</span> Pembuatan akun dibatasi untuk peran <strong>Peminjam</strong>, <strong>Pengelola BMN</strong>, dan <strong>Ketua Tim Aset</strong>. Role Administrator Sistem (ADMIN) tidak diizinkan melalui menu ini.
                            </div>
                        </div>

                        <!-- 1. Nama Lengkap & NIP -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    Nama Lengkap <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       name="name"
                                       required
                                       value="{{ old('name') }}"
                                       placeholder="Nama lengkap & gelar"
                                       class="w-full px-3.5 py-2.5 rounded-xl text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-hidden focus:ring-2 focus:ring-[#0B63B5]">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    NIP (18 Digit Angka)
                                </label>
                                <input type="text"
                                       name="nip"
                                       value="{{ old('nip') }}"
                                       placeholder="19xxxxxxxxxxxxxxxx"
                                       maxlength="30"
                                       class="w-full px-3.5 py-2.5 rounded-xl text-xs font-mono bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-hidden focus:ring-2 focus:ring-[#0B63B5]">
                            </div>
                        </div>

                        <!-- 2. Email & Password -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    Email Kedinasan <span class="text-red-500">*</span>
                                </label>
                                <input type="email"
                                       name="email"
                                       required
                                       value="{{ old('email') }}"
                                       placeholder="nama@komdigi.go.id"
                                       class="w-full px-3.5 py-2.5 rounded-xl text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-hidden focus:ring-2 focus:ring-[#0B63B5]">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    Kata Sandi Baru <span class="text-red-500">*</span>
                                </label>
                                <input type="password"
                                       name="password"
                                       required
                                       minlength="8"
                                       placeholder="Minimal 8 karakter"
                                       class="w-full px-3.5 py-2.5 rounded-xl text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-hidden focus:ring-2 focus:ring-[#0B63B5]">
                            </div>
                        </div>

                        <!-- 3. Pilihan Role (Hak Akses) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                Pilih Peran (Role Hak Akses) <span class="text-red-500">*</span>
                            </label>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                                <!-- Option 1: Peminjam -->
                                <label class="p-3 rounded-2xl border transition-all cursor-pointer flex flex-col justify-between"
                                       :class="selectedRole === 'PEMINJAM' ? 'border-[#0B63B5] bg-blue-50/50 dark:bg-blue-950/40 ring-2 ring-[#0B63B5]/30' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800'">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs font-bold text-slate-900 dark:text-white">Peminjam</span>
                                        <input type="radio" name="role" value="PEMINJAM" x-model="selectedRole" class="text-[#0B63B5] focus:ring-[#0B63B5]">
                                    </div>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-tight">
                                        Pegawai pemohon; mengajukan pinjaman BMN.
                                    </p>
                                </label>

                                <!-- Option 2: Pengelola BMN -->
                                <label class="p-3 rounded-2xl border transition-all cursor-pointer flex flex-col justify-between"
                                       :class="selectedRole === 'PENGELOLA_BMN' ? 'border-amber-500 bg-amber-50/50 dark:bg-amber-950/40 ring-2 ring-amber-500/30' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800'">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs font-bold text-slate-900 dark:text-white">Pengelola BMN</span>
                                        <input type="radio" name="role" value="PENGELOLA_BMN" x-model="selectedRole" class="text-amber-600 focus:ring-amber-500">
                                    </div>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-tight">
                                        Menelaah form, verifikasi fisik, dan terbit BAST.
                                    </p>
                                </label>

                                <!-- Option 3: Ketua Tim Aset -->
                                <label class="p-3 rounded-2xl border transition-all cursor-pointer flex flex-col justify-between"
                                       :class="selectedRole === 'KETUA_TIM_ASET' ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/40 ring-2 ring-indigo-500/30' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800'">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs font-bold text-slate-900 dark:text-white">Ketua Tim Aset</span>
                                        <input type="radio" name="role" value="KETUA_TIM_ASET" x-model="selectedRole" class="text-indigo-600 focus:ring-indigo-500">
                                    </div>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-tight">
                                        Menyetujui & disposisi izin peminjaman BMN.
                                    </p>
                                </label>
                            </div>
                        </div>

                        <!-- 4. Pangkat / Golongan & Jabatan -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    Pangkat / Golongan Ruang
                                </label>
                                <input type="text"
                                       name="rank_grade"
                                       value="{{ old('rank_grade') }}"
                                       placeholder="Contoh: Penata Muda (III/a)"
                                       class="w-full px-3.5 py-2.5 rounded-xl text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-hidden focus:ring-2 focus:ring-[#0B63B5]">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    Jabatan Kedinasan
                                </label>
                                <input type="text"
                                       name="position"
                                       value="{{ old('position') }}"
                                       placeholder="Contoh: Pranata Komputer Ahli Muda"
                                       class="w-full px-3.5 py-2.5 rounded-xl text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-hidden focus:ring-2 focus:ring-[#0B63B5]">
                            </div>
                        </div>

                        <!-- 5. Kontak & Alamat -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    Nomor Telepon / WhatsApp
                                </label>
                                <input type="text"
                                       name="phone"
                                       value="{{ old('phone') }}"
                                       placeholder="0812xxxxxxxx"
                                       class="w-full px-3.5 py-2.5 rounded-xl text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-hidden focus:ring-2 focus:ring-[#0B63B5]">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    Alamat Rumah (Sesuai KTP)
                                </label>
                                <input type="text"
                                       name="home_address"
                                       value="{{ old('home_address') }}"
                                       placeholder="Alamat domisili pegawai"
                                       class="w-full px-3.5 py-2.5 rounded-xl text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-hidden focus:ring-2 focus:ring-[#0B63B5]">
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer Actions -->
                    <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-2.5">
                        <button type="button"
                                @click="addUserModalOpen = false"
                                class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 font-bold text-xs transition">
                            Batal
                        </button>
                        
                        <button type="submit"
                                class="px-5 py-2 rounded-xl bg-[#0B63B5] hover:bg-[#0B4F8A] text-white font-bold text-xs shadow-md shadow-blue-600/25 transition flex items-center gap-1.5 cursor-pointer btn-tactile-press">
                            <i data-lucide="check" class="w-4 h-4"></i>
                            <span>Simpan & Buat Pengguna</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
