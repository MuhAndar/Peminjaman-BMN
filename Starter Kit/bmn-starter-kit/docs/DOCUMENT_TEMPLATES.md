# Template Tiga Dokumen Resmi

Sumber: `docs/reference/template-dokumen-bmn.pdf` (3 halaman; pratinjau PNG: `template-1.png`, `template-2.png`, `template-3.png`). Redaksi di bawah disalin persis dari template. **Jangan diparafrase, dipersingkat, atau dikoreksi tanpa persetujuan** (BR-11). Placeholder ditulis `{{seperti_ini}}`.

## 0. Ketentuan umum layout (berlaku untuk ketiganya)

- Kertas A4 portrait, margin ± 2 cm kiri-kanan, ± 1,5 cm atas. Huruf sans-serif (template memakai Arial/Helvetica), isi 11 pt, tabel 10 pt.
- **Kop surat** (di atas, rata kiri, diakhiri garis ganda tipis):
  - Logo KOMDIGI di kiri (aset logo harus disediakan, lihat OPEN_QUESTIONS Q10).
  - Baris 1 (tebal, besar, abu tua): `KEMENTERIAN KOMUNIKASI DAN DIGITAL RI`
  - Baris 2 (tebal, kecil): `BADAN PENGEMBANGAN SUMBER DAYA MANUSIA KOMUNIKASI DAN DIGITAL`
  - Baris 3–4 (tebal, sedang): `BALAI BESAR PELATIHAN SUMBER DAYA MANUSIA` / `KOMUNIKASI DAN DIGITAL MAKASSAR`
  - Baris 5 (kecil): `Jl. Prof. Abdurrahman Basalamah II No 25 Makassar 90231, www.komdigi.go.id`
  - Seluruh isi kop dibaca dari `app_settings`, bukan di-hardcode.
