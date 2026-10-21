@extends('pdf.layout')

@section('title', 'Berita Acara Serah Terima (BAST) Peminjaman BMN')

@section('content')
    <div class="doc-title">
        BERITA ACARA SERAH TERIMA PEMINJAMAN<br>
        BARANG INVENTARIS BBLSDM-MAKASSAR
    </div>
    <div class="doc-number">
        Nomor : {{ $document->doc_number ?? 'DRAF' }}
    </div>

    <div style="margin-bottom: 6px;">Yang bertanda tangan di bawah ini :</div>
    <table class="info-table" style="margin-bottom: 4px;">
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
    <div style="margin-bottom: 8px;">sebagai <strong>Pihak Pertama</strong> (Pengelola Aset), bersama</div>

    <table class="info-table" style="margin-bottom: 4px;">
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
    <div style="margin-bottom: 10px;">sebagai <strong>Pihak Kedua</strong> (Peminjam).</div>

    <div class="paragraph no-indent" style="margin-bottom: 6px;">
        Berdasarkan surat peminjaman No: <strong>{{ $suratPeminjamanDoc->doc_number ?? '-' }}</strong> maka dengan ini <strong>Pihak Pertama</strong> menyerahkan barang inventaris kantor kepada <strong>Pihak Kedua</strong> berupa :
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 28px;">NO</th>
                <th>Nama Barang</th>
                <th>Merk</th>
                <th style="width: 45px;">NUP</th>
                <th style="width: 45px;">Tahun</th>
                <th>Nilai</th>
                <th style="width: 75px;">Kondisi</th>
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
                <td class="center">{{ $item->condition_out ?? 'Baik' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-bottom: 10px;">
        dengan ketentuan :
        <ol style="margin-top: 3px; padding-left: 20px;">
            <li>Barang digunakan sesuai peruntukan.</li>
            <li>Barang dikembalikan dengan tepat waktu.</li>
            <li>Jika rusak/hilang, peminjam bertanggung jawab mutlak dan tunduk pada peraturan yang berlaku.</li>
        </ol>
    </div>

    <table class="signature-table">
        <tr>
            <td>
                <br>
                <strong>Pihak Pertama,</strong><br>
                <div class="signature-space">
                    @if(!empty($officerSignatureBase64))
                        <img src="{{ $officerSignatureBase64 }}" style="max-height: 65px; max-width: 150px; display: block;">
                    @endif
                </div>
                <strong><u>{{ $signatoryPengelola->employee->full_name ?? 'Muhammad Agung, S.Ds.' }}</u></strong><br>
                NIP. {{ \App\Helpers\FormatHelper::formatNip($signatoryPengelola->employee->nip ?? '199803112025041003') }}
            </td>
            <td>
                Makassar, {{ \App\Helpers\FormatHelper::formatDateIndo($document->doc_date ?? now()) }}<br>
                <strong>Pihak Kedua,</strong><br>
                <div class="signature-space">
                    @if(!empty($borrowerSignatureBase64))
                        <img src="{{ $borrowerSignatureBase64 }}" style="max-height: 65px; max-width: 150px; display: block;">
                    @endif
                </div>
                <strong><u>{{ $loan->snap_full_name }}</u></strong><br>
                NIP. {{ \App\Helpers\FormatHelper::formatNip($loan->snap_nip) }}
            </td>
        </tr>
    </table>
@endsection
