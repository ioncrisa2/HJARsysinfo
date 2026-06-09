# Mobile API Implementation Checklist

Tanggal penyusunan: 2026-05-29

Status legend:

| Status | Arti |
| --- | --- |
| `[x]` | Selesai dan sudah diverifikasi |
| `[ ]` | Belum dikerjakan |
| `[~]` | Sedang dikerjakan atau sebagian selesai |
| `[!]` | Terblokir atau perlu keputusan |

Catatan keras: API saat ini belum siap untuk CRUD penuh mobile. Yang ada baru cukup untuk login, baca dictionary, baca pembanding, dan similar search. Kalau Flutter dipaksa masuk ke submit data sekarang, hasilnya akan penuh workaround.

## Target Minimum Mobile API

- [x] Mobile bisa login dengan Bearer token.
- [x] Mobile bisa refresh token.
- [x] Mobile bisa membaca profil user login.
- [x] Mobile bisa membaca dictionary.
- [x] Mobile bisa membaca list/detail data pembanding.
- [ ] Mobile bisa membaca role dan permission user dari API.
- [ ] Mobile bisa membaca lokasi dari `/api/v1`.
- [ ] Mobile bisa create data pembanding untuk semua `jenis_listing`.
- [ ] Mobile bisa create data pembanding `sewa` dengan periode harga sewa eksplisit.
- [ ] Mobile bisa update data pembanding.
- [ ] Mobile bisa mengajukan hapus data pembanding.
- [ ] Mobile bisa delete langsung jika permission mengizinkan.
- [ ] Mobile bisa menambah master data jika nilai belum tersedia.
- [ ] Mobile bisa menambah lokasi jika wilayah belum tersedia.
- [ ] Semua endpoint write punya permission check yang konsisten dengan web.
- [ ] Semua endpoint API mengembalikan JSON, termasuk 401, 403, dan 422.
- [ ] Dokumentasi API cukup jelas untuk developer mobile tanpa membaca source Laravel.

## Kondisi Saat Ini

### Sudah Ada

- [x] `POST /api/auth/login`
- [x] `POST /api/auth/refresh`
- [x] `GET /api/auth/me`
- [x] `POST /api/auth/logout`
- [x] `GET /api/v1/dictionaries/{type}`
- [x] `GET /api/v1/pembandings`
- [x] `GET /api/v1/pembandings/{id}`
- [x] `GET /api/v1/pembandings/{id}/similar`
- [x] `POST /api/v1/pembandings/similar`

### Belum Ada

- [ ] `POST /api/v1/pembandings`
- [ ] `PUT/PATCH /api/v1/pembandings/{id}`
- [ ] `POST /api/v1/pembandings/{id}` dengan `_method=PUT` untuk multipart mobile.
- [ ] `POST /api/v1/pembandings/{id}/delete-request`
- [ ] `DELETE /api/v1/pembandings/{id}`
- [ ] CRUD master data di jalur `/api/v1`.
- [ ] CRUD lokasi di jalur `/api/v1`.
- [ ] Response `/api/auth/me` memuat `roles`.
- [ ] Response `/api/auth/me` memuat `permissions`.
- [ ] Dokumentasi request body create/update pembanding.
- [ ] Test API untuk create/update/delete pembanding.
- [ ] Test API untuk master data.
- [ ] Test API untuk lokasi.

## Prinsip Implementasi

- [ ] Validasi web dan API untuk pembanding memakai sumber aturan yang sama atau diekstrak ke basis bersama.
- [ ] Perbaikan field `sewa` yang sudah ada tetap berlaku di API.
- [ ] Endpoint mobile tidak memakai route `/home/master-data/...`.
- [ ] Endpoint mobile memakai Bearer token, bukan web session.
- [ ] Endpoint mobile tidak mengembalikan redirect, flash session, HTML 403, atau halaman Inertia.
- [ ] Write master data dan lokasi wajib permission-gated.
- [ ] Write master data dan lokasi wajib punya duplicate prevention.
- [ ] Semua write tercatat ke user token.
- [ ] Rilis dilakukan bertahap, bukan menunggu semua sempurna.

