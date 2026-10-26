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
    bastTab: '{{ in_array($loan->status, ['RETURN_REQUESTED', 'RETURN_CHECKED', 'RETURNED']) ? 'return' : 'loan' }}',
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
    redrawCanvas(exportMode = false) {
        if (!this.ctx || !this.canvas) return;
        const rect = this.canvas.getBoundingClientRect();
        this.ctx.clearRect(0, 0, rect.width, rect.height);

        const inkColor = '#0F172A';

        this.ctx.strokeStyle = inkColor;
        this.ctx.fillStyle = inkColor;
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
        const inkColor = '#0F172A';
        this.ctx.strokeStyle = inkColor;
        this.ctx.fillStyle = inkColor;
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

        const inkColor = '#0F172A';
        this.ctx.strokeStyle = inkColor;
        this.ctx.fillStyle = inkColor;

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
            if (this.currentStroke.length > 2) {
                const filtered = this.filterPoints(this.currentStroke, 2);
                finalStroke = this.chaikinSmooth(filtered, 2);
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
    trimCanvas(canvas, padding = 12) {
        if (!canvas) return '';
        const ctx = canvas.getContext('2d');
        const width = canvas.width;
        const height = canvas.height;
        if (width === 0 || height === 0) return '';
        const imgData = ctx.getImageData(0, 0, width, height);
        const data = imgData.data;

        let minX = width, minY = height, maxX = 0, maxY = 0;
        let found = false;

        for (let y = 0; y < height; y++) {
            for (let x = 0; x < width; x++) {
                const alpha = data[(y * width + x) * 4 + 3];
                if (alpha > 15) {
                    found = true;
                    if (x < minX) minX = x;
                    if (x > maxX) maxX = x;
                    if (y < minY) minY = y;
                    if (y > maxY) maxY = y;
                }
            }
        }

        if (!found) return canvas.toDataURL('image/png');

        const pad = Math.round(padding * 2);
        minX = Math.max(0, minX - pad);
        minY = Math.max(0, minY - pad);
        maxX = Math.min(width, maxX + pad);
        maxY = Math.min(height, maxY + pad);

        let cropWidth = maxX - minX;
        let cropHeight = maxY - minY;

        // Maintain document aspect ratio (2.5 : 1)
        const targetRatio = 2.5;
        const currentRatio = cropWidth / cropHeight;

        if (currentRatio < targetRatio) {
            const desiredWidth = Math.min(width, Math.round(cropHeight * targetRatio));
            const diff = desiredWidth - cropWidth;
            minX = Math.max(0, Math.round(minX - diff / 2));
            cropWidth = desiredWidth;
        } else if (currentRatio > targetRatio) {
            const desiredHeight = Math.min(height, Math.round(cropWidth / targetRatio));
            const diff = desiredHeight - cropHeight;
            minY = Math.max(0, Math.round(minY - diff / 2));
            cropHeight = desiredHeight;
        }

        const trimmed = document.createElement('canvas');
        trimmed.width = cropWidth;
        trimmed.height = cropHeight;
        const trimmedCtx = trimmed.getContext('2d');

        trimmedCtx.drawImage(
            canvas,
            minX, minY, cropWidth, cropHeight,
            0, 0, cropWidth, cropHeight
        );

        return trimmed.toDataURL('image/png');
    },
    submitSig() {
        if (this.sigTab === 'pad') {
            if (!this.hasDrawn || this.strokes.length === 0) {
                alert('Silakan goreskan tanda tangan pada papan gambar kanvas terlebih dahulu.');
                return;
            }
            this.redrawCanvas(true);
            document.getElementById('loan_sig_data_input').value = this.trimCanvas(this.canvas);
            this.redrawCanvas(false);
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
        <div class="flex items-center gap-3">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('loans.index') }}"
               onclick="if (document.referrer && document.referrer.indexOf(window.location.host) !== -1) { history.back(); return false; }"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 hover:text-slate-900 hover:bg-slate-50 transition shadow-2xs">
                <i data-lucide="arrow-left" class="w-4 h-4 text-slate-500"></i>
                <span>Kembali</span>
            </a>
            <div class="flex items-center gap-2 text-xs text-slate-500 font-semibold">
                <a href="{{ route('dashboard') }}" class="hover:text-slate-900">Beranda</a>
                <span>/</span>
                <a href="{{ route('loans.index') }}" class="hover:text-slate-900">Pengajuan Saya</a>
                <span>/</span>
                <span class="text-slate-800 font-bold">Detail #{{ substr($loan->id, 0, 8) }}</span>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border {{ $meta['bg'] }}">
                <i data-lucide="{{ $meta['icon'] }}" class="w-3.5 h-3.5"></i>
                <span>{{ $meta['label'] }}</span>
            </span>

            @if(in_array($loan->status, ['DRAFT', 'SUBMITTED', 'NEEDS_REVISION', 'UNDER_APPROVAL']) && ($isPengelola || $user->isAdmin()))
                <form method="POST" action="{{ route('kelola.destroy', $loan->id) }}" class="inline-block">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            data-confirm="Apakah Anda yakin ingin menghapus berkas pengajuan peminjaman ini? Tindakan ini akan membatalkan pengajuan dan seluruh barang inventaris terkait akan langsung kembali berstatus Tersedia untuk dipinjam pegawai lain."
                            data-confirm-title="Hapus Berkas Pengajuan BMN?"
                            data-confirm-btn="Ya, Hapus Pengajuan"
                            data-confirm-cancel="Batal"
                            data-confirm-type="danger"
                            title="Hapus berkas pengajuan ini sebelum disetujui Ketua Tim"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-red-200 dark:border-red-800/80 bg-red-50 dark:bg-red-950/60 hover:bg-red-100 dark:hover:bg-red-900/60 text-red-700 dark:text-red-300 text-xs font-bold transition shadow-2xs cursor-pointer">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5 text-red-600 dark:text-red-400"></i>
                        <span>Hapus Pengajuan</span>
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Main Grid: Info on Left (8 cols), Timeline on Right (4 cols) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- LEFT COLUMN: Details, Items, Actions -->
        <div class="lg:col-span-8 space-y-6">
            @if(in_array($loan->status, ['DRAFT', 'NEEDS_REVISION']) && ($isOwner || $user->isAdmin()))
                @php
                    $hasBorrowerSig = !empty($loan->borrower_signature) && \Illuminate\Support\Facades\Storage::disk('public')->exists($loan->borrower_signature);
                    if (!$hasBorrowerSig && !empty(auth()->user()->signature_path) && \Illuminate\Support\Facades\Storage::disk('public')->exists(auth()->user()->signature_path)) {
                        $hasBorrowerSig = true;
                    }
                    $isRevision = ($loan->status === 'NEEDS_REVISION');
                @endphp
                <div class="rounded-3xl border-2 {{ $isRevision ? 'border-amber-300 dark:border-amber-700/80 bg-amber-50/70 dark:bg-amber-950/40' : 'border-[#0B63B5]/40 dark:border-blue-600/60 bg-blue-50/60 dark:bg-blue-950/40' }} p-6 shadow-sm space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-2xl {{ $isRevision ? 'bg-amber-100 dark:bg-amber-900/60 text-amber-700 dark:text-amber-400' : 'bg-blue-100 dark:bg-blue-900/60 text-[#0B63B5] dark:text-blue-400' }} flex items-center justify-center shrink-0 shadow-2xs">
                                <i data-lucide="{{ $isRevision ? 'alert-triangle' : 'file-edit' }}" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-black text-slate-900 dark:text-white">
                                    {{ $isRevision ? 'Pengajuan Dikembalikan untuk Diperbaiki (Perlu Revisi)' : 'Draf Pengajuan Peminjaman BMN' }}
                                </h3>
                                <p class="text-xs text-slate-600 dark:text-slate-300 mt-0.5">
                                    {{ $isRevision 
                                        ? 'Pengelola BMN mengembalikan pengajuan ini. Anda dapat mengedit rincian draf pengajuan, memeriksa tanda tangan digital, dan mengirimkannya kembali.' 
                                        : 'Pengajuan Anda saat ini tersimpan sebagai draf. Periksa kelengkapan, bubuhkan tanda tangan digital, lalu kirim ke Pengelola BMN.' }}
                                </p>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-black uppercase tracking-wider {{ $isRevision ? 'bg-amber-200/80 dark:bg-amber-900/70 text-amber-900 dark:text-amber-200 border border-amber-300 dark:border-amber-700' : 'bg-blue-200/80 dark:bg-blue-900/70 text-blue-900 dark:text-blue-200 border border-blue-300 dark:border-blue-700' }} self-start shrink-0">
                            <i data-lucide="{{ $isRevision ? 'undo-2' : 'file-text' }}" class="w-3.5 h-3.5"></i>
                            <span>{{ $isRevision ? 'Perlu Revisi' : 'Draf Belum Terkirim' }}</span>
                        </span>
                    </div>

                    @if($isRevision && !empty($loan->review_note))
                        <div class="p-4 rounded-2xl bg-white/90 dark:bg-slate-900/90 border border-amber-200 dark:border-amber-800 text-xs space-y-1 shadow-2xs">
                            <span class="font-bold text-amber-900 dark:text-amber-300 uppercase tracking-wider text-[10px] flex items-center gap-1.5">
                                <i data-lucide="message-square" class="w-3.5 h-3.5 text-amber-600"></i>
                                Catatan dari Pengelola BMN:
                            </span>
                            <p class="text-slate-800 dark:text-slate-200 font-medium whitespace-pre-line leading-relaxed">
                                {{ $loan->review_note }}
                            </p>
                        </div>
                    @endif

                    <!-- Tanda Tangan Peminjam Status -->
                    <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border {{ $hasBorrowerSig ? 'border-emerald-200 dark:border-emerald-800/60' : 'border-amber-200 dark:border-amber-800/60' }} flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs">
                        <div class="flex items-center gap-3 min-w-0">
                            @if($hasBorrowerSig)
                                <div class="w-24 h-12 bg-white signature-paper rounded-xl border border-emerald-200 dark:border-emerald-700 p-1 flex items-center justify-center shrink-0 shadow-2xs" style="background-color: #ffffff !important;">
                                    <img src="{{ asset('storage/' . ($loan->borrower_signature ?: auth()->user()->signature_path)) }}" alt="TTD Peminjam" class="max-h-full max-w-full object-contain">
                                </div>
                                <div class="min-w-0 text-xs">
                                    <div class="flex items-center gap-1.5 font-bold text-slate-800 dark:text-white">
                                        <span>Tanda Tangan Peminjam Terpasang</span>
                                        <i data-lucide="check-circle-2" class="w-3.5 h-3.5 text-emerald-600"></i>
                                    </div>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                        Tanda tangan sah atas nama <strong>{{ $loan->snap_full_name }}</strong>
                                    </p>
                                </div>
                            @else
                                <div class="w-12 h-12 rounded-xl bg-amber-100 dark:bg-amber-900/60 text-amber-700 dark:text-amber-400 flex items-center justify-center shrink-0">
                                    <i data-lucide="signature" class="w-6 h-6"></i>
                                </div>
                                <div class="min-w-0 text-xs">
                                    <div class="font-bold text-amber-900 dark:text-amber-200">
                                        Tanda Tangan Peminjam Belum Ada
                                    </div>
                                    <p class="text-[11px] text-amber-700 dark:text-amber-400 mt-0.5">
                                        Wajib dibubuhkan sebelum mengirimkan berkas pengajuan ke Pengelola BMN.
                                    </p>
                                </div>
                            @endif
                        </div>

                        <div class="shrink-0">
                            @if($hasBorrowerSig)
                                <button type="button" @click="openSigModal('borrower')"
                                        class="px-3.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition shadow-2xs cursor-pointer flex items-center gap-1.5">
                                    <i data-lucide="edit-3" class="w-3.5 h-3.5 text-emerald-600"></i>
                                    <span>Ganti Tanda Tangan</span>
                                </button>
                            @else
                                <button type="button" @click="openSigModal('borrower')"
                                        class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold shadow-sm transition cursor-pointer flex items-center gap-1.5">
                                    <i data-lucide="pen-tool" class="w-3.5 h-3.5"></i>
                                    <span>Bubuhkan Tanda Tangan</span>
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Tombol Aksi Peminjam (Edit Draf & Kirim Berkas) -->
                    <div class="flex flex-wrap items-center gap-3 pt-1">
                        <a href="{{ route('loans.edit', $loan->id) }}"
                           class="px-5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-bold transition shadow-2xs flex items-center gap-2">
                            <i data-lucide="edit-2" class="w-4 h-4 text-[#0B63B5]"></i>
                            <span>Ubah / Edit Draf Pengajuan</span>
                        </a>

                        <form method="POST" action="{{ route('loans.submit', $loan->id) }}">
                            @csrf
                            @if($hasBorrowerSig)
                                <button type="submit"
                                        data-confirm="{{ $isRevision ? 'Apakah Anda yakin perbaikan draf telah selesai dan berkas siap dikirim ulang ke Pengelola BMN?' : 'Apakah Anda yakin berkas pengajuan peminjaman BMN ini sudah lengkap dan siap dikirimkan ke Pengelola BMN?' }}"
                                        data-confirm-title="{{ $isRevision ? 'Kirim Ulang Pengajuan' : 'Kirim Pengajuan Peminjaman' }}"
                                        data-confirm-btn="Ya, Kirim Sekarang"
                                        data-confirm-type="primary"
                                        class="px-6 py-2.5 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white rounded-xl text-xs font-black shadow-md shadow-blue-500/20 transition flex items-center gap-2 cursor-pointer active:scale-95">
                                    <i data-lucide="send" class="w-4 h-4"></i>
                                    <span>{{ $isRevision ? 'Kirim Ulang Pengajuan' : 'Kirim Pengajuan' }}</span>
                                </button>
                            @else
                                <button type="button" disabled
                                        title="Harap bubuhkan tanda tangan peminjam terlebih dahulu sebelum mengirim pengajuan"
                                        class="px-6 py-2.5 bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border border-slate-300 dark:border-slate-700 rounded-xl text-xs font-bold flex items-center gap-2 cursor-not-allowed shadow-inner select-none">
                                    <i data-lucide="send" class="w-4 h-4 opacity-40"></i>
                                    <span>{{ $isRevision ? 'Kirim Ulang Pengajuan' : 'Kirim Pengajuan' }}</span>
                                </button>
                            @endif
                        </form>
                    </div>
                </div>
            @endif

            <!-- Summary Card -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-4">
                    <div>
                        <h2 class="text-lg font-black text-slate-900 dark:text-white">Peminjaman Barang Inventaris Kantor</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">ID Pengajuan: <span class="font-mono">{{ $loan->id }}</span></p>
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                        Diajukan: <strong class="text-slate-800 dark:text-slate-200 tabular-nums">{{ $loan->created_at->format('d/m/Y H:i') }} WITA</strong>
                    </div>
                </div>

                <!-- Borrower Snapshot Details -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <span class="text-slate-400 dark:text-slate-400 font-medium block mb-0.5">Nama Peminjam (Pihak Kedua):</span>
                        <span class="font-bold text-slate-900 dark:text-white text-sm">{{ $loan->snap_full_name }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 dark:text-slate-400 font-medium block mb-0.5">NIP:</span>
                        <span class="font-semibold text-slate-800 dark:text-slate-200 tabular-nums">{{ \App\Helpers\FormatHelper::formatNip($loan->snap_nip) }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 dark:text-slate-400 font-medium block mb-0.5">Pangkat / Golongan:</span>
                        <span class="font-medium text-slate-800 dark:text-slate-200">{{ $loan->snap_rank_grade ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 dark:text-slate-400 font-medium block mb-0.5">Jabatan:</span>
                        <span class="font-medium text-slate-800 dark:text-slate-200">{{ $loan->snap_position ?? '-' }}</span>
                    </div>
                    <div class="sm:col-span-2">
                        <span class="text-slate-400 dark:text-slate-400 font-medium block mb-0.5">Alamat Rumah Tercetak:</span>
                        <span class="font-medium text-slate-800 dark:text-slate-200">{{ $loan->snap_home_address ?? '-' }}</span>
                    </div>
                </div>

                <!-- Period Details -->
                <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/60 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                    <div>
                        <span class="text-slate-400 dark:text-slate-400 font-medium block">Periode Peminjaman:</span>
                        <span class="font-bold text-slate-900 dark:text-white tabular-nums">
                            {{ \App\Helpers\FormatHelper::formatDateIndo($loan->start_date) }} s.d. {{ \App\Helpers\FormatHelper::formatDateIndo($loan->end_date) }}
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-400 dark:text-slate-400 font-medium block">Jenis Penugasan:</span>
                        <span class="font-bold text-[#0B63B5] dark:text-blue-400">
                            {{ $loan->loan_type === 'ANNUAL' ? 'Penugasan Tahunan (ANNUAL)' : 'Sementara (SHORT_TERM)' }}
                        </span>
                    </div>
                </div>

                @if($loan->review_note)
                    <div class="p-3.5 rounded-2xl bg-blue-50/70 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800/60 text-xs leading-relaxed">
                        <strong class="text-[#0B63B5] dark:text-blue-400 block mb-0.5">Catatan Telaah Pengelola BMN:</strong>
                        <p class="text-slate-700 dark:text-slate-200">{{ $loan->review_note }}</p>
                    </div>
                @endif

                @if($loan->decision_note)
                    <div class="p-3.5 rounded-2xl bg-emerald-50/70 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-xs leading-relaxed">
                        <strong class="text-emerald-700 dark:text-emerald-400 block mb-0.5">Catatan Keputusan Ketua Tim:</strong>
                        <p class="text-slate-700 dark:text-slate-200">{{ $loan->decision_note }}</p>
                    </div>
                @endif
            </div>

            <!-- Items Table Card -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-4">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Daftar Barang BMN yang Dipinjam</h3>

                <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-bold uppercase">
                                <th class="py-3 px-3">No</th>
                                <th class="py-3 px-3">Nama Barang</th>
                                <th class="py-3 px-3">Merk / Tipe</th>
                                <th class="py-3 px-3">NUP</th>
                                <th class="py-3 px-3">Tahun & Nilai</th>
                                <th class="py-3 px-3">Keperluan</th>
                                <th class="py-3 px-3 text-center">Kondisi Keluar / Masuk</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            @foreach($loan->items as $idx => $item)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                <td class="py-3 px-3 font-bold text-slate-400 dark:text-slate-500 tabular-nums">{{ $idx + 1 }}</td>
                                <td class="py-3 px-3 font-bold text-slate-900 dark:text-white">{{ $item->snap_item_name }}</td>
                                <td class="py-3 px-3 text-slate-700 dark:text-slate-300">{{ $item->snap_brand_model ?? '-' }}</td>
                                <td class="py-3 px-3 font-semibold text-slate-800 dark:text-slate-200 tabular-nums">{{ $item->snap_nup ?? '-' }}</td>
                                <td class="py-3 px-3 tabular-nums text-slate-700 dark:text-slate-300">{{ $item->snap_year }} &bull; {{ $item->formatted_snap_value }}</td>
                                <td class="py-3 px-3 italic text-slate-600 dark:text-slate-400">{{ $item->purpose }}</td>
                                <td class="py-3 px-3 text-center">
                                    <span class="px-2 py-0.5 rounded bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 font-semibold text-[10px] border border-emerald-200 dark:border-emerald-800">
                                        {{ $item->condition_out ?? 'Baik' }}
                                    </span>
                                    @if($item->condition_in)
                                        <div class="mt-1 text-[10px] font-bold text-slate-600 dark:text-slate-400">
                                            Kembali: <span class="{{ in_array($item->condition_in, ['Rusak', 'Hilang', 'RUSAK', 'TIDAK_LENGKAP_HILANG']) ? 'text-red-600 dark:text-red-400' : 'text-emerald-700 dark:text-emerald-300' }}">{{ $item->condition_in }}</span>
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
            <div class="bg-white dark:bg-[#1E293B] rounded-3xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Dokumen Resmi BMN (Template Baku PDF)</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Tiga dokumen resmi sesuai format sah Balai Besar Komdigi Makassar</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                    <!-- 1. Surat Peminjaman -->
                    @php $docSurat = $loan->documents()->where('doc_type', 'SURAT_PEMINJAMAN')->where('status', 'ISSUED')->first(); @endphp
                    <div class="p-4 rounded-2xl border {{ $docSurat ? 'border-blue-200 dark:border-blue-800/80 bg-blue-50/50 dark:bg-blue-950/40' : 'border-slate-200 dark:border-slate-700/80 bg-slate-50/80 dark:bg-slate-800/60' }} flex flex-col justify-between transition-colors">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider {{ $docSurat ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400 dark:text-slate-400' }} block mb-1">Dokumen 1</span>
                            <h4 class="font-bold text-xs text-slate-900 dark:text-white">Surat Peminjaman</h4>
                            <p class="text-[11px] {{ $docSurat ? 'text-blue-800 dark:text-blue-200 font-semibold' : 'text-slate-500 dark:text-slate-300' }} mt-0.5">
                                {{ $docSurat ? $docSurat->doc_number : 'Terbit saat disetujui Ketua Tim' }}
                            </p>
                        </div>
                        <div class="mt-4">
                            @if($docSurat)
                                <a href="{{ route('document.pdf', [$loan->id, 'SURAT_PEMINJAMAN']) }}" target="_blank"
                                   class="w-full py-2 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-sm active:scale-95">
                                    <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                                    <span>Cetak PDF</span>
                                </a>
                            @else
                                <span class="text-xs text-slate-600 dark:text-slate-300 font-semibold block text-center py-2 bg-slate-100 dark:bg-slate-800/90 rounded-xl border border-slate-200/70 dark:border-slate-700/70 shadow-2xs">
                                    <span class="inline-flex items-center gap-1.5 justify-center">
                                        <i data-lucide="clock" class="w-3.5 h-3.5 text-slate-400"></i>
                                        <span>Belum Diterbitkan</span>
                                    </span>
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- 2. BAST Peminjaman -->
                    @php $docBastPjm = $loan->documents()->where('doc_type', 'BAST_PEMINJAMAN')->where('status', 'ISSUED')->first(); @endphp
                    <div class="p-4 rounded-2xl border {{ $docBastPjm ? 'border-cyan-200 dark:border-cyan-800/80 bg-cyan-50/50 dark:bg-cyan-950/40' : 'border-slate-200 dark:border-slate-700/80 bg-slate-50/80 dark:bg-slate-800/60' }} flex flex-col justify-between transition-colors">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider {{ $docBastPjm ? 'text-cyan-600 dark:text-cyan-400' : 'text-slate-400 dark:text-slate-400' }} block mb-1">Dokumen 2</span>
                            <h4 class="font-bold text-xs text-slate-900 dark:text-white">BAST Peminjaman</h4>
                            <p class="text-[11px] {{ $docBastPjm ? 'text-cyan-800 dark:text-cyan-200 font-semibold' : 'text-slate-500 dark:text-slate-300' }} mt-0.5">
                                {{ $docBastPjm ? $docBastPjm->doc_number : 'Diterbitkan oleh Pengelola BMN' }}
                            </p>
                        </div>
                        <div class="mt-4">
                            @if($docBastPjm)
                                <a href="{{ route('document.pdf', [$loan->id, 'BAST_PEMINJAMAN']) }}" target="_blank"
                                   class="w-full py-2 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-sm active:scale-95">
                                    <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                                    <span>Cetak PDF</span>
                                </a>
                            @else
                                <span class="text-xs text-slate-600 dark:text-slate-300 font-semibold block text-center py-2 bg-slate-100 dark:bg-slate-800/90 rounded-xl border border-slate-200/70 dark:border-slate-700/70 shadow-2xs">
                                    <span class="inline-flex items-center gap-1.5 justify-center">
                                        <i data-lucide="clock" class="w-3.5 h-3.5 text-slate-400"></i>
                                        <span>Belum Diterbitkan</span>
                                    </span>
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- 3. BAST Pengembalian -->
                    @php $docBastKbl = $loan->documents()->where('doc_type', 'BAST_PENGEMBALIAN')->where('status', 'ISSUED')->first(); @endphp
                    <div class="p-4 rounded-2xl border {{ $docBastKbl ? 'border-emerald-200 dark:border-emerald-800/80 bg-emerald-50/50 dark:bg-emerald-950/40' : 'border-slate-200 dark:border-slate-700/80 bg-slate-50/80 dark:bg-slate-800/60' }} flex flex-col justify-between transition-colors">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider {{ $docBastKbl ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-400' }} block mb-1">Dokumen 3</span>
                            <h4 class="font-bold text-xs text-slate-900 dark:text-white">BAST Pengembalian</h4>
                            <p class="text-[11px] {{ $docBastKbl ? 'text-emerald-800 dark:text-emerald-200 font-semibold' : 'text-slate-500 dark:text-slate-300' }} mt-0.5">
                                {{ $docBastKbl ? $docBastKbl->doc_number : 'Terbit saat fisik BMN diperiksa' }}
                            </p>
                        </div>
                        <div class="mt-4">
                            @if($docBastKbl)
                                <a href="{{ route('document.pdf', [$loan->id, 'BAST_PENGEMBALIAN']) }}" target="_blank"
                                   class="w-full py-2 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-sm active:scale-95">
                                    <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                                    <span>Cetak PDF</span>
                                </a>
                            @else
                                <span class="text-xs text-slate-600 dark:text-slate-300 font-semibold block text-center py-2 bg-slate-100 dark:bg-slate-800/90 rounded-xl border border-slate-200/70 dark:border-slate-700/70 shadow-2xs">
                                    <span class="inline-flex items-center gap-1.5 justify-center">
                                        <i data-lucide="clock" class="w-3.5 h-3.5 text-slate-400"></i>
                                        <span>Belum Diterbitkan</span>
                                    </span>
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. SPESIMEN TANDA TANGAN DIGITAL DOKUMEN BAST (PROGRESSIVE TABS - REKOMENDASI A) -->
            @php
                $isReturnCycle = in_array($loan->status, ['RETURN_REQUESTED', 'RETURN_CHECKED', 'RETURNED']);
                $ketuaApproved = in_array($loan->status, ['APPROVED', 'BAST_ISSUED', 'ACTIVE', 'RETURN_REQUESTED', 'RETURN_CHECKED', 'RETURNED']);
                $bastPeminjamanDoc = $loan->documents->where('doc_type', 'BAST_PEMINJAMAN')->first();
                $bastPengembalianDoc = $loan->documents->where('doc_type', 'BAST_PENGEMBALIAN')->first();
            @endphp
            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                @if($isReturnCycle)
                    <!-- Progressive Segmented Tabs Header saat Tahap Pengembalian Aktif -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-blue-50 text-[#0B63B5] flex items-center justify-center shrink-0">
                                <i data-lucide="signature" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <h3 class="font-extrabold text-sm text-slate-900">Kelengkapan Tanda Tangan Dokumen BAST</h3>
                                <p class="text-[11px] text-slate-500">Kelengkapan pengesahan digital untuk BAST Pengembalian dan BAST Peminjaman.</p>
                            </div>
                        </div>

                        <!-- Segmented Tab Pills -->
                        <div class="flex items-center bg-slate-100 dark:bg-slate-800 p-1 rounded-xl self-start sm:self-auto shrink-0 border border-slate-200/80 dark:border-slate-700/80">
                            <button type="button" @click="bastTab = 'return'"
                                    :class="bastTab === 'return' ? 'bg-white dark:bg-[#0B63B5] text-[#0B63B5] dark:text-white font-extrabold shadow-2xs' : 'text-slate-600 dark:text-slate-300 font-semibold hover:text-slate-900 dark:hover:text-white'"
                                    class="px-3 py-1.5 rounded-lg text-xs transition flex items-center gap-1.5 cursor-pointer">
                                <span class="w-2 h-2 rounded-full bg-blue-600" x-show="bastTab === 'return'"></span>
                                <span>BAST Pengembalian</span>
                                <span class="px-1.5 py-0.2 text-[9px] font-extrabold rounded-md bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800 uppercase">Aktif</span>
                            </button>
                            <button type="button" @click="bastTab = 'loan'"
                                    :class="bastTab === 'loan' ? 'bg-white dark:bg-[#0B63B5] text-slate-900 dark:text-white font-extrabold shadow-2xs' : 'text-slate-600 dark:text-slate-300 font-semibold hover:text-slate-900 dark:hover:text-white'"
                                    class="px-3 py-1.5 rounded-lg text-xs transition flex items-center gap-1.5 cursor-pointer">
                                <i data-lucide="archive" class="w-3.5 h-3.5 text-slate-400"></i>
                                <span>BAST Peminjaman</span>
                                <span class="px-1.5 py-0.2 text-[9px] font-extrabold rounded-md bg-slate-200/70 dark:bg-slate-700 text-slate-600 dark:text-slate-300 uppercase">Arsip</span>
                            </button>
                        </div>
                    </div>
                @else
                    <!-- Header Tunggal Terfokus (Masa Peminjaman Aktif / Belum Masuk Pengembalian) -->
                    <div class="flex items-center gap-2.5 border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div class="w-8 h-8 rounded-xl bg-blue-50 text-[#0B63B5] flex items-center justify-center shrink-0">
                            <i data-lucide="signature" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h3 class="font-extrabold text-sm text-slate-900 dark:text-white">Tanda Tangan Digital BAST Peminjaman</h3>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">Tanda tangan digital resmi untuk pengesahan lembar dokumen Berita Acara Serah Terima (BAST).</p>
                        </div>
                    </div>
                @endif

                @if($isReturnCycle)
                    <!-- TAB 1: BAST PENGEMBALIAN (AKTIF) -->
                    <div x-show="bastTab === 'return'" class="space-y-4">
                        <!-- 3 Baris Penuh Lebar Status TTD Pengembalian -->
                        <div class="space-y-2">
                            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5 mb-1">
                                <i data-lucide="users" class="w-3.5 h-3.5 text-slate-400"></i>
                                <span>Status Tanda Tangan BAST Pengembalian:</span>
                            </span>

                            <!-- Baris 1: Pihak I (Petugas Pemeriksa Gudang BMN) -->
                            @if($loan->officer_signature)
                                <div class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold shadow-2xs">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                                        <span>Pihak I (Petugas): Sudah Ditandatangani</span>
                                    </div>
                                    <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 border border-emerald-200">Lengkap</span>
                                </div>
                            @else
                                <div class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-white dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-xs font-medium shadow-2xs">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="clock" class="w-4 h-4 text-slate-400 shrink-0"></i>
                                        <span>Pihak I (Petugas): Belum Ditandatangani</span>
                                    </div>
                                    <span class="text-[10px] font-semibold text-slate-400">Menunggu</span>
                                </div>
                            @endif

                            <!-- Baris 2: Pihak II (Peminjam Pengembali) -->
                            @if($loan->borrower_signature)
                                <div class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold shadow-2xs">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                                        <span>Pihak II (Peminjam): Sudah Ditandatangani</span>
                                    </div>
                                    <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 border border-emerald-200">Lengkap</span>
                                </div>
                            @else
                                <div class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs font-bold shadow-2xs">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="alert-circle" class="w-4 h-4 text-amber-600 shrink-0"></i>
                                        <span>Pihak II (Peminjam): Belum Ditandatangani</span>
                                    </div>
                                    <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-md bg-amber-100 text-amber-800 border border-amber-200">Perlu TTD</span>
                                </div>
                            @endif

                            <!-- Baris 3: Mengetahui (Pengesahan Satker) -->
                            <div class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-blue-50 border border-blue-200 text-[#0B63B5] text-xs font-bold shadow-2xs">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="check-check" class="w-4 h-4 text-[#0B63B5] shrink-0"></i>
                                    <span>Mengetahui: Disahkan Pejabat Aset</span>
                                </div>
                                <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-md bg-blue-100 text-[#0B63B5] border border-blue-200">Otomatis</span>
                            </div>
                        </div>

                        <!-- Kartu Tindakan User TTD Pengembalian -->
                        @if($isOwner)
                            <div class="p-4 rounded-2xl border {{ $loan->borrower_signature ? 'border-emerald-200 dark:border-emerald-800/60 bg-emerald-50/25 dark:bg-emerald-950/20' : 'border-blue-200 dark:border-blue-800/60 bg-blue-50/25 dark:bg-blue-950/20' }} flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div class="flex items-center gap-3.5 min-w-0">
                                    @if($loan->borrower_signature)
                                        <div class="p-2 bg-white signature-paper rounded-xl border border-emerald-200 dark:border-emerald-700 shadow-2xs flex items-center justify-center shrink-0 w-28 h-14" style="background-color: #ffffff !important;">
                                            <img src="{{ asset('storage/' . $loan->borrower_signature) }}" class="max-h-11 max-w-full object-contain" alt="Tanda Tangan Peminjam">
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-bold text-slate-800 dark:text-white">Tanda Tangan Pengembalian Anda (Pihak Kedua)</span>
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 shrink-0">Sudah Ditandatangani</span>
                                            </div>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate mt-0.5">Atas nama: <strong class="text-slate-800 dark:text-white">{{ $loan->snap_full_name }}</strong></p>
                                        </div>
                                    @else
                                        <div class="w-12 h-12 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-slate-400 shrink-0 shadow-2xs">
                                            <i data-lucide="signature" class="w-6 h-6 text-slate-300 dark:text-slate-600"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-bold text-slate-800 dark:text-white">Tanda Tangan Pengembalian Anda (Pihak Kedua)</span>
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200 shrink-0">Belum Ditandatangani</span>
                                            </div>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Bubuhkan tanda tangan untuk lembar BAST pengembalian barang.</p>
                                        </div>
                                    @endif
                                </div>

                                <div class="shrink-0 flex items-center gap-2">
                                    <button type="button" @click="openSigModal('borrower')"
                                            class="w-full sm:w-auto px-4 py-2 {{ $loan->borrower_signature ? 'bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-[#0B63B5] dark:text-blue-400 border border-blue-200 dark:border-slate-700' : 'bg-[#0B63B5] hover:bg-[#0B4F8A] text-white' }} rounded-xl text-xs font-bold shadow-2xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                                        <i data-lucide="{{ $loan->borrower_signature ? 'edit-3' : 'pen-tool' }}" class="w-3.5 h-3.5"></i>
                                        <span>{{ $loan->borrower_signature ? 'Ubah Tanda Tangan' : 'Tanda Tangani BAST Pengembalian' }}</span>
                                    </button>
                                    @if($bastPengembalianDoc)
                                        <a href="{{ route('documents.pdf', [$loan->id, 'BAST_PENGEMBALIAN']) }}" target="_blank"
                                           class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition flex items-center gap-1.5">
                                            <i data-lucide="file-text" class="w-3.5 h-3.5 text-slate-600"></i>
                                            <span>Lihat PDF</span>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @elseif($isPengelola)
                            <div class="p-4 rounded-2xl border {{ $loan->officer_signature ? 'border-emerald-200 bg-emerald-50/25' : 'border-indigo-200 bg-indigo-50/25' }} flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div class="flex items-center gap-3.5 min-w-0">
                                    @if($loan->officer_signature)
                                        <div class="p-2 bg-white rounded-xl border border-emerald-200 shadow-2xs flex items-center justify-center shrink-0 w-28 h-14">
                                            <img src="{{ asset('storage/' . $loan->officer_signature) }}" class="max-h-11 max-w-full object-contain" alt="Tanda Tangan Petugas">
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-bold text-slate-800">Tanda Tangan Petugas Pemeriksa (Pihak Pertama)</span>
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 shrink-0">Sudah Ditandatangani</span>
                                            </div>
                                            <p class="text-[11px] text-slate-500 truncate mt-0.5">Petugas: <strong class="text-slate-800">{{ auth()->user()->name }}</strong></p>
                                        </div>
                                    @else
                                        <div class="w-12 h-12 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-400 shrink-0 shadow-2xs">
                                            <i data-lucide="signature" class="w-6 h-6 text-slate-300"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-bold text-slate-800">Tanda Tangan Petugas Pemeriksa (Pihak Pertama)</span>
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200 shrink-0">Belum Ditandatangani</span>
                                            </div>
                                            <p class="text-[11px] text-slate-500 mt-0.5">Silakan tanda tangani hasil pemeriksaan barang kembali.</p>
                                        </div>
                                    @endif
                                </div>

                                <div class="shrink-0 flex items-center gap-2">
                                    <button type="button" @click="openSigModal('officer')"
                                            class="w-full sm:w-auto px-4 py-2 {{ $loan->officer_signature ? 'bg-white hover:bg-slate-50 text-indigo-700 border border-indigo-200' : 'bg-indigo-600 hover:bg-indigo-700 text-white' }} rounded-xl text-xs font-bold shadow-2xs transition flex items-center justify-center gap-1.5">
                                        <i data-lucide="{{ $loan->officer_signature ? 'edit-3' : 'pen-tool' }}" class="w-3.5 h-3.5"></i>
                                        <span>{{ $loan->officer_signature ? 'Ubah Tanda Tangan' : 'Tanda Tangani BAST Pengembalian' }}</span>
                                    </button>
                                    @if($bastPengembalianDoc)
                                        <a href="{{ route('documents.pdf', [$loan->id, 'BAST_PENGEMBALIAN']) }}" target="_blank"
                                           class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition flex items-center gap-1.5">
                                            <i data-lucide="file-text" class="w-3.5 h-3.5 text-slate-600"></i>
                                            <span>Lihat PDF</span>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-blue-100 text-[#0B63B5] flex items-center justify-center shrink-0">
                                        <i data-lucide="file-check" class="w-4 h-4"></i>
                                    </div>
                                    <div>
                                        <span class="text-xs font-bold text-slate-800 block">Status Dokumen BAST Pengembalian</span>
                                        <p class="text-[11px] text-slate-500">Tanda tangan digital penerimaan kembali BMN dicetak otomatis pada dokumen PDF resmi.</p>
                                    </div>
                                </div>
                                @if($bastPengembalianDoc)
                                    <a href="{{ route('documents.pdf', [$loan->id, 'BAST_PENGEMBALIAN']) }}" target="_blank"
                                       class="px-4 py-2 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white rounded-xl text-xs font-bold shadow-2xs transition flex items-center gap-1.5 shrink-0 self-start sm:self-auto">
                                        <i data-lucide="file-text" class="w-4 h-4"></i>
                                        <span>Buka PDF BAST Pengembalian</span>
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>

                    <!-- TAB 2: BAST PEMINJAMAN (ARSIP SELESAI) -->
                    <div x-show="bastTab === 'loan'" class="space-y-4">
                        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2.5">
                                <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                                <span class="text-xs font-bold text-slate-700">BAST Peminjaman Awal Telah Ditandatangani Lengkap & Disahkan</span>
                            </div>
                            @if($bastPeminjamanDoc)
                                <a href="{{ route('documents.pdf', [$loan->id, 'BAST_PEMINJAMAN']) }}" target="_blank"
                                   class="px-3.5 py-1.5 bg-white hover:bg-slate-50 border border-slate-200 text-[#0B63B5] rounded-xl text-xs font-bold shadow-2xs transition flex items-center gap-1.5 shrink-0">
                                    <i data-lucide="file-text" class="w-3.5 h-3.5 text-[#0B63B5]"></i>
                                    <span>Unduh PDF Arsip</span>
                                </a>
                            @endif
                        </div>

                        <!-- 3 Baris Penuh Lebar Status Arsip Peminjaman -->
                        <div class="space-y-2">
                            <div class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold shadow-2xs">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                                    <span>Pihak I (Petugas): Sudah Ditandatangani</span>
                                </div>
                                <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 border border-emerald-200">Arsip Sah</span>
                            </div>

                            <div class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold shadow-2xs">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                                    <span>Pihak II (Peminjam): Sudah Ditandatangani</span>
                                </div>
                                <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 border border-emerald-200">Arsip Sah</span>
                            </div>

                            <div class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-blue-50 border border-blue-200 text-[#0B63B5] text-xs font-bold shadow-2xs">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="check-check" class="w-4 h-4 text-[#0B63B5] shrink-0"></i>
                                    <span>Mengetahui: Disahkan Ketua Tim</span>
                                </div>
                                <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-md bg-blue-100 text-[#0B63B5] border border-blue-200">Disahkan</span>
                            </div>
                        </div>

                        <!-- Tampilan Spesimen Tanda Tangan Anda (Khusus User Sendiri) -->
                        @if($isOwner && $loan->borrower_signature)
                            <div class="p-3.5 rounded-2xl bg-white border border-slate-200 flex items-center gap-3">
                                <div class="p-2 bg-slate-50 rounded-xl border border-slate-200 shadow-2xs flex items-center justify-center shrink-0 w-24 h-12">
                                    <img src="{{ asset('storage/' . $loan->borrower_signature) }}" class="max-h-9 max-w-full object-contain" alt="Tanda Tangan Peminjam">
                                </div>
                                <div class="text-xs">
                                    <span class="font-bold text-slate-800 block">Tanda Tangan Anda (Pihak Kedua)</span>
                                    <span class="text-[11px] text-slate-500">Tercatat sah pada {{ $loan->snap_full_name }}</span>
                                </div>
                            </div>
                        @elseif($isPengelola && $loan->officer_signature)
                            <div class="p-3.5 rounded-2xl bg-white border border-slate-200 flex items-center gap-3">
                                <div class="p-2 bg-slate-50 rounded-xl border border-slate-200 shadow-2xs flex items-center justify-center shrink-0 w-24 h-12">
                                    <img src="{{ asset('storage/' . $loan->officer_signature) }}" class="max-h-9 max-w-full object-contain" alt="Tanda Tangan Petugas">
                                </div>
                                <div class="text-xs">
                                    <span class="font-bold text-slate-800 block">Tanda Tangan Petugas (Pihak Pertama)</span>
                                    <span class="text-[11px] text-slate-500">Tercatat sah pada {{ auth()->user()->name }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    <!-- TAMPILAN TUNGGAL TERFOKUS (SIKLUS NORMAL PEMINJAMAN) -->
                    <!-- Kelengkapan Tanda Tangan Dokumen: 3 Baris Penuh Lebar (w-full) -->
                    <div class="space-y-2">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5 mb-1">
                            <i data-lucide="users" class="w-3.5 h-3.5 text-slate-400"></i>
                            <span>Kelengkapan Tanda Tangan Dokumen:</span>
                        </span>

                        <!-- Baris 1: Pihak I (Petugas Pengelola BMN) -->
                        @if($loan->officer_signature)
                            <div class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold shadow-2xs">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                                    <span>Pihak I (Petugas): Sudah Ditandatangani</span>
                                </div>
                                <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 border border-emerald-200">Lengkap</span>
                            </div>
                        @else
                            <div class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-white dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-xs font-medium shadow-2xs">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="clock" class="w-4 h-4 text-slate-400 dark:text-slate-400 shrink-0"></i>
                                    <span>Pihak I (Petugas): Belum Ditandatangani</span>
                                </div>
                                <span class="text-[10px] font-semibold text-slate-400 dark:text-slate-400">Menunggu</span>
                            </div>
                        @endif

                        <!-- Baris 2: Pihak II (Peminjam BMN) -->
                        @if($loan->borrower_signature)
                            <div class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold shadow-2xs">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                                    <span>Pihak II (Peminjam): Sudah Ditandatangani</span>
                                </div>
                                <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 border border-emerald-200">Lengkap</span>
                            </div>
                        @else
                            <div class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs font-bold shadow-2xs">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="alert-circle" class="w-4 h-4 text-amber-600 shrink-0"></i>
                                    <span>Pihak II (Peminjam): Belum Ditandatangani</span>
                                </div>
                                <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-md bg-amber-100 text-amber-800 border border-amber-200">Perlu TTD</span>
                            </div>
                        @endif

                        <!-- Baris 3: Mengetahui (Ketua Tim Keuangan & Aset) -->
                        @if($ketuaApproved)
                            <div class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-blue-50 border border-blue-200 text-[#0B63B5] text-xs font-bold shadow-2xs">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="check-check" class="w-4 h-4 text-[#0B63B5] shrink-0"></i>
                                    <span>Mengetahui: Disahkan Ketua Tim</span>
                                </div>
                                <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-md bg-blue-100 text-[#0B63B5] border border-blue-200">Disahkan</span>
                            </div>
                        @else
                            <div class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-white dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 text-xs font-medium shadow-2xs">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="clock" class="w-4 h-4 text-slate-400 shrink-0"></i>
                                    <span>Mengetahui: Menunggu Disposisi</span>
                                </div>
                                <span class="text-[10px] font-semibold text-slate-400 dark:text-slate-400">Menunggu</span>
                            </div>
                        @endif
                    </div>

                    <!-- Kartu Informasi & Tindakan Tanda Tangan Anda (Versi Simpel & Ringkas) -->
                    @if($isOwner)
                        <!-- Tanda Tangan Peminjam (Pihak Kedua) -->
                        <div class="p-4 rounded-2xl border {{ $loan->borrower_signature ? 'border-emerald-200 dark:border-emerald-800/60 bg-emerald-50/25 dark:bg-emerald-950/20' : 'border-blue-200 dark:border-blue-800/60 bg-blue-50/25 dark:bg-blue-950/20' }} flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-center gap-3.5 min-w-0">
                                @if($loan->borrower_signature)
                                    <div class="p-2 bg-white signature-paper rounded-xl border border-emerald-200 dark:border-emerald-700 shadow-2xs flex items-center justify-center shrink-0 w-28 h-14" style="background-color: #ffffff !important;">
                                        <img src="{{ asset('storage/' . $loan->borrower_signature) }}" class="max-h-11 max-w-full object-contain" alt="Tanda Tangan Peminjam">
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-slate-800 dark:text-white">Tanda Tangan Anda (Pihak Kedua)</span>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 shrink-0">Sudah Ditandatangani</span>
                                        </div>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate mt-0.5">Atas nama: <strong class="text-slate-800 dark:text-white">{{ $loan->snap_full_name }}</strong></p>
                                    </div>
                                @else
                                    <div class="w-12 h-12 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-slate-400 shrink-0 shadow-2xs">
                                        <i data-lucide="signature" class="w-6 h-6 text-slate-300 dark:text-slate-600"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-slate-800 dark:text-white">Tanda Tangan Anda (Pihak Kedua)</span>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200 shrink-0">Belum Ditandatangani</span>
                                        </div>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Silakan bubuhkan tanda tangan untuk dokumen BAST peminjaman.</p>
                                    </div>
                                @endif
                            </div>

                            <div class="shrink-0 flex items-center">
                                @if($loan->borrower_signature)
                                    <button type="button" @click="openSigModal('borrower')"
                                            class="w-full sm:w-auto px-4 py-2 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-[#0B63B5] dark:text-blue-400 border border-blue-200 dark:border-slate-700 rounded-xl text-xs font-bold shadow-2xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                                        <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                        <span>Ubah Tanda Tangan</span>
                                    </button>
                                @else
                                    <button type="button" @click="openSigModal('borrower')"
                                            class="w-full sm:w-auto px-4 py-2 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white rounded-xl text-xs font-bold shadow-sm transition flex items-center justify-center gap-1.5 cursor-pointer">
                                        <i data-lucide="pen-tool" class="w-3.5 h-3.5"></i>
                                        <span>Tanda Tangani BAST Sekarang</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @elseif($isPengelola)
                        <!-- Tanda Tangan Petugas Pengelola BMN (Pihak Pertama) -->
                        <div class="p-4 rounded-2xl border {{ $loan->officer_signature ? 'border-emerald-200 dark:border-emerald-800/60 bg-emerald-50/25 dark:bg-emerald-950/20' : 'border-indigo-200 dark:border-indigo-800/60 bg-indigo-50/25 dark:bg-indigo-950/20' }} flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-center gap-3.5 min-w-0">
                                @if($loan->officer_signature)
                                    <div class="p-2 bg-white signature-paper rounded-xl border border-emerald-200 dark:border-emerald-700 shadow-2xs flex items-center justify-center shrink-0 w-28 h-14" style="background-color: #ffffff !important;">
                                        <img src="{{ asset('storage/' . $loan->officer_signature) }}" class="max-h-11 max-w-full object-contain" alt="Tanda Tangan Petugas">
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-slate-800 dark:text-white">Tanda Tangan Anda (Pihak Pertama)</span>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 shrink-0">Sudah Ditandatangani</span>
                                        </div>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate mt-0.5">Petugas: <strong class="text-slate-800 dark:text-white">{{ auth()->user()->name }}</strong></p>
                                    </div>
                                @else
                                    <div class="w-12 h-12 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-slate-400 shrink-0 shadow-2xs">
                                        <i data-lucide="signature" class="w-6 h-6 text-slate-300 dark:text-slate-600"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-slate-800 dark:text-white">Tanda Tangan Anda (Pihak Pertama)</span>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200 shrink-0">Belum Ditandatangani</span>
                                        </div>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Silakan bubuhkan tanda tangan petugas penyerah barang.</p>
                                    </div>
                                @endif
                            </div>

                            <div class="shrink-0 flex items-center">
                                @if($loan->officer_signature)
                                    <button type="button" @click="openSigModal('officer')"
                                            class="w-full sm:w-auto px-4 py-2 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-slate-700 rounded-xl text-xs font-bold shadow-2xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                                        <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                        <span>Ubah Tanda Tangan</span>
                                    </button>
                                @else
                                    <button type="button" @click="openSigModal('officer')"
                                            class="w-full sm:w-auto px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition flex items-center justify-center gap-1.5 cursor-pointer">
                                        <i data-lucide="pen-tool" class="w-3.5 h-3.5"></i>
                                        <span>Tanda Tangani BAST Sekarang</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @elseif($isKetuaTim)
                        <!-- Otoritas Pengesahan Ketua Tim (Mengetahui) -->
                        <div class="p-4 rounded-2xl border border-blue-200 bg-blue-50/25 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-center gap-3.5 min-w-0">
                                @if(auth()->user()->signature_path)
                                    <div class="p-2 bg-white rounded-xl border border-blue-200 shadow-2xs flex items-center justify-center shrink-0 w-28 h-14">
                                        <img src="{{ asset('storage/' . auth()->user()->signature_path) }}" class="max-h-11 max-w-full object-contain" alt="Tanda Tangan Ketua Tim">
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-slate-800">Spesimen Pengesahan (Mengetahui)</span>
                                            @if($ketuaApproved)
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 shrink-0">Sudah Disahkan</span>
                                            @else
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200 shrink-0">Menunggu Disposisi</span>
                                            @endif
                                        </div>
                                        <p class="text-[11px] text-slate-500 truncate mt-0.5">Ketua Tim: <strong class="text-slate-800">{{ auth()->user()->name }}</strong></p>
                                    </div>
                                @else
                                    <div class="w-12 h-12 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-400 shrink-0 shadow-2xs">
                                        <i data-lucide="signature" class="w-6 h-6 text-slate-300"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-slate-800">Spesimen Pengesahan (Mengetahui)</span>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200 shrink-0">Belum Ada di Profil</span>
                                        </div>
                                        <p class="text-[11px] text-slate-500 mt-0.5">Tanda tangan diambil otomatis dari profil akun Anda.</p>
                                    </div>
                                @endif
                            </div>

                            <div class="shrink-0 flex items-center">
                                <a href="{{ route('profile.show', ['tab' => 'signature']) }}" class="w-full sm:w-auto px-4 py-2 bg-white hover:bg-slate-50 text-[#0B63B5] border border-blue-200 rounded-xl text-xs font-bold shadow-2xs transition flex items-center justify-center gap-1.5">
                                    <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                    <span>Kelola di Profil</span>
                                </a>
                            </div>
                        </div>
                    @else
                        <!-- Mode Pengawas / Admin -->
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-blue-100 text-[#0B63B5] flex items-center justify-center shrink-0">
                                    <i data-lucide="file-check" class="w-4 h-4"></i>
                                </div>
                                <div>
                                    <span class="text-xs font-bold text-slate-800 block">Tinjauan Penandatanganan Dokumen BAST (Mode Administrator)</span>
                                    <p class="text-[11px] text-slate-500">Tanda tangan masing-masing pihak telah tersimpan aman dan dicetak utuh pada lembar dokumen PDF BAST.</p>
                                </div>
                            </div>
                            @if($loan->status !== 'DRAFT' && $loan->status !== 'SUBMITTED' && $loan->status !== 'UNDER_APPROVAL')
                                <a href="{{ route('documents.pdf', [$loan->id, 'BAST_PEMINJAMAN']) }}" target="_blank"
                                   class="px-4 py-2 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white rounded-xl text-xs font-bold shadow-2xs transition flex items-center gap-1.5 shrink-0 self-start sm:self-auto">
                                    <i data-lucide="file-text" class="w-4 h-4"></i>
                                    <span>Buka PDF BAST</span>
                                </a>
                            @endif
                        </div>
                    @endif
                @endif
            </div>
            <!-- 3. DOKUMENTASI FOTO BAST (SERAH TERIMA & PENGEMBALIAN) -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center shrink-0">
                            <i data-lucide="images" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h3 class="font-extrabold text-sm text-slate-900">Lampiran Foto Fisik Dokumen BAST</h3>
                            <p class="text-[11px] text-slate-500">Pratinjau foto serah terima & inspeksi pengembalian yang dicetak pada lembar lampiran PDF resmi BAST.</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        @if($loan->photos->isNotEmpty())
                            <span class="text-xs font-bold px-3 py-1 bg-purple-50 text-purple-700 border border-purple-200 rounded-xl">
                                {{ $loan->photos->count() }} Foto Terlampir
                            </span>
                        @endif
                        @if($isPengelola && in_array($loan->status, ['APPROVED', 'BAST_ISSUED', 'ACTIVE', 'RETURN_REQUESTED', 'RETURN_CHECKED', 'RETURNED']))
                            <button type="button" @click="openPhotoModal('BAST_PEMINJAMAN')"
                                    class="text-[11px] text-slate-500 hover:text-[#0B63B5] font-semibold flex items-center gap-1 px-2.5 py-1 rounded-lg hover:bg-slate-50 transition" title="Tambah foto dokumentasi susulan">
                                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                <span>Foto Susulan</span>
                            </button>
                        @endif
                    </div>
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
                    <div class="p-6 rounded-2xl bg-slate-50/80 border border-slate-200 text-center space-y-2">
                        <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto">
                            <i data-lucide="images" class="w-5 h-5"></i>
                        </div>
                        <h4 class="text-xs font-bold text-slate-700">Belum Ada Foto Terlampir di Dokumen BAST</h4>
                        <p class="text-[11px] text-slate-500 max-w-md mx-auto leading-relaxed">
                            Foto fisik barang akan otomatis tampil di sini dan dicetak pada lembar lampiran PDF BAST setelah diunggah oleh Pengelola BMN saat penerbitan dokumen BAST Peminjaman atau saat pemeriksaan fisik pengembalian.
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
                                class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold shadow-md transition flex items-center gap-2 cursor-pointer">
                            <i data-lucide="package-open" class="w-4 h-4"></i>
                            <span>Ajukan Pengembalian Fisik BMN ke Kantor</span>
                        </button>
                    </form>
                </div>
            @endif

            <!-- 2. Action Pengelola BMN: Telaah Pengajuan (SUBMITTED) -->
            @if($loan->status === 'SUBMITTED' && ($isPengelola || $user->isAdmin()))
                @php
                    $officerSigPath = $loan->officer_signature ?: (auth()->user()->signature_path ?? null);
                    $hasOfficerSig = !empty($officerSigPath) && \Illuminate\Support\Facades\Storage::disk('public')->exists($officerSigPath);
                @endphp
                <div class="bg-white dark:bg-slate-900 rounded-3xl border-2 border-blue-200 dark:border-blue-800 p-6 shadow-sm space-y-4">
                    <div class="flex items-center gap-2 text-[#0B63B5] dark:text-blue-400">
                        <i data-lucide="clipboard-check" class="w-5 h-5"></i>
                        <h3 class="text-base font-black text-slate-900 dark:text-white">Tindakan Pengelola BMN (SOP Peminjaman Langkah 2 & 3)</h3>
                    </div>
                    <p class="text-xs text-slate-600 dark:text-slate-300">
                        Periksa kelengkapan form, ketersediaan barang di gudang, dan kondisi fisik barang (Target SLA: 10 menit).
                    </p>

                    <!-- Status Tanda Tangan Petugas Pengelola BMN -->
                    <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border {{ $hasOfficerSig ? 'border-emerald-200 dark:border-emerald-800/60' : 'border-amber-200 dark:border-amber-800/60' }} flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs">
                        <div class="flex items-center gap-3 min-w-0">
                            @if($hasOfficerSig)
                                <div class="w-24 h-12 bg-white signature-paper rounded-xl border border-emerald-200 dark:border-emerald-700 p-1 flex items-center justify-center shrink-0 shadow-2xs" style="background-color: #ffffff !important;">
                                    <img src="{{ asset('storage/' . $officerSigPath) }}" alt="TTD Pengelola" class="max-h-full max-w-full object-contain">
                                </div>
                                <div class="min-w-0 text-xs">
                                    <div class="flex items-center gap-1.5 font-bold text-slate-800 dark:text-white">
                                        <span>Tanda Tangan Pengelola Terpasang</span>
                                        <i data-lucide="check-circle-2" class="w-3.5 h-3.5 text-emerald-600"></i>
                                    </div>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                        Petugas: <strong>{{ auth()->user()->name }}</strong>
                                    </p>
                                </div>
                            @else
                                <div class="w-12 h-12 rounded-xl bg-amber-100 dark:bg-amber-900/60 text-amber-700 dark:text-amber-400 flex items-center justify-center shrink-0">
                                    <i data-lucide="signature" class="w-6 h-6"></i>
                                </div>
                                <div class="min-w-0 text-xs">
                                    <div class="font-bold text-amber-900 dark:text-amber-200">
                                        Tanda Tangan Pengelola BMN Belum Ada
                                    </div>
                                    <p class="text-[11px] text-amber-700 dark:text-amber-400 mt-0.5">
                                        Wajib dibubuhkan sebelum meneruskan berkas telaah ke Ketua Tim Keuangan & Aset.
                                    </p>
                                </div>
                            @endif
                        </div>

                        <div class="shrink-0">
                            @if($hasOfficerSig)
                                <button type="button" @click="openSigModal('officer')"
                                        class="px-3.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition shadow-2xs cursor-pointer flex items-center gap-1.5">
                                    <i data-lucide="edit-3" class="w-3.5 h-3.5 text-emerald-600"></i>
                                    <span>Ganti Tanda Tangan</span>
                                </button>
                            @else
                                <button type="button" @click="openSigModal('officer')"
                                        class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold shadow-sm transition cursor-pointer flex items-center gap-1.5">
                                    <i data-lucide="pen-tool" class="w-3.5 h-3.5"></i>
                                    <span>Bubuhkan Tanda Tangan</span>
                                </button>
                            @endif
                        </div>
                    </div>

                    <form method="POST" action="{{ route('kelola.review', $loan->id) }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Catatan Penelaahan</label>
                            <textarea name="note" rows="2" class="w-full p-3 rounded-xl border border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs font-medium outline-none focus:border-blue-500"
                                      placeholder="Tuliskan catatan kelengkapan dokumen atau alasan perbaikan jika dikembalikan..."></textarea>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            @if($hasOfficerSig)
                                <button type="submit" name="action" value="approve_review"
                                        data-confirm="Apakah seluruh dokumen dan ketersediaan fisik BMN telah sesuai untuk diteruskan ke Ketua Tim?"
                                        data-confirm-title="Teruskan ke Ketua Tim"
                                        data-confirm-btn="Ya, Teruskan Berkas"
                                        data-confirm-type="primary"
                                        class="px-5 py-2.5 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white rounded-xl text-xs font-bold shadow-sm transition flex items-center gap-1.5 cursor-pointer">
                                    <i data-lucide="arrow-right-circle" class="w-4 h-4"></i>
                                    <span>Teruskan ke Ketua Tim (Dokumen Lengkap)</span>
                                </button>
                            @else
                                <button type="button" disabled
                                        title="Harap bubuhkan tanda tangan Pengelola BMN terlebih dahulu sebelum meneruskan berkas ke Ketua Tim"
                                        class="px-5 py-2.5 bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border border-slate-300 dark:border-slate-700 rounded-xl text-xs font-bold flex items-center gap-1.5 cursor-not-allowed shadow-inner select-none">
                                    <i data-lucide="arrow-right-circle" class="w-4 h-4 opacity-40"></i>
                                    <span>Teruskan ke Ketua Tim (Dokumen Lengkap)</span>
                                </button>
                            @endif

                            <button type="submit" name="action" value="request_revision"
                                    data-confirm="Apakah Anda yakin ingin mengembalikan pengajuan ini ke peminjam untuk diperbaiki?"
                                    data-confirm-title="Kembalikan untuk Perbaikan"
                                    data-confirm-btn="Ya, Kembalikan"
                                    data-confirm-type="warning"
                                    class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
                                <i data-lucide="undo-2" class="w-4 h-4"></i>
                                <span>Kembalikan untuk Diperbaiki</span>
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            <!-- Action Pengelola BMN: Pengajuan Dalam Perbaikan / Draf (NEEDS_REVISION / DRAFT) -->
            @if(in_array($loan->status, ['NEEDS_REVISION', 'DRAFT']) && ($isPengelola || $user->isAdmin()))
                <div class="bg-white dark:bg-slate-900 rounded-3xl border-2 border-amber-200 dark:border-amber-800 p-6 shadow-sm space-y-4">
                    <div class="flex items-center gap-2.5 text-amber-700 dark:text-amber-400">
                        <i data-lucide="help-circle" class="w-5 h-5"></i>
                        <h3 class="text-base font-black text-slate-900 dark:text-white">
                            {{ $loan->status === 'NEEDS_REVISION' ? 'Tindakan Pengelola BMN: Menunggu Perbaikan Peminjam' : 'Tindakan Pengelola BMN: Berkas Draf Peminjam' }}
                        </h3>
                    </div>
                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                        {{ $loan->status === 'NEEDS_REVISION'
                            ? 'Pengajuan ini sedang menunggu peminjam untuk memperbaiki draf sesuai catatan telaah. Apabila peminjam tidak kunjung memperbaiki atau membatalkan rencana peminjaman, Pengelola BMN dapat menghapus pengajuan ini agar barang kembali berstatus Tersedia untuk dipinjam pegawai lain.'
                            : 'Pengajuan ini masih berupa draf yang belum dikirim resmi oleh peminjam. Pengelola BMN dapat menghapus pengajuan ini jika diperlukan agar barang inventaris kantor kembali berstatus Tersedia.' }}
                    </p>
                    <div class="pt-1">
                        <form method="POST" action="{{ route('kelola.destroy', $loan->id) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    data-confirm="Apakah Anda yakin ingin menghapus berkas pengajuan ini? Tindakan ini akan membatalkan berkas pengajuan dan mengembalikan seluruh barang inventaris terkait agar berstatus Tersedia untuk dipinjam pegawai lain."
                                    data-confirm-title="Hapus Berkas Pengajuan BMN?"
                                    data-confirm-btn="Ya, Hapus Pengajuan"
                                    data-confirm-cancel="Batal"
                                    data-confirm-type="danger"
                                    class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs font-bold shadow-sm transition flex items-center gap-2 cursor-pointer">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                <span>Hapus Pengajuan & Kembalikan Ketersediaan Barang</span>
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            <!-- Action Pengelola BMN: Berkas Menunggu Persetujuan Ketua Tim (UNDER_APPROVAL) -->
            @if($loan->status === 'UNDER_APPROVAL' && ($isPengelola || $user->isAdmin()))
                <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                            <i data-lucide="hourglass" class="w-4 h-4"></i>
                        </div>
                        <div class="text-xs">
                            <span class="font-bold text-slate-800 dark:text-white block">Berkas Sedang Menunggu Persetujuan Disposisi Ketua Tim</span>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">Pengelola masih dapat membatalkan/menghapus pengajuan sebelum disetujui Ketua Tim jika terdapat pembatalan tugas.</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('kelola.destroy', $loan->id) }}" class="shrink-0">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                data-confirm="Apakah Anda yakin ingin menghapus berkas pengajuan ini sebelum persetujuan disposisi Ketua Tim? Barang akan kembali berstatus Tersedia."
                                data-confirm-title="Hapus Berkas Pengajuan BMN?"
                                data-confirm-btn="Ya, Hapus Pengajuan"
                                data-confirm-cancel="Batal"
                                data-confirm-type="danger"
                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs font-bold shadow-2xs transition flex items-center gap-1.5 cursor-pointer">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            <span>Hapus Pengajuan</span>
                        </button>
                    </form>
                </div>
            @endif

            <!-- 3. Action Pengelola BMN: Terbitkan BAST (APPROVED) -->
            @if($loan->status === 'APPROVED' && $isPengelola)
                <div class="bg-white rounded-3xl border-2 border-cyan-300 p-6 shadow-sm space-y-4">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-cyan-100 text-cyan-700 flex items-center justify-center shrink-0">
                            <i data-lucide="file-plus" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-black text-slate-900">Penerbitan BAST Peminjaman (SOP Langkah 5)</h3>
                            <p class="text-xs text-slate-600">
                                Disposisi persetujuan telah diberikan oleh Ketua Tim. Silakan buatkan dokumen BAST Peminjaman resmi bernomor.
                            </p>
                        </div>
                    </div>

                    @php
                        $bastPjmPhotos = $loan->photos()->where('stage', 'BAST_PEMINJAMAN')->get();
                        $hasOfficerSig = !empty($loan->officer_signature) && \Illuminate\Support\Facades\Storage::disk('public')->exists($loan->officer_signature);
                        if (!$hasOfficerSig && !empty(auth()->user()->signature_path) && \Illuminate\Support\Facades\Storage::disk('public')->exists(auth()->user()->signature_path)) {
                            $hasOfficerSig = true;
                        }
                    @endphp

                    <form method="POST" action="{{ route('kelola.issue-bast', $loan->id) }}" enctype="multipart/form-data" class="space-y-4"
                          x-data="{
                              existingPhotos: {{ $bastPjmPhotos->count() }},
                              selectedCount: 0,
                              previewUrls: [],
                              maxPhotos: 5,
                              handleFiles(input) {
                                  const files = Array.from(input.files || []);
                                  if (files.length === 0) return;
                                  if (this.existingPhotos + files.length > this.maxPhotos) {
                                      alert('Maksimal total 5 foto fisik dokumentasi BAST. Sistem membatasi foto yang dipilih.');
                                  }
                                  const validFiles = files.slice(0, Math.max(1, this.maxPhotos - this.existingPhotos));
                                  this.selectedCount = validFiles.length;
                                  this.previewUrls = [];
                                  validFiles.forEach(file => {
                                      const reader = new FileReader();
                                      reader.onload = (e) => { this.previewUrls.push(e.target.result); };
                                      reader.readAsDataURL(file);
                                  });
                                  this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                              },
                              get canSubmit() {
                                  return (this.existingPhotos > 0 || this.selectedCount > 0);
                              }
                          }">
                        @csrf

                        <!-- Status Tanda Tangan Petugas Pengelola BMN (Wajib) -->
                        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border {{ $hasOfficerSig ? 'border-emerald-200 dark:border-emerald-800/60' : 'border-amber-200 dark:border-amber-800/60' }} shadow-2xs space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                    <i data-lucide="signature" class="w-4 h-4 {{ $hasOfficerSig ? 'text-emerald-600' : 'text-amber-600' }}"></i>
                                    Tanda Tangan Petugas Pengelola BMN (Pihak Pertama)
                                </span>
                                <span class="text-[10px] font-bold uppercase tracking-wider {{ $hasOfficerSig ? 'text-emerald-700 bg-emerald-50 border-emerald-200 dark:text-emerald-300 dark:bg-emerald-950/60 dark:border-emerald-800/60' : 'text-amber-700 bg-amber-50 border-amber-200 dark:text-amber-300 dark:bg-amber-950/60 dark:border-amber-800/60' }} px-2 py-0.5 rounded-full border">
                                    {{ $hasOfficerSig ? 'Sudah Terpasang' : 'Wajib Dibubuhkan' }}
                                </span>
                            </div>

                            @if($hasOfficerSig)
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 rounded-xl bg-emerald-50/40 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/70">
                                    <div class="flex items-center gap-3.5 min-w-0">
                                        <div class="w-28 h-14 bg-white signature-paper border border-emerald-200 dark:border-emerald-700 rounded-xl p-1.5 flex items-center justify-center shrink-0 shadow-2xs" style="background-color: #ffffff !important;">
                                            <img src="{{ asset('storage/' . ($loan->officer_signature ?: auth()->user()->signature_path)) }}" alt="TTD Petugas" class="max-h-full max-w-full object-contain">
                                        </div>
                                        <div class="text-xs min-w-0">
                                            <span class="font-bold text-slate-900 dark:text-white block">Tanda Tangan Siap Dicetak</span>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Petugas: <strong>{{ auth()->user()->name }}</strong></p>
                                        </div>
                                    </div>
                                    <button type="button" @click="openSigModal('officer')"
                                            class="inline-flex items-center justify-center gap-1.5 px-3.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition shadow-2xs cursor-pointer shrink-0">
                                        <i data-lucide="edit-3" class="w-3.5 h-3.5 text-emerald-600"></i>
                                        <span>Ganti TTD</span>
                                    </button>
                                </div>
                            @else
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 rounded-xl bg-amber-50/80 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/60">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-400 flex items-center justify-center shrink-0">
                                            <i data-lucide="alert-circle" class="w-5 h-5"></i>
                                        </div>
                                        <div class="text-xs">
                                            <p class="font-bold text-amber-900 dark:text-amber-200">Belum Ada Tanda Tangan Petugas</p>
                                            <p class="text-[11px] text-amber-700 dark:text-amber-300 mt-0.5">BAST Peminjaman tidak boleh terbit tanpa tanda tangan resmi petugas penyerah barang.</p>
                                        </div>
                                    </div>
                                    <button type="button" @click="openSigModal('officer')"
                                            class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-xs transition cursor-pointer shrink-0">
                                        <i data-lucide="pen-tool" class="w-4 h-4"></i>
                                        <span>Bubuhkan TTD Petugas</span>
                                    </button>
                                </div>
                            @endif
                        </div>

                        <!-- Foto Fisik BMN Bukti Dukung BAST (Wajib Dilampirkan) -->
                        <div class="p-4 rounded-2xl bg-cyan-50/70 dark:bg-cyan-950/40 border border-cyan-200 dark:border-cyan-800/60 space-y-2.5">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold text-cyan-950 dark:text-cyan-200 flex items-center gap-1.5">
                                    <i data-lucide="camera" class="w-4 h-4 text-cyan-600 dark:text-cyan-400"></i>
                                    <span>Foto Fisik BMN Bukti Dukung BAST (Wajib Dilampirkan):</span>
                                </label>
                                @if($bastPjmPhotos->isNotEmpty())
                                    <span class="px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/70 text-emerald-800 dark:text-emerald-300 text-[10px] font-bold border border-emerald-300 dark:border-emerald-800 flex items-center gap-1">
                                        <i data-lucide="check" class="w-3 h-3"></i>
                                        {{ $bastPjmPhotos->count() }} Foto Terlampir
                                    </span>
                                @endif
                            </div>
                            <p class="text-[11px] text-cyan-800 dark:text-cyan-300">
                                @if($bastPjmPhotos->isNotEmpty())
                                    Sistem telah memiliki {{ $bastPjmPhotos->count() }} foto fisik untuk pengajuan ini. Anda dapat menambah foto baru (Maksimal total 5 foto).
                                @else
                                    Sesuai tata kelola akuntabilitas BMN, wajib melampirkan minimal 1 foto fisik barang sebagai bukti sah kondisi awal sebelum BAST Peminjaman resmi diterbitkan (Maks. 5 foto).
                                @endif
                            </p>

                            @if($bastPjmPhotos->isNotEmpty())
                                <div class="space-y-1 pt-1 pb-2 border-b border-cyan-200/60 dark:border-cyan-800/60">
                                    <span class="text-[10px] text-cyan-900 dark:text-cyan-200 font-bold block">Foto Yang Sudah Tersimpan:</span>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($bastPjmPhotos as $p)
                                            <div class="relative w-16 h-16 rounded-xl overflow-hidden border border-emerald-300 dark:border-emerald-700 bg-white shadow-2xs">
                                                <img src="{{ asset('storage/' . $p->file_path) }}" alt="Foto BAST" class="w-full h-full object-cover">
                                                <span class="absolute bottom-0 right-0 bg-emerald-700 text-white text-[8px] px-1 font-bold">Tersimpan</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                <label class="cursor-pointer px-3.5 py-1.5 bg-white dark:bg-slate-900 border border-cyan-300 dark:border-cyan-700 hover:bg-cyan-50 dark:hover:bg-cyan-900/40 text-cyan-800 dark:text-cyan-200 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-2xs">
                                    <i data-lucide="camera" class="w-3.5 h-3.5 text-cyan-600 dark:text-cyan-400"></i>
                                    <span>Jepret Kamera</span>
                                    <input type="file" name="photos[]" accept="image/*" capture="environment" class="hidden" @change="handleFiles($event.target)">
                                </label>
                                <label class="cursor-pointer px-3.5 py-1.5 bg-white dark:bg-slate-900 border border-cyan-300 dark:border-cyan-700 hover:bg-cyan-50 dark:hover:bg-cyan-900/40 text-cyan-800 dark:text-cyan-200 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-2xs">
                                    <i data-lucide="images" class="w-3.5 h-3.5 text-cyan-600 dark:text-cyan-400"></i>
                                    <span>Pilih dari Galeri</span>
                                    <input type="file" name="photos[]" accept="image/*" multiple class="hidden" @change="handleFiles($event.target)">
                                </label>
                                <span class="text-xs font-medium"
                                      :class="selectedCount > 0 ? 'text-emerald-700 dark:text-emerald-300 font-bold' : 'text-slate-500 dark:text-slate-400 italic'"
                                      x-text="selectedCount > 0 ? selectedCount + ' foto baru dipilih (Maks. 5 foto)' : (existingPhotos > 0 ? existingPhotos + ' foto sudah tersimpan di sistem' : 'Belum ada foto dipilih')">
                                </span>
                            </div>

                            <!-- Preview Thumbnail Foto Baru Terpilih -->
                            <template x-if="previewUrls.length > 0">
                                <div class="space-y-1.5 pt-2 border-t border-cyan-200/50 dark:border-cyan-800/50">
                                    <span class="text-[10px] text-cyan-900 dark:text-cyan-200 font-bold block">Preview Foto Terpilih:</span>
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="(url, idx) in previewUrls" :key="idx">
                                            <div class="relative w-16 h-16 rounded-xl overflow-hidden border-2 border-cyan-500 dark:border-cyan-400 shadow-xs bg-slate-100">
                                                <img :src="url" class="w-full h-full object-cover">
                                                <span class="absolute bottom-0 right-0 bg-slate-900/70 text-white text-[9px] px-1 font-mono font-bold" x-text="'#' + (idx + 1)"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>

                        @if($hasOfficerSig)
                            <div class="space-y-2">
                                <button type="submit"
                                        :disabled="!canSubmit"
                                        :class="canSubmit 
                                            ? 'bg-cyan-600 hover:bg-cyan-700 text-white cursor-pointer shadow-sm hover:shadow-md active:scale-95' 
                                            : 'bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-500 cursor-not-allowed shadow-none border border-slate-300/80 dark:border-slate-700'"
                                        data-confirm="Terbitkan dokumen Berita Acara Serah Terima (BAST) Peminjaman bernomor otomatis sekarang?"
                                        data-confirm-title="Terbitkan BAST Peminjaman"
                                        data-confirm-btn="Ya, Terbitkan BAST"
                                        data-confirm-type="primary"
                                        class="px-5 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2">
                                    <i data-lucide="file-plus" class="w-4 h-4"></i>
                                    <span>Buat Dokumen BAST Peminjaman Bernomor</span>
                                </button>
                                <template x-if="!canSubmit">
                                    <p class="text-[11px] text-amber-600 dark:text-amber-400 font-semibold flex items-center gap-1.5 pt-0.5">
                                        <i data-lucide="alert-triangle" class="w-3.5 h-3.5 shrink-0"></i>
                                        <span>Tombol penerbitan BAST tidak dapat diklik sebelum foto fisik barang diunggah/dipilih.</span>
                                    </p>
                                </template>
                            </div>
                        @else
                            <button type="button" @click="openSigModal('officer')"
                                    class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold shadow-sm transition flex items-center gap-2 cursor-pointer">
                                <i data-lucide="pen-tool" class="w-4 h-4"></i>
                                <span>Wajib Bubuhkan TTD Petugas Sebelum Terbitkan BAST</span>
                            </button>
                        @endif
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
                                class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-sm transition flex items-center gap-2 cursor-pointer">
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

                    @php
                        $bastKblPhotos = $loan->photos()->where('stage', 'BAST_PENGEMBALIAN')->get();
                    @endphp

                    <form method="POST" action="{{ route('kelola.process-return-check', $loan->id) }}" enctype="multipart/form-data" class="space-y-4"
                          x-data="{
                              existingPhotos: {{ $bastKblPhotos->count() }},
                              selectedCount: 0,
                              previewUrls: [],
                              maxPhotos: 5,
                              handleFiles(input) {
                                  const files = Array.from(input.files || []);
                                  if (files.length === 0) return;
                                  if (this.existingPhotos + files.length > this.maxPhotos) {
                                      alert('Maksimal total 5 foto fisik pengembalian yang dapat dipilih.');
                                  }
                                  const validFiles = files.slice(0, Math.max(1, this.maxPhotos - this.existingPhotos));
                                  this.selectedCount = validFiles.length;
                                  this.previewUrls = [];
                                  validFiles.forEach(file => {
                                      const reader = new FileReader();
                                      reader.onload = (e) => { this.previewUrls.push(e.target.result); };
                                      reader.readAsDataURL(file);
                                  });
                                  this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                              },
                              get canSubmit() {
                                  return (this.existingPhotos > 0 || this.selectedCount > 0);
                              }
                          }">
                        @csrf
                        @foreach($loan->items as $item)
                            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-2">
                                <div class="font-bold text-xs text-slate-900">{{ $item->snap_item_name }} ({{ $item->snap_brand_model }})</div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Kondisi Fisik Diterima</label>
                                        <select name="items[{{ $item->id }}][condition_in]" class="w-full p-2 bg-white rounded-xl border border-slate-300 text-xs font-semibold cursor-pointer">
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

                        <div class="p-4 rounded-2xl bg-amber-50/70 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 space-y-2.5">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold text-amber-950 dark:text-amber-200 flex items-center gap-1.5">
                                    <i data-lucide="camera" class="w-4 h-4 text-amber-600 dark:text-amber-400"></i>
                                    <span>Foto Dokumentasi Kondisi Fisik Pengembalian (Wajib Dilampirkan):</span>
                                </label>
                                @if($bastKblPhotos->isNotEmpty())
                                    <span class="px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/70 text-emerald-800 dark:text-emerald-300 text-[10px] font-bold border border-emerald-300 dark:border-emerald-800 flex items-center gap-1">
                                        <i data-lucide="check" class="w-3 h-3"></i>
                                        {{ $bastKblPhotos->count() }} Foto Terlampir
                                    </span>
                                @endif
                            </div>
                            <p class="text-[11px] text-amber-800 dark:text-amber-300">
                                @if($bastKblPhotos->isNotEmpty())
                                    Sistem telah memiliki {{ $bastKblPhotos->count() }} foto fisik saat pengembalian. Anda dapat menambah foto baru (Maksimal total 5 foto).
                                @else
                                    Lampirkan foto fisik barang saat dikembalikan sebagai bukti sah kondisi akhir fisik BMN pada lembar lampiran BAST Pengembalian (Maks. 5 foto).
                                @endif
                            </p>

                            @if($bastKblPhotos->isNotEmpty())
                                <div class="space-y-1 pt-1 pb-2 border-b border-amber-200/60 dark:border-amber-800/60">
                                    <span class="text-[10px] text-amber-900 dark:text-amber-200 font-bold block">Foto Yang Sudah Tersimpan:</span>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($bastKblPhotos as $p)
                                            <div class="relative w-16 h-16 rounded-xl overflow-hidden border border-emerald-300 dark:border-emerald-700 bg-white shadow-2xs">
                                                <img src="{{ asset('storage/' . $p->file_path) }}" alt="Foto BAST Kembali" class="w-full h-full object-cover">
                                                <span class="absolute bottom-0 right-0 bg-emerald-700 text-white text-[8px] px-1 font-bold">Tersimpan</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                <label class="cursor-pointer px-3.5 py-1.5 bg-white dark:bg-slate-900 border border-amber-300 dark:border-amber-700 hover:bg-amber-50 dark:hover:bg-amber-900/40 text-amber-800 dark:text-amber-200 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-2xs">
                                    <i data-lucide="camera" class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400"></i>
                                    <span>Jepret Kamera</span>
                                    <input type="file" name="photos[]" accept="image/*" capture="environment" class="hidden" @change="handleFiles($event.target)">
                                </label>
                                <label class="cursor-pointer px-3.5 py-1.5 bg-white dark:bg-slate-900 border border-amber-300 dark:border-amber-700 hover:bg-amber-50 dark:hover:bg-amber-900/40 text-amber-800 dark:text-amber-200 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-2xs">
                                    <i data-lucide="images" class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400"></i>
                                    <span>Pilih dari Galeri</span>
                                    <input type="file" name="photos[]" accept="image/*" multiple class="hidden" @change="handleFiles($event.target)">
                                </label>
                                <span class="text-xs font-medium"
                                      :class="selectedCount > 0 ? 'text-emerald-700 dark:text-emerald-300 font-bold' : 'text-slate-500 dark:text-slate-400 italic'"
                                      x-text="selectedCount > 0 ? selectedCount + ' foto baru dipilih (Maks. 5 foto)' : (existingPhotos > 0 ? existingPhotos + ' foto sudah tersimpan di sistem' : 'Belum ada foto dipilih')">
                                </span>
                            </div>

                            <!-- Preview Thumbnail Foto Baru -->
                            <template x-if="previewUrls.length > 0">
                                <div class="space-y-1.5 pt-2 border-t border-amber-200/50 dark:border-amber-800/50">
                                    <span class="text-[10px] text-amber-900 dark:text-amber-200 font-bold block">Preview Foto Terpilih:</span>
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="(url, idx) in previewUrls" :key="idx">
                                            <div class="relative w-16 h-16 rounded-xl overflow-hidden border-2 border-amber-500 dark:border-amber-400 shadow-xs bg-slate-100">
                                                <img :src="url" class="w-full h-full object-cover">
                                                <span class="absolute bottom-0 right-0 bg-slate-900/70 text-white text-[9px] px-1 font-mono font-bold" x-text="'#' + (idx + 1)"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="space-y-2">
                            <button type="submit"
                                    :disabled="!canSubmit"
                                    :class="canSubmit 
                                        ? 'bg-[#0B63B5] hover:bg-[#0B4F8A] text-white cursor-pointer shadow-sm hover:shadow-md active:scale-95' 
                                        : 'bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-500 cursor-not-allowed shadow-none border border-slate-300/80 dark:border-slate-700'"
                                    data-confirm="Simpan hasil inspeksi fisik dan terbitkan dokumen BAST Pengembalian bernomor resmi?"
                                    data-confirm-title="Pemeriksaan Fisik BMN"
                                    data-confirm-btn="Ya, Terbitkan BAST Kembali"
                                    data-confirm-type="primary"
                                    class="px-5 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2">
                                <i data-lucide="clipboard-check" class="w-4 h-4"></i>
                                <span>Selesaikan Pemeriksaan & Terbitkan BAST Pengembalian</span>
                            </button>
                            <template x-if="!canSubmit">
                                <p class="text-[11px] text-amber-600 dark:text-amber-400 font-semibold flex items-center gap-1.5 pt-0.5">
                                    <i data-lucide="alert-triangle" class="w-3.5 h-3.5 shrink-0"></i>
                                    <span>Wajib melampirkan minimal 1 foto fisik kondisi pengembalian untuk mengaktifkan tombol penyelesaian & penerbitan BAST.</span>
                                </p>
                            </template>
                        </div>
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
                                class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition flex items-center gap-2 cursor-pointer">
                            <i data-lucide="archive" class="w-4 h-4"></i>
                            <span>Tutup Siklus Peminjaman & Simpan BMN ke Inventaris</span>
                        </button>
                    </form>
                </div>
            @endif
        </div>        <!-- RIGHT COLUMN: SOP TIMELINES -->
        <div class="lg:col-span-4 space-y-6">
            <!-- 1. ALUR SOP PEMINJAMAN (7 LANGKAH) -->
            <div class="bg-white dark:bg-[#151D2F] rounded-3xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm">
                <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-3 mb-4">
                    <i data-lucide="git-commit" class="w-5 h-5 text-[#0B63B5] dark:text-blue-400"></i>
                    <div>
                        <h3 class="font-black text-sm text-slate-900 dark:text-white">Alur SOP Peminjaman BMN</h3>
                        <span class="text-[10px] text-slate-400 dark:text-slate-400 font-semibold">Standar Operasional Prosedur • 7 Tahap</span>
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

                <!-- Vertical Timeline Peminjaman -->
                <div class="relative pl-6 space-y-5 before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200 dark:before:bg-slate-700">
                    @foreach($sopSteps as $sNum => $sInfo)
                        @php
                            $isDone = ($stepIndex > $sNum) || ($stepIndex === 7 && $sNum === 7 && in_array($loan->status, ['ACTIVE', 'RETURN_REQUESTED', 'RETURN_CHECKED', 'RETURNED']));
                            $isCurrent = ($stepIndex === $sNum && !(in_array($loan->status, ['RETURN_REQUESTED', 'RETURN_CHECKED', 'RETURNED'])));
                        @endphp
                        <div class="relative">
                            <!-- Bullet Icon -->
                            <div class="absolute -left-6 top-0.5 w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold
                                        {{ $isDone ? 'bg-emerald-500 text-white ring-4 ring-emerald-50 dark:ring-emerald-950/60' : ($isCurrent ? 'bg-[#0B63B5] dark:bg-blue-600 text-white ring-4 ring-blue-100 dark:ring-blue-950/70 animate-pulse' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-300/50 dark:border-slate-700') }}">
                                @if($isDone) &check; @else {{ $sNum }} @endif
                            </div>

                            <div>
                                <div class="flex items-center justify-between gap-1">
                                    <h4 class="text-xs font-bold {{ $isCurrent ? 'text-[#0B63B5] dark:text-blue-400 font-extrabold' : ($isDone ? 'text-slate-800 dark:text-slate-200' : 'text-slate-600 dark:text-slate-300 font-medium') }}">
                                        {{ $sInfo['title'] }}
                                    </h4>
                                    <span class="text-[9px] font-semibold text-slate-400 dark:text-slate-400 tabular-nums">{{ $sInfo['sla'] }}</span>
                                </div>
                                <span class="text-[11px] block mt-0.5 {{ $isCurrent ? 'text-slate-700 dark:text-slate-200 font-semibold' : 'text-slate-500 dark:text-slate-400' }}">
                                    Pelaksana: {{ $sInfo['actor'] }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- 2. ALUR SOP PENGEMBALIAN BMN (4 TAHAP) -->
                <div class="mt-8 pt-6 border-t border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2 pb-3 mb-4">
                        <i data-lucide="corner-down-left" class="w-4 h-4 text-purple-600 dark:text-purple-400"></i>
                        <div>
                            <h3 class="font-black text-sm text-slate-900 dark:text-white">Alur SOP Pengembalian BMN</h3>
                            <span class="text-[10px] text-slate-400 dark:text-slate-400 font-semibold">Standar Operasional Prosedur • Tahap Pengembalian</span>
                        </div>
                    </div>

                    @php
                        $returnStepIndex = $meta['return_step'] ?? 0;
                        $sopReturnSteps = [
                            1 => ['title' => 'Mengajukan Pengembalian', 'actor' => 'Peminjam', 'sla' => '1 Menit'],
                            2 => ['title' => 'Pemeriksaan Fisik & Draf BAST', 'actor' => 'Petugas BMN', 'sla' => '5 Menit'],
                            3 => ['title' => 'Tanda Tangan BAST Kembali', 'actor' => 'Peminjam & Petugas', 'sla' => '1 Menit'],
                            4 => ['title' => 'Simpan BMN ke Inventaris', 'actor' => 'Petugas BMN', 'sla' => '1 Menit'],
                        ];
                    @endphp

                    <div class="relative pl-6 space-y-5 before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200 dark:before:bg-slate-700">
                        @foreach($sopReturnSteps as $rNum => $rInfo)
                            @php
                                $rDone = ($returnStepIndex > $rNum) || ($returnStepIndex === 4 && $rNum === 4);
                                $rCurrent = ($returnStepIndex === $rNum);
                            @endphp
                            <div class="relative">
                                <div class="absolute -left-6 top-0.5 w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold
                                            {{ $rDone ? 'bg-emerald-500 text-white ring-4 ring-emerald-50 dark:ring-emerald-950/60' : ($rCurrent ? 'bg-purple-600 dark:bg-purple-500 text-white ring-4 ring-purple-100 dark:ring-purple-950/70 animate-pulse' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-300/50 dark:border-slate-700') }}">
                                    @if($rDone) &check; @else {{ $rNum }} @endif
                                </div>

                                <div>
                                    <div class="flex items-center justify-between gap-1">
                                        <h4 class="text-xs font-bold {{ $rCurrent ? 'text-purple-700 dark:text-purple-400 font-extrabold' : ($rDone ? 'text-slate-800 dark:text-slate-200' : 'text-slate-600 dark:text-slate-300 font-medium') }}">
                                            {{ $rInfo['title'] }}
                                        </h4>
                                        <span class="text-[9px] font-semibold text-slate-400 dark:text-slate-400 tabular-nums">{{ $rInfo['sla'] }}</span>
                                    </div>
                                    <span class="text-[11px] block mt-0.5 {{ $rCurrent ? 'text-slate-700 dark:text-slate-200 font-semibold' : 'text-slate-500 dark:text-slate-400' }}">
                                        Pelaksana: {{ $rInfo['actor'] }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- 3. STATUS PEMINJAMAN (RIWAYAT PROGRES) -->
                <div class="mt-8 pt-5 border-t border-slate-100 dark:border-slate-800">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-xs font-bold text-slate-900 dark:text-white">Status Peminjaman</h4>
                        <span class="text-[10px] text-slate-400 dark:text-slate-400 font-semibold">Riwayat Progres</span>
                    </div>
                    <div class="space-y-3 text-xs">
                        @foreach($loan->history as $h)
                            <div class="p-3 rounded-2xl bg-slate-50/80 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-700/80 hover:bg-white dark:hover:bg-slate-800 hover:shadow-2xs transition space-y-1.5">
                                <div class="flex items-center justify-between text-[11px] text-slate-400">
                                    <div class="flex items-center gap-1.5 truncate">
                                        <div class="w-4 h-4 rounded-full bg-blue-100 dark:bg-blue-900/80 text-[#0B63B5] dark:text-blue-300 flex items-center justify-center text-[9px] font-black shrink-0">
                                            {{ substr($h->actor->name ?? 'S', 0, 1) }}
                                        </div>
                                        <span class="font-bold text-slate-700 dark:text-slate-200 truncate">{{ $h->actor->name ?? 'Sistem BMN' }}</span>
                                    </div>
                                    <span class="tabular-nums text-slate-400 dark:text-slate-400 text-[10px] shrink-0 font-medium">{{ $h->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                                <div class="flex flex-wrap items-center gap-1.5 text-xs leading-normal">
                                    <span class="font-medium text-slate-500 dark:text-slate-400 text-[11px]">{{ $h->from_status_label }}</span>
                                    <span class="text-slate-400 dark:text-slate-500 font-normal text-xs">&rarr;</span>
                                    <span class="font-extrabold text-[#0B63B5] dark:text-blue-400 bg-blue-50 dark:bg-blue-950/60 px-2 py-0.5 rounded-md border border-blue-100 dark:border-blue-900/60 text-[11px]">{{ $h->to_status_label }}</span>
                                </div>
                                @if($h->note)
                                    <div class="mt-1.5 bg-white dark:bg-slate-900/70 p-2.5 rounded-xl border border-slate-200/80 dark:border-slate-700/80 shadow-2xs space-y-1">
                                        <div class="flex items-center gap-1.5 text-[10px] font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                                            <i data-lucide="message-square-text" class="w-3.5 h-3.5 text-[#0B63B5] dark:text-blue-400"></i>
                                            <span>{{ $h->note_context_label }}:</span>
                                        </div>
                                        <p class="text-[11px] text-slate-700 dark:text-slate-300 italic leading-relaxed pl-5">
                                            "{{ $h->note }}"
                                        </p>
                                    </div>
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
         @keydown.escape.window="sigModalOpen = false"
         @keydown.window="if ((($event.ctrlKey || $event.metaKey) && $event.key.toLowerCase() === 'z') && sigModalOpen && sigTab === 'pad') { $event.preventDefault(); undo(); }">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="sigModalOpen = false"></div>

        <div class="min-h-full flex items-center justify-center p-4">
            <div class="relative bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl border border-slate-100 dark:border-slate-800 space-y-5"
                 @click.stop>
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-[#0B63B5] dark:text-blue-400 flex items-center justify-center shrink-0">
                            <i data-lucide="signature" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Bubuhkan Tanda Tangan Digital</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Menandatangani sebagai: <strong class="text-[#0B63B5] dark:text-blue-400" x-text="sigAs === 'borrower' ? 'Pihak Kedua (Peminjam)' : 'Pihak Pertama (Pengelola BMN)'"></strong>
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="sigModalOpen = false" class="text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300 p-1 rounded-lg cursor-pointer shrink-0">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Tabs -->
                <div class="grid grid-cols-3 gap-1 p-1 bg-slate-100 dark:bg-slate-800 rounded-2xl text-xs font-bold text-center border border-slate-200/80 dark:border-slate-700/60">
                    <button type="button" @click="sigTab = 'profile'"
                            :class="sigTab === 'profile' ? 'bg-white dark:bg-[#0B63B5] text-slate-900 dark:text-white shadow-xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-white'"
                            class="py-2 rounded-xl transition cursor-pointer">
                        Dari Profil
                    </button>
                    <button type="button" @click="sigTab = 'pad'; initCanvas()"
                            :class="sigTab === 'pad' ? 'bg-white dark:bg-[#0B63B5] text-slate-900 dark:text-white shadow-xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-white'"
                            class="py-2 rounded-xl transition cursor-pointer">
                        Papan Gambar
                    </button>
                    <button type="button" @click="sigTab = 'file'"
                            :class="sigTab === 'file' ? 'bg-white dark:bg-[#0B63B5] text-slate-900 dark:text-white shadow-xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-white'"
                            class="py-2 rounded-xl transition cursor-pointer">
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
                            <div class="p-4 rounded-2xl border-2 border-dashed border-emerald-200 dark:border-emerald-800/80 bg-emerald-50/30 dark:bg-emerald-950/20 text-center space-y-2">
                                <span class="text-xs font-bold text-emerald-800 dark:text-emerald-400 block">Tanda tangan yang tersimpan di profil Anda:</span>
                                <div class="p-2.5 bg-white signature-paper rounded-xl border border-dashed border-slate-300 dark:border-slate-400 inline-flex flex-col items-center justify-center min-h-[85px] max-w-[240px] shadow-2xs relative" style="background-color: #ffffff !important;">
                                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-1">Ruang Dokumen (± 2,5 cm)</span>
                                    <img src="{{ asset('storage/' . auth()->user()->signature_path) }}" alt="TTD Profil" class="max-h-[60px] max-w-[155px] object-contain mx-auto">
                                </div>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400">Tanda tangan ini akan otomatis dicetak pada lembar PDF BAST.</p>
                            </div>
                        @else
                            <div class="p-5 rounded-2xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/60 text-center space-y-2">
                                <i data-lucide="alert-circle" class="w-7 h-7 text-amber-600 dark:text-amber-400 mx-auto"></i>
                                <h4 class="text-xs font-bold text-amber-900 dark:text-amber-300">Belum Ada Tanda Tangan di Profil</h4>
                                <p class="text-[11px] text-amber-700 dark:text-amber-200/80">
                                    Anda belum mengatur tanda tangan di profil. Silakan pilih tab <strong>"Papan Gambar"</strong> untuk menggambar langsung, atau <strong>"Unggah Berkas"</strong>.
                                </p>
                            </div>
                        @endif
                    </div>

                    <!-- Tab 2: Papan Gambar Kanvas -->
                    <div x-show="sigTab === 'pad'" class="space-y-3">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-700 dark:text-slate-200 flex items-center gap-1.5">
                                <i data-lucide="edit-3" class="w-3.5 h-3.5 text-[#0B63B5] dark:text-blue-400"></i>
                                <span>Goreskan Tanda Tangan:</span>
                            </span>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="undo()" class="text-slate-600 dark:text-slate-300 hover:text-slate-800 dark:hover:text-white font-bold flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 cursor-pointer" title="Batalkan goresan (Ctrl + Z)">
                                    <i data-lucide="undo" class="w-3 h-3"></i>
                                    <span>Undo <kbd class="text-[9px] text-slate-400 font-normal border border-slate-200 dark:border-slate-700 px-1 rounded">Ctrl+Z</kbd></span>
                                </button>
                                <button type="button" @click="clearCanvas()" class="text-red-600 dark:text-red-400 hover:text-red-700 font-bold flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-lg border border-red-200 dark:border-red-900/60 bg-red-50/50 dark:bg-red-950/30 cursor-pointer">
                                    <i data-lucide="rotate-ccw" class="w-3 h-3"></i>
                                    <span>Bersihkan</span>
                                </button>
                            </div>
                        </div>

                        <!-- KOTAK TTD DISESUAIKAN DENGAN UKURAN GENERATE DOKUMEN BMN (± 2.5 CM / RASIO 2.5 : 1) -->
                        <div class="bg-slate-50 dark:bg-slate-800/60 border border-slate-200/90 dark:border-slate-700/80 rounded-2xl p-4 space-y-2.5">
                            <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                                <span class="font-bold text-slate-700 dark:text-slate-200 flex items-center gap-1.5">
                                    <i data-lucide="file-check-2" class="w-3.5 h-3.5 text-[#0B63B5] dark:text-blue-400"></i>
                                    <span>Pratinjau Ruang TTD Dokumen BMN</span>
                                </span>
                                <span class="text-[10px] font-bold text-[#0B63B5] dark:text-blue-400 bg-blue-50 dark:bg-blue-950/60 px-2 py-0.5 rounded-full border border-blue-200 dark:border-blue-800/60">
                                    Proporsi 2.5 : 1 (Tinggi ± 2,5 cm)
                                </span>
                            </div>

                            <div class="w-full max-w-[390px] mx-auto bg-white signature-paper rounded-xl border-2 border-dashed border-slate-300 p-2.5 shadow-xs transition-colors" style="background-color: #ffffff !important; border-color: #cbd5e1 !important;">
                                <div class="relative w-full aspect-[5/2] rounded-lg overflow-hidden signature-canvas-pad border border-slate-300/80 shadow-[inset_0_2px_4px_rgba(0,0,0,0.06)]" style="background-color: #f8fafc !important; border-color: #cbd5e1 !important;">
                                    <canvas id="loan-sig-pad"
                                            class="w-full h-full cursor-crosshair touch-none block"
                                            style="background: transparent !important;"
                                            @mousedown="startDraw($event)"
                                            @mousemove="draw($event)"
                                            @mouseup="endDraw($event)"
                                            @mouseleave="endDraw($event)"
                                            @touchstart="startDraw($event)"
                                            @touchmove="draw($event)"
                                            @touchend="endDraw($event)">
                                    </canvas>

                                    <div x-show="!hasDrawn && strokes.length === 0" class="pointer-events-none absolute inset-0 flex items-center justify-center text-slate-400 text-xs font-medium" style="color: #94a3b8 !important;">
                                        Goreskan tanda tangan di sini
                                    </div>
                                </div>

                                <!-- Under-Signature Official Signatory Simulation -->
                                <div class="pt-2 text-center border-t border-slate-200 mt-2 select-none" style="border-top-color: #e2e8f0 !important;">
                                    <div class="text-xs font-bold text-slate-800 underline decoration-slate-400 underline-offset-2" style="color: #1e293b !important;">
                                        <span x-text="sigAs === 'borrower' ? '{{ $loan->snap_full_name }}' : '{{ auth()->user()->employee->full_name ?? auth()->user()->name }}'"></span>
                                    </div>
                                    <div class="text-[10px] text-slate-500 font-medium mt-0.5" style="color: #64748b !important;">
                                        <span x-text="sigAs === 'borrower' ? 'NIP. {{ \App\Helpers\FormatHelper::formatNip($loan->snap_nip) }}' : 'NIP. {{ \App\Helpers\FormatHelper::formatNip(auth()->user()->employee->nip ?? '') }}'"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-1 text-xs text-slate-500 dark:text-slate-400">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="save_to_profile" value="1" class="rounded border-slate-300 dark:border-slate-700 text-[#0B63B5] focus:ring-[#0B63B5] dark:bg-slate-900">
                                <span>Simpan juga tanda tangan ini ke profil saya</span>
                            </label>
                        </div>
                    </div>

                    <!-- Tab 3: Unggah Berkas -->
                    <div x-show="sigTab === 'file'" class="space-y-3">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Pilih Berkas Gambar Tanda Tangan (PNG / JPG transparan disarankan):</label>
                        <input type="file" name="signature_file" accept="image/png,image/jpeg,image/jpg"
                               class="w-full p-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-[#0B63B5] file:text-white hover:file:bg-[#0B4F8A]">

                        <label class="flex items-center gap-2 cursor-pointer pt-1 text-xs text-slate-600 dark:text-slate-400">
                            <input type="checkbox" name="save_to_profile" value="1" class="rounded border-slate-300 dark:border-slate-700 text-[#0B63B5] focus:ring-[#0B63B5] dark:bg-slate-900">
                            <span>Simpan juga berkas ini ke profil saya</span>
                        </label>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-center gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="sigModalOpen = false"
                                class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs font-bold transition cursor-pointer">
                            Batal
                        </button>
                        <button type="button" @click="submitSig()"
                                class="px-5 py-2.5 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white rounded-xl text-xs font-bold shadow-md transition flex items-center gap-1.5 cursor-pointer">
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
            <div class="relative bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl border border-slate-100 dark:border-slate-800 space-y-5"
                 @click.stop>
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 flex items-center justify-center">
                            <i data-lucide="camera" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Unggah Foto Dokumentasi BAST</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Ambil jepretan kamera langsung atau pilih 1 atau lebih foto dari galeri</p>
                        </div>
                    </div>
                    <button type="button" @click="photoModalOpen = false" class="text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300 p-1 rounded-lg cursor-pointer">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <form method="POST" action="{{ route('loans.photos.upload', $loan->id) }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <!-- Stage Selector -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Tahap Dokumentasi BAST:</label>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition"
                                   :class="photoStage === 'BAST_PEMINJAMAN' ? 'border-cyan-500 dark:border-cyan-600 bg-cyan-50/50 dark:bg-cyan-950/50 text-cyan-900 dark:text-cyan-200 font-bold' : 'border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300'">
                                <input type="radio" name="stage" value="BAST_PEMINJAMAN" x-model="photoStage" class="hidden">
                                <i data-lucide="package-check" class="w-4 h-4 text-cyan-600 dark:text-cyan-400"></i>
                                <span>BAST Peminjaman</span>
                            </label>
                            <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition"
                                   :class="photoStage === 'BAST_PENGEMBALIAN' ? 'border-purple-500 dark:border-purple-600 bg-purple-50/50 dark:bg-purple-950/50 text-purple-900 dark:text-purple-200 font-bold' : 'border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300'">
                                <input type="radio" name="stage" value="BAST_PENGEMBALIAN" x-model="photoStage" class="hidden">
                                <i data-lucide="rotate-ccw" class="w-4 h-4 text-purple-600 dark:text-purple-400"></i>
                                <span>BAST Pengembalian</span>
                            </label>
                        </div>
                    </div>

                    <!-- Action: Camera or Gallery Picker -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">Pilih Sumber Foto (Boleh 1 atau lebih foto):</label>
                        <div class="grid grid-cols-2 gap-2.5">
                            <!-- Button 1: Kamera HP / Webcam -->
                            <label class="flex flex-col items-center justify-center p-4 rounded-2xl border-2 border-dashed border-[#0B63B5]/40 dark:border-blue-700/60 bg-blue-50/30 dark:bg-blue-950/30 hover:bg-blue-50 dark:hover:bg-blue-900/30 cursor-pointer transition text-center group">
                                <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/60 text-[#0B63B5] dark:text-blue-300 flex items-center justify-center mb-2 group-hover:scale-110 transition">
                                    <i data-lucide="camera" class="w-5 h-5"></i>
                                </div>
                                <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Jepret Kamera</span>
                                <span class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Buka kamera ponsel/laptop</span>
                                <input type="file" name="photos[]" accept="image/*" capture="environment" class="hidden" @change="handlePhotoSelect($event)">
                            </label>

                            <!-- Button 2: Galeri / File (Bisa Multiple) -->
                            <label class="flex flex-col items-center justify-center p-4 rounded-2xl border-2 border-dashed border-purple-300 dark:border-purple-700/60 bg-purple-50/30 dark:bg-purple-950/30 hover:bg-purple-50 dark:hover:bg-purple-900/30 cursor-pointer transition text-center group">
                                <div class="w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900/60 text-purple-700 dark:text-purple-300 flex items-center justify-center mb-2 group-hover:scale-110 transition">
                                    <i data-lucide="images" class="w-5 h-5"></i>
                                </div>
                                <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Pilih dari Galeri</span>
                                <span class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Bisa pilih banyak foto</span>
                                <input type="file" name="photos[]" accept="image/*" multiple class="hidden" @change="handlePhotoSelect($event)">
                            </label>
                        </div>
                    </div>

                    <!-- Preview of Selected Photos -->
                    <template x-if="previewPhotos.length > 0">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-slate-700 dark:text-slate-200" x-text="previewPhotos.length + ' foto siap diunggah:'"></span>
                                <button type="button" @click="previewPhotos = []" class="text-red-600 dark:text-red-400 font-bold text-[11px] hover:underline cursor-pointer">Hapus Semua</button>
                            </div>
                            <div class="grid grid-cols-3 gap-2 max-h-36 overflow-y-auto p-1 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-200 dark:border-slate-700">
                                <template x-for="(p, idx) in previewPhotos" :key="idx">
                                    <div class="relative rounded-xl overflow-hidden aspect-square border border-slate-300 dark:border-slate-600 group">
                                        <img :src="p.url" class="w-full h-full object-cover">
                                        <div class="absolute bottom-0 inset-x-0 bg-slate-900/70 p-1 text-[9px] text-white truncate text-center" x-text="p.size"></div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- Caption -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Keterangan Foto (Opsional):</label>
                        <input type="text" name="caption" placeholder="Contoh: Kondisi fisik barang lengkap dan normal"
                               class="w-full p-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-medium focus:border-purple-500 outline-none">
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="photoModalOpen = false"
                                class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs font-bold transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                                class="px-5 py-2.5 bg-purple-700 hover:bg-purple-800 text-white rounded-xl text-xs font-bold shadow-md transition flex items-center gap-1.5 cursor-pointer">
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
                    class="absolute -top-10 right-0 text-white/80 hover:text-white p-1 rounded-full bg-slate-800/60 hover:bg-slate-800 transition cursor-pointer">
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
