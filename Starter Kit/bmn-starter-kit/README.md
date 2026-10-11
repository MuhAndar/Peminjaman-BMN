# Starter Kit — Website Peminjaman & Pengembalian BMN

Kit ini berisi konteks, aturan, dan rencana kerja supaya agent di **Google Antigravity IDE** langsung paham proyeknya dan menghasilkan kode yang konsisten.

## Isi kit

```
AGENTS.md                      ← konteks utama (dibaca otomatis)
.gitignore                     ← sudah memblokir Excel & data pribadi
docs/
  PRD.md                       ← kebutuhan produk
  WORKFLOW_SOP.md              ← alur SOP → state machine
  DATA_MODEL.md                ← skema DB + mapping Excel + pembersihan data
  DOCUMENT_TEMPLATES.md        ← isi persis 3 dokumen resmi
  UI_UX_SPEC.md                ← desain modern, responsif, mobile
  BUILD_PLAN.md                ← fase kerja + prompt siap tempel
  SKILLS_AND_TOOLS.md          ← rekomendasi skill + perintah instalasi
  OPEN_QUESTIONS.md            ← pertanyaan terbuka + asumsi default
  reference/                   ← flowchart SOP (PNG) + template dokumen (PDF/PNG)
.agents/
  rules/                       ← aturan permanen (engineering, ui-ux, security-privacy)
  skills/
    bmn-document-generator/    ← skill khusus: generate 3 PDF
    bmn-excel-import/          ← skill khusus: impor & bersihkan Excel
data/                          ← kosong; isi HANYA file anonim (di-gitignore)
```

## Cara memasang (±10 menit)

1. Buat folder proyek, mis. `bmn-peminjaman/`, lalu salin **seluruh isi kit ini** ke dalamnya (termasuk folder tersembunyi `.agents`).
2. Inisialisasi git: `git init && git add . && git commit -m "chore: add agent starter kit"`.
3. Buka folder itu di Antigravity (**File → Open Folder**).
4. Buka panel agent → menu **⋯ → Customizations → Rules**. Pastikan 3 rule dari `.agents/rules/` muncul dan mode aktivasinya **Always On**. Cek juga tab **Skills**: `bmn-document-generator` dan `bmn-excel-import` harus terdaftar.
5. Pasang skill pihak ketiga yang direkomendasikan, lihat `docs/SKILLS_AND_TOOLS.md`.
6. Jawab (atau setujui default) pertanyaan di `docs/OPEN_QUESTIONS.md`, lalu update bagian yang berubah.
7. Pilih mode **Planning**, lalu tempel prompt **Fase 0** dari `docs/BUILD_PLAN.md`.

## Peringatan data pribadi

File Excel "Entry Form Peminjaman" berisi data pegawai asli (NIP, alamat rumah, nomor WA, email, bahkan NIK pada sebagian baris). **Jangan** menaruhnya di folder proyek, GitHub publik, atau menyalinnya ke prompt. Agent IDE mengirim isi file yang dibacanya ke model AI. Untuk pengembangan gunakan data anonim; impor data asli hanya di server produksi memakai skill `bmn-excel-import`.

## Catatan versi Antigravity

Antigravity sekarang memakai folder `.agents/` (jamak) sebagai default dan masih mendukung `.agent/` (tunggal). Batas ukuran satu file rule adalah 12.000 karakter; semua file di kit ini sudah di bawah batas tersebut. Jika versi Anda masih memakai `.agent/`, cukup ganti nama foldernya.
