# Spesifikasi UI/UX — Modern, Responsif, Mobile-First

Tujuan: aplikasi terasa seperti produk digital modern yang tenang dan tepercaya, cocok untuk instansi pemerintah, dan nyaman dipakai pegawai dari ponsel. Bukan tampilan "template AI" yang generik.

## 1. Arah desain

- **Karakter**: bersih, lega, hierarki jelas, banyak ruang putih, sedikit warna aksen. Informasi penting (status, tenggat, tombol aksi berikutnya) selalu paling menonjol.
- **Hindari**: gradien ungu/neon, kartu bersarang berlapis, bayangan tebal, sudut terlalu bulat di mana-mana, ikon dekoratif tanpa fungsi, emoji sebagai ikon, teks abu terlalu pucat, animasi berlebihan.
- **Identitas**: ambil warna dari logo KOMDIGI (biru tua, biru muda/cyan, merah marun, kuning). Ambil kode warna persis dari berkas logo resmi/brand guideline, nilai di bawah hanya titik awal.
- Mulai dengan menjalankan skill desain (lihat `SKILLS_AND_TOOLS.md`), lalu kunci hasilnya menjadi design tokens di `src/styles/tokens.css` dan konfigurasi Tailwind. Semua komponen memakai token, bukan nilai hex langsung.

## 2. Design tokens (titik awal)

| Token | Light | Dark | Catatan |
|---|---|---|---|
| `--bg` | `#F7F9FC` | `#0B1220` | Latar halaman |
| `--surface` | `#FFFFFF` | `#111A2B` | Kartu, sheet |
| `--border` | `#E2E8F0` | `#22304A` | |
| `--text` | `#0F172A` | `#E6EDF7` | |
| `--text-muted` | `#475569` | `#9FB0C8` | Kontras ≥ 4,5:1 |
| `--primary` | `#0B63B5` | `#5AAEF0` | Tombol utama, tautan, fokus |
| `--primary-strong` | `#0B4F8A` | `#8CC6F5` | Hover/aktif |
| `--accent` | `#12A8E0` | `#38BDF8` | Dekoratif/ikon besar, bukan teks kecil di atas putih |
| `--success` | `#15803D` | `#4ADE80` | |
| `--warning` | `#B45309` | `#FBBF24` | |
| `--danger` | `#B3261E` | `#F87171` | |

Radius: 8 px kontrol, 12 px kartu, 16 px sheet. Bayangan sangat halus (`0 1px 2px rgb(0 0 0 / 0.06)`). Dukung `prefers-color-scheme` dan tombol ganti tema.

## 3. Tipografi

- Keluarga huruf: **Plus Jakarta Sans** (variabel; ramah Bahasa Indonesia) lewat `next/font`, cadangan `system-ui, sans-serif`. Angka tabel dan NIP: `font-variant-numeric: tabular-nums`.
- Skala: 12 / 14 / 16 (dasar) / 18 / 20 / 24 / 30 px; tinggi baris 1,5 untuk isi, 1,25 untuk judul.
- Ukuran font input minimal **16 px** (mencegah zoom otomatis di iOS).

## 4. Breakpoint dan shell

Mobile-first; tulis gaya dasar untuk 360 px lalu tambah `sm 640`, `md 768`, `lg 1024`, `xl 1280`.

| Lebar | Shell |
|---|---|
| < 768 | App bar atas (judul halaman + aksi) · navigasi bawah 4 item · tombol aksi utama mengambang atau bar aksi lengket di bawah |
| 768–1023 | Rel navigasi samping ringkas (ikon + label) · konten satu kolom lebar |
| ≥ 1024 | Sidebar 256 px · konten maks 1200 px · panel detail di samping daftar bila cocok |

Hormati area aman perangkat (`env(safe-area-inset-*)`), `viewport-fit=cover`, tinggi layar dinamis (`100dvh`).

Navigasi bawah per peran:
- Peminjam: Beranda · Barang · Pengajuan · Profil, plus tombol **Ajukan** menonjol.
- Pengelola BMN: Antrean · Aktif · Barang · Lainnya.
- Ketua Tim: Persetujuan · Riwayat · Profil.

## 5. Pola komponen kunci

1. **Katalog barang**: kartu dengan foto/ikon kategori, nama, merk-model, NUP, status; filter berupa chip kategori + pencarian; filter lanjutan di *bottom sheet* pada mobile. Barang tidak tersedia tampil redup dengan alasan dan tanggal tersedia lagi.
2. **Wizard pengajuan (4 langkah)**: ① Pilih barang → ② Periode & keperluan → ③ Data diri (terisi otomatis, alamat bisa diubah) → ④ Tinjau & kirim. Indikator langkah di atas, tombol Lanjut/Kembali di bar bawah lengket, data tersimpan otomatis sebagai draf. Pada langkah ④ tampilkan pratinjau Surat Peminjaman.
3. **Timeline status**: vertikal, mengikuti 7 langkah SOP; langkah selesai (centang), aktif (disorot), berikutnya (redup). Tampilkan siapa yang sedang harus bertindak ("Menunggu Pengelola BMN").
4. **Tabel → kartu**: tabel penuh di ≥ 768 px; di bawahnya setiap baris menjadi kartu dengan 3–4 info terpenting dan aksi di kanan bawah. Kolom bisa diurutkan dan dicari. Jangan menggulir halaman ke samping; bila perlu, tabel menggulir di dalam wadahnya sendiri.
5. **Kalender ketersediaan kendaraan** (fase lanjutan): tampilan bulan dengan blok periode terisi; pada mobile berupa daftar agenda.
6. **Persetujuan cepat**: kartu pengajuan dengan ringkasan (siapa, barang, periode, keperluan) dan dua tombol besar Setujui / Kembalikan; tidak ada aksi yang hanya bisa dilakukan dengan gestur geser.
7. **Unggah dokumen**: tombol "Ambil foto" (kamera) dan "Pilih berkas"; pratinjau, tombol putar, kompres otomatis sisi klien sebelum unggah (≤ 10 MB).
8. **Pemeriksaan pengembalian**: daftar barang dengan kontrol segmen *Baik / Rusak / Tidak Lengkap-Hilang*; memilih selain Baik membuka kolom tindak lanjut yang wajib.
9. **Pratinjau dokumen PDF**: tampil di dalam halaman (desktop) atau buka/unduh (mobile); selalu ada tombol **Unduh** dan **Bagikan**.

