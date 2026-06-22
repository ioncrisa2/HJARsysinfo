# RBAC Permission Audit dan Perbaikan Akses

Dokumen ini mencatat arah perbaikan role based access control (RBAC) untuk aplikasi Bank Data, terutama admin panel dan fitur Pembanding. Tujuannya: perubahan akses ke depan cukup lewat role/permission, bukan dengan mengubah kode untuk setiap role baru.

## Prinsip Akses

- Role hanya paket permission.
- Permission menjadi sumber kebenaran akses.
- UI hanya mengikuti permission, bukan menjadi pengaman utama.
- Backend tetap wajib mengunci akses melalui route middleware, policy, Gate, atau controller guard.
- Action sensitif harus punya permission terpisah dari permission melihat halaman.

## Permission Pintu Masuk Admin

Permission utama untuk masuk admin panel:

```text
can_access_admin
```

User tanpa permission ini tidak boleh membuka route `/admin/*`, walaupun memiliki permission granular seperti `view_any_user`.

Catatan: permission lama `can_access_admin_panel` sudah dikonsolidasikan ke `can_access_admin` melalui `AdminPanelPermissionSeeder`. Jika permission legacy masih ada di database lama, assignment role/user dipindahkan ke `can_access_admin`, lalu permission legacy dihapus.

Permission lama `manage_master_data` juga dikonsolidasikan melalui `MasterDataPermissionSeeder`. Assignment lama dipindahkan ke permission granular `view_master_data`, `create_master_data`, `update_master_data`, `delete_master_data`, `reorder_master_data`, dan permission geo terkait, lalu permission legacy dihapus.

## Permission Admin Granular

Permission admin didefinisikan terpusat di:

```text
app/Support/AdminAccess.php
```

### Core Admin

| Permission | Fungsi |
| --- | --- |
| `can_access_admin` | Masuk area admin |
| `view_admin_dashboard` | Melihat dashboard admin |
| `view_access_control` | Melihat halaman Access Control |
| `view_admin_search` | Menggunakan global admin search |
| `view_activity_log` | Melihat activity logs |

### User Management

| Permission | Fungsi |
| --- | --- |
| `view_any_user` | Melihat daftar user |
| `view_user` | Melihat detail user jika dipakai |
| `create_user` | Membuat user |
| `update_user` | Mengubah user dan status user |
| `delete_user` | Menghapus satu user |
| `delete_any_user` | Bulk delete user |

### Role dan Permission Management

| Permission | Fungsi |
| --- | --- |
| `view_any_role` | Melihat daftar role |
| `view_role` | Melihat role |
| `create_role` | Membuat role |
| `update_role` | Mengubah role dan assignment permission |
| `delete_role` | Menghapus role |
| `view_any_permission` | Melihat daftar permission |
| `view_permission` | Melihat permission |
| `create_permission` | Membuat permission |
| `update_permission` | Mengubah permission jika nanti disediakan |
| `delete_permission` | Menghapus permission |

### Pembanding / Bank Data

| Permission | Fungsi |
| --- | --- |
| `view_map` | Melihat dashboard/peta sebaran data |
| `view_any_data::pembanding` | Melihat daftar Pembanding |
| `view_data::pembanding` | Melihat detail Pembanding |
| `create_data::pembanding` | Membuat Pembanding |
| `update_data::pembanding` | Mengubah semua Pembanding |
| `update_own_data::pembanding` | Mengubah Pembanding milik sendiri |
| `delete_data::pembanding` | Soft delete Pembanding |
| `delete_any_data::pembanding` | Bulk delete Pembanding jika nanti ada |
| `restore_data::pembanding` | Restore Pembanding |
| `force_delete_data::pembanding` | Hapus permanen Pembanding |
| `export_data::pembanding` | Download/export data Pembanding |

### Moderation

| Permission | Fungsi |
| --- | --- |
| `view_moderation` | Melihat moderation desk |
| `approve_delete_request` | Approve request hapus |
| `reject_delete_request` | Reject request hapus |
| `restore_data::pembanding` | Restore data dari trash |
| `force_delete_data::pembanding` | Force delete data dari trash |

### Master Data

| Permission | Fungsi |
| --- | --- |
| `view_master_data` | Melihat master data admin |
| `create_master_data` | Membuat master data |
| `update_master_data` | Mengubah master data |
| `update_master_data_status` | Toggle status aktif/nonaktif |
| `delete_master_data` | Menghapus satu master data |
| `delete_any_master_data` | Bulk delete master data |
| `reorder_master_data` | Mengubah urutan master data |

### Geo Data

