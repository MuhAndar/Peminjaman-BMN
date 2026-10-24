@extends('pdf.layout')

@section('title', 'Berita Acara Serah Terima (BAST) Pengembalian BMN')

@section('content')
    <div class="judul-wrapper">
        <div class="doc-title">
            <span class="doc-title-line">BERITA ACARA SERAH TERIMA PENGEMBALIAN</span>
            <span class="doc-title-line">BARANG INVENTARIS BBPSDMP-MAKASSAR</span>
        </div>
        <div class="doc-number">
            Nomor : {{ $document->doc_number ?? '...............................................................' }}
        </div>
    </div>

    <div style="margin-bottom: 5pt;">Yang bertanda tangan di bawah ini:</div>
    <table class="info-table" style="margin-bottom: 2pt;">
        <tr>
            <td class="label">N a m a</td>
            <td class="colon">:</td>
            <td><strong>{{ $signatoryPengelola->employee->full_name ?? 'Muhammad Agung, S.Ds.' }}</strong></td>
        </tr>
        <tr>
            <td class="label">N I P</td>
            <td class="colon">:</td>
            <td>{{ \App\Helpers\FormatHelper::formatNip($signatoryPengelola->employee->nip ?? '199803112025041003') }}</td>
        </tr>
        <tr>
            <td class="label">Pangkat / Golongan</td>
            <td class="colon">:</td>
            <td>{{ $signatoryPengelola->employee->rank_grade ?? 'Penata Muda, III/a' }}</td>
        </tr>
    </table>
    <div style="margin-bottom: 6pt;">sebagai <strong>Pihak Pertama</strong> (Pengelola Aset), bersama</div>

    <table class="info-table" style="margin-bottom: 2pt;">
        <tr>
            <td class="label">N a m a</td>
            <td class="colon">:</td>
            <td><strong>{{ $loan->snap_full_name }}</strong></td>
        </tr>
        <tr>
            <td class="label">N I P</td>
            <td class="colon">:</td>
            <td>{{ \App\Helpers\FormatHelper::formatNip($loan->snap_nip) }}</td>
        </tr>
        <tr>
            <td class="label">Pangkat / Golongan</td>
            <td class="colon">:</td>
            <td>{{ $loan->snap_rank_grade ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">J a b a t a n</td>
            <td class="colon">:</td>
            <td>{{ $loan->snap_position ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Alamat Rumah</td>
            <td class="colon">:</td>
            <td>{{ $loan->snap_home_address ?? '-' }}</td>
        </tr>
    </table>
    <div style="margin-bottom: 6pt;">sebagai <strong>Pihak Kedua</strong> (Peminjam).</div>

    <div class="paragraph no-indent" style="margin-bottom: 6pt;">
        Berdasarkan surat peminjaman No: <strong>{{ $suratPeminjamanDoc->doc_number ?? '…………………….' }}</strong> maka dengan ini <strong>Pihak Kedua</strong> menyerahkan kembali barang inventaris kantor kepada <strong>Pihak Pertama</strong> berupa:
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">NO</th>
                <th style="width: 28%;">Nama Barang</th>
                <th style="width: 19%;">Merk</th>
                <th style="width: 7%;">NUP</th>
                <th style="width: 8%;">Tahun</th>
                <th style="width: 16%;">Nilai</th>
                <th style="width: 17%;">Kondisi Saat Dikembalikan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($loan->items as $index => $item)
            <tr>
                <td class="center">{{ $index + 1 }}</td>
                <td>{{ $item->snap_item_name }}</td>
                <td>{{ $item->snap_brand_model ?? '-' }}</td>
                <td class="center">{{ $item->snap_nup ?? '-' }}</td>
                <td class="center">{{ $item->snap_year ?? '-' }}</td>
                <td class="right">{{ $item->formatted_snap_value }}</td>
                <td class="center"><strong>{{ $item->condition_in ?? 'Baik' }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @php
        // Evaluasi kondisi barang saat dikembalikan
        $hasHilang = $loan->items->contains(fn($i) => in_array($i->condition_in, ['Tidak Lengkap/Hilang', 'TIDAK_LENGKAP_HILANG']));
        $hasRusak = $loan->items->contains(fn($i) => in_array($i->condition_in, ['Rusak', 'RUSAK']));
        $allBaik = !$hasHilang && !$hasRusak;

        // Kumpulkan catatan tindak lanjut jika ada
        $allFollowUps = $loan->items->pluck('follow_up_note')->filter()->implode("; \n");
    @endphp

    <div style="margin-top: 6pt; margin-bottom: 4pt;">
        Barang diterima kembali oleh <strong>Pihak Pertama</strong> dalam kondisi:
    </div>
    <div class="checkbox-container">
        &nbsp;&nbsp;&nbsp;&nbsp;
        <span class="cb-box">{{ $allBaik ? 'X' : '' }}</span> Baik
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <span class="cb-box">{{ ($hasRusak && !$hasHilang) ? 'X' : '' }}</span> Rusak
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <span class="cb-box">{{ $hasHilang ? 'X' : '' }}</span> Tidak Lengkap/Hilang
    </div>

    <div class="box-follow-up">
        <strong>Tindak lanjut yang diperlukan:</strong><br>
        <span style="font-size: 10pt; line-height: 1.3;">{{ $allFollowUps ?: '' }}</span>
    </div>

    <table class="signature-table">
        <tr>
            <td>
                <div style="height: 14pt;"></div>
                <strong>Pihak Pertama,</strong>
                <div class="signature-space">
                    @if(!empty($officerSignatureBase64))
                        <img src="{{ $officerSignatureBase64 }}" alt="TTD Pengelola">
                    @endif
                </div>
                <strong><u>{{ $signatoryPengelola->employee->full_name ?? 'Muhammad Agung, S.Ds.' }}</u></strong><br>
                NIP. {{ \App\Helpers\FormatHelper::formatNip($signatoryPengelola->employee->nip ?? '199803112025041003') }}
            </td>
            <td>
                Makassar, {{ \App\Helpers\FormatHelper::formatDateIndo($document->doc_date ?? now()) }}<br>
                <strong>Pihak Kedua,</strong>
                <div class="signature-space">
                    @if(!empty($borrowerSignatureBase64))
                        <img src="{{ $borrowerSignatureBase64 }}" alt="TTD Peminjam">
                    @endif
                </div>
                <strong><u>{{ $loan->snap_full_name }}</u></strong><br>
                NIP. {{ \App\Helpers\FormatHelper::formatNip($loan->snap_nip) }}
            </td>
        </tr>
    </table>

    @if(!empty($photos))
        <div style="page-break-before: always; padding-top: 10pt;">
            <div class="judul-wrapper" style="margin-bottom: 12pt;">
                <div class="doc-title">
                    <span class="doc-title-line">LAMPIRAN DOKUMENTASI FISIK BARANG MILIK NEGARA (BMN)</span>
                    <span class="doc-title-line">BERITA ACARA SERAH TERIMA PENGEMBALIAN</span>
                </div>
                <div class="doc-number">
                    Nomor Berita Acara: {{ $document->doc_number ?? '...............................................................' }}
                </div>
            </div>

            <table style="width: 100%; border-collapse: collapse; margin-top: 10pt;">
                @foreach(array_chunk($photos, 2) as $row)
                    <tr>
                        @foreach($row as $p)
                            <td style="width: 50%; padding: 8pt; vertical-align: top; text-align: center; border: 1pt solid #D9D9D9; background: #fafafa;">
                                <div style="background: #ffffff; border: 1pt solid #e2e8f0; padding: 4pt; border-radius: 4pt;">
                                    <img src="{{ $p['src'] }}" style="max-width: 100%; max-height: 190pt; object-fit: contain;">
                                </div>
                                <div style="font-size: 8.5pt; color: #1e293b; margin-top: 6pt; font-weight: bold;">
                                    {{ $p['caption'] }}
                                </div>
                                <div style="font-size: 7.5pt; color: #64748b; margin-top: 2pt;">
                                    Waktu Dokumentasi: {{ $p['date'] }} WITA
                                </div>
                            </td>
                        @endforeach
                        @if(count($row) === 1)
                            <td style="width: 50%; border: none;"></td>
                        @endif
                    </tr>
                @endforeach
            </table>
        </div>
    @endif
@endsection