## Phase 0 - Tetapkan Kontrak API

Tujuan: kontrak endpoint disepakati sebelum implementasi besar.

### Checklist

- [ ] Tetapkan format response sukses.
- [ ] Tetapkan format response error validasi.
- [ ] Tetapkan format response 401.
- [ ] Tetapkan format response 403.
- [ ] Tetapkan standar pagination.
- [ ] Tetapkan standar upload gambar.
- [ ] Tetapkan format tanggal.
- [ ] Tetapkan strategi update multipart: `POST /{id}` + `_method=PUT`.
- [ ] Tetapkan strategi update JSON: `PUT/PATCH /{id}`.
- [ ] Tetapkan policy delete user biasa: delete request.
- [ ] Tetapkan policy delete user berizin: direct delete.
- [ ] Tetapkan apakah force delete dibutuhkan di API atau tetap admin-only web.
- [ ] Update `docs/API.md` dengan kontrak awal.

### Definition of Done

- [ ] `docs/API.md` punya daftar endpoint baru.
- [ ] `docs/API.md` punya request body minimum.
- [ ] `docs/API.md` punya contoh response sukses.
- [ ] `docs/API.md` punya contoh response error.
- [ ] `docs/API.md` mencantumkan permission per endpoint.

## Phase 1 - Perkuat Auth Context Untuk Mobile

Tujuan: mobile tahu user login punya role dan permission apa.

### Endpoint

- [ ] `GET /api/auth/me`
- [ ] `POST /api/auth/login`

### Checklist

- [ ] Tambahkan `roles` ke `UserResource`.
- [ ] Tambahkan `permissions` ke `UserResource`.
- [ ] Pastikan response login memakai struktur user yang sama dengan `/me`.
- [ ] Pastikan permission `view_any_data::pembanding` muncul jika user punya akses lihat.
- [ ] Pastikan permission `create_data::pembanding` muncul jika user punya akses create.
- [ ] Pastikan permission `update_data::pembanding` muncul jika user punya akses update.
- [ ] Pastikan permission `delete_data::pembanding` muncul jika user punya akses delete.
- [ ] Pastikan permission `manage_master_data` muncul jika user punya akses master data.
- [ ] Tambahkan test user tanpa permission.
- [ ] Tambahkan test user surveyor.
- [ ] Tambahkan test user super admin.

### Definition of Done

- [ ] Flutter bisa menampilkan atau menyembunyikan tombol create berdasarkan `/me`.
- [ ] Flutter bisa menampilkan atau menyembunyikan tombol edit berdasarkan `/me`.
- [ ] Flutter bisa menampilkan atau menyembunyikan tombol delete berdasarkan `/me`.
- [ ] Flutter bisa menampilkan atau menyembunyikan menu master data/lokasi berdasarkan `/me`.

## Phase 2 - API Read Untuk Form Dependency

Tujuan: mobile bisa membangun form pembanding tanpa hardcode master data dan lokasi.

### Endpoint

- [x] `GET /api/v1/dictionaries/{type}`
- [ ] `GET /api/v1/locations/provinces`
- [ ] `GET /api/v1/locations/regencies?province_id=...&q=...`
- [ ] `GET /api/v1/locations/districts?regency_id=...&q=...`
- [ ] `GET /api/v1/locations/villages?district_id=...&q=...`
- [ ] `GET /api/v1/pembandings/form-options` jika diputuskan perlu.

### Checklist

