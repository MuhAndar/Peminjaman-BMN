@extends('layouts.app')

@section('title', 'Admin Sistem BMN')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Panel Administrasi Sistem BMN</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Pengaturan master data, akun pengguna, format penomoran dokumen, dan log audit sistem.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <a href="{{ route('audit.loans') }}"
               class="px-4 py-2.5 rounded-xl bg-blue-50/80 hover:bg-[#0B63B5] text-[#0B63B5] hover:text-white border border-blue-200/80 hover:border-[#0B63B5] text-xs font-extrabold flex items-center gap-2 shadow-2xs hover:shadow-xs transition-all duration-150 active:scale-95 group">
                <i data-lucide="clipboard-check" class="w-4 h-4 text-[#0B63B5] group-hover:text-white transition-colors"></i>
                <span>Audit Peminjaman</span>
            </a>
            <a href="{{ route('admin.activity-logs') }}"
               class="px-4 py-2.5 rounded-xl border border-slate-200/80 bg-white hover:bg-slate-50 text-slate-700 text-xs font-extrabold flex items-center gap-2 shadow-2xs hover:shadow-xs transition-all duration-150 active:scale-95">
                <i data-lucide="activity" class="w-4 h-4 text-slate-500"></i>
                <span>Log Sistem</span>
            </a>
            <a href="{{ route('admin.users') }}"
               class="px-4 py-2.5 rounded-xl bg-[#0B63B5] hover:bg-[#0B4F8A] text-white text-xs font-extrabold flex items-center gap-2 shadow-sm hover:shadow-md transition-all duration-150 active:scale-95">
                <i data-lucide="users" class="w-4 h-4"></i>
                <span>Kelola Pengguna</span>
            </a>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <span class="text-xs font-bold text-slate-500 uppercase">Total Pengajuan</span>
            <p class="text-3xl font-extrabold text-slate-900 mt-2 tabular-nums">{{ $stats['total_loans'] }}</p>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <span class="text-xs font-bold text-slate-500 uppercase">Sedang Dipinjam</span>
            <p class="text-3xl font-extrabold text-emerald-600 mt-2 tabular-nums">{{ $stats['active_loans'] }}</p>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <span class="text-xs font-bold text-slate-500 uppercase">Total Aset BMN</span>
            <p class="text-3xl font-extrabold text-slate-900 mt-2 tabular-nums">{{ $stats['total_assets'] }}</p>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <span class="text-xs font-bold text-slate-500 uppercase">BMN Tersedia & Baik</span>
            <p class="text-3xl font-extrabold text-[#0B63B5] mt-2 tabular-nums">{{ $stats['available_assets'] }}</p>
        </div>
    </div>

    <!-- Recent System Activity -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6">
        <h2 class="text-lg font-bold text-slate-900 mb-4">Pengajuan BMN Terkini</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-xs font-bold text-slate-400 uppercase">
                        <th class="pb-3">Waktu</th>
                        <th class="pb-3">Peminjam</th>
                        <th class="pb-3">Barang BMN</th>
                        <th class="pb-3">Status</th>
                        <th class="pb-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($recentLoans as $loan)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="py-3 text-xs text-slate-500 tabular-nums">{{ $loan->created_at->format('d/m/Y H:i') }}</td>
                        <td class="py-3 font-bold text-slate-900">{{ $loan->snap_full_name }}</td>
                        <td class="py-3 text-slate-700 font-semibold">{{ $loan->items_summary }}</td>
                        <td class="py-3">
                            <span class="px-2.5 py-1 text-xs font-bold rounded-full border {{ $loan->status_meta['bg'] }}">
                                {{ $loan->status_meta['label'] }}
                            </span>
                        </td>
                        <td class="py-3 text-right">
                            <a href="{{ route('loans.show', $loan->id) }}" class="text-xs font-bold text-[#0B63B5] hover:underline">Detail</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
