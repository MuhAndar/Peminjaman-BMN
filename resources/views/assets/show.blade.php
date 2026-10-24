@extends('layouts.app')

@section('title', $asset->item_name . ' — BMN')

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-2">
        <a href="{{ route('assets.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 hover:text-slate-900 hover:bg-slate-50 transition shadow-2xs">
            <i data-lucide="arrow-left" class="w-4 h-4 text-slate-500"></i>
            <span>Kembali ke Katalog BMN</span>
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
                @if($asset->isAvailable())
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        Tersedia (Kondisi Baik)
                    </span>
                @else
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                        Status: {{ $asset->status }}
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

        @if($asset->plate_number)
            <div class="p-4 rounded-2xl bg-blue-50/60 border border-blue-200/80 text-xs">
                <span class="text-slate-500 font-medium">Nomor Registrasi Kendaraan (Plat):</span>
                <strong class="text-slate-900 text-sm ml-2 font-mono">{{ $asset->plate_number }}</strong>
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