- [ ] Buat controller API khusus lokasi atau bungkus ulang logic lokasi yang ada.
- [ ] Reuse limit aman untuk pencarian lokasi.
- [ ] Pastikan response lokasi memakai format JSON API yang konsisten.
- [ ] Pastikan dictionary tidak lagi mengembalikan array mentah jika standar wrapper dipilih.
- [ ] Pastikan `active_only=false` hanya boleh dipakai user yang berhak.
- [ ] Tambahkan test read province.
- [ ] Tambahkan test read regency by province.
- [ ] Tambahkan test read district by regency.
- [ ] Tambahkan test read village by district.

### Definition of Done

- [ ] Mobile bisa membuka form create pembanding dari nol hanya dengan data API.
- [ ] Mobile tidak perlu hardcode dictionary.
- [ ] Mobile tidak perlu hardcode lokasi.

## Phase 3 - CRUD Pembanding API

Tujuan: mobile bisa create/update data pembanding untuk semua `jenis_listing`.

### Endpoint

- [ ] `POST /api/v1/pembandings`
- [ ] `POST /api/v1/pembandings/{id}` dengan `_method=PUT`
- [ ] `PUT /api/v1/pembandings/{id}`
- [ ] `PATCH /api/v1/pembandings/{id}`
- [ ] `POST /api/v1/pembandings/{id}/delete-request`
- [ ] `DELETE /api/v1/pembandings/{id}`

### Checklist

- [ ] Buat method create pembanding API.
- [ ] Buat method update pembanding API.
- [ ] Buat method delete request pembanding API.
- [ ] Buat method direct delete pembanding API.
- [ ] Reuse atau ekstrak validasi `PembandingStoreRequest`.
- [ ] Reuse atau ekstrak validasi `PembandingUpdateRequest`.
- [ ] Pastikan create menyimpan `created_by` dari token user.
- [ ] Pastikan update tidak menghapus image lama jika tidak ada file baru.
- [ ] Pastikan upload image memakai disk/path yang sama dengan web.
- [ ] Pastikan create mengembalikan `PembandingResource`.
- [ ] Pastikan update mengembalikan `PembandingResource`.
- [ ] Pastikan request hapus memakai `PembandingDeleteRequest` yang sama dengan web.
- [ ] Pastikan direct delete hanya untuk `delete_data::pembanding`.

### Checklist Khusus `jenis_listing = sewa`

- [ ] `harga` wajib diisi.
- [ ] `jangka_waktu_sewa` wajib diisi jika listing sewa.
- [ ] `satuan_waktu_sewa` wajib diisi jika listing sewa.
- [ ] `satuan_waktu_sewa` hanya boleh `Bulan` atau `Tahun`.
- [ ] Harga per bulan bisa disimpan.
- [ ] Harga per beberapa bulan bisa disimpan, contoh 3 Bulan.
- [ ] Harga per tahun bisa disimpan.
- [ ] Non-sewa otomatis membersihkan `jangka_waktu_sewa`.
- [ ] Non-sewa otomatis membersihkan `satuan_waktu_sewa`.

### Permission

- [ ] index/show memakai `view_any_data::pembanding`.
- [ ] create memakai `create_data::pembanding`.
- [ ] update memakai `update_data::pembanding`.
- [ ] direct delete memakai `delete_data::pembanding`.
- [ ] delete request minimal mengikuti akses lihat pembanding.

### Definition of Done

- [ ] Test API create jual berhasil.
- [ ] Test API create sewa tanpa periode gagal.
- [ ] Test API create sewa dengan `Bulan` berhasil.
- [ ] Test API create sewa dengan `Tahun` berhasil.
- [ ] Test API non-sewa membersihkan field periode sewa.
- [ ] Test API upload image create berhasil.
- [ ] Test API upload image update berhasil.
- [ ] Test API user tanpa permission mendapat 403 JSON.
- [ ] Test API validasi gagal mendapat 422 JSON.

## Phase 4 - CRUD Master Data API

Tujuan: mobile bisa menambah master data ketika nilai tidak tersedia, tanpa memakai endpoint web.

### Endpoint

