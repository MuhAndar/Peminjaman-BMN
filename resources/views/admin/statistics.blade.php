@extends('layouts.app')

@section('title', 'Statistik & Analitik Riwayat Peminjaman — Admin BMN')

@section('content')
<div class="space-y-6">
    <!-- Top Bar Navigation -->
    <div class="flex items-center justify-between gap-3">
        <a href="{{ route('dashboard') }}"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs font-bold text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800 transition shadow-2xs">
            <i data-lucide="arrow-left" class="w-4 h-4 text-slate-500"></i>
            <span>Kembali ke Beranda</span>
        </a>

        <!-- Filter Tahun Interaktif -->
        <form method="GET" action="{{ route('admin.statistics') }}" class="flex items-center gap-2">
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 hidden sm:inline">Periode Analitik:</span>
            <div class="relative">
                <select name="year" onchange="this.form.submit()"
                        class="pl-3 pr-8 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-800 dark:text-slate-200 shadow-2xs outline-none focus:border-blue-500 cursor-pointer">
                    @foreach($allYears as $yr)
                        <option value="{{ $yr }}" {{ $selectedYear == $yr ? 'selected' : '' }}>Tahun {{ $yr }}</option>
                    @endforeach
                    <option value="0" {{ $selectedYear == 0 ? 'selected' : '' }}>Seluruh Tahun (Akumulasi)</option>
                </select>
                <div class="absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>
                </div>
            </div>
        </form>
    </div>

    <!-- Header Banner Section -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-blue-600 transition">Beranda</a>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-slate-600 dark:text-slate-300">Administrasi Sistem</span>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-[#0B63B5] dark:text-blue-400">Statistik & Analitik BMN</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-2xl bg-blue-100 dark:bg-blue-950/80 text-[#0B63B5] dark:text-blue-400 flex items-center justify-center shrink-0">
                    <i data-lucide="bar-chart-3" class="w-5 h-5"></i>
                </div>
                <span>Statistik & Analitik Riwayat Peminjaman BMN</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">
                Visualisasi tren sirkulasi barang, utilitas aset per kategori, rasio kepatuhan SLA pengembalian, dan sebaran status SOP.
            </p>
        </div>

        <div class="flex items-center gap-2 self-start md:self-auto">
            <span class="px-3 py-1.5 rounded-xl bg-blue-50 dark:bg-blue-950/60 border border-blue-200 dark:border-blue-900/60 text-[#0B63B5] dark:text-blue-400 text-xs font-bold flex items-center gap-1.5">
                <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                <span>{{ $selectedYear > 0 ? "Periode Tahun $selectedYear" : "Semua Periode" }}</span>
            </span>
        </div>
    </div>

    <!-- 1. KPI SUMMARY CARDS (5 Metric Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        
        <!-- Metric 1: Total Pengajuan -->
        <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-slate-400">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Pengajuan</span>
                <div class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-950/70 text-[#0B63B5] dark:text-blue-400 flex items-center justify-center">
                    <i data-lucide="files" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-3xl font-black text-slate-900 dark:text-white tabular-nums tracking-tight">
                    {{ number_format($totalLoans) }}
                </div>
                <div class="flex items-center gap-1.5 mt-1 text-[11px] text-slate-500 dark:text-slate-400 font-medium">
                    <span class="font-bold text-[#0B63B5]">{{ $loanTypeBreakdown['SHORT_TERM'] }}</span> Sementara &bull;
                    <span class="font-bold text-purple-600">{{ $loanTypeBreakdown['ANNUAL'] }}</span> Tahunan
                </div>
            </div>
        </div>

        <!-- Metric 2: Peminjaman Aktif -->
        <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-slate-400">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Sedang Digunakan</span>
                <div class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-950/70 text-[#0B63B5] dark:text-blue-400 flex items-center justify-center">
                    <i data-lucide="play-circle" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-3xl font-black text-[#0B63B5] dark:text-blue-400 tabular-nums tracking-tight">
                    {{ number_format($activeLoansCount) }}
                </div>
                <div class="flex items-center gap-1.5 mt-1 text-[11px] text-emerald-600 dark:text-emerald-400 font-bold">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Aktif Terpinjam Saat Ini</span>
                </div>
            </div>
        </div>

        <!-- Metric 3: Selesai Dikembalikan -->
        <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-slate-400">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Selesai Kembali</span>
                <div class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/70 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                    <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-3xl font-black text-emerald-600 dark:text-emerald-400 tabular-nums tracking-tight">
                    {{ number_format($completedLoans) }}
                </div>
                <div class="flex items-center gap-1 mt-1 text-[11px] text-slate-500 dark:text-slate-400 font-medium">
                    <span>BAST Pengembalian Terbit</span>
                </div>
            </div>
        </div>

        <!-- Metric 4: Rasio Tepat Waktu -->
        <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-slate-400">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Kepatuhan Waktu</span>
                <div class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/70 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                    <i data-lucide="clock" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-3xl font-black text-slate-900 dark:text-white tabular-nums tracking-tight flex items-baseline gap-1">
                    <span>{{ $onTimeRate }}%</span>
                </div>
                <div class="flex items-center gap-1 mt-1 text-[11px] text-emerald-600 dark:text-emerald-400 font-bold">
                    <span>Tepat Waktu Kembali</span>
                </div>
            </div>
        </div>

        <!-- Metric 5: Keterlambatan (Overdue) -->
        <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-slate-400">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Terlambat (Overdue)</span>
                <div class="w-8 h-8 rounded-xl {{ $overdueLoansCount > 0 ? 'bg-red-50 dark:bg-red-950/70 text-red-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-400' }} flex items-center justify-center">
                    <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-3xl font-black {{ $overdueLoansCount > 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-800 dark:text-slate-200' }} tabular-nums tracking-tight">
                    {{ number_format($overdueLoansCount) }}
                </div>
                <div class="flex items-center gap-1 mt-1 text-[11px] {{ $overdueLoansCount > 0 ? 'text-red-600 font-bold' : 'text-slate-400 font-medium' }}">
                    <span>{{ $overdueLoansCount > 0 ? 'Perlu Ditindaklanjuti' : 'Nol Keterlambatan ✓' }}</span>
                </div>
            </div>
        </div>

    </div>

    <!-- 2. PRIMARY CHARTS ROW (Tren Bulanan & Sebaran Status) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- CHART 1: Tren Peminjaman & Pengembalian Bulanan (Line & Bar) -->
        <div class="lg:col-span-8 bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-3 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="trending-up" class="w-5 h-5 text-[#0B63B5] dark:text-blue-400"></i>
                        <span>Tren Sirkulasi Peminjaman & Pengembalian Bulanan</span>
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">Frekuensi pengajuan baru vs penyelesaian pengembalian per bulan</p>
                </div>
                <div class="flex items-center gap-3 text-xs font-bold">
                    <div class="flex items-center gap-1.5 text-blue-600 dark:text-blue-400">
                        <span class="w-3 h-3 rounded-full bg-[#0B63B5]"></span>
                        <span>Pengajuan</span>
                    </div>
                    <div class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400">
                        <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                        <span>Pengembalian</span>
                    </div>
                </div>
            </div>

            <!-- Canvas Container -->
            <div class="relative h-72 sm:h-80 w-full">
                <canvas id="monthlyTrendChart"></canvas>
            </div>
        </div>

        <!-- CHART 2: Sebaran Status SOP Peminjaman (Doughnut Chart) -->
        <div class="lg:col-span-4 bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-4 flex flex-col justify-between">
            <div class="pb-3 border-b border-slate-100 dark:border-slate-800">
                <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="pie-chart" class="w-5 h-5 text-[#0B63B5] dark:text-blue-400"></i>
                    <span>Sebaran Status Peminjaman</span>
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">Komposisi status siklus peminjaman BMN</p>
            </div>

            <!-- Canvas Container -->
            <div class="relative h-60 w-full flex items-center justify-center my-auto">
                <canvas id="statusDoughnutChart"></canvas>
            </div>

            <!-- Custom Legend Details -->
            <div class="grid grid-cols-2 gap-2 text-xs pt-3 border-t border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shrink-0"></span>
                    <span class="text-slate-600 dark:text-slate-300 truncate">Selesai: <strong>{{ $statusDistribution['RETURNED'] }}</strong></span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#0B63B5] shrink-0"></span>
                    <span class="text-slate-600 dark:text-slate-300 truncate">Aktif: <strong>{{ $statusDistribution['ACTIVE'] }}</strong></span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 shrink-0"></span>
                    <span class="text-slate-600 dark:text-slate-300 truncate">Proses SOP: <strong>{{ $statusDistribution['IN_PROCESS'] }}</strong></span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-red-500 shrink-0"></span>
                    <span class="text-slate-600 dark:text-slate-300 truncate">Ditolak: <strong>{{ $statusDistribution['REJECTED'] }}</strong></span>
                </div>
            </div>
        </div>

    </div>

    <!-- 3. SECONDARY CHARTS & CATEGORY ANALYTICS -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- CHART 3: Kategori BMN Paling Sering Dipinjam (Horizontal Bar) -->
        <div class="lg:col-span-7 bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="layers" class="w-5 h-5 text-[#0B63B5] dark:text-blue-400"></i>
                        <span>Kategori BMN Paling Sering Dipinjam (Top Utilitas)</span>
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">Jumlah unit barang yang dipinjam berdasarkan kategori aset BMN</p>
                </div>
            </div>

            <!-- Canvas Container -->
            <div class="relative h-64 sm:h-72 w-full">
                <canvas id="categoryBarChart"></canvas>
            </div>
        </div>

        <!-- CHART 4: Jenis Peminjaman & Rekapitulasi (Pie / Polar) -->
        <div class="lg:col-span-5 bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-4 flex flex-col justify-between">
            <div class="pb-3 border-b border-slate-100 dark:border-slate-800">
                <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="calendar-range" class="w-5 h-5 text-[#0B63B5] dark:text-blue-400"></i>
                    <span>Proporsi Jenis Peminjaman</span>
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">Perbandingan Peminjaman Sementara vs Penugasan Tahunan</p>
            </div>

            <!-- Canvas Container -->
            <div class="relative h-56 w-full flex items-center justify-center my-auto">
                <canvas id="typePieChart"></canvas>
            </div>

            <!-- Metrik Rata-rata Durasi -->
            <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700 flex items-center justify-between text-xs">
                <div>
                    <span class="text-slate-400 block font-medium">Rata-rata Durasi Pinjam:</span>
                    <span class="font-extrabold text-slate-900 dark:text-white text-sm">{{ $avgDurationDays }} Hari / Pengajuan</span>
                </div>
                <div class="text-right">
                    <span class="text-slate-400 block font-medium">Total Akumulasi Barang:</span>
                    <span class="font-extrabold text-[#0B63B5] dark:text-blue-400 text-sm">{{ $totalItemsCount }} Unit BMN</span>
                </div>
            </div>
        </div>

    </div>

    <!-- 4. TABEL REKAPITULASI UTILITAS KATEGORI BMN -->
    <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
            <div>
                <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="table" class="w-5 h-5 text-[#0B63B5] dark:text-blue-400"></i>
                    <span>Tabel Ringkasan Efisiensi & Utilitas Aset BMN</span>
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">Rincian intensitas peminjaman dan rata-rata durasi pemakaian per kelompok barang</p>
            </div>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                        <th class="py-3 px-4">No</th>
                        <th class="py-3 px-4">Kategori Barang BMN</th>
                        <th class="py-3 px-4 text-center">Total Unit Dipinjam</th>
                        <th class="py-3 px-4 text-center">Rata-rata Durasi</th>
                        <th class="py-3 px-4 text-center">Tingkat Permintaan</th>
                        <th class="py-3 px-4 text-right">Status Ketersediaan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                    @forelse($categorySummary as $idx => $cs)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                            <td class="py-3.5 px-4 text-slate-400 font-bold">{{ $idx + 1 }}</td>
                            <td class="py-3.5 px-4 font-bold text-slate-800 dark:text-slate-200">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full bg-[#0B63B5]"></div>
                                    <span>{{ $cs['label'] }}</span>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-center font-extrabold text-[#0B63B5] dark:text-blue-400 tabular-nums">
                                {{ $cs['total_borrowed'] }} Unit
                            </td>
                            <td class="py-3.5 px-4 text-center text-slate-600 dark:text-slate-300 tabular-nums">
                                {{ $cs['avg_duration'] }} Hari
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $cs['total_borrowed'] > 5 ? 'bg-blue-100 text-[#0B63B5] dark:bg-blue-950 dark:text-blue-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300' }}">
                                    {{ $cs['total_borrowed'] > 5 ? 'Tinggi (Sering Dipinjam)' : 'Normal' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
                                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                    <span>Terkendali di Katalog</span>
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400">
                                <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                                <span>Belum ada data riwayat peminjaman pada periode ini.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Chart.js via Fast CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Shared palette tokens
    const isDark = document.documentElement.classList.contains('dark');
    const textColor = isDark ? '#94A3B8' : '#64748B';
    const gridColor = isDark ? 'rgba(51, 65, 85, 0.4)' : 'rgba(226, 232, 240, 0.7)';

    // 1. MONTHLY TREND CHART (LINE & BAR)
    const trendCtx = document.getElementById('monthlyTrendChart');
    if (trendCtx) {
        new Chart(trendCtx, {
            type: 'bar',
            data: {
                labels: @json($monthLabels),
                datasets: [
                    {
                        type: 'line',
                        label: 'Pengajuan Masuk',
                        data: @json($monthlySubmissions),
                        borderColor: '#0B63B5',
                        backgroundColor: 'rgba(11, 99, 181, 0.1)',
                        borderWidth: 3,
                        tension: 0.35,
                        pointBackgroundColor: '#0B63B5',
                        pointBorderColor: '#fff',
                        pointRadius: 4,
                        fill: true,
                        yAxisID: 'y'
                    },
                    {
                        type: 'bar',
                        label: 'Pengembalian Selesai',
                        data: @json($monthlyCompletions),
                        backgroundColor: 'rgba(16, 185, 129, 0.75)',
                        hoverBackgroundColor: '#10B981',
                        borderRadius: 6,
                        yAxisID: 'y'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: isDark ? '#0F172A' : '#1E293B',
                        titleColor: '#fff',
                        bodyColor: '#E2E8F0',
                        padding: 10,
                        cornerRadius: 10
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: textColor, font: { weight: '600', size: 11 } }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { color: textColor, precision: 0, font: { weight: '600', size: 11 } },
                        grid: { color: gridColor }
                    }
                }
            }
        });
    }

    // 2. STATUS DOUGHNUT CHART
    const statusCtx = document.getElementById('statusDoughnutChart');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Selesai Kembali', 'Sedang Aktif', 'Dalam Proses SOP', 'Ditolak'],
                datasets: [{
                    data: [
                        {{ $statusDistribution['RETURNED'] }},
                        {{ $statusDistribution['ACTIVE'] }},
                        {{ $statusDistribution['IN_PROCESS'] }},
                        {{ $statusDistribution['REJECTED'] }}
                    ],
                    backgroundColor: [
                        '#10B981', // emerald
                        '#0B63B5', // brand blue
                        '#F59E0B', // amber
                        '#EF4444'  // red
                    ],
                    borderWidth: 0,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: isDark ? '#0F172A' : '#1E293B',
                        padding: 10,
                        cornerRadius: 10
                    }
                }
            }
        });
    }

    // 3. TOP CATEGORIES HORIZONTAL BAR CHART
    const categoryCtx = document.getElementById('categoryBarChart');
    if (categoryCtx) {
        const catLabels = @json(array_keys($topCategories));
        const catValues = @json(array_values($topCategories));

        new Chart(categoryCtx, {
            type: 'bar',
            data: {
                labels: catLabels.length ? catLabels : ['Peralatan TIK & Multimedia', 'Laptop / Komputer Jinjing', 'Mobil Dinas'],
                datasets: [{
                    label: 'Unit Dipinjam',
                    data: catValues.length ? catValues : [0, 0, 0],
                    backgroundColor: [
                        '#0B63B5',
                        '#12A8E0',
                        '#3B82F6',
                        '#6366F1',
                        '#8B5CF6',
                        '#EC4899'
                    ],
                    borderRadius: 8
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: isDark ? '#0F172A' : '#1E293B',
                        padding: 10,
                        cornerRadius: 10
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { color: textColor, precision: 0, font: { weight: '600', size: 11 } },
                        grid: { color: gridColor }
                    },
                    y: {
                        grid: { display: false },
                        ticks: { color: textColor, font: { weight: '600', size: 11 } }
                    }
                }
            }
        });
    }

    // 4. LOAN TYPE PIE CHART
    const typeCtx = document.getElementById('typePieChart');
    if (typeCtx) {
        new Chart(typeCtx, {
            type: 'pie',
            data: {
                labels: ['Peminjaman Sementara (SHORT_TERM)', 'Penugasan Tahunan (ANNUAL)'],
                datasets: [{
                    data: [
                        {{ $loanTypeBreakdown['SHORT_TERM'] }},
                        {{ $loanTypeBreakdown['ANNUAL'] }}
                    ],
                    backgroundColor: [
                        '#0B63B5',
                        '#8B5CF6'
                    ],
                    borderWidth: 0,
                    hoverOffset: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: textColor,
                            font: { weight: '600', size: 11 },
                            padding: 12
                        }
                    },
                    tooltip: {
                        backgroundColor: isDark ? '#0F172A' : '#1E293B',
                        padding: 10,
                        cornerRadius: 10
                    }
                }
            }
        });
    }
});
</script>
@endsection
