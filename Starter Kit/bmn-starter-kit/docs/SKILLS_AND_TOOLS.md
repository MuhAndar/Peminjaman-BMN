# Skill, MCP, dan Alat Bantu yang Direkomendasikan

Yang diverifikasi langsung dari dokumentasi/README (Oktober 2026): struktur folder skill dan rules Antigravity, CLI `skills`, installer `antigravity-awesome-skills`, dan `uipro-cli`. Nama skill di repo Anthropic dan Vercel serta konfigurasi Context7/shadcn berasal dari pengetahuan saya: jalankan `--list` untuk memastikan nama persisnya. Ekosistem ini bergerak cepat; bila sebuah perintah gagal, jalankan `--help` atau buka README repositorinya.

## 1. Cara kerja skill di Antigravity

Skill = folder berisi `SKILL.md` (frontmatter `name` + `description`) yang dibaca agent hanya saat relevan, sehingga tidak memenuhi konteks. Lokasi:

| Cakupan | Lokasi |
|---|---|
| Workspace (khusus proyek) | `<proyek>/.agents/skills/<nama>/SKILL.md` (lama: `.agent/skills/`, masih didukung) |
| Global (semua proyek) | `~/.gemini/config/skills/<nama>/` (installer lama memakai `~/.gemini/antigravity/skills/`) |

Skill bisa dipanggil lewat perintah slash `/<nama-skill>` atau disebut di prompt ("gunakan skill frontend-design"). Setelah memasang skill, **restart Antigravity**, lalu cek di **⋯ → Customizations → Skills**.

Karena tiap installer bisa menulis ke folder berbeda, selalu cek hasilnya:
```bash
ls .agents/skills .agent/skills 2>/dev/null
ls ~/.gemini/config/skills ~/.gemini/antigravity/skills 2>/dev/null
```
Jika skill mendarat di `.agent/skills/` sedangkan proyek memakai `.agents/skills/`, pindahkan foldernya.

## 2. Installer universal: `skills` CLI

Satu perintah untuk memasang skill dari repositori GitHub mana pun ke Antigravity. Butuh Node.js (untuk `npx`).

```bash
# lihat skill apa saja yang ada di sebuah repo (tanpa memasang)
npx skills add anthropics/skills --list

# pasang skill tertentu ke Antigravity, di proyek ini
npx skills add anthropics/skills --skill frontend-design -a antigravity

# pasang secara global (semua proyek)
npx skills add anthropics/skills --skill frontend-design -g -a antigravity

# hapus
npx skills remove frontend-design --agent antigravity
```
Opsional: matikan telemetri dengan `DISABLE_TELEMETRY=1 npx skills add ...`. Sumber: github.com/vercel-labs/skills.

## 3. Skill yang saya rekomendasikan untuk proyek ini

### Prioritas A — pasang semuanya

| Skill | Untuk apa | Repo | Perintah |
|---|---|---|---|
| `frontend-design` | Mencegah tampilan generik "buatan AI"; mendorong arah visual yang berani dan konsisten. Dipakai saat Fase 0 dan setiap layar baru | github.com/anthropics/skills | `npx skills add anthropics/skills --skill frontend-design -a antigravity` |
| `ui-ux-pro-max` | Pustaka gaya, palet, pasangan huruf, dan aturan UX; menghasilkan design system sebelum menulis UI | github.com/nextlevelbuilder/ui-ux-pro-max-skill | `npm install -g uipro-cli` lalu jalankan `uipro init` di folder proyek dan pilih **Antigravity** (atau tanpa instal global: `npx uipro-cli init`). Restart IDE setelahnya |
| `web-design-guidelines` | Mengaudit UI terhadap panduan antarmuka web (aksesibilitas, fokus, formulir, gerak, performa) | github.com/vercel-labs/agent-skills | `npx skills add vercel-labs/agent-skills --skill web-design-guidelines -a antigravity` |
| `react-best-practices` | Praktik performa React/Next.js (waterfall data, ukuran bundle, render). Nama persis bisa berbeda; cek `--list` | github.com/vercel-labs/agent-skills | `npx skills add vercel-labs/agent-skills --list` lalu pasang skill React/Next.js yang tampil |