- [ ] `POST /api/v1/dictionaries/{type}`
- [ ] `PUT /api/v1/dictionaries/{type}/{id}`
- [ ] `PATCH /api/v1/dictionaries/{type}/{id}`
- [ ] `DELETE /api/v1/dictionaries/{type}/{id}`
- [ ] `POST /api/v1/dictionaries/{type}/reorder`

### Checklist

- [ ] Port logic dari `App\DictionaryApiController` ke namespace API atau service bersama.
- [ ] Gunakan `DictionaryTypeMap` sebagai satu-satunya resolver type.
- [ ] Normalisasi `name`.
- [ ] Generate `slug` dari `name`.
- [ ] Validasi unique slug.
- [ ] Clear cache setelah create.
- [ ] Clear cache setelah update.
- [ ] Clear cache setelah delete.
- [ ] Clear cache setelah reorder.
- [ ] Batasi `badge_color_token` hanya untuk type yang mendukung.
- [ ] Batasi `marker_icon_url` hanya untuk type yang mendukung.
- [ ] Putuskan apakah surveyor boleh create master data langsung.
- [ ] Pastikan update/delete/reorder tidak terbuka untuk surveyor kecuali memang diputuskan.

### Permission

- [ ] Create minimal `manage_master_data` atau role yang diputuskan.
- [ ] Update minimal `manage_master_data`.
- [ ] Delete minimal `manage_master_data`.
- [ ] Reorder minimal `manage_master_data`.

### Definition of Done

- [ ] Mobile bisa create master data baru.
- [ ] Master data baru muncul saat fetch dictionary berikutnya.
- [ ] Duplicate name/slug ditolak.
- [ ] User tanpa permission mendapat 403 JSON.

## Phase 5 - CRUD Lokasi API

Tujuan: mobile bisa menambah lokasi saat data wilayah belum tersedia.

### Endpoint

- [ ] `POST /api/v1/locations/provinces`
- [ ] `PUT /api/v1/locations/provinces/{province}`
- [ ] `PATCH /api/v1/locations/provinces/{province}`
- [ ] `DELETE /api/v1/locations/provinces/{province}`
- [ ] `POST /api/v1/locations/regencies`
- [ ] `PUT /api/v1/locations/regencies/{regency}`
- [ ] `PATCH /api/v1/locations/regencies/{regency}`
- [ ] `DELETE /api/v1/locations/regencies/{regency}`
- [ ] `POST /api/v1/locations/districts`
- [ ] `PUT /api/v1/locations/districts/{district}`
- [ ] `PATCH /api/v1/locations/districts/{district}`
- [ ] `DELETE /api/v1/locations/districts/{district}`
- [ ] `POST /api/v1/locations/villages`
- [ ] `PUT /api/v1/locations/villages/{village}`
- [ ] `PATCH /api/v1/locations/villages/{village}`
- [ ] `DELETE /api/v1/locations/villages/{village}`

### Checklist

- [ ] Reuse `LocationIdGenerator` untuk id kabupaten.
- [ ] Reuse `LocationIdGenerator` untuk id kecamatan.
- [ ] Reuse `LocationIdGenerator` untuk id desa.
- [ ] Normalisasi nama lokasi ke uppercase seperti web.
- [ ] Validasi parent id provinsi untuk kabupaten.
- [ ] Validasi parent id kabupaten untuk kecamatan.
- [ ] Validasi parent id kecamatan untuk desa.
- [ ] Tambahkan duplicate check provinsi berdasarkan normalized name.
- [ ] Tambahkan duplicate check kabupaten berdasarkan parent + normalized name.
- [ ] Tambahkan duplicate check kecamatan berdasarkan parent + normalized name.
- [ ] Tambahkan duplicate check desa berdasarkan parent + normalized name.
- [ ] Tolak delete provinsi jika masih punya kabupaten.
- [ ] Tolak delete kabupaten jika masih punya kecamatan.
- [ ] Tolak delete kecamatan jika masih punya desa.
- [ ] Tolak delete lokasi jika masih dipakai data pembanding.
- [ ] Putuskan apakah surveyor boleh create lokasi langsung.

