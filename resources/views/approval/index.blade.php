@extends('layouts.app')

@section('title', 'Persetujuan & Riwayat Telaah Ketua Tim')

@section('content')
<div class="space-y-6">
    <!-- Top Bar Navigation -->
    <div class="flex items-center justify-between gap-3">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}"
           onclick="if (document.referrer && document.referrer.indexOf(window.location.host) !== -1) { history.back(); return false; }"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs font-bold text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800 transition shadow-2xs">
            <i data-lucide="arrow-left" class="w-4 h-4 text-slate-500 dark:text-slate-400"></i>
            <span>Kembali</span>
        </a>
    </div>

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Persetujuan & Riwayat Telaah Ketua Tim</h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                Kelola keputusan persetujuan peminjaman BMN (SOP-4) serta pantau arsip riwayat disposisi yang telah disahkan.
            </p>
        </div>
    </div>

    <!-- Filter Tabs: Menunggu Persetujuan vs Riwayat Disetujui vs Ditolak vs Semua -->
    <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
        <a href="{{ route('persetujuan.index', ['tab' => 'waiting']) }}"
           class="px-4 py-2.5 rounded-xl text-xs font-bold whitespace-nowrap transition flex items-center gap-2 {{ $tab === 'waiting' ? 'bg-[#0B63B5] text-white shadow-sm' : 'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700' }}">
            <i data-lucide="inbox" class="w-4 h-4"></i>
            <span>Perlu Keputusan</span>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-extrabold {{ $tab === 'waiting' ? 'bg-white/20 text-white' : 'bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300' }}">
                {{ $counts['waiting'] }}
            </span>
        </a>

        <a href="{{ route('persetujuan.index', ['tab' => 'approved']) }}"
           class="px-4 py-2.5 rounded-xl text-xs font-bold whitespace-nowrap transition flex items-center gap-2 {{ $tab === 'approved' ? 'bg-[#0B63B5] text-white shadow-sm' : 'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700' }}">
            <i data-lucide="check-check" class="w-4 h-4"></i>
            <span>Riwayat Disetujui</span>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-extrabold {{ $tab === 'approved' ? 'bg-white/20 text-white' : 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300' }}">
                {{ $counts['approved'] }}
            </span>
        </a>

        <a href="{{ route('persetujuan.index', ['tab' => 'rejected']) }}"
           class="px-4 py-2.5 rounded-xl text-xs font-bold whitespace-nowrap transition flex items-center gap-2 {{ $tab === 'rejected' ? 'bg-[#0B63B5] text-white shadow-sm' : 'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700' }}">
            <i data-lucide="x-circle" class="w-4 h-4"></i>
            <span>Riwayat Ditolak / Revisi</span>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-extrabold {{ $tab === 'rejected' ? 'bg-white/20 text-white' : 'bg-red-100 dark:bg-red-950 text-red-700 dark:text-red-300' }}">
                {{ $counts['rejected'] }}
            </span>
        </a>

        <a href="{{ route('persetujuan.index', ['tab' => 'all']) }}"
           class="px-4 py-2.5 rounded-xl text-xs font-bold whitespace-nowrap transition flex items-center gap-2 {{ $tab === 'all' ? 'bg-[#0B63B5] text-white shadow-sm' : 'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700' }}">
            <i data-lucide="layers" class="w-4 h-4"></i>
            <span>Semua Pengajuan</span>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-extrabold {{ $tab === 'all' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">
                {{ $counts['all'] }}
            </span>
        </a>
    </div>

    <!-- Loan Cards Grid -->
    @if($loans->isEmpty())
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-12 text-center shadow-xs">
            <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto mb-2">
                <i data-lucide="folder-search" class="w-6 h-6"></i>
            </div>
            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">
                @if($tab === 'waiting')
                    Tidak ada pengajuan yang menunggu keputusan
                @elseif($tab === 'approved')
                    Belum ada riwayat pengajuan yang disetujui
                @elseif($tab === 'rejected')
                    Tidak ada riwayat pengajuan yang ditolak atau dikembalikan
                @else
                    Belum ada data pengajuan BMN
                @endif
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                @if($tab === 'waiting')
                    Semua pengajuan yang diteruskan oleh Pengelola BMN telah Anda putuskan.
                @else
                    Data peminjaman yang telah selesai diproses akan tetap tersimpan di riwayat ini.
                @endif
            </p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($loans as $loan)
                @php $meta = $loan->status_meta; @endphp
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 hover:border-blue-300 dark:hover:border-blue-500 p-5 shadow-sm space-y-3 transition flex flex-col justify-between">
                    <div>
                        <!-- Header Card -->
                        <div class="flex items-center justify-between text-xs pb-2 border-b border-slate-100 dark:border-slate-800">
                            <span class="text-slate-400 dark:text-slate-500 tabular-nums">Diajukan: {{ $loan->created_at->format('d/m/Y H:i') }}</span>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold border {{ $meta['bg'] }}">
                                {{ $meta['label'] }}
                            </span>
                        </div>

                        <!-- Borrower Info -->
                        <div class="mt-3">
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="font-extrabold text-slate-900 dark:text-white text-base">{{ $loan->snap_full_name }}</h3>
                                @if($loan->status === 'UNDER_APPROVAL')
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/60 px-2 py-0.5 rounded-md border border-amber-200 dark:border-amber-800">
                                        Perlu Tindakan
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ $loan->snap_position ?? 'Pegawai' }} &bull; NIP: <span class="tabular-nums">{{ \App\Helpers\FormatHelper::formatNip($loan->snap_nip) }}</span>
                            </p>
                        </div>

                        <!-- Loan Details -->
                        <div class="mt-3 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-transparent dark:border-slate-700/50 text-xs text-slate-600 dark:text-slate-300 space-y-1">
                            <div><strong class="text-slate-800 dark:text-slate-200">Barang BMN:</strong> {{ $loan->items_summary }}</div>
                            <div><strong class="text-slate-800 dark:text-slate-200">Periode:</strong> {{ \App\Helpers\FormatHelper::formatDateIndo($loan->start_date) }} s.d. {{ \App\Helpers\FormatHelper::formatDateIndo($loan->end_date) }}</div>
                            <div><strong class="text-slate-800 dark:text-slate-200">Keperluan:</strong> <em class="text-slate-700 dark:text-slate-300">{{ $loan->items->first()->purpose ?? '-' }}</em></div>
                        </div>

                        <!-- Review Note from Pengelola -->
                        @if($loan->review_note)
                            <div class="mt-2 text-xs text-blue-700 dark:text-blue-300 bg-blue-50/70 dark:bg-blue-950/40 p-2.5 rounded-xl border border-blue-200/70 dark:border-blue-800/60 leading-relaxed">
                                <strong class="text-blue-800 dark:text-blue-300">Hasil Telaah Pengelola:</strong> {{ $loan->review_note }}
                            </div>
                        @endif

                        <!-- Decision Note from Ketua Tim -->
                        @if($loan->decision_note)
                            <div class="mt-2 text-xs text-slate-700 dark:text-slate-300 bg-emerald-50/50 dark:bg-emerald-950/30 p-2.5 rounded-xl border border-emerald-200/70 dark:border-emerald-800/60 leading-relaxed">
                                <strong class="text-emerald-800 dark:text-emerald-400">Catatan Disposisi Anda:</strong> {{ $loan->decision_note }}
                            </div>
                        @endif

                        <!-- Official Documents -->
                        @php $issuedDocs = $loan->documents()->where('status', 'ISSUED')->get(); @endphp
                        @if($issuedDocs->isNotEmpty())
                            <div class="mt-2.5 pt-2 border-t border-slate-100 dark:border-slate-800 flex flex-wrap items-center gap-2">
                                <span class="text-[10px] font-bold text-slate-400 uppercase">Dokumen Terbit:</span>
                                @foreach($issuedDocs as $doc)
                                    <a href="{{ route('document.pdf', [$loan->id, $doc->doc_type]) }}" target="_blank"
                                       class="inline-flex items-center gap-1 text-[11px] font-bold text-[#0B63B5] dark:text-blue-400 bg-blue-50 dark:bg-blue-950/60 hover:bg-blue-100 dark:hover:bg-blue-900/60 px-2 py-0.5 rounded-md border border-blue-100 dark:border-blue-900/40">
                                        <i data-lucide="file-text" class="w-3 h-3"></i>
                                        <span>{{ $doc->doc_type === 'SURAT_PEMINJAMAN' ? 'Surat Peminjaman' : ($doc->doc_type === 'BAST_PEMINJAMAN' ? 'BAST Pinjam' : 'BAST Kembali') }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- Action Button -->
                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                        <span class="text-[11px] text-slate-400 dark:text-slate-500 font-medium">ID: {{ substr($loan->id, 0, 8) }}</span>
                        @if($loan->status === 'UNDER_APPROVAL')
                            <a href="{{ route('persetujuan.show', $loan->id) }}"
                               class="px-4 py-2 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white text-xs font-bold rounded-xl shadow-sm transition flex items-center gap-1.5">
                                <i data-lucide="file-check" class="w-4 h-4"></i>
                                <span>Buka & Ambil Keputusan</span>
                            </a>
                        @else
                            <a href="{{ route('persetujuan.show', $loan->id) }}"
                               class="px-3.5 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl transition flex items-center gap-1.5">
                                <span>Lihat Berkas & Riwayat</span>
                                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $loans->links() }}
        </div>
    @endif
</div>
@endsection
