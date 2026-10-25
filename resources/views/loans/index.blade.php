@extends('layouts.app')

@section('title', 'Riwayat Pengajuan Peminjaman')

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
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Riwayat Pengajuan Saya</h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                Pantau seluruh pengajuan peminjaman BMN Anda. Data dan dokumen resmi tetap tersimpan permanen bahkan setelah peminjaman selesai.
            </p>
        </div>
        <a href="{{ route('loans.create') }}" class="px-4 py-2 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white text-xs font-bold rounded-xl shadow-sm hover:shadow-md transition flex items-center gap-1.5 self-start sm:self-auto active:scale-95">
            <i data-lucide="plus-circle" class="w-4 h-4"></i>
            <span>Buat Pengajuan Baru</span>
        </a>
    </div>

    <!-- Status Filter Chips -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
        <a href="{{ route('loans.index', ['status' => 'all']) }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition flex items-center gap-2 {{ ($statusFilter ?? 'all') === 'all' ? 'bg-[#0B63B5] text-white shadow-sm' : 'bg-white dark:bg-[#1E293B] border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
            <span>Semua</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ ($statusFilter ?? 'all') === 'all' ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">
                {{ $counts['all'] ?? 0 }}
            </span>
        </a>
        <a href="{{ route('loans.index', ['status' => 'active']) }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition flex items-center gap-2 {{ ($statusFilter ?? '') === 'active' ? 'bg-[#0B63B5] text-white shadow-sm' : 'bg-white dark:bg-[#1E293B] border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
            <span>Sedang Dipinjam</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ ($statusFilter ?? '') === 'active' ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">
                {{ $counts['active'] ?? 0 }}
            </span>
        </a>
        <a href="{{ route('loans.index', ['status' => 'process']) }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition flex items-center gap-2 {{ ($statusFilter ?? '') === 'process' ? 'bg-[#0B63B5] text-white shadow-sm' : 'bg-white dark:bg-[#1E293B] border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
            <span>Dalam Proses</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ ($statusFilter ?? '') === 'process' ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">
                {{ $counts['process'] ?? 0 }}
            </span>
        </a>
        <a href="{{ route('loans.index', ['status' => 'returned']) }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition flex items-center gap-2 {{ ($statusFilter ?? '') === 'returned' ? 'bg-[#0B63B5] text-white shadow-sm' : 'bg-white dark:bg-[#1E293B] border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
            <i data-lucide="archive" class="w-3.5 h-3.5"></i>
            <span>Selesai / Dikembalikan</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ ($statusFilter ?? '') === 'returned' ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">
                {{ $counts['returned'] ?? 0 }}
            </span>
        </a>
        <a href="{{ route('loans.index', ['status' => 'rejected']) }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition flex items-center gap-2 {{ ($statusFilter ?? '') === 'rejected' ? 'bg-[#0B63B5] text-white shadow-sm' : 'bg-white dark:bg-[#1E293B] border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
            <span>Ditolak / Revisi</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ ($statusFilter ?? '') === 'rejected' ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">
                {{ $counts['rejected'] ?? 0 }}
            </span>
        </a>
    </div>

    @if($loans->isEmpty())
        <div class="bg-white dark:bg-[#1E293B] rounded-3xl border border-slate-200/80 dark:border-slate-800 p-12 text-center">
            <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto mb-3">
                <i data-lucide="inbox" class="w-6 h-6"></i>
            </div>
            <h3 class="text-sm font-bold text-slate-800 dark:text-white">Tidak Ada Pengajuan</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Tidak ada data peminjaman pada filter ini.</p>
        </div>
    @else
        <div class="bg-white dark:bg-[#1E293B] rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
            <!-- Desktop Table View -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700/80 text-xs font-bold text-slate-400 uppercase">
                            <th class="py-3.5 px-6">Tanggal Dibuat</th>
                            <th class="py-3.5 px-4">Barang BMN</th>
                            <th class="py-3.5 px-4">Periode Peminjaman</th>
                            <th class="py-3.5 px-4">Status Pengajuan</th>
                            <th class="py-3.5 px-4">Dokumen Resmi</th>
                            <th class="py-3.5 px-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($loans as $loan)
                            @php $meta = $loan->status_meta; @endphp
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/80 transition">
                                <td class="py-4 px-6 text-xs text-slate-500 dark:text-slate-400 tabular-nums font-medium">
                                    {{ $loan->created_at->format('d/m/Y') }}
                                    <div class="text-[10px] font-mono text-slate-400">#{{ substr($loan->id, 0, 8) }}</div>
                                </td>
                                <td class="py-4 px-4 font-bold text-slate-900 dark:text-white">
                                    {{ $loan->items_summary }}
                                    <div class="text-xs text-slate-500 dark:text-slate-400 font-normal">
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
                                <td class="py-4 px-4 text-xs">
                                    @php $docs = $loan->documents()->where('status', 'ISSUED')->get(); @endphp
                                    @if($docs->isNotEmpty())
                                        <div class="flex flex-col gap-1">
                                            @foreach($docs as $doc)
                                                <a href="{{ route('document.pdf', [$loan->id, $doc->doc_type]) }}" target="_blank"
                                                   class="text-[#0B63B5] dark:text-blue-400 hover:underline font-semibold flex items-center gap-1">
                                                    <i data-lucide="file-down" class="w-3.5 h-3.5"></i>
                                                    <span>{{ $doc->doc_type === 'SURAT_PEMINJAMAN' ? 'Surat Peminjaman' : ($doc->doc_type === 'BAST_PEMINJAMAN' ? 'BAST Peminjaman' : 'BAST Kembali') }}</span>
                                                </a>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-slate-400 italic">Belum terbit</span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <a href="{{ route('loans.show', $loan->id) }}" class="inline-flex items-center gap-1 text-xs font-bold text-[#0B63B5] dark:text-blue-400 bg-blue-50 dark:bg-blue-950/60 hover:bg-blue-100 dark:hover:bg-blue-900/60 px-3 py-1.5 rounded-xl transition">
                                        <span>Detail</span>
                                        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
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
                                <span class="text-xs text-slate-400 font-medium tabular-nums">{{ $loan->created_at->format('d/m/Y') }}</span>
                                <h3 class="font-bold text-slate-900 dark:text-white text-sm mt-0.5">
                                    {{ $loan->items_summary }}
                                </h3>
                            </div>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $meta['bg'] }}">
                                {{ $meta['label'] }}
                            </span>
                        </div>

                        <div class="text-xs text-slate-600 dark:text-slate-300 space-y-1 bg-slate-50 dark:bg-slate-800/50 p-2.5 rounded-xl">
                            <div><strong>Periode:</strong> {{ \App\Helpers\FormatHelper::formatDateIndo($loan->start_date) }} s.d. {{ \App\Helpers\FormatHelper::formatDateIndo($loan->end_date) }}</div>
                            @if($loan->items->first()?->purpose)
                                <div><strong>Keperluan:</strong> {{ $loan->items->first()->purpose }}</div>
                            @endif
                        </div>

                        <div class="pt-1 flex items-center justify-between">
                            <span class="text-xs text-slate-400">{{ $loan->documents()->where('status', 'ISSUED')->count() }} Dokumen Terbit</span>
                            <a href="{{ route('loans.show', $loan->id) }}" class="text-xs font-bold text-[#0B63B5] dark:text-blue-400 bg-blue-50 dark:bg-blue-950/60 px-3 py-1.5 rounded-lg flex items-center gap-1">
                                <span>Lihat Detail</span>
                                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
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
