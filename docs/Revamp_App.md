# Revamp App: Satu Panel untuk Seluruh User

## Status dan keputusan final

Dokumen ini menetapkan arah migrasi berikut:

- seluruh user memakai application shell dan desain panel `super_admin` yang sama;
- perbedaan antar-user hanya berasal dari role, permission, ownership data, dan status workflow;
- satu fitur hanya memiliki satu route canonical dan satu implementasi utama;
- menu yang tidak dapat diakses tidak ditampilkan;
- endpoint tetap diamankan di backend meskipun menu sudah disembunyikan;
- layout user lama dihapus setelah seluruh consumer dipindahkan;
- `super_admin` bukan aplikasi terpisah, melainkan role dengan permission paling lengkap.

Target ini bukan sekadar membuat tampilan user menyerupai panel admin. Targetnya adalah menghapus konsep dua panel dari arsitektur aplikasi.

## Hasil implementasi 7 Juli 2026

- [x] Frontend Inertia dipindahkan langsung ke `resources/js`.
- [x] Vite dan Blade memakai satu entrypoint `resources/js/app.js`.
- [x] Seluruh authenticated page memakai `AppLayout` berbasis desain panel lama `super_admin`.
- [x] Menu bersama berasal dari backend dan difilter berdasarkan permission.
- [x] Route aktif disatukan dalam namespace `/app`.
- [x] Login seluruh role diarahkan ke application shell yang sama.
- [x] Bulk Import tidak lagi bercabang berdasarkan panel.
- [x] `TopNavLayout`, `PembandingImportLayout`, dan `UserLayout` dihapus.
- [x] Folder `Pages/Admin`, `components/admin`, `Controllers/Admin`, dan `Requests/Admin` dihapus.
- [x] `AdminAccess` diganti menjadi `AppAccess` dan `adminMenu` menjadi `appMenu`.
- [x] `can_access_admin` dipensiunkan; permission fitur tetap menjadi guard endpoint.
- [x] Page/controller duplikat Dashboard, Profile, Pembanding, dan Master Data dikonsolidasikan.
- [x] Full test suite dan production build lulus setelah migrasi.
- [ ] Redirect kompatibilitas `/home/*` dan `/admin/*` dihapus setelah masa transisi URL selesai.

## Hasil evaluasi plan sebelumnya

Plan sebelumnya sudah benar dalam memilih satu layout, satu menu, dan satu route per fitur. Namun ada beberapa celah yang dapat membuat implementasi tetap berakhir sebagai dua panel:

1. `can_access_admin` masih diperlakukan sebagai gerbang panel. Jika seluruh user memakai panel yang sama, permission ini kehilangan makna dan tidak boleh tetap menjadi middleware global.
2. `PembandingImportLayout` masih dapat mempertahankan percabangan layout berdasarkan URL. Layout adaptif seperti ini hanya menutupi duplikasi, bukan menghapusnya.
3. Redirect login masih dapat bercabang antara `/home` dan `/admin`. Setelah revamp, seluruh user harus masuk melalui landing route yang sama.
4. Halaman user dan admin yang duplikat belum memiliki aturan pemilihan yang tegas. Desain admin boleh menjadi dasar, tetapi business rule dari halaman user tidak boleh hilang.
5. Route `/home` dan `/admin` belum dibedakan antara route transisi dan route aktif. Selama keduanya menjalankan controller yang sama sebagai endpoint aktif, kompleksitas tetap ada.

Plan revisi di bawah menutup kelima celah tersebut.

## Prinsip arsitektur

### 1. Satu application shell

Gunakan visual panel `super_admin` sebagai design system utama, lalu ubah penamaannya menjadi netral:

- `AdminLayout.vue` menjadi `AppLayout.vue`;
- `AdminSidebar.vue` menjadi `AppSidebar.vue`;
- `AdminTopbar.vue` menjadi `AppTopbar.vue`;
- `adminMenu` menjadi `appMenu`;
- `AdminAccess` dipisah atau diubah menjadi layanan navigasi/capability yang tidak khusus admin.

