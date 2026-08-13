# PLANNING MVP — Anita MUA (Booking-First)

Strategi: rilis **MVP berfokus booking** dulu. Fitur non-booking (keuangan, ubah paket, inventory,
packing, reminder, dsb.) **ditunda** ke fase berikutnya. Tujuan: sistem usable cepat, alur
utama (booking → DP → verifikasi → BOOKED → acara) jalan end-to-end tanpa kompleksitas.

---

## 1. Alur Utama MVP (booking flow)

```
Client pilih paket & isi form booking
  → Booking status PENDING
Admin verifikasi DP 10% (cek bukti transfer)
  → Booking status BOOKED
  → Jadwal hari H otomatis dibuat (dari event_date)
Client pantau status & upload bukti pembayaran selanjutnya
Admin verifikasi pembayaran (dp25 / dp75 / pelunasan)
  → Selesai, admin tandai COMPLETED setelah acara
```

## 2. Definisi Fitur MVP vs Ditunda

| Modul | MVP (fase 1) | Ditunda (fase 2+) |
|---|---|---|
| Booking | Buat, list, detail, verifikasi DP, cancel, complete | Ubah paket (`package_change_requests`) |
| Pembayaran | 4 tahap: dp10, dp25, dp75, pelunasan; upload & verifikasi bukti | — |
| Paket | CRUD sederhana + benefit + harga (dipakai form booking) | Benefit/format detail, gambar paket |
| Vendor | (opsional) dropdown sederhana saat booking | Master vendor CRUD, `package_vendor`, sinkronisasi vendor |
| Jadwal | Hari H dari `event_date` + kalender sederhana | Survey, fitting, `schedules` manual per jenis |
| Client | Status booking, pembayaran, upload bukti | Progress 8 tahap, vendor saya, dokumentasi |
| Owner | Ringkasan pipeline & uang masuk (dari payments) | Finance ledger, profit, chart bulanan |
| Admin | Statistik ringkas + daftar aksi (verifikasi) | Inventory, packing, reminder, users, timeline |
| Team | Daftar acara hari H mendatang | Survey/fitting field work, packing checklist |
| Landing | Home, Paket, Pricelist, Booking, Kontak | Galeri, testimoni, FAQ (boleh dibiarkan) |

## 3. Dashboard per Role (MVP)

### 3.1 Client
**Sidebar MVP:**
- Dashboard
- Booking Saya (status & detail)
- Pembayaran (upload bukti)

**Widget Dashboard:**
1. Kartu status booking terakhir (pending → booked → completed)
2. Ringkasan pembayaran: total paket, sudah dibayar, sisa (dari payments verified)
3. Info acara: tanggal, lokasi, paket
4. Tombol aksi: booking baru / upload bukti DP

**Sembunyikan:** progress timeline 9 langkah, vendor saya, reminder center, dokumentasi.

### 3.2 Admin
**Sidebar MVP:**
- Dashboard
- Booking (list + detail + verifikasi DP + cancel + complete)
- Pembayaran (list + verifikasi)
- Paket (CRUD)
- Jadwal (hari H)

**Widget Dashboard:**
1. Booking menunggu verifikasi (list + tombol verifikasi langsung)
2. Pembayaran menunggu verifikasi (list + tombol verifikasi)
3. Acara hari ini & minggu ini

**Sembunyikan:** kalender penuh, keuangan, inventory, packing, reminder, activity log,
manajemen user, master vendor.

### 3.3 Owner
**Sidebar MVP:** Dashboard, Booking, Pembayaran, Paket, Jadwal.

**Widget Dashboard:**
1. Pipeline: booking pending / booked / completed / cancelled (hitung)
2. Uang masuk: total dp10 verified, dp25, dp75, pelunasan (dari tabel `payments`, bukan `finances`)
3. Daftar acara mendatang (client, tanggal, paket, kode)

**Hapus (ditunda):** widget revenue/expense/profit, chart bulanan, vendor aktif,
barang belum kembali, reminder hari ini.

### 3.4 Tim Lapangan
**Sidebar MVP:** Dashboard, Jadwal Saya.

**Widget Dashboard:**
1. Daftar acara hari H mendatang (tanggal, client, lokasi, paket)
2. Tandai selesai saat acara beres

**Sembunyikan:** packing checklist, survey, fitting, upload foto, progress acara.

## 4. Perubahan Teknis

1. **`routes/web.php`** — hapus/komentari route fase 2+ di tiap role; sisakan route booking flow.
2. **`layouts/app.blade.php`** — pangkas menu sidebar sesuai tabel di atas.
3. **`DashboardController`** — tiap method role hanya query data booking & payments;
   hapus query `Finance`, `Vendor`, `PackingItem`, `Reminder`.
4. **View dashboard** — pangkas widget non-MVP (chart, stat keuangan, dsb).
5. **Seeder** — cukup seed users (4 role), packages + benefits, 1–2 booking contoh beserta
   payment dp10 untuk demo alur. Data vendor/inventory/finance dihapus dari seed MVP.
6. **Route dihapus → controller terkait bisa tetap ada** (tidak dihapus file), supaya
   mudah dikembalikan di fase 2.

## 5. Kriteria Selesai MVP

- [ ] Client bisa booking dari landing page dan dashboard.
- [ ] Admin verifikasi DP 10% → status berubah `BOOKED`, jadwal hari H terbentuk.
- [ ] Client bisa upload bukti pembayaran tahap berikutnya; admin verifikasi.
- [ ] Owner melihat pipeline booking & total uang masuk tanpa modul keuangan.
- [ ] Tim melihat acara hari H mendatang.
- [ ] Semua menu fase 2+ tidak terlihat di sidebar dan tidak bisa diakses via URL.
- [ ] `php artisan test` tetap hijau (sesuaikan test yang merujuk fitur ditunda).

## 6. Fase 2+ (menyusul, urutan usulan)

1. Keuangan (ledger income/expense, profit, chart)
2. Ubah paket dengan approval (`package_change_requests`)
3. Inventory wardrobe + packing checklist
4. Survey & fitting (field work)
5. Reminder otomatis & activity timeline
6. Master vendor + sinkronisasi vendor ke booking
7. Manajemen user & galeri/testimoni/FAQ
