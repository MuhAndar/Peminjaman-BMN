# Rencana Pembangunan Bertahap + Prompt Siap Tempel

Cara pakai: di Antigravity pilih mode **Planning**, tempel prompt fase, tinjau rencana agent, setujui, lalu biarkan mengerjakan. Satu fase = satu sesi. Setelah selesai, periksa kriteria "Selesai bila", commit, lalu lanjut. Jangan gabung beberapa fase dalam satu prompt.

## Prompt pembuka (jalankan sekali, tanpa kode)

```
Baca AGENTS.md, seluruh isi docs/ (PRD, WORKFLOW_SOP, DATA_MODEL, DOCUMENT_TEMPLATES,
UI_UX_SPEC, OPEN_QUESTIONS) dan lihat gambar di docs/reference/.
Jangan menulis kode dulu. Tulis ringkasan pemahamanmu dalam maksimal 20 baris:
tujuan produk, peran, alur status, tiga dokumen, dan 5 risiko terbesar.
Lalu daftar semua ketidakjelasan atau kontradiksi yang kamu temukan di dokumen.
```

Periksa jawabannya. Kalau pemahamannya meleset, perbaiki dokumen dulu, bukan kodenya.

---

## Fase 0 — Fondasi proyek

**Prompt**
```
Kerjakan Fase 0. Buat kerangka proyek sesuai "Stack default" di AGENTS.md:
Next.js App Router + TypeScript strict + Tailwind + shadcn/ui + pnpm.
Siapkan: struktur folder sesuai .agents/rules/engineering.md, ESLint + Prettier,
Vitest, Playwright, skrip pnpm yang tercantum di AGENTS.md, .env.example,
dan halaman beranda sementara.
Gunakan skill frontend-design dan ui-ux-pro-max untuk menyusun design tokens
(src/styles/tokens.css) berdasarkan docs/UI_UX_SPEC.md bagian 2-3, termasuk mode gelap,
lalu bangun app shell responsif (app bar + navigasi bawah mobile, sidebar desktop).
Tampilkan hasilnya di browser pada lebar 390 dan 1280 px.
```
**Selesai bila**: `pnpm dev` jalan; lint/typecheck/test hijau; shell tampil benar di mobile dan desktop; token terpusat; mode gelap berfungsi.

## Fase 1 — Basis data, autentikasi, peran

**Prompt**
```
Kerjakan Fase 1. Implementasikan skema di docs/DATA_MODEL.md bagian 1 dengan Drizzle ORM
dan migrasi (termasuk exclusion constraint bentrok periode).
Tambahkan autentikasi sesuai asumsi default di docs/OPEN_QUESTIONS.md (Q2),
tabel peran, helper requireRole() di lib/rbac.ts, dan guard rute per grup peran.
Buat seed development berisi DATA FIKTIF saja: 12 pegawai, 4 peran, 20 barang
(laptop, HP, motor, 2 mobil), 2 penandatangan. Ikuti .agents/rules/security-privacy.md.
Tulis unit test untuk rbac dan test integrasi untuk constraint bentrok.
```
**Selesai bila**: migrasi + seed berjalan dari nol; login berfungsi untuk tiap peran; akses rute lintas peran ditolak; test constraint bentrok lulus.

## Fase 2 — Master data dan impor Excel

**Prompt**
```
Kerjakan Fase 2. (a) CRUD Barang untuk Pengelola BMN: daftar (tabel di desktop, kartu di mobile),
filter kategori/status, pencarian, form tambah/ubah, nonaktifkan, halaman detail dengan riwayat.
(b) Halaman Admin untuk pegawai, peran, penandatangan, dan pengaturan satker/kop/format nomor.
(c) Gunakan skill bmn-excel-import untuk membuat scripts/import-excel.ts dan halaman /admin/impor
(dry-run → pratinjau → terapkan). Uji hanya dengan fixture fiktif.
```
**Selesai bila**: CRUD berfungsi di mobile dan desktop; impor fixture idempoten; laporan anomali tampil; tidak ada data pribadi di log.

## Fase 3 — Pengajuan peminjaman (sisi peminjam)

**Prompt**
```
Kerjakan Fase 3. Implementasikan lib/loan-state-machine.ts lengkap (tabel transisi di
docs/WORKFLOW_SOP.md bagian 4) dengan unit test untuk semua transisi sah dan tidak sah.
Lalu bangun sisi Peminjam: katalog barang dengan ketersediaan, wizard pengajuan 4 langkah
(docs/UI_UX_SPEC.md bagian 5), simpan draf otomatis, cek bentrok dengan pesan ramah,
daftar riwayat, halaman detail dengan timeline status. Data diri terisi otomatis dari profil
dan disimpan sebagai snapshot saat kirim.
```
**Selesai bila**: peminjam bisa membuat dan mengirim pengajuan multi-barang dari ponsel; bentrok periode ditolak; timeline tampil benar; e2e Playwright (viewport mobile) lulus.

