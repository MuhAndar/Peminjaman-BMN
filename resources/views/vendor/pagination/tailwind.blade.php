@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi Halaman" class="flex flex-col sm:flex-row items-center justify-between gap-4 select-none">
        <!-- Info Ringkasan Jumlah Data -->
        <div class="text-xs text-slate-500 font-medium order-2 sm:order-1 text-center sm:text-left">
            @if ($paginator->firstItem())
                Menampilkan
                <span class="font-extrabold text-slate-800 tabular-nums">{{ $paginator->firstItem() }}</span>
                sampai
                <span class="font-extrabold text-slate-800 tabular-nums">{{ $paginator->lastItem() }}</span>
                dari
                <span class="font-extrabold text-slate-900 tabular-nums">{{ $paginator->total() }}</span>
                data
            @else
                Total <span class="font-extrabold text-slate-900 tabular-nums">{{ $paginator->total() }}</span> data
            @endif
        </div>

        <!-- Tampilan Paginasi Mobile (<640px): Ringkas, Seimbang, Tidak Melebar -->
        <div class="flex sm:hidden items-center justify-between w-full gap-2 order-1">
            {{-- Tombol Sebelumnya Mobile --}}
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-bold text-slate-400 bg-slate-100/90 border border-slate-200/60 rounded-xl cursor-not-allowed opacity-60">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                    <span>Sebelumnya</span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                   class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-[#0B63B5] hover:text-white hover:border-[#0B63B5] shadow-2xs active:scale-95 transition">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                    <span>Sebelumnya</span>
                </a>
            @endif

            {{-- Indikator Halaman Tengah Mobile --}}
            <div class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 rounded-xl border border-slate-200/80 dark:border-slate-700 text-xs font-extrabold text-slate-800 dark:text-slate-200 tabular-nums">
                {{ $paginator->currentPage() }} <span class="text-slate-400 font-normal">/</span> {{ $paginator->lastPage() }}
            </div>

            {{-- Tombol Berikutnya Mobile --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                   class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-[#0B63B5] hover:text-white hover:border-[#0B63B5] shadow-2xs active:scale-95 transition">
                    <span>Berikutnya</span>
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </a>
            @else
                <span aria-disabled="true" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-bold text-slate-400 bg-slate-100/90 border border-slate-200/60 rounded-xl cursor-not-allowed opacity-60">
                    <span>Berikutnya</span>
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </span>
            @endif
        </div>

        <!-- Tampilan Paginasi Desktop (>=640px): Lengkap dengan Nomor Halaman -->
        <div class="hidden sm:inline-flex items-center gap-1.5 order-1 sm:order-2 flex-wrap justify-center">
            {{-- Tombol Sebelumnya (Previous) --}}
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="Sebelumnya"
                      class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-slate-400 bg-slate-100/80 border border-slate-200/60 rounded-xl cursor-not-allowed opacity-60">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                    <span>Sebelumnya</span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Sebelumnya"
                   class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-slate-700 bg-white border border-slate-200/80 rounded-xl hover:bg-[#0B63B5] hover:text-white hover:border-[#0B63B5] shadow-2xs hover:shadow-xs transition duration-150 active:scale-95">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                    <span>Sebelumnya</span>
                </a>
            @endif

            {{-- Nomor Halaman (Pills) --}}
            <div class="inline-flex items-center gap-1">
                @foreach ($elements as $element)
                    {{-- Separator Titik Tiga (...) --}}
                    @if (is_string($element))
                        <span aria-disabled="true" class="w-8 h-8 flex items-center justify-center text-xs font-bold text-slate-400">
                            {{ $element }}
                        </span>
                    @endif

                    {{-- Link Nomor Halaman --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page"
                                      class="w-8 h-8 flex items-center justify-center text-xs font-black rounded-xl bg-[#0B63B5] text-white shadow-xs shadow-blue-500/30 ring-2 ring-[#0B63B5]/20 tabular-nums">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="{{ $url }}"
                                   aria-label="Ke halaman {{ $page }}"
                                   class="w-8 h-8 flex items-center justify-center text-xs font-bold rounded-xl text-slate-700 bg-white border border-slate-200/80 hover:bg-blue-50 hover:text-[#0B63B5] hover:border-blue-300 shadow-2xs transition duration-150 tabular-nums active:scale-95">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            {{-- Tombol Berikutnya (Next) --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Berikutnya"
                   class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-slate-700 bg-white border border-slate-200/80 rounded-xl hover:bg-[#0B63B5] hover:text-white hover:border-[#0B63B5] shadow-2xs hover:shadow-xs transition duration-150 active:scale-95">
                    <span>Berikutnya</span>
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </a>
            @else
                <span aria-disabled="true" aria-label="Berikutnya"
                      class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-slate-400 bg-slate-100/80 border border-slate-200/60 rounded-xl cursor-not-allowed opacity-60">
                    <span>Berikutnya</span>
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </span>
            @endif
        </div>
    </nav>
@endif
