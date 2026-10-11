# PRD — Sistem Peminjaman & Pengembalian BMN

Versi 0.1 (draft) · Satker: BBPSDMP Makassar · Status: siap dikerjakan dengan asumsi default (lihat `OPEN_QUESTIONS.md`).

## 1. Latar belakang

Saat ini peminjaman BMN dijalankan manual: pegawai mengisi form, petugas mencatat di Excel "Entry Form Peminjaman", lalu tiga dokumen (Surat Peminjaman, BAST Peminjaman, BAST Pengembalian) dibuat lewat mail-merge Word dan ditandatangani basah. Alur resminya sudah ada di SOP (nomor B-489/BBPSDMP.73/OT.02.02/09/2025, versi 1.0, draft).

Masalah yang diselesaikan:
- Pengisian data berulang (nama, NIP, pangkat, jabatan, alamat, data barang) di tiga dokumen.
- Tidak ada pengecekan otomatis apakah barang sedang dipinjam orang lain atau kondisinya layak.
- Nomor surat dan rekap bergantung pada satu file Excel.
- Pemantauan jatuh tempo dan status barang tidak real-time.
- Pegawai tidak bisa melihat status pengajuannya.

## 2. Tujuan dan metrik

| Tujuan | Metrik keberhasilan |
|---|---|
| Mempercepat proses | Pengajuan sampai dokumen siap tanda tangan ≤ 28 menit kerja sesuai target SOP (lihat catatan di `WORKFLOW_SOP.md`) |
| Menghilangkan input ganda | Peminjam hanya mengisi barang, periode, dan keperluan; data diri terisi otomatis |
| Mencegah bentrok | 0 peminjaman ganda untuk barang dan periode yang sama |
| Akuntabilitas | Semua perubahan status tercatat (siapa, kapan) dan dokumen bernomor unik |
| Dapat dipakai di HP | Seluruh alur peminjam dan persetujuan bisa dilakukan penuh dari ponsel |

## 3. Peran pengguna

| Peran | Siapa (contoh jabatan) | Hak utama |
|---|---|---|
| `PEMINJAM` | Semua pegawai aktif | Membuat pengajuan, melihat status miliknya, mengajukan pengembalian, mengunggah dokumen bertanda tangan |
| `PENGELOLA_BMN` | Pengelola/Petugas BMN (Pihak Pertama di BAST) | Menelaah pengajuan, cek ketersediaan dan kondisi, membuat BAST, memproses pengembalian, kelola data barang |
| `KETUA_TIM_ASET` | Ketua Tim Keuangan dan Aset | Menyetujui atau menolak pengajuan, tanda tangan Surat Peminjaman |
| `ADMIN` | Admin sistem | Kelola akun/peran, penandatangan, pengaturan satker, format nomor, master data |
| `PIMPINAN` (opsional) | Kepala BBPSDMP | Baca-saja dashboard dan laporan |

Satu orang boleh memegang beberapa peran. Satu orang tidak boleh menyetujui atau memproses pengajuannya sendiri (lihat BR-08).

## 4. Cakupan

### MVP (wajib)
1. Login dan manajemen peran.
2. Master data: Barang (BMN), Pegawai, Penandatangan, Pengaturan satker.
3. Impor data awal dari Excel (barang, pegawai, riwayat peminjaman) — lihat skill `bmn-excel-import`.
4. Pengajuan peminjaman (satu pengajuan boleh memuat beberapa barang).
5. Telaah pengajuan oleh Pengelola BMN lalu persetujuan Ketua Tim Aset.
6. Pembuatan otomatis tiga dokumen PDF sesuai template, dengan penomoran otomatis.
7. Serah terima: unggah BAST bertanda tangan, status barang menjadi "Dipinjam".
8. Pengembalian: cek kondisi, BAST Pengembalian, status barang kembali "Tersedia".
9. Dashboard, daftar jatuh tempo/terlambat, ekspor rekap Excel (format kolom sama dengan Entry Form lama).
10. Audit log.

### Setelah MVP (fase lanjutan)
- Kalender ketersediaan kendaraan dinas dengan tampilan bulanan.
- Perpanjangan massal peminjaman tahunan setiap Januari.
- Notifikasi WhatsApp/email (H-3 jatuh tempo, terlambat).
- QR code pada barang untuk cek status cepat.
- Tanda tangan elektronik (TTE) menggantikan tanda tangan basah.
- PWA yang dapat dipasang di layar utama HP.