### Permission

- [ ] Create minimal `manage_master_data` atau role yang diputuskan.
- [ ] Update minimal `manage_master_data`.
- [ ] Delete minimal `manage_master_data`.

### Definition of Done

- [ ] Mobile bisa create province.
- [ ] Mobile bisa create regency sesuai province.
- [ ] Mobile bisa create district sesuai regency.
- [ ] Mobile bisa create village sesuai district.
- [ ] Duplicate location ditolak.
- [ ] Lokasi yang masih dipakai pembanding tidak bisa dihapus.
- [ ] User tanpa permission mendapat 403 JSON.

## Phase 6 - Audit, Moderasi, dan Konsistensi Error

Tujuan: operasi mobile bisa ditelusuri dan gagal dengan format yang bisa diproses aplikasi.

### Checklist

- [ ] Semua endpoint API menerima `Accept: application/json`.
- [ ] Unauthorized mengembalikan 401 JSON.
- [ ] Forbidden mengembalikan 403 JSON.
- [ ] Validation error mengembalikan 422 JSON.
- [ ] Not found mengembalikan 404 JSON.
- [ ] Activity log mencatat create pembanding dari API.
- [ ] Activity log mencatat update pembanding dari API.
- [ ] Activity log mencatat delete pembanding dari API jika direct delete dipakai.
- [ ] Delete request API memakai model `PembandingDeleteRequest`.
- [ ] Tambahkan rate limit create pembanding.
- [ ] Tambahkan rate limit update pembanding.
- [ ] Tambahkan rate limit create master data.
- [ ] Tambahkan rate limit create lokasi.

### Definition of Done

- [ ] Tidak ada endpoint mobile yang mengembalikan HTML error page.
- [ ] Semua write bisa dilacak ke user token.
- [ ] Semua response error bisa diproses Flutter secara konsisten.

## Phase 7 - Dokumentasi dan Test Gate

Tujuan: backend dan Flutter tidak saling tebak.

### Dokumentasi

- [ ] Update `docs/API.md`.
- [ ] Tambahkan contoh login.
- [ ] Tambahkan contoh `/me` dengan roles dan permissions.
- [ ] Tambahkan contoh request multipart create pembanding.
- [ ] Tambahkan contoh request update pembanding.
- [ ] Tambahkan contoh request delete request.
- [ ] Tambahkan contoh request sewa harga per bulan.
- [ ] Tambahkan contoh request sewa harga per 3 bulan.
- [ ] Tambahkan contoh request sewa harga per tahun.
- [ ] Tambahkan daftar dictionary type valid.
- [ ] Tambahkan daftar permission untuk UI mobile.
- [ ] Tambahkan contoh create master data.
- [ ] Tambahkan contoh create lokasi.

### Test

- [ ] Feature test auth roles/permissions.
- [ ] Feature test pembanding API create.
- [ ] Feature test pembanding API update.
- [ ] Feature test pembanding API delete request.
- [ ] Feature test pembanding API direct delete.
- [ ] Feature test rent fields.
- [ ] Feature test master data API.
- [ ] Feature test location API.
- [ ] Regression test JSON 401.
- [ ] Regression test JSON 403.
- [ ] Regression test JSON 422.

### Command Gate

- [ ] Jalankan `vendor\bin\pint.bat` untuk file PHP yang disentuh.
- [ ] Jalankan `php artisan test`.
- [ ] Jalankan `npm run build` jika frontend ikut berubah.
- [ ] Pastikan dokumentasi cocok dengan route aktual.

## Urutan Eksekusi Prioritas

### Prioritas 1 - Auth Context dan Read Dependency

- [ ] Selesaikan `/me` roles/permissions.
- [ ] Selesaikan read lokasi API.
- [ ] Rapikan response dictionary bila standar wrapper dipilih.
- [ ] Update dokumentasi awal.

