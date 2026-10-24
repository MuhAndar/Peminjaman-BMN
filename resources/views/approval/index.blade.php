@extends('layouts.app')

@section('title', 'Daftar Persetujuan Ketua Tim')

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
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Antrean Persetujuan Peminjaman BMN</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Pengajuan yang telah ditelaah oleh Pengelola BMN dan menunggu keputusan Ketua Tim Keuangan & Aset (SOP-4).
            </p>
        </div>
    </div>

    @if($loans->isEmpty())
        <div class="bg-white rounded-3xl border border-slate-200/80 p-12 text-center">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-2">
                <i data-lucide="check-check" class="w-6 h-6"></i>
            </div>
            <h3 class="text-sm font-bold text-slate-800">Tidak ada pengajuan tertunda</h3>
            <p class="text-xs text-slate-500 mt-1">Semua berkas peminjaman yang masuk telah diproses.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($loans as $loan)
                <div class="bg-white rounded-2xl border border-slate-200/80 hover:border-blue-300 p-5 shadow-sm space-y-3 transition flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between text-xs pb-2 border-b border-slate-100">
                            <span class="text-slate-400 tabular-nums">Masuk: {{ $loan->created_at->format('d/m/Y H:i') }}</span>
                            <span class="px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 font-bold border border-indigo-200 text-[11px]">
                                Menunggu Persetujuan
                            </span>
                        </div>

                        <div class="mt-3">
                            <h3 class="font-extrabold text-slate-900 text-base">{{ $loan->snap_full_name }}</h3>
                            <p class="text-xs text-slate-500">{{ $loan->snap_position ?? 'Pegawai' }} &bull; {{ $loan->snap_rank_grade ?? '-' }}</p>
                        </div>

                        <div class="mt-3 p-3 rounded-xl bg-slate-50 text-xs text-slate-600 space-y-1">
                            <div><strong>Barang:</strong> {{ $loan->items->pluck('snap_item_name')->implode(', ') }}</div>
                            <div><strong>Periode:</strong> {{ \App\Helpers\FormatHelper::formatDateIndo($loan->start_date) }} s.d. {{ \App\Helpers\FormatHelper::formatDateIndo($loan->end_date) }}</div>
                            <div><strong>Keperluan:</strong> <em>{{ $loan->items->first()->purpose }}</em></div>
                        </div>

                        @if($loan->review_note)
                            <div class="mt-2 text-xs text-blue-700 bg-blue-50/70 p-2.5 rounded-xl border border-blue-200/70">
                                <strong>Hasil Telaah Pengelola:</strong> {{ $loan->review_note }}
                            </div>
                        @endif
                    </div>

                    <div class="pt-2">
                        <a href="{{ route('persetujuan.show', $loan->id) }}"
                           class="w-full py-2.5 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white text-xs font-bold rounded-xl shadow-sm transition flex items-center justify-center gap-1.5">
                            <i data-lucide="file-check" class="w-4 h-4"></i>
                            <span>Buka & Ambil Keputusan</span>
                        </a>
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
