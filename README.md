# 🏛️ Sistem Layanan Peminjaman & Pengembalian BMN
### Balai Besar Pelatihan Sumber Daya Manusia Komunikasi dan Digital (BBLSDM Komdigi) Makassar

[![Laravel 11](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![Tailwind CSS v4](https://img.shields.io/badge/Tailwind_CSS-v4.0-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![Alpine.js](https://img.shields.io/badge/Alpine.js-3.x-8BC0D0?style=for-the-badge&logo=alpine.js&logoColor=white)](https://alpinejs.dev)
[![DomPDF](https://img.shields.io/badge/DomPDF-Official_Templates-0B63B5?style=for-the-badge)](https://github.com/barryvdh/laravel-dompdf)
[![License MIT](https://img.shields.io/badge/License-MIT-blue?style=for-the-badge)](LICENSE)

Aplikasi web terpadu untuk digitalisasi tata kelola, peminjaman, pemantauan, dan pengembalian aset **Barang Milik Negara (BMN)** di lingkungan **BBLSDM Komdigi Makassar**. Sistem ini dirancang secara ketat mengikuti **Standar Operasional Prosedur (SOP)** resmi instansi dengan penomoran dokumen otomatis terpadu, pencetakan berkas hukum (PDF), pencegahan bentrok jadwal peminjaman, serta *audit trail* forensik.

---

## 📌 Daftar Isi
- [Alur Kerja SOP Resmi](#-alur-kerja-sop-resmi)
  - [SOP Peminjaman BMN (7 Langkah)](#1-sop-peminjaman-bmn-7-langkah)
  - [SOP Pengembalian BMN (6 Langkah)](#2-sop-pengembalian-bmn-6-langkah)
- [Peran Pengguna & Hak Akses (RBAC)](#-peran-pengguna--hak-akses-rbac)
- [Fitur Unggulan Sistem](#-fitur-unggulan-sistem)
- [Teknologi yang Digunakan](#-teknologi-yang-digunakan)
- [Struktur Proyek](#-struktur-proyek)
- [Panduan Instalasi & Pengoperasian](#-panduan-instalasi--pengoperasian)
- [Kredensial Akun Demo Pengujian](#-kredensial-akun-demo-pengujian)
- [Pengujian Otomatis (Testing)](#-pengujian-otomatis-testing)

---

## 📋 Alur Kerja SOP Resmi

### 1. SOP Peminjaman BMN (7 Langkah)
```
[1. Peminjam: Input Pengajuan] 
       │
       ▼
[2. Pengelola BMN: Telaah Berkas Permohonan]
       │
       ▼
[3. Pengelola BMN: Verifikasi Fisik & Ketersediaan Aset]
       │
       ▼
[4. Ketua Tim Keuangan & Aset: Otorisasi & Persetujuan]
       │
       ▼
[5. Pengelola BMN: Penerbitan Surat Peminjaman & BAST Peminjaman]
       │
       ▼
[6. Penandatanganan Dokumen Resmi oleh Para Pihak]
       │
       ▼
[7. Penyerahan Fisik BMN ke Peminjam (Status: AKTIF DIPINJAM)]
```

### 2. SOP Pengembalian BMN (6 Langkah)
```
[1. Peminjam: Permohonan Pengembalian Barang via Sistem]
       │
       ▼
[2. Pengelola BMN: Penerimaan Fisik Barang]
       │
       ▼
[3. Pengelola BMN: Pemeriksaan Fisik & Kelayakan (Baik / Rusak / Hilang)]
       │
       ▼
[4. Pengelola BMN: Penerbitan BAST Pengembalian]
       │
       ▼
[5. Penandatanganan BAST Pengembalian oleh Para Pihak]
       │
       ▼
[6. Finalisasi: Barang Masuk Kembali ke Inventaris (Status: SELESAI)]
```

---

## 👥 Peran Pengguna & Hak Akses (RBAC)

1. **Peminjam (`PEMINJAM`)**
   - Menelusuri katalog barang BMN yang tersedia.
   - Mengajukan permohonan peminjaman melalui *wizard form* 4 tahap.
   - Memantau linimasa progres pengajuan secara real-time.
   - Mengunduh dokumen resmi PDF (*Surat Peminjaman*, *BAST Peminjaman*, *BAST Pengembalian*).
   - Mengajukan permohonan pengembalian aset saat masa pakai berakhir.
2. **Pengelola BMN (`PENGELOLA_BMN`)**
   - Mengelola antrean telaah permohonan masuk.
   - Memeriksa ketersediaan fisik barang dan jadwal pemakaian.
   - Meneruskan berkas yang memenuhi syarat ke Ketua Tim.
   - Menerbitkan nomor surat atomik dan mencetak BAST Peminjaman.
   - Mengonfirmasi penyerahan barang dan memproses pengembalian fisik.
3. **Ketua Tim Keuangan & Tata Usaha (`KETUA_TIM_ASET`)**
   - Menelaah pengajuan peminjaman dari perspektif manajerial.
   - Memberikan keputusan resmi: **Setujui (*Approve*)**, **Minta Perbaikan (*Revision*)**, atau **Tolak (*Reject*)**.
4. **Administrator Sistem (`ADMIN`)**
   - Akses penuh ke seluruh modul sistem.
   - Memantau rekam jejak **Log Aktivitas & Audit Trail** forensik.
   - Manajemen akun pegawai dan hak akses (RBAC).
   - Pengelolaan data pejabat penandatangan resmi.
   - Konfigurasi parameter satker, kop dinas, dan SLA waktu layanan.

---

## ⚡ Fitur Unggulan Sistem

- **Single Source of Truth State Machine (`LoanStateMachine`)**:
  Seluruh aturan bisnis, validasi guard, penulisan riwayat status, dan log audit dieksekusi secara ketat dalam satu transaksi basis data atomik.
- **Tanda Tangan Digital & Penghalusan Kurva Otomatis (*Chaikin Smoothing Algorithm*)**:
  Pengguna dapat membubuhkan tanda tangan langsung melalui papan kanvas interaktif dengan algoritme *Chaikin curve smoothing* yang otomatis menghaluskan goresan saat sentuhan dilepas, atau mengunggah spesimen tanda tangan transparan di profil untuk digunakan berulang kali pada Surat Peminjaman dan Berita Acara (BAST).
- **Upload & Dokumentasi Foto Fisik BAST (Kamera & Galeri)**:
  Petugas dan peminjam dapat melampirkan foto serah terima dan kondisi fisik aset saat peminjaman maupun saat pengembalian (dukungan jepret kamera langsung ponsel `capture="environment"` dan galeri banyak foto) lengkap dengan *lightbox viewer* resolusi penuh.
- **Pemisahan Audit Log Forensik Sistem & Audit Aktivitas Peminjaman BMN**:
  - **Audit Peminjaman BMN (`/audit/peminjaman`)**: Khusus transaksi operasional peminjaman barang, tanpa memuat IP address, hash, atau data teknis sistem (aman untuk audit satker/pimpinan).
  - **Log Aktivitas Sistem Forensik IT (`/admin/log-aktivitas`)**: Jejak teknis lengkap untuk audit TI internal (perubahan profil, unggah file, alamat IP, mutasi data *before/after*).
  - Keduanya dilengkapi filter fleksibel (per bulan, tahun anggaran, atau rentang tanggal) serta fasilitas ekspor instan ke **Excel (`.xlsx`)** dan **CSV UTF-8**.
- **Pembaruan Data Diri Peminjam & Live Preview**:
  Menu pengelolaan data diri pegawai (NIP 18-digit, pangkat/golongan, jabatan, unit kerja, alamat rumah) di profil dengan *live preview* dokumen BMN interaktif.
- **Modul Staging Impor Data Excel (`/kelola/import-data`)**:
  Fasilitas unggah dan pembacaan berkas spreadsheet inventaris Microsoft Excel (`.xlsx`/`.xls`) berbasis **PhpSpreadsheet** lengkap dengan pratinjau tabel 10 baris pertama dan tombol unduh format acuan BMN resmi.
- **Pencegahan Bentrok Jadwal Aset (*Anti Double-Booking Guard*)**:
  Sistem secara otomatis mencegah peminjaman barang yang sama pada rentang tanggal yang saling tumpang tindih (`BR-02`).
- **Penomoran Dokumen Atomik Terpadu (`DocumentNumberService`)**:
  Format penomoran surat resmi berurutan per tahun anggaran yang aman dari *race-condition* konkurensi (`BR-06`).
- **Snapshot Integritas Dokumen Hukum (`BR-07`)**:
  Menyimpan snapshot data peminjam (NIP, nama, jabatan, alamat) dan data aset saat pengajuan diproses agar dokumen hukum tetap valid meskipun master data berubah di masa depan.
- **Pencegahan Konflik Kepentingan (`BR-08`)**:
  Sistem melarang pemohon menelaah atau menyetujui pengajuannya sendiri.
- **Penerbitan Dokumen PDF Baku (DomPDF)**:
  Template cetak 100% presisi mengikuti format resmi BBLSDM Komdigi Makassar:
  1. *Surat Permohonan Peminjaman BMN*
  2. *Berita Acara Serah Terima (BAST) Peminjaman BMN*
  3. *Berita Acara Serah Terima (BAST) Pengembalian BMN*
- **Desain Antarmuka Modern & Ergonomis**:
  - Sidebar kiri yang dapat diciutkan (*minimized*) atau dilebarkan (*expanded*).
  - Tombol lingkaran panah (*floating arrow toggle*) di antara logo dan menu Beranda.
  - *Floating Tooltip* interaktif dengan latar gelap elegan saat navbar dalam mode *minimized*.
  - Transisi mulus tanpa jeda (*zero layout shift*) berkat sinkronisasi lebar padding kontainer.
  - *1-Click Demo Quick Switcher* untuk kemudahan demonstrasi pengujian alur SOP tanpa perlu logout manual.

---

## 🛠️ Teknologi yang Digunakan

| Komponen | Pustaka / Teknologi | Deskripsi |
|---|---|---|
| **Framework** | [Laravel 11](https://laravel.com) | Arsitektur backend modern, Eloquent ORM, Service Layer |
| **Styling** | [Tailwind CSS v4](https://tailwindcss.com) | Desain utility-first dengan sistem token warna kustom |
| **Reaktivitas UI** | [Alpine.js 3.x](https://alpinejs.dev) | State management reaktif ringan untuk modal, kanvas TTE, drawer, dan toast |
| **Spreadsheet Engine** | [PhpSpreadsheet 5.x](https://github.com/PHPOffice/PhpSpreadsheet) | Parsing berkas Excel .xlsx, pembacaan sheet, dan ekspor rekapan audit berformat resmi |
| **Ikonografi** | [Lucide Icons](https://lucide.dev) | Ikon SVG berstandar visual konsisten |
| **Tipografi** | Plus Jakarta Sans | Font modern Google Fonts |
| **PDF Engine** | [DomPDF](https://github.com/barryvdh/laravel-dompdf) | Rendering dokumen resmi pemerintah ke format PDF vektor |
| **Basis Data** | SQLite / MySQL | Penyimpanan terstruktur dengan integritas foreign key |
| **Automated Testing** | PHPUnit | Rangkaian pengujian unit dan alur fitur |

---

## 📂 Struktur Proyek

```
BMN/
├── app/
│   ├── Helpers/
│   │   └── FormatHelper.php          # Format Rupiah, NIP 18-digit, Tanggal Indonesia (WITA)
│   ├── Http/Controllers/
│   │   ├── AdminController.php       # Log Audit, Pengguna, Pejabat, Pengaturan
│   │   ├── ApprovalController.php    # Alur Persetujuan Ketua Tim
│   │   ├── AssetController.php       # Katalog & Detail Aset BMN
│   │   ├── AuthController.php        # Autentikasi & Quick Demo Switcher
│   │   ├── DashboardController.php   # Dashboard adaptif per peran
│   │   ├── DocumentController.php    # Preview & Unduh PDF Resmi
│   │   └── LoanRequestController.php # Alur Pengajuan, Telaah, BAST & Pengembalian
│   ├── Models/                       # Eloquent Models (Asset, LoanRequest, AuditLog, dll.)
│   └── Services/
│       ├── BmnDocumentService.php    # Pembangkit Dokumen PDF Resmi
│       ├── DocumentNumberService.php # Penomoran Dokumen Atomik
│       └── LoanStateMachine.php      # State Machine SOP 7 & 6 Langkah
├── database/
│   ├── migrations/                   # Skema Tabel Basis Data
│   └── seeders/                      # Data Awal Pegawai, Pejabat & Aset Dummy
├── resources/
│   ├── css/app.css                   # Konfigurasi Tailwind CSS v4
│   ├── js/app.js                     # Inisialisasi Alpine.js & Lucide
│   └── views/
│       ├── admin/                    # Tampilan Administrasi & Audit Log
│       ├── approval/                 # Tampilan Persetujuan Ketua Tim
│       ├── assets/                   # Tampilan Katalog BMN
│       ├── dashboard/                # Dashboard Adaptif per Role
│       ├── kelola/                   # Antrean Telaah Pengelola BMN
│       ├── layouts/app.blade.php     # Layout Induk (Sidebar Kiri & Topbar)
│       ├── loans/                    # Wizard Pengajuan & Detail Timeline SOP
│       └── pdf/                      # Template Blade Dokumen PDF Resmi
├── routes/web.php                    # Definisi Rute Web Berdasarkan Hak Akses
└── tests/Feature/                    # Pengujian Otomatis State Machine & Audit Log
```

---

## 🚀 Panduan Instalasi & Pengoperasian

### Prasyarat Sistem
- PHP >= 8.2 (dengan ekstensi `pdo_sqlite` atau `pdo_mysql`, `mbstring`, `openssl`, `gd`)
- Composer >= 2.x
- Node.js >= 18.x & NPM

### Langkah Instalasi

1. **Clone Repositori**:
   ```bash
   git clone https://github.com/MuhAndar/Peminjaman-BMN.git
   cd Peminjaman-BMN
   ```

2. **Pasang Dependensi PHP & Node.js**:
   ```bash
   composer install
   npm install
   ```

3. **Konfigurasi Environment**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Migrasi Basis Data, Seeding & Storage Link**:
   ```bash
   touch database/database.sqlite
   php artisan migrate --seed
   php artisan storage:link
   ```

5. **Kompilasi Aset Frontend**:
   ```bash
   npm run build
   ```
   *(Gunakan `npm run dev` jika ingin menjalankan file watcher saat pengembangan).*

6. **Jalankan Server Lokal**:
   ```bash
   php artisan serve
   ```
   Buka peramban pada alamat: `http://127.0.0.1:8000`

---

## 🔑 Kredensial Akun Demo Pengujian

Semua akun demo menggunakan kata sandi default: **`password`**

| Peran (Role) | Nama Pegawai | Email Login | Akses Utama |
|---|---|---|---|
| **Peminjam** | Andi Pratama, S.Kom. | `peminjam@komdigi.go.id` | Katalog, Form Pengajuan BMN, Riwayat Pengajuan |
| **Pengelola BMN** | Muhammad Agung, S.Sos. | `pengelola@komdigi.go.id` | Antrean Telaah, Penerbitan BAST, Verifikasi Fisik |
| **Ketua Tim Aset** | Olga Olivia, S.E., M.M. | `ketuatim@komdigi.go.id` | Persetujuan Pengajuan (*Approve/Revise/Reject*) |
| **Administrator** | Administrator Sistem | `admin@komdigi.go.id` | Log Aktivitas & Audit, Manajemen Pengguna, Master Pejabat |

> 💡 **Fitur Cepat:** Anda juga dapat beralih peran secara instan melalui widget **Ganti Akun Demo** di pojok kanan atas tanpa perlu mengetik kata sandi.

---

## 🧪 Pengujian Otomatis (Testing)

Proyek ini dilengkapi rangkaian pengujian otomatis komprehensif mencakup integritas state machine, penomoran dokumen atomik, pembatasan hak akses pengembalian aset hanya pada peminjam pemilik, penanganan tanda tangan digital, unggah foto dokumentasi BAST, ekspor audit Excel/CSV, dan validasi data diri:

```bash
php artisan test
```

Hasil pengujian:
```text
   PASS  Tests\Unit\ExampleTest
  ✓ that true is true

   PASS  Tests\Feature\AdminAuditLogTest
  ✓ peminjam cannot access admin audit logs
  ✓ admin can access audit logs page
  ✓ audit logs can be filtered
  ✓ loan audit accessible by pengelola and admin forbidden for peminjam
  ✓ loan audit only shows loan activities without sensitive data
  ✓ loan audit export works for xlsx and csv

   PASS  Tests\Feature\ExampleTest
  ✓ guest is redirected to login

   PASS  Tests\Feature\LoanWorkflowTest
  ✓ format helpers
  ✓ document numbering is atomic and sequential
  ✓ complete sop workflow transitions
  ✓ br 08 prevents borrower from approving own loan
  ✓ br 02 prevents overlapping asset loans

   PASS  Tests\Feature\NewFeaturesTest
  ✓ only borrower who owns loan can request return
  ✓ bast photo upload and delete
  ✓ signature management in profile and loan signing
  ✓ audit logs export with filters
  ✓ excel upload and template download
  ✓ pdf generation with digital signatures
  ✓ borrower can update personal data for bmn documents

  Tests:    20 passed (113 assertions)
```

---

## 📄 Lisensi
Didistribusikan di bawah Lisensi [MIT](LICENSE). Hak Cipta © 2026 BBLSDM Komunikasi dan Digital Makassar.
