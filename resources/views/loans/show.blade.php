@extends('layouts.app')

@section('title', 'Detail Pengajuan BMN #' . substr($loan->id, 0, 8))

@section('content')
@php
    $meta = $loan->status_meta;
    $user = auth()->user();
    $isPengelola = $user->isPengelola();
    $isKetuaTim = $user->isKetuaTim();
    $isOwner = ($user->employee_id === $loan->borrower_id);
@endphp

<div class="space-y-6" x-data="{
    sigModalOpen: false,
    sigAs: '{{ $isOwner ? "borrower" : "officer" }}',
    sigTab: '{{ auth()->user()->signature_path ? "profile" : "pad" }}',
    photoModalOpen: false,
    photoStage: 'BAST_PEMINJAMAN',
    lightboxOpen: false,
    activePhotoUrl: '',
    activePhotoTitle: '',
    activePhotoMeta: '',
    // Canvas Pad State with Automatic Stroke Smoothing
    canvas: null,
    ctx: null,
    isDrawing: false,
    hasDrawn: false,
    smoothingEnabled: true,
    justCorrected: false,
    currentStroke: [],
    strokes: [],
    initCanvas() {
        this.$nextTick(() => {
            this.canvas = document.getElementById('loan-sig-pad');
            if (!this.canvas) return;
            this.ctx = this.canvas.getContext('2d');
            const rect = this.canvas.getBoundingClientRect();
            this.canvas.width = rect.width * 2;
            this.canvas.height = rect.height * 2;
            this.ctx.scale(2, 2);
            this.ctx.strokeStyle = '#0F172A';
            this.ctx.fillStyle = '#0F172A';
            this.ctx.lineWidth = 2.5;
            this.ctx.lineCap = 'round';
            this.ctx.lineJoin = 'round';
            this.redrawCanvas();
        });
    },
    getPos(e) {
        const rect = this.canvas.getBoundingClientRect();
        if (e.touches && e.touches.length > 0) {
            return { x: e.touches[0].clientX - rect.left, y: e.touches[0].clientY - rect.top };
        }
        return { x: e.clientX - rect.left, y: e.clientY - rect.top };
    },
    filterPoints(points, minDist = 2) {
        if (!points || points.length <= 2) return points;
        const filtered = [points[0]];
        let last = points[0];
        for (let i = 1; i < points.length; i++) {
            const p = points[i];
            const dist = Math.hypot(p.x - last.x, p.y - last.y);
            if (dist >= minDist) {
                filtered.push(p);
                last = p;
            }
        }
        if (filtered[filtered.length - 1] !== points[points.length - 1]) {
            filtered.push(points[points.length - 1]);
        }
        return filtered;
    },
    chaikinSmooth(points, iterations = 2) {
        if (!points || points.length <= 2) return points;
        let smoothed = points;
        for (let it = 0; it < iterations; it++) {
            const next = [smoothed[0]];
            for (let i = 0; i < smoothed.length - 1; i++) {
                const p0 = smoothed[i];
                const p1 = smoothed[i + 1];
                next.push({
                    x: 0.75 * p0.x + 0.25 * p1.x,
                    y: 0.75 * p0.y + 0.25 * p1.y
                });
                next.push({
                    x: 0.25 * p0.x + 0.75 * p1.x,
                    y: 0.25 * p0.y + 0.75 * p1.y
                });
            }
            next.push(smoothed[smoothed.length - 1]);
            smoothed = next;
        }
        return smoothed;
    },
    redrawCanvas() {
        if (!this.ctx || !this.canvas) return;
        const rect = this.canvas.getBoundingClientRect();
        this.ctx.clearRect(0, 0, rect.width, rect.height);
        this.ctx.strokeStyle = '#0F172A';
        this.ctx.fillStyle = '#0F172A';
        this.ctx.lineWidth = 2.5;
        this.ctx.lineCap = 'round';
        this.ctx.lineJoin = 'round';

        for (const stroke of this.strokes) {
            if (!stroke || stroke.length === 0) continue;
            if (stroke.length === 1) {
                this.ctx.beginPath();
                this.ctx.arc(stroke[0].x, stroke[0].y, 1.25, 0, Math.PI * 2);
                this.ctx.fill();
                continue;
            }
            if (stroke.length === 2) {
                this.ctx.beginPath();
                this.ctx.moveTo(stroke[0].x, stroke[0].y);
                this.ctx.lineTo(stroke[1].x, stroke[1].y);
                this.ctx.stroke();
                continue;
            }
            this.ctx.beginPath();
            this.ctx.moveTo(stroke[0].x, stroke[0].y);
            for (let i = 1; i < stroke.length - 1; i++) {
                const xc = (stroke[i].x + stroke[i + 1].x) / 2;
                const yc = (stroke[i].y + stroke[i + 1].y) / 2;
                this.ctx.quadraticCurveTo(stroke[i].x, stroke[i].y, xc, yc);
            }
            this.ctx.lineTo(stroke[stroke.length - 1].x, stroke[stroke.length - 1].y);
            this.ctx.stroke();
        }
    },
    startDraw(e) {
        e.preventDefault();
        this.isDrawing = true;
        const pos = this.getPos(e);
        this.currentStroke = [pos];
        this.ctx.beginPath();
        this.ctx.moveTo(pos.x, pos.y);
    },
    draw(e) {
        if (!this.isDrawing) return;
        e.preventDefault();
        const pos = this.getPos(e);
        this.currentStroke.push(pos);

        if (this.currentStroke.length >= 3) {
            const p1 = this.currentStroke[this.currentStroke.length - 2];
            const p2 = this.currentStroke[this.currentStroke.length - 1];
            const xc = (p1.x + p2.x) / 2;
            const yc = (p1.y + p2.y) / 2;
            this.ctx.quadraticCurveTo(p1.x, p1.y, xc, yc);
            this.ctx.stroke();
        } else {
            this.ctx.lineTo(pos.x, pos.y);
            this.ctx.stroke();
        }
        this.hasDrawn = true;
    },
    endDraw(e) {
        if (!this.isDrawing) return;
        this.isDrawing = false;
        if (this.currentStroke && this.currentStroke.length > 0) {
            let finalStroke = this.currentStroke;
            if (this.smoothingEnabled && this.currentStroke.length > 2) {
                const filtered = this.filterPoints(this.currentStroke, 2);
                finalStroke = this.chaikinSmooth(filtered, 2);
                this.justCorrected = true;
                setTimeout(() => { this.justCorrected = false; }, 900);
            }
            this.strokes.push(finalStroke);
            this.hasDrawn = true;
            this.redrawCanvas();
            this.currentStroke = [];
        }
    },
    clearCanvas() {
        if (!this.ctx || !this.canvas) return;
        const rect = this.canvas.getBoundingClientRect();
        this.ctx.clearRect(0, 0, rect.width, rect.height);
        this.hasDrawn = false;
        this.strokes = [];
        this.currentStroke = [];
    },
    undo() {
        if (!this.strokes || this.strokes.length === 0) {
            this.clearCanvas();
            return;
        }
        this.strokes.pop();
        if (this.strokes.length === 0) {
            this.hasDrawn = false;
        }
        this.redrawCanvas();
    },
    openSigModal(asRole) {
        this.sigAs = asRole;
        this.sigModalOpen = true;
        this.$nextTick(() => {
            this.initCanvas();
            if (window.lucide) lucide.createIcons();
        });
    },
    submitSig() {
        if (this.sigTab === 'pad') {
            if (!this.hasDrawn) {
                alert('Silakan goreskan tanda tangan pada papan gambar kanvas terlebih dahulu.');
                return;
            }
            document.getElementById('loan_sig_data_input').value = this.canvas.toDataURL('image/png');
        }
        document.getElementById('loan-sig-form').submit();
    },
    openPhotoModal(stage) {
        this.photoStage = stage;
        this.photoModalOpen = true;
        this.previewPhotos = [];
        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
    },
    openLightbox(url, title, meta) {
        this.activePhotoUrl = url;
        this.activePhotoTitle = title;
        this.activePhotoMeta = meta;
        this.lightboxOpen = true;
    },
    previewPhotos: [],
    handlePhotoSelect(e) {
        const files = Array.from(e.target.files);
        this.previewPhotos = [];
        files.forEach(f => {
            const url = URL.createObjectURL(f);
            this.previewPhotos.push({ name: f.name, size: (f.size / 1024).toFixed(0) + ' KB', url: url });
        });
    }
}">
    <!-- Breadcrumb & Top Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-2 text-xs text-slate-500 font-semibold">
            <a href="{{ route('dashboard') }}" class="hover:text-slate-900">Beranda</a>
            <span>/</span>
            <a href="{{ route('loans.index') }}" class="hover:text-slate-900">Pengajuan</a>
            <span>/</span>
            <span class="text-slate-800 font-bold">Detail #{{ substr($loan->id, 0, 8) }}</span>
        </div>

        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border {{ $meta['bg'] }}">
                <i data-lucide="{{ $meta['icon'] }}" class="w-3.5 h-3.5"></i>
                <span>{{ $meta['label'] }}</span>
            </span>
        </div>
    </div>

    <!-- Main Grid: Info on Left (8 cols), Timeline on Right (4 cols) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- LEFT COLUMN: Details, Items, Actions -->
        <div class="lg:col-span-8 space-y-6">
            <!-- Summary Card -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                    <div>
                        <h2 class="text-lg font-black text-slate-900">Peminjaman Barang Inventaris Kantor</h2>
                        <p class="text-xs text-slate-500">ID Pengajuan: <span class="font-mono">{{ $loan->id }}</span></p>
                    </div>
                    <div class="text-xs text-slate-500 font-medium">
                        Diajukan: <strong class="text-slate-800 tabular-nums">{{ $loan->created_at->format('d/m/Y H:i') }} WITA</strong>
                    </div>
                </div>

                <!-- Borrower Snapshot Details -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <span class="text-slate-400 font-medium block mb-0.5">Nama Peminjam (Pihak Kedua):</span>
                        <span class="font-bold text-slate-900 text-sm">{{ $loan->snap_full_name }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 font-medium block mb-0.5">NIP:</span>
                        <span class="font-semibold text-slate-800 tabular-nums">{{ \App\Helpers\FormatHelper::formatNip($loan->snap_nip) }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 font-medium block mb-0.5">Pangkat / Golongan:</span>
                        <span class="font-medium text-slate-800">{{ $loan->snap_rank_grade ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 font-medium block mb-0.5">Jabatan:</span>
                        <span class="font-medium text-slate-800">{{ $loan->snap_position ?? '-' }}</span>
                    </div>
                    <div class="sm:col-span-2">
                        <span class="text-slate-400 font-medium block mb-0.5">Alamat Rumah Tercetak:</span>
                        <span class="font-medium text-slate-800">{{ $loan->snap_home_address ?? '-' }}</span>
                    </div>
                </div>

                <!-- Period Details -->
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                    <div>
                        <span class="text-slate-400 font-medium block">Periode Peminjaman:</span>
                        <span class="font-bold text-slate-900 tabular-nums">
                            {{ \App\Helpers\FormatHelper::formatDateIndo($loan->start_date) }} s.d. {{ \App\Helpers\FormatHelper::formatDateIndo($loan->end_date) }}
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-400 font-medium block">Jenis Penugasan:</span>
                        <span class="font-bold text-[#0B63B5]">
                            {{ $loan->loan_type === 'ANNUAL' ? 'Penugasan Tahunan (ANNUAL)' : 'Sementara (SHORT_TERM)' }}
                        </span>
                    </div>
                </div>

                @if($loan->review_note)
                    <div class="p-3.5 rounded-2xl bg-blue-50/70 border border-blue-200 text-xs">
                        <strong class="text-[#0B63B5] block mb-0.5">Catatan Telaah Pengelola BMN:</strong>
                        <p class="text-slate-700 leading-relaxed">{{ $loan->review_note }}</p>
                    </div>
                @endif

                @if($loan->decision_note)
                    <div class="p-3.5 rounded-2xl bg-emerald-50/70 border border-emerald-200 text-xs">
                        <strong class="text-emerald-700 block mb-0.5">Catatan Keputusan Ketua Tim:</strong>
                        <p class="text-slate-700 leading-relaxed">{{ $loan->decision_note }}</p>
                    </div>
                @endif
            </div>

            <!-- Items Table Card -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                <h3 class="text-base font-bold text-slate-900">Daftar Barang BMN yang Dipinjam</h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase">
                                <th class="py-3 px-3">No</th>
                                <th class="py-3 px-3">Nama Barang</th>
                                <th class="py-3 px-3">Merk / Tipe</th>
                                <th class="py-3 px-3">NUP</th>
                                <th class="py-3 px-3">Tahun & Nilai</th>
                                <th class="py-3 px-3">Keperluan</th>
                                <th class="py-3 px-3 text-center">Kondisi Keluar / Masuk</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($loan->items as $idx => $item)
                            <tr class="hover:bg-slate-50/50">
                                <td class="py-3 px-3 font-bold text-slate-400 tabular-nums">{{ $idx + 1 }}</td>
                                <td class="py-3 px-3 font-bold text-slate-900">{{ $item->snap_item_name }}</td>
                                <td class="py-3 px-3 text-slate-700">{{ $item->snap_brand_model ?? '-' }}</td>
                                <td class="py-3 px-3 font-semibold text-slate-800 tabular-nums">{{ $item->snap_nup ?? '-' }}</td>
                                <td class="py-3 px-3 tabular-nums text-slate-700">{{ $item->snap_year }} &bull; {{ $item->formatted_snap_value }}</td>
                                <td class="py-3 px-3 italic text-slate-600">{{ $item->purpose }}</td>
                                <td class="py-3 px-3 text-center">
                                    <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 font-semibold text-[10px]">
                                        {{ $item->condition_out ?? 'Baik' }}
                                    </span>
                                    @if($item->condition_in)
                                        <div class="mt-1 text-[10px] font-bold text-slate-600">
                                            Kembali: <span class="{{ in_array($item->condition_in, ['Rusak', 'Hilang']) ? 'text-red-600' : 'text-emerald-700' }}">{{ $item->condition_in }}</span>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Official Documents Card -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Dokumen Resmi BMN (Template Baku PDF)</h3>
                        <p class="text-xs text-slate-500">Tiga dokumen resmi sesuai format sah Balai Besar Komdigi Makassar</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- 1. Surat Peminjaman -->
                    @php $docSurat = $loan->documents()->where('doc_type', 'SURAT_PEMINJAMAN')->where('status', 'ISSUED')->first(); @endphp
                    <div class="p-4 rounded-2xl border {{ $docSurat ? 'border-blue-200 bg-blue-50/40' : 'border-slate-200 bg-slate-50/60' }} flex flex-col justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Dokumen 1</span>
                            <h4 class="font-bold text-xs text-slate-900">Surat Peminjaman</h4>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                {{ $docSurat ? $docSurat->doc_number : 'Terbit saat disetujui Ketua Tim' }}
                            </p>
                        </div>
                        <div class="mt-4">
                            @if($docSurat)
                                <a href="{{ route('document.pdf', [$loan->id, 'SURAT_PEMINJAMAN']) }}" target="_blank"
                                   class="w-full py-2 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-sm">
                                    <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                                    <span>Cetak PDF</span>
                                </a>
                            @else
                                <span class="text-xs text-slate-400 font-medium block text-center py-2 bg-slate-100 rounded-xl">Belum Diterbitkan</span>
                            @endif
                        </div>
                    </div>

                    <!-- 2. BAST Peminjaman -->
                    @php $docBastPjm = $loan->documents()->where('doc_type', 'BAST_PEMINJAMAN')->where('status', 'ISSUED')->first(); @endphp
                    <div class="p-4 rounded-2xl border {{ $docBastPjm ? 'border-cyan-200 bg-cyan-50/40' : 'border-slate-200 bg-slate-50/60' }} flex flex-col justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Dokumen 2</span>
                            <h4 class="font-bold text-xs text-slate-900">BAST Peminjaman</h4>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                {{ $docBastPjm ? $docBastPjm->doc_number : 'Diterbitkan oleh Pengelola BMN' }}
                            </p>
                        </div>
                        <div class="mt-4">
                            @if($docBastPjm)
                                <a href="{{ route('document.pdf', [$loan->id, 'BAST_PEMINJAMAN']) }}" target="_blank"
                                   class="w-full py-2 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-sm">
                                    <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                                    <span>Cetak PDF</span>
                                </a>
                            @else
                                <span class="text-xs text-slate-400 font-medium block text-center py-2 bg-slate-100 rounded-xl">Belum Diterbitkan</span>
                            @endif
                        </div>
                    </div>

                    <!-- 3. BAST Pengembalian -->
                    @php $docBastKbl = $loan->documents()->where('doc_type', 'BAST_PENGEMBALIAN')->where('status', 'ISSUED')->first(); @endphp
                    <div class="p-4 rounded-2xl border {{ $docBastKbl ? 'border-emerald-200 bg-emerald-50/40' : 'border-slate-200 bg-slate-50/60' }} flex flex-col justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Dokumen 3</span>
                            <h4 class="font-bold text-xs text-slate-900">BAST Pengembalian</h4>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                {{ $docBastKbl ? $docBastKbl->doc_number : 'Terbit saat fisik BMN diperiksa' }}
                            </p>
                        </div>
                        <div class="mt-4">
                            @if($docBastKbl)
                                <a href="{{ route('document.pdf', [$loan->id, 'BAST_PENGEMBALIAN']) }}" target="_blank"
                                   class="w-full py-2 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-sm">
                                    <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                                    <span>Cetak PDF</span>
                                </a>
                            @else
                                <span class="text-xs text-slate-400 font-medium block text-center py-2 bg-slate-100 rounded-xl">Belum Diterbitkan</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. STATUS & BUBURKAN TANDA TANGAN DIGITAL DOKUMEN BAST -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-blue-50 text-[#0B63B5] flex items-center justify-center shrink-0">
                            <i data-lucide="signature" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h3 class="font-extrabold text-sm text-slate-900">Spesimen Tanda Tangan Digital BAST</h3>
                            <p class="text-[11px] text-slate-500">Tanda tangan digital yang dibubuhkan otomatis dicetak pada lembar PDF resmi.</p>
                        </div>
                    </div>

                    <!-- Tombol Aksi Tanda Tangan -->
                    <div class="flex items-center gap-2">
                        @if($isOwner)
                            <button type="button" @click="openSigModal('borrower')"
                                    class="px-4 py-2 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                                <i data-lucide="pen-tool" class="w-3.5 h-3.5"></i>
                                <span>{{ $loan->borrower_signature ? 'Perbarui Tanda Tangan Peminjam' : 'Tanda Tangani (Pihak Kedua)' }}</span>
                            </button>
                        @endif

                        @if($isPengelola)
                            <button type="button" @click="openSigModal('officer')"
                                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                                <i data-lucide="pen-tool" class="w-3.5 h-3.5"></i>
                                <span>{{ $loan->officer_signature ? 'Perbarui Tanda Tangan Petugas' : 'Tanda Tangani (Pihak Pertama)' }}</span>
                            </button>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Status Pihak Pertama (Pengelola BMN) -->
                    <div class="p-4 rounded-2xl border {{ $loan->officer_signature ? 'border-emerald-200 bg-emerald-50/30' : 'border-slate-200 bg-slate-50/50' }} flex flex-col justify-between space-y-3">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Pihak Pertama (Pengelola Aset)</span>
                            <span class="font-bold text-xs text-slate-800 block">Petugas Pengelola BMN</span>
                        </div>
                        <div class="p-3 bg-white rounded-xl border border-slate-200 flex items-center justify-center min-h-[75px]">
                            @if($loan->officer_signature)
                                <img src="{{ asset('storage/' . $loan->officer_signature) }}" class="max-h-14 max-w-full object-contain" alt="TTD Pengelola">
                            @else
                                <span class="text-xs text-slate-400 italic">Belum Ditandatangani Digital</span>
                            @endif
                        </div>
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="font-bold {{ $loan->officer_signature ? 'text-emerald-700' : 'text-slate-400' }}">
                                {{ $loan->officer_signature ? '✓ Ditandatangani Digital' : 'Tanda Tangan Manual Basah' }}
                            </span>
                            @if($isPengelola)
                                <button type="button" @click="openSigModal('officer')" class="text-[#0B63B5] font-bold hover:underline">
                                    {{ $loan->officer_signature ? 'Ubah' : 'Tanda Tangani' }}
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Status Pihak Kedua (Peminjam) -->
                    <div class="p-4 rounded-2xl border {{ $loan->borrower_signature ? 'border-emerald-200 bg-emerald-50/30' : 'border-slate-200 bg-slate-50/50' }} flex flex-col justify-between space-y-3">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Pihak Kedua (Peminjam BMN)</span>
                            <span class="font-bold text-xs text-slate-800 block">{{ $loan->snap_full_name }}</span>
                        </div>
                        <div class="p-3 bg-white rounded-xl border border-slate-200 flex items-center justify-center min-h-[75px]">
                            @if($loan->borrower_signature)
                                <img src="{{ asset('storage/' . $loan->borrower_signature) }}" class="max-h-14 max-w-full object-contain" alt="TTD Peminjam">
                            @else
                                <span class="text-xs text-slate-400 italic">Belum Ditandatangani Digital</span>
                            @endif
                        </div>
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="font-bold {{ $loan->borrower_signature ? 'text-emerald-700' : 'text-slate-400' }}">
                                {{ $loan->borrower_signature ? '✓ Ditandatangani Digital' : 'Tanda Tangan Manual Basah' }}
                            </span>
                            @if($isOwner)
                                <button type="button" @click="openSigModal('borrower')" class="text-[#0B63B5] font-bold hover:underline">
                                    {{ $loan->borrower_signature ? 'Ubah' : 'Tanda Tangani' }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. DOKUMENTASI FOTO BAST (SERAH TERIMA & PENGEMBALIAN) -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center shrink-0">
                            <i data-lucide="camera" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h3 class="font-extrabold text-sm text-slate-900">Dokumentasi Foto Fisik BAST</h3>
                            <p class="text-[11px] text-slate-500">Foto bukti serah terima peminjaman & inspeksi fisik pengembalian barang BMN.</p>
                        </div>
                    </div>

                    <!-- Tombol Unggah Foto Baru -->
                    @if($isOwner || $isPengelola)
                        <div class="flex items-center gap-2">
                            <button type="button" @click="openPhotoModal('BAST_PEMINJAMAN')"
                                    class="px-3.5 py-1.5 bg-blue-50 hover:bg-blue-100 text-[#0B63B5] border border-blue-200 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-2xs">
                                <i data-lucide="camera" class="w-3.5 h-3.5"></i>
                                <span>+ Foto BAST Peminjaman</span>
                            </button>
                            <button type="button" @click="openPhotoModal('BAST_PENGEMBALIAN')"
                                    class="px-3.5 py-1.5 bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-2xs">
                                <i data-lucide="camera" class="w-3.5 h-3.5"></i>
                                <span>+ Foto BAST Pengembalian</span>
                            </button>
                        </div>
                    @endif
                </div>

                @if($loan->photos->isNotEmpty())
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                        @foreach($loan->photos as $photo)
                            <div class="group relative rounded-2xl border border-slate-200 overflow-hidden bg-slate-50 hover:shadow-md transition">
                                <div class="aspect-4/3 w-full overflow-hidden bg-slate-100 cursor-pointer"
                                     @click="openLightbox('{{ asset('storage/' . $photo->file_path) }}', '{{ addslashes($photo->caption ?: $photo->original_name) }}', 'Tahap: {{ str_replace('_', ' ', $photo->stage) }} • {{ $photo->created_at->format('d/m/Y H:i') }}')">
                                    <img src="{{ asset('storage/' . $photo->file_path) }}" alt="{{ $photo->caption }}"
                                         class="w-full h-full object-cover group-hover:scale-105 transition duration-200">
                                </div>
                                <div class="p-2.5 space-y-1">
                                    <div class="flex items-center justify-between">
                                        <span class="px-2 py-0.5 rounded-md text-[9px] font-extrabold uppercase {{ $photo->stage === 'BAST_PEMINJAMAN' ? 'bg-cyan-100 text-cyan-800' : ($photo->stage === 'BAST_PENGEMBALIAN' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700') }}">
                                            {{ str_replace('BAST_', '', $photo->stage) }}
                                        </span>
                                        @if($photo->uploaded_by === auth()->id() || $isPengelola)
                                            <form method="POST" action="{{ route('loans.photos.delete', $photo->id) }}" onsubmit="return confirm('Hapus foto dokumentasi ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-slate-400 hover:text-red-600 transition" title="Hapus Foto">
                                                    <i data-lucide="trash-2" class="w-3 h-3"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                    <p class="text-[11px] font-semibold text-slate-800 line-clamp-1" title="{{ $photo->caption }}">
                                        {{ $photo->caption ?: $photo->original_name }}
                                    </p>
                                    <span class="text-[9px] text-slate-400 block tabular-nums">
                                        {{ $photo->created_at->format('d/m/Y H:i') }} &bull; {{ $photo->uploader->name ?? 'User' }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200 text-center space-y-2">
                        <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto">
                            <i data-lucide="images" class="w-5 h-5"></i>
                        </div>
                        <h4 class="text-xs font-bold text-slate-700">Belum Ada Foto Dokumentasi BAST</h4>
                        <p class="text-[11px] text-slate-500">
                            Silakan unggah foto serah terima barang atau foto pemeriksaan fisik saat pengembalian (dapat dijepret langsung atau dipilih dari galeri ponsel).
                        </p>
                    </div>
                @endif
            </div>

            <!-- ROLE-BASED ACTION PANELS -->
            <!-- 1. Action Peminjam: Kembalikan BMN (Khusus Role Peminjam Pemilik) -->
            @if($loan->status === 'ACTIVE' && $isOwner && (auth()->user()->isPeminjam() || auth()->user()->isAdmin()))
                <div class="bg-amber-50 rounded-3xl border border-amber-200 p-6 shadow-sm">
                    <h3 class="text-base font-bold text-amber-900">Pengembalian BMN (SOP Pengembalian Langkah 1)</h3>
                    <p class="text-xs text-amber-800 mt-1">
                        Jika kegiatan dinas telah selesai atau barang diminta kembali, silakan ajukan pengembalian fisik barang ke kantor.
                    </p>
                    <form method="POST" action="{{ route('loans.request-return', $loan->id) }}" class="mt-4">
                        @csrf
                        <button type="submit"
                                data-confirm="Apakah Anda yakin ingin mengajukan pengembalian fisik BMN ini ke Petugas BMN di kantor?"
                                data-confirm-title="Pengembalian BMN"
                                data-confirm-btn="Ya, Ajukan Pengembalian"
                                data-confirm-type="warning"
                                class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold shadow-md transition flex items-center gap-2">
                            <i data-lucide="package-open" class="w-4 h-4"></i>
                            <span>Ajukan Pengembalian Fisik BMN ke Kantor</span>
                        </button>
                    </form>
                </div>
            @endif

            <!-- 2. Action Pengelola BMN: Telaah Pengajuan (SUBMITTED) -->
            @if($loan->status === 'SUBMITTED' && $isPengelola)
                <div class="bg-white rounded-3xl border-2 border-blue-200 p-6 shadow-sm space-y-4">
                    <div class="flex items-center gap-2 text-[#0B63B5]">
                        <i data-lucide="clipboard-check" class="w-5 h-5"></i>
                        <h3 class="text-base font-black text-slate-900">Tindakan Pengelola BMN (SOP Peminjaman Langkah 2 & 3)</h3>
                    </div>
                    <p class="text-xs text-slate-600">
                        Periksa kelengkapan form, ketersediaan barang di gudang, dan kondisi fisik barang (Target SLA: 10 menit).
                    </p>

                    <form method="POST" action="{{ route('kelola.review', $loan->id) }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Catatan Penelaahan</label>
                            <textarea name="note" rows="2" class="w-full p-3 rounded-xl border border-slate-300 text-xs font-medium outline-none focus:border-blue-500"
                                      placeholder="Tuliskan catatan kelengkapan dokumen atau alasan perbaikan jika dikembalikan..."></textarea>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <button type="submit" name="action" value="approve_review"
                                    data-confirm="Apakah seluruh dokumen dan ketersediaan fisik BMN telah sesuai untuk diteruskan ke Ketua Tim?"
                                    data-confirm-title="Teruskan ke Ketua Tim"
                                    data-confirm-btn="Ya, Teruskan Berkas"
                                    data-confirm-type="primary"
                                    class="px-5 py-2.5 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white rounded-xl text-xs font-bold shadow-sm transition flex items-center gap-1.5">
                                <i data-lucide="arrow-right-circle" class="w-4 h-4"></i>
                                <span>Teruskan ke Ketua Tim (Dokumen Lengkap)</span>
                            </button>

                            <button type="submit" name="action" value="request_revision"
                                    data-confirm="Apakah Anda yakin ingin mengembalikan pengajuan ini ke peminjam untuk diperbaiki?"
                                    data-confirm-title="Kembalikan untuk Perbaikan"
                                    data-confirm-btn="Ya, Kembalikan"
                                    data-confirm-type="warning"
                                    class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5">
                                <i data-lucide="undo-2" class="w-4 h-4"></i>
                                <span>Kembalikan untuk Diperbaiki</span>
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            <!-- 3. Action Pengelola BMN: Terbitkan BAST (APPROVED) -->
            @if($loan->status === 'APPROVED' && $isPengelola)
                <div class="bg-white rounded-3xl border-2 border-cyan-300 p-6 shadow-sm space-y-3">
                    <h3 class="text-base font-black text-slate-900">Penerbitan BAST Peminjaman (SOP Langkah 5)</h3>
                    <p class="text-xs text-slate-600">
                        Disposisi persetujuan telah diberikan oleh Ketua Tim. Silakan buatkan dokumen BAST Peminjaman resmi bernomor.
                    </p>
                    <form method="POST" action="{{ route('kelola.issue-bast', $loan->id) }}">
                        @csrf
                        <button type="submit"
                                data-confirm="Terbitkan dokumen Berita Acara Serah Terima (BAST) Peminjaman bernomor otomatis sekarang?"
                                data-confirm-title="Terbitkan BAST Peminjaman"
                                data-confirm-btn="Ya, Terbitkan BAST"
                                data-confirm-type="primary"
                                class="px-5 py-2.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-xl text-xs font-bold shadow-sm transition flex items-center gap-2">
                            <i data-lucide="file-plus" class="w-4 h-4"></i>
                            <span>Buat Dokumen BAST Peminjaman Bernomor</span>
                        </button>
                    </form>
                </div>
            @endif

            <!-- 4. Action Pengelola BMN: Konfirmasi Serah Terima Fisik (BAST_ISSUED) -->
            @if($loan->status === 'BAST_ISSUED' && $isPengelola)
                <div class="bg-white rounded-3xl border-2 border-emerald-300 p-6 shadow-sm space-y-3">
                    <h3 class="text-base font-black text-slate-900">Serah Terima BMN & Tandatangan (SOP Langkah 6 & 7)</h3>
                    <p class="text-xs text-slate-600">
                        Cetak draf BAST di atas, mintakan tanda tangan basah Peminjam, serahkan fisik BMN, lalu konfirmasi pengaktifan.
                    </p>
                    <form method="POST" action="{{ route('kelola.confirm-active', $loan->id) }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div class="p-4 rounded-2xl bg-emerald-50/70 border border-emerald-200 space-y-2">
                            <label class="block text-xs font-bold text-emerald-950">Foto Dokumentasi Serah Terima BAST (Opsional):</label>
                            <p class="text-[11px] text-emerald-800">Lampirkan foto penyerahan fisik barang atau bukti BAST yang telah ditandatangani.</p>
                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                <label class="cursor-pointer px-3.5 py-1.5 bg-white border border-emerald-300 hover:bg-emerald-50 text-emerald-800 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-2xs">
                                    <i data-lucide="camera" class="w-3.5 h-3.5 text-emerald-600"></i>
                                    <span>Jepret Kamera</span>
                                    <input type="file" name="photos[]" accept="image/*" capture="environment" class="hidden" onchange="updateFileCount(this, 'active_photo_count')">
                                </label>
                                <label class="cursor-pointer px-3.5 py-1.5 bg-white border border-emerald-300 hover:bg-emerald-50 text-emerald-800 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-2xs">
                                    <i data-lucide="images" class="w-3.5 h-3.5 text-emerald-600"></i>
                                    <span>Pilih dari Galeri</span>
                                    <input type="file" name="photos[]" accept="image/*" multiple class="hidden" onchange="updateFileCount(this, 'active_photo_count')">
                                </label>
                                <span id="active_photo_count" class="text-xs text-slate-500 italic">Belum ada foto dipilih</span>
                            </div>
                        </div>

                        <button type="submit"
                                data-confirm="Pastikan fisik BMN telah diserahkan dan dokumen BAST fisik telah ditandatangani peminjam. Aktifkan peminjaman sekarang?"
                                data-confirm-title="Konfirmasi Serah Terima BMN"
                                data-confirm-btn="Ya, Serahkan & Aktifkan"
                                data-confirm-type="success"
                                class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-sm transition flex items-center gap-2">
                            <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                            <span>Konfirmasi Tanda Tangan & Serahkan Barang (Aktifkan)</span>
                        </button>
                    </form>
                </div>
            @endif

            <!-- 5. Action Pengelola BMN: Cek Fisik Pengembalian (RETURN_REQUESTED) -->
            @if($loan->status === 'RETURN_REQUESTED' && $isPengelola)
                <div class="bg-white rounded-3xl border-2 border-amber-300 p-6 shadow-sm space-y-4">
                    <h3 class="text-base font-black text-slate-900">Inspeksi Fisik Pengembalian BMN (SOP Pengembalian Langkah 2 & 3)</h3>
                    <p class="text-xs text-slate-600">
                        Lakukan pengecekan kesesuaian jumlah, jenis, dan kondisi fisik BMN yang dikembalikan di kantor (Target SLA: 5 Menit).
                    </p>

                    <form method="POST" action="{{ route('kelola.process-return-check', $loan->id) }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        @foreach($loan->items as $item)
                            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-2">
                                <div class="font-bold text-xs text-slate-900">{{ $item->snap_item_name }} ({{ $item->snap_brand_model }})</div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Kondisi Fisik Diterima</label>
                                        <select name="items[{{ $item->id }}][condition_in]" class="w-full p-2 bg-white rounded-xl border border-slate-300 text-xs font-semibold">
                                            <option value="Baik" selected>Baik (Lengkap & Normal)</option>
                                            <option value="Rusak">Rusak (Perlu Perbaikan)</option>
                                            <option value="Tidak Lengkap/Hilang">Tidak Lengkap / Hilang</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Tindak Lanjut (Wajib jika Rusak/Hilang)</label>
                                        <input type="text" name="items[{{ $item->id }}][follow_up_note]" placeholder="Contoh: Penggantian sparepart oleh peminjam"
                                               class="w-full p-2 bg-white rounded-xl border border-slate-300 text-xs">
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <div class="p-4 rounded-2xl bg-amber-50/70 border border-amber-200 space-y-2">
                            <label class="block text-xs font-bold text-amber-950">Foto Dokumentasi Kondisi Fisik Pengembalian (Opsional):</label>
                            <p class="text-[11px] text-amber-800">Lampirkan foto fisik barang saat dikembalikan (sangat dianjurkan terutama bila terdapat lecet/rusak).</p>
                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                <label class="cursor-pointer px-3.5 py-1.5 bg-white border border-amber-300 hover:bg-amber-50 text-amber-800 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-2xs">
                                    <i data-lucide="camera" class="w-3.5 h-3.5 text-amber-600"></i>
                                    <span>Jepret Kamera</span>
                                    <input type="file" name="photos[]" accept="image/*" capture="environment" class="hidden" onchange="updateFileCount(this, 'return_photo_count')">
                                </label>
                                <label class="cursor-pointer px-3.5 py-1.5 bg-white border border-amber-300 hover:bg-amber-50 text-amber-800 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-2xs">
                                    <i data-lucide="images" class="w-3.5 h-3.5 text-amber-600"></i>
                                    <span>Pilih dari Galeri</span>
                                    <input type="file" name="photos[]" accept="image/*" multiple class="hidden" onchange="updateFileCount(this, 'return_photo_count')">
                                </label>
                                <span id="return_photo_count" class="text-xs text-slate-500 italic">Belum ada foto dipilih</span>
                            </div>
                        </div>

                        <button type="submit"
                                data-confirm="Simpan hasil inspeksi fisik dan terbitkan dokumen BAST Pengembalian bernomor resmi?"
                                data-confirm-title="Pemeriksaan Fisik BMN"
                                data-confirm-btn="Ya, Terbitkan BAST Kembali"
                                data-confirm-type="primary"
                                class="px-5 py-2.5 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white rounded-xl text-xs font-bold shadow-sm transition flex items-center gap-2">
                            <i data-lucide="clipboard-check" class="w-4 h-4"></i>
                            <span>Selesaikan Pemeriksaan & Terbitkan BAST Pengembalian</span>
                        </button>
                    </form>
                </div>
            @endif

            <!-- 6. Action Pengelola BMN: Finalisasi Simpan BMN (RETURN_CHECKED) -->
            @if($loan->status === 'RETURN_CHECKED' && $isPengelola)
                <div class="bg-white rounded-3xl border-2 border-indigo-300 p-6 shadow-sm space-y-3">
                    <h3 class="text-base font-black text-slate-900">Penyelesaian Pengembalian BMN (SOP Pengembalian Langkah 5 & 6)</h3>
                    <p class="text-xs text-slate-600">
                        BAST Pengembalian telah ditandatangani. Simpan kembali fisik BMN ke gudang/tempat penyimpanan dan perbarui status inventaris.
                    </p>
                    <form method="POST" action="{{ route('kelola.finalize-return', $loan->id) }}">
                        @csrf
                        <button type="submit"
                                data-confirm="Apakah Anda yakin ingin menutup siklus peminjaman ini dan menyimpan kembali fisik BMN ke inventaris?"
                                data-confirm-title="Finalisasi Pengembalian"
                                data-confirm-btn="Ya, Selesaikan & Simpan"
                                data-confirm-type="success"
                                class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition flex items-center gap-2">
                            <i data-lucide="archive" class="w-4 h-4"></i>
                            <span>Tutup Siklus Peminjaman & Simpan BMN ke Inventaris</span>
                        </button>
                    </form>
                </div>
            @endif
        </div>

        <!-- RIGHT COLUMN: 7-STEP SOP TIMELINE -->
        <div class="lg:col-span-4 space-y-6">
            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm">
                <div class="flex items-center gap-2 border-b border-slate-100 pb-3 mb-4">
                    <i data-lucide="git-commit" class="w-5 h-5 text-[#0B63B5]"></i>
                    <div>
                        <h3 class="font-black text-sm text-slate-900">Alur SOP Peminjaman BMN</h3>
                        <span class="text-[10px] text-slate-400 font-semibold">SK SOP B-489 BBLSDM</span>
                    </div>
                </div>

                @php
                    $stepIndex = $meta['step'];
                    $sopSteps = [
                        1 => ['title' => 'Mengajukan Form', 'actor' => 'Peminjam', 'sla' => '5 Menit'],
                        2 => ['title' => 'Telaah Dokumen & Kondisi', 'actor' => 'Pengelola BMN', 'sla' => '10 Menit'],
                        3 => ['title' => 'Diteruskan ke PIC Aset', 'actor' => 'Pengelola BMN', 'sla' => '1 Menit'],
                        4 => ['title' => 'Persetujuan Disposisi', 'actor' => 'Ketua Tim Aset', 'sla' => '5 Menit'],
                        5 => ['title' => 'Pembuatan Draf BAST', 'actor' => 'Pengelola BMN', 'sla' => '5 Menit'],
                        6 => ['title' => 'Tanda Tangan & Dokumentasi', 'actor' => 'Peminjam & Petugas', 'sla' => '5 Menit'],
                        7 => ['title' => 'Menerima & Memakai BMN', 'actor' => 'Peminjam', 'sla' => '1 Menit'],
                    ];
                @endphp

                <!-- Vertical Timeline -->
                <div class="relative pl-6 space-y-6 before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
                    @foreach($sopSteps as $sNum => $sInfo)
                        @php
                            $isDone = ($stepIndex > $sNum) || ($stepIndex === 7 && $sNum === 7);
                            $isCurrent = ($stepIndex === $sNum);
                        @endphp
                        <div class="relative">
                            <!-- Bullet Icon -->
                            <div class="absolute -left-6 top-0.5 w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold
                                        {{ $isDone ? 'bg-emerald-500 text-white ring-4 ring-emerald-50' : ($isCurrent ? 'bg-[#0B63B5] text-white ring-4 ring-blue-100 animate-pulse' : 'bg-slate-200 text-slate-500') }}">
                                @if($isDone) &check; @else {{ $sNum }} @endif
                            </div>

                            <div>
                                <div class="flex items-center justify-between gap-1">
                                    <h4 class="text-xs font-bold {{ $isCurrent ? 'text-[#0B63B5]' : ($isDone ? 'text-slate-800' : 'text-slate-400') }}">
                                        {{ $sInfo['title'] }}
                                    </h4>
                                    <span class="text-[9px] font-semibold text-slate-400 tabular-nums">{{ $sInfo['sla'] }}</span>
                                </div>
                                <span class="text-[11px] block mt-0.5 {{ $isCurrent ? 'text-slate-700 font-semibold' : 'text-slate-400' }}">
                                    Pelaksana: {{ $sInfo['actor'] }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Status History Trail -->
                <div class="mt-8 pt-5 border-t border-slate-100">
                    <h4 class="text-xs font-bold text-slate-900 mb-3">Jejak Audit Status</h4>
                    <div class="space-y-3 text-xs">
                        @foreach($loan->history as $h)
                            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                                <div class="flex items-center justify-between text-[11px] text-slate-400 mb-1">
                                    <span class="font-semibold text-slate-700">{{ $h->actor->name ?? 'Sistem' }}</span>
                                    <span class="tabular-nums">{{ $h->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                                <div class="font-bold text-slate-800 text-[11px]">
                                    {{ $h->from_status ?? 'Mulai' }} &rarr; <span class="text-[#0B63B5]">{{ $h->to_status }}</span>
                                </div>
                                @if($h->note)
                                    <p class="text-[11px] text-slate-500 mt-1 italic">"{{ $h->note }}"</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 1. MODAL TANDA TANGAN DIGITAL DOKUMEN BAST -->
    <div x-show="sigModalOpen" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         @keydown.escape.window="sigModalOpen = false">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="sigModalOpen = false"></div>

        <div class="min-h-full flex items-center justify-center p-4">
            <div class="relative bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl border border-slate-100 space-y-5"
                 @click.stop>
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-blue-50 text-[#0B63B5] flex items-center justify-center">
                            <i data-lucide="signature" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold text-slate-900">Bubuhkan Tanda Tangan Digital</h3>
                            <p class="text-xs text-slate-500">
                                Menandatangani sebagai: <strong class="text-[#0B63B5]" x-text="sigAs === 'borrower' ? 'Pihak Kedua (Peminjam)' : 'Pihak Pertama (Pengelola BMN)'"></strong>
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="sigModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Tabs -->
                <div class="grid grid-cols-3 gap-1 p-1 bg-slate-100 rounded-2xl text-xs font-bold text-center">
                    <button type="button" @click="sigTab = 'profile'"
                            :class="sigTab === 'profile' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-700'"
                            class="py-2 rounded-xl transition">
                        Dari Profil
                    </button>
                    <button type="button" @click="sigTab = 'pad'; initCanvas()"
                            :class="sigTab === 'pad' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-700'"
                            class="py-2 rounded-xl transition">
                        Papan Gambar
                    </button>
                    <button type="button" @click="sigTab = 'file'"
                            :class="sigTab === 'file' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-700'"
                            class="py-2 rounded-xl transition">
                        Unggah Berkas
                    </button>
                </div>

                <!-- Form -->
                <form id="loan-sig-form" method="POST" action="{{ route('loans.sign', $loan->id) }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <input type="hidden" name="sign_as" :value="sigAs">
                    <input type="hidden" name="source" :value="sigTab">
                    <input type="hidden" name="signature_data" id="loan_sig_data_input">

                    <!-- Tab 1: Dari Profil -->
                    <div x-show="sigTab === 'profile'" class="space-y-3">
                        @if(auth()->user()->signature_path)
                            <div class="p-4 rounded-2xl border-2 border-dashed border-emerald-200 bg-emerald-50/30 text-center space-y-2">
                                <span class="text-xs font-bold text-emerald-800 block">Tanda tangan yang tersimpan di profil Anda:</span>
                                <div class="p-2 bg-white rounded-xl border border-slate-200 inline-block shadow-2xs">
                                    <img src="{{ asset('storage/' . auth()->user()->signature_path) }}" alt="TTD Profil" class="max-h-24 max-w-full object-contain mx-auto">
                                </div>
                                <p class="text-[11px] text-slate-500">Tanda tangan ini akan otomatis dicetak pada lembar PDF BAST.</p>
                            </div>
                        @else
                            <div class="p-5 rounded-2xl bg-amber-50 border border-amber-200 text-center space-y-2">
                                <i data-lucide="alert-circle" class="w-7 h-7 text-amber-600 mx-auto"></i>
                                <h4 class="text-xs font-bold text-amber-900">Belum Ada Tanda Tangan di Profil</h4>
                                <p class="text-[11px] text-amber-700">
                                    Anda belum mengatur tanda tangan di profil. Silakan pilih tab <strong>"Papan Gambar"</strong> untuk menggambar langsung, atau <strong>"Unggah Berkas"</strong>.
                                </p>
                            </div>
                        @endif
                    </div>

                    <!-- Tab 2: Papan Gambar Kanvas -->
                    <div x-show="sigTab === 'pad'" class="space-y-3">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-700 flex items-center gap-1.5">
                                <i data-lucide="edit-3" class="w-3.5 h-3.5 text-[#0B63B5]"></i>
                                <span>Goreskan Tanda Tangan:</span>
                            </span>
                            <div class="flex items-center gap-2">
                                <span x-show="justCorrected" x-transition.opacity class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                    <i data-lucide="sparkles" class="w-3 h-3 text-emerald-500"></i>
                                    <span>Stroke Dihaluskan</span>
                                </span>
                                <button type="button" @click="undo()" class="text-slate-600 hover:text-slate-800 font-bold flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-lg border border-slate-200 bg-white cursor-pointer">
                                    <i data-lucide="undo" class="w-3 h-3"></i>
                                    <span>Undo</span>
                                </button>
                                <button type="button" @click="clearCanvas()" class="text-red-600 hover:text-red-700 font-bold flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-lg border border-red-200 bg-red-50/50 cursor-pointer">
                                    <i data-lucide="rotate-ccw" class="w-3 h-3"></i>
                                    <span>Bersihkan</span>
                                </button>
                            </div>
                        </div>

                        <div class="relative rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 overflow-hidden">
                            <canvas id="loan-sig-pad"
                                    class="w-full h-44 cursor-crosshair touch-none block"
                                    @mousedown="startDraw($event)"
                                    @mousemove="draw($event)"
                                    @mouseup="endDraw($event)"
                                    @mouseleave="endDraw($event)"
                                    @touchstart="startDraw($event)"
                                    @touchmove="draw($event)"
                                    @touchend="endDraw($event)">
                            </canvas>
                            <div x-show="!hasDrawn" class="pointer-events-none absolute inset-0 flex items-center justify-center text-slate-400 text-xs font-medium">
                                Sentuh layar atau klik & seret mouse di sini (stroke otomatis dihaluskan saat dilepas)
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-2 pt-1 text-xs text-slate-500">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="save_to_profile" value="1" class="rounded border-slate-300 text-[#0B63B5] focus:ring-[#0B63B5]">
                                <span>Simpan juga tanda tangan ini ke profil saya</span>
                            </label>
                            <label class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-slate-600 cursor-pointer select-none">
                                <input type="checkbox" x-model="smoothingEnabled" class="rounded border-slate-300 text-[#0B63B5] focus:ring-[#0B63B5] w-3 h-3">
                                <span>Koreksi Halus</span>
                            </label>
                        </div>
                    </div>

                    <!-- Tab 3: Unggah Berkas -->
                    <div x-show="sigTab === 'file'" class="space-y-3">
                        <label class="block text-xs font-bold text-slate-700">Pilih Berkas Gambar Tanda Tangan (PNG / JPG transparan disarankan):</label>
                        <input type="file" name="signature_file" accept="image/png,image/jpeg,image/jpg"
                               class="w-full p-2.5 rounded-xl border border-slate-300 text-xs bg-slate-50 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-[#0B63B5] file:text-white hover:file:bg-[#0B4F8A]">

                        <label class="flex items-center gap-2 cursor-pointer pt-1">
                            <input type="checkbox" name="save_to_profile" value="1" class="rounded border-slate-300 text-[#0B63B5] focus:ring-[#0B63B5]">
                            <span class="text-xs text-slate-600">Simpan juga berkas ini ke profil saya</span>
                        </label>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                        <button type="button" @click="sigModalOpen = false"
                                class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-bold transition">
                            Batal
                        </button>
                        <button type="button" @click="submitSig()"
                                class="px-5 py-2.5 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white rounded-xl text-xs font-bold shadow-md transition flex items-center gap-1.5">
                            <i data-lucide="check" class="w-4 h-4"></i>
                            <span>Bubuhkan ke Dokumen</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 2. MODAL UNGGAH FOTO DOKUMENTASI BAST -->
    <div x-show="photoModalOpen" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         @keydown.escape.window="photoModalOpen = false">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="photoModalOpen = false"></div>

        <div class="min-h-full flex items-center justify-center p-4">
            <div class="relative bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl border border-slate-100 space-y-5"
                 @click.stop>
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center">
                            <i data-lucide="camera" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold text-slate-900">Unggah Foto Dokumentasi BAST</h3>
                            <p class="text-xs text-slate-500">Ambil jepretan kamera langsung atau pilih 1 atau lebih foto dari galeri</p>
                        </div>
                    </div>
                    <button type="button" @click="photoModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <form method="POST" action="{{ route('loans.photos.upload', $loan->id) }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <!-- Stage Selector -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Tahap Dokumentasi BAST:</label>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition"
                                   :class="photoStage === 'BAST_PEMINJAMAN' ? 'border-cyan-500 bg-cyan-50/50 text-cyan-900 font-bold' : 'border-slate-200 text-slate-600'">
                                <input type="radio" name="stage" value="BAST_PEMINJAMAN" x-model="photoStage" class="hidden">
                                <i data-lucide="package-check" class="w-4 h-4 text-cyan-600"></i>
                                <span>BAST Peminjaman</span>
                            </label>
                            <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition"
                                   :class="photoStage === 'BAST_PENGEMBALIAN' ? 'border-purple-500 bg-purple-50/50 text-purple-900 font-bold' : 'border-slate-200 text-slate-600'">
                                <input type="radio" name="stage" value="BAST_PENGEMBALIAN" x-model="photoStage" class="hidden">
                                <i data-lucide="rotate-ccw" class="w-4 h-4 text-purple-600"></i>
                                <span>BAST Pengembalian</span>
                            </label>
                        </div>
                    </div>

                    <!-- Action: Camera or Gallery Picker -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-2">Pilih Sumber Foto (Boleh 1 atau lebih foto):</label>
                        <div class="grid grid-cols-2 gap-2.5">
                            <!-- Button 1: Kamera HP / Webcam -->
                            <label class="flex flex-col items-center justify-center p-4 rounded-2xl border-2 border-dashed border-[#0B63B5]/40 bg-blue-50/30 hover:bg-blue-50 cursor-pointer transition text-center group">
                                <div class="w-10 h-10 rounded-full bg-blue-100 text-[#0B63B5] flex items-center justify-center mb-2 group-hover:scale-110 transition">
                                    <i data-lucide="camera" class="w-5 h-5"></i>
                                </div>
                                <span class="text-xs font-bold text-slate-800">Jepret Kamera</span>
                                <span class="text-[10px] text-slate-500 mt-0.5">Buka kamera ponsel/laptop</span>
                                <input type="file" name="photos[]" accept="image/*" capture="environment" class="hidden" @change="handlePhotoSelect($event)">
                            </label>

                            <!-- Button 2: Galeri / File (Bisa Multiple) -->
                            <label class="flex flex-col items-center justify-center p-4 rounded-2xl border-2 border-dashed border-purple-300 bg-purple-50/30 hover:bg-purple-50 cursor-pointer transition text-center group">
                                <div class="w-10 h-10 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center mb-2 group-hover:scale-110 transition">
                                    <i data-lucide="images" class="w-5 h-5"></i>
                                </div>
                                <span class="text-xs font-bold text-slate-800">Pilih dari Galeri</span>
                                <span class="text-[10px] text-slate-500 mt-0.5">Bisa pilih banyak foto</span>
                                <input type="file" name="photos[]" accept="image/*" multiple class="hidden" @change="handlePhotoSelect($event)">
                            </label>
                        </div>
                    </div>

                    <!-- Preview of Selected Photos -->
                    <template x-if="previewPhotos.length > 0">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-slate-700" x-text="previewPhotos.length + ' foto siap diunggah:'"></span>
                                <button type="button" @click="previewPhotos = []" class="text-red-600 font-bold text-[11px] hover:underline">Hapus Semua</button>
                            </div>
                            <div class="grid grid-cols-3 gap-2 max-h-36 overflow-y-auto p-1 bg-slate-50 rounded-2xl border border-slate-200">
                                <template x-for="(p, idx) in previewPhotos" :key="idx">
                                    <div class="relative rounded-xl overflow-hidden aspect-square border border-slate-300 group">
                                        <img :src="p.url" class="w-full h-full object-cover">
                                        <div class="absolute bottom-0 inset-x-0 bg-slate-900/70 p-1 text-[9px] text-white truncate text-center" x-text="p.size"></div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- Caption -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Keterangan Foto (Opsional):</label>
                        <input type="text" name="caption" placeholder="Contoh: Kondisi fisik barang lengkap dan normal"
                               class="w-full p-2.5 rounded-xl border border-slate-300 text-xs font-medium focus:border-purple-500 outline-none">
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                        <button type="button" @click="photoModalOpen = false"
                                class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-bold transition">
                            Batal
                        </button>
                        <button type="submit"
                                class="px-5 py-2.5 bg-purple-700 hover:bg-purple-800 text-white rounded-xl text-xs font-bold shadow-md transition flex items-center gap-1.5">
                            <i data-lucide="upload" class="w-4 h-4"></i>
                            <span>Unggah Foto Dokumentasi</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 3. MODAL LIGHTBOX PRATINJAU FOTO DOKUMENTASI -->
    <div x-show="lightboxOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-md"
         @keydown.escape.window="lightboxOpen = false"
         @click="lightboxOpen = false">
        <div class="relative max-w-4xl w-full flex flex-col items-center gap-3" @click.stop>
            <button type="button" @click="lightboxOpen = false"
                    class="absolute -top-10 right-0 text-white/80 hover:text-white p-1 rounded-full bg-slate-800/60 hover:bg-slate-800 transition">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>

            <div class="rounded-2xl overflow-hidden border border-white/10 shadow-2xl bg-black/40 max-h-[80vh] flex items-center justify-center">
                <img :src="activePhotoUrl" :alt="activePhotoTitle" class="max-h-[80vh] max-w-full object-contain">
            </div>

            <div class="text-center text-white space-y-1">
                <h4 class="text-sm font-bold" x-text="activePhotoTitle"></h4>
                <p class="text-xs text-white/70" x-text="activePhotoMeta"></p>
            </div>
        </div>
    </div>
</div>

<script>
    function updateFileCount(input, targetId) {
        const el = document.getElementById(targetId);
        if (!el) return;
        const count = input.files ? input.files.length : 0;
        if (count === 0) {
            el.textContent = 'Belum ada foto dipilih';
            el.className = 'text-xs text-slate-500 italic';
        } else {
            el.textContent = count + ' foto siap diunggah';
            el.className = 'text-xs text-emerald-700 font-bold';
        }
    }
</script>
@endsection
