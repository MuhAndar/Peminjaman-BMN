@extends('layouts.app')

@section('title', 'Keputusan Persetujuan Peminjaman BMN')

@section('content')
<div class="space-y-6">
    <!-- Top Bar Navigation -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('persetujuan.index') }}"
               onclick="if (document.referrer && document.referrer.indexOf(window.location.host) !== -1) { history.back(); return false; }"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs font-bold text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800 transition shadow-2xs">
                <i data-lucide="arrow-left" class="w-4 h-4 text-slate-500 dark:text-slate-400"></i>
                <span>Kembali</span>
            </a>
        </div>
        <span class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
            Status: Menunggu Persetujuan Ketua Tim (SOP-4)
        </span>
    </div>

    <!-- Review Dossier -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-6 sm:p-8 shadow-sm space-y-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">Telaah Persetujuan Peminjaman BMN</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Periksa data peminjam, barang BMN, dan catatan Pengelola sebelum memberikan disposisi keputusan resmi.
            </p>
        </div>

        <!-- Pemohon & Periode -->
        <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/60 grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <div>
                <span class="text-slate-400 dark:text-slate-400 block mb-0.5">Pemohon (Pihak Kedua):</span>
                <strong class="text-slate-900 dark:text-white text-sm block">{{ $loan->snap_full_name }}</strong>
                <span class="text-slate-500 dark:text-slate-400">NIP. {{ \App\Helpers\FormatHelper::formatNip($loan->snap_nip) }}</span>
            </div>
            <div>
                <span class="text-slate-400 dark:text-slate-400 block mb-0.5">Jabatan / Golongan:</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">{{ $loan->snap_position ?? '-' }}</span>
                <span class="text-slate-500 dark:text-slate-400 block">{{ $loan->snap_rank_grade ?? '-' }}</span>
            </div>
            <div class="sm:col-span-2 pt-2 border-t border-slate-200/60 dark:border-slate-700/60">
                <span class="text-slate-400 dark:text-slate-400 block mb-0.5">Periode & Jenis Peminjaman:</span>
                <span class="font-bold text-slate-900 dark:text-white tabular-nums">
                    {{ \App\Helpers\FormatHelper::formatDateIndo($loan->start_date) }} s.d. {{ \App\Helpers\FormatHelper::formatDateIndo($loan->end_date) }}
                </span>
                <span class="text-xs text-[#0B63B5] dark:text-blue-400 font-semibold ml-2">({{ $loan->loan_type }})</span>
            </div>
        </div>

        @if($loan->review_note)
            <div class="p-4 rounded-2xl bg-blue-50/70 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800/60 text-xs leading-relaxed">
                <strong class="text-[#0B63B5] dark:text-blue-400 block mb-1">Catatan Hasil Telaah Pengelola BMN (SOP-2 & 3):</strong>
                <p class="text-slate-700 dark:text-slate-200">{{ $loan->review_note }}</p>
            </div>
        @endif

        <!-- Table Barang -->
        <div>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-2">Barang yang Diajukan:</h3>
            <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-bold uppercase">
                            <th class="py-2.5 px-3">No</th>
                            <th class="py-2.5 px-3">Nama Barang</th>
                            <th class="py-2.5 px-3">Merk / Type</th>
                            <th class="py-2.5 px-3">NUP</th>
                            <th class="py-2.5 px-3">Nilai</th>
                            <th class="py-2.5 px-3">Keperluan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        @foreach($loan->items as $idx => $item)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                            <td class="py-2.5 px-3 font-bold text-slate-400 dark:text-slate-500 tabular-nums">{{ $idx + 1 }}</td>
                            <td class="py-2.5 px-3 font-bold text-slate-900 dark:text-white">{{ $item->snap_item_name }}</td>
                            <td class="py-2.5 px-3 text-slate-700 dark:text-slate-300">{{ $item->snap_brand_model ?? '-' }}</td>
                            <td class="py-2.5 px-3 font-semibold text-slate-800 dark:text-slate-200 tabular-nums">{{ $item->snap_nup ?? '-' }}</td>
                            <td class="py-2.5 px-3 tabular-nums text-slate-700 dark:text-slate-300">{{ $item->formatted_snap_value }}</td>
                            <td class="py-2.5 px-3 italic text-slate-600 dark:text-slate-400">{{ $item->purpose }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- FORM KEPUTUSAN KETUA TIM -->
        <div class="pt-6 border-t border-slate-200 dark:border-slate-800 space-y-4">
            <h3 class="text-base font-black text-slate-900 dark:text-white">Keputusan Disposisi Ketua Tim Keuangan & Aset</h3>

            @if(isset($isSelf) && $isSelf)
                <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/50 border border-amber-300 dark:border-amber-800 text-xs text-amber-800 dark:text-amber-200 flex items-start gap-3">
                    <i data-lucide="shield-alert" class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5"></i>
                    <div>
                        <strong class="font-bold block mb-1">Batasan Otorisasi Tata Kelola (BR-08):</strong>
                        <span>Anda tercatat sebagai pemohon (peminjam) pada pengajuan ini. Berdasarkan asas pemisahan tugas dan kepatuhan audit internal, Anda tidak dapat menelaah atau menyetujui pengajuan peminjaman milik Anda sendiri. Disposisi harus dilakukan oleh pejabat yang berwenang atau Administrator.</span>
                    </div>
                </div>
            @else
                <!-- 1. Form Setujui -->
                <form id="approval-form"
                      method="POST"
                      action="{{ route('persetujuan.approve', $loan->id) }}"
                      enctype="multipart/form-data"
                      x-data="ketuaTimApproval()"
                      class="p-5 rounded-2xl bg-emerald-50/70 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 space-y-4">
                    @csrf

                    <div class="flex items-center gap-2 text-emerald-800 dark:text-emerald-300 font-bold text-sm">
                        <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600 dark:text-emerald-400"></i>
                        <span>Opsi 1: Setujui Pengajuan Peminjaman</span>
                    </div>

                    <!-- Hidden inputs untuk tanda tangan & opsi profil -->
                    <input type="hidden" name="signature_source" :value="sigSource">
                    <input type="hidden" name="signature_data" :value="sigData">
                    <input type="hidden" name="save_to_profile" :value="saveToProfile ? '1' : '0'">

                    <!-- Card Status Tanda Tangan Digital Ketua Tim -->
                    <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-emerald-200 dark:border-emerald-800/60 shadow-2xs space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                <i data-lucide="signature" class="w-4 h-4 text-emerald-600 dark:text-emerald-400"></i>
                                Tanda Tangan Digital Ketua Tim (Tercetak di Surat Peminjaman)
                            </span>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 px-2 py-0.5 rounded-full border border-emerald-200 dark:border-emerald-800/60">
                                Wajib Dibubuhkan
                            </span>
                        </div>

                        <!-- Kondisi 1: Tanda tangan SUDAH terpasang -->
                        <template x-if="sigPreviewUrl">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 rounded-xl bg-emerald-50/40 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/70">
                                <div class="flex items-center gap-3.5 min-w-0">
                                    <!-- Thumbnail Spesimen TTD: Selalu White Paper Surface agar Tinta Hitam Tajam & Jelas di Mode Gelap -->
                                    <div class="w-28 h-14 bg-white signature-paper border border-emerald-200 dark:border-emerald-700 rounded-xl p-1.5 flex items-center justify-center shrink-0 shadow-2xs" style="background-color: #ffffff !important;">
                                        <img :src="sigPreviewUrl" alt="Pratinjau TTD" class="max-h-full max-w-full object-contain">
                                    </div>
                                    <div class="text-xs min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-900 dark:text-white">Tanda Tangan Digital Ketua Tim</span>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-900/60 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 shrink-0">
                                                Sudah Terpasang
                                            </span>
                                        </div>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 truncate" x-text="sigSourceLabel()"></p>
                                    </div>
                                </div>
                                <button type="button" @click="openSigModal()"
                                        class="inline-flex items-center justify-center gap-1.5 px-3.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition shadow-2xs active:scale-95 cursor-pointer shrink-0">
                                    <i data-lucide="edit-3" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400"></i>
                                    <span>Ubah Tanda Tangan</span>
                                </button>
                            </div>
                        </template>

                        <!-- Kondisi 2: Tanda tangan BELUM dibubuhkan -->
                        <template x-if="!sigPreviewUrl">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 rounded-xl bg-amber-50/80 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/60">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-400 flex items-center justify-center shrink-0">
                                        <i data-lucide="alert-circle" class="w-5 h-5"></i>
                                    </div>
                                    <div class="text-xs">
                                        <p class="font-bold text-amber-900 dark:text-amber-200">Belum Ada Tanda Tangan yang Terpasang</p>
                                        <p class="text-[11px] text-amber-700 dark:text-amber-300/80 mt-0.5">Surat Peminjaman tidak dapat diterbitkan tanpa tanda tangan digital resmi Anda.</p>
                                    </div>
                                </div>
                                <button type="button" @click="openSigModal()"
                                        class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition cursor-pointer shrink-0">
                                    <i data-lucide="signature" class="w-4 h-4"></i>
                                    <span>Bubuhkan Tanda Tangan Digital</span>
                                </button>
                            </div>
                        </template>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Catatan Persetujuan Disposisi (Opsional):</label>
                        <input type="text" name="note" placeholder="Contoh: Disetujui untuk pelaksanaan tugas kedinasan sesuai jadwal"
                               class="w-full p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-emerald-300 dark:border-emerald-700/70 text-xs text-slate-900 dark:text-slate-200 outline-none focus:ring-2 focus:ring-emerald-200 dark:focus:ring-emerald-800 placeholder-slate-400 dark:placeholder-slate-500">
                    </div>

                    <button type="button"
                            @click="handleApprovalClick($event)"
                            :disabled="!sigPreviewUrl || !sigSource"
                            :title="(!sigPreviewUrl || !sigSource) ? 'Harap bubuhkan tanda tangan digital Ketua Tim terlebih dahulu sebelum menyetujui pengajuan' : ''"
                            :class="(!sigPreviewUrl || !sigSource)
                                ? 'bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border border-slate-300 dark:border-slate-700 cursor-not-allowed shadow-inner select-none'
                                : 'bg-emerald-600 hover:bg-emerald-700 text-white cursor-pointer shadow-sm active:scale-95'"
                            class="px-5 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5">
                        <i data-lucide="badge-check" class="w-4 h-4" :class="(!sigPreviewUrl || !sigSource) ? 'opacity-40' : ''"></i>
                        <span>Setujui & Terbitkan Surat Peminjaman</span>
                    </button>

                    <!-- MODAL POPUP TANDA TANGAN DIGITAL KETUA TIM -->
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
                                        <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-[#0B63B5] dark:text-blue-400 flex items-center justify-center">
                                            <i data-lucide="signature" class="w-5 h-5"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Bubuhkan Tanda Tangan Digital</h3>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                                Menandatangani sebagai: <strong class="text-[#0B63B5] dark:text-blue-400">Ketua Tim Keuangan & Aset</strong>
                                            </p>
                                        </div>
                                    </div>
                                    <button type="button" @click="sigModalOpen = false" class="text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300 p-1 rounded-lg cursor-pointer">
                                        <i data-lucide="x" class="w-5 h-5"></i>
                                    </button>
                                </div>

                                <!-- Tabs (Exact match dengan modal BAST di loans/show) -->
                                <div class="grid grid-cols-3 gap-1 p-1 bg-slate-100 dark:bg-slate-800 rounded-2xl text-xs font-bold text-center border border-slate-200/80 dark:border-slate-700/60">
                                    <button type="button" @click="switchTab('profile')"
                                            :class="sigTab === 'profile' ? 'bg-white dark:bg-[#0B63B5] text-slate-900 dark:text-white shadow-xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-white'"
                                            class="py-2 rounded-xl transition cursor-pointer">
                                        Dari Profil
                                    </button>
                                    <button type="button" @click="switchTab('pad')"
                                            :class="sigTab === 'pad' ? 'bg-white dark:bg-[#0B63B5] text-slate-900 dark:text-white shadow-xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-white'"
                                            class="py-2 rounded-xl transition cursor-pointer">
                                        Papan Gambar
                                    </button>
                                    <button type="button" @click="switchTab('file')"
                                            :class="sigTab === 'file' ? 'bg-white dark:bg-[#0B63B5] text-slate-900 dark:text-white shadow-xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-white'"
                                            class="py-2 rounded-xl transition cursor-pointer">
                                        Unggah Berkas
                                    </button>
                                </div>

                                <!-- Tab 1: Dari Profil -->
                                <div x-show="sigTab === 'profile'" class="space-y-3">
                                    @if(auth()->user()->signature_path)
                                        <div class="p-4 rounded-2xl border-2 border-dashed border-emerald-200 dark:border-emerald-800/80 bg-emerald-50/30 dark:bg-emerald-950/20 text-center space-y-2">
                                            <span class="text-xs font-bold text-emerald-800 dark:text-emerald-400 block">Tanda tangan yang tersimpan di profil Anda:</span>
                                            <div class="p-2.5 bg-white signature-paper rounded-xl border border-dashed border-slate-300 dark:border-slate-400 inline-flex flex-col items-center justify-center min-h-[85px] max-w-[240px] shadow-2xs relative" style="background-color: #ffffff !important;">
                                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-1">Ruang Dokumen (± 2,5 cm)</span>
                                                <img src="{{ asset('storage/' . auth()->user()->signature_path) }}" alt="TTD Profil" class="max-h-[60px] max-w-[155px] object-contain mx-auto">
                                            </div>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400">Tanda tangan ini akan otomatis dicetak pada lembar PDF Surat Peminjaman.</p>
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
                                <div x-show="sigTab === 'pad'" class="space-y-3" style="display: none;">
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="font-bold text-slate-700 dark:text-slate-200 flex items-center gap-1.5">
                                            <i data-lucide="edit-3" class="w-3.5 h-3.5 text-[#0B63B5] dark:text-blue-400"></i>
                                            <span>Goreskan Tanda Tangan:</span>
                                        </span>
                                        <div class="flex items-center gap-2">
                                            <button type="button" @click="undo()" class="text-slate-600 dark:text-slate-300 hover:text-slate-800 dark:hover:text-white font-bold flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 cursor-pointer shadow-2xs" title="Batalkan goresan (Ctrl + Z)">
                                                <i data-lucide="undo" class="w-3 h-3"></i>
                                                <span>Undo <kbd class="text-[9px] text-slate-400 font-normal border border-slate-200 dark:border-slate-700 px-1 rounded">Ctrl+Z</kbd></span>
                                            </button>
                                            <button type="button" @click="clearPad()" class="text-red-600 dark:text-red-400 hover:text-red-700 font-bold flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-lg border border-red-200 dark:border-red-900/60 bg-red-50/50 dark:bg-red-950/30 cursor-pointer shadow-2xs">
                                                <i data-lucide="rotate-ccw" class="w-3 h-3"></i>
                                                <span>Bersihkan</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- KOTAK TTD STANDAR BMN (± 2.5 CM / RASIO 2.5 : 1) -->
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
                                                <canvas id="ketua-sig-pad"
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
                                                    {{ auth()->user()->employee->full_name ?? auth()->user()->name }}
                                                </div>
                                                <div class="text-[10px] text-slate-500 font-medium mt-0.5" style="color: #64748b !important;">
                                                    NIP. {{ \App\Helpers\FormatHelper::formatNip(auth()->user()->employee->nip ?? '') }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="pt-1 text-xs text-slate-500 dark:text-slate-400">
                                        <label class="flex items-center gap-2 cursor-pointer select-none">
                                            <input type="checkbox" x-model="modalSaveToProfile" class="rounded border-slate-300 dark:border-slate-700 text-[#0B63B5] focus:ring-[#0B63B5]">
                                            <span>Simpan juga tanda tangan ini ke profil saya</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Tab 3: Unggah Berkas -->
                                <div x-show="sigTab === 'file'" class="space-y-3" style="display: none;">
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Pilih Berkas Gambar Tanda Tangan (PNG / JPG transparan disarankan):</label>
                                    <input type="file" id="ketua_sig_file_input" name="signature_file" accept="image/png,image/jpeg,image/jpg"
                                           @change="handleFileSelect($event)"
                                           class="w-full p-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs bg-slate-50 dark:bg-slate-800 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-[#0B63B5] file:text-white hover:file:bg-[#0B4F8A]">

                                    <template x-if="filePreviewUrl">
                                        <div class="p-3 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 flex items-center gap-3">
                                            <img :src="filePreviewUrl" alt="File Preview" class="h-12 w-24 object-contain bg-white rounded p-1 border">
                                            <span class="text-xs text-slate-600 dark:text-slate-300 truncate" x-text="selectedFileName"></span>
                                        </div>
                                    </template>

                                    <!-- Checkbox: Default KOSONG / UNCHECKED sesuai permintaan user (Wording ringkas selaras Pengelola & User) -->
                                    <label class="flex items-center gap-2 cursor-pointer pt-1 text-xs text-slate-600 dark:text-slate-400 select-none">
                                        <input type="checkbox" x-model="modalSaveToProfile" class="rounded border-slate-300 dark:border-slate-700 text-[#0B63B5] focus:ring-[#0B63B5]">
                                        <span>Simpan juga berkas ini ke profil saya</span>
                                    </label>
                                </div>

                                <!-- Footer Buttons (Batal & Bubuhkan ke Dokumen) -->
                                <div class="flex items-center justify-center gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                                    <button type="button" @click="sigModalOpen = false"
                                            class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs font-bold transition cursor-pointer">
                                        Batal
                                    </button>
                                    <button type="button" @click="applySignatureFromModal()"
                                            class="px-5 py-2.5 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white rounded-xl text-xs font-bold shadow-md transition flex items-center gap-1.5 cursor-pointer">
                                        <i data-lucide="check" class="w-4 h-4"></i>
                                        <span>Bubuhkan ke Dokumen</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                    <!-- 2. Form Kembalikan untuk Diperbaiki -->
                    <form method="POST" action="{{ route('persetujuan.revision', $loan->id) }}"
                          @submit="clearDraftStorage()"
                          class="p-4 rounded-2xl bg-amber-50/70 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 space-y-3">
                        @csrf
                        <div class="flex items-center gap-2 text-amber-800 dark:text-amber-300 font-bold text-xs">
                            <i data-lucide="undo-2" class="w-4 h-4 text-amber-600 dark:text-amber-400"></i>
                            <span>Opsi 2: Kembalikan (Perlu Perbaikan)</span>
                        </div>
                        <textarea name="note" rows="2" required placeholder="Tuliskan catatan perbaikan wajib..."
                                  class="w-full p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-amber-300 dark:border-amber-700/70 text-xs text-slate-900 dark:text-slate-200 outline-none focus:ring-2 focus:ring-amber-200 dark:focus:ring-amber-800 placeholder-slate-400 dark:placeholder-slate-500"></textarea>
                        <button type="submit"
                                data-confirm="Apakah Anda yakin ingin mengembalikan pengajuan ini ke peminjam untuk diperbaiki?"
                                data-confirm-title="Kembalikan Pengajuan"
                                data-confirm-btn="Ya, Kembalikan untuk Revisi"
                                data-confirm-type="warning"
                                class="w-full py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold transition cursor-pointer">
                            Kembalikan ke Peminjam
                        </button>
                    </form>

                    <!-- 3. Form Tolak Final -->
                    <form method="POST" action="{{ route('persetujuan.reject', $loan->id) }}"
                          @submit="clearDraftStorage()"
                          class="p-4 rounded-2xl bg-red-50/70 dark:bg-red-950/40 border border-red-200 dark:border-red-800/60 space-y-3">
                        @csrf
                        <div class="flex items-center gap-2 text-red-800 dark:text-red-300 font-bold text-xs">
                            <i data-lucide="x-circle" class="w-4 h-4 text-red-600 dark:text-red-400"></i>
                            <span>Opsi 3: Tolak Pengajuan Final</span>
                        </div>
                        <textarea name="note" rows="2" required placeholder="Tuliskan alasan penolakan wajib..."
                                  class="w-full p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-red-300 dark:border-red-700/70 text-xs text-slate-900 dark:text-slate-200 outline-none focus:ring-2 focus:ring-red-200 dark:focus:ring-red-800 placeholder-slate-400 dark:placeholder-slate-500"></textarea>
                        <button type="submit"
                                data-confirm="Apakah Anda yakin ingin MENOLAK pengajuan ini secara final?"
                                data-confirm-title="Penolakan Pengajuan"
                                data-confirm-btn="Ya, Tolak Pengajuan"
                                data-confirm-type="danger"
                                class="w-full py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs font-bold transition cursor-pointer">
                            Tolak Pengajuan
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
function ketuaTimApproval() {
    const loanDbSig = @json($loan->approver_signature ? asset('storage/' . $loan->approver_signature) : '', JSON_UNESCAPED_SLASHES);
    const userProfileSig = @json(auth()->user()->signature_path ? asset('storage/' . auth()->user()->signature_path) : '', JSON_UNESCAPED_SLASHES);
    const draftKey = 'bmn_ketua_sig_draft_{{ $loan->id }}';

    let initialSigUrl = loanDbSig || userProfileSig || '';
    let initialSource = loanDbSig ? 'pad' : (userProfileSig ? 'profile' : '');
    let initialSigData = '';
    let initialSaveToProfile = false;

    // Cek apakah ada draft tanda tangan di localStorage yang tersimpan sebelumnya (bertahan saat refresh)
    try {
        const savedDraft = localStorage.getItem(draftKey);
        if (savedDraft) {
            const parsed = JSON.parse(savedDraft);
            if (parsed && parsed.sigPreviewUrl) {
                initialSigUrl = parsed.sigPreviewUrl;
                initialSource = parsed.sigSource || initialSource;
                initialSigData = parsed.sigData || '';
                initialSaveToProfile = !!parsed.saveToProfile;
            }
        }
    } catch (e) {
        console.warn('Draft storage error:', e);
    }

    return {
        sigModalOpen: false,
        sigTab: initialSource || (userProfileSig ? 'profile' : 'pad'),
        sigSource: initialSource,
        sigData: initialSigData,
        sigPreviewUrl: initialSigUrl,
        saveToProfile: initialSaveToProfile,
        modalSaveToProfile: false, // Default KOSONG / UNCHECKED sesuai permintaan user
        filePreviewUrl: null,
        selectedFileName: '',
        smoothingEnabled: true,
        justCorrected: false,
        isSavingDraft: false,

        canvas: null,
        ctx: null,
        isDrawing: false,
        hasDrawn: false,
        strokes: [],
        currentStroke: [],

        openSigModal() {
            this.sigModalOpen = true;
            this.modalSaveToProfile = false; // Pastikan selalu kosong/uncheck setiap kali modal dibuka

            // Reset kanvas dan pilihan berkas agar saat klik "Ubah Tanda Tangan", kanvas terbuka bersih/fresh tanpa coretan lama
            this.clearPad();
            this.filePreviewUrl = null;
            this.selectedFileName = '';
            const fileInput = document.getElementById('ketua_sig_file_input');
            if (fileInput) fileInput.value = '';

            // Buka tab sesuai sumber terakhir yang digunakan, atau default ke pad jika tidak ada profil
            if (this.sigSource) {
                this.sigTab = this.sigSource;
            } else if (!{{ auth()->user()->signature_path ? 'true' : 'false' }}) {
                this.sigTab = 'pad';
            }

            this.$nextTick(() => {
                if (this.sigTab === 'pad') {
                    this.initCanvas();
                }
                if (window.lucide) lucide.createIcons();
            });
        },

        switchTab(tab) {
            this.sigTab = tab;
            if (tab === 'pad') {
                this.$nextTick(() => this.initCanvas());
            }
        },

        initCanvas() {
            this.$nextTick(() => {
                this.canvas = document.getElementById('ketua-sig-pad');
                if (!this.canvas) return;
                this.ctx = this.canvas.getContext('2d');
                const rect = this.canvas.getBoundingClientRect();
                if (rect.width === 0 || rect.height === 0) return;

                this.canvas.width = rect.width * 2;
                this.canvas.height = rect.height * 2;
                this.ctx.setTransform(1, 0, 0, 1, 0, 0);
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
            this.ctx.lineWidth = 2.5;
            this.ctx.lineCap = 'round';
            this.ctx.lineJoin = 'round';

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

        filterPoints(points, minDistance) {
            if (!points || points.length <= 2) return points || [];
            const result = [points[0]];
            let last = points[0];
            for (let i = 1; i < points.length; i++) {
                const pt = points[i];
                const d = Math.hypot(pt.x - last.x, pt.y - last.y);
                if (d >= minDistance) {
                    result.push(pt);
                    last = pt;
                }
            }
            if (result[result.length - 1] !== points[points.length - 1]) {
                result.push(points[points.length - 1]);
            }
            return result;
        },

        chaikinSmooth(points, iterations = 2) {
            if (!points || points.length <= 2) return points || [];
            let current = points;
            for (let it = 0; it < iterations; it++) {
                const smoothed = [current[0]];
                for (let i = 0; i < current.length - 1; i++) {
                    const p0 = current[i];
                    const p1 = current[i + 1];
                    const q = { x: 0.75 * p0.x + 0.25 * p1.x, y: 0.75 * p0.y + 0.25 * p1.y };
                    const r = { x: 0.25 * p0.x + 0.75 * p1.x, y: 0.25 * p0.y + 0.75 * p1.y };
                    smoothed.push(q);
                    smoothed.push(r);
                }
                smoothed.push(current[current.length - 1]);
                current = smoothed;
            }
            return current;
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

        undo() {
            if (this.strokes.length > 0) {
                this.strokes.pop();
                this.hasDrawn = this.strokes.length > 0;
                this.redrawCanvas();
            }
        },

        clearPad() {
            this.strokes = [];
            this.currentStroke = [];
            this.hasDrawn = false;
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

        handleFileSelect(e) {
            const files = e.target.files;
            if (files && files.length > 0) {
                const file = files[0];
                this.selectedFileName = file.name;
                this.filePreviewUrl = URL.createObjectURL(file);
            }
        },

        applySignatureFromModal() {
            if (this.sigTab === 'profile') {
                const hasProfileSig = {{ auth()->user()->signature_path ? 'true' : 'false' }};
                if (!hasProfileSig) {
                    alert('Anda belum memiliki tanda tangan tersimpan di profil. Silakan pilih tab "Papan Gambar" atau "Unggah Berkas".');
                    return;
                }
                this.sigSource = 'profile';
                this.sigPreviewUrl = '{{ asset("storage/" . auth()->user()->signature_path) }}';
                this.sigData = '';
                this.saveToProfile = false;
                this.sigModalOpen = false;
            } else if (this.sigTab === 'pad') {
                if (!this.hasDrawn || this.strokes.length === 0) {
                    alert('Silakan goreskan tanda tangan pada papan gambar kanvas terlebih dahulu.');
                    return;
                }
                this.redrawCanvas(true);
                const trimmed = this.trimCanvas(this.canvas);
                this.redrawCanvas(false);
                this.sigData = trimmed;
                this.sigPreviewUrl = trimmed;
                this.sigSource = 'pad';
                this.saveToProfile = this.modalSaveToProfile;
                this.sigModalOpen = false;
            } else if (this.sigTab === 'file') {
                const fileInput = document.getElementById('ketua_sig_file_input');
                if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                    alert('Silakan pilih berkas gambar tanda tangan terlebih dahulu.');
                    return;
                }
                this.sigSource = 'file';
                this.sigData = '';
                this.sigPreviewUrl = this.filePreviewUrl;
                this.saveToProfile = this.modalSaveToProfile;
                this.sigModalOpen = false;
            }

            // Simpan langsung ke localStorage agar jika halaman di-refresh, ttd tetap terpasang
            try {
                localStorage.setItem(draftKey, JSON.stringify({
                    sigPreviewUrl: this.sigPreviewUrl,
                    sigData: this.sigData,
                    sigSource: this.sigSource,
                    saveToProfile: this.saveToProfile
                }));
            } catch (e) {
                console.warn('Gagal menyimpan draft tanda tangan ke localStorage:', e);
            }

            // Sinkronisasi otomatis ke server di latar belakang
            this.syncDraftToServer();

            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },

        async syncDraftToServer() {
            if (!this.sigSource) return;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const formData = new FormData();
            formData.append('_token', csrfToken);
            formData.append('signature_source', this.sigSource);
            formData.append('signature_data', this.sigData);
            formData.append('save_to_profile', this.saveToProfile ? '1' : '0');

            if (this.sigSource === 'file') {
                const fileInput = document.getElementById('ketua_sig_file_input');
                if (fileInput && fileInput.files[0]) {
                    formData.append('signature_file', fileInput.files[0]);
                }
            }

            try {
                this.isSavingDraft = true;
                const res = await fetch("{{ route('persetujuan.draft-signature', $loan->id) }}", {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                const data = await res.json();
                if (data.ok && data.signature_url) {
                    this.sigPreviewUrl = data.signature_url;
                    try {
                        localStorage.setItem(draftKey, JSON.stringify({
                            sigPreviewUrl: data.signature_url,
                            sigData: '',
                            sigSource: this.sigSource,
                            saveToProfile: this.saveToProfile
                        }));
                    } catch (e) {}
                }
            } catch (err) {
                console.warn('Autosave server gagal, cadangan localStorage tetap aktif:', err);
            } finally {
                this.isSavingDraft = false;
            }
        },

        clearDraftStorage() {
            try {
                localStorage.removeItem(draftKey);
            } catch (e) {}
        },

        sigSourceLabel() {
            if (this.sigSource === 'profile') return 'Menggunakan spesimen tersimpan di profil Anda';
            if (this.sigSource === 'pad') return 'Digoreskan langsung pada papan gambar digital';
            if (this.sigSource === 'file') return 'Diunggah dari berkas gambar (' + (this.selectedFileName || 'gambar') + ')';
            return '';
        },

        handleApprovalClick(e) {
            e.preventDefault();
            if (!this.sigPreviewUrl || !this.sigSource) {
                alert('Tanda tangan digital Ketua Tim wajib dibubuhkan sebelum menyetujui pengajuan. Silakan bubuhkan tanda tangan Anda terlebih dahulu.');
                this.openSigModal();
                return false;
            }

            window.dispatchEvent(new CustomEvent('open-confirm-modal', {
                detail: {
                    title: 'Persetujuan Peminjaman BMN',
                    message: 'Apakah Anda yakin ingin MENYETUJUI pengajuan peminjaman ini? Surat Peminjaman bernomor otomatis akan langsung diterbitkan beserta tanda tangan resmi Anda.',
                    confirmText: 'Ya, Setujui & Terbitkan',
                    cancelText: 'Batal',
                    type: 'success',
                    callback: () => {
                        this.clearDraftStorage();
                        document.getElementById('approval-form').submit();
                    }
                }
            }));
        }
    };
}
</script>
@endsection
