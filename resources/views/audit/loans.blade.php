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
        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 hover:text-slate-900 hover:bg-slate-50 transition shadow-2xs">
            <i data-lucide="arrow-left" class="w-4 h-4 text-slate-500"></i>
            <span>Kembali ke Beranda</span>
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
    <div class="p-4 rounded-2xl bg-blue-50/60 border border-blue-200/70 flex items-start gap-3.5">
        <div class="w-8 h-8 rounded-xl bg-blue-100 text-[#0B63B5] flex items-center justify-center shrink-0 mt-0.5">
            <i data-lucide="shield-check" class="w-4 h-4"></i>
        </div>
        <div class="text-xs text-slate-600 leading-relaxed">
            <span class="font-bold text-slate-800">Ruang Lingkup Audit Peminjaman:</span>
            Halaman ini khusus memuat rekam jejak siklus operasional peminjaman barang milik negara (BMN) dan telah disaring untuk **tidak menampilkan informasi sensitif** (tanpa pelacakan IP, tanpa berkas rahasia, dan tanpa log perubahan sistem di luar peminjaman barang).
            @if(auth()->user()->isAdmin())
                Untuk log teknis forensik infrastruktur IT, silakan akses <a href="{{ route('admin.activity-logs') }}" class="font-extrabold text-[#0B63B5] hover:underline">Log Aktivitas Sistem</a>.
            @endif
        </div>
    </div>

    <!-- STATS KPI CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Peristiwa Peminjaman -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-[#0B63B5] flex items-center justify-center shrink-0">
                <i data-lucide="git-branch" class="w-6 h-6"></i>
            </div>
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Aktivitas</span>
                <span class="text-2xl font-black text-slate-900 tabular-nums">{{ number_format($totalLoanEvents, 0, ',', '.') }}</span>
                <span class="text-[11px] text-slate-500 block font-medium">Peristiwa SOP tercatat</span>
            </div>
        </div>

        <!-- Hari Ini -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                <i data-lucide="calendar" class="w-6 h-6"></i>
            </div>
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Aktivitas Hari Ini</span>
                <span class="text-2xl font-black text-slate-900 tabular-nums">{{ number_format($todayLoanEvents, 0, ',', '.') }}</span>
                <span class="text-[11px] text-slate-500 block font-medium">Transaksi dalam 24 jam</span>
            </div>
        </div>

        <!-- Sedang Berjalan -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <i data-lucide="clock" class="w-6 h-6"></i>
            </div>
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Sedang Dipinjam</span>
                <span class="text-2xl font-black text-slate-900 tabular-nums">{{ $activeLoans }}</span>
                <span class="text-[11px] text-slate-500 block font-medium">Pengajuan berstatus Aktif</span>
            </div>
        </div>

        <!-- Selesai -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                <i data-lucide="check-circle" class="w-6 h-6"></i>
            </div>
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Selesai & Kembali</span>
                <span class="text-2xl font-black text-slate-900 tabular-nums">{{ $completedLoans }}</span>
                <span class="text-[11px] text-slate-500 block font-medium">Fisik BMN telah dikembalikan</span>
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
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200/80 bg-slate-50/70 text-slate-500 uppercase text-[10px] tracking-wider font-extrabold">
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
                <tbody class="divide-y divide-slate-100">
                    @forelse($logs as $log)
                    @php
                        $loan = $log->loanRequest;
                        $fromStatus = $log->before['status'] ?? '-';
                        $toStatus = $log->after['status'] ?? '-';
                        $note = $log->after['note'] ?? null;
                    @endphp
                    <tr class="hover:bg-blue-50/30 transition-colors">
                        <!-- Waktu -->
                        <td class="py-3.5 px-4 whitespace-nowrap font-medium text-slate-500 tabular-nums">
                            {{ $log->created_at ? $log->created_at->setTimezone('Asia/Makassar')->format('d/m/Y H:i') : '-' }}
                        </td>

                        <!-- ID / Ref -->
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            @if($loan)
                                <a href="{{ route('loans.show', $loan->id) }}" class="font-mono font-bold text-[#0B63B5] hover:underline" title="Buka Detail Pengajuan">
                                    #{{ substr((string)$loan->id, 0, 8) }}
                                </a>
                            @else
                                <span class="font-mono text-slate-400">#{{ substr((string)$log->entity_id, 0, 8) }}</span>
                            @endif
                        </td>

                        <!-- Peminjam & Unit Kerja -->
                        <td class="py-3.5 px-4">
                            @if($loan)
                                <span class="font-extrabold text-slate-900 block">{{ $loan->snap_full_name }}</span>
                                <span class="text-[11px] text-slate-500 font-medium block truncate max-w-xs">{{ $loan->snap_unit_kerja }}</span>
                            @else
                                <span class="text-slate-400 italic">Data pengajuan diarsipkan</span>
                            @endif
                        </td>

                        <!-- Barang BMN -->
                        <td class="py-3.5 px-4">
                            @if($loan && $loan->items->isNotEmpty())
                                <div class="font-bold text-slate-800 truncate max-w-xs">
                                    {{ $loan->items->pluck('snap_item_name')->implode(', ') }}
                                </div>
                                <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                                    {{ $loan->items->count() }} unit barang BMN
                                </div>
                            @else
                                <span class="text-slate-400 italic">—</span>
                            @endif
                        </td>

                        <!-- Tahapan SOP -->
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            <div class="flex items-center gap-1.5">
                                <span class="px-2 py-0.5 rounded-lg bg-slate-100 text-slate-600 font-mono text-[10px] font-bold">{{ $fromStatus }}</span>
                                <i data-lucide="arrow-right" class="w-3 h-3 text-slate-400"></i>
                                <span class="px-2 py-0.5 rounded-lg bg-blue-50 text-[#0B63B5] font-mono text-[10px] font-bold">{{ $toStatus }}</span>
                            </div>
                        </td>

                        <!-- Pelaksana -->
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            <span class="font-bold text-slate-900 block">{{ $log->actor ? $log->actor->name : 'Sistem' }}</span>
                            <span class="text-[10px] text-slate-500 font-semibold block uppercase tracking-wider">{{ $log->actor ? $log->actor->role : 'SYSTEM' }}</span>
                        </td>

                        <!-- Catatan Disposisi -->
                        <td class="py-3.5 px-4 max-w-xs">
                            @if($note)
                                <p class="text-[11px] text-slate-600 italic truncate" title="{{ $note }}">
                                    "{{ $note }}"
                                </p>
                            @else
                                <span class="text-slate-400 italic text-[11px]">—</span>
                            @endif
                        </td>

                        <!-- Tombol Aksi -->
                        <td class="py-3.5 px-4 text-center whitespace-nowrap">
                            <div class="inline-flex items-center gap-1.5">
                                @if($loan)
                                <a href="{{ route('loans.show', $loan->id) }}"
                                   class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl border border-slate-200/80 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs shadow-2xs transition active:scale-95"
                                   title="Buka Dokumen & Berkas Peminjaman">
                                    <i data-lucide="file-text" class="w-3.5 h-3.5 text-[#0B63B5]"></i>
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
                                            'from_status' => $fromStatus,
                                            'to_status' => $toStatus,
                                            'note' => $note,
                                        ]) }})"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-blue-50/80 hover:bg-[#0B63B5] text-[#0B63B5] hover:text-white border border-blue-200/70 hover:border-[#0B63B5] font-bold text-xs shadow-2xs hover:shadow-xs transition-all duration-150 active:scale-95 group cursor-pointer"
                                        title="Rincian Transaksi Audit">
                                    <i data-lucide="file-search" class="w-3.5 h-3.5 text-[#0B63B5] group-hover:text-white transition-colors"></i>
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
                 class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-2xl border border-slate-200/80">

                <!-- Header Modal -->
                <div class="px-6 py-5 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-blue-100 text-[#0B63B5] flex items-center justify-center">
                            <i data-lucide="clipboard-check" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-slate-900" id="modal-detail-title">
                                Rincian Audit Transaksi Peminjaman BMN
                            </h3>
                            <p class="text-[11px] text-slate-500" x-text="'Ref Log ID #' + (selectedLog ? selectedLog.id : '')"></p>
                        </div>
                    </div>
                    <button type="button" @click="detailModalOpen = false" class="p-1.5 rounded-xl hover:bg-slate-200 text-slate-400 hover:text-slate-700 transition cursor-pointer">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Body Modal -->
                <div class="p-6 space-y-5" x-if="selectedLog">
                    <!-- Ringkasan Transaksi SOP -->
                    <div class="p-4 rounded-2xl bg-blue-50/60 border border-blue-200/80">
                        <span class="text-[10px] uppercase font-bold text-blue-600 block mb-1">Perubahan Status Siklus SOP</span>
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 rounded-xl bg-white border border-slate-200 font-mono font-bold text-slate-700 text-xs" x-text="selectedLog ? selectedLog.from_status : ''"></span>
                            <i data-lucide="arrow-right" class="w-4 h-4 text-blue-500"></i>
                            <span class="px-2.5 py-1 rounded-xl bg-[#0B63B5] text-white font-mono font-black text-xs shadow-xs" x-text="selectedLog ? selectedLog.to_status : ''"></span>
                        </div>
                        <template x-if="selectedLog && selectedLog.note">
                            <div class="mt-2.5 pt-2 border-t border-blue-200/60 text-xs text-slate-700">
                                <span class="font-bold text-slate-900">Catatan / Disposisi:</span>
                                <p class="italic mt-0.5 text-slate-600" x-text="'&ldquo;' + selectedLog.note + '&rdquo;'"></p>
                            </div>
                        </template>
                    </div>

                    <!-- Informasi Peminjam & Pelaksana -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Data Peminjam -->
                        <div class="p-4 rounded-2xl bg-slate-50/80 border border-slate-200 text-xs space-y-2">
                            <div class="flex items-center gap-1.5 font-bold text-slate-800 text-[11px] uppercase tracking-wider pb-1 border-b border-slate-200">
                                <i data-lucide="user" class="w-3.5 h-3.5 text-[#0B63B5]"></i>
                                <span>Identitas Peminjam</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 font-bold block">Nama Pegawai:</span>
                                <span class="font-bold text-slate-900 block" x-text="selectedLog ? selectedLog.borrower_name : '-'"></span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 font-bold block">Unit Kerja:</span>
                                <span class="text-slate-700 block" x-text="selectedLog ? selectedLog.unit_kerja : '-'"></span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 font-bold block">Keperluan:</span>
                                <span class="text-slate-700 block italic" x-text="selectedLog ? selectedLog.purpose : '-'"></span>
                            </div>
                        </div>

                        <!-- Data Pelaksana & Waktu -->
                        <div class="p-4 rounded-2xl bg-slate-50/80 border border-slate-200 text-xs space-y-2">
                            <div class="flex items-center gap-1.5 font-bold text-slate-800 text-[11px] uppercase tracking-wider pb-1 border-b border-slate-200">
                                <i data-lucide="shield-check" class="w-3.5 h-3.5 text-[#0B63B5]"></i>
                                <span>Verifikator / Pelaksana Aksi</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 font-bold block">Petugas Pelaksana:</span>
                                <span class="font-bold text-slate-900 block" x-text="selectedLog ? selectedLog.actor_name : '-'"></span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 font-bold block">Peran / Otoritas:</span>
                                <span class="font-bold text-[#0B63B5] block uppercase" x-text="selectedLog ? selectedLog.actor_role : '-'"></span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 font-bold block">Waktu Transaksi (WITA):</span>
                                <span class="font-mono text-slate-700 block tabular-nums" x-text="selectedLog ? selectedLog.created_at : '-'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Daftar Barang BMN Terkait -->
                    <div>
                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                            <i data-lucide="package" class="w-4 h-4 text-[#0B63B5]"></i>
                            <span>Barang Milik Negara (BMN) Terkait</span>
                        </h4>
                        <div class="rounded-2xl border border-slate-200 overflow-hidden text-xs">
                            <table class="w-full text-left">
                                <thead class="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase">
                                    <tr>
                                        <th class="py-2.5 px-3">Nama Aset BMN</th>
                                        <th class="py-2.5 px-3">Kode BMN</th>
                                        <th class="py-2.5 px-3 text-right">NUP</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-for="(item, idx) in (selectedLog && selectedLog.items ? selectedLog.items : [])" :key="idx">
                                        <tr>
                                            <td class="py-2.5 px-3 font-bold text-slate-800" x-text="item.name"></td>
                                            <td class="py-2.5 px-3 font-mono text-slate-500" x-text="item.code || '-'"></td>
                                            <td class="py-2.5 px-3 font-mono font-bold text-[#0B63B5] text-right" x-text="item.nup || '-'"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Footer Modal -->
                <div class="px-6 py-4 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                    <template x-if="selectedLog && selectedLog.loan_id">
                        <a :href="'/pengajuan/' + selectedLog.loan_id"
                           class="px-4 py-2 rounded-xl bg-blue-50 hover:bg-blue-100 text-[#0B63B5] font-extrabold text-xs flex items-center gap-1.5 transition">
                            <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                            <span>Buka Lembar Pengajuan Lengkap</span>
                        </a>
                    </template>
                    <button type="button" @click="detailModalOpen = false"
                            class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition border border-slate-200/80 shadow-2xs hover:shadow-xs active:scale-95 cursor-pointer ml-auto">
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
                 class="relative bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:max-w-lg w-full border border-slate-200">

                <form method="GET" action="{{ route('audit.loans.export') }}" target="_blank" @submit="setTimeout(() => exportModalOpen = false, 500)">
                    <!-- Modal Header -->
                    <div class="px-6 py-5 bg-gradient-to-r from-emerald-600 to-teal-700 text-white flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-white/10 flex items-center justify-center">
                                <i data-lucide="file-spreadsheet" class="w-5 h-5 text-white"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-extrabold leading-6" id="export-modal-title">Ekspor Rekapan Audit Peminjaman</h3>
                                <p class="text-xs text-emerald-100 mt-0.5">Laporan resmi aktivitas peminjaman BMN tanpa data sensitif.</p>
                            </div>
                        </div>
                        <button type="button" @click="exportModalOpen = false" class="text-emerald-100 hover:text-white transition">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 space-y-4 text-xs">
                        <!-- Mode Periode -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Pilih Periode Laporan</label>
                            <div class="grid grid-cols-3 gap-2">
                                <button type="button" @click="exportPeriodType = 'month'"
                                        class="py-2.5 px-3 rounded-xl border text-center font-bold transition select-none"
                                        :class="exportPeriodType === 'month' ? 'bg-[#0B63B5] text-white border-[#0B63B5] shadow-xs' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'">
                                    Per Bulan
                                </button>
                                <button type="button" @click="exportPeriodType = 'year'"
                                        class="py-2.5 px-3 rounded-xl border text-center font-bold transition select-none"
                                        :class="exportPeriodType === 'year' ? 'bg-[#0B63B5] text-white border-[#0B63B5] shadow-xs' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'">
                                    Per Tahun
                                </button>
                                <button type="button" @click="exportPeriodType = 'custom'"
                                        class="py-2.5 px-3 rounded-xl border text-center font-bold transition select-none"
                                        :class="exportPeriodType === 'custom' ? 'bg-[#0B63B5] text-white border-[#0B63B5] shadow-xs' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'">
                                    Rentang Tanggal
                                </button>
                            </div>
                            <input type="hidden" name="filter_mode" :value="exportPeriodType">
                        </div>

                        <!-- Parameter Per Bulan -->
                        <div x-show="exportPeriodType === 'month'" class="grid grid-cols-2 gap-3 p-4 bg-slate-50 rounded-2xl border border-slate-200">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 mb-1">Bulan</label>
                                <select name="month" x-model="exportMonth" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white font-medium">
                                    @for($m = 1; $m <= 12; $m++)
                                        <option value="{{ $m }}">{{ \App\Helpers\FormatHelper::getMonthName($m) }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 mb-1">Tahun</label>
                                <select name="year" x-model="exportYear" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white font-medium">
                                    @for($y = now()->year; $y >= now()->year - 4; $y--)
                                        <option value="{{ $y }}">{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>

                        <!-- Parameter Per Tahun -->
                        <div x-show="exportPeriodType === 'year'" class="p-4 bg-slate-50 rounded-2xl border border-slate-200">
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Tahun Anggaran</label>
                            <select name="year" x-model="exportYear" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white font-medium">
                                @for($y = now()->year; $y >= now()->year - 4; $y--)
                                    <option value="{{ $y }}">{{ $y }}</option>
                                @endfor
                            </select>
                        </div>

                        <!-- Parameter Rentang Tanggal Spesifik -->
                        <div x-show="exportPeriodType === 'custom'" class="grid grid-cols-2 gap-3 p-4 bg-slate-50 rounded-2xl border border-slate-200">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 mb-1">Dari Tanggal</label>
                                <input type="date" name="start_date" x-model="exportStartDate" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white font-medium">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 mb-1">Sampai Tanggal</label>
                                <input type="date" name="end_date" x-model="exportEndDate" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white font-medium">
                            </div>
                        </div>

                        <!-- Pilihan Format Unduhan -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Format Berkas</label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="flex items-center gap-3 p-3 rounded-2xl border cursor-pointer transition select-none"
                                       :class="exportFormat === 'xlsx' ? 'border-emerald-500 bg-emerald-50/60 ring-2 ring-emerald-500/20' : 'border-slate-200 hover:bg-slate-50'">
                                    <input type="radio" name="format" value="xlsx" x-model="exportFormat" class="sr-only">
                                    <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                        <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                                    </div>
                                    <div>
                                        <span class="font-extrabold text-slate-900 block">Excel (.xlsx)</span>
                                        <span class="text-[10px] text-slate-500">Direkomendasikan</span>
                                    </div>
                                </label>

                                <label class="flex items-center gap-3 p-3 rounded-2xl border cursor-pointer transition select-none"
                                       :class="exportFormat === 'csv' ? 'border-blue-500 bg-blue-50/60 ring-2 ring-blue-500/20' : 'border-slate-200 hover:bg-slate-50'">
                                    <input type="radio" name="format" value="csv" x-model="exportFormat" class="sr-only">
                                    <div class="w-8 h-8 rounded-xl bg-blue-100 text-[#0B63B5] flex items-center justify-center shrink-0">
                                        <i data-lucide="file-text" class="w-4 h-4"></i>
                                    </div>
                                    <div>
                                        <span class="font-extrabold text-slate-900 block">CSV (.csv)</span>
                                        <span class="text-[10px] text-slate-500">Universal UTF-8</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                        <button type="button" @click="exportModalOpen = false"
                                class="px-4 py-2.5 rounded-xl border border-slate-200/80 bg-white hover:bg-slate-100 text-slate-700 text-xs font-bold transition shadow-2xs hover:shadow-xs active:scale-95 cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
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
@endsection
