# Model Data, Mapping Excel, dan Pembersihan Data

Sumber: `Entry_Form_Peminjaman_docx.xlsx` (tidak disertakan di kit karena memuat data pribadi). Hasil profil file tersebut:

| Temuan | Angka |
|---|---|
| Sheet1 "log peminjaman" | 42 baris (Des 2025 – Jul 2026), 19 kolom |
| Peminjam unik | 30 orang |
| Barang unik (kode+NUP) | 35 dari 38 baris ber-NUP; 3 barang dipinjam lebih dari sekali |
| Baris kendaraan tanpa kode/NUP (kode 0, NUP 0) | 4 baris, 2 kendaraan (dikenali dari nomor polisi) |
| Pola periode | 36 baris berdurasi ≥ 300 hari (penugasan tahunan 1 Jan–31 Des), 5 baris 2–4 hari (mobil dinas) |
| Tgl Surat vs Tgl Peminjaman | sama pada 39 baris, H-1 pada 3 baris |
| Pengajuan potensial bila digabung per (NIP, tgl surat, periode) | 39 surat, 3 di antaranya memuat >1 barang |
| Sheet2 "data pegawai" | 48 pegawai (PNS 27, PPPK 10, CPNS 10, PPNPN 1), 28 kolom |
| Kolom sensitif di Sheet2 | NIP/NIK, nomor WA (47), email (48), tanggal lahir, alamat rumah |
| Alamat rumah kosong | 17 dari 48 pegawai |
| NIP bukan 18 digit | 1 (PPNPN: berisi nomor 16 digit yang berupa NIK, bukan NIP) |

Semua 30 peminjam ada di Sheet2 dan pangkat/jabatan/alamatnya konsisten antar sheet.

## 1. Skema (PostgreSQL)

Nama tabel dan kolom dalam bahasa Inggris. Semua `timestamptz`; tanggal murni memakai `date`. Tampilkan dalam `Asia/Makassar`.

