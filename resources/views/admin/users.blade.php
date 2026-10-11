@extends('layouts.app')

@section('title', 'Manajemen Pengguna — BBPSDMP Komdigi')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-blue-600 transition">Beranda</a>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-slate-600">Administrasi Sistem</span>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-[#0B63B5]">Manajemen Pengguna</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-2xl bg-blue-100 text-[#0B63B5] flex items-center justify-center shrink-0">
                    <i data-lucide="users" class="w-5 h-5"></i>
                </div>
                <span>Manajemen Pengguna & Hak Akses (RBAC)</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1 font-medium">
                Daftar akun pegawai dan penetapan peran (Admin, Pengelola BMN, Ketua Tim Aset, Peminjam).
            </p>
        </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-[11px]">
                        <th class="py-3.5 px-4">Nama & Email</th>
                        <th class="py-3.5 px-4">NIP / Pegawai</th>
                        <th class="py-3.5 px-4">Jabatan</th>
                        <th class="py-3.5 px-4">Peran (Role)</th>
                        <th class="py-3.5 px-4">Status Akun</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @foreach($users as $user)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="py-3.5 px-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-xl bg-blue-100 text-[#0B63B5] font-black flex items-center justify-center text-xs">
                                    {{ substr($user->name, 0, 1) }}
                                </div>
                                <div>
                                    <span class="font-bold text-slate-900 block">{{ $user->name }}</span>
                                    <span class="text-[11px] text-slate-400 block">{{ $user->email }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="py-3.5 px-4 font-mono">
                            {{ $user->employee ? \App\Helpers\FormatHelper::nip($user->employee->nip) : '-' }}
                        </td>
                        <td class="py-3.5 px-4">
                            {{ $user->employee->position ?? '-' }}
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold border
                                @if($user->role === 'ADMIN') bg-purple-50 text-purple-700 border-purple-200
                                @elseif($user->role === 'PENGELOLA_BMN') bg-amber-50 text-amber-700 border-amber-200
                                @elseif($user->role === 'KETUA_TIM_ASET') bg-indigo-50 text-indigo-700 border-indigo-200
                                @else bg-blue-50 text-blue-700 border-blue-200 @endif">
                                {{ str_replace('_', ' ', $user->role) }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 font-bold text-[10px] border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                <span>Aktif</span>
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
            {{ $users->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