`AppLayout` digunakan oleh seluruh halaman authenticated tanpa pengecualian berdasarkan role.

Layout bersama bertanggung jawab atas:

- sidebar desktop dan mobile;
- topbar dan identitas user;
- breadcrumb;
- menu berdasarkan permission;
- flash message dan toast;
- confirmation dialog;
- responsive behavior;
- focus management dan aksesibilitas;
- loading dan navigation state.

### 2. Satu namespace route

Gunakan prefix netral `/app`, bukan `/admin`. Memakai `/admin` untuk semua user memang dapat bekerja, tetapi istilahnya salah dan akan terus mendorong developer membuat pengecualian berdasarkan jenis panel.

Contoh route canonical:

- `/app` atau `/app/dashboard`;
- `/app/pembanding`;
- `/app/pembanding-imports`;
- `/app/master-data`;
- `/app/profile`;
- `/app/users`;
- `/app/settings`.

Gunakan nama route konsisten, misalnya `app.dashboard`, `app.pembanding.index`, dan `app.p2pk-imports.index`.

Route `/home/*` dan `/admin/*` hanya boleh menjadi redirect sementara selama masa transisi. Route lama tidak boleh menjalankan business flow kedua.

### 3. Hapus gerbang panel, pertahankan gerbang fitur

Route group `/app` cukup menggunakan middleware authenticated user yang aktif, misalnya `auth` dan `app.user`.

Permission `can_access_admin` tidak boleh lagi menjadi syarat global karena tidak ada panel admin terpisah. Pilih salah satu:

- hapus permission tersebut setelah seluruh route selesai dimigrasikan; atau
- migrasikan sementara menjadi `access_application`, lalu hapus jika `auth` dan `app.user` sudah cukup.

Setiap route tetap memiliki permission fitur yang spesifik, contohnya:

- `view_any_user` untuk daftar user;
- `view_access_control` untuk access control;
- `view_any_data::pembanding` untuk daftar Data Pembanding;
- `bulk_import_data::pembanding` untuk Bulk Import;
- `view_settings` untuk settings.

Menyembunyikan menu bukan authorization. Middleware, policy, Form Request, action authorization, dan ownership query tetap wajib.

### 4. Permission, bukan nama role, mengendalikan UI

Frontend tidak boleh memeriksa `super_admin`, `surveyor`, atau role lain untuk menentukan tombol dan menu. Backend mengirim capability yang sudah dihitung:

- `can.view`;
- `can.create`;
- `can.update`;
- `can.delete`;
- `can.approve`;
- `can.bulkImport`;
- capability lain yang benar-benar dipakai halaman.

Role tetap dipakai untuk mengelompokkan permission di backend. Pengecualian berbasis role hanya dibenarkan untuk business rule yang memang eksplisit, seperti `super_admin` dapat memulihkan batch Bulk Import milik user lain.

### 5. Satu halaman utama per kebutuhan bisnis

Jika halaman user dan admin melakukan pekerjaan yang sama, pilih satu implementasi utama. Desain halaman admin menjadi dasar visual, tetapi seluruh behavior penting dari halaman user harus dipindahkan sebelum halaman user dihapus.

Jangan langsung menghapus halaman user hanya karena tampilannya lama. Audit dahulu:

- ownership dan scope data;
- action yang tersedia;
- validasi;
- delete request dan moderation flow;
- filter dan pagination;
- URL hasil action;
- mobile behavior;
- pesan error dan empty state.

Halaman terpisah hanya dibenarkan jika workflow bisnisnya berbeda, bukan karena role berbeda.

## Perilaku role dan menu

Semua role melihat shell yang sama. Isi sidebar dihasilkan oleh backend berdasarkan permission user.