```sql
-- Master
CREATE TABLE employees (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  nip varchar(18) UNIQUE,                 -- NULL untuk PPNPN; JANGAN simpan NIK di sini
  full_name text NOT NULL,                -- lengkap dengan gelar, dipakai di dokumen
  rank_grade text,                        -- "Penata Tk. I, III/d"
  position text,                          -- jabatan
  home_address text,                      -- boleh kosong; peminjam melengkapi saat mengajukan
  employment_status text NOT NULL,        -- PNS | CPNS | PPPK | PPNPN
  email text UNIQUE,                      -- hanya jika dipakai untuk login/notifikasi
  phone text,                             -- hanya jika notifikasi WA dipakai
  is_active boolean NOT NULL DEFAULT true,
  created_at timestamptz NOT NULL DEFAULT now(), updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE users (                      -- akun login (bisa dikelola oleh auth library)
  id uuid PRIMARY KEY, employee_id uuid UNIQUE REFERENCES employees(id),
  email text UNIQUE NOT NULL, is_active boolean NOT NULL DEFAULT true
);
CREATE TABLE user_roles (user_id uuid REFERENCES users(id), role text NOT NULL, PRIMARY KEY (user_id, role));
-- role: PEMINJAM | PENGELOLA_BMN | KETUA_TIM_ASET | ADMIN | PIMPINAN

CREATE TABLE asset_categories (
  code text PRIMARY KEY,                  -- LAPTOP | MOBILE_PHONE | CAR | MOTORCYCLE
  label text NOT NULL,                    -- "Laptop", "Telepon Seluler", "Mobil Dinas", "Sepeda Motor"
  default_loan_type text NOT NULL,        -- ANNUAL | SHORT_TERM
  requires_schedule_check boolean NOT NULL DEFAULT false   -- true untuk CAR
);

CREATE TABLE assets (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  category_code text NOT NULL REFERENCES asset_categories(code),
  item_name text NOT NULL,                -- "Lap Top", "Mobil Dinas" (sesuai kolom Nama Barang di dokumen)
  brand text,                             -- Merk (ter-normalisasi)
  model text,                             -- Type
  bmn_code varchar(10),                   -- Kode Barang; NULL jika belum terdaftar
  nup integer,                            -- NUP; NULL jika belum terdaftar
  plate_number text,                      -- hanya kendaraan, mis. "DD 1820 XAS"
  acquisition_year integer NOT NULL,      -- kolom Tahun
  acquisition_value numeric(16,0) NOT NULL, -- kolom Nilai (Rupiah)
  condition text NOT NULL DEFAULT 'BAIK', -- BAIK | RUSAK | TIDAK_LENGKAP_HILANG
  status text NOT NULL DEFAULT 'AVAILABLE', -- AVAILABLE | ON_LOAN | NEEDS_FOLLOW_UP | INACTIVE
  notes text,
  UNIQUE (bmn_code, nup),
  UNIQUE (plate_number),
  CHECK ((bmn_code IS NULL) = (nup IS NULL))
);

CREATE TABLE signatories (               -- penandatangan bisa berganti tanpa ubah kode
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  role text NOT NULL,                     -- KETUA_TIM_KEUANGAN_ASET | PENGELOLA_ASET
  employee_id uuid NOT NULL REFERENCES employees(id),
  title_line text,                        -- "Ketua Tim Keuangan dan Aset BBLSDM Makassar,"
  valid_from date NOT NULL, valid_to date
);

-- Transaksi
CREATE TABLE loan_requests (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  borrower_id uuid NOT NULL REFERENCES employees(id),
  loan_type text NOT NULL,                -- ANNUAL | SHORT_TERM
  start_date date NOT NULL, end_date date NOT NULL CHECK (end_date >= start_date),
  status text NOT NULL DEFAULT 'DRAFT',
  -- snapshot data diri saat diajukan (BR-07)
  snap_full_name text NOT NULL, snap_nip varchar(18), snap_rank_grade text,
  snap_position text, snap_home_address text,
  review_note text, decision_note text,
  is_legacy boolean NOT NULL DEFAULT false,   -- hasil impor Excel
  legacy_seq integer,                         -- kolom "No" di Excel
  created_at timestamptz NOT NULL DEFAULT now(), submitted_at timestamptz, updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE loan_items (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  loan_request_id uuid NOT NULL REFERENCES loan_requests(id) ON DELETE CASCADE,
  asset_id uuid NOT NULL REFERENCES assets(id),
  purpose text NOT NULL,                  -- kolom Keperluan
  condition_out text,                     -- kolom Kondisi di BAST Peminjaman
  condition_in text,                      -- Kondisi Saat Dikembalikan
  follow_up_note text,                    -- "Tindak lanjut yang diperlukan"
  -- snapshot data barang saat diajukan
  snap_item_name text NOT NULL, snap_brand_model text, snap_nup integer,
  snap_year integer, snap_value numeric(16,0),
  UNIQUE (loan_request_id, asset_id)
);

CREATE TABLE loan_status_history (
  id bigserial PRIMARY KEY, loan_request_id uuid NOT NULL REFERENCES loan_requests(id),
  from_status text, to_status text NOT NULL, actor_id uuid REFERENCES users(id),
  note text, at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE documents (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  loan_request_id uuid NOT NULL REFERENCES loan_requests(id),
  doc_type text NOT NULL,                 -- SURAT_PEMINJAMAN | BAST_PEMINJAMAN | BAST_PENGEMBALIAN
  doc_number text NOT NULL, doc_date date NOT NULL,
  status text NOT NULL DEFAULT 'ISSUED',  -- ISSUED | VOID
  generated_file_key text, signed_file_key text,
  issued_by uuid REFERENCES users(id), issued_at timestamptz NOT NULL DEFAULT now(),
  UNIQUE (doc_type, doc_number)
);

CREATE TABLE document_sequences (         -- penomoran atomik (BR-06)
  doc_type text NOT NULL, year integer NOT NULL, last_value integer NOT NULL DEFAULT 0,
  PRIMARY KEY (doc_type, year)
);
-- Ambil nomor berikutnya dengan: UPDATE ... SET last_value = last_value + 1 ... RETURNING last_value
-- di dalam transaksi yang sama dengan insert ke documents.

CREATE TABLE app_settings (key text PRIMARY KEY, value jsonb NOT NULL);  -- kop, alamat, format nomor, SLA
CREATE TABLE audit_logs (
  id bigserial PRIMARY KEY, actor_id uuid, action text NOT NULL, entity text NOT NULL,
  entity_id text, before jsonb, after jsonb, ip inet, at timestamptz NOT NULL DEFAULT now()
);
CREATE TABLE import_batches (id uuid PRIMARY KEY, source_name text, dry_run boolean, summary jsonb, created_at timestamptz DEFAULT now());
```

Pencegahan bentrok (BR-02): periode disimpan di tingkat barang supaya database sendiri yang menolak tumpang-tindih, bukan hanya kode aplikasi. Tambahkan kolom turunan pada `loan_items` (diisi saat pengajuan, dikosongkan bila dibatalkan/ditolak/dikembalikan):

```sql
CREATE EXTENSION IF NOT EXISTS btree_gist;
ALTER TABLE loan_items
  ADD COLUMN period daterange,            -- daterange(start_date, end_date, '[]')
  ADD COLUMN is_blocking boolean NOT NULL DEFAULT false;  -- true selama status memegang barang
ALTER TABLE loan_items
  ADD CONSTRAINT no_overlapping_loans
  EXCLUDE USING gist (asset_id WITH =, period WITH &&) WHERE (is_blocking);
```

Status yang memegang barang (`is_blocking = true`): SUBMITTED, UNDER_APPROVAL, APPROVED, BAST_ISSUED, ACTIVE, RETURN_REQUESTED, RETURN_CHECKED (prinsip siapa cepat dia dapat; NEEDS_REVISION, REJECTED, CANCELLED, RETURNED melepas barang). Cek juga di sisi aplikasi agar pesan kesalahan ramah.

## 2. Mapping kolom Excel Sheet1 → tabel

