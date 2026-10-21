---
name: bmn-document-generator
description: Membuat dan menguji tiga dokumen resmi BMN (Surat Peminjaman, BAST Peminjaman, BAST Pengembalian) sebagai PDF yang persis mengikuti template BBLSDM Makassar, lengkap dengan penomoran otomatis. Gunakan skill ini setiap kali pengguna menyebut surat peminjaman, BAST, berita acara serah terima, cetak dokumen, PDF, kop surat, nomor surat/dokumen, atau tanda tangan penandatangan, meskipun tidak menyebut kata "skill".
---

# Pembuat Dokumen BMN

Dokumen resmi instansi harus konsisten, bernomor unik, dan tidak berubah redaksi. Skill ini memastikan ketiganya dibuat dari satu sumber redaksi dan satu pipeline.

## Baca dulu
1. `docs/DOCUMENT_TEMPLATES.md` — redaksi persis, layout, field mapping.
2. Lihat gambar `docs/reference/template-1.png`, `template-2.png`, `template-3.png` untuk acuan visual.
3. `docs/DATA_MODEL.md` (tabel `documents`, `document_sequences`, `signatories`) dan `docs/WORKFLOW_SOP.md` bagian 5 (kapan nomor diterbitkan).

## Langkah kerja

### 1. Konstanta redaksi
Buat `src/lib/document-text.ts` berisi seluruh teks baku (judul, kalimat pembuka, ketentuan 1–3, paragraf pernyataan tanggung jawab, label). Salin dari `DOCUMENT_TEMPLATES.md` tanpa mengubah satu kata pun. Semua template mengimpor dari sini; tidak ada string redaksi di JSX.

### 2. Pembentuk data
Buat fungsi murni `buildDocumentData(loan, docType, signatories, settings)` yang menghasilkan objek siap-render: data peminjam dari **snapshot** `loan_requests.snap_*`, baris barang dari `loan_items.snap_*` (Merk = `brand + " " + model`), penandatangan aktif pada `doc_date`, tanggal dan nilai terformat lewat `lib/format.ts`. Fungsi ini diuji unit tanpa PDF.

### 3. Komponen PDF (`src/pdf/`)
Pakai `@react-pdf/renderer`. Bangun komponen bersama: `Kop`, `JudulDokumen`, `BlokIdentitas` (label rata dengan titik dua sejajar), `TabelBarang` (header diulang antar halaman, header abu #D9D9D9), `BlokTandaTangan` (`wrap={false}` agar tidak terpotong), lalu tiga dokumen: `SuratPeminjaman`, `BastPeminjaman`, `BastPengembalian`.
- A4, huruf Helvetica bawaan PDF (padanan Arial template), isi 11 pt, tabel 10 pt.
- Kop dibaca dari `app_settings`; logo dari aset yang diberikan pengguna (jangan membuat logo sendiri).
- `BastPengembalian`: kotak ☒/☐ untuk kondisi terburuk dan kotak "Tindak lanjut yang diperlukan" berbingkai.

### 4. Penomoran
Di dalam **satu transaksi**: naikkan `document_sequences` (per `doc_type` + `year`) dengan `UPDATE … RETURNING`, format nomor dari `app_settings`, insert ke `documents` (`UNIQUE (doc_type, doc_number)`). Jika PDF gagal dibuat setelah nomor terbit, tandai dokumen `VOID`/ulang, jangan gunakan nomor yang sama untuk dokumen berbeda. Format nomor default ada di `OPEN_QUESTIONS.md` (asumsi, bisa diubah Admin).

### 5. Penyajian
Route `GET /dokumen/[id]/pdf`: periksa izin (pemilik pengajuan, Pengelola, Ketua Tim, Admin), kirim `Content-Disposition` sesuai pilihan lihat/unduh. Nama berkas: `Surat-Peminjaman_{nomor}_{nama}.pdf` dengan karakter aman.

### 6. Pengujian
- Unit: `buildDocumentData` (1, 3, dan 15 barang; nilai besar; alamat panjang; NIP null untuk PPNPN menampilkan "-").
- Teks: ekstrak teks PDF hasil (mis. `pdf-parse`) dan pastikan setiap konstanta redaksi ada persis.
- Visual: render halaman ke PNG dan bandingkan dengan `template-*.png`; periksa tabel 15 barang berlanjut ke halaman 2 dengan header terulang dan blok tanda tangan tidak terpotong.
- Konkurensi: 20 permintaan nomor paralel menghasilkan 20 nomor unik berurutan tanpa celah.

## Hal yang sering salah
- Memparafrase redaksi "agar lebih baik". Jangan; BR-11.
- Mengambil data diri dari profil terkini, bukan snapshot. Itu mengubah dokumen lama.
- Menghitung nomor di aplikasi lalu insert terpisah (rawan nomor ganda).
- Blok tanda tangan terpotong di halaman berbeda dari isi surat.
- Format tanggal bukan WITA atau bukan Bahasa Indonesia.
