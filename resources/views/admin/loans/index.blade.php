@extends('layouts.app')

@section('title', 'Monitoring & Rollback Pengajuan BMN - Admin')

@section('content')
<div class="space-y-6" x-data="{
    rollbackModalOpen: false,
    selectedLoan: null,
    targetStatus: 'SUBMITTED',
    rollbackReason: '',
    openRollback(loan) {
        this.selectedLoan = loan;
        this.targetStatus = 'SUBMITTED';
        this.rollbackReason = '';
        this.rollbackModalOpen = true;
        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
    },
    closeRollback() {
        this.rollbackModalOpen = false;
        this.selectedLoan = null;
    }
}">
    <!-- Header & Breadcrumbs -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">
                <a href="{{ route('dashboard') }}" class="hover:text-[#0B63B5] dark:hover:text-blue-400 transition">Beranda</a>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-400"></i>
                <span class="text-slate-800 dark:text-white">Administrasi</span>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-400"></i>
                <span class="text-[#0B63B5] dark:text-blue-400 font-bold">Monitoring & Rollback</span>
            </nav>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-2xl bg-blue-50 dark:bg-blue-950/60 text-[#0B63B5] dark:text-blue-400 flex items-center justify-center shrink-0">
                    <i data-lucide="git-pull-request" class="w-5 h-5"></i>
                </div>
                <span>Monitoring & Kontrol Alur Pengajuan BMN</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1 max-w-3xl">
                Pantau seluruh siklus permohonan peminjaman inventaris BMN lintas unit kerja, deteksi batas tenggat pengembalian, dan lakukan intervensi alur (rollback) secara aman dan teraudit.
            </p>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3.5 sm:gap-4">
        <!-- Total Pengajuan -->
        <a href="{{ route('admin.loans.index', ['status' => 'all']) }}"
           class="p-4 sm:p-5 rounded-2xl bg-white dark:bg-slate-900 border transition shadow-2xs hover:shadow-md cursor-pointer {{ $statusFilter === 'all' ? 'border-blue-500 ring-2 ring-blue-500/20' : 'border-slate-200/80 dark:border-slate-800' }}">
            <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-2">
                <span class="text-xs font-bold">Total Pengajuan</span>
                <div class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-[#0B63B5] dark:text-blue-400 flex items-center justify-center">
                    <i data-lucide="layers" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 dark:text-white tabular-nums">{{ number_format($stats['total']) }}</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Seluruh arsip siklus</div>
        </a>

        <!-- Aktif Dipinjam -->
        <a href="{{ route('admin.loans.index', ['status' => 'active']) }}"
           class="p-4 sm:p-5 rounded-2xl bg-white dark:bg-slate-900 border transition shadow-2xs hover:shadow-md cursor-pointer {{ $statusFilter === 'active' ? 'border-emerald-500 ring-2 ring-emerald-500/20' : 'border-slate-200/80 dark:border-slate-800' }}">
            <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-2">
                <span class="text-xs font-bold text-emerald-800 dark:text-emerald-300">Aktif Dipinjam</span>
                <div class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 flex items-center justify-center">
                    <i data-lucide="package-check" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-emerald-700 dark:text-emerald-400 tabular-nums">{{ number_format($stats['active']) }}</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Fisik BMN pada peminjam</div>
        </a>

        <!-- Antrean Proses -->
        <a href="{{ route('admin.loans.index', ['status' => 'pending']) }}"
           class="p-4 sm:p-5 rounded-2xl bg-white dark:bg-slate-900 border transition shadow-2xs hover:shadow-md cursor-pointer {{ $statusFilter === 'pending' ? 'border-amber-500 ring-2 ring-amber-500/20' : 'border-slate-200/80 dark:border-slate-800' }}">
            <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-2">
                <span class="text-xs font-bold text-amber-800 dark:text-amber-300">Antrean Proses</span>
                <div class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 flex items-center justify-center">
                    <i data-lucide="clock" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-amber-700 dark:text-amber-400 tabular-nums">{{ number_format($stats['pending']) }}</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Telaah, disposisi, BAST</div>
        </a>

        <!-- Lewat Batas Waktu / Overdue -->
        <a href="{{ route('admin.loans.index', ['status' => 'overdue']) }}"
           class="p-4 sm:p-5 rounded-2xl bg-white dark:bg-slate-900 border transition shadow-2xs hover:shadow-md cursor-pointer {{ $statusFilter === 'overdue' ? 'border-red-500 ring-2 ring-red-500/20' : ($stats['overdue'] > 0 ? 'border-red-300 dark:border-red-900/60 bg-red-50/20 dark:bg-rose-950/20' : 'border-slate-200/80 dark:border-slate-800') }}">
            <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-2">
                <span class="text-xs font-bold text-red-700 dark:text-rose-400">Lewat Tenggat</span>
                <div class="w-8 h-8 rounded-xl bg-red-50 dark:bg-red-950/60 text-red-600 dark:text-rose-400 flex items-center justify-center {{ $stats['overdue'] > 0 ? 'animate-pulse' : '' }}">
                    <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-red-600 dark:text-rose-400 tabular-nums">{{ number_format($stats['overdue']) }}</div>
            <div class="text-[11px] text-red-700 dark:text-rose-300 mt-0.5 font-medium">Perlu dikembalikan segera</div>
        </a>

        <!-- Selesai / Dikembalikan -->
        <a href="{{ route('admin.loans.index', ['status' => 'returned']) }}"
           class="col-span-2 lg:col-span-1 p-4 sm:p-5 rounded-2xl bg-white dark:bg-slate-900 border transition shadow-2xs hover:shadow-md cursor-pointer {{ $statusFilter === 'returned' ? 'border-slate-500 ring-2 ring-slate-500/20' : 'border-slate-200/80 dark:border-slate-800' }}">
            <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-2">
                <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Selesai Kembali</span>
                <div class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center">
                    <i data-lucide="archive" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-800 dark:text-slate-200 tabular-nums">{{ number_format($stats['returned']) }}</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Siklus peminjaman tuntas</div>
        </a>
    </div>

    <!-- Filter Bar & Search -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-4 sm:p-5 shadow-sm space-y-4">
        <form method="GET" action="{{ route('admin.loans.index') }}" class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
            <input type="hidden" name="status" value="{{ $statusFilter }}">

            <!-- Search Field -->
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i data-lucide="search" class="w-4 h-4"></i>
                </div>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari peminjam, NIP, nama barang BMN, merk, nomor surat/BAST..."
                       class="w-full pl-10 pr-4 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50/70 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-medium focus:bg-white dark:focus:bg-slate-900 focus:border-[#0B63B5] dark:focus:border-blue-500 outline-none transition">
                @if(request('search'))
                    <a href="{{ route('admin.loans.index', ['status' => $statusFilter]) }}" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </a>
                @endif
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <button type="submit" class="px-5 py-2.5 rounded-2xl bg-[#0B63B5] hover:bg-[#0B4F8A] text-white text-xs font-bold transition shadow-xs flex items-center gap-1.5 cursor-pointer">
                    <i data-lucide="filter" class="w-4 h-4"></i>
                    <span>Terapkan Filter</span>
                </button>
                @if(request('search') || $statusFilter !== 'all')
                    <a href="{{ route('admin.loans.index') }}" class="px-3.5 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs font-bold transition cursor-pointer">
                        Reset
                    </a>
                @endif
            </div>
        </form>

        <!-- Status Filter Chips -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs font-bold scrollbar-none">
            <span class="text-[11px] text-slate-400 uppercase tracking-wider shrink-0 mr-1">Status:</span>
            <a href="{{ route('admin.loans.index', ['status' => 'all', 'search' => request('search')]) }}"
               class="px-3 py-1.5 rounded-xl transition shrink-0 {{ $statusFilter === 'all' ? 'bg-[#0B63B5] text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200' }}">
                Semua ({{ $stats['total'] }})
            </a>
            <a href="{{ route('admin.loans.index', ['status' => 'active', 'search' => request('search')]) }}"
               class="px-3 py-1.5 rounded-xl transition shrink-0 {{ $statusFilter === 'active' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 hover:bg-emerald-100' }}">
                Aktif Dipinjam ({{ $stats['active'] }})
            </a>
            <a href="{{ route('admin.loans.index', ['status' => 'pending', 'search' => request('search')]) }}"
               class="px-3 py-1.5 rounded-xl transition shrink-0 {{ $statusFilter === 'pending' ? 'bg-amber-600 text-white shadow-xs' : 'bg-amber-50 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 hover:bg-amber-100' }}">
                Antrean Proses ({{ $stats['pending'] }})
            </a>
            @if($stats['overdue'] > 0)
                <a href="{{ route('admin.loans.index', ['status' => 'overdue', 'search' => request('search')]) }}"
                   class="px-3 py-1.5 rounded-xl transition shrink-0 flex items-center gap-1 {{ $statusFilter === 'overdue' ? 'bg-red-600 text-white shadow-xs' : 'bg-red-50 dark:bg-red-950/60 text-red-700 dark:text-rose-300 hover:bg-red-100 border border-red-200' }}">
                    <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>
                    <span>Lewat Tenggat ({{ $stats['overdue'] }})</span>
                </a>
            @endif
            <a href="{{ route('admin.loans.index', ['status' => 'returned', 'search' => request('search')]) }}"
               class="px-3 py-1.5 rounded-xl transition shrink-0 {{ $statusFilter === 'returned' ? 'bg-slate-700 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200' }}">
                Selesai / Kembali ({{ $stats['returned'] }})
            </a>
        </div>
    </div>

    <!-- Data Table / List View -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
        @if($loans->isEmpty())
            <div class="p-12 text-center space-y-3">
                <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto">
                    <i data-lucide="inbox" class="w-7 h-7"></i>
                </div>
                <h3 class="text-base font-bold text-slate-800 dark:text-slate-200">Tidak ada pengajuan BMN ditemukan</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">
                    Tidak ada pengajuan yang sesuai dengan kriteria filter atau pencarian Anda saat ini.
                </p>
                <div class="pt-2">
                    <a href="{{ route('admin.loans.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-200 text-xs font-bold transition">
                        Reset Filter
                    </a>
                </div>
            </div>
        @else
            <!-- Desktop Table View (Hidden on mobile) -->
            <div class="hidden lg:block overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 font-bold uppercase tracking-wider text-[11px]">
                            <th class="py-3.5 px-4">Pengajuan & Tanggal</th>
                            <th class="py-3.5 px-4">Peminjam</th>
                            <th class="py-3.5 px-4">Barang Inventaris (BMN)</th>
                            <th class="py-3.5 px-4">Periode Pinjam</th>
                            <th class="py-3.5 px-4">Status Siklus</th>
                            <th class="py-3.5 px-4">Dokumen</th>
                            <th class="py-3.5 px-4 text-center">Aksi & Rollback</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/70 font-medium">
                        @foreach($loans as $loan)
                            @php
                                $meta = $loan->status_meta;
                                $isOverdue = $loan->isOverdue();
                                $suratPjm = $loan->documents->where('doc_type', 'SURAT_PEMINJAMAN')->first();
                                $bastPjm = $loan->documents->where('doc_type', 'BAST_PEMINJAMAN')->first();
                                $bastKbl = $loan->documents->where('doc_type', 'BAST_PENGEMBALIAN')->first();
                            @endphp
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition {{ $isOverdue ? 'bg-red-50/50 dark:bg-rose-950/20 border-l-4 border-l-red-600' : '' }}">
                                <!-- ID & Tanggal -->
                                <td class="py-3.5 px-4">
                                    <div class="font-mono font-bold text-slate-900 dark:text-white">
                                        #{{ substr($loan->id, 0, 8) }}
                                    </div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400">
                                        {{ \App\Helpers\FormatHelper::formatDateIndo($loan->created_at) }}
                                    </div>
                                </td>

                                <!-- Peminjam -->
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900 dark:text-white">
                                        {{ $loan->snap_full_name }}
                                    </div>
                                    <div class="text-[11px] text-slate-500 font-mono">
                                        NIP. {{ \App\Helpers\FormatHelper::formatNip($loan->snap_nip) }}
                                    </div>
                                </td>

                                <!-- Barang BMN -->
                                <td class="py-3.5 px-4 max-w-xs">
                                    <div class="font-bold text-slate-800 dark:text-slate-200 truncate">
                                        {{ $loan->items_summary }}
                                    </div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                                        {{ $loan->items->pluck('snap_brand_model')->filter()->implode(', ') ?: '-' }}
                                    </div>
                                </td>

                                <!-- Periode Pinjam -->
                                <td class="py-3.5 px-4 tabular-nums">
                                    <div class="text-[11px] text-slate-700 dark:text-slate-300">
                                        {{ \App\Helpers\FormatHelper::formatDateIndo($loan->start_date) }}<br>
                                        s.d. {{ \App\Helpers\FormatHelper::formatDateIndo($loan->end_date) }}
                                    </div>
                                </td>

                                <!-- Status Siklus -->
                                <td class="py-3.5 px-4">
                                    @if($isOverdue)
                                        <div class="space-y-1">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-black bg-red-600 text-white shadow-2xs animate-pulse">
                                                <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>
                                                <span>LEWAT TENGGAT</span>
                                            </span>
                                            <div class="text-[10px] text-red-700 dark:text-rose-300 font-semibold pl-1">
                                                Batas: {{ \App\Helpers\FormatHelper::formatDateIndo($loan->end_date) }}
                                            </div>
                                        </div>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border {{ $meta['bg'] }}">
                                            <i data-lucide="{{ $meta['icon'] }}" class="w-3.5 h-3.5"></i>
                                            <span>{{ $meta['label'] }}</span>
                                        </span>
                                    @endif
                                </td>

                                <!-- Dokumen Terbit -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-1.5">
                                        @if($suratPjm)
                                            <a href="{{ route('document.pdf', ['loanId' => $loan->id, 'docType' => 'SURAT_PEMINJAMAN']) }}" target="_blank"
                                               class="p-1 rounded-md bg-blue-50 dark:bg-blue-950/60 text-[#0B63B5] dark:text-blue-400 hover:bg-blue-100 transition shadow-2xs"
                                               title="Surat Peminjaman: {{ $suratPjm->doc_number }}">
                                                <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                                            </a>
                                        @endif
                                        @if($bastPjm)
                                            <a href="{{ route('document.pdf', ['loanId' => $loan->id, 'docType' => 'BAST_PEMINJAMAN']) }}" target="_blank"
                                               class="p-1 rounded-md bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-100 transition shadow-2xs"
                                               title="BAST Peminjaman: {{ $bastPjm->doc_number }}">
                                                <i data-lucide="package-check" class="w-3.5 h-3.5"></i>
                                            </a>
                                        @endif
                                        @if($bastKbl)
                                            <a href="{{ route('document.pdf', ['loanId' => $loan->id, 'docType' => 'BAST_PENGEMBALIAN']) }}" target="_blank"
                                               class="p-1 rounded-md bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-400 hover:bg-purple-100 transition shadow-2xs"
                                               title="BAST Pengembalian: {{ $bastKbl->doc_number }}">
                                                <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                                            </a>
                                        @endif
                                        @if(!$suratPjm && !$bastPjm && !$bastKbl)
                                            <span class="text-[11px] text-slate-400 italic">Belum terbit</span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Aksi -->
                                <td class="py-3.5 px-4 text-center">
                                    <div class="inline-flex items-center gap-1.5">
                                        <a href="{{ route('loans.show', $loan->id) }}"
                                           class="px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 font-bold text-[11px] transition shadow-2xs">
                                            Detail
                                        </a>
                                        <button type="button"
                                                @click="openRollback({{ json_encode([
                                                    'id' => $loan->id,
                                                    'borrower_name' => $loan->snap_full_name,
                                                    'nip' => $loan->snap_nip,
                                                    'items_summary' => $loan->items_summary,
                                                    'status' => $loan->status,
                                                    'status_label' => $meta['label']
                                                ]) }})"
                                                class="px-2.5 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-[11px] transition shadow-2xs flex items-center gap-1 cursor-pointer">
                                            <i data-lucide="undo" class="w-3 h-3"></i>
                                            <span>Rollback</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card View (Visible on mobile only) -->
            <div class="lg:hidden divide-y divide-slate-100 dark:divide-slate-800">
                @foreach($loans as $loan)
                    @php
                        $meta = $loan->status_meta;
                        $isOverdue = $loan->isOverdue();
                    @endphp
                    <div class="p-4 space-y-3 {{ $isOverdue ? 'bg-red-50/50 dark:bg-rose-950/20 border-l-4 border-l-red-600' : '' }}">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="font-mono text-xs font-bold text-slate-900 dark:text-white">#{{ substr($loan->id, 0, 8) }}</span>
                                <span class="text-xs text-slate-500 ml-1.5">• {{ \App\Helpers\FormatHelper::formatDateIndo($loan->created_at) }}</span>
                            </div>
                            @if($isOverdue)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-red-600 text-white animate-pulse">
                                    LEWAT TENGGAT
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $meta['bg'] }}">
                                    {{ $meta['label'] }}
                                </span>
                            @endif
                        </div>

                        <div>
                            <h4 class="text-sm font-bold text-slate-900 dark:text-white">{{ $loan->snap_full_name }}</h4>
                            <p class="text-xs text-slate-500 font-mono">NIP. {{ \App\Helpers\FormatHelper::formatNip($loan->snap_nip) }}</p>
                        </div>

                        <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 text-xs">
                            <span class="text-slate-500 block mb-0.5 text-[11px]">Barang Inventaris:</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200">{{ $loan->items_summary }}</span>
                        </div>

                        <div class="flex items-center justify-between text-xs text-slate-500 pt-1">
                            <span>Batas: {{ \App\Helpers\FormatHelper::formatDateIndo($loan->end_date) }}</span>
                            <div class="flex items-center gap-1.5">
                                <a href="{{ route('loans.show', $loan->id) }}" class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs">
                                    Detail
                                </a>
                                <button type="button"
                                        @click="openRollback({{ json_encode([
                                            'id' => $loan->id,
                                            'borrower_name' => $loan->snap_full_name,
                                            'nip' => $loan->snap_nip,
                                            'items_summary' => $loan->items_summary,
                                            'status' => $loan->status,
                                            'status_label' => $meta['label']
                                        ]) }})"
                                        class="px-3 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs flex items-center gap-1">
                                    <i data-lucide="undo" class="w-3 h-3"></i>
                                    <span>Rollback</span>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination Bar -->
            <div class="p-4 sm:p-5 border-t border-slate-200 dark:border-slate-800">
                {{ $loans->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL ROLLBACK ALUR PENGAJUAN (ADMIN OVERRIDE) -->
    <div x-show="rollbackModalOpen" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         @keydown.escape.window="closeRollback()">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="closeRollback()"></div>

        <div class="min-h-full flex items-center justify-center p-4">
            <div class="relative bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-5"
                 @click.stop>
                <!-- Modal Header -->
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 flex items-center justify-center">
                            <i data-lucide="rotate-ccw" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Rollback Alur Pengajuan BMN</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Intervensi status administratif oleh Administrator Sistem</p>
                        </div>
                    </div>
                    <button type="button" @click="closeRollback()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg cursor-pointer">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <template x-if="selectedLoan">
                    <form method="POST" :action="'/admin/monitoring-pengajuan/' + selectedLoan.id + '/rollback'" class="space-y-4">
                        @csrf
                        <!-- Ringkasan Pengajuan -->
                        <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 space-y-1.5 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="font-mono font-bold text-slate-900 dark:text-white" x-text="'#' + selectedLoan.id.substring(0, 8)"></span>
                                <span class="px-2 py-0.5 rounded-full font-bold bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 text-[10px]" x-text="selectedLoan.status_label"></span>
                            </div>
                            <div class="font-bold text-slate-800 dark:text-slate-200" x-text="selectedLoan.borrower_name"></div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400" x-text="'Barang: ' + selectedLoan.items_summary"></div>
                        </div>

                        <!-- Pilihan Status Tujuan Rollback -->
                        <div>
                            <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-2">
                                Pilih Posisi / Alur Tujuan Rollback:
                            </label>
                            <div class="space-y-2 text-xs">
                                <!-- Option 1: Draf Pengajuan Ulang -->
                                <label class="flex items-start gap-2.5 p-3 rounded-2xl border cursor-pointer transition"
                                       :class="targetStatus === 'DRAFT' ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-950/40 text-blue-950 dark:text-blue-200 font-bold' : 'border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50'">
                                    <input type="radio" name="target_status" value="DRAFT" x-model="targetStatus" class="mt-0.5">
                                    <div>
                                        <div class="font-bold">1. Pengajuan Ulang (Draf Peminjam)</div>
                                        <div class="text-[11px] font-normal text-slate-500 dark:text-slate-400">Peminjam dapat mengubah daftar barang atau mengajukan permohonan baru.</div>
                                    </div>
                                </label>

                                <!-- Option 2: Ditelaah Ulang -->
                                <label class="flex items-start gap-2.5 p-3 rounded-2xl border cursor-pointer transition"
                                       :class="targetStatus === 'SUBMITTED' ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-950/40 text-blue-950 dark:text-blue-200 font-bold' : 'border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50'">
                                    <input type="radio" name="target_status" value="SUBMITTED" x-model="targetStatus" class="mt-0.5">
                                    <div>
                                        <div class="font-bold">2. Ditelaah Ulang (Antrean Telaah Pengelola)</div>
                                        <div class="text-[11px] font-normal text-slate-500 dark:text-slate-400">Kembalikan ke antrean Petugas BMN untuk penelaahan kesesuaian barang.</div>
                                    </div>
                                </label>

                                <!-- Option 3: Disposisi Ulang -->
                                <label class="flex items-start gap-2.5 p-3 rounded-2xl border cursor-pointer transition"
                                       :class="targetStatus === 'UNDER_APPROVAL' ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-950/40 text-blue-950 dark:text-blue-200 font-bold' : 'border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50'">
                                    <input type="radio" name="target_status" value="UNDER_APPROVAL" x-model="targetStatus" class="mt-0.5">
                                    <div>
                                        <div class="font-bold">3. Disposisi Ulang (Persetujuan Ketua Tim)</div>
                                        <div class="text-[11px] font-normal text-slate-500 dark:text-slate-400">Kembalikan ke antrean persetujuan pimpinan / Ketua Tim Aset.</div>
                                    </div>
                                </label>

                                <!-- Option 4: Penerbitan BAST Ulang -->
                                <label class="flex items-start gap-2.5 p-3 rounded-2xl border cursor-pointer transition"
                                       :class="targetStatus === 'APPROVED' ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-950/40 text-blue-950 dark:text-blue-200 font-bold' : 'border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50'">
                                    <input type="radio" name="target_status" value="APPROVED" x-model="targetStatus" class="mt-0.5">
                                    <div>
                                        <div class="font-bold">4. Persetujuan Sah (Penerbitan BAST Ulang)</div>
                                        <div class="text-[11px] font-normal text-slate-500 dark:text-slate-400">Kembalikan ke tahap siap terbit BAST Peminjaman oleh Pengelola.</div>
                                    </div>
                                </label>

                                <!-- Option 5: Peminjaman Aktif -->
                                <label class="flex items-start gap-2.5 p-3 rounded-2xl border cursor-pointer transition"
                                       :class="targetStatus === 'ACTIVE' ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-950/40 text-blue-950 dark:text-blue-200 font-bold' : 'border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50'">
                                    <input type="radio" name="target_status" value="ACTIVE" x-model="targetStatus" class="mt-0.5">
                                    <div>
                                        <div class="font-bold">5. Peminjaman Aktif (Sedang Dipinjam)</div>
                                        <div class="text-[11px] font-normal text-slate-500 dark:text-slate-400">Status barang aktif digunakan peminjam di lapangan/kantor.</div>
                                    </div>
                                </label>

                                <!-- Option 6: Pengembalian Diajukan Ulang -->
                                <label class="flex items-start gap-2.5 p-3 rounded-2xl border cursor-pointer transition"
                                       :class="targetStatus === 'RETURN_REQUESTED' ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-950/40 text-blue-950 dark:text-blue-200 font-bold' : 'border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50'">
                                    <input type="radio" name="target_status" value="RETURN_REQUESTED" x-model="targetStatus" class="mt-0.5">
                                    <div>
                                        <div class="font-bold">6. Pengembalian Diajukan Ulang (Cek Fisik)</div>
                                        <div class="text-[11px] font-normal text-slate-500 dark:text-slate-400">Kembalikan ke antrean pemeriksaan fisik pengembalian barang.</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Catatan Alasan Rollback (Wajib) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 mb-1">
                                Alasan Rollback Alur (Wajib untuk Rekam Jejak Audit):
                            </label>
                            <textarea name="reason" rows="3" required x-model="rollbackReason"
                                      placeholder="Contoh: Kesalahan upload foto fisik BAST oleh petugas, memerlukan pengunggahan ulang dokumentasi"
                                      class="w-full p-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs outline-none focus:border-amber-500"></textarea>
                            <span class="text-[10px] text-slate-500 dark:text-slate-400">
                                Catatan ini akan dicatat ke dalam riwayat audit sistem dan riwayat status pengajuan.
                            </span>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100 dark:border-slate-800">
                            <button type="button" @click="closeRollback()"
                                    class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs font-bold transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit"
                                    :disabled="rollbackReason.trim().length < 5"
                                    class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 disabled:opacity-40 disabled:cursor-not-allowed text-white rounded-xl text-xs font-bold shadow-md transition flex items-center gap-1.5 cursor-pointer">
                                <i data-lucide="check" class="w-4 h-4"></i>
                                <span>Eksekusi Rollback Alur</span>
                            </button>
                        </div>
                    </form>
                </template>
            </div>
        </div>
    </div>
</div>
@endsection
