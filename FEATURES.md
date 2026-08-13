# Status Fitur — Anita MUA

Legend: **✅ Aktif** (sudah terhubung route & bisa diakses) | **🟡 Kode siap, belum diaktifkan** (model + controller + view ada, belum ada route/menu) | **🔴 Belum ada**

## ✅ Fitur Sudah Aktif

| Modul | Keterangan |
|---|---|
| Landing Website | Home, Tentang, Paket, Pricelist, Galeri, Testimoni, FAQ, Kontak, Booking |
| Autentikasi & Role | Login/logout, middleware `role`, dashboard per role (Owner/Admin/Tim/Client) |
| Booking | Form booking (nama, WA, email, tanggal acara, lokasi, paket, catatan) → sukses page |
| Verifikasi DP 10% | Admin verifikasi → status `BOOKED` + akun client dibuat otomatis |
| Pembatalan | Admin cancel → status `Cancelled`, DP hangus, tersimpan sebagai arsip |
| Pembayaran | List payment, verifikasi DP 10/25/75, pelunasan, update manual |
| Paket | CRUD paket + pilih **kategori benefit** yang dipakai + centang benefit dari Master Benefit |
| Master Benefit | CRUD benefit + kategori (master data, dipakai ulang di banyak paket, tidak diketik manual) |
| Kalender/Jadwal | Tampilan bulanan (survey/fitting/hari H), tambah jadwal, ubah status, hapus; Hari H finished → project `COMPLETED` |
| Portal Client | Detail booking: progress 8 tahap, paket, jadwal, pembayaran, upload bukti transfer |
| Inventory Wardrobe | CRUD barang + foto (klik untuk perbesar) + kode otomatis, filter kategori/status/kondisi |
| Kategori Inventory | CRUD kategori inventory + cegah hapus yang masih dipakai |
| Survey | Form + tampilan data survey (lokasi, maps, PIC, catatan, foto/video) di detail booking, 1 per booking |
| Fitting | Form + daftar sesi fitting (tanggal, jam, PIC, foto, status) di detail booking, bisa lebih dari 1 sesi |
| Packing Checklist | Halaman per booking (`admin.bookings.packing`): checklist sebelum/sesudah fitting + deteksi barang belum kembali |
| Reminder Otomatis | Command `reminders:generate` (H-30/H-7/H-2/H-1) + scheduler 06:00 |
| Kelola Konten Landing | CRUD Testimoni, Galeri, FAQ (drag & drop SortableJS) + pengaturan situs (kontak, bank, sosial media) |
| Activity Log (backend) | Semua aksi penting tercatat via `ActivityLogger` (siapa, kapan, apa) |
| Testing | 22 test (SmokeTest): landing, auth, role guard, alur DP→BOOKED, cancel, ubah paket, packing, render semua halaman, command reminder |

## 🟡 Kode Siap, Belum Diaktifkan

Model, migration, controller, dan view sudah dibuat, tapi **belum ada route** sehingga belum bisa diakses dari menu.

| Modul | Yang sudah ada | Kurang |
|---|---|---|
| Master Vendor & Kategori Vendor | `VendorController`, `VendorCategoryController`, model, view | Route + menu (dinonaktifkan — modul vendor tidak dipakai) |
| Keuangan | `FinanceController`, model, view | Route + menu (dinonaktifkan sementara) |
| Manajemen User | `AdminController@users` (CRUD + toggle aktif), view | Route + menu (dinonaktifkan sementara) |
| Reminder Manual | `AdminController@reminders` + view | Route + menu (dinonaktifkan sementara) |
| Activity Timeline | `AdminController@timeline` + view | Route + menu (dinonaktifkan sementara) |
| Ubah Paket (Client) | Model `PackageChangeRequest` + migration | Route client + approval admin/owner + view |

## 🔴 Belum Ada Sama Sekali

| Modul | Keterangan |
|---|---|
| Approval Ubah Paket | Alur persetujuan admin/owner untuk `package_change_requests` |
| WhatsApp API | Integrasi reminder via WhatsApp (PRD: nilai jual besar) |

## Urutan Pengerjaan Disarankan

1. **Ubah Paket** — route `requestPackageChange` client (method sudah ada di `ClientController`) + controller approval `package_change_requests` di sisi admin/owner.
2. **WhatsApp API** — integrasi reminder (opsional, fase lanjut).
3. **Aktifkan modul yang dinonaktifkan** (Vendor, Keuangan, Users, Timeline, Reminder) bila sudah dibutuhkan.
