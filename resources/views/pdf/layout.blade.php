<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>@yield('title')</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 1.5cm 2cm 2cm 2cm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10.5pt;
            line-height: 1.45;
            color: #000;
            margin: 0;
            padding: 0;
        }
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
            margin-bottom: 18px;
        }
        .doc-title {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 2px;
            text-transform: uppercase;
        }
        .doc-number {
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 18px;
        }
        .info-table {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
        }
        .info-table td {
            vertical-align: top;
            padding: 2.5px 0;
        }
        .info-table td.label {
            width: 165px;
        }
        .info-table td.colon {
            width: 15px;
            text-align: center;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0 16px 0;
            font-size: 9.5pt;
        }
        .data-table th, .data-table td {
            border: 1px solid #444;
            padding: 5px 6px;
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
        .paragraph {
            text-align: justify;
            margin-bottom: 10px;
            text-indent: 28px;
        }
        .paragraph.no-indent {
            text-indent: 0;
        }
        .signature-table {
            width: 100%;
            margin-top: 25px;
            border-collapse: collapse;
            page-break-inside: avoid;
        }
        .signature-table td {
            vertical-align: top;
            width: 50%;
        }
        .signature-space {
            height: 70px;
        }
        .box-follow-up {
            border: 1px solid #000;
            padding: 8px 10px;
            min-height: 80px;
            margin-top: 10px;
            margin-bottom: 15px;
        }
        .checkbox-container {
            margin: 10px 0;
            font-size: 10pt;
        }
    </style>
</head>
<body>
    <!-- KOP SURAT RESMI BBPSDMP MAKASSAR -->
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

    @yield('content')
</body>
</html>
