<?php

namespace App\Services;

use App\Helpers\FormatHelper;
use App\Models\AppSetting;
use App\Models\DocumentSequence;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DocumentNumberService
{
    /**
     * Generate an atomic, sequential document number per type per year.
     * Enforces BR-06: Nomor dokumen dibuat atomik, berurutan per jenis per tahun, tanpa celah.
     */
    public static function generateNextNumber(string $docType, ?Carbon $date = null): string
    {
        $date = $date ?? Carbon::now('Asia/Makassar');
        $year = $date->year;
        $monthRoman = FormatHelper::toRoman($date->month);

        return DB::transaction(function () use ($docType, $year, $monthRoman) {
            DB::table('document_sequences')->updateOrInsert(
                ['doc_type' => $docType, 'year' => $year],
                []
            );

            DB::table('document_sequences')
                ->where('doc_type', $docType)
                ->where('year', $year)
                ->increment('last_value');

            $nextVal = DB::table('document_sequences')
                ->where('doc_type', $docType)
                ->where('year', $year)
                ->value('last_value');

            $seqFormatted = sprintf('%03d', $nextVal); // contoh: 001, 002

            // Prefix format default
            $codeMap = [
                'SURAT_PEMINJAMAN' => 'BMN-PJM',
                'BAST_PEMINJAMAN' => 'BAST-PJM',
                'BAST_PENGEMBALIAN' => 'BAST-KBL',
            ];

            $code = $codeMap[$docType] ?? 'BMN-DOC';
            $satkerCode = AppSetting::get('satker_code', 'BBLSDM.73');

            // Format: {seq}/{code}/{satkerCode}/{monthRoman}/{year}
            // Contoh: 001/BMN-PJM/BBLSDM.73/X/2026
            return "{$seqFormatted}/{$code}/{$satkerCode}/{$monthRoman}/{$year}";
        });
    }
}
