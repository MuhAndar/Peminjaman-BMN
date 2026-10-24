@extends('layouts.app')

@section('title', 'Pejabat Penandatangan — BBLSDM Komdigi')

@section('content')
<div class="space-y-6">
    <!-- Top Bar Navigation -->
    <div class="flex items-center justify-between gap-3">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}"
           onclick="if (document.referrer && document.referrer.indexOf(window.location.host) !== -1) { history.back(); return false; }"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 hover:text-slate-900 hover:bg-slate-50 transition shadow-2xs">
            <i data-lucide="arrow-left" class="w-4 h-4 text-slate-500"></i>
            <span>Kembali</span>
        </a>
    </div>

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-blue-600 transition">Beranda</a>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-slate-600">Administrasi Sistem</span>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-[#0B63B5]">Pejabat Penandatangan</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-2xl bg-blue-100 text-[#0B63B5] flex items-center justify-center shrink-0">
                    <i data-lucide="feather" class="w-5 h-5"></i>
                </div>
                <span>Pejabat Penandatangan Dokumen Resmi</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1 font-medium">
                Daftar pejabat yang berwenang menandatangani Surat Peminjaman, BAST Peminjaman, dan BAST Pengembalian.
            </p>
        </div>
    </div>

    <!-- Signatories Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($signatories as $sig)
        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-blue-50 text-[#0B63B5] border border-blue-200 uppercase tracking-wider">
                        {{ str_replace('_', ' ', $sig->role_type ?? '') }}
                    </span>
                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <span>Aktif</span>
                    </span>
                </div>
                <h3 class="font-extrabold text-slate-900 text-base">{{ $sig->name }}</h3>
                <p class="text-xs font-mono text-slate-500 mt-0.5">NIP: {{ \App\Helpers\FormatHelper::nip($sig->nip) }}</p>
                <div class="mt-3 pt-3 border-t border-slate-100 space-y-1 text-xs text-slate-600">
                    <p><span class="font-bold text-slate-400">Pangkat/Gol:</span> {{ $sig->rank_grade }}</p>
                    <p><span class="font-bold text-slate-400">Jabatan:</span> {{ $sig->position }}</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
