@extends('layouts.app')

@section('title', 'Profil & Tanda Tangan Saya — BBLSDM Komdigi')

@section('content')
<div class="space-y-6" x-data="{
    activeTab: 'pad',
    canvas: null,
    ctx: null,
    isDrawing: false,
    hasDrawn: false,
    history: [],
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
            this.ctx.lineWidth = 2.5;
            this.ctx.lineCap = 'round';
            this.ctx.lineJoin = 'round';

            this.saveState();
        });
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
    startDraw(e) {
        e.preventDefault();
        this.isDrawing = true;
        const pos = this.getPos(e);
        this.ctx.beginPath();
        this.ctx.moveTo(pos.x, pos.y);
    },
    draw(e) {
        if (!this.isDrawing) return;
        e.preventDefault();
        const pos = this.getPos(e);
        this.ctx.lineTo(pos.x, pos.y);
        this.ctx.stroke();
        this.hasDrawn = true;
    },
    endDraw(e) {
        if (!this.isDrawing) return;
        this.isDrawing = false;
        this.saveState();
    },
    clearCanvas() {
        if (!this.ctx || !this.canvas) return;
        const rect = this.canvas.getBoundingClientRect();
        this.ctx.clearRect(0, 0, rect.width, rect.height);
        this.hasDrawn = false;
        this.history = [];
        this.saveState();
    },
    saveState() {
        if (!this.canvas) return;
        if (this.history.length > 10) this.history.shift();
        this.history.push(this.canvas.toDataURL());
    },
    undo() {
        if (this.history.length <= 1) {
            this.clearCanvas();
            return;
        }
        this.history.pop();
        const prev = this.history[this.history.length - 1];
        const img = new Image();
        img.onload = () => {
            const rect = this.canvas.getBoundingClientRect();
            this.ctx.clearRect(0, 0, rect.width, rect.height);
            this.ctx.drawImage(img, 0, 0, rect.width, rect.height);
        };
        img.src = prev;
    },
    submitPadForm() {
        if (!this.hasDrawn) {
            alert('Silakan goreskan tanda tangan Anda pada papan gambar terlebih dahulu.');
            return;
        }
        const dataUrl = this.canvas.toDataURL('image/png');
        document.getElementById('signature_data_input').value = dataUrl;
        document.getElementById('pad-form').submit();
    }
}" x-init="initCanvas()">

    <!-- HEADER -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-blue-600 transition">Beranda</a>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-[#0B63B5]">Profil & Tanda Tangan Saya</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-2xl bg-blue-100 text-[#0B63B5] flex items-center justify-center shrink-0">
                    <i data-lucide="user-check" class="w-5 h-5"></i>
                </div>
                <span>Profil Akun & Tanda Tangan Digital</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1 font-medium">
                Kelola data diri pegawai dan spesimen tanda tangan resmi untuk pengesahan otomatis Surat Peminjaman, BAST Peminjaman, dan BAST Pengembalian.
            </p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <span class="px-3.5 py-1.5 rounded-full text-xs font-extrabold uppercase bg-blue-50 text-[#0B63B5] border border-blue-200">
                Peran: {{ str_replace('_', ' ', $user->role) }}
            </span>
        </div>
    </div>

    <!-- MAIN TWO COLUMNS -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- LEFT COLUMN: IDENTITAS PEGAWAI (5 COLS) -->
        <div class="lg:col-span-5 space-y-6">
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 space-y-5">
                <div class="flex items-center gap-4 pb-4 border-b border-slate-100">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-[#0B63B5] to-[#12A8E0] text-white font-black text-2xl flex items-center justify-center shadow-md shadow-blue-500/20 shrink-0">
                        {{ substr($user->name, 0, 1) }}
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-base font-extrabold text-slate-900 truncate">{{ $user->name }}</h2>
                        <span class="text-xs text-slate-500 block truncate">{{ $user->email }}</span>
                        <span class="mt-1 inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            Akun Aktif
                        </span>
                    </div>
                </div>

                <div class="space-y-3.5 text-xs">
                    <div>
                        <span class="text-slate-400 font-bold uppercase tracking-wider text-[10px] block">NIP (Nomor Induk Pegawai)</span>
                        <span class="text-slate-800 font-semibold tabular-nums text-sm">
                            {{ $user->employee->nip ? \App\Helpers\FormatHelper::formatNip($user->employee->nip) : 'Non-PNS / PPNPN' }}
                        </span>
                    </div>

                    <div>
                        <span class="text-slate-400 font-bold uppercase tracking-wider text-[10px] block">Pangkat / Golongan</span>
                        <span class="text-slate-800 font-semibold text-sm">
                            {{ $user->employee->rank_grade ?? '-' }}
                        </span>
                    </div>

                    <div>
                        <span class="text-slate-400 font-bold uppercase tracking-wider text-[10px] block">Jabatan / Posisi</span>
                        <span class="text-slate-800 font-semibold text-sm">
                            {{ $user->employee->position ?? '-' }}
                        </span>
                    </div>

                    <div>
                        <span class="text-slate-400 font-bold uppercase tracking-wider text-[10px] block">Status Kepegawaian</span>
                        <span class="text-slate-800 font-semibold text-sm">
                            {{ $user->employee->employment_status ?? 'PNS' }}
                        </span>
                    </div>

                    <div>
                        <span class="text-slate-400 font-bold uppercase tracking-wider text-[10px] block">Alamat Rumah</span>
                        <p class="text-slate-700 font-medium leading-relaxed mt-0.5">
                            {{ $user->employee->home_address ?? 'Alamat belum diisi pada master data' }}
                        </p>
                    </div>

                    <div>
                        <span class="text-slate-400 font-bold uppercase tracking-wider text-[10px] block">Unit Kerja</span>
                        <span class="text-slate-800 font-semibold text-sm block">
                            BBLSDM Komunikasi dan Digital Makassar
                        </span>
                    </div>
                </div>
            </div>

            <!-- TANDA TANGAN AKTIF SAAT INI -->
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
                        <i data-lucide="signature" class="w-4 h-4 text-[#0B63B5]"></i>
                        <span>Spesimen Tanda Tangan Tersimpan</span>
                    </h3>
                </div>

                @if($user->signature_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->signature_path))
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 flex flex-col items-center justify-center space-y-3">
                        <div class="p-4 bg-white rounded-xl border border-slate-200 w-full flex items-center justify-center min-h-[120px] shadow-2xs">
                            <img src="{{ asset('storage/' . $user->signature_path) }}" alt="Tanda Tangan {{ $user->name }}" class="max-h-24 max-w-full object-contain">
                        </div>
                        <div class="w-full flex items-center justify-between text-[11px] text-slate-500 font-medium">
                            <span class="flex items-center gap-1 text-emerald-600 font-bold">
                                <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                                Siap digunakan di BAST & Dokumen
                            </span>
                            <form method="POST" action="{{ route('profile.signature.delete') }}" onsubmit="return confirm('Hapus spesimen tanda tangan ini dari profil?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700 font-bold hover:underline transition flex items-center gap-1">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    <span>Hapus</span>
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="p-6 rounded-2xl bg-amber-50/70 border border-amber-200 text-center space-y-2">
                        <div class="w-10 h-10 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center mx-auto">
                            <i data-lucide="alert-circle" class="w-5 h-5"></i>
                        </div>
                        <h4 class="text-xs font-bold text-amber-900">Belum Ada Tanda Tangan Tersimpan</h4>
                        <p class="text-[11px] text-amber-700">
                            Silakan gambar langsung pada papan kanvas di sebelah kanan atau unggah gambar tanda tangan Anda.
                        </p>
                    </div>
                @endif
            </div>
        </div>

        <!-- RIGHT COLUMN: KELOLA TANDA TANGAN (7 COLS) -->
        <div class="lg:col-span-7 space-y-6">
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 space-y-5">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div>
                        <h2 class="text-base font-black text-slate-900 flex items-center gap-2">
                            <i data-lucide="pen-tool" class="w-5 h-5 text-[#0B63B5]"></i>
                            <span>Buat / Perbarui Tanda Tangan Digital</span>
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Pilih metode penambahan tanda tangan: gambar manual di papan atau unggah berkas gambar.
                        </p>
                    </div>
                </div>

                <!-- TAB SWITCHER -->
                <div class="flex rounded-2xl bg-slate-100 p-1">
                    <button type="button" @click="activeTab = 'pad'; initCanvas()"
                            :class="activeTab === 'pad' ? 'bg-white text-slate-900 font-extrabold shadow-sm' : 'text-slate-600 hover:text-slate-900 font-bold'"
                            class="flex-1 py-2 rounded-xl text-xs flex items-center justify-center gap-2 transition">
                        <i data-lucide="pencil" class="w-4 h-4"></i>
                        <span>Gambar di Papan Kanvas</span>
                    </button>
                    <button type="button" @click="activeTab = 'upload'"
                            :class="activeTab === 'upload' ? 'bg-white text-slate-900 font-extrabold shadow-sm' : 'text-slate-600 hover:text-slate-900 font-bold'"
                            class="flex-1 py-2 rounded-xl text-xs flex items-center justify-center gap-2 transition">
                        <i data-lucide="upload" class="w-4 h-4"></i>
                        <span>Unggah Berkas Gambar</span>
                    </button>
                </div>

                <!-- OPTION 1: CANVAS SIGNATURE PAD -->
                <div x-show="activeTab === 'pad'" class="space-y-4">
                    <div class="relative bg-slate-50 border-2 border-dashed border-slate-300 rounded-2xl p-2 overflow-hidden">
                        <canvas id="signature-pad"
                                @mousedown="startDraw($event)"
                                @mousemove="draw($event)"
                                @mouseup="endDraw($event)"
                                @mouseleave="endDraw($event)"
                                @touchstart="startDraw($event)"
                                @touchmove="draw($event)"
                                @touchend="endDraw($event)"
                                class="w-full h-56 bg-white rounded-xl shadow-inner cursor-crosshair touch-none block"></canvas>

                        <!-- Guide Baseline -->
                        <div class="absolute left-6 right-6 bottom-14 border-b border-slate-200 pointer-events-none flex justify-between text-[10px] text-slate-300 select-none">
                            <span>Area tanda tangan</span>
                            <span>Garis acuan</span>
                        </div>
                    </div>

                    <!-- Action Controls for Canvas -->
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <button type="button" @click="clearCanvas()"
                                    class="px-3.5 py-2 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-100 text-xs font-bold transition flex items-center gap-1.5 shadow-2xs">
                                <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                                <span>Bersihkan</span>
                            </button>
                            <button type="button" @click="undo()"
                                    class="px-3.5 py-2 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-100 text-xs font-bold transition flex items-center gap-1.5 shadow-2xs">
                                <i data-lucide="undo" class="w-3.5 h-3.5"></i>
                                <span>Batalkan Goresan</span>
                            </button>
                        </div>

                        <form id="pad-form" method="POST" action="{{ route('profile.signature.update') }}">
                            @csrf
                            <input type="hidden" name="signature_data" id="signature_data_input">
                            <button type="button" @click="submitPadForm()"
                                    class="px-5 py-2.5 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white rounded-xl text-xs font-extrabold shadow-sm transition flex items-center gap-2">
                                <i data-lucide="check" class="w-4 h-4"></i>
                                <span>Simpan Tanda Tangan Kanvas</span>
                            </button>
                        </form>
                    </div>

                    <p class="text-[11px] text-slate-500 italic">
                        Tip: Anda dapat menggambar tanda tangan menggunakan mouse di komputer atau jari/stylus di layar sentuh ponsel & tablet.
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
                                    class="px-5 py-2.5 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white rounded-xl text-xs font-extrabold shadow-sm transition flex items-center gap-2">
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