### Di luar cakupan
Pengadaan, penghapusan, inventarisasi fisik, dan penilaian BMN; integrasi langsung ke SIMAK-BMN.

## 5. Jenis peminjaman

Dari log Excel terlihat dua pola yang berbeda dan keduanya harus didukung:

| Jenis | Contoh | Ciri | Kolom `loan_type` |
|---|---|---|---|
| Penugasan tahunan | Laptop, HP, sepeda motor/scooter dinas | 1 Jan s.d. 31 Des, keperluan seragam "pelaksanaan pekerjaan di dalam dan luar kantor", dibuat serentak | `ANNUAL` |
| Sementara | Mobil dinas | Beberapa hari, keperluan spesifik (koordinasi ke kabupaten), rawan bentrok jadwal | `SHORT_TERM` |

Barang berkategori kendaraan roda empat wajib melalui cek bentrok jadwal; barang `ANNUAL` cukup dicek bahwa belum ditugaskan ke orang lain.

## 6. User stories dan kriteria penerimaan

### Peminjam
- **US-01** Sebagai peminjam, saya login lalu melihat daftar barang yang tersedia beserta kategori, merk, dan statusnya.
  - Barang yang sedang dipinjam atau rusak tampil nonaktif dengan alasan dan tanggal tersedia kembali.
- **US-02** Saya membuat pengajuan: pilih satu atau lebih barang, isi periode dan keperluan per barang.
  - Data diri (nama, NIP, pangkat/golongan, jabatan, alamat) terisi otomatis dari profil; saya bisa memperbaiki alamat.
  - Sistem menolak periode yang bentrok dengan peminjaman lain, dengan pesan yang jelas.
  - Tanggal pengembalian tidak boleh sebelum tanggal peminjaman.
- **US-03** Saya melihat status pengajuan dalam bentuk timeline yang mengikuti langkah SOP.
- **US-04** Saya mengunggah foto/scan dokumen bertanda tangan (PDF, JPG, PNG; maksimal 10 MB per file) langsung dari kamera HP.
- **US-05** Saya mengajukan pengembalian dan melihat hasil pengecekan kondisi.

### Pengelola BMN
- **US-06** Saya melihat antrean pengajuan baru, diurutkan dari yang terlama, dengan penanda target waktu SOP.
- **US-07** Saya menelaah pengajuan dengan checklist: dokumen lengkap, barang tersedia, kondisi baik. Jika tidak lengkap, saya mengembalikan ke peminjam dengan catatan. Jika lengkap, saya meneruskan ke Ketua Tim.
- **US-08** Setelah disetujui, saya membuat BAST Peminjaman dengan satu klik, mengunduh PDF, dan mengunggah versi bertanda tangan.
- **US-09** Saya memproses pengembalian: cek jumlah, jenis, dan kondisi per barang; pilih kondisi Baik, Rusak, atau Tidak Lengkap/Hilang; isi tindak lanjut; buat BAST Pengembalian.
- **US-10** Saya mengelola data barang (tambah, ubah, nonaktifkan) dan melihat riwayat peminjaman tiap barang.

### Ketua Tim Aset
- **US-11** Saya melihat pengajuan yang sudah ditelaah dan menyetujui atau menolak (alasan wajib saat menolak), dari HP sekalipun.
- **US-12** Saya dapat menyetujui banyak pengajuan penugasan tahunan sekaligus.

### Admin
- **US-13** Saya mengatur akun dan peran, penandatangan aktif (Ketua Tim dan Pengelola Aset), nama satker/kop, dan format nomor dokumen.
- **US-14** Saya mengimpor data dari Excel dengan pratinjau dan laporan kesalahan sebelum data disimpan.

## 7. Aturan bisnis

