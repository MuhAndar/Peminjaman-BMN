@extends('layouts.app')

@section('title', $asset->item_name . ' — BMN')

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-2">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('assets.index') }}"
           onclick="if (document.referrer && document.referrer.indexOf(window.location.host) !== -1) { history.back(); return false; }"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 hover:text-slate-900 hover:bg-slate-50 transition shadow-2xs">
            <i data-lucide="arrow-left" class="w-4 h-4 text-slate-500"></i>
            <span>Kembali</span>
        </a>
    </div>

    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-5">
            <div>
                <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-slate-100 text-slate-700">
                    {{ $asset->category->label ?? '-' }}
                </span>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-2">
                    {{ $asset->item_name }}
                </h1>
                <p class="text-sm font-semibold text-slate-600">
                    {{ $asset->brand_model_combined }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                @php
                    $opStatus = $asset->operational_status;
                @endphp
                @if($opStatus['code'] === 'AVAILABLE')
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        Tersedia (Kondisi Baik)
                    </span>
                @elseif($opStatus['code'] === 'PENDING_APPROVAL')
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-[#0B63B5] border border-blue-200">
                        Dalam proses pengajuan
                    </span>
                @elseif($opStatus['code'] === 'ON_LOAN')
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                        Sedang Dipinjam
                    </span>
                @else
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-700 border border-rose-200">
                        {{ $opStatus['label'] }}
                    </span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                <span class="text-slate-400 block mb-0.5">Kode BMN</span>
                <strong class="text-slate-800 tabular-nums">{{ $asset->bmn_code ?? '-' }}</strong>
            </div>
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                <span class="text-slate-400 block mb-0.5">NUP</span>
                <strong class="text-slate-800 tabular-nums">{{ $asset->nup ?? '-' }}</strong>
            </div>
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                <span class="text-slate-400 block mb-0.5">Tahun Perolehan</span>
                <strong class="text-slate-800 tabular-nums">{{ $asset->acquisition_year }}</strong>
            </div>
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                <span class="text-slate-400 block mb-0.5">Nilai Perolehan</span>
                <strong class="text-slate-800 tabular-nums">{{ $asset->formatted_value }}</strong>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                <span class="text-slate-400 block mb-0.5">Jenis BMN</span>
                <strong class="text-slate-800">{{ $asset->jenis_bmn ?? '-' }}</strong>
            </div>
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                <span class="text-slate-400 block mb-0.5">Nilai Buku</span>
                <strong class="text-slate-800 tabular-nums">{{ $asset->formatted_nilai_buku }}</strong>
            </div>
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                <span class="text-slate-400 block mb-0.5">Lokasi Ruang</span>
                <strong class="text-slate-800">{{ $asset->lokasi_ruang ?? 'Belum berlokasi' }}</strong>
            </div>
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                <span class="text-slate-400 block mb-0.5">Status Penggunaan</span>
                <strong class="text-slate-800">{{ $asset->status_penggunaan ?? 'Aktif Operasional' }}</strong>
            </div>
        </div>

        @if($asset->plate_number)
            <div class="p-4 rounded-2xl bg-blue-50/60 border border-blue-200/80 text-xs">
                <span class="text-slate-500 font-medium">Nomor Registrasi Kendaraan (Plat):</span>
                <strong class="text-slate-900 text-sm ml-2 font-mono">{{ $asset->plate_number }}</strong>
            </div>
        @endif

        @if($asset->no_psp || $asset->no_dokumen || $asset->no_bpkp || $asset->no_stnk)
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs space-y-1">
                <strong class="text-slate-800 block mb-1">Informasi Dokumen & Kepemilikan (SIMAN):</strong>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-slate-600">
                    @if($asset->no_psp) <div><span class="text-slate-400">No PSP:</span> <span class="font-mono">{{ $asset->no_psp }}</span></div> @endif
                    @if($asset->no_dokumen) <div><span class="text-slate-400">No Dokumen:</span> <span class="font-mono">{{ $asset->no_dokumen }}</span></div> @endif
                    @if($asset->no_bpkp) <div><span class="text-slate-400">No BPKP:</span> <span class="font-mono">{{ $asset->no_bpkp }}</span></div> @endif
                    @if($asset->no_stnk) <div><span class="text-slate-400">No STNK:</span> <span class="font-mono">{{ $asset->no_stnk }}</span></div> @endif
                </div>
            </div>
        @endif

        @if($asset->notes)
            <div class="text-xs text-slate-600 bg-slate-50 p-4 rounded-2xl border border-slate-200">
                <strong class="text-slate-800 block mb-1">Catatan Inventaris:</strong>
                <p>{{ $asset->notes }}</p>
            </div>
        @endif

        <div class="pt-4 border-t border-slate-100">
            @if($asset->isAvailable())
                <a href="{{ route('loans.create', ['asset_id' => $asset->id]) }}"
                   class="inline-flex items-center gap-2 px-6 py-2.5 bg-[#0B63B5] hover:bg-[#0B4F8A] text-white text-xs font-bold rounded-xl shadow-md transition">
                    <i data-lucide="plus-circle" class="w-4 h-4"></i>
                    <span>Ajukan Peminjaman Barang Ini</span>
                </a>
            @else
                <button disabled class="px-6 py-2.5 bg-slate-100 text-slate-400 text-xs font-bold rounded-xl cursor-not-allowed">
                    Barang Sedang Dipinjam
                </button>
            @endif
        </div>
    </div>
</div>
@endsection
