@extends('layouts.app')

@section('title', 'Audit Aktivitas Peminjaman BMN — BBLSDM Komdigi')

@section('content')
<div class="space-y-6" x-data="{
    detailModalOpen: false,
    selectedLog: null,
    exportModalOpen: false,
    exportPeriodType: 'month',
    exportMonth: '{{ now()->month }}',
    exportYear: '{{ now()->year }}',
    exportStartDate: '{{ now()->startOfMonth()->toDateString() }}',
    exportEndDate: '{{ now()->toDateString() }}',
    exportFormat: 'xlsx',
    viewDetail(log) {
        this.selectedLog = log;
        this.detailModalOpen = true;
        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
    }
}">
    <!-- Top Bar Navigation -->
    <div class="flex items-center justify-between gap-3">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}"
           onclick="if (document.referrer && document.referrer.indexOf(window.location.host) !== -1) { history.back(); return false; }"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 hover:text-slate-900 hover:bg-slate-50 transition shadow-2xs">
            <i data-lucide="arrow-left" class="w-4 h-4 text-slate-500"></i>
            <span>Kembali</span>
        </a>
    </div>

    <!-- PAGE HEADER -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-blue-600 transition">Beranda</a>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-slate-600">Audit BMN</span>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-[#0B63B5]">Audit Aktivitas Peminjaman</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-2xl bg-blue-100 text-[#0B63B5] flex items-center justify-center shrink-0">
                    <i data-lucide="clipboard-check" class="w-5 h-5"></i>
                </div>
                <span>Audit Aktivitas Peminjaman BMN</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1 font-medium">
                Rekam jejak resmi seluruh tahapan dan siklus SOP peminjaman barang BMN (Pengajuan, Telaah, Persetujuan, Terbit BAST, dan Pengembalian) secara transparan dan akuntabel.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <!-- Ekspor Rekapan Audit Button -->
            <button type="button" @click="exportModalOpen = true; $nextTick(() => { if (window.lucide) lucide.createIcons(); })"
                    class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-extrabold text-xs flex items-center gap-2 transition-all duration-150 shadow-sm hover:shadow-md active:scale-95 cursor-pointer">
                <i data-lucide="download" class="w-4 h-4"></i>
                <span>Ekspor Rekapan Audit</span>
            </button>

            <a href="{{ route('audit.loans') }}" class="px-4 py-2.5 rounded-xl border border-slate-200/80 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs flex items-center gap-2 transition shadow-2xs hover:shadow-xs active:scale-95">
                <i data-lucide="refresh-cw" class="w-4 h-4 text-slate-500"></i>
                <span>Muat Ulang</span>
            </a>
            <div class="px-3.5 py-2 rounded-xl bg-blue-50/80 border border-blue-200/80 text-[#0B63B5] text-xs font-bold flex items-center gap-2 shadow-2xs">
                <span class="w-2 h-2 rounded-full bg-[#0B63B5] animate-pulse"></span>
                <span>Audit Peminjaman Aktif</span>
            </div>
        </div>
    </div>

    <!-- PRIVACY & SCOPE BANNER -->
    <div class="p-4 rounded-2xl bg-blue-50/60 dark:bg-blue-950/40 border border-blue-200/70 dark:border-blue-900/60 flex items-start gap-3.5 text-blue-950 dark:text-blue-200">
        <div class="w-8 h-8 rounded-xl bg-blue-100 dark:bg-blue-900/70 text-[#0B63B5] dark:text-blue-400 flex items-center justify-center shrink-0 mt-0.5">
            <i data-lucide="shield-check" class="w-4 h-4"></i>
        </div>
        <div class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed font-medium">
            <span class="font-bold text-slate-800 dark:text-white">Ruang Lingkup Audit Peminjaman:</span>
            Halaman ini khusus memuat rekam jejak siklus operasional peminjaman barang milik negara (BMN) dan telah disaring untuk **tidak menampilkan informasi sensitif** (tanpa pelacakan IP, tanpa berkas rahasia, dan tanpa log perubahan sistem di luar peminjaman barang).
            @if(auth()->user()->isAdmin())
                Untuk log teknis forensik infrastruktur IT, silakan akses <a href="{{ route('admin.activity-logs') }}" class="font-extrabold text-[#0B63B5] dark:text-blue-400 hover:underline">Log Aktivitas Sistem</a>.
            @endif
        </div>
    </div>

    <!-- STATS KPI CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Peristiwa Peminjaman -->
        <div class="bg-white dark:bg-[#151D2F] p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-[#0B63B5] dark:text-blue-400 flex items-center justify-center shrink-0">
                <i data-lucide="git-branch" class="w-6 h-6"></i>
            </div>
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Aktivitas</span>
                <span class="text-2xl font-black text-slate-900 dark:text-white tabular-nums">{{ number_format($totalLoanEvents, 0, ',', '.') }}</span>
                <span class="text-[11px] text-slate-500 dark:text-slate-400 block font-medium">Peristiwa SOP tercatat</span>
            </div>
        </div>

        <!-- Hari Ini -->
        <div class="bg-white dark:bg-[#151D2F] p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                <i data-lucide="calendar" class="w-6 h-6"></i>
            </div>
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Aktivitas Hari Ini</span>
                <span class="text-2xl font-black text-slate-900 dark:text-white tabular-nums">{{ number_format($todayLoanEvents, 0, ',', '.') }}</span>
                <span class="text-[11px] text-slate-500 dark:text-slate-400 block font-medium">Transaksi dalam 24 jam</span>
            </div>
        </div>

        <!-- Sedang Berjalan -->
        <div class="bg-white dark:bg-[#151D2F] p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                <i data-lucide="clock" class="w-6 h-6"></i>
            </div>
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Sedang Dipinjam</span>
                <span class="text-2xl font-black text-slate-900 dark:text-white tabular-nums">{{ $activeLoans }}</span>
                <span class="text-[11px] text-slate-500 dark:text-slate-400 block font-medium">Pengajuan berstatus Aktif</span>
            </div>
        </div>

        <!-- Selesai -->
        <div class="bg-white dark:bg-[#151D2F] p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0">
                <i data-lucide="check-circle" class="w-6 h-6"></i>
            </div>
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Selesai & Kembali</span>
                <span class="text-2xl font-black text-slate-900 dark:text-white tabular-nums">{{ $completedLoans }}</span>
                <span class="text-[11px] text-slate-500 dark:text-slate-400 block font-medium">Fisik BMN telah dikembalikan</span>
            </div>
        </div>
    </div>

    <!-- FILTER & SEARCH BAR -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
        <form method="GET" action="{{ route('audit.loans') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <!-- Search Text -->
            <div class="lg:col-span-2">
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Cari Peminjam / Barang / ID</label>
                <div class="relative">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-3"></i>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Nama peminjam, barang BMN, NUP, ID..."
                           class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#0B63B5]/20 focus:border-[#0B63B5] font-medium">
                </div>
            </div>

            <!-- Filter Status -->
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Tahapan SOP</label>
                <select name="status" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#0B63B5]/20 focus:border-[#0B63B5] font-medium bg-white">
                    <option value="">Semua Tahapan</option>
                    @foreach($statuses as $stCode => $stLabel)
                        <option value="{{ $stCode }}" {{ request('status') === $stCode ? 'selected' : '' }}>
                            {{ $stLabel }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Pelaksana -->
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Pelaksana / Verifikator</label>
                <select name="actor_id" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#0B63B5]/20 focus:border-[#0B63B5] font-medium bg-white">
                    <option value="">Semua Pelaksana</option>
                    @foreach($actors as $actor)
                        <option value="{{ $actor->id }}" {{ request('actor_id') == $actor->id ? 'selected' : '' }}>
                            {{ $actor->name }} ({{ $actor->role }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Buttons -->
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white py-2 px-3.5 rounded-xl font-bold text-xs flex items-center justify-center gap-1.5 transition-all shadow-sm hover:shadow-md active:scale-95 cursor-pointer">
                    <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                    <span>Terapkan</span>
                </button>
                @if(request()->hasAny(['search', 'status', 'actor_id', 'date']))
                <a href="{{ route('audit.loans') }}" class="p-2 rounded-xl border border-slate-200 text-slate-500 hover:bg-slate-100 transition shadow-2xs active:scale-95" title="Reset Filter">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </a>
                @endif
            </div>
        </form>
    </div>

    <!-- TABEL DATA AUDIT PEMINJAMAN -->
    <div class="bg-white dark:bg-[#151D2F] rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200/80 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-900/60 text-slate-500 dark:text-slate-400 uppercase text-[10px] tracking-wider font-extrabold">
                        <th class="py-3.5 px-4">Waktu (WITA)</th>
                        <th class="py-3.5 px-4">ID / Ref</th>
                        <th class="py-3.5 px-4">Peminjam & Unit Kerja</th>
                        <th class="py-3.5 px-4">Barang BMN</th>
                        <th class="py-3.5 px-4">Tahapan SOP</th>
                        <th class="py-3.5 px-4">Pelaksana</th>
                        <th class="py-3.5 px-4">Catatan Disposisi</th>
                        <th class="py-3.5 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($logs as $log)
                    @php
                        $statusLabels = [
                            'DRAFT' => 'Draf',
                            'SUBMITTED' => 'Diajukan',
                            'NEEDS_REVISION' => 'Perlu Revisi',
                            'REVISION_REQUESTED' => 'Perlu Revisi',
                            'UNDER_APPROVAL' => 'Persetujuan Ketua Tim',
                            'APPROVED' => 'Disetujui',
                            'BAST_ISSUED' => 'BAST Diterbitkan',
                            'ACTIVE' => 'Aktif Dipinjam',
                            'RETURN_REQUESTED' => 'Ajukan Pengembalian',
                            'RETURN_CHECKED' => 'Fisik Diperiksa',
                            'RETURNED' => 'Selesai & Kembali',
                            'REJECTED' => 'Ditolak',
                        ];

                        $actionTranslations = [
                            'CREATE_LOAN_DRAFT' => 'Pembuatan Draf Pengajuan',
                            'UPDATE_LOAN_DRAFT' => 'Pembaruan Draf Pengajuan',
                            'DELETE_LOAN_REQUEST' => 'Penghapusan Berkas Pengajuan',
                            'TRANSITION_LOAN' => 'Transisi Status Pengajuan',
                            'SIGN_LOAN_BORROWER' => 'Tanda Tangan Peminjam',
                            'SIGN_LOAN_OFFICER' => 'Tanda Tangan Petugas BMN',
                            'SIGN_LOAN_APPROVER' => 'Tanda Tangan Ketua Tim Aset',
                            'SIGN_LOAN_borrower' => 'Tanda Tangan Peminjam (BAST)',
                            'SIGN_LOAN_officer' => 'Tanda Tangan Petugas (BAST)',
                            'SIGN_LOAN_approver' => 'Tanda Tangan Ketua Tim Aset',
                            'RESET_LOAN_TO_SUBMITTED' => 'Reset Antrean ke Pengajuan',
                            'UPLOAD_BAST_PHOTO' => 'Unggah Foto Bukti Fisik',
                            'DELETE_BAST_PHOTO' => 'Hapus Foto Fisik',
                            'DELETE_LOAN_PHOTO' => 'Hapus Foto Fisik',
                            'DOWNLOAD_PDF' => 'Unduh Dokumen PDF',
                        ];

                        $actionLabels = [
                            'CREATE_LOAN_DRAFT' => ['label' => 'Pembuatan Draf Pengajuan', 'color' => 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/60 dark:text-sky-300 dark:border-sky-800', 'icon' => 'file-plus-2'],
                            'UPDATE_LOAN_DRAFT' => ['label' => 'Pembaruan Draf Pengajuan', 'color' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800', 'icon' => 'file-edit'],
                            'DELETE_LOAN_REQUEST' => ['label' => 'Penghapusan Berkas Pengajuan', 'color' => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/60 dark:text-red-300 dark:border-red-800', 'icon' => 'trash-2'],
                            'SIGN_LOAN_borrower' => ['label' => 'Tanda Tangan Peminjam (BAST)', 'color' => 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/60 dark:text-purple-300 dark:border-purple-800', 'icon' => 'pen-tool'],
                            'SIGN_LOAN_BORROWER' => ['label' => 'Tanda Tangan Peminjam (Draf/BAST)', 'color' => 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/60 dark:text-purple-300 dark:border-purple-800', 'icon' => 'pen-tool'],
                            'SIGN_LOAN_officer' => ['label' => 'Tanda Tangan Petugas (BAST)', 'color' => 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/60 dark:text-indigo-300 dark:border-indigo-800', 'icon' => 'pen-tool'],
                            'SIGN_LOAN_OFFICER' => ['label' => 'Tanda Tangan Petugas BMN', 'color' => 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/60 dark:text-indigo-300 dark:border-indigo-800', 'icon' => 'pen-tool'],
                            'SIGN_LOAN_approver' => ['label' => 'Tanda Tangan Ketua Tim Aset', 'color' => 'bg-teal-50 text-teal-700 border-teal-200 dark:bg-teal-950/60 dark:text-teal-300 dark:border-teal-800', 'icon' => 'badge-check'],
                            'SIGN_LOAN_APPROVER' => ['label' => 'Tanda Tangan Ketua Tim Aset', 'color' => 'bg-teal-50 text-teal-700 border-teal-200 dark:bg-teal-950/60 dark:text-teal-300 dark:border-teal-800', 'icon' => 'badge-check'],
                            'RESET_LOAN_TO_SUBMITTED' => ['label' => 'Reset Antrean ke Pengajuan', 'color' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800', 'icon' => 'rotate-ccw'],
                            'UPLOAD_BAST_PHOTO' => ['label' => 'Unggah Foto Bukti Fisik', 'color' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800', 'icon' => 'camera'],
                            'DELETE_BAST_PHOTO' => ['label' => 'Hapus Foto Fisik', 'color' => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/60 dark:text-red-300 dark:border-red-800', 'icon' => 'trash-2'],
                            'DELETE_LOAN_PHOTO' => ['label' => 'Hapus Foto Fisik', 'color' => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/60 dark:text-red-300 dark:border-red-800', 'icon' => 'trash-2'],
                            'DOWNLOAD_PDF' => ['label' => 'Unduh Dokumen PDF', 'color' => 'bg-slate-50 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700', 'icon' => 'file-down'],
                        ];

                        $loan = $log->loanRequest;
                        $rawFrom = $log->before['status'] ?? null;
                        $rawTo = $log->after['status'] ?? null;
                        $isTransition = ($rawFrom !== null && $rawTo !== null && $rawFrom !== $rawTo);

                        $fromLabel = $rawFrom ? ($statusLabels[$rawFrom] ?? $rawFrom) : null;
                        $toLabel = $rawTo ? ($statusLabels[$rawTo] ?? $rawTo) : null;

                        $actionInfo = $actionLabels[$log->action] ?? null;
                        $note = $log->after['note'] ?? null;
                        $modalDisplayAction = $isTransition
                            ? "{$fromLabel} → {$toLabel}"
                            : ($actionInfo['label'] ?? ($toLabel ?: ($actionTranslations[$log->action] ?? ucwords(str_replace('_', ' ', strtolower($log->action))))));
                    @endphp
                    <tr class="hover:bg-blue-50/30 dark:hover:bg-slate-800/50 transition-colors">
                        <!-- Waktu -->
                        <td class="py-3.5 px-4 whitespace-nowrap font-medium text-slate-500 dark:text-slate-400 tabular-nums">
                            {{ $log->created_at ? $log->created_at->setTimezone('Asia/Makassar')->format('d/m/Y H:i') : '-' }}
                        </td>

                        <!-- ID / Ref -->
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            @if($loan)
                                <a href="{{ route('loans.show', $loan->id) }}" class="font-mono font-bold text-[#0B63B5] dark:text-blue-400 hover:underline" title="Buka Detail Pengajuan">
                                    #{{ substr((string)$loan->id, 0, 8) }}
                                </a>
                            @else
                                <span class="font-mono text-slate-400 dark:text-slate-500">#{{ substr((string)$log->entity_id, 0, 8) }}</span>
                            @endif
                        </td>

                        <!-- Peminjam & Unit Kerja -->
                        <td class="py-3.5 px-4">
                            @if($loan)
                                <span class="font-extrabold text-slate-900 dark:text-white block">{{ $loan->snap_full_name }}</span>
                                <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium block truncate max-w-xs">{{ $loan->snap_unit_kerja }}</span>
                            @else
                                <span class="text-slate-400 dark:text-slate-500 italic">Data pengajuan diarsipkan</span>
                            @endif
                        </td>

                        <!-- Barang BMN -->
                        <td class="py-3.5 px-4">
                            @if($loan && $loan->items->isNotEmpty())
                                <div class="font-bold text-slate-800 dark:text-slate-200 truncate max-w-xs">
                                    {{ $loan->items_summary }}
                                </div>
                                <div class="text-[10px] text-slate-400 dark:text-slate-500 font-mono mt-0.5">
                                    {{ $loan->items->count() }} unit barang BMN
                                </div>
                            @else
                                <span class="text-slate-400 dark:text-slate-500 italic">—</span>
                            @endif
                        </td>

                        <!-- Tahapan SOP / Aktivitas -->
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            @if($isTransition)
                                <div class="flex items-center gap-1.5">
                                    <span class="px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 text-[11px] font-semibold">{{ $fromLabel }}</span>
                                    <i data-lucide="arrow-right" class="w-3 h-3 text-slate-400 dark:text-slate-500"></i>
                                    <span class="px-2 py-0.5 rounded-lg bg-blue-50 dark:bg-blue-950/70 text-[#0B63B5] dark:text-blue-300 border border-blue-200/60 dark:border-blue-900/60 text-[11px] font-bold">{{ $toLabel }}</span>
                                </div>
                            @elseif($actionInfo)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold border {{ $actionInfo['color'] }}">
                                    <i data-lucide="{{ $actionInfo['icon'] }}" class="w-3.5 h-3.5"></i>
                                    <span>{{ $actionInfo['label'] }}</span>
                                </span>
                            @elseif($toLabel)
                                <span class="px-2.5 py-1 rounded-lg bg-blue-50 dark:bg-blue-950/70 text-[#0B63B5] dark:text-blue-300 border border-blue-200/60 dark:border-blue-900/60 text-[11px] font-bold">
                                    {{ $toLabel }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                    <i data-lucide="activity" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span>{{ $actionTranslations[$log->action] ?? ucwords(str_replace('_', ' ', strtolower($log->action))) }}</span>
                                </span>
                            @endif
                        </td>

                        <!-- Pelaksana -->
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            <span class="font-bold text-slate-900 dark:text-white block">{{ $log->actor ? $log->actor->name : 'Sistem' }}</span>
                            <span class="text-[10px] text-slate-500 dark:text-slate-400 font-semibold block uppercase tracking-wider">{{ $log->actor ? $log->actor->role : 'SYSTEM' }}</span>
                        </td>

                        <!-- Catatan Disposisi -->
                        <td class="py-3.5 px-4 max-w-xs">
                            @if($note)
                                <p class="text-[11px] text-slate-600 dark:text-slate-300 italic truncate" title="{{ $note }}">
                                    "{{ $note }}"
                                </p>
                            @else
                                <span class="text-slate-400 dark:text-slate-500 italic text-[11px]">—</span>
                            @endif
                        </td>

                        <!-- Tombol Aksi -->
                        <td class="py-3.5 px-4 text-center whitespace-nowrap">
                            <div class="inline-flex items-center gap-1.5">
                                @if($loan)
                                <a href="{{ route('loans.show', $loan->id) }}"
                                   class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl border border-slate-200/80 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs shadow-2xs transition active:scale-95"
                                   title="Buka Dokumen & Berkas Peminjaman">
                                    <i data-lucide="file-text" class="w-3.5 h-3.5 text-[#0B63B5] dark:text-blue-400"></i>
                                    <span>Lihat</span>
                                </a>
                                @endif
                                <button type="button"
                                        @click="viewDetail({{ json_encode([
                                            'id' => $log->id,
                                            'created_at' => $log->created_at ? $log->created_at->setTimezone('Asia/Makassar')->format('d/m/Y H:i') : '-',
                                            'actor_name' => $log->actor ? $log->actor->name : 'Sistem',
                                            'actor_role' => $log->actor ? $log->actor->role : 'SYSTEM',
                                            'loan_id' => $loan ? $loan->id : $log->entity_id,
                                            'borrower_name' => $loan ? $loan->snap_full_name : '-',
                                            'borrower_nip' => $loan ? $loan->snap_nip : '-',
                                            'unit_kerja' => $loan ? $loan->snap_unit_kerja : '-',
                                            'purpose' => $loan ? $loan->loan_purpose : '-',
                                            'start_date' => $loan && $loan->loan_start_date ? $loan->loan_start_date->format('d/m/Y') : '-',
                                            'end_date' => $loan && $loan->loan_end_date ? $loan->loan_end_date->format('d/m/Y') : '-',
                                            'items' => $loan ? $loan->items->map(fn($it) => ['name' => $it->snap_item_name, 'code' => $it->bmn_asset_code, 'nup' => $it->nup]) : [],
                                            'from_status' => $fromLabel,
                                            'to_status' => $toLabel,
                                            'is_transition' => $isTransition,
                                            'action_label' => $modalDisplayAction,
                                            'note' => $note,
                                        ]) }})"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-blue-50/80 dark:bg-blue-950/50 hover:bg-[#0B63B5] text-[#0B63B5] dark:text-blue-400 hover:text-white border border-blue-200/70 dark:border-blue-800/80 hover:border-[#0B63B5] font-bold text-xs shadow-2xs hover:shadow-xs transition-all duration-150 active:scale-95 group cursor-pointer"
                                        title="Rincian Transaksi Audit">
                                    <i data-lucide="file-search" class="w-3.5 h-3.5 text-[#0B63B5] dark:text-blue-400 group-hover:text-white transition-colors"></i>
                                    <span>Detail</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-12 text-center text-slate-400">
                            <i data-lucide="inbox" class="w-10 h-10 mx-auto text-slate-300 mb-2"></i>
                            <p class="text-sm font-semibold text-slate-600">Tidak ada riwayat aktivitas peminjaman yang ditemukan.</p>
                            <p class="text-xs text-slate-400 mt-1">Coba sesuaikan filter pencarian di atas.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
        <div class="px-6 py-4 border-t border-slate-200/80 bg-slate-50/60 rounded-b-3xl">
            {{ $logs->links() }}
        </div>
        @endif
    </div>

    <!-- MODAL RINCIAN TRANSAKSI AUDIT PEMINJAMAN (BEBAS DATA SENSITIF) -->
    <div x-show="detailModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-detail-title" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div x-show="detailModalOpen"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
             @click="detailModalOpen = false"></div>

        <div class="flex min-h-full items-center justify-center p-4 sm:p-6 text-center">
            <div x-show="detailModalOpen"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative transform overflow-hidden rounded-3xl bg-white dark:bg-[#151D2F] text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-2xl border border-slate-200/80 dark:border-slate-800">

                <!-- Header Modal -->
                <div class="px-6 py-5 bg-slate-50/80 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-blue-100 dark:bg-blue-900/40 text-[#0B63B5] dark:text-blue-400 flex items-center justify-center">
                            <i data-lucide="clipboard-check" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-slate-900 dark:text-white" id="modal-detail-title">
                                Rincian Audit Transaksi Peminjaman BMN
                            </h3>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400" x-text="'Ref Log ID #' + (selectedLog ? selectedLog.id : '')"></p>
                        </div>
                    </div>
                    <button type="button" @click="detailModalOpen = false" class="p-1.5 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-400 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition cursor-pointer">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Body Modal -->
                <div class="p-6 space-y-5" x-if="selectedLog">
                    <!-- Ringkasan Transaksi SOP -->
                    <div class="p-4 rounded-2xl bg-blue-50/60 dark:bg-blue-950/20 border border-blue-200/80 dark:border-blue-900/40">
                        <template x-if="selectedLog && selectedLog.is_transition">
                            <div>
                                <span class="text-[10px] uppercase font-bold text-blue-600 dark:text-blue-400 block mb-1.5">Perubahan Status Siklus SOP</span>
                                <div class="flex items-center gap-2">
                                    <span class="px-2.5 py-1 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 font-bold text-slate-700 dark:text-slate-200 text-xs shadow-2xs" x-text="selectedLog ? selectedLog.from_status : ''"></span>
                                    <i data-lucide="arrow-right" class="w-4 h-4 text-blue-500 dark:text-blue-400"></i>
                                    <span class="px-2.5 py-1 rounded-xl bg-[#0B63B5] text-white font-black text-xs shadow-xs" x-text="selectedLog ? selectedLog.to_status : ''"></span>
                                </div>
                            </div>
                        </template>
                        <template x-if="selectedLog && !selectedLog.is_transition">
                            <div>
                                <span class="text-[10px] uppercase font-bold text-blue-600 dark:text-blue-400 block mb-1.5">Aktivitas / Pengesahan Dokumen</span>
                                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white dark:bg-slate-800 border border-blue-200 dark:border-blue-900/60 font-bold text-slate-800 dark:text-slate-100 text-xs shadow-2xs">
                                    <i data-lucide="activity" class="w-4 h-4 text-[#0B63B5] dark:text-blue-400"></i>
                                    <span x-text="selectedLog ? selectedLog.action_label : ''"></span>
                                </div>
                            </div>
                        </template>
                        <template x-if="selectedLog && selectedLog.note">
                            <div class="mt-2.5 pt-2 border-t border-blue-200/60 dark:border-blue-900/40 text-xs text-slate-700 dark:text-slate-300">
                                <span class="font-bold text-slate-900 dark:text-white">Catatan / Disposisi:</span>
                                <p class="italic mt-0.5 text-slate-600 dark:text-slate-300" x-text="'&ldquo;' + selectedLog.note + '&rdquo;'"></p>
                            </div>
                        </template>
                    </div>

                    <!-- Informasi Peminjam & Pelaksana -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Data Peminjam -->
                        <div class="p-4 rounded-2xl bg-slate-50/80 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800 text-xs space-y-2">
                            <div class="flex items-center gap-1.5 font-bold text-slate-800 dark:text-slate-200 text-[11px] uppercase tracking-wider pb-1 border-b border-slate-200 dark:border-slate-800">
                                <i data-lucide="user" class="w-3.5 h-3.5 text-[#0B63B5] dark:text-blue-400"></i>
                                <span>Identitas Peminjam</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 dark:text-slate-500 font-bold block">Nama Pegawai:</span>
                                <span class="font-bold text-slate-900 dark:text-white block" x-text="selectedLog ? selectedLog.borrower_name : '-'"></span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 dark:text-slate-500 font-bold block">Unit Kerja:</span>
                                <span class="text-slate-700 dark:text-slate-300 block" x-text="selectedLog ? selectedLog.unit_kerja : '-'"></span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 dark:text-slate-500 font-bold block">Keperluan:</span>
                                <span class="text-slate-700 dark:text-slate-300 block italic" x-text="selectedLog ? selectedLog.purpose : '-'"></span>
                            </div>
                        </div>

                        <!-- Data Pelaksana & Waktu -->
                        <div class="p-4 rounded-2xl bg-slate-50/80 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800 text-xs space-y-2">
                            <div class="flex items-center gap-1.5 font-bold text-slate-800 dark:text-slate-200 text-[11px] uppercase tracking-wider pb-1 border-b border-slate-200 dark:border-slate-800">
                                <i data-lucide="shield-check" class="w-3.5 h-3.5 text-[#0B63B5] dark:text-blue-400"></i>
                                <span>Verifikator / Pelaksana Aksi</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 dark:text-slate-500 font-bold block">Petugas Pelaksana:</span>
                                <span class="font-bold text-slate-900 dark:text-white block" x-text="selectedLog ? selectedLog.actor_name : '-'"></span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 dark:text-slate-500 font-bold block">Peran / Otoritas:</span>
                                <span class="font-bold text-[#0B63B5] dark:text-blue-400 block uppercase" x-text="selectedLog ? selectedLog.actor_role : '-'"></span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 dark:text-slate-500 font-bold block">Waktu Transaksi (WITA):</span>
                                <span class="font-mono text-slate-700 dark:text-slate-300 block tabular-nums" x-text="selectedLog ? selectedLog.created_at : '-'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Daftar Barang BMN Terkait -->
                    <div>
                        <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                            <i data-lucide="package" class="w-4 h-4 text-[#0B63B5] dark:text-blue-400"></i>
                            <span>Barang Milik Negara (BMN) Terkait</span>
                        </h4>
                        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden text-xs">
                            <table class="w-full text-left">
                                <thead class="bg-slate-50 dark:bg-slate-900/60 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase">
                                    <tr>
                                        <th class="py-2.5 px-3">Nama Aset BMN</th>
                                        <th class="py-2.5 px-3">Kode BMN</th>
                                        <th class="py-2.5 px-3 text-right">NUP</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <template x-for="(item, idx) in (selectedLog && selectedLog.items ? selectedLog.items : [])" :key="idx">
                                        <tr>
                                            <td class="py-2.5 px-3 font-bold text-slate-800 dark:text-slate-200" x-text="item.name"></td>
                                            <td class="py-2.5 px-3 font-mono text-slate-500 dark:text-slate-400" x-text="item.code || '-'"></td>
                                            <td class="py-2.5 px-3 font-mono font-bold text-[#0B63B5] dark:text-blue-400 text-right" x-text="item.nup || '-'"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Footer Modal -->
                <div class="px-6 py-4 bg-slate-50/80 dark:bg-slate-900/60 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <template x-if="selectedLog && selectedLog.loan_id">
                        <a :href="'/pengajuan/' + selectedLog.loan_id"
                           class="px-4 py-2 rounded-xl bg-blue-50 dark:bg-blue-950/40 hover:bg-blue-100 dark:hover:bg-blue-900/60 text-[#0B63B5] dark:text-blue-400 font-extrabold text-xs flex items-center gap-1.5 transition">
                            <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                            <span>Buka Lembar Pengajuan Lengkap</span>
                        </a>
                    </template>
                    <button type="button" @click="detailModalOpen = false"
                            class="px-5 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs transition border border-slate-200/80 dark:border-slate-700 shadow-2xs hover:shadow-xs active:scale-95 cursor-pointer ml-auto">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL EKSPOR REKAPAN AUDIT PEMINJAMAN -->
    <div x-show="exportModalOpen" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="export-modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
            <!-- Backdrop -->
            <div x-show="exportModalOpen"
                 x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                 @click="exportModalOpen = false"></div>

            <!-- Modal Panel -->
            <div x-show="exportModalOpen"
                 x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                 class="relative bg-white dark:bg-[#151D2F] rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:max-w-lg w-full border border-slate-200 dark:border-slate-800">

                <form method="GET" action="{{ route('audit.loans.export') }}" hx-boost="false" @submit="setTimeout(() => exportModalOpen = false, 500)">
                    <!-- Modal Header -->
                    <div class="px-6 py-5 bg-gradient-to-r from-emerald-600 to-teal-700 text-white flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-white/10 flex items-center justify-center">
                                <i data-lucide="file-spreadsheet" class="w-5 h-5 text-white"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-extrabold leading-6 text-white" id="export-modal-title">Ekspor Rekapan Audit Peminjaman</h3>
                                <p class="text-xs text-emerald-100 mt-0.5 font-medium">Laporan resmi aktivitas peminjaman BMN tanpa data sensitif.</p>
                            </div>
                        </div>
                        <button type="button" @click="exportModalOpen = false" class="text-emerald-100 hover:text-white transition cursor-pointer">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 space-y-4 text-xs">
                        <!-- Mode Periode -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider mb-2">Pilih Periode Laporan</label>
                            <div class="grid grid-cols-3 gap-2">
                                <button type="button" @click="exportPeriodType = 'month'"
                                        class="py-2.5 px-3 rounded-xl border text-center font-bold transition select-none cursor-pointer"
                                        :class="exportPeriodType === 'month' ? 'bg-[#0B63B5] dark:bg-blue-600 text-white border-[#0B63B5] dark:border-blue-600 shadow-xs' : 'bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700'">
                                    Per Bulan
                                </button>
                                <button type="button" @click="exportPeriodType = 'year'"
                                        class="py-2.5 px-3 rounded-xl border text-center font-bold transition select-none cursor-pointer"
                                        :class="exportPeriodType === 'year' ? 'bg-[#0B63B5] dark:bg-blue-600 text-white border-[#0B63B5] dark:border-blue-600 shadow-xs' : 'bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700'">
                                    Per Tahun
                                </button>
                                <button type="button" @click="exportPeriodType = 'custom'"
                                        class="py-2.5 px-3 rounded-xl border text-center font-bold transition select-none cursor-pointer"
                                        :class="exportPeriodType === 'custom' ? 'bg-[#0B63B5] dark:bg-blue-600 text-white border-[#0B63B5] dark:border-blue-600 shadow-xs' : 'bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700'">
                                    Rentang Tanggal
                                </button>
                            </div>
                            <input type="hidden" name="filter_mode" :value="exportPeriodType">
                        </div>

                        <!-- Parameter Per Bulan -->
                        <div x-show="exportPeriodType === 'month'" class="grid grid-cols-2 gap-3 p-4 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-200 dark:border-slate-700/80">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Bulan</label>
                                <select name="month" x-model="exportMonth" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-medium outline-none">
                                    @for($m = 1; $m <= 12; $m++)
                                        <option value="{{ $m }}">{{ \App\Helpers\FormatHelper::getMonthName($m) }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Tahun</label>
                                <select name="year" x-model="exportYear" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-medium outline-none">
                                    @for($y = now()->year; $y >= now()->year - 4; $y--)
                                        <option value="{{ $y }}">{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>

                        <!-- Parameter Per Tahun -->
                        <div x-show="exportPeriodType === 'year'" class="p-4 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-200 dark:border-slate-700/80">
                            <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Tahun Anggaran</label>
                            <select name="year" x-model="exportYear" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-medium outline-none">
                                @for($y = now()->year; $y >= now()->year - 4; $y--)
                                    <option value="{{ $y }}">{{ $y }}</option>
                                @endfor
                            </select>
                        </div>

                        <!-- Parameter Rentang Tanggal Spesifik -->
                        <div x-show="exportPeriodType === 'custom'" class="grid grid-cols-2 gap-3 p-4 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-200 dark:border-slate-700/80">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Dari Tanggal</label>
                                <input type="date" name="start_date" x-model="exportStartDate" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-medium outline-none">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Sampai Tanggal</label>
                                <input type="date" name="end_date" x-model="exportEndDate" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-medium outline-none">
                            </div>
                        </div>

                        <!-- Pilihan Format Unduhan -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider mb-2">Format Berkas</label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="flex items-center gap-3 p-3 rounded-2xl border cursor-pointer transition select-none"
                                       :class="exportFormat === 'xlsx' ? 'border-emerald-500 bg-emerald-50/70 dark:bg-emerald-950/40 ring-2 ring-emerald-500/20' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800/80 bg-white dark:bg-slate-800/40'">
                                    <input type="radio" name="format" value="xlsx" x-model="exportFormat" class="sr-only">
                                    <div class="w-8 h-8 rounded-xl bg-emerald-100 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 flex items-center justify-center shrink-0">
                                        <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                                    </div>
                                    <div>
                                        <span class="font-extrabold text-slate-900 dark:text-white block">Excel (.xlsx)</span>
                                        <span class="text-[10px] text-slate-500 dark:text-slate-400">Direkomendasikan</span>
                                    </div>
                                </label>

                                <label class="flex items-center gap-3 p-3 rounded-2xl border cursor-pointer transition select-none"
                                       :class="exportFormat === 'csv' ? 'border-blue-500 bg-blue-50/70 dark:bg-blue-950/40 ring-2 ring-blue-500/20' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800/80 bg-white dark:bg-slate-800/40'">
                                    <input type="radio" name="format" value="csv" x-model="exportFormat" class="sr-only">
                                    <div class="w-8 h-8 rounded-xl bg-blue-100 dark:bg-blue-900/60 text-[#0B63B5] dark:text-blue-300 flex items-center justify-center shrink-0">
                                        <i data-lucide="file-text" class="w-4 h-4"></i>
                                    </div>
                                    <div>
                                        <span class="font-extrabold text-slate-900 dark:text-white block">CSV (.csv)</span>
                                        <span class="text-[10px] text-slate-500 dark:text-slate-400">Universal UTF-8</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900/60 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
                        <button type="button" @click="exportModalOpen = false"
                                class="px-4 py-2.5 rounded-xl border border-slate-200/80 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition shadow-2xs hover:shadow-xs active:scale-95 cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" hx-boost="false" download
                                class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white text-xs font-extrabold shadow-sm hover:shadow-md transition-all active:scale-95 flex items-center gap-2 cursor-pointer">
                            <i data-lucide="download" class="w-4 h-4"></i>
                            <span>Unduh Rekapan Peminjaman</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
        </div>
    </div>
</div>
@endsection