| Role | Contoh menu yang terlihat |
|---|---|
| `super_admin` | Seluruh menu dan action yang tersedia |
| `pimpinan` | Dashboard, monitoring, Bank Data, export/laporan yang diizinkan |
| `data_contributor` | Dashboard, Bank Data, create/edit data miliknya, profile |
| `surveyor` | Dashboard, peta, Bank Data dan workflow survei yang diizinkan |
| `bulk_import` | Dashboard ringkas, Bulk Import, dan profile |

Tabel ini bukan sumber authorization. Seeder permission dan policy adalah sumber kebenaran.

Aturan rendering menu:

- sembunyikan menu yang permission-nya tidak dimiliki user;
- sembunyikan parent jika seluruh child tersembunyi;
- jangan tampilkan disabled action kepada role yang tidak akan pernah memiliki akses;
- gunakan disabled state hanya untuk action yang sebenarnya diizinkan tetapi sementara tidak tersedia karena status record;
- jangan tampilkan internal permission string kepada user;
- profil dan logout tersedia untuk seluruh user aktif;
- active state dan breadcrumb berasal dari route canonical `/app`.

## Landing page setelah login

Redirect login tidak boleh lagi bercabang ke panel user atau admin.

Target paling sederhana:

- seluruh role aktif memiliki permission dashboard dasar;
- seluruh login berhasil diarahkan ke `app.dashboard`;
- isi widget dashboard difilter berdasarkan permission;
- menu pertama tidak perlu ditebak berdasarkan role.

Jika ada role yang benar-benar tidak boleh melihat dashboard, `/app` harus mengarahkan user ke route pertama yang dapat diakses berdasarkan permission. Jangan hard-code redirect berdasarkan nama role.

## Target penghapusan layout lama

Setelah consumer terakhir selesai dimigrasikan, hapus:

- `resources/js/Layouts/TopNavLayout.vue`;
- `resources/js/Layouts/PembandingImportLayout.vue`;
- konfigurasi navigation yang tertanam di `TopNavLayout`;
- seluruh conditional layout berdasarkan `/home` atau `/admin`;
- import `TopNavLayout` dan `PembandingImportLayout` dari seluruh page;
- nama komponen admin lama setelah versi netral digunakan.

`PembandingImportLayout` tidak masuk desain akhir. Komponen itu hanya adapter sementara yang memilih `AdminLayout` atau `TopNavLayout`, sehingga mempertahankannya berarti mempertahankan dua panel.

## Target cleanup struktur folder

Struktur folder harus mengikuti fitur dan tanggung jawab, bukan role yang memakai halaman. Folder bernama `Admin` tidak lagi tepat setelah seluruh user menggunakan panel yang sama.

Target frontend:

```text
resources/js/
|- app.js
|- bootstrap.js
|- Layouts/
|  `- AppLayout.vue
|- components/
|  |- layout/
|  |  |- AppSidebar.vue
|  |  `- AppTopbar.vue
|  |- master-data/
|  |- pembanding/
|  |- ui/
|  `- widgets/
`- Pages/
   |- AccessControl/
   |- ActivityLogs/
   |- Auth/
   |- Backup/
   |- Dashboard.vue
   |- DataContributorInvitations/
   |- Export/
   |- GeoData/
   |- MasterData/
   |- Moderation/
   |- Pembanding/
   |- PembandingImports/
   |- Profile/
   |- Search/
   |- Settings/
   `- Users/
