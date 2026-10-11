@extends('pdf.layout')

@section('title', 'Surat Peminjaman Barang Inventaris')

@section('content')
    <div class="doc-title">
        SURAT PEMINJAMAN<br>
        BARANG INVENTARIS BBPSDMP-MAKASSAR
    </div>
    <div class="doc-number">
        Nomor : {{ $document->doc_number ?? 'DRAF' }}
    </div>

    <div style="margin-bottom: 8px;">Saya yang bertanda tangan di bawah ini :</div>
    <table class="info-table">
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

    <div style="margin-bottom: 6px;">
        Menyatakan bahwa benar telah <strong>MEMINJAM BARANG INVENTARIS KANTOR</strong> berupa :
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
                <th>Keperluan</th>
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
                <td>{{ $item->purpose }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-bottom: 8px;">
        Dengan waktu peminjaman :
        <div style="padding-left: 15px; margin-top: 3px;">
            - Tanggal Peminjaman &nbsp;&nbsp;: {{ \App\Helpers\FormatHelper::formatDateIndo($loan->start_date) }}<br>
            - Tanggal Pengembalian : {{ \App\Helpers\FormatHelper::formatDateIndo($loan->end_date) }}
        </div>
    </div>

    <div class="paragraph">
        Atas peminjaman barang inventaris tersebut saya menyatakan akan menggunakan barang sesuai peruntukan, menjaga dengan baik, dan mengembalikan sesuai waktu peminjaman atau barang diminta kembali oleh Satker.
    </div>

    <div class="paragraph">
        Apabila terjadi kehilangan atau kerusakan atas barang inventaris tersebut yang menimbulkan kerugian negara, saya bertanggung jawab mutlak dan tunduk pada peraturan yang berlaku, seperti yang telah diatur dalam Undang-Undang RI No. 1 Tahun 2004 tanggal 14 Januari 2004.
    </div>

    <table class="signature-table">
        <tr>
            <td>
                <br>
                Ketua Tim Keuangan dan Aset<br>
                BBPSDMP Makassar,<br>
                <div class="signature-space"></div>
                <strong><u>{{ $signatoryKetua->employee->full_name ?? 'Olga Olivia Sombolayuk, SE' }}</u></strong><br>
                NIP. {{ \App\Helpers\FormatHelper::formatNip($signatoryKetua->employee->nip ?? '198206082009012001') }}
            </td>
            <td>
                Makassar, {{ \App\Helpers\FormatHelper::formatDateIndo($document->doc_date ?? now()) }}<br>
                Peminjam,<br>
                <div class="signature-space"></div>
                <strong><u>{{ $loan->snap_full_name }}</u></strong><br>
                NIP. {{ \App\Helpers\FormatHelper::formatNip($loan->snap_nip) }}
            </td>
        </tr>
    </table>
@endsection
