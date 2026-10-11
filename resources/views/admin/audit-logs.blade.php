@extends('layouts.app')

@section('title', 'Log Aktivitas & Jejak Audit — BBPSDMP Komdigi')

@section('content')
<div class="space-y-6" x-data="{
    detailModalOpen: false,
    selectedLog: null,
    viewDetail(log) {
        this.selectedLog = log;
        this.detailModalOpen = true;
        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
    }
}">
    <!-- PAGE HEADER -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-blue-600 transition">Beranda</a>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-slate-600">Administrasi Sistem</span>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-[#0B63B5]">Log Aktivitas & Audit</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-2xl bg-blue-100 text-[#0B63B5] flex items-center justify-center shrink-0">
                    <i data-lucide="history" class="w-5 h-5"></i>
                </div>
                <span>Log Aktivitas & Jejak Audit Sistem</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1 font-medium">
                Rekam jejak forensik seluruh perubahan status pengajuan BMN, tindakan telaah, persetujuan, penerbitan BAST, dan aktivitas pengguna secara transparan.
            </p>
        </div>

        <div class="flex items-center gap-2.5 shrink-0">
            <a href="{{ route('admin.audit-logs') }}" class="px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs flex items-center gap-2 transition shadow-sm">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                <span>Muat Ulang</span>
            </a>
            <div class="px-3.5 py-2 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Audit Trail Aktif</span>
            </div>
        </div>
    </div>

    <!-- STATS KPI CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Log -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-[#0B63B5] flex items-center justify-center shrink-0">
                <i data-lucide="file-text" class="w-6 h-6"></i>
            </div>
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Aktivitas</span>
                <span class="text-2xl font-black text-slate-900 tabular-nums">{{ number_format($totalLogs, 0, ',', '.') }}</span>
                <span class="text-[11px] text-slate-500 block font-medium">Peristiwa tercatat di sistem</span>
            </div>
        </div>

        <!-- Hari Ini -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                <i data-lucide="calendar" class="w-6 h-6"></i>
            </div>
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Aktivitas Hari Ini</span>
                <span class="text-2xl font-black text-slate-900 tabular-nums">{{ number_format($todayLogs, 0, ',', '.') }}</span>
                <span class="text-[11px] text-slate-500 block font-medium">Peristiwa dalam 24 jam terakhir</span>
            </div>
        </div>

        <!-- Aktor Aktif -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                <i data-lucide="users" class="w-6 h-6"></i>
            </div>
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Pengguna Terlibat</span>
                <span class="text-2xl font-black text-slate-900 tabular-nums">{{ $activeUsers }}</span>
                <span class="text-[11px] text-slate-500 block font-medium">Aktor yang memiliki jejak aksi</span>
            </div>
        </div>

        <!-- Transisi Siklus SOP -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <i data-lucide="git-branch" class="w-6 h-6"></i>
            </div>
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Transisi Status BMN</span>
                <span class="text-2xl font-black text-slate-900 tabular-nums">{{ number_format($statusTransitions, 0, ',', '.') }}</span>
                <span class="text-[11px] text-slate-500 block font-medium">Langkah SOP dieksekusi</span>
            </div>
        </div>
    </div>

    <!-- FILTER & SEARCH BAR -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('admin.audit-logs') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <!-- Search Text -->
            <div class="lg:col-span-2">
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Cari Kata Kunci</label>
                <div class="relative">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-3"></i>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Cari aksi, pengguna, ID, atau IP..."
                           class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#0B63B5]/20 focus:border-[#0B63B5] font-medium">
                </div>
            </div>

            <!-- Filter Aksi -->
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Tipe Aksi</label>
                <select name="action" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#0B63B5]/20 focus:border-[#0B63B5] font-medium bg-white">
                    <option value="">Semua Aksi</option>
                    @foreach($actions as $act)
                        <option value="{{ $act }}" {{ request('action') === $act ? 'selected' : '' }}>{{ $act }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Pengguna -->
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Pengguna / Aktor</label>
                <select name="actor_id" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#0B63B5]/20 focus:border-[#0B63B5] font-medium bg-white">
                    <option value="">Semua Pengguna</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ request('actor_id') == $u->id ? 'selected' : '' }}>
                            {{ $u->name }} ({{ $u->role }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Buttons -->
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white py-2 px-3 rounded-xl font-bold text-xs flex items-center justify-center gap-1.5 transition shadow-sm">
                    <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                    <span>Terapkan</span>
                </button>
                @if(request()->hasAny(['search', 'action', 'actor_id', 'entity', 'date']))
                <a href="{{ route('admin.audit-logs') }}" class="p-2 rounded-xl border border-slate-200 text-slate-500 hover:bg-slate-100 transition" title="Reset Filter">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </a>
                @endif
            </div>
        </form>
    </div>

    <!-- LOG ACTIVITY TABLE -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <i data-lucide="list" class="w-4 h-4 text-[#0B63B5]"></i>
                <span>Daftar Riwayat Perubahan & Log Aktivitas</span>
            </h2>
            <span class="text-xs text-slate-500 font-medium">
                Menampilkan {{ $logs->firstItem() ?? 0 }} - {{ $logs->lastItem() ?? 0 }} dari {{ $logs->total() }} catatan
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-[11px]">
                        <th class="py-3 px-4">Waktu (WITA)</th>
                        <th class="py-3 px-4">Pengguna / Aktor</th>
                        <th class="py-3 px-4">Aksi / Tindakan</th>
                        <th class="py-3 px-4">Entitas & Objek</th>
                        <th class="py-3 px-4">Ringkasan Perubahan</th>
                        <th class="py-3 px-4">Alamat IP</th>
                        <th class="py-3 px-4 text-center">Rincian</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($logs as $log)
                    <tr class="hover:bg-blue-50/40 transition">
                        <!-- Waktu -->
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            <span class="font-bold text-slate-900 block tabular-nums">
                                {{ \App\Helpers\FormatHelper::indonesianDate($log->created_at, true) }}
                            </span>
                            <span class="text-[11px] text-slate-400 block mt-0.5">
                                {{ $log->created_at->diffForHumans() }}
                            </span>
                        </td>

                        <!-- Aktor -->
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            @if($log->actor)
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-lg bg-blue-100 text-[#0B63B5] font-black flex items-center justify-center text-xs shrink-0">
                                        {{ substr($log->actor->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <span class="font-bold text-slate-900 block">{{ $log->actor->name }}</span>
                                        <div class="flex items-center gap-1.5 mt-0.5">
                                            <span class="text-[10px] font-bold px-1.5 py-0.2 rounded
                                                @if($log->actor->role === 'ADMIN') bg-purple-50 text-purple-700 border border-purple-200
                                                @elseif($log->actor->role === 'PENGELOLA_BMN') bg-amber-50 text-amber-700 border border-amber-200
                                                @elseif($log->actor->role === 'KETUA_TIM_ASET') bg-indigo-50 text-indigo-700 border border-indigo-200
                                                @else bg-blue-50 text-blue-700 border border-blue-200 @endif">
                                                {{ str_replace('_', ' ', $log->actor->role) }}
                                            </span>
                                            <span class="text-[10px] text-slate-400">{{ $log->actor->email }}</span>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <span class="text-slate-400 italic">Sistem Otomatis / Tamu</span>
                            @endif
                        </td>

                        <!-- Aksi -->
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            @php
                                $actionName = $log->action;
                                $badgeClass = 'bg-slate-100 text-slate-700 border-slate-200';
                                $iconName = 'activity';

                                if (str_contains($actionName, 'SUBMITTED')) {
                                    $badgeClass = 'bg-blue-50 text-blue-700 border-blue-200';
                                    $iconName = 'send';
                                } elseif (str_contains($actionName, 'UNDER_APPROVAL')) {
                                    $badgeClass = 'bg-amber-50 text-amber-700 border-amber-200';
                                    $iconName = 'clock';
                                } elseif (str_contains($actionName, 'APPROVED')) {
                                    $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                    $iconName = 'check-circle-2';
                                } elseif (str_contains($actionName, 'BAST_ISSUED')) {
                                    $badgeClass = 'bg-purple-50 text-purple-700 border-purple-200';
                                    $iconName = 'file-check';
                                } elseif (str_contains($actionName, 'ACTIVE')) {
                                    $badgeClass = 'bg-cyan-50 text-cyan-700 border-cyan-200';
                                    $iconName = 'box';
                                } elseif (str_contains($actionName, 'RETURN_REQUESTED')) {
                                    $badgeClass = 'bg-orange-50 text-orange-700 border-orange-200';
                                    $iconName = 'corner-down-left';
                                } elseif (str_contains($actionName, 'RETURN_CHECKED')) {
                                    $badgeClass = 'bg-teal-50 text-teal-700 border-teal-200';
                                    $iconName = 'clipboard-check';
                                } elseif (str_contains($actionName, 'COMPLETED')) {
                                    $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                    $iconName = 'check-check';
                                } elseif (str_contains($actionName, 'REJECTED') || str_contains($actionName, 'REVISION')) {
                                    $badgeClass = 'bg-rose-50 text-rose-700 border-rose-200';
                                    $iconName = 'alert-triangle';
                                } elseif (str_contains($actionName, 'LOGIN')) {
                                    $badgeClass = 'bg-sky-50 text-sky-700 border-sky-200';
                                    $iconName = 'log-in';
                                } elseif (str_contains($actionName, 'LOGOUT')) {
                                    $badgeClass = 'bg-slate-50 text-slate-600 border-slate-200';
                                    $iconName = 'log-out';
                                }
                            @endphp
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border font-bold text-[11px] {{ $badgeClass }}">
                                <i data-lucide="{{ $iconName }}" class="w-3 h-3"></i>
                                <span>{{ $actionName }}</span>
                            </span>
                        </td>

                        <!-- Entitas -->
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            <div class="flex items-center gap-1.5">
                                <span class="font-bold text-slate-800">{{ $log->entity }}</span>
                                @if($log->entity === 'LoanRequest' && $log->entity_id)
                                    <a href="{{ route('loans.show', $log->entity_id) }}" class="inline-flex items-center gap-1 text-[11px] text-[#0B63B5] hover:underline font-mono bg-blue-50 px-1.5 py-0.5 rounded border border-blue-200" title="Buka Pengajuan BMN">
                                        <span>#{{ substr($log->entity_id, 0, 8) }}...</span>
                                        <i data-lucide="external-link" class="w-2.5 h-2.5"></i>
                                    </a>
                                @elseif($log->entity_id)
                                    <span class="text-[11px] text-slate-500 font-mono bg-slate-100 px-1.5 py-0.5 rounded">
                                        #{{ substr($log->entity_id, 0, 8) }}
                                    </span>
                                @endif
                            </div>
                        </td>

                        <!-- Ringkasan Perubahan -->
                        <td class="py-3.5 px-4">
                            @if(isset($log->before['status']) && isset($log->after['status']))
                                <div class="flex items-center gap-1.5 text-[11px]">
                                    <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 font-mono">{{ $log->before['status'] }}</span>
                                    <i data-lucide="arrow-right" class="w-3 h-3 text-slate-400"></i>
                                    <span class="px-1.5 py-0.5 rounded bg-blue-50 text-[#0B63B5] font-bold font-mono">{{ $log->after['status'] }}</span>
                                </div>
                                @if(!empty($log->after['note']))
                                    <p class="text-[11px] text-slate-500 italic mt-0.5 truncate max-w-xs">
                                        "{{ $log->after['note'] }}"
                                    </p>
                                @endif
                            @elseif(is_array($log->after) && count($log->after) > 0)
                                <div class="text-[11px] text-slate-600 truncate max-w-xs font-mono">
                                    {{ json_encode($log->after) }}
                                </div>
                            @else
                                <span class="text-slate-400 italic text-[11px]">—</span>
                            @endif
                        </td>

                        <!-- IP Address -->
                        <td class="py-3.5 px-4 whitespace-nowrap font-mono text-[11px] text-slate-500">
                            {{ $log->ip ?? '-' }}
                        </td>

                        <!-- Tombol Detail -->
                        <td class="py-3.5 px-4 text-center whitespace-nowrap">
                            <button type="button"
                                    @click="viewDetail({{ json_encode($log) }})"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-[#0B63B5] hover:text-white text-slate-700 font-bold text-[11px] transition">
                                <i data-lucide="eye" class="w-3 h-3"></i>
                                <span>Lihat Diff</span>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-slate-400">
                            <i data-lucide="inbox" class="w-10 h-10 mx-auto text-slate-300 mb-2"></i>
                            <p class="text-sm font-semibold text-slate-600">Tidak ada riwayat aktivitas yang ditemukan.</p>
                            <p class="text-xs text-slate-400 mt-1">Coba sesuaikan filter pencarian di atas.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
            {{ $logs->links() }}
        </div>
        @endif
    </div>

    <!-- MODAL POPUP DETAIL RIWAYAT PERUBAHAN & DIFF DATA -->
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
                 class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all w-full max-w-3xl border border-slate-100">

                <!-- Header Modal -->
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-blue-100 text-[#0B63B5] flex items-center justify-center">
                            <i data-lucide="file-diff" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-slate-900" id="modal-detail-title">
                                Detail Jejak Audit & Perubahan Data
                            </h3>
                            <p class="text-[11px] text-slate-500" x-text="'Peristiwa ID #' + (selectedLog ? selectedLog.id : '')"></p>
                        </div>
                    </div>
                    <button type="button" @click="detailModalOpen = false" class="p-1.5 rounded-xl hover:bg-slate-200 text-slate-400 hover:text-slate-700 transition">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Body Modal -->
                <div class="p-6 space-y-5" x-if="selectedLog">
                    <!-- Meta Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-4 bg-slate-50/80 rounded-2xl border border-slate-200 text-xs">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Tindakan / Aksi:</span>
                            <span class="font-bold text-slate-900 font-mono block mt-0.5" x-text="selectedLog ? selectedLog.action : ''"></span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Entitas Terkait:</span>
                            <span class="font-bold text-slate-900 block mt-0.5" x-text="(selectedLog ? selectedLog.entity : '') + ' #' + (selectedLog && selectedLog.entity_id ? selectedLog.entity_id.substring(0, 8) : '-')"></span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Aktor:</span>
                            <span class="font-bold text-slate-900 block mt-0.5" x-text="selectedLog && selectedLog.actor ? selectedLog.actor.name : 'Sistem'"></span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Alamat IP:</span>
                            <span class="font-mono text-slate-700 block mt-0.5" x-text="selectedLog ? selectedLog.ip : '-'"></span>
                        </div>
                    </div>

                    <!-- Side by Side Diff: Before vs After -->
                    <div>
                        <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                            <i data-lucide="git-commit" class="w-4 h-4 text-[#0B63B5]"></i>
                            <span>Perbandingan Data (Sebelum vs Sesudah)</span>
                        </h4>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- SEBELUM (BEFORE) -->
                            <div class="p-4 rounded-2xl bg-red-50/50 border border-red-200">
                                <div class="flex items-center justify-between mb-2 pb-1 border-b border-red-200/60">
                                    <span class="text-xs font-bold text-red-800 flex items-center gap-1.5">
                                        <i data-lucide="minus-circle" class="w-3.5 h-3.5 text-red-600"></i>
                                        <span>Data Sebelum (Before)</span>
                                    </span>
                                </div>
                                <template x-if="selectedLog && selectedLog.before">
                                    <pre class="text-[11px] font-mono text-red-900 whitespace-pre-wrap overflow-x-auto leading-relaxed bg-white p-3 rounded-xl border border-red-200/60" x-text="JSON.stringify(selectedLog.before, null, 2)"></pre>
                                </template>
                                <template x-if="!selectedLog || !selectedLog.before">
                                    <p class="text-xs text-slate-400 italic p-3">Tidak ada data sebelumnya (Entitas baru dibuat).</p>
                                </template>
                            </div>

                            <!-- SESUDAH (AFTER) -->
                            <div class="p-4 rounded-2xl bg-emerald-50/50 border border-emerald-200">
                                <div class="flex items-center justify-between mb-2 pb-1 border-b border-emerald-200/60">
                                    <span class="text-xs font-bold text-emerald-800 flex items-center gap-1.5">
                                        <i data-lucide="plus-circle" class="w-3.5 h-3.5 text-emerald-600"></i>
                                        <span>Data Sesudah (After)</span>
                                    </span>
                                </div>
                                <template x-if="selectedLog && selectedLog.after">
                                    <pre class="text-[11px] font-mono text-emerald-900 whitespace-pre-wrap overflow-x-auto leading-relaxed bg-white p-3 rounded-xl border border-emerald-200/60" x-text="JSON.stringify(selectedLog.after, null, 2)"></pre>
                                </template>
                                <template x-if="!selectedLog || !selectedLog.after">
                                    <p class="text-xs text-slate-400 italic p-3">Tidak ada data sesudah (Entitas dihapus).</p>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Modal -->
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end">
                    <button type="button" @click="detailModalOpen = false"
                            class="px-5 py-2.5 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold text-xs transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
