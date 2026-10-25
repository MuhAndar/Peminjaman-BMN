@extends('layouts.app')

@section('title', 'Persetujuan Peminjaman BMN')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Persetujuan Ketua Tim Keuangan & Aset</h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                Telaah dan berikan persetujuan peminjaman BMN sesuai SOP-4 (Target SLA: 5 Menit).
            </p>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-[#1E293B] p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Menunggu Persetujuan</span>
            <p class="text-3xl font-black text-[#0B63B5] dark:text-blue-400 mt-2 tabular-nums">{{ $stats['waiting'] }}</p>
            <span class="text-[11px] text-blue-600 dark:text-blue-400 font-semibold">Telah ditelaah Pengelola</span>
        </div>

        <div class="bg-white dark:bg-[#1E293B] p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Disetujui</span>
            <p class="text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-2 tabular-nums">{{ $stats['approved'] }}</p>
            <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold">Surat Peminjaman Terbit</span>
        </div>

        <div class="bg-white dark:bg-[#1E293B] p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Ditolak</span>
            <p class="text-3xl font-black text-red-600 dark:text-red-400 mt-2 tabular-nums">{{ $stats['rejected'] }}</p>
            <span class="text-[11px] text-slate-400 font-semibold">Tidak memenuhi syarat</span>
        </div>
    </div>

    <!-- Approval List -->
    <div class="bg-white dark:bg-[#1E293B] rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-6">
        <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Pengajuan Menunggu Persetujuan</h2>

        @if($waitingApproval->isEmpty())
            <div class="py-12 text-center">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto mb-2">
                    <i data-lucide="check-check" class="w-6 h-6"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">Tidak ada pengajuan tertunda</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Semua berkas peminjaman telah diputuskan.</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($waitingApproval as $loan)
                    <div class="p-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800/40 hover:border-blue-400 dark:hover:border-blue-500 transition flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <span class="font-extrabold text-slate-900 dark:text-white text-base">{{ $loan->snap_full_name }}</span>
                                <span class="text-xs text-slate-500 dark:text-slate-400">({{ $loan->snap_position ?? 'Pegawai' }})</span>
                            </div>
                            <div class="text-xs text-slate-600 dark:text-slate-300 flex flex-wrap gap-x-4 gap-y-1">
                                <div><strong>Barang:</strong> {{ $loan->items_summary }}</div>
                                <div><strong>Periode:</strong> {{ \App\Helpers\FormatHelper::formatDateIndo($loan->start_date) }} s.d. {{ \App\Helpers\FormatHelper::formatDateIndo($loan->end_date) }}</div>
                            </div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">
                                <strong>Keperluan:</strong> <em>{{ $loan->items->first()->purpose }}</em>
                            </div>
                            @if($loan->review_note)
                                <div class="text-xs text-blue-700 dark:text-blue-300 bg-blue-50/80 dark:bg-blue-950/60 px-3 py-1.5 rounded-lg border border-blue-200/70 dark:border-blue-800/60 inline-block">
                                    <strong>Catatan Telaah Pengelola:</strong> {{ $loan->review_note }}
                                </div>
                            @endif
                        </div>

                        <div class="flex items-center gap-2 self-end md:self-center shrink-0">
                            <a href="{{ route('persetujuan.show', $loan->id) }}" class="px-4 py-2 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white text-xs font-bold rounded-xl shadow-sm transition flex items-center gap-1.5">
                                <i data-lucide="file-check" class="w-4 h-4"></i>
                                <span>Periksa & Putuskan</span>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
