# Anita MUA — CRM & Booking System

Sistem manajemen bisnis Make Up Artist lengkap: landing page publik, booking dengan DP bertahap,
portal client, dashboard Owner/Admin/Tim Lapangan, master vendor, inventory wardrobe,
packing checklist, keuangan, kalender, reminder otomatis, dan activity timeline.

## Fitur (sesuai PRD)

| Modul | Deskripsi |
|---|---|
| **Landing Website** | Home, Tentang, Paket, Pricelist, Galeri, Testimoni, FAQ, Kontak, Booking |
| **Booking System** | Pilih paket → form booking → DP 10% → verifikasi admin → status BOOKED |
| **Pembayaran** | DP 10% (booking), DP 25% (fitting), DP 75% (H-7), Pelunasan (H-1/H-2). Cancel = DP hangus |
| **Dashboard Client** | Progress acara 8 tahap, paket saya, vendor saya, jadwal, pembayaran, upload bukti transfer |
| **Paket** | CRUD paket (Silver/Gold/Diamond/Luxury) + benefit + vendor bawaan dari Master Vendor |
| **Master Vendor** | Kategori (Photographer, Videographer, Decoration, MC, Catering, Entertainment, WO, dll) |
| **Inventory Wardrobe** | Barang dengan kode otomatis, kategori, kondisi, status |
| **Packing Checklist** | Checklist sebelum/sesudah fitting + deteksi otomatis barang belum kembali |
| **Survey & Fitting** | Lokasi, maps, PIC, catatan, foto, status (scheduled/on_going/finished) |
| **Kalender** | Semua jadwal (survey, fitting, hari H) dalam satu tampilan bulanan |
| **Reminder Otomatis** | Command `reminders:generate` (H-30, H-7, H-2, H-1) + reminder manual |
| **Keuangan** | Pendapatan, pengeluaran, profit, margin, cashflow bulanan, profit per project |
| **Dashboard Owner** | 11 widget: booking baru, DP masuk, pelunasan, barang belum kembali, dll |
| **Activity Timeline** | Semua perubahan tercatat (siapa, kapan, apa) per project |
| **Manajemen User** | Role: Owner, Admin, Tim Lapangan, Client |

## Arsitektur Database (23 tabel)

```
users ──────────────► bookings ─────► package_change_requests
                        │
                        ├──► payments            (dp10, dp25, dp75, pelunasan)
                        ├──► booking_vendors ───► vendors ──► vendor_categories
                        ├──► schedules           (survey, fitting, hari_h)
                        ├──► surveys
                        ├──► fittings
                        ├──► packing_lists ───► packing_items ──► inventory_items ──► inventory_categories
                        ├──► reminders
                        ├──► finances
                        ├──► activity_logs
                        └──► testimonials

packages ──► package_benefits
packages ──► package_vendor (pivot ke vendors)

gallery, faqs, site_settings
```

## Instalasi

```bash
composer install
cp .env.example .env        # set DB_DATABASE=database MySQL
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

Akses: `http://localhost:8000`

### Akun demo

| Role | Email | Password |
|---|---|---|
| Owner | `owner@anitamua.com` | `password` |
| Admin | `admin@anitamua.com` | `password` |
| Tim Lapangan | `team@anitamua.com` | `password` |
| Client | `client@anitamua.com` | `password` |

## Matriks Akses per Role

| Modul | Owner | Admin | Tim Lapangan | Client |
|---|---|---|---|---|
| **Dashboard** | Dashboard Owner | Dashboard Admin | Dashboard Tim | Dashboard Client |
| **Booking** | Kelola penuh | Kelola penuh | Lihat saja | Lihat progress |
| **Pembayaran** | Verifikasi & konfirmasi | Verifikasi & konfirmasi | – | Upload bukti + lihat status |
| **Paket** | CRUD | CRUD | – | Lihat pricelist |
| **Master Vendor** | CRUD | CRUD | – | Lihat vendor saya |
| **Inventory Wardrobe** | CRUD | CRUD | – | – |
| **Jadwal / Kalender** | Buat/ubah/hapus | Buat/ubah/hapus | Ubah status saja | Lihat jadwal |
| **Packing Checklist** | Kelola | Kelola | Jalankan checklist | – |
| **Survey** | Kelola | Kelola | Isi data lapangan | Lihat progress |
| **Fitting** | Kelola | Kelola | Isi data lapangan | Lihat progress |
| **Reminder** | Kelola & terima | Kelola & terima | – | Terima |
| **Keuangan** | Full | – | – | – |
| **Activity Timeline** | Lihat | Lihat | – | – |
| **Manajemen User** | Full | – | – | – |
| **Landing Website** | Kelola konten | – | – | Akses publik |

## Aturan Bisnis (sudah diterapkan)

1. Booking sah setelah DP 10% diverifikasi admin → status `BOOKED`.
2. Pembatalan sepihak client → status `Cancelled`, DP hangus, tetap tersimpan sebagai arsip.
3. Perubahan paket butuh persetujuan admin/owner (via `package_change_requests`).
4. Vendor di pricelist & dashboard client selalu bersumber dari Master Vendor (disinkronkan saat paket diganti).
5. Jadwal hanya bisa diubah admin/owner (Tim Lapangan hanya ubah status).
6. Semua perubahan penting tercatat di `activity_logs` (Activity Timeline).

## Reminder Otomatis

```bash
php artisan reminders:generate     # buat reminder H-30/H-7/H-2/H-1 untuk semua booking aktif
php artisan schedule:work          # jalankan scheduler (otomatis tiap 06:00)
```

## Testing

```bash
php artisan test
```

18 test mencakup: landing pages, autentikasi, role guard, alur DP → BOOKED, pembatalan,
perubahan paket + sinkronisasi vendor, packing checklist + deteksi barang hilang,
render semua halaman back office, dan command reminder.

## Struktur Utama

```
app/Models/          24 model + relasi
app/Helpers/         BookingProgress (perhitungan progress acara)
app/Services/        ActivityLogger
app/Http/Controllers  Landing, Booking, Auth, Dashboard, Client,
                      Admin/* (booking, payment, vendor, package, inventory,
                               schedule, fieldwork, packing, finance, users, timeline, reminder)
database/migrations/ 23 migration
database/seeders/    Data demo lengkap
resources/views/     Landing (10 halaman), Dashboard (4 role), Admin (13 halaman)
tests/Feature/       SmokeTest (16 test alur bisnis)
```
