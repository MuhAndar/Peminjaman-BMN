@extends('layouts.app')

@section('title', 'Pengaturan Satker — BBPSDMP Komdigi')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-blue-600 transition">Beranda</a>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-slate-600">Administrasi Sistem</span>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-[#0B63B5]">Pengaturan Satker</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-2xl bg-blue-100 text-[#0B63B5] flex items-center justify-center shrink-0">
                    <i data-lucide="settings" class="w-5 h-5"></i>
                </div>
                <span>Pengaturan Satker & Konfigurasi Sistem</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1 font-medium">
                Konfigurasi identitas satuan kerja, alamat resmi kop dinas, dan format penomoran dokumen BMN.
            </p>
        </div>
    </div>

    <!-- Settings Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Satker Info -->
        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-4">
            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i data-lucide="building-2" class="w-5 h-5 text-[#0B63B5]"></i>
                <span>Identitas Satuan Kerja</span>
            </h2>
            <div class="space-y-3 text-xs">
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase">Kode Satker</label>
                    <div class="mt-1 font-mono font-bold text-slate-800 bg-slate-50 p-2.5 rounded-xl border border-slate-200">{{ $satkerCode }}</div>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase">Nama Satker</label>
                    <div class="mt-1 font-bold text-slate-800 bg-slate-50 p-2.5 rounded-xl border border-slate-200">{{ $satkerName }}</div>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase">Alamat Kop Dokumen</label>
                    <div class="mt-1 text-slate-700 bg-slate-50 p-2.5 rounded-xl border border-slate-200">{{ $satkerAddress }}</div>
                </div>
            </div>
        </div>

        <!-- SLA Settings -->
        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-4">
            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i data-lucide="timer" class="w-5 h-5 text-[#0B63B5]"></i>
                <span>SLA Waktu Layanan SOP</span>
            </h2>
            <div class="space-y-3 text-xs">
                <div class="p-3 rounded-xl bg-blue-50 text-blue-900 border border-blue-200">
                    <p class="font-bold">Target Waktu Telaah Pengelola BMN:</p>
                    <p class="mt-0.5 text-xs text-blue-700">Maksimal 60 Menit sejak berkas masuk.</p>
                </div>
                <div class="p-3 rounded-xl bg-purple-50 text-purple-900 border border-purple-200">
                    <p class="font-bold">Target Waktu Persetujuan Ketua Tim:</p>
                    <p class="mt-0.5 text-xs text-purple-700">Maksimal 120 Menit sejak diteruskan dari pengelola.</p>
                </div>
                <div class="p-3 rounded-xl bg-emerald-50 text-emerald-900 border border-emerald-200">
                    <p class="font-bold">Penomoran Dokumen:</p>
                    <p class="mt-0.5 text-xs text-emerald-700">Format Otomatis Atomik Terpadu (Surat Peminjaman, BAST Peminjaman, BAST Pengembalian).</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
