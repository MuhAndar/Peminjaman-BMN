---
trigger: model_decision
description: Terapkan setiap kali membuat atau mengubah halaman, komponen, gaya, atau tata letak antarmuka.
---

# Aturan UI/UX

Spesifikasi lengkap: `docs/UI_UX_SPEC.md`. Ringkasnya:

1. **Mobile-first.** Tulis gaya dasar untuk 360 px, baru tambah `sm/md/lg/xl`. Tidak boleh ada gulir horizontal pada halaman. Tabel menjadi kartu di bawah 768 px.
2. **Pakai token, bukan nilai mentah.** Warna, radius, bayangan, jarak diambil dari `src/styles/tokens.css`/konfigurasi Tailwind. Jangan menulis hex di komponen.
3. **Komponen dasar dari shadcn/ui**, dipoles dengan token proyek. Ikon dari `lucide-react`. Jangan memakai emoji sebagai ikon fungsional.
4. **Status** selalu lewat `<StatusBadge />` yang membaca `lib/status-meta.ts` (label Indonesia + warna + ikon). Jangan membuat badge status ad-hoc.
5. **Sentuh dan ketik**: target ≥ 44×44 px, input font ≥ 16 px, tombol aksi utama di zona jempol (bar bawah lengket) pada mobile.
6. **Selalu sediakan tiga keadaan**: memuat (skeleton), kosong (dengan tindakan), galat (dengan "Coba lagi").
7. **Teks antarmuka berbahasa Indonesia formal tapi ramah**; kalimat aktif, kata kerja jelas ("Kirim pengajuan", "Setujui", "Kembalikan untuk diperbaiki"). Format `11 Januari 2026` dan `Rp 12.646.700` lewat `format.ts`.
8. **Aksesibilitas**: label pada semua kontrol, fokus terlihat, kontras ≥ 4,5:1, `aria-live` untuk perubahan status, hormati `prefers-reduced-motion`.
9. **Performa**: Server Component secara default; `use client` hanya untuk interaksi; gambar lewat `next/image`; tidak memuat pustaka berat di klien tanpa alasan.
10. **Sebelum membangun layar baru**, gunakan skill desain yang terpasang (`frontend-design`, `ui-ux-pro-max`, `web-design-guidelines`) untuk menentukan arah visual, lalu konsisten dengan hasil yang sudah dikunci di token.
11. **Verifikasi visual**: setelah selesai, buka halaman di browser agent pada lebar 390 dan 1280 px, ambil tangkapan layar, dan periksa daftar uji di `UI_UX_SPEC.md` bagian 12.
