    **1. Landing Website (Public)**

Ini adalah website yang dilihat semua orang.

**Menu**

- Home
- Tentang Anita MUA
- Paket Make Up
- Pricelist
- Galeri
- Testimoni
- FAQ
- Kontak
- Booking Sekarang
**2. Booking System**

Flow

Client

## ↓

Pilih Paket

## ↓

Isi Form Booking

## ↓

Transfer DP 10%

## ↓


Admin Verifikasi

## ↓

Status

BOOKED

## ↓

Client mendapat akun Portal CRM

**Form Booking**

- Nama
- No WA
- Email
- Tanggal Acara
- Lokasi
- Paket
- Catatan
**3. Pembayaran**

Flow pembayaran

DP 10%

## ↓

DP 25% (Saat Fitting)

## ↓

## DP 75%

## (H-7)


## ↓

Pelunasan

(H-1 / H-2)

## ↓

Selesai

Status pembayaran akan berubah otomatis setelah admin melakukan konfirmasi.

**Jika Booking Dibatalkan**

Status

Cancelled

Keterangan

DP Hangus

Tetap tersimpan di database sebagai arsip.

**4. Dashboard Client**

Setelah DP diverifikasi.

Client mendapat akun login.

Di dalam dashboard terdapat

**Progress Acara**

✔ Booking

## ✔ DP

Vendor

Survey


Fitting

Pelunasan

Hari H

Selesai

**Paket Saya**

Misalnya

Paket Diamond

Harga

Tanggal Acara

Lokasi

PIC

**Vendor Saya**

Photographer

Studio A

Decoration

Dekor Indah

## MC

Fajar

Entertainment

Band XYZ

**Jadwal**

Survey


Fitting

Hari H

Pelunasan

**Pembayaran**

DP 10%

## DP 25%

## DP 75%

Pelunasan

Semua muncul statusnya.

**5. Paket**

Owner bisa membuat paket.

Misalnya

Silver

Gold

Diamond

Luxury

Setiap paket memiliki

- Nama
- Harga
- Deskripsi


- Vendor bawaan
- Benefit
**6. Master Vendor**

Menu khusus Owner/Admin.

Data

- Nama Vendor
- Kategori
- No HP
- Instagram
- Alamat
- Harga Kerjasama
- Status
- Catatan

Kategori

- Photographer
- Videographer
- Decoration
- MC
- Catering
- Entertainment
- WO
- dll

Vendor ini nanti tinggal dipilih saat membuat paket.

**7. Pricelist**

Client melihat

Paket Gold

Harga


Benefit

Vendor yang didapat

Vendor otomatis berasal dari Master Vendor.

**8. Inventory Wardrobe**

Menurutku ini akan menjadi fitur paling membantu.

Semua barang diberi data.

Contoh

Gaun Gold

Mahkota A

Veil Premium

Hijab Satin

Sepatu Gold

Bros

Kalung

Anting

Sarung Tangan

Dll

Setiap barang memiliki

- Kode
- Nama


- Kategori
- Kondisi
- Status
**9. Packing Checklist**

Sebelum fitting.

Admin klik

Packing

Checklist

☑ Gaun

☑ Crown

☑ Veil

☑ Hijab

☑ Bros

☑ Sepatu

☑ Anting

Sesudah fitting

Checklist ulang

☑ Gaun

☑ Crown

☑ Veil


Anting

Sistem langsung mendeteksi

Barang Belum Kembali

**10. Survey**

Isi

- Lokasi
- Maps
- PIC
- Catatan
- Foto
- Video

Semua tersimpan.

**11. Fitting**

Data

Tanggal

Jam

Client

PIC

Catatan

Checklist Barang

Upload Foto

Status

Scheduled

On Going

Finished


**12. Kalender**

Semua jadwal muncul.

Misalnya

Hari ini

2 Fitting

1 Survey

3 Wedding

Owner tinggal lihat kalender.

**13. Reminder Otomatis**

CRM otomatis mengingatkan

Client

Admin

Owner

Misalnya

H- 30

Reminder Fitting

H- 7

Reminder DP 75%

H- 2

Reminder Pelunasan

H- 1

Reminder Hari H