| Permission | Fungsi |
| --- | --- |
| `view_geo_data` | Melihat data lokasi |
| `create_geo_data` | Membuat data lokasi |
| `update_geo_data` | Mengubah data lokasi |
| `delete_geo_data` | Menghapus data lokasi |

### Export, Backup, Settings

| Permission | Fungsi |
| --- | --- |
| `view_export` | Melihat halaman export |
| `export_data::pembanding` | Download file export |
| `view_backup` | Melihat halaman backup |
| `create_database_backup` | Membuat backup database |
| `create_uploads_backup` | Membuat backup uploaded files |
| `view_settings` | Melihat settings |
| `update_settings` | Mengubah settings |
| `clear_cache` | Membersihkan cache aplikasi |

## Matrix Role Operasional

| Role | Akses utama | Tidak boleh |
| --- | --- | --- |
| `super_admin` | Semua permission hasil seeder | Tidak ada pembatasan aplikasi normal |
| `pimpinan` | Dashboard/peta, lihat semua Pembanding, create Pembanding, update semua Pembanding | Delete, force delete, export, admin panel, settings, backup, user management |
| `data_contributor` | Dashboard terbatas, lihat Pembanding, create Pembanding, update Pembanding milik sendiri | Update data orang lain, delete, review/approve/reject, export, admin panel |
| `surveyor` | Master data user-side jika role ini diberi permission granular oleh seeder | Admin panel kecuali diberi `can_access_admin` dan permission admin lain |
| Role custom | Mengikuti permission yang diberikan | Tidak boleh mengandalkan nama role sebagai security |

## Keputusan API Read-Only Referensi

Endpoint API mobile berikut tetap `auth:sanctum` tanpa permission granular tambahan:

- `GET /api/v1/dictionaries/{type}`
- `GET /api/v1/locations/*`

Alasan: endpoint ini hanya membaca data referensi yang dibutuhkan aplikasi mobile/user login untuk form dan filter. Action manajemen master data tetap dikunci permission granular di route web `/home/master-data/*` dan admin `/admin/*`.

## Role Baru

### `pimpinan`

Role ini untuk user pimpinan yang bisa melihat, membuat, dan mengubah data bank data, tetapi tidak boleh menghapus.

Permission utama:

- `view_map`
- `view_any_data::pembanding`
- `view_data::pembanding`
- `create_data::pembanding`
- `update_data::pembanding`

Tidak diberikan:

- `delete_data::pembanding`
- `force_delete_data::pembanding`
- `export_data::pembanding`
- permission admin sensitif

### `data_contributor`

Role ini untuk user kontrak/freelance yang hanya membantu input data.

Permission utama:

- `view_map`
- `view_any_data::pembanding`
- `view_data::pembanding`
- `create_data::pembanding`
- `update_own_data::pembanding`

Pembatasan penting:

- Hanya bisa update data miliknya sendiri berdasarkan `created_by`.
- Tidak boleh update data user lain.
- Tidak boleh delete data apa pun.
- Dashboard dibatasi hanya map dan stat card.
- Tidak punya akses admin panel karena tidak punya `can_access_admin`.

## File Penting yang Diubah

| File | Fungsi |
| --- | --- |
| `app/Support/AdminAccess.php` | Pusat daftar permission dan menu admin berbasis permission |
| `app/Http/Controllers/Auth/AuthenticatedSessionController.php` | Redirect login berbasis `can_access_admin`, bukan nama role |
| `database/seeders/PembandingAccessRoleSeeder.php` | Seeder permission, role `pimpinan`, `data_contributor`, dan permission admin untuk `super_admin` |
| `database/seeders/AdminPanelPermissionSeeder.php` | Migrasi `can_access_admin_panel` ke `can_access_admin` |
| `database/seeders/MasterDataPermissionSeeder.php` | Migrasi `manage_master_data` ke permission granular master/geo |
| `routes/web.php` | Route admin memakai `can_access_admin` dan permission per action |
| `app/Http/Middleware/HandleInertiaRequests.php` | Share permission, `can_access_admin`, dan `adminMenu` ke Inertia |
| `app/Policies/PembandingPolicy.php` | Ownership update untuk `update_own_data::pembanding` |
| `app/Policies/RolePolicy.php` | Membersihkan placeholder permission yang rusak |
| `app/Http/Controllers/Admin/*` | Capability props untuk UI dan beberapa guard/validasi action |
| `app/Http/Controllers/Admin/Concerns/AuthorizesAdminPermissions.php` | Helper controller untuk `Gate::authorize` permission admin |
| `resources/js/inertia/components/admin/layout/AdminSidebar.vue` | Menu admin dari backend, bukan hardcoded |
| `resources/js/inertia/components/admin/layout/AdminTopbar.vue` | Global search admin mengikuti permission |
| `resources/js/inertia/Pages/Admin/*` | Tombol/action admin mengikuti capability props |
| `tests/Feature/AdminPermissionAccessTest.php` | Test permission admin |
| `tests/Feature/PembandingRoleAccessTest.php` | Test role `pimpinan` dan `data_contributor` |
| `tests/Feature/DashboardAccessTest.php` | Test dashboard `data_contributor` |

