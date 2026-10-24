@extends('layouts.app')

@section('title', 'Beranda Peminjam')

@section('content')
<div class="space-y-6">
    <!-- Welcome Banner -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-[#0B63B5] to-[#12A8E0] p-6 sm:p-8 text-white shadow-xl shadow-blue-500/10">
        <div class="relative z-10 max-w-2xl">
            <span class="inline-block px-3 py-1 rounded-full bg-white/20 backdrop-blur-md text-xs font-semibold text-white mb-3">
                Selamat Datang, {{ auth()->user()->employee->rank_grade ?? 'Pegawai BBLSDM' }}
            </span>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">
                {{ auth()->user()->name }}
            </h1>
            <p class="mt-2 text-sm text-blue-50 leading-relaxed">
                Kelola pengajuan peminjaman barang inventaris negara dengan cepat dan transparan sesuai alur SOP resmi.
            </p>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('loans.create') }}" class="inline-flex items-center gap-2 bg-white text-[#0B63B5] hover:bg-blue-50 px-5 py-2.5 rounded-xl text-sm font-bold shadow-md transition">
                    <i data-lucide="plus-circle" class="w-4 h-4 text-[#0B63B5]"></i>
                    <span>Buat Pengajuan Baru</span>
                </a>
                <a href="{{ route('assets.index') }}" class="inline-flex items-center gap-2 bg-blue-700/40 hover:bg-blue-700/60 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition border border-white/20">
                    <i data-lucide="search" class="w-4 h-4"></i>
                    <span>Cari Barang Tersedia</span>
                </a>
            </div>
        </div>
        <div class="absolute -right-10 -bottom-10 w-64 h-64 rounded-full bg-white/10 blur-2xl pointer-events-none"></div>
    </div>

    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase">Sedang Dipinjam</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i data-lucide="package-check" class="w-4 h-4"></i>
                </div>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-2 tabular-nums">{{ $stats['active'] }}</p>
            <span class="text-[11px] text-slate-400 font-medium">Fisik BMN pada Anda</span>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase">Dalam Proses</span>
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <i data-lucide="hourglass" class="w-4 h-4"></i>
                </div>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-2 tabular-nums">{{ $stats['pending'] }}</p>
            <span class="text-[11px] text-slate-400 font-medium">Menunggu telaah/persetujuan</span>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase">Selesai Dikembalikan</span>
                <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center">
                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                </div>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-2 tabular-nums">{{ $stats['returned'] }}</p>
            <span class="text-[11px] text-slate-400 font-medium">Riwayat peminjaman</span>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase">Jatuh Tempo</span>
                <div class="w-8 h-8 rounded-lg {{ $stats['overdue'] > 0 ? 'bg-red-50 text-red-600' : 'bg-slate-100 text-slate-400' }} flex items-center justify-center">
                    <i data-lucide="alarm-clock" class="w-4 h-4"></i>
                </div>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold {{ $stats['overdue'] > 0 ? 'text-red-600' : 'text-slate-900' }} mt-2 tabular-nums">{{ $stats['overdue'] }}</p>
            <span class="text-[11px] text-slate-400 font-medium">Perlu pengembalian segera</span>
        </div>
    </div>

    <!-- Recent Loans -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Peminjaman Terbaru Saya</h2>
                <p class="text-xs text-slate-500">Daftar status pengajuan peminjaman terkini</p>
            </div>
            <a href="{{ route('loans.index') }}" class="text-xs font-bold text-[#0B63B5] hover:underline flex items-center gap-1">
                <span>Lihat Semua</span>
                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
            </a>
        </div>

        @if($myLoans->isEmpty())
            <div class="py-12 text-center">
                <div class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                    <i data-lucide="inbox" class="w-6 h-6"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-800">Belum ada peminjaman</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                    Anda belum pernah membuat pengajuan peminjaman barang BMN.
                </p>
                <a href="{{ route('loans.create') }}" class="mt-4 inline-flex items-center gap-2 bg-[#0B63B5] text-white text-xs font-bold px-4 py-2 rounded-xl">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    <span>Ajukan Sekarang</span>
                </a>
            </div>
        @else
            <div class="space-y-3">
                @foreach($myLoans as $loan)
                    @php $meta = $loan->status_meta; @endphp
                    <div class="p-4 rounded-2xl border border-slate-200/70 hover:border-blue-300 hover:shadow-sm transition flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-start gap-3.5">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0B63B5] flex items-center justify-center shrink-0">
                                <i data-lucide="package" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-sm font-bold text-slate-900">
                                        {{ $loan->items_summary }}
                                    </span>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $meta['bg'] }}">
                                        {{ $meta['label'] }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 mt-1">
                                    Periode: <span class="tabular-nums font-medium text-slate-700">{{ \App\Helpers\FormatHelper::formatDateIndo($loan->start_date) }}</span> s.d. <span class="tabular-nums font-medium text-slate-700">{{ \App\Helpers\FormatHelper::formatDateIndo($loan->end_date) }}</span>
                                    &bull; Keperluan: <span class="italic">{{ Str::limit($loan->items->first()->purpose ?? '-', 45) }}</span>
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0 self-end sm:self-center">
                            @if($loan->status === 'ACTIVE')
                                <form method="POST" action="{{ route('loans.request-return', $loan->id) }}">
                                    @csrf
                                    <button type="submit"
                                            data-confirm="Apakah Anda yakin ingin mengajukan pengembalian fisik BMN ini ke Petugas BMN di kantor?"
                                            data-confirm-title="Pengembalian BMN"
                                            data-confirm-btn="Ya, Ajukan Pengembalian"
                                            data-confirm-type="warning"
                                            class="px-3 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold shadow-sm transition flex items-center gap-1.5">
                                        <i data-lucide="package-open" class="w-3.5 h-3.5"></i>
                                        <span>Kembalikan BMN</span>
                                    </button>
                                </form>
                            @endif

                            <a href="{{ route('loans.show', $loan->id) }}" class="px-3 py-1.5 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-bold transition flex items-center gap-1">
                                <span>Lihat Detail</span>
                                <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-400"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
