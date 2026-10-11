---
trigger: always_on
description: Standar rekayasa perangkat lunak untuk proyek BMN. Berlaku untuk semua perubahan kode.
---

# Standar Engineering

## Struktur proyek
```
src/
  app/                    # rute Next.js (App Router), dikelompokkan per peran
    (auth)/login  (peminjam)/…  (kelola)/kelola/…  (persetujuan)/persetujuan/…  (admin)/admin/…
  components/ui/          # komponen dasar (shadcn/ui)
  components/domain/      # komponen khusus BMN (StatusBadge, LoanTimeline, AssetCard, …)
  lib/                    # logika murni tanpa I/O
    loan-state-machine.ts # SATU-SATUNYA tempat aturan transisi status
    document-text.ts      # redaksi baku 3 dokumen (konstanta)
    status-meta.ts        # label, warna, ikon status
    format.ts             # rupiah, tanggal Indonesia, format NIP
    numbering.ts          # format nomor dokumen
    rbac.ts               # izin per peran
  server/
    db/ (schema, migrasi)  queries/  actions/  services/
  pdf/                    # template @react-pdf/renderer
  styles/tokens.css
scripts/                  # impor Excel, seed
tests/                    # unit + e2e
```

## Aturan kode
- TypeScript `strict: true`; dilarang `any` dan `@ts-ignore` tanpa komentar alasan. Gunakan `unknown` + validasi Zod di batas sistem.
- Semua input dari klien divalidasi dengan **Zod di server**, termasuk Server Actions dan Route Handlers. Skema dipakai bersama klien (formulir) dan server.
- Logika bisnis (state machine, cek bentrok, penomoran, hitung terlambat) ditulis sebagai fungsi murni di `lib/` atau service di `server/services/` dan **diuji unit**. Komponen UI tidak memuat aturan bisnis.
- Perubahan status pengajuan hanya lewat `transitionLoan(...)` di state machine. Fungsi itu memeriksa peran, guard, menulis `loan_status_history` dan `audit_logs`, dan berjalan dalam **satu transaksi** basis data.
- Penomoran dokumen dan cek bentrok barang berjalan di dalam transaksi (atau constraint database). Jangan hitung "nomor berikutnya" di aplikasi lalu insert terpisah.
- Otorisasi diperiksa **di server pada setiap action/route**, bukan hanya dengan menyembunyikan tombol di UI. Pola: `await requireRole(['PENGELOLA_BMN'])` di awal.
- Tanggal: simpan `timestamptz` (UTC) dan `date` untuk tanggal murni; tampilkan di `Asia/Makassar`. Jangan memakai `new Date()` mentah untuk logika "hari ini"; gunakan helper `todayInMakassar()`.
- Uang: `numeric(16,0)` di DB, dibaca sebagai string/BigInt di aplikasi, diformat lewat `format.ts`. Jangan memakai float untuk nilai Rupiah.
- Semua teks yang tampil ke pengguna berbahasa Indonesia dan dikumpulkan di satu tempat per modul (konstanta), bukan tersebar sebagai string literal di JSX yang berulang.
- Penanganan galat: kembalikan hasil terstruktur `{ ok: false, code, message }` dari action; jangan membocorkan stack trace atau pesan SQL ke klien.
- Jangan menambah dependensi baru tanpa alasan tertulis di PR/ringkasan. Prefer pustaka yang sudah ada di stack.

## Basis data
- Perubahan skema hanya lewat file migrasi yang bisa ditinjau. Jangan edit migrasi yang sudah diterapkan.
- Gunakan constraint (`UNIQUE`, `CHECK`, `EXCLUDE`) untuk invarian penting; aplikasi memberi pesan ramah, database tetap penjaga terakhir.
- Seed development hanya berisi **data fiktif**; lihat aturan privasi.

## Pengujian (wajib sebelum menyatakan selesai)
- Unit test: state machine (semua transisi sah dan tidak sah), penomoran (konkurensi), cek bentrok periode, format NIP/rupiah/tanggal, pembersihan data impor.
- Integration test: server action utama dengan database uji.
- E2E (Playwright, viewport mobile dan desktop): alur Ajukan → Telaah → Setujui → BAST → Aktif → Kembalikan → Selesai.
- Jalankan `pnpm lint && pnpm typecheck && pnpm test` dan laporkan hasilnya. Jangan menandai tugas selesai bila ada yang gagal.

## Alur kerja dengan agent
- Untuk tugas > 3 berkas, buat rencana singkat dulu (Planning mode) dan minta persetujuan sebelum menulis kode.
- Kerjakan satu fase `docs/BUILD_PLAN.md` dalam satu waktu; commit kecil dengan pesan Conventional Commits (`feat:`, `fix:`, `chore:`, `docs:`, `test:`).
- Jika spesifikasi tidak menjawab suatu keputusan, rujuk `docs/OPEN_QUESTIONS.md`, pakai asumsi default, beri komentar `// ASSUMPTION:` dan sebutkan di ringkasan. Jangan mengarang aturan bisnis.
- Setelah selesai, tulis ringkasan: apa yang berubah, cara mengujinya, asumsi yang dipakai, dan hal yang belum dikerjakan.
