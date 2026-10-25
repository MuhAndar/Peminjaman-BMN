@extends('layouts.app')

@section('title', 'Dashboard Pengelola BMN')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Meja Kerja Pengelola BMN</h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                Pantau antrean telaah pengajuan, penerbitan BAST, dan inspeksi fisik pengembalian barang.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('audit.loans') }}" class="px-4 py-2 bg-blue-50/80 dark:bg-blue-950/60 hover:bg-[#0B63B5] text-[#0B63B5] dark:text-blue-300 hover:text-white border border-blue-200/80 dark:border-blue-800 hover:border-[#0B63B5] text-xs font-extrabold rounded-xl shadow-2xs hover:shadow-xs transition flex items-center gap-2 active:scale-95 group">
                <i data-lucide="clipboard-check" class="w-4 h-4 text-[#0B63B5] dark:text-blue-400 group-hover:text-white transition-colors"></i>
                <span>Audit Peminjaman</span>
            </a>
            <a href="{{ route('kelola.queue') }}" class="px-4 py-2 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white text-xs font-bold rounded-xl shadow-sm hover:shadow-md transition flex items-center gap-2 active:scale-95">
                <i data-lucide="list-checks" class="w-4 h-4"></i>
                <span>Buka Semua Antrean</span>
            </a>
        </div>
    </div>

    <!-- SOP Queue Metrics -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="{{ route('kelola.queue', ['status' => 'SUBMITTED']) }}" class="bg-white dark:bg-[#1E293B] p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 hover:border-blue-400 dark:hover:border-blue-500 hover:shadow-md transition group block">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Menunggu Telaah</span>
                <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center group-hover:bg-blue-600 group-hover:text-white transition">
                    <i data-lucide="inbox" class="w-4 h-4"></i>
                </div>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white mt-2 tabular-nums">{{ $stats['need_review'] }}</p>
            <span class="text-[11px] text-blue-600 dark:text-blue-400 font-semibold">SOP-2 (Target 10 Menit)</span>
        </a>

        <a href="{{ route('kelola.queue', ['status' => 'APPROVED']) }}" class="bg-white dark:bg-[#1E293B] p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 hover:border-cyan-400 dark:hover:border-cyan-500 hover:shadow-md transition group block">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Terbitkan BAST</span>
                <div class="w-8 h-8 rounded-lg bg-cyan-50 dark:bg-cyan-950/60 text-cyan-600 dark:text-cyan-400 flex items-center justify-center group-hover:bg-cyan-600 group-hover:text-white transition">
                    <i data-lucide="file-signature" class="w-4 h-4"></i>
                </div>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white mt-2 tabular-nums">{{ $stats['need_bast'] }}</p>
            <span class="text-[11px] text-cyan-600 dark:text-cyan-400 font-semibold">SOP-5 (Disposisi Disetujui)</span>
        </a>

        <a href="{{ route('kelola.queue', ['status' => 'RETURN_REQUESTED']) }}" class="bg-white dark:bg-[#1E293B] p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 hover:border-amber-400 dark:hover:border-amber-500 hover:shadow-md transition group block">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Cek Pengembalian</span>
                <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center group-hover:bg-amber-600 group-hover:text-white transition">
                    <i data-lucide="clipboard-check" class="w-4 h-4"></i>
                </div>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white mt-2 tabular-nums">{{ $stats['need_return_check'] }}</p>
            <span class="text-[11px] text-amber-600 dark:text-amber-400 font-semibold">SOP-Kembali (Target 5 Menit)</span>
        </a>

        <a href="{{ route('kelola.queue', ['status' => 'ACTIVE']) }}" class="bg-white dark:bg-[#1E293B] p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 hover:border-emerald-400 dark:hover:border-emerald-500 hover:shadow-md transition group block">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Sedang Dipinjam</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center group-hover:bg-emerald-600 group-hover:text-white transition">
                    <i data-lucide="package-check" class="w-4 h-4"></i>
                </div>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white mt-2 tabular-nums">{{ $stats['active'] }}</p>
            <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold">Total {{ $stats['total_assets'] }} Barang Terdaftar</span>
        </a>
    </div>

    <!-- Urgent Queue List -->
    <div class="bg-white dark:bg-[#1E293B] rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-6">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Antrean Perlu Tindakan Segera</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Pengajuan yang membutuhkan telaah atau penerbitan dokumen oleh Pengelola BMN</p>
            </div>
        </div>

        @if($pendingRequests->isEmpty())
            <div class="py-12 text-center">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto mb-2">
                    <i data-lucide="check-check" class="w-6 h-6"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">Semua Antrean Selesai</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Tidak ada pengajuan yang membutuhkan telaah saat ini.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-400 uppercase tracking-wider">
                            <th class="pb-3">Pemohon</th>
                            <th class="pb-3">Barang BMN</th>
                            <th class="pb-3">Periode</th>
                            <th class="pb-3">Status Saat Ini</th>
                            <th class="pb-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($pendingRequests as $loan)
                            @php $meta = $loan->status_meta; @endphp
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition">
                                <td class="py-3.5 pr-4">
                                    <div class="font-bold text-slate-900 dark:text-white">{{ $loan->snap_full_name }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ $loan->snap_position ?? 'Pegawai' }}</div>
                                </td>
                                <td class="py-3.5 pr-4">
                                    <div class="font-semibold text-slate-800 dark:text-slate-200">
                                        {{ $loan->items_summary }}
                                    </div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ $loan->items->pluck('snap_brand_model')->filter()->unique()->implode(', ') }}
                                    </div>
                                </td>
                                <td class="py-3.5 pr-4 text-xs font-medium text-slate-600 dark:text-slate-300 tabular-nums">
                                    {{ \App\Helpers\FormatHelper::formatDateIndo($loan->start_date) }}<br>
                                    s.d. {{ \App\Helpers\FormatHelper::formatDateIndo($loan->end_date) }}
                                </td>
                                <td class="py-3.5 pr-4">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold border {{ $meta['bg'] }}">
                                        {{ $meta['label'] }}
                                    </span>
                                </td>
                                <td class="py-3.5 text-right">
                                    <a href="{{ route('loans.show', $loan->id) }}" class="inline-flex items-center gap-1 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white text-xs font-bold px-3 py-1.5 rounded-xl shadow-sm transition">
                                        <span>Proses</span>
                                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
