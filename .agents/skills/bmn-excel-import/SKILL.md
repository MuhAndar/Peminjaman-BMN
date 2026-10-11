---
name: bmn-excel-import
description: Mengimpor dan membersihkan data awal dari Excel "Entry Form Peminjaman" (log peminjaman + data pegawai) ke basis data BMN, dengan mode dry-run, laporan anomali, dan perlindungan data pribadi. Gunakan skill ini setiap kali pengguna menyebut impor Excel, migrasi data lama, seed data, Entry Form, Sheet1/Sheet2, data pegawai, data barang, atau membersihkan data peminjaman, meskipun tidak menyebut kata "skill".
---

# Impor Excel BMN

Excel lama adalah sumber data awal tetapi kotor dan memuat data pribadi. Skill ini membuat impor yang aman, bisa diulang, dan transparan tentang apa yang diubah.

## Larangan utama
Berkas Excel asli **tidak boleh** berada di workspace atau di-commit, dan isinya tidak boleh dicetak ke konsol/log/chat. Impor data asli dijalankan pengguna di server/komputer aman, memakai berkas yang ditunjuk lewat argumen path di luar repo. Untuk pengembangan, buat berkas fixture **fiktif** (`tests/fixtures/entry-form.sample.xlsx`) dengan struktur kolom yang sama.

## Baca dulu
`docs/DATA_MODEL.md`: bagian temuan profil, mapping kolom, tabel kategori dari kode barang, dan **aturan pembersihan 1–12**. Itu spesifikasinya; skill ini menjelaskan cara mengerjakannya.

## Langkah kerja

1. **Skrip** `scripts/import-excel.ts` (TypeScript + `exceljs`). CLI:
   ```
   pnpm import:excel --file /path/di/luar/repo/Entry_Form.xlsx            # dry-run (default)
   pnpm import:excel --file /path/... --apply                             # simpan ke DB
   pnpm import:excel --file /path/... --report ./tmp/anomali.json
   ```
2. **Pembaca**: baca `Sheet1` dan `Sheet2` berdasarkan **nama kolom di baris header** (bukan posisi kolom), toleran terhadap spasi dan huruf besar/kecil. Sel tanggal dibaca sebagai `Date`; jika berupa angka serial Excel, konversi dari epoch 1899-12-30. Lewati baris kosong.
3. **Tahap parse → normalisasi → validasi → rencana**: setiap tahap berupa fungsi murni dengan unit test. Hasil akhir adalah *rencana* (daftar `create/update/skip` + anomali), belum menyentuh DB.
4. **Anomali** (kumpulkan, jangan hentikan impor): merk/type tertukar, nomor polisi diekstrak, kode/NUP = 0, NIP bukan 18 digit, pegawai di Sheet1 tidak ditemukan di Sheet2, alamat kosong, durasi ganjil, duplikat barang. Setiap anomali: `{ row, field, code, message }` **tanpa** nilai mentah yang berisi data pribadi.
5. **Terapkan** (`--apply`): satu transaksi; upsert berdasarkan kunci alami (`employees.nip`, `assets.bmn_code+nup`, `assets.plate_number`); catat `import_batches` (ringkasan, bukan data baris); idempoten.
6. **Keluaran konsol**: hanya hitungan (dibuat/diperbarui/dilewati) dan jumlah anomali per kode. Tidak ada nama, NIP, alamat.
7. **Antarmuka admin** (`/admin/impor`): unggah → pratinjau rencana + anomali → konfirmasi → terapkan. Berkas diproses di memori/penyimpanan sementara dan dihapus setelah selesai.

## Pengujian
- Fixture fiktif yang sengaja memuat semua anomali: merk huruf besar/kecil campur, Type kosong, nomor polisi di `Nama Barang` dan di `Type`, kode 0/NUP 0, NIP 16 digit, pegawai tanpa alamat, dua baris pegawai sama dengan tanggal sama (digabung jadi satu pengajuan).
- Pastikan: impor kedua kali tidak menambah baris; `--apply` tanpa dry-run sebelumnya tetap menampilkan ringkasan; tidak ada nilai pribadi pada keluaran konsol (uji dengan menangkap stdout).

## Tanyakan ke pengguna bila
- Ada kolom baru atau nama kolom berubah di berkas.
- Aturan status riwayat (aturan 9) perlu diubah, mis. mereka punya data pengembalian di tempat lain.
- Sebuah NIP tidak cocok antara sheet; jangan menebak, laporkan sebagai anomali.
