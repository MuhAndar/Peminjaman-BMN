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

<div class="space-y-6">
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

            <!-- ROLE-BASED ACTION PANELS -->
            <!-- 1. Action Peminjam: Kembalikan BMN -->
            @if($loan->status === 'ACTIVE' && ($isOwner || $isPengelola))
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
                    <form method="POST" action="{{ route('kelola.confirm-active', $loan->id) }}">
                        @csrf
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

                    <form method="POST" action="{{ route('kelola.process-return-check', $loan->id) }}" class="space-y-4">
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
                        <span class="text-[10px] text-slate-400 font-semibold">SK SOP B-489 BBPSDMP</span>
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
</div>
@endsection
