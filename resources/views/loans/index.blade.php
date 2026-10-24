@extends('layouts.app')

@section('title', 'Riwayat Pengajuan Peminjaman')

@section('content')
<div class="space-y-6">
    <!-- Top Bar Navigation -->
    <div class="flex items-center justify-between gap-3">
        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 hover:text-slate-900 hover:bg-slate-50 transition shadow-2xs">
            <i data-lucide="arrow-left" class="w-4 h-4 text-slate-500"></i>
            <span>Kembali ke Beranda</span>
        </a>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Riwayat Pengajuan Saya</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Pantau seluruh pengajuan peminjaman barang BMN yang telah Anda buat beserta dokumen resminya.
            </p>
        </div>
        <a href="{{ route('loans.create') }}" class="px-4 py-2 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white text-xs font-bold rounded-xl shadow-sm transition flex items-center gap-1.5 self-start sm:self-auto">
            <i data-lucide="plus-circle" class="w-4 h-4"></i>
            <span>Buat Pengajuan Baru</span>
        </a>
    </div>

    @if($loans->isEmpty())
        <div class="bg-white rounded-3xl border border-slate-200/80 p-12 text-center">
            <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                <i data-lucide="file-text" class="w-6 h-6"></i>
            </div>
            <h3 class="text-sm font-bold text-slate-800">Belum ada riwayat pengajuan</h3>
            <p class="text-xs text-slate-500 mt-1">Mulai ajukan peminjaman laptop, mobil dinas, atau BMN lainnya.</p>
        </div>
    @else
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
            <!-- Desktop Table View -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-400 uppercase">
                            <th class="py-3.5 px-6">Tanggal Dibuat</th>
                            <th class="py-3.5 px-4">Barang BMN</th>
                            <th class="py-3.5 px-4">Periode Peminjaman</th>
                            <th class="py-3.5 px-4">Status Pengajuan</th>
                            <th class="py-3.5 px-4">Dokumen Resmi</th>
                            <th class="py-3.5 px-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($loans as $loan)
                            @php $meta = $loan->status_meta; @endphp
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-4 px-6 text-xs text-slate-500 tabular-nums font-medium">
                                    {{ $loan->created_at->format('d/m/Y') }}
                                </td>
                                <td class="py-4 px-4 font-bold text-slate-900">
                                    {{ $loan->items->pluck('snap_item_name')->implode(', ') }}
                                    <div class="text-xs text-slate-500 font-normal">
                                        {{ $loan->items->pluck('snap_brand_model')->filter()->implode(', ') }}
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
                                <td class="py-4 px-4 text-xs">
                                    @php $docs = $loan->documents()->where('status', 'ISSUED')->get(); @endphp
                                    @if($docs->isNotEmpty())
                                        <div class="flex flex-col gap-1">
                                            @foreach($docs as $doc)
                                                <a href="{{ route('document.pdf', [$loan->id, $doc->doc_type]) }}" target="_blank"
                                                   class="text-[#0B63B5] hover:underline font-semibold flex items-center gap-1">
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
                                    <a href="{{ route('loans.show', $loan->id) }}" class="inline-flex items-center gap-1 text-xs font-bold text-[#0B63B5] bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-xl transition">
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
            <div class="md:hidden divide-y divide-slate-100">
                @foreach($loans as $loan)
                    @php $meta = $loan->status_meta; @endphp
                    <div class="p-4 space-y-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="text-xs text-slate-400 font-medium tabular-nums">{{ $loan->created_at->format('d/m/Y') }}</span>
                                <h3 class="font-bold text-slate-900 text-sm mt-0.5">
                                    {{ $loan->items->pluck('snap_item_name')->implode(', ') }}
                                </h3>
                            </div>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $meta['bg'] }}">
                                {{ $meta['label'] }}
                            </span>
                        </div>

                        <div class="text-xs text-slate-600 space-y-1">
                            <div><strong>Periode:</strong> {{ \App\Helpers\FormatHelper::formatDateIndo($loan->start_date) }} s.d. {{ \App\Helpers\FormatHelper::formatDateIndo($loan->end_date) }}</div>
                            <div><strong>Keperluan:</strong> {{ $loan->items->first()->purpose ?? '-' }}</div>
                        </div>

                        <div class="pt-2 flex items-center justify-between">
                            <span class="text-xs text-slate-400">{{ $loan->documents()->count() }} Dokumen</span>
                            <a href="{{ route('loans.show', $loan->id) }}" class="text-xs font-bold text-[#0B63B5] bg-blue-50 px-3 py-1.5 rounded-lg flex items-center gap-1">
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
