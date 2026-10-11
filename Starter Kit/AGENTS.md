# AGENTS.md — Aplikasi Peminjaman & Pengembalian BMN (BBPSDMP Makassar)

File ini dibaca otomatis oleh Antigravity (dan juga Claude Code, Cursor, Codex). Isinya sengaja singkat: ringkasan proyek + peta dokumen. Detail ada di `docs/`, aturan teknis ada di `.agents/rules/`.

## Tujuan produk
Web app internal untuk mengelola peminjaman dan pengembalian Barang Milik Negara (BMN) di Balai Besar Pelatihan SDM Komunikasi dan Digital Makassar (Kementerian Komdigi). Aplikasi menggantikan proses manual saat ini: Excel "Entry Form Peminjaman" + mail-merge Word + tanda tangan basah.

Alur mengikuti SOP resmi: **Peminjam → Pengelola BMN → Ketua Tim Keuangan dan Aset → BAST → serah terima → pengembalian**.

Tiga dokumen yang harus dihasilkan aplikasi (PDF, persis mengikuti template):
1. Surat Peminjaman Barang Inventaris
2. Berita Acara Serah Terima (BAST) Peminjaman
3. BAST Pengembalian

## Peta dokumen (baca sesuai tugas)
| Kebutuhan | File |
|---|---|
| Fitur, peran, aturan bisnis, kriteria selesai | `docs/PRD.md` |
| Alur SOP dan status pengajuan | `docs/WORKFLOW_SOP.md` |
| Skema database, mapping kolom Excel, aturan pembersihan data | `docs/DATA_MODEL.md` |
| Isi persis ketiga dokumen + field mapping | `docs/DOCUMENT_TEMPLATES.md` |
| Desain, responsif, mobile, aksesibilitas | `docs/UI_UX_SPEC.md` |
| Urutan pengerjaan + prompt per fase | `docs/BUILD_PLAN.md` |
| Pertanyaan yang belum dijawab + asumsi default | `docs/OPEN_QUESTIONS.md` |
| Gambar referensi (flowchart SOP, template dokumen) | `docs/reference/` |

## Stack default (bisa diganti setelah `OPEN_QUESTIONS.md` terjawab)
Next.js (App Router) + TypeScript strict · Tailwind CSS + shadcn/ui · PostgreSQL + Drizzle ORM · Zod · react-hook-form · TanStack Table · `@react-pdf/renderer` (PDF) · `exceljs` (impor/ekspor Excel) · date-fns + date-fns-tz · Vitest + Playwright. Package manager: pnpm.

## Perintah
```
pnpm dev            # dev server
pnpm lint && pnpm typecheck && pnpm test
pnpm db:migrate     # jalankan migrasi
pnpm db:seed        # seed DATA ANONIM untuk development
pnpm test:e2e       # Playwright
```

## Aturan emas (ringkas)
1. **Bahasa**: seluruh teks UI dan dokumen dalam Bahasa Indonesia formal. Kode, nama variabel, nama tabel, dan commit message dalam Bahasa Inggris.
2. **Zona waktu**: `Asia/Makassar` (WITA, UTC+8). Tanggal dokumen ditulis format Indonesia, contoh "11 Januari 2026".
3. **Mobile-first**: setiap halaman harus nyaman di layar 360 px sebelum dirapikan untuk desktop.
4. **Dokumen resmi tidak boleh berubah redaksi.** Teks ketiga dokumen diambil dari `docs/DOCUMENT_TEMPLATES.md`; jangan diparafrase.
5. **Data pribadi pegawai sensitif.** Jangan pernah menaruh data asli (NIP, alamat, nomor WA, NIK) di repo, seed, log, atau contoh. Lihat `.agents/rules/security-privacy.md`.
6. **Satu sumber status.** Perpindahan status pengajuan hanya lewat state machine di `src/lib/loan-state-machine.ts`, bukan update kolom status langsung.
7. Kerjakan per fase di `docs/BUILD_PLAN.md`. Jangan loncat fase. Selesai satu fase = lint, typecheck, dan test hijau.
8. Jika ada informasi yang belum pasti, lihat `docs/OPEN_QUESTIONS.md`. Gunakan asumsi default di sana dan beri komentar `// ASSUMPTION: ...` di kode; jangan mengarang aturan baru.