```

Aturan cleanup frontend:

- folder `resources/js/inertia` dihapus lebih dahulu melalui pemindahan mekanis ke `resources/js`;
- hapus `resources/js/Pages/Admin` setelah seluruh page dipindahkan atau digabungkan;
- pindahkan page unik berdasarkan nama fitur, bukan ke folder role baru;
- gabungkan pasangan page yang bertabrakan seperti Dashboard, Profile, Pembanding, dan Master Data sebelum menghapus salah satunya;
- pindahkan `components/admin/layout` menjadi `components/layout`;
- jangan membuat folder `SuperAdmin`, `User`, `Surveyor`, atau folder role lain;
- pertahankan folder `Auth` karena itu boundary fitur, bukan role;
- pertahankan penamaan `Pages` dan `Layouts` yang sudah menjadi konvensi resolver proyek; jangan melakukan case-only rename yang tidak memberi nilai.

Target backend:

```text
app/Http/Controllers/
|- Api/
|- App/
|  |- AccessControlController.php
|  |- ActivityLogController.php
|  |- BackupController.php
|  |- DashboardController.php
|  |- PembandingController.php
|  `- controller fitur authenticated lainnya
`- Auth/
```

Aturan cleanup backend:

- hapus namespace `App\Http\Controllers\Admin` setelah controller dipindahkan atau digabungkan ke `App\Http\Controllers\App`;
- jangan sekadar memindahkan dua controller yang fungsinya sama; gabungkan behavior dan pilih satu nama canonical;
- selesaikan collision `DashboardController`, `ProfileController`, Pembanding, dan Master Data berdasarkan tanggung jawab, bukan dengan suffix `Admin` atau `User`;
- pertahankan `Api` dan `Auth` karena keduanya boundary transport/workflow yang nyata;
- ubah `AdminAccess.php` menjadi nama netral seperti `AppNavigation.php`;
- jika katalog permission dan pembentukan menu mulai terlalu besar, pisahkan `AppNavigation` dari `PermissionCatalog` alih-alih mempertahankan class serba guna;
- rename test seperti `AdminPermissionAccessTest` menjadi nama netral setelah route canonical tersedia.

Folder admin tidak boleh dipertahankan hanya karena fiturnya sensitif. `Users`, `AccessControl`, `Backup`, dan `Settings` tetap dapat berada di folder fitur umum; permission route-lah yang membuatnya terbatas.

## Ratakan folder Inertia ke `resources/js`

Folder `resources/js/inertia` sebelumnya masuk akal ketika frontend Inertia hidup berdampingan dengan frontend lain seperti Filament atau entrypoint legacy. Kondisi repository saat ini sudah berbeda:

- tidak ditemukan referensi Filament aktif;
- Blade Inertia hanya memuat `resources/js/inertia/app.js`;
- `resources/js/app.js` lama berisi bootstrap Alpine/Leaflet dan tidak dipanggil oleh Blade aktif;
- Vite masih membangun kedua entrypoint, sehingga menghasilkan struktur dan build yang tidak perlu.

Karena Inertia/Vue sekarang merupakan frontend utama, seluruh isi `resources/js/inertia` harus dipindahkan langsung ke `resources/js`. Setelah itu `resources/js/app.js` menjadi satu-satunya entrypoint aplikasi authenticated.

Target perpindahan:

| Sebelum | Sesudah |
|---|---|
| `resources/js/inertia/app.js` | `resources/js/app.js` |
| `resources/js/inertia/Pages` | `resources/js/Pages` |
| `resources/js/inertia/Layouts` | `resources/js/Layouts` |
| `resources/js/inertia/components` | `resources/js/components` |
| `resources/js/inertia/composables` | `resources/js/composables` |
| `resources/js/inertia/config` | `resources/js/config` |
| `resources/js/inertia/utils` | `resources/js/utils` |

Pemindahan ini harus menjadi fase pertama setelah baseline karena bersifat mekanis dan akan menyederhanakan seluruh rename berikutnya. Jangan mencampurnya dengan penggabungan page admin/user dalam commit yang sama; jika build gagal, penyebabnya harus mudah dilacak.

## Strategi migrasi

Migrasi dilakukan per vertical slice. Setiap fitur dipindahkan lengkap dari route, controller, page, authorization, menu, test, sampai redirect. Jangan memindahkan layout seluruh halaman sekaligus tanpa memindahkan URL dan authorization-nya.

### Fase 0 - Amankan baseline

- [ ] Simpan atau commit pekerjaan fitur yang sedang berjalan agar revamp tidak bercampur dengan perubahan lain.
- [ ] Inventarisasi route `/home`, `/admin`, dan seluruh hard-coded URL.
- [ ] Inventarisasi consumer `TopNavLayout`, `AdminLayout`, dan `PembandingImportLayout`.
- [ ] Petakan pasangan page/controller user dan admin yang fungsinya sama.
- [ ] Petakan permission, policy, ownership rule, dan middleware setiap fitur.
- [ ] Tambahkan test baseline untuk semua role utama.
- [ ] Bekukan penambahan route atau halaman duplikat baru.

### Fase 1 - Ratakan frontend Inertia

- [ ] Pastikan tidak ada Blade, plugin, atau script aktif yang masih membutuhkan `resources/js/app.js` legacy berbasis Alpine.
- [ ] Simpan behavior Leaflet yang masih benar-benar dipakai ke komponen/composable Vue; jangan membawa bootstrap Alpine yang sudah mati ke entrypoint baru.
- [ ] Ganti `resources/js/app.js` dengan entrypoint Inertia dari `resources/js/inertia/app.js`.
- [ ] Pindahkan `Pages`, `Layouts`, `components`, `composables`, `config`, dan `utils` ke langsung di bawah `resources/js`.
- [ ] Ubah import bootstrap pada entrypoint menjadi path root yang benar.
- [ ] Ubah page glob resolver menjadi `./Pages/**/*.vue` dari lokasi entrypoint baru.
- [ ] Perbarui `vite.config.js` agar hanya membangun `resources/css/app.css` dan `resources/js/app.js`.
- [ ] Perbarui `resources/views/inertia.blade.php` agar memuat `resources/js/app.js`.
- [ ] Perbarui seluruh import yang masih mengandung path `/inertia/`.
- [ ] Hapus entrypoint legacy, folder `resources/js/inertia`, dan `UserLayout.vue` jika tetap tidak memiliki consumer.
- [ ] Jalankan production build dan smoke test seluruh page Inertia.
- [ ] Pastikan Vite manifest tidak lagi memiliki dua entrypoint JavaScript aplikasi.

Fase ini tidak boleh mengubah route, permission, layout visual, atau behavior halaman. Tujuannya hanya meratakan struktur frontend dengan aman.

### Fase 2 - Bangun shell netral dari panel super admin

- [ ] Rename `AdminLayout` menjadi `AppLayout` tanpa mengubah behavior terlebih dahulu.
- [ ] Rename sidebar dan topbar menjadi komponen netral.
- [ ] Ubah copy seperti "Admin Control Panel" dan suffix "Admin" menjadi istilah aplikasi umum.
- [ ] Pertahankan density, sidebar, topbar, dan visual hierarchy panel `super_admin`.
- [ ] Jadikan toast, confirmation dialog, dan breadcrumb hanya didaftarkan sekali di `AppLayout`.
- [ ] Pastikan sidebar mobile, focus trap, focus restoration, keyboard navigation, dan touch target minimal 44x44 px bekerja.
- [ ] Tetapkan WCAG 2.1 AA sebagai quality gate.
- [ ] Nyatakan dark mode di luar scope kecuali memang sudah ada sistem theme lengkap.

### Fase 3 - Satukan navigasi dan capability

- [ ] Ubah `AdminAccess::menuFor()` menjadi layanan menu aplikasi yang netral.
- [ ] Tambahkan seluruh menu user yang valid ke sumber menu backend yang sama.
- [ ] Hapus menu hard-coded dari `TopNavLayout`.
- [ ] Filter section dan child berdasarkan permission backend.
- [ ] Kirim capability action per halaman, bukan seluruh daftar permission mentah.
- [ ] Tambahkan test snapshot/struktur menu untuk setiap role.
- [ ] Tambahkan test bahwa endpoint tetap 403 saat menu tidak terlihat.

Kelompok menu yang direkomendasikan:

1. Overview;
2. Bank Data;
3. Master Data;
4. User & Access, hanya jika diizinkan;
5. System, hanya jika diizinkan.

### Fase 4 - Buat namespace `/app`

- [ ] Buat route group `/app` dengan middleware `auth` dan `app.user`.
- [ ] Pindahkan permission dari gerbang panel ke route fitur masing-masing.
- [ ] Buat `app.dashboard` sebagai landing page bersama.
- [ ] Ubah redirect login dan intended URL handling ke namespace `/app`.
- [ ] Pastikan maintenance mode dan deactivated user tetap ditangani.
- [ ] Jadikan route lama redirect sementara yang mempertahankan query string jika relevan.
- [ ] Jangan membuat controller kedua hanya untuk membedakan role atau layout.

### Fase 5 - Pilot Bulk Import

Bulk Import menjadi pilot karena sudah menunjukkan bug lintas-panel.

- [ ] Tetapkan hanya `/app/pembanding-imports` sebagai endpoint aktif.
- [ ] Gunakan `AppLayout` langsung untuk Index, Show, dan Edit.
- [ ] Hapus `importContext`, `is_admin`, dan branching base URL dari desain akhir.
- [ ] Pastikan upload, redirect, filter, pagination, selection, dan bulk apply memakai route `app.*`.
- [ ] Pastikan edit, update, image, retry, polling, dan finalize memakai route `app.*`.
- [ ] Pastikan link hasil import membuka Data Pembanding pada route `app.*`.
- [ ] Pastikan role `bulk_import` hanya melihat batch miliknya.
- [ ] Pastikan `super_admin` dapat melihat dan melanjutkan seluruh batch.
- [ ] Ubah route `/home/pembanding-imports*` dan `/admin/pembanding-imports*` menjadi redirect atau hapus setelah masa transisi.
- [ ] Hapus `PembandingImportLayout.vue` setelah pilot stabil.
- [ ] Tambahkan regression test bahwa semua response dan redirect tetap di namespace `/app`.

### Fase 6 - Migrasi Profile dan Dashboard

- [ ] Gabungkan profile user dan profile admin menjadi satu halaman self-service.
- [ ] Pastikan update profile dan password tetap tersedia sesuai aturan keamanan.
- [ ] Gunakan dashboard desain `super_admin` sebagai basis.
- [ ] Gabungkan widget yang masih relevan dari dashboard user.
- [ ] Filter widget dan alert berdasarkan permission.
- [ ] Arahkan seluruh login berhasil ke landing route yang sama.
- [ ] Hapus page dan controller dashboard/profile yang duplikat setelah parity tercapai.

### Fase 7 - Migrasi Data Pembanding

- [ ] Bandingkan seluruh behavior page `Admin/Pembanding` dan `Pembanding`.
- [ ] Pilih implementasi visual admin sebagai basis halaman canonical.
- [ ] Pertahankan ownership rule untuk contributor/surveyor.
- [ ] Pertahankan create, edit own, update any, history, export, dan delete request sesuai permission.
- [ ] Bedakan read-only dan edit mode secara visual, bukan hanya disabled input.
- [ ] Sembunyikan action yang tidak pernah dimiliki role.
- [ ] Pastikan action sementara tidak tersedia menjelaskan alasannya.
- [ ] Migrasikan link dari Bulk Import ke route Data Pembanding canonical.
- [ ] Hapus page/controller duplikat hanya setelah seluruh feature test parity lulus.

### Fase 8 - Migrasi Master Data, Geo, dan fitur system

- [ ] Gabungkan implementasi Master Data user dan admin berdasarkan capability.
- [ ] Migrasikan Geo Data.
- [ ] Migrasikan User Management dan Access Control.
- [ ] Migrasikan invitations dan moderation.
- [ ] Migrasikan export, backup, settings, activity log, dan search.
- [ ] Pastikan seluruh action sensitif memiliki confirmation dan permission spesifik.
- [ ] Pastikan user tanpa permission tidak melihat section System.

### Fase 9 - Hapus arsitektur lama

- [ ] Pastikan tidak ada page yang mengimpor `TopNavLayout`.
- [ ] Hapus `TopNavLayout.vue`.
- [ ] Pastikan tidak ada page yang mengimpor `PembandingImportLayout`.
- [ ] Hapus `PembandingImportLayout.vue`.
- [ ] Hapus route aktif `/home/*` dan `/admin/*` setelah periode redirect selesai.
- [ ] Hapus branching login berdasarkan `can_access_admin`.
- [ ] Hapus `can_access_admin` dari middleware global dan seeder setelah tidak dipakai.
- [ ] Hapus nama `Admin` dari layout/navigation umum.
- [ ] Pindahkan seluruh page unik dari `Pages/Admin` ke folder fitur.
- [ ] Gabungkan page yang memiliki versi admin dan user sebelum menghapus duplikat.
- [ ] Hapus folder `resources/js/Pages/Admin` setelah kosong.
- [ ] Pindahkan `components/admin/layout` ke `components/layout` lalu hapus folder `components/admin` jika kosong.
- [ ] Pindahkan atau gabungkan controller dari `App\Http\Controllers\Admin` ke namespace authenticated app yang netral.
- [ ] Hapus folder `app/Http/Controllers/Admin` setelah kosong.
- [ ] Rename `AdminAccess`, `adminMenu`, dan test bernama admin yang sudah memiliki cakupan aplikasi umum.
- [ ] Pastikan tidak ada folder baru yang dibentuk berdasarkan role.
- [ ] Hapus page dan controller duplikat yang sudah tidak memiliki consumer.
- [ ] Hapus seluruh hard-coded URL lama.
- [ ] Perbarui dokumentasi API dan dokumentasi operasional.

## Checklist per fitur

Setiap fitur baru dianggap selesai dimigrasikan hanya jika:

- [ ] memakai `AppLayout` secara langsung;
- [ ] memiliki satu route canonical `/app`;
- [ ] tidak memiliki branching layout atau URL berdasarkan role;
- [ ] menu berasal dari backend dan sudah difilter;
- [ ] tombol/action mengikuti capability backend;
- [ ] middleware dan policy menolak akses langsung yang tidak sah;
- [ ] ownership dan scope data tetap benar;
- [ ] semua redirect, pagination, filter, polling, upload, dan download tetap di namespace `/app`;
- [ ] memiliki loading, empty, validation, success, warning, dan error state;
- [ ] dapat digunakan dengan keyboard dan mobile;
- [ ] test role yang diizinkan dan ditolak lulus;
- [ ] implementasi lama sudah dihapus atau hanya berupa redirect transisi.

## Matriks test minimum

Untuk setiap modul, uji minimal:

| Skenario | Hasil yang diharapkan |
|---|---|
| Permission menu tidak dimiliki | Menu tidak dirender |
| URL dibuka tanpa permission | Response 403 |
| Permission view saja | Halaman tampil tanpa action mutasi |
| Permission mutasi dimiliki | Action tampil dan endpoint berhasil |
| Record milik user lain | Ditolak sesuai policy/ownership rule |
| `super_admin` | Seluruh action yang sah tersedia |
| Mobile dan keyboard | Navigasi dan action tetap dapat digunakan |
| Redirect setelah action | Tetap menggunakan route `app.*` |

Test khusus yang perlu diperbarui:

- login redirect dan intended URL;
- admin menu menjadi app menu;
- dashboard widget filtering;
- maintenance mode;
- Pembanding role access;
- Bulk Import ownership dan redirect;
- Master Data API;
- seluruh test yang masih hard-code `/home` atau `/admin`.

## Quality gate

Sebelum revamp dinyatakan selesai:

- [ ] folder `resources/js/inertia` sudah tidak ada.
- [ ] Vite dan Blade hanya mereferensikan `resources/js/app.js` sebagai entrypoint JavaScript aplikasi.
- [ ] `rg "TopNavLayout|PembandingImportLayout" resources/js` tidak menghasilkan consumer atau file.
- [ ] `rg "can_access_admin" app routes resources database tests` hanya kosong atau tersisa pada migration compatibility yang terdokumentasi.
- [ ] `rg "Pages/Admin|components/admin|Controllers\\Admin|AdminLayout|AdminSidebar|AdminTopbar|adminMenu|AdminAccess" app resources routes tests` tidak menunjukkan struktur panel lama.
- [ ] `rg "'/home|\"/home|'/admin|\"/admin" app resources routes tests` tidak menunjukkan endpoint aktif lama.
- [ ] `php artisan route:list` hanya menunjukkan satu namespace aktif untuk fitur authenticated.
- [ ] seluruh feature test authorization dan ownership lulus;
- [ ] seluruh test navigation dan redirect lulus;
- [ ] frontend production build lulus;
- [ ] tidak ada console error pada navigasi Inertia;
- [ ] pengujian desktop, tablet, mobile, keyboard, dan screen reader smoke test selesai;
- [ ] tidak ada layout, page, controller, atau route duplikat tanpa alasan bisnis tertulis.

## Risiko utama

### Kehilangan business rule saat memilih halaman admin

Desain panel admin harus dipakai, tetapi behavior halaman user tidak boleh dibuang. Audit parity wajib dilakukan sebelum menghapus page lama.

### Kebocoran akses akibat menu-only security

Menu tersembunyi tidak mencegah request manual. Semua route dan action harus tetap memiliki authorization backend.

### Memberikan `can_access_admin` kepada semua role

Ini jalan pintas yang salah. Permission tersebut akan menjadi tidak bermakna dan berpotensi membuka route yang masih bergantung pada gerbang panel. Pindahkan authorization ke permission fitur sebelum memensiunkannya.

### Route ganda selama transisi

Route lama hanya boleh redirect. Menjalankan flow aktif pada `/home`, `/admin`, dan `/app` sekaligus akan memperbesar area bug dan test.

### Penghapusan terlalu cepat

Layout lama memang harus dihapus, tetapi hanya setelah consumer terakhir bermigrasi dan test parity lulus. Menghapusnya lebih awal akan memaksa perubahan big bang yang sulit diverifikasi.

## Definisi selesai

Revamp selesai ketika:

- frontend Inertia berada langsung di `resources/js` tanpa wrapper folder dan tanpa dual-entry Vite;
- seluruh user memakai `AppLayout` hasil generalisasi desain panel `super_admin`;
- semua login masuk ke application shell yang sama;
- sidebar dan action berbeda berdasarkan permission backend;
- `super_admin` tidak memiliki panel atau route khusus;
- setiap fitur memiliki satu route canonical dan satu implementasi utama;
- authorization route, action, policy, dan ownership tetap ketat;
- `TopNavLayout` dan `PembandingImportLayout` sudah dihapus;
- folder `Pages/Admin`, `components/admin`, dan `Controllers/Admin` sudah kosong dan dihapus;
- page, component, controller, support class, dan test menggunakan nama berbasis fitur atau aplikasi, bukan nama panel/role;
- route aktif `/home` dan `/admin` sudah dihapus atau hanya tersisa sebagai redirect transisi yang terjadwal untuk dihapus;
- `can_access_admin` tidak lagi menjadi gerbang aplikasi;
- seluruh test authorization, navigation, responsive behavior, dan production build lulus.

## Prinsip akhir

Satu aplikasi, satu application shell, satu design system, satu route per fitur, dan satu sumber permission. Role menentukan apa yang dapat dilihat dan dilakukan, bukan menentukan panel mana yang dipakai.