## Fase 4 — Telaah dan persetujuan

**Prompt**
```
Kerjakan Fase 4. Bangun antrean Pengelola BMN dan halaman telaah dengan checklist
(lengkap, tersedia, kondisi baik), aksi "Kembalikan ke peminjam" (catatan wajib) dan
"Teruskan ke Ketua Tim". Bangun halaman persetujuan Ketua Tim (Setujui / Kembalikan / Tolak,
alasan wajib saat menolak, setujui massal untuk penugasan tahunan).
Terapkan BR-08 (tidak boleh memproses pengajuan sendiri). Tambahkan notifikasi dalam aplikasi
dan penanda target waktu SOP. Semua perubahan status lewat state machine dan tercatat di audit.
```
**Selesai bila**: alur Ajukan → Telaah → Setujui jalan end-to-end; uji otorisasi negatif lulus; audit log terisi.

## Fase 5 — Dokumen PDF

**Prompt**
```
Kerjakan Fase 5 dengan skill bmn-document-generator. Buat tiga dokumen PDF persis sesuai
docs/DOCUMENT_TEMPLATES.md, penomoran atomik, route unduh dengan pemeriksaan izin, tombol
Lihat/Unduh/Bagikan, unggah dokumen bertanda tangan (kamera/berkas, kompres di klien),
transisi APPROVED → BAST_ISSUED → ACTIVE, dan status barang menjadi ON_LOAN.
Bandingkan hasil PDF dengan docs/reference/template-*.png untuk kasus 1, 3, dan 15 barang.
```
**Selesai bila**: tiga PDF identik redaksi dengan template; 20 permintaan nomor paralel menghasilkan nomor unik; tabel panjang tidak memotong blok tanda tangan.

## Fase 6 — Pengembalian

**Prompt**
```
Kerjakan Fase 6. Peminjam mengajukan pengembalian; Pengelola memeriksa per barang
(Baik/Rusak/Tidak Lengkap-Hilang + tindak lanjut wajib bila bukan Baik, BR-10), menerbitkan
BAST Pengembalian, mengunggah versi bertanda tangan, lalu status barang diperbarui
(AVAILABLE atau NEEDS_FOLLOW_UP). Tambahkan perhitungan "Terlambat" (BR-09) dan halaman
Peminjaman Aktif untuk Pengelola dengan filter terlambat/akan jatuh tempo.
```
**Selesai bila**: e2e penuh Ajukan → … → Selesai lulus di mobile dan desktop; kasus barang rusak menutup dengan benar.

## Fase 7 — Dashboard, laporan, audit

**Prompt**
```
Kerjakan Fase 7. Dashboard per peran (angka ringkas, daftar yang butuh tindakan),
laporan rekap peminjaman dengan filter periode/kategori/peminjam, ekspor Excel dengan urutan
kolom yang sama seperti Sheet1 Entry Form lama (docs/DATA_MODEL.md bagian 2), dan halaman audit
Admin dengan filter. Dashboard Pimpinan hanya-baca bila peran itu diaktifkan.
```
**Selesai bila**: ekspor Excel terbuka benar di Excel/LibreOffice; angka dashboard cocok dengan data uji.

## Fase 8 — Poles, kualitas, dan rilis

**Prompt**
```
Kerjakan Fase 8. Audit seluruh halaman dengan skill web-design-guidelines dan daftar uji di
docs/UI_UX_SPEC.md bagian 12; perbaiki semua temuan. Jalankan Lighthouse mobile (target
Performance ≥ 90, Accessibility ≥ 95), perbaiki penyebab skor rendah. Tambahkan manifest PWA,
halaman offline, header keamanan (CSP dll.), rate limiting login, pnpm audit. Tulis README
penerapan: variabel lingkungan, migrasi, backup/restore, cara menjalankan impor produksi.
```
**Selesai bila**: semua checklist UI hijau; tidak ada celah dependensi kritis; README penerapan bisa diikuti orang lain dari nol.

## Fase lanjutan (setelah MVP dipakai)
Kalender kendaraan · perpanjangan massal tahunan · notifikasi WhatsApp/email · QR code barang · TTE (tanda tangan elektronik) · ekspor Word dapat diedit.

---

## Tips bekerja dengan agent

- **Kecil dan bertahap.** Prompt yang meminta "buat seluruh aplikasi" hampir selalu menghasilkan kode yang sulit diperbaiki.
- **Minta rencana dulu**, baca, koreksi, baru setujui.
- **Verifikasi sendiri hal yang berisiko**: otorisasi, penomoran dokumen, dan redaksi dokumen.
- **Commit setelah tiap fase** agar mudah mundur.
- **Perbarui dokumen, bukan hanya kode.** Jika keputusan berubah, ubah `docs/` dulu supaya agent berikutnya tidak kembali ke versi lama.
- **Mode terminal:** atur agar Antigravity meminta persetujuan sebelum menjalankan perintah terminal, terutama saat mengimpor data atau memasang paket.
