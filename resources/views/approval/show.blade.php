@extends('layouts.app')

@section('title', 'Keputusan Persetujuan Peminjaman BMN')
@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('approval.index') }}"
               onclick="if (document.referrer && document.referrer.indexOf(window.location.host) !== -1) { history.back(); return false; }"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 hover:text-slate-900 hover:bg-slate-50 transition shadow-2xs">
                <i data-lucide="arrow-left" class="w-4 h-4 text-slate-500"></i>
                <span>Kembali</span>
            </a>
        </div>
        <span class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
            Status: Menunggu Persetujuan Ketua Tim (SOP-4)
        </span>
    </div>

    <!-- Review Dossier -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm space-y-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Telaah Persetujuan Peminjaman BMN</h1>
            <p class="text-xs text-slate-500 mt-1">
                Periksa data peminjam, barang BMN, dan catatan Pengelola sebelum memberikan disposisi keputusan.
            </p>
        </div>

        <!-- Pemohon & Periode -->
        <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200/80 grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <div>
                <span class="text-slate-400 block mb-0.5">Pemohon (Pihak Kedua):</span>
                <strong class="text-slate-900 text-sm block">{{ $loan->snap_full_name }}</strong>
                <span class="text-slate-500">NIP. {{ \App\Helpers\FormatHelper::formatNip($loan->snap_nip) }}</span>
            </div>
            <div>
                <span class="text-slate-400 block mb-0.5">Jabatan / Golongan:</span>
                <span class="font-bold text-slate-800">{{ $loan->snap_position ?? '-' }}</span>
                <span class="text-slate-500 block">{{ $loan->snap_rank_grade ?? '-' }}</span>
            </div>
            <div class="sm:col-span-2 pt-2 border-t border-slate-200/60">
                <span class="text-slate-400 block mb-0.5">Periode & Jenis Peminjaman:</span>
                <span class="font-bold text-slate-900 tabular-nums">
                    {{ \App\Helpers\FormatHelper::formatDateIndo($loan->start_date) }} s.d. {{ \App\Helpers\FormatHelper::formatDateIndo($loan->end_date) }}
                </span>
                <span class="text-xs text-[#0B63B5] font-semibold ml-2">({{ $loan->loan_type }})</span>
            </div>
        </div>

        @if($loan->review_note)
            <div class="p-4 rounded-2xl bg-blue-50/70 border border-blue-200 text-xs">
                <strong class="text-[#0B63B5] block mb-1">Catatan Hasil Telaah Pengelola BMN (SOP-2 & 3):</strong>
                <p class="text-slate-700 leading-relaxed">{{ $loan->review_note }}</p>
            </div>
        @endif

        <!-- Table Barang -->
        <div>
            <h3 class="text-sm font-bold text-slate-900 mb-2">Barang yang Diajukan:</h3>
            <div class="overflow-x-auto rounded-2xl border border-slate-200">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase">
                            <th class="py-2.5 px-3">No</th>
                            <th class="py-2.5 px-3">Nama Barang</th>
                            <th class="py-2.5 px-3">Merk / Type</th>
                            <th class="py-2.5 px-3">NUP</th>
                            <th class="py-2.5 px-3">Nilai</th>
                            <th class="py-2.5 px-3">Keperluan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($loan->items as $idx => $item)
                        <tr>
                            <td class="py-2.5 px-3 font-bold text-slate-400 tabular-nums">{{ $idx + 1 }}</td>
                            <td class="py-2.5 px-3 font-bold text-slate-900">{{ $item->snap_item_name }}</td>
                            <td class="py-2.5 px-3 text-slate-700">{{ $item->snap_brand_model ?? '-' }}</td>
                            <td class="py-2.5 px-3 font-semibold text-slate-800 tabular-nums">{{ $item->snap_nup ?? '-' }}</td>
                            <td class="py-2.5 px-3 tabular-nums text-slate-700">{{ $item->formatted_snap_value }}</td>
                            <td class="py-2.5 px-3 italic text-slate-600">{{ $item->purpose }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- FORM KEPUTUSAN KETUA TIM -->
        <div class="pt-6 border-t border-slate-200 space-y-4">
            <h3 class="text-base font-black text-slate-900">Keputusan Disposisi Ketua Tim Keuangan & Aset</h3>

            <!-- 1. Form Setujui -->
            <form method="POST" action="{{ route('persetujuan.approve', $loan->id) }}" class="p-4 rounded-2xl bg-emerald-50/70 border border-emerald-200 space-y-3">
                @csrf
                <div class="flex items-center gap-2 text-emerald-800 font-bold text-sm">
                    <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600"></i>
                    <span>Opsi 1: Setujui Pengajuan Peminjaman</span>
                </div>

                @if(auth()->user()->signature_path)
                    <div class="text-[11px] text-emerald-700 bg-white/80 p-2 rounded-xl border border-emerald-200 flex items-center gap-2">
                        <i data-lucide="signature" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                        <span>Tanda tangan digital Anda tersimpan di sistem dan akan otomatis dibubuhkan pada dokumen Surat Peminjaman.</span>
                    </div>
                @else
                    <div class="text-[11px] text-amber-800 bg-amber-50 p-2 rounded-xl border border-amber-300 flex items-center gap-2">
                        <i data-lucide="alert-circle" class="w-4 h-4 text-amber-600 shrink-0"></i>
                        <span>Perhatian: Anda belum menyimpan tanda tangan di <a href="{{ route('profile.show', ['tab' => 'signature']) }}" target="_blank" class="underline font-bold text-amber-900 hover:text-black">Menu Profil</a>. Disarankan menyimpannya agar otomatis tercetak pada dokumen resmi.</span>
                    </div>
                @endif

                <input type="text" name="note" placeholder="Catatan persetujuan (opsional, contoh: Disetujui untuk penugasan luar kota)"
                       class="w-full p-2.5 rounded-xl bg-white border border-emerald-300 text-xs outline-none focus:ring-2 focus:ring-emerald-200">
                <button type="submit"
                        data-confirm="Apakah Anda yakin ingin MENYETUJUI pengajuan peminjaman ini? Surat Peminjaman bernomor otomatis akan langsung diterbitkan."
                        data-confirm-title="Persetujuan Peminjaman BMN"
                        data-confirm-btn="Ya, Setujui & Terbitkan"
                        data-confirm-type="success"
                        class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-sm transition flex items-center gap-1.5">
                    <i data-lucide="badge-check" class="w-4 h-4"></i>
                    <span>Setujui & Terbitkan Surat Peminjaman</span>
                </button>
            </form>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                <!-- 2. Form Kembalikan untuk Diperbaiki -->
                <form method="POST" action="{{ route('persetujuan.revision', $loan->id) }}" class="p-4 rounded-2xl bg-amber-50/70 border border-amber-200 space-y-3">
                    @csrf
                    <div class="flex items-center gap-2 text-amber-800 font-bold text-xs">
                        <i data-lucide="undo-2" class="w-4 h-4 text-amber-600"></i>
                        <span>Opsi 2: Kembalikan (Perlu Perbaikan)</span>
                    </div>
                    <textarea name="note" rows="2" required placeholder="Tuliskan catatan perbaikan wajib..."
                              class="w-full p-2.5 rounded-xl bg-white border border-amber-300 text-xs outline-none focus:ring-2 focus:ring-amber-200"></textarea>
                    <button type="submit"
                            data-confirm="Apakah Anda yakin ingin mengembalikan pengajuan ini ke peminjam untuk diperbaiki?"
                            data-confirm-title="Kembalikan Pengajuan"
                            data-confirm-btn="Ya, Kembalikan untuk Revisi"
                            data-confirm-type="warning"
                            class="w-full py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold transition">
                        Kembalikan ke Peminjam
                    </button>
                </form>

                <!-- 3. Form Tolak Final -->
                <form method="POST" action="{{ route('persetujuan.reject', $loan->id) }}" class="p-4 rounded-2xl bg-red-50/70 border border-red-200 space-y-3">
                    @csrf
                    <div class="flex items-center gap-2 text-red-800 font-bold text-xs">
                        <i data-lucide="x-circle" class="w-4 h-4 text-red-600"></i>
                        <span>Opsi 3: Tolak Pengajuan Final</span>
                    </div>
                    <textarea name="note" rows="2" required placeholder="Tuliskan alasan penolakan wajib..."
                              class="w-full p-2.5 rounded-xl bg-white border border-red-300 text-xs outline-none focus:ring-2 focus:ring-red-200"></textarea>
                    <button type="submit"
                            data-confirm="Apakah Anda yakin ingin MENOLAK pengajuan ini secara final?"
                            data-confirm-title="Penolakan Pengajuan"
                            data-confirm-btn="Ya, Tolak Pengajuan"
                            data-confirm-type="danger"
                            class="w-full py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs font-bold transition">
                        Tolak Pengajuan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