Menurutku kalau nanti terhubung ke WhatsApp API, ini akan menjadi nilai jual yang besar.


**14. Keuangan**

Dashboard

Pendapatan

Pengeluaran

Profit

Margin

Cashflow

Per Project

Misalnya

Project

Andi & Siska

Pendapatan

20 juta

Pengeluaran

9 juta

Profit

11 juta

Owner langsung tahu laba tiap acara.

**15. Dashboard Owner**

Widget

Booking Baru


Client Aktif

Acara Minggu Ini

DP Masuk

Pelunasan

Pendapatan

Profit

Vendor Aktif

Barang Belum Kembali

Reminder Hari Ini

Semua bisa dilihat dalam satu halaman.

**16. Manajemen User**

Role yang menurutku cukup

**Owner**

Semua akses.

**Admin**

Booking

Pembayaran

Vendor

Paket

Inventory

Jadwal


**Tim Lapangan**

Checklist

Packing

Survey

Fitting

Upload Foto

**Client**

Melihat progress

Vendor

Pembayaran

Jadwal

**17. Aturan Bisnis (Business Rules)**

Ini penting untuk coding nanti agar alurnya jelas:

- Booking dianggap sah setelah DP 10% diverifikasi admin.
- Jika client membatalkan booking secara sepihak, status berubah menjadi **Cancelled** dan DP
    dinyatakan **hangus**.
- Client boleh mengajukan perubahan paket, tetapi perubahan **tidak langsung berlaku**. Admin
    atau owner harus menyetujui dan memperbarui data paket di sistem.
- Vendor yang tampil di halaman pricelist dan dashboard client berasal dari **Master Vendor** ,
    bukan diketik manual.
- Jadwal fitting, survey, dan Hari H hanya dapat diubah oleh admin/owner.
- Semua perubahan penting (paket, jadwal, pembayaran, vendor) dicatat dalam **Activity Log**
    agar riwayat perubahan bisa ditelusuri.

**Roadmap Pengerjaan (Urutan Coding)**

Menurutku urutan ini paling aman dan minim revisi.

**Phase 1 — UI/UX Design (JPG/Figma)**

- Landing Page
- Login


- Dashboard Owner
- Dashboard Admin
- Dashboard Client
- Dashboard Tim Lapangan
- Halaman Paket
- Halaman Vendor
- Inventory
- Keuangan
- Booking
- Calendar
- Jadwal Fitting
- Survey
- Checklist Barang

**Kirim ke client untuk approval terlebih dahulu.**

**Phase 2 — Database**

- ERD (Entity Relationship Diagram)
- Struktur tabel
- Relasi antar modul

**Phase 3 — Backend**

- Autentikasi & Role
- CRUD Master Data
- Booking
- Pembayaran
- Vendor
- Inventory
- Jadwal
- Reminder
- Keuangan


**Phase 4 — Frontend**

- Implementasi desain menjadi antarmuka
- Dashboard
- Form
- Kalender
- Tabel
- Laporan

**Phase 5 — Testing**

- Uji semua alur bisnis
- Uji role pengguna
- Uji perhitungan keuangan
- Uji checklist inventaris
- Uji perubahan paket dan pembatalan booking

**Saran Tambahan**

Kalau aku yang mengembangkan proyek ini, aku akan menambahkan satu fitur lagi yang sering
terlupakan tetapi sangat berguna: **Activity Timeline** pada setiap project client.

Contohnya:

08 Jul 2026 09:

Booking dibuat oleh Admin

08 Jul 2026 09:

DP 10% dikonfirmasi

09 Jul 2026 10:

Paket diubah dari Gold ke Diamond oleh Owner

12 Jul 2026 13:

Vendor Photographer diganti menjadi Studio A

18 Jul 2026 15:


Fitting selesai

25 Jul 2026 11:

Pelunasan diterima

Saat ada pertanyaan seperti _"Siapa yang mengganti paket?"_ , _"Kapan vendor diubah?"_ , atau _"Kenapa
status berubah?"_ , owner tinggal membuka timeline tanpa perlu mencari di WhatsApp atau bertanya
ke admin. Menurutku fitur ini akan membuat CRM terasa jauh lebih profesional dan sangat
membantu ketika jumlah project sudah puluhan atau ratusan.


