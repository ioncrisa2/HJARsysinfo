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