| Kolom Excel | Tujuan | Catatan |
|---|---|---|
| No | `loan_requests.legacy_seq` | Urutan entri per tahun (Des 2025 berakhir di 59–60, 2026 mulai dari 1) |
| Bln, Thn | — | Turunan dari tanggal; abaikan |
| Nama | `employees.full_name` | Dengan gelar; kunci pencocokan tetap NIP |
| NIP | `employees.nip` | 18 digit |
| Pangkat/Gol, Jabatan, Alamat Rumah | `snap_*` di `loan_requests` | Snapshot per surat |
| Nama Barang | `assets.item_name` | Pada kendaraan baru nomor polisi terselip di sini |
| Merk | `assets.brand` | Kadang berisi merk + model sekaligus |
| Type | `assets.model` | Pada Mobil Dinas lama berisi model + nomor polisi |
| Kode Barang | `assets.bmn_code` | `0` → `NULL` |
| NUP | `assets.nup` | `0` → `NULL` |
| Tahun | `assets.acquisition_year` | — |
| Nilai | `assets.acquisition_value` | — |
| Keperluan | `loan_items.purpose` | — |
| Tgl Peminjaman / Tgl Pengembalian | `loan_requests.start_date / end_date` | Sel bertipe tanggal |
| Tgl Surat | `documents.doc_date` (SURAT_PEMINJAMAN) | — |

Pemetaan kategori dari kode barang yang muncul di file:

| Kode Barang | `category_code` | `default_loan_type` |
|---|---|---|
| 3100102002 | LAPTOP | ANNUAL |
| 3060201004 | MOBILE_PHONE | ANNUAL |
| 3020104001, 3020104002 | MOTORCYCLE | ANNUAL |
| 3020102003 atau `0` dengan "Mobil" di nama | CAR | SHORT_TERM |

Sheet2 → `employees`: ambil hanya `Nama Lengkap`, `NIP`, `Pangkat, Gol/Ruang`, `Jabatan (SIMPATIK)` (fallback `Jabatan From Kepegawaian`), `Alamat Rumah`, `Status Kepegawaian`, `AKTIF`, dan `Email` (hanya bila login/notifikasi memakainya). **Jangan impor**: tanggal lahir, tahun masuk, nomor WA (kecuali notifikasi WA diputuskan), kolom tim kerja/minat.

## 3. Aturan pembersihan (dipakai skill `bmn-excel-import`)

1. Hapus spasi ganda dan spasi ujung; baris kosong dilewati.
2. Normalisasi merk lewat kamus (`TOYOTA`/`Toyota` → `Toyota`; `LENOVO`/`Lenovo` → `Lenovo`; `ASUS`/`Asus` → `ASUS`). Ada 16 variasi mentah → 14 setelah normalisasi.
3. Jika `Type` kosong dan `Merk` berisi lebih dari satu kata (contoh: merk + seri + ukuran layar), pecah: kata pertama = merk, sisanya = model. Simpan nilai asli di `import_batches.summary` untuk ditinjau.
4. Ekstrak nomor polisi dengan regex `\b[A-Z]{1,2}\s?\d{1,4}\s?[A-Z]{1,3}\b` dari `Nama Barang` dan `Type`; hapus dari teks asalnya; simpan di `plate_number`; `item_name` menjadi "Mobil Dinas".
5. `Kode Barang = 0` dan `NUP = 0` → `NULL`, tandai di laporan anomali "belum punya kode/NUP".
6. Kunci pegawai = NIP. Jika NIP bukan 18 digit (kasus PPNPN dengan 16 digit), **jangan simpan angka itu**; set `nip = NULL` dan tampilkan peringatan.
7. Gabungkan baris menjadi satu `loan_request` per kombinasi (NIP, Tgl Surat, Tgl Peminjaman, Tgl Pengembalian); tiap baris menjadi satu `loan_item`.
8. `loan_type`: `ANNUAL` bila durasi ≥ 300 hari, selain itu `SHORT_TERM`.
9. Status riwayat: `ACTIVE` bila `end_date ≥ hari ini (WITA)`; selain itu `RETURNED` dengan `is_legacy = true` (Excel tidak mencatat pengembalian, jadi tandai "pengembalian tidak terdokumentasi").
10. Mode **dry-run secara default**: tampilkan ringkasan (jumlah dibuat/diperbarui/dilewati) dan laporan anomali; simpan hanya jika dijalankan dengan `--apply`.
11. Idempoten: pengulangan impor memperbarui berdasarkan kunci alami (`nip`, `bmn_code+nup`, `plate_number`) dan tidak menduplikasi.
12. Jangan pernah mencetak baris data asli ke konsol/log; cetak hitungan dan nomor baris saja.

## 4. Format tampilan data

- NIP di dokumen: `19820608 200901 2 001` (8-6-1-3 digit). Simpan polos 18 digit.
- Nilai: `Rp 12.646.700` (tanpa desimal).
- Tanggal: `11 Januari 2026`; di tabel padat boleh `11/01/2026`.
- Kolom rekap ekspor Excel mengikuti urutan Sheet1 asli supaya tetap kompatibel dengan kebiasaan petugas.
