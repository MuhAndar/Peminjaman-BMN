@extends('layouts.app')

@section('title', 'Data Diri Peminjam & Profil Saya — BBLSDM Komdigi')

@section('content')
<div class="space-y-6" x-data="{
    mainTab: '{{ request('tab', 'biodata') }}',
    activeTab: 'pad',
    fullName: '{{ addslashes($user->employee->full_name ?? $user->name) }}',
    nip: '{{ addslashes($user->employee->formatted_nip ?? '') }}',
    rankGrade: '{{ addslashes($user->employee->rank_grade ?? '') }}',
    position: '{{ addslashes($user->employee->position ?? '') }}',
    homeAddress: '{{ addslashes($user->employee->home_address ?? '') }}',
    phone: '{{ addslashes($user->employee->phone ?? '') }}',
    status: '{{ addslashes($user->employee->employment_status ?? 'PNS') }}',
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
            this.canvas = document.getElementById('signature-pad');
            if (!this.canvas) return;
            this.ctx = this.canvas.getContext('2d');

            // Set canvas display size vs internal coordinate resolution
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
    switchMainTab(tab) {
        this.mainTab = tab;
        if (tab === 'signature') {
            this.$nextTick(() => {
                this.initCanvas();
            });
        }
    },
    getPos(e) {
        const rect = this.canvas.getBoundingClientRect();
        if (e.touches && e.touches.length > 0) {
            return {
                x: e.touches[0].clientX - rect.left,
                y: e.touches[0].clientY - rect.top
            };
        }
        return {
            x: e.clientX - rect.left,
            y: e.clientY - rect.top
        };
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
    submitPadForm() {
        if (!this.hasDrawn || this.strokes.length === 0) {
            alert('Silakan goreskan tanda tangan Anda pada papan gambar terlebih dahulu.');
            return;
        }
        const dataUrl = this.trimCanvas(this.canvas);
        document.getElementById('signature_data_input').value = dataUrl;
        document.getElementById('pad-form').submit();
    }
}" x-init="if (mainTab === 'signature') initCanvas()">

    <!-- Top Bar Navigation -->
    <div class="flex items-center justify-between gap-3">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}"
           onclick="if (document.referrer && document.referrer.indexOf(window.location.host) !== -1) { history.back(); return false; }"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 hover:text-slate-900 hover:bg-slate-50 transition shadow-2xs">
            <i data-lucide="arrow-left" class="w-4 h-4 text-slate-500"></i>
            <span>Kembali</span>
        </a>
    </div>

    <!-- HEADER & TOP NAVIGATION -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                <a href="{{ route('dashboard') }}" class="hover:text-blue-600 transition">Beranda</a>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-[#0B63B5]">Profil & Data Diri</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-2xl bg-blue-100 text-[#0B63B5] flex items-center justify-center shrink-0">
                    <i data-lucide="user-pen" class="w-5 h-5"></i>
                </div>
                <span>Data Diri Peminjam & Tanda Tangan Digital</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1 font-medium">
                Kelola data diri resmi peminjam dan spesimen tanda tangan untuk pencetakan Surat Peminjaman, BAST Peminjaman, dan BAST Pengembalian BMN kantor BBLSDM Makassar.
            </p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <span class="px-3.5 py-1.5 rounded-full text-xs font-extrabold uppercase bg-blue-50 text-[#0B63B5] border border-blue-200">
                Peran: {{ str_replace('_', ' ', $user->role) }}
            </span>
        </div>
    </div>

    <!-- MAIN SEGMENTED TAB SELECTOR -->
    <div class="flex rounded-2xl bg-slate-200/70 dark:bg-slate-800/90 p-1.5 max-w-xl border border-transparent dark:border-slate-700/60 shadow-xs">
        <button type="button" @click="switchMainTab('biodata')"
                :class="mainTab === 'biodata' ? 'bg-white dark:bg-[#0B63B5] text-[#0B63B5] dark:text-white font-extrabold shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white font-bold'"
                class="flex-1 py-2.5 px-4 rounded-xl text-xs sm:text-sm flex items-center justify-center gap-2 transition select-none cursor-pointer">
            <i data-lucide="id-card" class="w-4 h-4"></i>
            <span>Ubah Data Diri Peminjaman</span>
        </button>
        <button type="button" @click="switchMainTab('signature')"
                :class="mainTab === 'signature' ? 'bg-white dark:bg-[#0B63B5] text-[#0B63B5] dark:text-white font-extrabold shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white font-bold'"
                class="flex-1 py-2.5 px-4 rounded-xl text-xs sm:text-sm flex items-center justify-center gap-2 transition select-none cursor-pointer">
            <i data-lucide="signature" class="w-4 h-4"></i>
            <span>Tanda Tangan Digital</span>
            @if(!$user->signature_path)
                <span class="w-2 h-2 rounded-full bg-amber-500" title="Belum memiliki tanda tangan tersimpan"></span>
            @else
                <span class="w-2 h-2 rounded-full bg-emerald-500" title="Tanda tangan tersimpan aktif"></span>
            @endif
        </button>
    </div>

    <!-- MAIN TWO COLUMNS -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- LEFT COLUMN: RINGKASAN PROFIL & DOKUMEN CHECKLIST (5 COLS) -->
        <div class="lg:col-span-5 space-y-6">

            <!-- KARTU IDENTITAS PEGAWAI SAAT INI -->
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 space-y-5">
                <div class="flex items-center gap-4 pb-4 border-b border-slate-100">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-[#0B63B5] to-[#12A8E0] text-white font-black text-2xl flex items-center justify-center shadow-md shadow-blue-500/20 shrink-0">
                        {{ substr($user->name, 0, 1) }}
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-base font-extrabold text-slate-900 truncate" x-text="fullName || '{{ $user->name }}'"></h2>
                        <span class="text-xs text-slate-500 block truncate">{{ $user->email }}</span>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Akun Aktif
                            </span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-slate-100 text-slate-600" x-text="status"></span>
                        </div>
                    </div>
                </div>

                <!-- INDIKATOR KELENGKAPAN DOKUMEN PEMINJAMAN -->
                @php
                    $hasEmp = (bool)$user->employee;
                    $emp = $user->employee;
                    $completeness = 0;
                    if ($user->name) $completeness += 20;
                    if ($emp && $emp->nip) $completeness += 20;
                    if ($emp && $emp->rank_grade) $completeness += 15;
                    if ($emp && $emp->position) $completeness += 15;
                    if ($emp && $emp->home_address) $completeness += 15;
                    if ($user->signature_path) $completeness += 15;
                @endphp
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-700 flex items-center gap-1.5">
                            <i data-lucide="shield-check" class="w-4 h-4 text-[#0B63B5]"></i>
                            <span>Kelengkapan Berkas Peminjaman</span>
                        </span>
                        <span class="font-black {{ $completeness === 100 ? 'text-emerald-600' : 'text-blue-600' }}">{{ $completeness }}%</span>
                    </div>
                    <div class="w-full bg-slate-200 h-2 rounded-full overflow-hidden">
                        <div class="bg-gradient-to-r from-[#0B63B5] to-[#12A8E0] h-full rounded-full transition-all duration-300" style="width: {{ $completeness }}%"></div>
                    </div>
                    <p class="text-[10px] text-slate-500 font-medium leading-relaxed">
                        Data diri dan tanda tangan diperlukan agar Surat Peminjaman dan BAST dapat diterbitkan otomatis tanpa koreksi manual.
                    </p>
                </div>

                <div class="space-y-3.5 text-xs">
                    <div>
                        <span class="text-slate-400 font-bold uppercase tracking-wider text-[10px] block">NIP (Nomor Induk Pegawai)</span>
                        <span class="text-slate-800 font-semibold tabular-nums text-sm">
                            {{ $user->employee && $user->employee->nip ? \App\Helpers\FormatHelper::formatNip($user->employee->nip) : 'Non-PNS / PPNPN (Belum Ada NIP)' }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <span class="text-slate-400 font-bold uppercase tracking-wider text-[10px] block">Pangkat / Gol.</span>
                            <span class="text-slate-800 font-semibold text-xs block truncate" x-text="rankGrade || 'Belum diisi'"></span>
                        </div>
                        <div>
                            <span class="text-slate-400 font-bold uppercase tracking-wider text-[10px] block">Jabatan</span>
                            <span class="text-slate-800 font-semibold text-xs block truncate" x-text="position || 'Belum diisi'"></span>
                        </div>
                    </div>

                    <div>
                        <span class="text-slate-400 font-bold uppercase tracking-wider text-[10px] block">Alamat Rumah (Sesuai Dokumen Resmi)</span>
                        <p class="text-slate-700 font-medium leading-relaxed mt-0.5 text-xs" x-text="homeAddress || 'Alamat rumah belum diisi. Harap lengkapi untuk kebutuhan dokumen peminjaman.'"></p>
                    </div>

                    <div>
                        <span class="text-slate-400 font-bold uppercase tracking-wider text-[10px] block">Nomor Telepon / WhatsApp</span>
                        <span class="text-slate-800 font-semibold text-xs block" x-text="phone || 'Belum dicantumkan'"></span>
                    </div>

                    <div>
                        <span class="text-slate-400 font-bold uppercase tracking-wider text-[10px] block">Satuan Kerja</span>
                        <span class="text-slate-800 font-semibold text-xs block">
                            BBLSDM Komdigi Makassar
                        </span>
                    </div>
                </div>

                <!-- QUICK TAB SHORTCUT BUTTONS -->
                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center gap-2">
                    <button type="button" @click="switchMainTab('biodata')"
                            :class="mainTab === 'biodata' ? 'bg-blue-50 dark:bg-blue-950/60 text-[#0B63B5] dark:text-blue-400 border-blue-200 dark:border-blue-800' : 'bg-slate-50 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700'"
                            class="flex-1 py-2 px-3 rounded-xl border text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-2xs cursor-pointer">
                        <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                        <span>Ubah Data Diri</span>
                    </button>
                    <button type="button" @click="switchMainTab('signature')"
                            :class="mainTab === 'signature' ? 'bg-blue-50 dark:bg-blue-950/60 text-[#0B63B5] dark:text-blue-400 border-blue-200 dark:border-blue-800' : 'bg-slate-50 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700'"
                            class="flex-1 py-2 px-3 rounded-xl border text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-2xs cursor-pointer">
                        <i data-lucide="signature" class="w-3.5 h-3.5"></i>
                        <span>Kelola TTD</span>
                    </button>
                </div>
            </div>

            <!-- TANDA TANGAN AKTIF SAAT INI -->
            <div class="bg-white dark:bg-[#1E293B] rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="signature" class="w-4 h-4 text-[#0B63B5]"></i>
                        <span>Spesimen Tanda Tangan Tersimpan</span>
                    </h3>
                </div>

                @if($user->signature_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->signature_path))
                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/80 flex flex-col items-center justify-center space-y-3">
                        <div class="p-3 bg-white signature-paper rounded-xl border border-dashed border-slate-300 dark:border-slate-400 w-full max-w-[260px] flex flex-col items-center justify-center min-h-[85px] shadow-2xs relative">
                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-1">Ukuran Dokumen (± 2,5 cm)</span>
                            <img src="{{ asset('storage/' . $user->signature_path) }}" alt="Tanda Tangan {{ $user->name }}" class="max-h-[66px] max-w-[165px] object-contain">
                        </div>
                        <div class="w-full flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 font-medium">
                            <span class="flex items-center gap-1 text-emerald-600 dark:text-emerald-400 font-bold">
                                <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                                Siap digunakan di BAST & Surat
                            </span>
                            <form method="POST" action="{{ route('profile.signature.delete') }}" onsubmit="return confirm('Hapus spesimen tanda tangan ini dari profil?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 dark:text-red-400 hover:text-red-700 font-bold hover:underline transition flex items-center gap-1 cursor-pointer">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    <span>Hapus</span>
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="p-5 rounded-2xl bg-amber-50/70 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/60 text-center space-y-2">
                        <div class="w-9 h-9 rounded-full bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400 flex items-center justify-center mx-auto">
                            <i data-lucide="alert-circle" class="w-4 h-4"></i>
                        </div>
                        <h4 class="text-xs font-bold text-amber-900 dark:text-amber-300">Belum Ada Tanda Tangan Tersimpan</h4>
                        <p class="text-[11px] text-amber-700 dark:text-amber-200/80">
                            Silakan buka tab "Tanda Tangan Digital" untuk menggambar langsung atau mengunggah gambar tanda tangan Anda.
                        </p>
                        <button type="button" @click="switchMainTab('signature')" class="mt-2 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs transition shadow-2xs cursor-pointer">
                            <i data-lucide="pen-tool" class="w-3.5 h-3.5"></i>
                            <span>Buat Tanda Tangan Sekarang</span>
                        </button>
                    </div>
                @endif
            </div>

            <!-- KLAUSUL RESMI KETENTUAN BMN -->
            <div class="p-4 rounded-2xl bg-blue-50/60 dark:bg-blue-950/30 border border-blue-200/60 dark:border-blue-900/40 text-slate-700 dark:text-slate-300 text-xs space-y-1.5">
                <div class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                    <i data-lucide="info" class="w-4 h-4 text-[#0B63B5] dark:text-blue-400"></i>
                    <span>Ketentuan Data Peminjam BMN</span>
                </div>
                <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-relaxed">
                    Sesuai Peraturan Menkeu dan SOP BMN BBLSDM Makassar, data identitas dan alamat rumah digunakan sebagai klausul pertanggungjawaban mutlak peminjam atas barang milik negara (UU No. 1 Tahun 2004).
                </p>
            </div>
        </div>

        <!-- RIGHT COLUMN: KONTEN TAB UTAMA (7 COLS) -->
        <div class="lg:col-span-7 space-y-6">

            <!-- TAB 1: MENU UBAH DATA DIRI PEMINJAMAN -->
            <div x-show="mainTab === 'biodata'" class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 sm:p-7 space-y-6">
                <div class="border-b border-slate-100 pb-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-blue-100 text-[#0B63B5] flex items-center justify-center shrink-0">
                                <i data-lucide="id-card" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h2 class="text-base font-black text-slate-900">Ubah Data Diri Peminjaman BMN</h2>
                                <p class="text-xs text-slate-500">Perbarui identitas kedinasan dan domisili Anda untuk kelengkapan dokumen resmi peminjaman.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FORM UBAH DATA DIRI -->
                <form method="POST" action="{{ route('profile.biodata.update') }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <!-- SECTION 1: IDENTITAS UTAMA -->
                    <div class="space-y-4">
                        <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                            <i data-lucide="user" class="w-3.5 h-3.5 text-[#0B63B5]"></i>
                            <span>1. Identitas Pokok Pegawai</span>
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- NAMA LENGKAP -->
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Nama Lengkap & Gelar <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="full_name" x-model="fullName" required
                                       value="{{ old('full_name', $user->employee->full_name ?? $user->name) }}"
                                       placeholder="Contoh: Muhammad Arif, S.Kom., M.T."
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-[#0B63B5] focus:ring-2 focus:ring-blue-100 outline-none text-sm font-bold text-slate-900 transition">
                                <span class="text-[11px] text-slate-400 mt-1 block">Tulis nama lengkap beserta gelar akademik/kedinasan jika ada.</span>
                            </div>

                            <!-- STATUS KEPEGAWAIAN -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Status Kepegawaian <span class="text-red-500">*</span>
                                </label>
                                <select name="employment_status" x-model="status" required
                                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-[#0B63B5] focus:ring-2 focus:ring-blue-100 outline-none text-xs font-bold text-slate-800 transition bg-white">
                                    <option value="PNS" {{ old('employment_status', $user->employee->employment_status ?? '') === 'PNS' ? 'selected' : '' }}>PNS (Pegawai Negeri Sipil)</option>
                                    <option value="CPNS" {{ old('employment_status', $user->employee->employment_status ?? '') === 'CPNS' ? 'selected' : '' }}>CPNS</option>
                                    <option value="PPPK" {{ old('employment_status', $user->employee->employment_status ?? '') === 'PPPK' ? 'selected' : '' }}>PPPK</option>
                                    <option value="PPNPN" {{ old('employment_status', $user->employee->employment_status ?? '') === 'PPNPN' ? 'selected' : '' }}>PPNPN / Tenaga Kontrak</option>
                                    <option value="Lainnya" {{ old('employment_status', $user->employee->employment_status ?? '') === 'Lainnya' ? 'selected' : '' }}>Lainnya / Magang Dinas</option>
                                </select>
                            </div>

                            <!-- NIP -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    NIP (Nomor Induk Pegawai)
                                </label>
                                <input type="text" name="nip" x-model="nip"
                                       value="{{ old('nip', $user->employee->nip ?? '') }}"
                                       placeholder="18 Digit: 19850101 201001 1 001"
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-[#0B63B5] focus:ring-2 focus:ring-blue-100 outline-none text-xs font-semibold text-slate-800 tabular-nums transition">
                                <span class="text-[11px] text-slate-400 mt-1 block">Kosongkan atau ketik tanda hubung (-) jika Non-PNS.</span>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: JABATAN & GOLONGAN -->
                    <div class="space-y-4 pt-2 border-t border-slate-100">
                        <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                            <i data-lucide="briefcase" class="w-3.5 h-3.5 text-[#0B63B5]"></i>
                            <span>2. Golongan & Penugasan Dinas</span>
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- PANGKAT / GOLONGAN -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Pangkat / Golongan Ruang
                                </label>
                                <input type="text" name="rank_grade" x-model="rankGrade" list="rankList"
                                       value="{{ old('rank_grade', $user->employee->rank_grade ?? '') }}"
                                       placeholder="Contoh: Penata Muda / III/a"
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-[#0B63B5] focus:ring-2 focus:ring-blue-100 outline-none text-xs font-semibold text-slate-800 transition">
                                <datalist id="rankList">
                                    <option value="Pengatur Muda (II/a)">
                                    <option value="Pengatur Muda Tk. I (II/b)">
                                    <option value="Pengatur (II/c)">
                                    <option value="Pengatur Tk. I (II/d)">
                                    <option value="Penata Muda (III/a)">
                                    <option value="Penata Muda Tk. I (III/b)">
                                    <option value="Penata (III/c)">
                                    <option value="Penata Tk. I (III/d)">
                                    <option value="Pembina (IV/a)">
                                    <option value="Pembina Tk. I (IV/b)">
                                    <option value="Pembina Utama Muda (IV/c)">
                                    <option value="Pembina Utama Madya (IV/d)">
                                    <option value="Pembina Utama (IV/e)">
                                    <option value="PPPK">
                                    <option value="Non-ASN">
                                </datalist>
                                <span class="text-[11px] text-slate-400 mt-1 block">Pilih dari daftar rekomendasi atau ketik langsung.</span>
                            </div>

                            <!-- JABATAN -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Jabatan / Penugasan
                                </label>
                                <input type="text" name="position" x-model="position"
                                       value="{{ old('position', $user->employee->position ?? '') }}"
                                       placeholder="Contoh: Pranata Komputer Ahli Muda"
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-[#0B63B5] focus:ring-2 focus:ring-blue-100 outline-none text-xs font-semibold text-slate-800 transition">
                                <span class="text-[11px] text-slate-400 mt-1 block">Jabatan struktural / fungsional Anda di BBLSDM.</span>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3: KONTAK & DOMISILI RESMI -->
                    <div class="space-y-4 pt-2 border-t border-slate-100">
                        <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                            <i data-lucide="map-pin" class="w-3.5 h-3.5 text-[#0B63B5]"></i>
                            <span>3. Domisili & Kontak (Wajib untuk Dokumen BAST)</span>
                        </h3>

                        <!-- ALAMAT RUMAH -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Alamat Rumah Lengkap <span class="text-red-500">* (Dicetak pada Surat & BAST)</span>
                            </label>
                            <textarea name="home_address" x-model="homeAddress" rows="3" required
                                      placeholder="Masukkan alamat rumah lengkap peminjam (Jalan, No., Kelurahan, Kecamatan, Kota)..."
                                      class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-[#0B63B5] focus:ring-2 focus:ring-blue-100 outline-none text-xs font-medium leading-relaxed transition">{{ old('home_address', $user->employee->home_address ?? '') }}</textarea>
                            <span class="text-[11px] text-slate-500 mt-1 block">Alamat ini adalah domisili resmi penanggung jawab barang inventaris kantor.</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- TELEPON / WHATSAPP -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Nomor Telepon / WhatsApp
                                </label>
                                <input type="text" name="phone" x-model="phone"
                                       value="{{ old('phone', $user->employee->phone ?? '') }}"
                                       placeholder="Contoh: 081234567890"
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-[#0B63B5] focus:ring-2 focus:ring-blue-100 outline-none text-xs font-semibold text-slate-800 transition">
                                <span class="text-[11px] text-slate-400 mt-1 block">Nomor aktif untuk koordinasi penyerahan/pengembalian barang.</span>
                            </div>

                            <!-- EMAIL AKUN -->
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">
                                    Email Akun Login (Read-Only)
                                </label>
                                <input type="email" value="{{ $user->email }}" readonly
                                       class="w-full px-3.5 py-2.5 rounded-xl bg-slate-100 border border-slate-200 text-xs font-medium text-slate-500 cursor-not-allowed">
                                <span class="text-[11px] text-slate-400 mt-1 block">Hubungi Admin jika ingin mengganti email login sistem.</span>
                            </div>
                        </div>
                    </div>

                    <!-- LIVE DOCUMENT PREVIEW SIMULATOR -->
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-2">
                        <div class="flex items-center justify-between text-xs pb-1.5 border-b border-slate-200">
                            <span class="font-bold text-slate-800 flex items-center gap-1.5">
                                <i data-lucide="eye" class="w-3.5 h-3.5 text-[#0B63B5]"></i>
                                <span>Simulasi Teks pada Surat Peminjaman Resmi BMN</span>
                            </span>
                            <span class="text-[10px] font-bold text-[#0B63B5] uppercase bg-blue-50 px-2 py-0.5 rounded border border-blue-200">Live Preview</span>
                        </div>
                        <div class="p-3 bg-white rounded-xl border border-slate-200 text-[11px] text-slate-700 font-mono space-y-0.5 leading-relaxed">
                            <div class="text-slate-400 italic mb-1">// Redaksi Surat Peminjaman & BAST:</div>
                            <div>Saya yang bertanda tangan di bawah ini :</div>
                            <div class="grid grid-cols-12 gap-1">
                                <span class="col-span-3 text-slate-500">N a m a</span>
                                <span class="col-span-9 font-bold text-slate-900">: <span x-text="fullName || '....................................'"></span></span>
                            </div>
                            <div class="grid grid-cols-12 gap-1">
                                <span class="col-span-3 text-slate-500">N I P</span>
                                <span class="col-span-9 font-bold text-slate-900">: <span x-text="nip || '....................................'"></span></span>
                            </div>
                            <div class="grid grid-cols-12 gap-1">
                                <span class="col-span-3 text-slate-500">Pangkat / Gol.</span>
                                <span class="col-span-9 font-bold text-slate-900">: <span x-text="rankGrade || '....................................'"></span></span>
                            </div>
                            <div class="grid grid-cols-12 gap-1">
                                <span class="col-span-3 text-slate-500">J a b a t a n</span>
                                <span class="col-span-9 font-bold text-slate-900">: <span x-text="position || '....................................'"></span></span>
                            </div>
                            <div class="grid grid-cols-12 gap-1">
                                <span class="col-span-3 text-slate-500">Alamat Rumah</span>
                                <span class="col-span-9 font-bold text-slate-900">: <span x-text="homeAddress || '....................................'"></span></span>
                            </div>
                            <div class="mt-2 text-slate-800 font-sans text-[10px] pt-1 border-t border-slate-100">
                                Menyatakan bahwa benar telah <strong>MEMINJAM BARANG INVENTARIS KANTOR</strong>...
                            </div>
                        </div>
                    </div>

                    <!-- TOMBOL SIMPAN DATA DIRI -->
                    <div class="pt-2 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <div class="text-[11px] text-slate-500 flex items-center gap-1.5">
                            <i data-lucide="info" class="w-4 h-4 text-slate-400 shrink-0"></i>
                            <span>Data otomatis tersimpan ke master profil peminjam Anda.</span>
                        </div>

                        <button type="submit"
                                class="w-full sm:w-auto px-6 py-2.5 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white rounded-xl text-xs font-extrabold shadow-sm hover:shadow transition flex items-center justify-center gap-2 select-none cursor-pointer">
                            <i data-lucide="check" class="w-4 h-4"></i>
                            <span>Simpan Perubahan Data Diri</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- TAB 2: MENU KELOLA TANDA TANGAN DIGITAL -->
            <div x-show="mainTab === 'signature'" x-cloak class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 sm:p-7 space-y-5">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div>
                        <h2 class="text-base font-black text-slate-900 flex items-center gap-2">
                            <i data-lucide="pen-tool" class="w-5 h-5 text-[#0B63B5]"></i>
                            <span>Buat / Perbarui Tanda Tangan Digital</span>
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Pilih metode penambahan tanda tangan: gambar manual di papan kanvas atau unggah berkas gambar tanda tangan resmi Anda.
                        </p>
                    </div>
                </div>

                <!-- SUB TAB SWITCHER FOR SIGNATURE METHOD -->
                <div class="flex rounded-2xl bg-slate-100 dark:bg-slate-800/80 p-1 border border-slate-200/80 dark:border-slate-700/60">
                    <button type="button" @click="activeTab = 'pad'; initCanvas()"
                            :class="activeTab === 'pad' ? 'bg-white dark:bg-[#0B63B5] text-slate-900 dark:text-white font-extrabold shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white font-bold'"
                            class="flex-1 py-2 rounded-xl text-xs flex items-center justify-center gap-2 transition cursor-pointer">
                        <i data-lucide="pencil" class="w-4 h-4"></i>
                        <span>Gambar di Papan Kanvas</span>
                    </button>
                    <button type="button" @click="activeTab = 'upload'"
                            :class="activeTab === 'upload' ? 'bg-white dark:bg-[#0B63B5] text-slate-900 dark:text-white font-extrabold shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white font-bold'"
                            class="flex-1 py-2 rounded-xl text-xs flex items-center justify-center gap-2 transition cursor-pointer">
                        <i data-lucide="upload" class="w-4 h-4"></i>
                        <span>Unggah Berkas Gambar</span>
                    </button>
                </div>

                <!-- OPTION 1: CANVAS SIGNATURE PAD -->
                <div x-show="activeTab === 'pad'"
                     @keydown.window="if (($event.ctrlKey || $event.metaKey) && $event.key.toLowerCase() === 'z' && activeTab === 'pad') { $event.preventDefault(); undo(); }"
                     class="space-y-4">
                    <div class="flex items-center justify-between text-xs pb-1">
                        <span class="font-bold text-slate-700 dark:text-slate-200 flex items-center gap-1.5">
                            <i data-lucide="edit-3" class="w-3.5 h-3.5 text-[#0B63B5] dark:text-blue-400"></i>
                            <span>Goreskan Tanda Tangan:</span>
                        </span>
                        <div class="flex items-center gap-2">
                            <span x-show="justCorrected" x-transition.opacity class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/50 px-2.5 py-0.5 rounded-full border border-emerald-200 dark:border-emerald-800 shadow-2xs">
                                <i data-lucide="sparkles" class="w-3 h-3 text-emerald-500"></i>
                                <span>Stroke Dihaluskan Otomatis</span>
                            </span>
                            <label class="inline-flex items-center gap-1.5 text-[11px] font-bold text-slate-600 dark:text-slate-300 cursor-pointer select-none bg-slate-100 dark:bg-slate-800 hover:bg-slate-200/80 dark:hover:bg-slate-700 px-2.5 py-1 rounded-lg transition border border-slate-200/60 dark:border-slate-700">
                                <input type="checkbox" x-model="smoothingEnabled" class="rounded border-slate-300 dark:border-slate-600 text-[#0B63B5] focus:ring-[#0B63B5] w-3.5 h-3.5">
                                <span>Koreksi Stroke Halus (Chaikin Smoothing)</span>
                            </label>
                        </div>
                    </div>

                    <!-- KOTAK TANDA TANGAN PROPORSIONAL DENGAN UKURAN DOKUMEN BMN (± 2,5 CM / RASIO 2.5 : 1) -->
                    <div class="bg-slate-50/80 dark:bg-slate-800/60 border border-slate-200/90 dark:border-slate-700/80 rounded-3xl p-5 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                            <span class="inline-flex items-center gap-1.5 font-bold text-slate-800 dark:text-slate-200">
                                <i data-lucide="file-check-2" class="w-4 h-4 text-[#0B63B5] dark:text-blue-400"></i>
                                <span>Pratinjau Ruang Tanda Tangan Dokumen BMN</span>
                            </span>
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-[#0B63B5] dark:text-blue-400 bg-blue-50 dark:bg-blue-950/60 px-2.5 py-1 rounded-full border border-blue-200/80 dark:border-blue-800/80 w-fit">
                                <i data-lucide="scale" class="w-3 h-3"></i>
                                <span>Rasio Dokumen 2.5 : 1 (Standar Cetak ± 2,5 cm)</span>
                            </span>
                        </div>

                        <!-- Canvas Box with Strict Document Aspect Ratio (2.5 : 1) -->
                        <div class="w-full max-w-[420px] mx-auto bg-white signature-paper rounded-2xl border-2 border-dashed border-slate-300 dark:border-slate-400 p-3 shadow-xs">
                            <div class="relative w-full aspect-[5/2] rounded-xl overflow-hidden bg-slate-50/30">
                                <canvas id="signature-pad"
                                        @mousedown="startDraw($event)"
                                        @mousemove="draw($event)"
                                        @mouseup="endDraw($event)"
                                        @mouseleave="endDraw($event)"
                                        @touchstart="startDraw($event)"
                                        @touchmove="draw($event)"
                                        @touchend="endDraw($event)"
                                        class="w-full h-full cursor-crosshair touch-none block bg-transparent"></canvas>

                                <!-- Baseline Guide -->
                                <div class="absolute left-4 right-4 bottom-5 border-b border-dashed border-slate-300 pointer-events-none flex justify-between text-[10px] text-slate-400 select-none">
                                    <span>Ruang TTD (± 2,5 cm)</span>
                                    <span>Garis Acuan</span>
                                </div>

                                <div x-show="!hasDrawn && strokes.length === 0" class="pointer-events-none absolute inset-0 flex items-center justify-center text-slate-400 text-xs font-medium">
                                    <span>Goreskan tanda tangan di sini</span>
                                </div>
                            </div>

                            <!-- Under-Signature Official Signatory Simulation -->
                            <div class="pt-2.5 pb-1 text-center border-t border-slate-100 mt-2.5 select-none">
                                <div class="text-xs font-bold text-slate-800 underline decoration-slate-400 underline-offset-2">
                                    {{ $user->employee->full_name ?? $user->name }}
                                </div>
                                <div class="text-[10px] text-slate-500 font-medium mt-0.5">
                                    NIP. {{ \App\Helpers\FormatHelper::formatNip($user->employee->nip ?? '') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Controls for Canvas -->
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <button type="button" @click="clearCanvas()"
                                    class="px-3.5 py-2 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-100 text-xs font-bold transition flex items-center gap-1.5 shadow-2xs cursor-pointer">
                                <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                                <span>Bersihkan</span>
                            </button>
                            <button type="button" @click="undo()"
                                    class="px-3.5 py-2 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-100 text-xs font-bold transition flex items-center gap-1.5 shadow-2xs cursor-pointer"
                                    title="Batalkan goresan terakhir (Ctrl + Z)">
                                <i data-lucide="undo" class="w-3.5 h-3.5"></i>
                                <span>Undo <kbd class="text-[10px] text-slate-400 border border-slate-200 px-1 rounded bg-slate-50 font-normal">Ctrl+Z</kbd></span>
                            </button>
                        </div>

                        <form id="pad-form" method="POST" action="{{ route('profile.signature.update') }}">
                            @csrf
                            <input type="hidden" name="signature_data" id="signature_data_input">
                            <button type="button" @click="submitPadForm()"
                                    class="px-5 py-2.5 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white rounded-xl text-xs font-extrabold shadow-sm transition flex items-center gap-2 cursor-pointer">
                                <i data-lucide="check" class="w-4 h-4"></i>
                                <span>Simpan Tanda Tangan Kanvas</span>
                            </button>
                        </form>
                    </div>

                    <p class="text-[11px] text-slate-500 italic flex items-center gap-1.5">
                        <i data-lucide="info" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                        <span>Setiap kali Anda melepas klik kiri mouse atau mengangkat sentuhan jari/stylus, goresan otomatis dikoreksi menjadi kurva stroke halus anti-gerigi.</span>
                    </p>
                </div>

                <!-- OPTION 2: FILE UPLOAD -->
                <div x-show="activeTab === 'upload'" x-cloak class="space-y-4" x-data="{ filePreview: null }">
                    <form method="POST" action="{{ route('profile.signature.update') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div class="border-2 border-dashed border-slate-300 hover:border-[#0B63B5] rounded-2xl p-6 text-center bg-slate-50/70 transition flex flex-col items-center justify-center space-y-3 cursor-pointer relative">
                            <input type="file" name="signature_file" accept="image/png,image/jpeg"
                                   @change="
                                       const f = $event.target.files[0];
                                       if (f) {
                                           const reader = new FileReader();
                                           reader.onload = (e) => filePreview = e.target.result;
                                           reader.readAsDataURL(f);
                                       }
                                   "
                                   class="absolute inset-0 opacity-0 cursor-pointer w-full h-full z-10" required>

                            <template x-if="!filePreview">
                                <div class="space-y-2">
                                    <div class="w-12 h-12 rounded-2xl bg-blue-50 text-[#0B63B5] flex items-center justify-center mx-auto shadow-2xs">
                                        <i data-lucide="image-up" class="w-6 h-6"></i>
                                    </div>
                                    <div class="text-xs font-bold text-slate-800">
                                        Klik untuk memilih berkas gambar tanda tangan
                                    </div>
                                    <div class="text-[11px] text-slate-500 font-medium">
                                        Format PNG (latar belakang transparan direkomendasikan) atau JPG (Maks. 2 MB)
                                    </div>
                                </div>
                            </template>

                            <template x-if="filePreview">
                                <div class="space-y-3 w-full">
                                    <div class="p-3 bg-white rounded-xl border border-slate-200 inline-block shadow-2xs">
                                        <img :src="filePreview" class="max-h-28 max-w-full object-contain mx-auto" alt="Preview Tanda Tangan">
                                    </div>
                                    <span class="text-xs font-bold text-emerald-600 block">Berkas siap disimpan. Klik tombol di bawah.</span>
                                </div>
                            </template>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit"
                                    class="px-5 py-2.5 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white rounded-xl text-xs font-extrabold shadow-sm transition flex items-center gap-2 cursor-pointer">
                                <i data-lucide="upload-cloud" class="w-4 h-4"></i>
                                <span>Unggah & Simpan Tanda Tangan</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>

    </div>
</div>
@endsection