### Prioritas B — pasang bila perlu

| Skill | Untuk apa | Perintah |
|---|---|---|
| `skill-creator` (Anthropic) | Menyempurnakan skill kustom di `.agents/skills/` (`bmn-document-generator`, `bmn-excel-import`) | `npx skills add anthropics/skills --skill skill-creator -a antigravity` |
| `antigravity-awesome-skills` (sickn33) | Katalog sangat besar (ribuan skill komunitas: backend, keamanan, pengujian, dll.), salah satu repositori skill terbesar di GitHub. Berguna sebagai bahan pilihan, **bukan untuk dipasang semua** | `npx antigravity-awesome-skills --help`, lalu pasang terpilih, mis. `npx antigravity-awesome-skills --path .agents/skills --category development,backend --risk safe,none`. Katalog: sickn33.github.io/antigravity-awesome-skills |

Kalau memakai katalog besar, ambil **5–10 skill yang jelas relevan** (Next.js, PostgreSQL, pengujian, keamanan web, aksesibilitas, debugging). Terlalu banyak skill aktif membuat agent bingung memilih dan memboroskan konteks.

### Skill kustom yang sudah ada di kit ini
`bmn-document-generator` dan `bmn-excel-import` (di `.agents/skills/`) memuat pengetahuan khusus instansi Anda yang tidak ada di skill umum.

## 4. MCP server yang berguna

MCP server memberi agent kemampuan memakai alat luar. Tambahkan lewat menu **MCP Servers** di panel agent Antigravity (pilih *View raw config* untuk memasukkan JSON).

- **Context7** — mengambil dokumentasi terbaru pustaka (Next.js, Drizzle, Tailwind, React-PDF) supaya agent tidak memakai API usang.
  ```json
  { "mcpServers": { "context7": { "command": "npx", "args": ["-y", "@upstash/context7-mcp"] } } }
  ```
- **shadcn/ui MCP** (opsional) — mencari dan memasang komponen shadcn. Lihat dokumentasi shadcn untuk perintah inisialisasi klien yang didukung.
- **Browser bawaan Antigravity** — dipakai agent untuk membuka aplikasi dan mengambil tangkapan layar mobile/desktop; aktifkan dan minta verifikasi visual di tiap fase.

**Hati-hati dengan MCP database.** Jika memasang MCP PostgreSQL, arahkan hanya ke database lokal berisi data fiktif, jangan ke server produksi.

## 5. Daftar periksa sebelum memasang skill/MCP pihak ketiga

- [ ] Berasal dari organisasi/penulis yang dikenal (Anthropic, Vercel, atau repositori dengan riwayat dan lisensi jelas).
- [ ] Baca `SKILL.md` dan semua berkas di `scripts/`. Tolak yang menjalankan perintah mencurigakan, mengunduh dan mengeksekusi sesuatu, atau mengirim data ke luar.
- [ ] Untuk MCP: periksa izin yang diminta dan jangan memberikan kredensial produksi.
- [ ] Catat versi/commit yang dipasang (`git log -1` pada repo sumber) agar bisa diaudit.
- [ ] Setelah dipasang, jalankan prompt uji sederhana dan pastikan perilakunya sesuai deskripsi.

## 6. Pengaturan Antigravity yang disarankan

- Mode **Planning** untuk tugas besar; mode cepat untuk perbaikan kecil.
- Atur kebijakan eksekusi terminal agar **meminta persetujuan** sebelum menjalankan perintah, terutama saat memasang paket atau menyentuh data.
- Batas ukuran file rule dan workflow di Antigravity adalah 12.000 karakter per file; kit ini sudah sesuai.
- Aktifkan fitur tangkapan layar/browser agent supaya hasil UI dapat diperiksa visual, bukan hanya lolos kompilasi.
- Taruh keputusan bisnis di `docs/`, bukan hanya di chat, supaya tetap ada pada sesi berikutnya.