## 6. Status dan label (satu sumber di `src/lib/status-meta.ts`)

| Status | Label Indonesia | Warna | Ikon (lucide) |
|---|---|---|---|
| DRAFT | Draf | netral | `file-pen` |
| SUBMITTED | Menunggu telaah | biru | `inbox` |
| NEEDS_REVISION | Perlu perbaikan | kuning/amber | `undo-2` |
| UNDER_APPROVAL | Menunggu persetujuan | biru tua | `hourglass` |
| APPROVED | Disetujui | hijau muda/teal | `badge-check` |
| BAST_ISSUED | Menunggu tanda tangan BAST | cyan | `file-signature` |
| ACTIVE | Sedang dipinjam | hijau | `package-check` |
| RETURN_REQUESTED | Pengembalian diajukan | biru | `package-open` |
| RETURN_CHECKED | Pengecekan selesai | biru tua | `clipboard-check` |
| RETURNED | Selesai | netral hijau | `check-circle-2` |
| REJECTED | Ditolak | merah | `x-circle` |
| CANCELLED | Dibatalkan | abu | `ban` |
| (turunan) OVERDUE | Terlambat | merah, garis tepi | `alarm-clock` |

Warna tidak boleh menjadi satu-satunya pembeda: selalu sertakan ikon dan teks.

## 7. Keadaan antarmuka

- **Memuat**: skeleton yang bentuknya sama dengan konten, bukan spinner layar penuh.
- **Kosong**: ilustrasi sederhana/ikon + satu kalimat penjelas + tombol tindakan ("Belum ada pengajuan. Ajukan peminjaman pertama Anda").
- **Galat**: pesan dalam Bahasa Indonesia yang menyebut apa yang terjadi dan apa yang bisa dilakukan; tombol "Coba lagi".
- **Sukses**: toast singkat (3–4 detik) di atas bar navigasi bawah, tidak menutupi tombol aksi.
- **Aksi berisiko** (tolak, batalkan, tandai hilang): dialog konfirmasi dengan kata kerja spesifik ("Ya, tolak pengajuan"), bukan "OK".
- **Formulir**: validasi saat kehilangan fokus dan saat kirim; pesan kesalahan dekat kolom; jangan menghapus isian pengguna saat galat.

## 8. Mikro-interaksi

Transisi 150–250 ms, easing lembut; hormati `prefers-reduced-motion`. Gerak hanya untuk memberi tahu perubahan (item baru muncul, status berubah), bukan hiasan.

## 9. Aksesibilitas (WCAG 2.2 AA)

- Target sentuh ≥ 44×44 px, jarak antar target ≥ 8 px.
- Kontras teks ≥ 4,5:1 (teks besar ≥ 3:1), cincin fokus terlihat jelas 2 px.
- Semua kontrol punya label; urutan tab logis; dialog menjebak fokus dan bisa ditutup dengan Esc.
- Tautan "Lewati ke konten utama"; atribut `lang="id"`; landmark (`header`, `nav`, `main`).
- Pengumuman perubahan status lewat `aria-live="polite"`.
- Uji dengan pembaca layar (TalkBack/VoiceOver) pada alur Ajukan Peminjaman.

## 10. Performa

- Target: LCP < 2,5 s, INP < 200 ms, CLS < 0,1 pada 4G dan ponsel Android kelas menengah; Lighthouse mobile ≥ 90.
- Server Components secara default; Client Components hanya untuk interaksi. Pecah kode per rute. Gambar lewat `next/image`. Muat pustaka PDF hanya di server. Tabel besar memakai paginasi sisi server.

## 11. PWA (fase lanjutan, tapi siapkan dari awal)

`manifest.webmanifest` (nama, ikon 192/512, `display: standalone`, `theme_color` = primary), ikon maskable, halaman offline sederhana yang menjelaskan koneksi terputus. Jangan menyimpan data pribadi di cache service worker.

## 12. Daftar uji per halaman (Definition of Done UI)

- [ ] Rapi di 360×640, 390×844, 768×1024, 1280×800, 1440×900.
- [ ] Tidak ada gulir horizontal pada halaman.
- [ ] Mode terang dan gelap sama-sama terbaca.
- [ ] Keadaan memuat, kosong, galat sudah ada.
- [ ] Bisa dioperasikan penuh dengan keyboard.
- [ ] Teks pada layar 200% zoom tidak terpotong.
- [ ] Skor Lighthouse Accessibility ≥ 95 dan Performance mobile ≥ 90.
- [ ] Tangkapan layar mobile dan desktop dilampirkan pada ringkasan pekerjaan agent (Antigravity artifact/screenshot).