Alasan: tanpa ini Flutter akan hardcode permission, dictionary, dan lokasi. Itu bukan hemat waktu, itu memindahkan masalah ke mobile.

### Prioritas 2 - Create Pembanding

- [ ] Selesaikan `POST /api/v1/pembandings`.
- [ ] Selesaikan upload image.
- [ ] Selesaikan validasi semua `jenis_listing`.
- [ ] Selesaikan validasi khusus `sewa`.
- [ ] Tambahkan test create.

Alasan: nilai utama mobile adalah input lapangan. Update/delete penting, tapi create adalah bottleneck pertama.

### Prioritas 3 - Update Pembanding dan Delete Request

- [ ] Selesaikan update pembanding.
- [ ] Selesaikan delete request.
- [ ] Selesaikan direct delete khusus permission.
- [ ] Tambahkan test permission.

Alasan: setelah input jalan, user butuh memperbaiki data salah dan mengajukan penghapusan tanpa merusak kontrol data.

### Prioritas 4 - Create Master Data dan Lokasi

- [ ] Selesaikan create master data.
- [ ] Selesaikan create lokasi.
- [ ] Selesaikan duplicate prevention.
- [ ] Selesaikan permission gate.

Alasan: ini produktif, tapi berisiko mengotori database. Jangan dibuka sebelum create pembanding stabil.

### Prioritas 5 - Update/Delete Master Data dan Lokasi

- [ ] Selesaikan update master data jika benar-benar dibutuhkan.
- [ ] Selesaikan delete master data jika benar-benar dibutuhkan.
- [ ] Selesaikan update lokasi jika benar-benar dibutuhkan.
- [ ] Selesaikan delete lokasi jika benar-benar dibutuhkan.

Alasan: tidak semua user mobile perlu kemampuan ini. Membukanya terlalu cepat meningkatkan risiko data referensi rusak.

## Sprint Tracking

### Sprint 1 - Fondasi Mobile API

- [ ] `/api/auth/me` mengembalikan roles.
- [ ] `/api/auth/me` mengembalikan permissions.
- [ ] API lokasi read tersedia.
- [ ] Form dependency mobile lengkap.
- [ ] `docs/API.md` mencatat kontrak awal.
- [ ] Test Sprint 1 pass.

Output:

- [ ] Mobile bisa login.
- [ ] Mobile tahu hak akses user.
- [ ] Mobile bisa fetch master data.
- [ ] Mobile bisa fetch lokasi.
- [ ] Mobile bisa render form create.

### Sprint 2 - Create Pembanding

- [ ] `POST /api/v1/pembandings` tersedia.
- [ ] Upload image multipart berjalan.
- [ ] Semua jenis listing didukung.
- [ ] `sewa` wajib punya periode harga.
- [ ] Test create pembanding lengkap.
- [ ] Dokumentasi create pembanding selesai.

Output:

- [ ] Mobile bisa input data pembanding lapangan.

### Sprint 3 - Update dan Delete Flow

- [ ] Update pembanding tersedia.
- [ ] Delete request tersedia.
- [ ] Direct delete tersedia hanya untuk permission yang benar.
- [ ] Error 403 konsisten JSON.
- [ ] Error 422 konsisten JSON.
- [ ] Test Sprint 3 pass.

Output:

- [ ] Mobile bisa memperbaiki data.
- [ ] Mobile bisa mengajukan penghapusan data.

### Sprint 4 - Master Data dan Lokasi Write

- [ ] Create master data API tersedia.
- [ ] Create lokasi API tersedia.
- [ ] Duplicate prevention master data tersedia.
- [ ] Duplicate prevention lokasi tersedia.
- [ ] Cache invalidation dictionary tersedia.
- [ ] Permission write ketat.
- [ ] Test Sprint 4 pass.

Output:

- [ ] Mobile bisa menambah nilai referensi saat data belum ada.
- [ ] Mobile tidak memakai endpoint web `/home/master-data/...`.

