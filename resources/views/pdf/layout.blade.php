<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>@yield('title')</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 1.0cm 2.0cm 1.5cm 2.0cm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
            line-height: 1.25;
            color: #000;
            margin: 0;
            padding: 0;
        }

        /* ===================== KOP SURAT ===================== */
        .kop-wrapper {
            width: 100%;
            margin-bottom: 12pt;
            text-align: center;
        }
        .kop-wrapper img {
            width: 100%;
            height: auto;
            display: block;
        }

        /* Fallback text header jika gambar kop tidak ditemukan */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }
        .header-logo {
            width: 80px;
            text-align: center;
            vertical-align: middle;
        }
        .header-text {
            text-align: center;
            vertical-align: middle;
        }
        .header-text .line1 {
            font-size: 11pt;
            font-weight: bold;
            color: #222;
        }
        .header-text .line2 {
            font-size: 8.5pt;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .header-text .line3 {
            font-size: 11pt;
            font-weight: bold;
            margin-top: 2px;
        }
        .header-text .line4 {
            font-size: 7.5pt;
            margin-top: 3px;
            color: #444;
        }
        .header-divider {
            border-top: 2.5px solid #000;
            border-bottom: 0.8px solid #000;
            height: 2px;
            margin-bottom: 14pt;
        }

        /* ===================== JUDUL DOKUMEN ===================== */
        .judul-wrapper {
            text-align: center;
            margin-bottom: 14pt;
        }
        .doc-title {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            line-height: 1.25;
            margin-bottom: 2pt;
            text-transform: uppercase;
        }
        .doc-title-line {
            text-decoration: underline;
            display: block;
        }
        .doc-number {
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
            margin-top: 2pt;
        }

        /* ===================== TABEL IDENTITAS / INFO ===================== */
        .info-table {
            width: 100%;
            margin-bottom: 8pt;
            border-collapse: collapse;
        }
        .info-table td {
            vertical-align: top;
            padding: 1.5pt 0;
            font-size: 11pt;
            line-height: 1.25;
        }
        .info-table td.label {
            width: 155px;
        }
        .info-table td.colon {
            width: 15px;
            text-align: center;
        }

        /* ===================== TABEL DATA BARANG ===================== */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 8pt 0 10pt 0;
            font-size: 10pt;
            line-height: 1.2;
        }
        .data-table th, .data-table td {
            border: 1px solid #000;
            padding: 4.5pt 5pt;
        }
        .data-table th {
            background-color: #D9D9D9;
            text-align: center;
            font-weight: bold;
        }
        .data-table td.center {
            text-align: center;
        }
        .data-table td.right {
            text-align: right;
        }

        /* ===================== PARAGRAF ===================== */
        .paragraph {
            text-align: justify;
            margin-bottom: 7pt;
            text-indent: 28pt;
            line-height: 1.25;
        }
        .paragraph.no-indent {
            text-indent: 0;
        }

        /* ===================== TANDA TANGAN ===================== */
        .signature-date-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14pt;
            margin-bottom: 2pt;
            page-break-inside: avoid;
        }
        .signature-date-table td {
            font-size: 11pt;
            line-height: 1.25;
            vertical-align: bottom;
        }
        .signature-table {
            width: 100%;
            margin-top: 0;
            border-collapse: collapse;
            page-break-inside: avoid;
        }
        .signature-table td {
            vertical-align: top;
            width: 50%;
            font-size: 11pt;
            line-height: 1.25;
            text-align: center;
        }
        .signature-space {
            height: 70px; /* Ruang kosong standar ± 2,5 cm di atas nama penandatangan */
            min-height: 70px;
            max-height: 70px;
            line-height: 70px;
            margin: 2px auto 4px auto;
            text-align: center;
            overflow: hidden;
        }
        .signature-space img {
            max-height: 66px;
            max-width: 165px;
            height: auto;
            width: auto;
            display: inline-block;
            vertical-align: middle;
            margin: 0 auto;
        }

        /* ===================== CHECKBOX & TINDAK LANJUT ===================== */
        .checkbox-container {
            margin: 8pt 0 10pt 0;
            font-size: 11pt;
            line-height: 1.3;
        }
        .cb-box {
            display: inline-block;
            width: 11pt;
            height: 11pt;
            border: 1pt solid #000;
            text-align: center;
            line-height: 10pt;
            font-size: 9pt;
            font-weight: bold;
            vertical-align: middle;
            margin-right: 4pt;
        }
        .box-follow-up {
            border: 1px solid #000;
            padding: 6pt 8pt;
            min-height: 55pt;
            margin: 8pt 0 12pt 0;
            font-size: 10.5pt;
            line-height: 1.25;
        }
    </style>
</head>
<body>
    @php
        $logoSrc = $kopSrc ?? null;
        if (!$logoSrc) {
            $kopPath = public_path('images/kop_surat.png');
            if (!file_exists($kopPath)) {
                $kopPath = public_path('images/kop_surat_full.png');
            }
            if (!file_exists($kopPath)) {
                $kopPath = base_path('Starter Kit/Referensi Dokumen dan SOP BMN/kop surat.PNG');
            }
            $logoSrc = file_exists($kopPath)
                ? 'data:image/png;base64,' . base64_encode(file_get_contents($kopPath))
                : '';
        }
    @endphp

    @if($logoSrc)
        <div class="kop-wrapper">
            <img src="{{ $logoSrc }}" alt="Kop Surat Resmi">
        </div>
    @else
        <!-- Fallback text header jika gambar kop tidak tersedia -->
        <table class="header-table">
            <tr>
                <td class="header-logo">
                    <div style="font-weight:bold; font-size:24pt; color:#0B63B5;">KOMDIGI</div>
                </td>
                <td class="header-text">
                    <div class="line1">KEMENTERIAN KOMUNIKASI DAN DIGITAL RI</div>
                    <div class="line2">BADAN PENGEMBANGAN SUMBER DAYA MANUSIA KOMUNIKASI DAN DIGITAL</div>
                    <div class="line3">BALAI BESAR PELATIHAN SUMBER DAYA MANUSIA<br>KOMUNIKASI DAN DIGITAL MAKASSAR</div>
                    <div class="line4">Jl. Prof. Abdurrahman Basalamah II No 25 Makassar 90231, www.komdigi.go.id</div>
                </td>
            </tr>
        </table>
        <div class="header-divider"></div>
    @endif

    @yield('content')
</body>
</html>