## Checklist yang Sudah Selesai

- [x] Audit menu dan action admin yang masih role-only.
- [x] Tambah permission granular untuk admin panel.
- [x] Gunakan `can_access_admin` sebagai permission pintu masuk admin.
- [x] Seed permission admin ke `super_admin`.
- [x] Seed role `pimpinan`.
- [x] Seed role `data_contributor`.
- [x] Batasi `data_contributor` hanya bisa update data miliknya sendiri.
- [x] Blok delete/force delete untuk `pimpinan`.
- [x] Blok delete/force delete untuk `data_contributor`.
- [x] Batasi dashboard `data_contributor` hanya map dan stat card.
- [x] Ubah route admin menjadi permission based.
- [x] Filter menu admin dari backend.
- [x] Filter tombol/action admin berdasarkan capability props.
- [x] Pisahkan permission lihat halaman export dan download export.
- [x] Pisahkan permission lihat backup dan membuat backup.
- [x] Pisahkan permission lihat settings, update settings, dan clear cache.
- [x] Batasi global admin search berdasarkan permission modul.
- [x] Tambah test akses admin granular.
- [x] Jalankan full test suite.
- [x] Jalankan frontend build.
- [x] Ubah redirect login di `bootstrap/app.php` dari `hasRole('super_admin')` menjadi permission `can_access_admin`.
- [x] Konsolidasi permission lama `can_access_admin_panel` ke `can_access_admin`.
- [x] Rapikan user-side master data dari `role_or_permission:surveyor|manage_master_data` ke permission granular.
- [x] Konsolidasi permission lama `manage_master_data` ke permission granular master/geo.
- [x] Putuskan API dictionary/location read-only tetap `auth:sanctum`.
- [x] Tambahkan `Gate::authorize()` internal untuk controller admin utama.
- [x] Dokumentasikan role matrix final untuk role operasional saat ini.
- [x] Tambah test untuk Settings, Backup, Export, Master Data, Geo Data, dan Moderation 403 per permission.
- [x] Audit permission legacy setelah konsolidasi; legacy hanya tersisa di seeder migrasi, test, dan dokumen.

## Checklist Follow-up

Item di bawah ini belum wajib untuk memakai fitur sekarang, tetapi perlu diselesaikan agar desain akses benar-benar konsisten.

- [ ] Tambahkan policy khusus model untuk resource admin non-Pembanding jika nanti butuh aturan berbasis record, bukan hanya permission action.
- [ ] Tambah UI audit screen/report untuk menampilkan permission yang tidak dipakai oleh route/menu saat jumlah permission makin besar.

## Cara Verifikasi

Jalankan seeder permission:

```bash
php artisan db:seed --class=PembandingAccessRoleSeeder
```

Jalankan test:

```bash
php artisan test
```

Jalankan build frontend:

```bash
npm.cmd run build
```

Cek middleware route admin:

```bash
php artisan route:list --path=admin/pembanding -v
php artisan route:list --path=admin/users -v
```

Manual smoke test:

1. Login sebagai `super_admin`.
2. Pastikan semua menu admin utama masih tampil.
3. Buat role test dengan `can_access_admin` dan `view_any_user` saja.
4. Login sebagai role test.
5. Pastikan hanya menu Users yang relevan tampil.
6. Pastikan tombol create/edit/delete user tidak tampil.
7. Coba request manual delete user. Hasil yang benar: `403`.
8. Tambahkan `delete_user` ke role test.
9. Coba delete lagi. Hasil yang benar: action baru diizinkan.

## Catatan Risiko

- Permission yang terlalu luas seperti `create_database_backup`, `update_settings`, `force_delete_data::pembanding`, dan `delete_any_user` harus dianggap sensitif.
- Jangan memberi `can_access_admin` tanpa mengecek permission granular yang menyertainya.
- Jangan mengandalkan UI hiding sebagai security. UI hanya kenyamanan. Pengamanan utama tetap route/controller/policy.
- Jangan membuat role baru dengan hardcode di controller. Role baru harus dibangun dari permission yang sudah ada.