### Sprint 5 - Hardening

- [ ] Update/delete master data diputuskan.
- [ ] Update/delete lokasi diputuskan.
- [ ] Rate limit write tersedia.
- [ ] Audit behavior dicek.
- [ ] Dokumentasi final.
- [ ] Full test pass.

Output:

- [ ] API layak dipakai untuk pilot mobile.

## Keputusan Yang Masih Harus Diambil

- [!] Putuskan apakah surveyor boleh create master data dan lokasi langsung, atau hanya mengusulkan.
- [!] Putuskan apakah mobile butuh offline draft.
- [!] Putuskan apakah create pembanding perlu idempotency key untuk retry.
- [!] Putuskan apakah delete pembanding dari mobile boleh direct delete untuk role tertentu.
- [!] Putuskan apakah semua API response wajib memakai wrapper `status/message/data`.
- [!] Putuskan apakah update/delete master data perlu tersedia di mobile tahap awal.
- [!] Putuskan apakah update/delete lokasi perlu tersedia di mobile tahap awal.

Rekomendasi saat ini:

- [ ] Surveyor boleh create lokasi/master data dengan duplicate check ketat.
- [ ] Update/delete master data hanya admin atau role pengelola.
- [ ] Update/delete lokasi hanya admin atau role pengelola.
- [ ] Delete pembanding user biasa lewat delete request.
- [ ] Direct delete pembanding hanya untuk permission tinggi.
- [ ] Semua API response dibuat konsisten memakai wrapper.

## Risiko Utama

- [ ] Risiko kualitas data master/lokasi dikontrol dengan duplicate prevention.
- [ ] Risiko permission drift dikontrol dengan policy/middleware yang konsisten.
- [ ] Risiko validasi drift dikontrol dengan shared validation rules.
- [ ] Risiko dokumentasi basi dikontrol dengan update `docs/API.md` setiap phase.
- [ ] Risiko Flutter membuat workaround dikontrol dengan menunggu Sprint 1 dan Sprint 2 sebelum submit data.

## Kriteria API Disebut Ready Untuk Mobile

- [ ] Login berjalan dengan Bearer token.
- [ ] Refresh token berjalan.
- [ ] Logout berjalan.
- [ ] `/me` memuat roles.
- [ ] `/me` memuat permissions.
- [ ] Mobile bisa membaca semua dictionary untuk form.
- [ ] Mobile bisa membaca semua lokasi untuk form.
- [ ] Mobile bisa create pembanding jual.
- [ ] Mobile bisa create pembanding sewa per bulan.
- [ ] Mobile bisa create pembanding sewa per beberapa bulan.
- [ ] Mobile bisa create pembanding sewa per tahun.
- [ ] Mobile bisa update pembanding.
- [ ] Mobile bisa mengajukan hapus pembanding.
- [ ] Mobile bisa direct delete sesuai permission.
- [ ] Mobile bisa create master data bila tidak tersedia.
- [ ] Mobile bisa create lokasi bila tidak tersedia.
- [ ] Semua error API berbentuk JSON.
- [ ] Semua endpoint write punya test permission.
- [ ] Semua endpoint write punya test validation.
- [ ] `php artisan test` pass.
- [ ] `docs/API.md` mencerminkan route aktual.

## Parallel Work Yang Aman Untuk Flutter

Flutter boleh mulai dari item ini sebelum backend CRUD selesai:

- [ ] Auth UI.
- [ ] Token storage.
- [ ] Refresh token handling.
- [ ] Read dictionary.
- [ ] Read location.
- [ ] Local draft form.
- [ ] Role-aware navigation dari `/me` setelah Phase 1 selesai.

Flutter belum boleh mengunci flow submit sebelum ini selesai:

- [ ] `POST /api/v1/pembandings`
- [ ] Dokumentasi create pembanding.
- [ ] Test create pembanding.
- [ ] Validasi `sewa` di API.