| Kode | Aturan |
|---|---|
| BR-01 | Barang dikenali unik oleh pasangan `kode_barang` + `NUP`. Kendaraan baru yang belum terdaftar (kode 0, NUP 0) dikenali dengan nomor polisi. |
| BR-02 | Satu barang tidak boleh punya dua peminjaman aktif yang periodenya beririsan. |
| BR-03 | Hanya barang berkondisi `BAIK` dan berstatus aktif yang bisa dipinjam, kecuali ada pengecualian oleh Pengelola BMN dengan alasan tertulis. |
| BR-04 | Alur status wajib mengikuti SOP; loncat status tidak diperbolehkan (lihat `WORKFLOW_SOP.md`). |
| BR-05 | Penolakan di tahap telaah atau persetujuan mengembalikan pengajuan ke peminjam dengan catatan wajib. |
| BR-06 | Nomor dokumen dibuat atomik, berurutan per jenis dokumen per tahun, tanpa celah, dan tidak dapat diubah setelah dokumen diterbitkan. Dokumen yang salah dibatalkan lalu diterbitkan ulang dengan nomor baru. |
| BR-07 | Data diri di dokumen adalah snapshot saat dokumen diterbitkan. Perubahan pangkat/jabatan kemudian tidak mengubah dokumen lama. |
| BR-08 | Pemohon tidak boleh menjadi penelaah atau penyetuju pengajuannya sendiri. Jika pemohon adalah Ketua Tim atau Pengelola BMN, sistem meminta penyetuju pengganti (lihat OPEN_QUESTIONS Q8). Catatan dari data: kedua penandatangan saat ini juga tercatat sebagai peminjam laptop. |
| BR-09 | Peminjaman dianggap terlambat jika tanggal pengembalian terlewati dan BAST Pengembalian belum selesai. Status terlambat dihitung, bukan disimpan. |
| BR-10 | Pengembalian dengan kondisi `RUSAK` atau `TIDAK_LENGKAP_HILANG` wajib mengisi "Tindak lanjut" dan mengubah status barang menjadi `PERLU_TINDAK_LANJUT` sampai Pengelola BMN menutupnya. |
| BR-11 | Redaksi tiga dokumen tidak boleh diubah tanpa persetujuan (lihat `DOCUMENT_TEMPLATES.md`). |
| BR-12 | Semua aksi mengubah data tercatat di `audit_logs` (aktor, aksi, entitas, waktu WITA, nilai sebelum/sesudah). |

## 8. Peta halaman

Publik: `/login`, `/lupa-password`.

Peminjam (navigasi bawah di mobile): `/` Beranda · `/barang` Katalog · `/pengajuan/baru` · `/pengajuan` Riwayat · `/pengajuan/[id]` Detail · `/profil`.

Pengelola BMN: `/kelola/antrean` · `/kelola/pengajuan/[id]` · `/kelola/barang` · `/kelola/barang/[id]` · `/kelola/peminjaman-aktif` · `/kelola/pengembalian` · `/kelola/dokumen` · `/kelola/laporan`.

Ketua Tim: `/persetujuan` · `/persetujuan/[id]`.

Admin: `/admin/pengguna` · `/admin/penandatangan` · `/admin/pengaturan` · `/admin/impor` · `/admin/audit`.

## 9. Kebutuhan non-fungsional

- **Responsif dan mobile-first**: lolos di lebar 360, 390, 768, 1024, 1440 px. Detail di `UI_UX_SPEC.md`.
- **Performa**: LCP < 2,5 detik pada 4G ponsel Android kelas menengah; skor Lighthouse mobile ≥ 90 untuk Performance dan Accessibility.
- **Keamanan**: autentikasi, otorisasi per peran di sisi server, validasi Zod di setiap input, rate limiting login, unggahan dipindai tipe dan ukuran. Lihat `.agents/rules/security-privacy.md`.
- **Privasi**: patuh prinsip minimisasi data (UU PDP No. 27 Tahun 2022). Hanya data yang dibutuhkan dokumen yang disimpan.
- **Ketersediaan data**: backup harian basis data; ekspor penuh ke Excel kapan saja.
- **Aksesibilitas**: WCAG 2.2 AA; seluruh fungsi bisa dioperasikan dengan keyboard.
- **Bahasa**: Indonesia; format tanggal `11 Januari 2026`, mata uang `Rp 12.646.700`.
- **Pengujian**: unit test untuk state machine, penomoran, dan cek bentrok; e2e untuk alur peminjaman penuh dan pengembalian.

## 10. Risiko

| Risiko | Mitigasi |
|---|---|
| Data Excel kotor (merk/type tertukar, kode barang 0, nama beda penulisan) | Skill `bmn-excel-import` dengan pratinjau dan laporan anomali |
| Kebocoran data pribadi | Aturan privasi, data anonim di dev, akses berbasis peran |
| Penolakan perubahan kebiasaan dari tanda tangan basah | Tahap 1 tetap cetak dan unggah scan; TTE menyusul |
| Redaksi dokumen berubah mendadak | Template dipusatkan di satu modul sehingga mudah diperbarui |
