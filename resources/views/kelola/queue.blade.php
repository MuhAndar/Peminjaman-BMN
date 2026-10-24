@extends('layouts.app')

@section('title', 'Antrean Pengelola BMN')

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

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Antrean Pengelolaan BMN</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Daftar pengajuan peminjaman dan pengembalian yang memerlukan proses kerja Pengelola BMN.
            </p>
        </div>
    </div>

    <!-- Status Filters -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
        <a href="{{ route('kelola.queue') }}"
           class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition {{ !$statusFilter ? 'bg-[#0B63B5] text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
            Semua Status
        </a>
        <a href="{{ route('kelola.queue', ['status' => 'SUBMITTED']) }}"
           class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition {{ $statusFilter === 'SUBMITTED' ? 'bg-[#0B63B5] text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
            Menunggu Telaah (SUBMITTED)
        </a>
        <a href="{{ route('kelola.queue', ['status' => 'APPROVED']) }}"
           class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition {{ $statusFilter === 'APPROVED' ? 'bg-[#0B63B5] text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
            Siap Buat BAST (APPROVED)
        </a>
        <a href="{{ route('kelola.queue', ['status' => 'BAST_ISSUED']) }}"
           class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition {{ $statusFilter === 'BAST_ISSUED' ? 'bg-[#0B63B5] text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
            Menunggu Tanda Tangan (BAST_ISSUED)
        </a>
        <a href="{{ route('kelola.queue', ['status' => 'ACTIVE']) }}"
           class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition {{ $statusFilter === 'ACTIVE' ? 'bg-[#0B63B5] text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
            Sedang Dipinjam (ACTIVE)
        </a>
        <a href="{{ route('kelola.queue', ['status' => 'RETURN_REQUESTED']) }}"
           class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition {{ $statusFilter === 'RETURN_REQUESTED' ? 'bg-[#0B63B5] text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
            Cek Pengembalian (RETURN_REQUESTED)
        </a>
    </div>

    <!-- Table of Queue -->
    @if($loans->isEmpty())
        <div class="bg-white rounded-3xl border border-slate-200/80 p-12 text-center">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-2">
                <i data-lucide="check-check" class="w-6 h-6"></i>
            </div>
            <h3 class="text-sm font-bold text-slate-800">Antrean Kosong</h3>
            <p class="text-xs text-slate-500 mt-1">Tidak ada data pengajuan pada filter status ini.</p>
        </div>
    @else
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-400 uppercase">
                            <th class="py-3.5 px-6">Tgl Masuk</th>
                            <th class="py-3.5 px-4">Peminjam</th>
                            <th class="py-3.5 px-4">Barang BMN</th>
                            <th class="py-3.5 px-4">Periode</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($loans as $loan)
                            @php $meta = $loan->status_meta; @endphp
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-4 px-6 text-xs text-slate-500 tabular-nums font-medium">
                                    {{ $loan->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="py-4 px-4">
                                    <div class="font-bold text-slate-900">{{ $loan->snap_full_name }}</div>
                                    <div class="text-xs text-slate-500">{{ $loan->snap_position ?? 'Pegawai' }}</div>
                                </td>
                                <td class="py-4 px-4 font-semibold text-slate-800 text-xs">
                                    {{ $loan->items_summary }}
                                    <div class="text-slate-400 text-[11px] font-normal">
                                        {{ $loan->items->pluck('snap_brand_model')->filter()->unique()->implode(', ') }}
                                    </div>
                                </td>
                                <td class="py-4 px-4 text-xs font-medium text-slate-600 tabular-nums">
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
        </div>

        <div class="mt-4">
            {{ $loans->links() }}
        </div>
    @endif
</div>
@endsection
