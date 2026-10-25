@extends('layouts.app')

@section('title', 'Antrean & Riwayat Pengelolaan BMN')

@section('content')
<div class="space-y-6">
    <!-- Top Bar Navigation -->
    <div class="flex items-center justify-between gap-3">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}"
           onclick="if (document.referrer && document.referrer.indexOf(window.location.host) !== -1) { history.back(); return false; }"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-200 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-700/80 transition shadow-2xs">
            <i data-lucide="arrow-left" class="w-4 h-4 text-slate-500 dark:text-slate-400"></i>
            <span>Kembali</span>
        </a>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Antrean & Riwayat Pengelolaan BMN</h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                Kelola antrean kerja aktif, penerbitan BAST, verifikasi fisik, serta seluruh riwayat pengajuan yang telah selesai (arsip permanen).
            </p>
        </div>
    </div>

    <!-- Status Filter Chips -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
        <a href="{{ route('kelola.queue') }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition flex items-center gap-2 {{ !$statusFilter ? 'bg-[#0B63B5] text-white shadow-sm' : 'bg-white dark:bg-[#1E293B] border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
            <span>Semua</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ !$statusFilter ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">
                {{ $counts['all'] ?? 0 }}
            </span>
        </a>
        <a href="{{ route('kelola.queue', ['status' => 'SUBMITTED']) }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition flex items-center gap-2 {{ $statusFilter === 'SUBMITTED' ? 'bg-[#0B63B5] text-white shadow-sm' : 'bg-white dark:bg-[#1E293B] border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
            <span>Perlu Ditelaah</span>
            @if(($counts['SUBMITTED'] ?? 0) > 0)
                <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-blue-100 text-blue-700 dark:bg-blue-900/60 dark:text-blue-300 font-bold">
                    {{ $counts['SUBMITTED'] }}
                </span>
            @endif
        </a>
        <a href="{{ route('kelola.queue', ['status' => 'UNDER_APPROVAL']) }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition flex items-center gap-2 {{ $statusFilter === 'UNDER_APPROVAL' ? 'bg-[#0B63B5] text-white shadow-sm' : 'bg-white dark:bg-[#1E293B] border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
            <span>Di Ketua Tim</span>
            @if(($counts['UNDER_APPROVAL'] ?? 0) > 0)
                <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-amber-100 text-amber-700 dark:bg-amber-900/60 dark:text-amber-300 font-bold">
                    {{ $counts['UNDER_APPROVAL'] }}
                </span>
            @endif
        </a>
        <a href="{{ route('kelola.queue', ['status' => 'APPROVED']) }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition flex items-center gap-2 {{ $statusFilter === 'APPROVED' ? 'bg-[#0B63B5] text-white shadow-sm' : 'bg-white dark:bg-[#1E293B] border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
            <span>Siap Terbit BAST</span>
            @if(($counts['APPROVED'] ?? 0) > 0)
                <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300 font-bold">
                    {{ $counts['APPROVED'] }}
                </span>
            @endif
        </a>
        <a href="{{ route('kelola.queue', ['status' => 'BAST_ISSUED']) }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition flex items-center gap-2 {{ $statusFilter === 'BAST_ISSUED' ? 'bg-[#0B63B5] text-white shadow-sm' : 'bg-white dark:bg-[#1E293B] border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
            <span>Menunggu TTD BAST</span>
            @if(($counts['BAST_ISSUED'] ?? 0) > 0)
                <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-purple-100 text-purple-700 dark:bg-purple-900/60 dark:text-purple-300 font-bold">
                    {{ $counts['BAST_ISSUED'] }}
                </span>
            @endif
        </a>
        <a href="{{ route('kelola.queue', ['status' => 'ACTIVE']) }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition flex items-center gap-2 {{ $statusFilter === 'ACTIVE' ? 'bg-[#0B63B5] text-white shadow-sm' : 'bg-white dark:bg-[#1E293B] border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
            <span>Sedang Dipinjam</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $statusFilter === 'ACTIVE' ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">
                {{ $counts['ACTIVE'] ?? 0 }}
            </span>
        </a>
        <a href="{{ route('kelola.queue', ['status' => 'RETURN_REQUESTED']) }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition flex items-center gap-2 {{ $statusFilter === 'RETURN_REQUESTED' ? 'bg-[#0B63B5] text-white shadow-sm' : 'bg-white dark:bg-[#1E293B] border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
            <span>Cek Pengembalian</span>
            @if(($counts['RETURN_REQUESTED'] ?? 0) > 0)
                <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-orange-100 text-orange-700 dark:bg-orange-900/60 dark:text-orange-300 font-bold">
                    {{ $counts['RETURN_REQUESTED'] }}
                </span>
            @endif
        </a>
        <a href="{{ route('kelola.queue', ['status' => 'RETURNED']) }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition flex items-center gap-2 {{ $statusFilter === 'RETURNED' ? 'bg-[#0B63B5] text-white shadow-sm' : 'bg-white dark:bg-[#1E293B] border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
            <i data-lucide="archive" class="w-3.5 h-3.5"></i>
            <span>Selesai / Riwayat Arsip</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $statusFilter === 'RETURNED' ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">
                {{ $counts['RETURNED'] ?? 0 }}
            </span>
        </a>
        <a href="{{ route('kelola.queue', ['status' => 'REJECTED']) }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition flex items-center gap-2 {{ $statusFilter === 'REJECTED' ? 'bg-[#0B63B5] text-white shadow-sm' : 'bg-white dark:bg-[#1E293B] border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
            <span>Ditolak / Revisi</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $statusFilter === 'REJECTED' ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">
                {{ $counts['REJECTED'] ?? 0 }}
            </span>
        </a>
    </div>

    <!-- Table of Queue -->
    @if($loans->isEmpty())
        <div class="bg-white dark:bg-[#1E293B] rounded-3xl border border-slate-200/80 dark:border-slate-800 p-12 text-center">
            <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto mb-2">
                <i data-lucide="inbox" class="w-6 h-6"></i>
            </div>
            <h3 class="text-sm font-bold text-slate-800 dark:text-white">Tidak Ada Data Pengajuan</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Tidak ada data pengajuan peminjaman pada filter status ini.</p>
        </div>
    @else
        <div class="bg-white dark:bg-[#1E293B] rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
            <!-- Desktop Table View -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700/80 text-xs font-bold text-slate-400 uppercase">
                            <th class="py-3.5 px-6">Tgl Masuk</th>
                            <th class="py-3.5 px-4">Peminjam</th>
                            <th class="py-3.5 px-4">Barang BMN</th>
                            <th class="py-3.5 px-4">Periode</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($loans as $loan)
                            @php $meta = $loan->status_meta; @endphp
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/60 transition">
                                <td class="py-4 px-6 text-xs text-slate-500 dark:text-slate-400 tabular-nums font-medium">
                                    {{ $loan->created_at->format('d/m/Y H:i') }}
                                    <div class="text-[10px] font-mono text-slate-400">#{{ substr($loan->id, 0, 8) }}</div>
                                </td>
                                <td class="py-4 px-4">
                                    <div class="font-bold text-slate-900 dark:text-white">{{ $loan->snap_full_name }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ $loan->snap_position ?? 'Pegawai' }} &bull; NIP: {{ $loan->snap_nip ?? '-' }}</div>
                                </td>
                                <td class="py-4 px-4 font-semibold text-slate-800 dark:text-slate-200 text-xs">
                                    {{ $loan->items_summary }}
                                    <div class="text-slate-400 text-[11px] font-normal">
                                        {{ $loan->items->pluck('snap_brand_model')->filter()->unique()->implode(', ') }}
                                    </div>
                                </td>
                                <td class="py-4 px-4 text-xs font-medium text-slate-600 dark:text-slate-300 tabular-nums">
                                    {{ \App\Helpers\FormatHelper::formatDateIndo($loan->start_date) }}<br>
                                    s.d. {{ \App\Helpers\FormatHelper::formatDateIndo($loan->end_date) }}
                                </td>
                                <td class="py-4 px-4">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold border {{ $meta['bg'] }}">
                                        {{ $meta['label'] }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <a href="{{ route('loans.show', $loan->id) }}" class="inline-flex items-center gap-1 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white text-xs font-bold px-3 py-1.5 rounded-xl transition shadow-sm">
                                        <span>Buka Berkas</span>
                                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card View (<768px) -->
            <div class="md:hidden divide-y divide-slate-100 dark:divide-slate-800">
                @foreach($loans as $loan)
                    @php $meta = $loan->status_meta; @endphp
                    <div class="p-4 space-y-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="text-xs text-slate-400 font-medium tabular-nums">{{ $loan->created_at->format('d/m/Y H:i') }}</span>
                                <h3 class="font-bold text-slate-900 dark:text-white text-sm mt-0.5">
                                    {{ $loan->snap_full_name }}
                                </h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $loan->snap_position ?? 'Pegawai' }}</p>
                            </div>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $meta['bg'] }}">
                                {{ $meta['label'] }}
                            </span>
                        </div>

                        <div class="text-xs text-slate-600 dark:text-slate-300 space-y-1 bg-slate-50 dark:bg-slate-800/50 p-2.5 rounded-xl">
                            <div><strong>Barang:</strong> {{ $loan->items_summary }}</div>
                            <div><strong>Periode:</strong> {{ \App\Helpers\FormatHelper::formatDateIndo($loan->start_date) }} s.d. {{ \App\Helpers\FormatHelper::formatDateIndo($loan->end_date) }}</div>
                        </div>

                        <div class="pt-1 flex items-center justify-between">
                            <span class="text-[11px] font-mono text-slate-400">#{{ substr($loan->id, 0, 8) }}</span>
                            <a href="{{ route('loans.show', $loan->id) }}" class="inline-flex items-center gap-1 bg-[#0B63B5] text-white text-xs font-bold px-3 py-1.5 rounded-lg transition">
                                <span>Buka Berkas</span>
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-4">
            {{ $loans->links() }}
        </div>
    @endif
</div>
@endsection
