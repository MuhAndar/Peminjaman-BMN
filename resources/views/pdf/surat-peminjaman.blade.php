@extends('pdf.layout')

@section('title', 'Surat Peminjaman Barang Inventaris')

@section('content')
    <div class="judul-wrapper">
        <div class="doc-title">
            <span class="doc-title-line">SURAT PEMINJAMAN</span>
            <span class="doc-title-line">BARANG INVENTARIS BBLSDM KOMDIGI MAKASSAR</span>
        </div>
        <div class="doc-number">
            Nomor : {{ $document->doc_number ?? '...............................................................' }}
        </div>
    </div>

    <div style="margin-bottom: 6pt;">Saya yang bertanda tangan di bawah ini :</div>
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

    <div style="margin-bottom: 6pt;">
        Menyatakan bahwa benar telah <strong>MEMINJAM BARANG INVENTARIS KANTOR</strong> berupa :
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">NO</th>
                <th style="width: 23%;">Nama Barang</th>
                <th style="width: 19%;">Merk</th>
                <th style="width: 7%;">NUP</th>
                <th style="width: 8%;">Tahun</th>
                <th style="width: 15%;">Nilai</th>
                <th style="width: 23%;">Keperluan</th>
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

    <div style="margin: 6pt 0;">
        Dengan waktu peminjaman
        <table class="info-table" style="margin-top: 2pt; margin-bottom: 0;">
            <tr>
                <td style="width: 170px;">Tanggal Peminjaman</td>
                <td class="colon">:</td>
                <td>{{ \App\Helpers\FormatHelper::formatDateIndo($loan->start_date) }}</td>
            </tr>
            <tr>
                <td style="width: 170px;">Tanggal Pengembalian</td>
                <td class="colon">:</td>
                <td>{{ \App\Helpers\FormatHelper::formatDateIndo($loan->end_date) }}</td>
            </tr>
        </table>
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
                <div style="height: 14pt;"></div>
                Ketua Tim Keuangan dan Aset<br>
                BBLSDM Komdigi Makassar,
                <div class="signature-space">
                    @if(!empty($ketuaSignatureBase64))
                        <img src="{{ $ketuaSignatureBase64 }}" alt="TTD Ketua">
                    @endif
                </div>
                <strong><u>{{ $signatoryKetua->employee->full_name ?? 'Olga Olivia Sombolayuk, SE' }}</u></strong><br>
                NIP. {{ \App\Helpers\FormatHelper::formatNip($signatoryKetua->employee->nip ?? '198206082009012001') }}
            </td>
            <td>
                Makassar, {{ \App\Helpers\FormatHelper::formatDateIndo($document->doc_date ?? now()) }}<br>
                Peminjam,<br>
                <div style="height: 14pt;"></div>
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
@endsection
