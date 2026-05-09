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

## Panel admin

Panel admin utama hasil migrasi tersedia di `/admin` dan dibangun dengan Vue 3 + Inertia.js. Semua route admin berada di dalam middleware `auth`, `app.user`, dan `role:super_admin`.

Panel admin lama sudah dilepas dari runtime dan dependency Composer. Route `/admin` sekarang sepenuhnya milik panel Vue/Inertia.

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

## Modul `/admin`

- Dashboard
- User management
- Access control untuk role dan permission
- Data pembanding CRUD
- Moderation desk
- Master data
- Geo data
- Export data
- System backup
- Global search
- Profile & password

Fitur backup database membutuhkan binary `mysqldump` di environment server. Backup uploads membutuhkan ekstensi PHP `ZipArchive`.