- Judul: dua baris, tebal, **garis bawah**, rata tengah. Di bawahnya `Nomor : {{doc_number}}` tebal, rata tengah.
- Label data diri (Nama, NIP, dst.) rata kiri dengan titik dua sejajar pada satu kolom.
- Tabel barang: garis tipis di semua sisi, baris header berlatar abu muda (#D9D9D9) dan rata tengah. Header diulang bila tabel berlanjut ke halaman berikutnya.
- Blok tanda tangan **tidak boleh terpotong** antar halaman (`break-inside: avoid`).
- Penulisan NIP di blok tanda tangan: `NIP. 19820608 200901 2 001`.
- Nama penandatangan digarisbawahi, dengan ruang kosong ± 2,5 cm di atasnya untuk tanda tangan basah.

Format nilai: tanggal `{{tanggal_panjang}}` = "11 Januari 2026" (WITA); `{{nilai}}` = "Rp 12.646.700".
Kolom **Merk** pada tabel = `brand + " " + model` (asumsi, karena template hanya punya satu kolom Merk sedangkan Excel punya Merk dan Type).

---

## 1. Surat Peminjaman

**Judul**
```
SURAT PEMINJAMAN
BARANG INVENTARIS BBPSDMP-MAKASSAR
Nomor : {{doc_number}}
```

**Isi**
```
Saya yang bertanda tangan di bawah ini :
N a m a               : {{borrower.full_name}}
N I P                 : {{borrower.nip_formatted}}
Pangkat / Golongan    : {{borrower.rank_grade}}
J a b a t a n         : {{borrower.position}}
Alamat Rumah          : {{borrower.home_address}}

Menyatakan bahwa benar telah MEMINJAM BARANG INVENTARIS KANTOR berupa :
```
(`MEMINJAM BARANG INVENTARIS KANTOR` dicetak tebal.)

**Tabel**: `NO | Nama Barang | Merk | NUP | Tahun | Nilai | Keperluan`

**Lanjutan**
```
Dengan waktu peminjaman
- Tanggal Peminjaman   : {{start_date_long}}
- Tanggal Pengembalian : {{end_date_long}}

Atas peminjaman barang inventaris tersebut saya menyatakan akan menggunakan barang sesuai peruntukan, menjaga dengan baik, dan mengembalikan sesuai waktu peminjaman atau barang diminta kembali oleh Satker.

Apabila terjadi kehilangan atau kerusakan atas barang inventaris tersebut yang menimbulkan kerugian negara, saya bertanggung jawab mutlak dan tunduk pada peraturan yang berlaku, seperti yang telah diatur dalam Undang-Undang RI No. 1 Tahun 2004 tanggal 14 Januari 2004.
```
(Dua paragraf rata kiri-kanan/justify dengan indentasi baris pertama.)

**Tanda tangan** (dua kolom; tanggal di atas kolom kanan)
```
                                           Makassar, {{doc_date_long}}
Ketua Tim Keuangan dan Aset                Peminjam,
BBPSDMP Makassar,

{{signatory_ketua_tim.full_name}}          {{borrower.full_name}}
NIP. {{signatory_ketua_tim.nip_formatted}} NIP. {{borrower.nip_formatted}}
```

---

## 2. Berita Acara Serah Terima (BAST) Peminjaman

**Judul**
```
BERITA ACARA SERAH TERIMA PEMINJAMAN
BARANG INVENTARIS BBPSDMP-MAKASSAR
Nomor : {{doc_number}}
```

**Isi**
```
Yang bertanda tangan di bawah ini:
N a m a               : {{pihak_pertama.full_name}}
N I P                 : {{pihak_pertama.nip_formatted}}
Pangkat / Golongan    : {{pihak_pertama.rank_grade}}
sebagai Pihak Pertama (Pengelola Aset), bersama

N a m a               : {{borrower.full_name}}
N I P                 : {{borrower.nip_formatted}}
Pangkat / Golongan    : {{borrower.rank_grade}}
J a b a t a n         : {{borrower.position}}
Alamat Rumah          : {{borrower.home_address}}
sebagai Pihak Kedua (Peminjam).

Berdasarkan surat peminjaman No: {{surat_peminjaman.doc_number}} maka dengan ini Pihak Pertama menyerahkan barang inventaris kantor kepada Pihak Kedua berupa:
```
(`Pihak Pertama`, `Pihak Kedua`, kata "Pihak Pertama" pada paragraf pembuka, dan label di bagian "sebagai ..." dicetak tebal sesuai template.)

**Tabel**: `NO | Nama Barang | Merk | NUP | Tahun | Nilai | Kondisi` (kolom Kondisi = `loan_items.condition_out`, default "Baik")

**Lanjutan**
```
dengan ketentuan:
1. Barang digunakan sesuai peruntukan.
2. Barang dikembalikan dengan tepat waktu.
3. Jika rusak/hilang, peminjam bertanggung jawab mutlak dan tunduk pada peraturan yang berlaku.
```

**Tanda tangan**
```
                                           Makassar, {{doc_date_long}}
Pihak Pertama,                             Pihak Kedua,

{{signatory_pengelola.full_name}}          {{borrower.full_name}}
NIP. {{signatory_pengelola.nip_formatted}} NIP. {{borrower.nip_formatted}}
```

---

## 3. BAST Pengembalian

**Judul**
```
BERITA ACARA SERAH TERIMA PENGEMBALIAN
BARANG INVENTARIS BBPSDMP-MAKASSAR
Nomor : {{doc_number}}
```

**Isi**: identik dengan BAST Peminjaman sampai "sebagai Pihak Kedua (Peminjam)." lalu:
```
Berdasarkan surat peminjaman No: {{surat_peminjaman.doc_number}} maka dengan ini Pihak Kedua menyerahkan kembali barang inventaris kantor kepada Pihak Pertama berupa:
```

**Tabel**: `NO | Nama Barang | Merk | NUP | Tahun | Nilai | Kondisi Saat Dikembalikan` (header kolom terakhir dua baris)

**Kondisi akhir** (satu baris, `Pihak Pertama` tebal)
```
Barang diterima kembali oleh Pihak Pertama dalam kondisi:
{{cek_baik}} Baik     {{cek_rusak}} Rusak     {{cek_hilang}} Tidak Lengkap/Hilang
```
`{{cek_*}}` = `☒` bila terpilih, `☐` bila tidak. Karena template hanya punya satu set kotak, isi dengan kondisi **terburuk** di antara semua barang (Tidak Lengkap/Hilang > Rusak > Baik). Kondisi per barang tetap tampil di tabel.

**Kotak tindak lanjut** (kotak berbingkai, tinggi ± 4,5 cm, judul di dalam kotak)
```
Tindak lanjut yang diperlukan:
{{follow_up_notes}}
```
Jika kosong, biarkan kotak kosong (untuk ditulis tangan).

**Tanda tangan**: sama dengan BAST Peminjaman.

---

## 4. Data penandatangan awal (seed, dari template)

| Peran | Nama | NIP |
|---|---|---|
| Ketua Tim Keuangan dan Aset BBPSDMP Makassar | Olga Olivia Sombolayuk, SE | 19820608 200901 2 001 |
| Pihak Pertama (Pengelola Aset) | Muhammad Agung, S.Ds. | 19980311 202504 1 003 |

Keduanya disimpan di tabel `signatories` dan harus dapat diganti Admin tanpa mengubah kode.

## 5. Dua mode keluaran

1. **PDF siap cetak** (wajib): dibuat di server dari komponen React-PDF, dua salinan identik bila perlu dicetak.
2. **Word (.docx) dapat diedit** (opsional, fase lanjutan): untuk kasus petugas perlu menyesuaikan teks. Tetap berasal dari data yang sama.

## 6. Pengujian dokumen

- Snapshot test visual: bandingkan hasil PDF dengan `template-*.png` untuk kasus 1 barang, 3 barang, dan 15 barang (tabel melebar lintas halaman).
- Uji nama panjang, alamat panjang (3 baris), jabatan sangat panjang (contoh: jabatan ganda dengan garis miring), dan nilai 9 digit.
- Uji bahwa teks yang dicetak identik dengan redaksi di file ini (string constant diekspor dari satu modul `src/lib/document-text.ts`).
