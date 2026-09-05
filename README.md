# HJAR Sysinfo

HJAR Sysinfo adalah sistem informasi internal untuk mengelola **data pembanding properti**, guna mendukung proses penilaian dan analisis pasar.

## Apa yang aplikasi ini lakukan

- Menyimpan dan mengelola data pembanding properti dalam satu sistem.
- Membantu tim meninjau, memperbarui, dan melacak perubahan data pembanding.
- Menyediakan referensi master data dan wilayah agar input data tetap konsisten.
- Menyajikan dashboard dan laporan untuk memantau kualitas data serta aktivitas pengguna.

## Siapa yang menggunakan

- Tim internal properti/penilaian.
- Admin dan staf input data.
- Supervisor atau analis untuk review data dan laporan.

## Aplikasi web

Seluruh user menggunakan application shell yang sama di `/app`, dibangun dengan Vue 3 dan Inertia.js. Sidebar, halaman, dan action ditampilkan berdasarkan permission masing-masing user.

Tidak ada panel terpisah untuk `super_admin`. Role tersebut menggunakan aplikasi yang sama dengan permission paling lengkap. Authorization tetap diterapkan pada route, policy, request, dan scope data; penyembunyian menu bukan pengganti keamanan backend.

## Setup pengembangan

```bash
composer install
npm install
php artisan migrate
npm run build
```

Untuk menjalankan aset secara development:

```bash
npm run dev
```

## Modul `/app`

- Dashboard
- User management
- Access control untuk role dan permission
- Data pembanding dan Bulk Import
- Moderation desk
- Master data
- Geo data
- Export data
- System backup
- Global search
- Profile & password

Fitur backup database membutuhkan binary `mysqldump` di environment server. Backup uploads membutuhkan ekstensi PHP `ZipArchive`.

## Akses dokumentasi dengan PIN

Scramble membatasi `/docs/api` dan `/docs/api.json` di production secara default (403). Untuk akses sementara, isi `.env` server:

```dotenv
API_DOCS_PIN=
API_DOCS_SESSION_MINUTES=60
SESSION_SECURE_COOKIE=true
```

Isi `API_DOCS_PIN` dengan PIN acak 8–12 digit (jangan gunakan contoh publik atau commit PIN). Jalankan `php artisan config:cache` setelah perubahan, kemudian buka `/docs/api` lewat HTTPS. Pastikan session dan cache persisten berfungsi; untuk beberapa instance gunakan penyimpanan bersama. Konfigurasi trusted proxy harus sesuai reverse proxy deployment agar batas per IP akurat.

PIN hanya membuka dokumentasi, bukan autentikasi endpoint API. Maksimal 5 percobaan per IP dan 100 secara global per 15 menit. Sesi docs berlaku 60 menit tanpa perpanjangan otomatis; mengganti PIN membatalkan sesi docs lama. Buka `/docs/login` lalu pilih **Kunci akses dokumentasi** untuk keluar. Kosongkan PIN dan jalankan `php artisan config:cache` untuk kembali ke kebijakan akses bawaan. PIN yang tidak memenuhi format menolak akses.
