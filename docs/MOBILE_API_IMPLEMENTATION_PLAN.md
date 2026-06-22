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
- [x] Login mobile dibatasi hanya untuk role `data_contributor`, `pimpinan`, dan `surveyor`.
- [x] Mobile bisa refresh token.
- [x] Mobile bisa membaca profil user login.
- [x] Mobile bisa membaca dictionary.
- [x] Mobile bisa membaca list/detail data pembanding.
- [x] Mobile bisa membaca role dan permission user dari API.
- [x] Mobile bisa membaca lokasi dari `/api/v1`.
- [x] Mobile bisa create data pembanding untuk semua `jenis_listing`.
- [x] Mobile bisa create data pembanding `sewa` dengan periode harga sewa eksplisit.
- [x] Mobile bisa update data pembanding.
- [x] Mobile bisa mengedit profil (nama, email) dan password.
- [x] Mobile bisa melihat history perubahan data pembanding.
- [x] Mobile bisa mengajukan hapus data pembanding.
- [x] Mobile bisa delete langsung jika permission mengizinkan.
- [x] (Dibatalkan) Mobile bisa menambah master data jika nilai belum tersedia.
- [x] (Dibatalkan) Mobile bisa menambah lokasi jika wilayah belum tersedia.
- [x] Semua endpoint write punya permission check yang konsisten dengan web.
- [x] Semua endpoint API mengembalikan JSON, termasuk 401, 403, dan 422.
- [x] Dokumentasi API cukup jelas untuk developer mobile tanpa membaca source Laravel.

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

- [x] `PUT /api/auth/profile`
- [x] `PUT /api/auth/profile/password`
- [x] `POST /api/v1/pembandings`
- [x] `PUT/PATCH /api/v1/pembandings/{id}`
- [x] `POST /api/v1/pembandings/{id}` dengan `_method=PUT` untuk multipart mobile.
- [x] `GET /api/v1/pembandings/{id}/history`
- [x] `POST /api/v1/pembandings/{id}/delete-request`
- [x] `DELETE /api/v1/pembandings/{id}`
- [x] (Dibatalkan) CRUD master data di jalur `/api/v1`.
- [x] (Dibatalkan) CRUD lokasi di jalur `/api/v1`.
- [x] Response `/api/auth/me` memuat `permissions`.
- [x] Dokumentasi request body create/update pembanding.
- [x] Test API untuk create/update/delete/history pembanding dan profile.

## Prinsip Implementasi

- [x] Validasi web dan API untuk pembanding memakai sumber aturan yang sama atau diekstrak ke basis bersama.
- [x] Perbaikan field `sewa` yang sudah ada tetap berlaku di API.
- [x] Endpoint mobile tidak memakai route `/home/master-data/...`.
- [x] Endpoint mobile memakai Bearer token, bukan web session.
- [x] Endpoint mobile tidak mengembalikan redirect, flash session, HTML 403, atau halaman Inertia.
- [x] (Dibatalkan) Write master data dan lokasi wajib permission-gated.
- [x] (Dibatalkan) Write master data dan lokasi wajib punya duplicate prevention.
- [x] Semua write tercatat ke user token.
- [x] Rilis dilakukan bertahap, bukan menunggu semua sempurna.

## Phase 0 - Tetapkan Kontrak API

Tujuan: kontrak endpoint disepakati sebelum implementasi besar.

### Checklist

- [x] Tetapkan format response sukses.
- [x] Tetapkan format response error validasi.
- [x] Tetapkan format response 401.
- [x] Tetapkan format response 403.
- [x] Tetapkan standar pagination.
- [x] Tetapkan standar upload gambar.
- [x] Tetapkan format tanggal.
- [x] Tetapkan strategi update multipart: `POST /{id}` + `_method=PUT`.
- [x] Tetapkan strategi update JSON: `PUT/PATCH /{id}`.
- [x] Tetapkan policy delete user biasa: delete request.
- [x] Tetapkan policy delete user berizin: direct delete.
- [x] Tetapkan apakah force delete dibutuhkan di API atau tetap admin-only web.
- [x] Update `docs/API.md` dengan kontrak awal.

### Definition of Done

- [x] `docs/API.md` punya daftar endpoint baru.
- [x] `docs/API.md` punya request body minimum.
- [x] `docs/API.md` punya contoh response sukses.
- [x] `docs/API.md` punya contoh response error.
- [x] `docs/API.md` mencantumkan permission per endpoint.

## Phase 1 - Perkuat Auth Context Untuk Mobile

Tujuan: mobile tahu user login punya role dan permission apa.

### Endpoint

- [x] `GET /api/auth/me`
- [x] `POST /api/auth/login`

### Checklist

- [x] Tambahkan `permissions` ke `UserResource`.
- [x] Pastikan response login memakai struktur user yang sama dengan `/me`.
- [x] Pastikan permission `view_any_data::pembanding` muncul jika user punya akses lihat.
- [x] Pastikan permission `create_data::pembanding` muncul jika user punya akses create.
- [x] Pastikan permission `update_data::pembanding` muncul jika user punya akses update.
- [x] Pastikan permission `delete_data::pembanding` muncul jika user punya akses delete.
- [x] Pastikan permission `manage_master_data` muncul jika user punya akses master data.
- [x] Pastikan hanya role `data_contributor`, `pimpinan`, dan `surveyor` yang bisa login ke mobile API.
- [x] Tambahkan test user tanpa permission.
- [x] Tambahkan test user surveyor.
- [x] Tambahkan test user super admin.

### Definition of Done

- [x] Flutter bisa menampilkan atau menyembunyikan tombol create berdasarkan `/me`.
- [x] Flutter bisa menampilkan atau menyembunyikan tombol edit berdasarkan `/me`.
- [x] Flutter bisa menampilkan atau menyembunyikan tombol delete berdasarkan `/me`.
- [x] Flutter bisa menampilkan atau menyembunyikan menu master data/lokasi berdasarkan `/me`.

## Phase 2 - API Read Untuk Form Dependency

Tujuan: mobile bisa membangun form pembanding tanpa hardcode master data dan lokasi.

### Endpoint

- [x] `GET /api/v1/dictionaries/{type}`
- [x] `GET /api/v1/locations/provinces`
- [x] `GET /api/v1/locations/regencies?province_id=...&q=...`
- [x] `GET /api/v1/locations/districts?regency_id=...&q=...`
- [x] `GET /api/v1/locations/villages?district_id=...&q=...`
- [x] (Tidak diperlukan saat ini) `GET /api/v1/pembandings/form-options`.

### Checklist

- [x] Buat controller API khusus lokasi atau bungkus ulang logic lokasi yang ada.
- [x] Reuse limit aman untuk pencarian lokasi.
- [x] Pastikan response lokasi memakai format JSON API yang konsisten.
- [x] Pastikan dictionary tidak lagi mengembalikan array mentah jika standar wrapper dipilih.
- [x] Pastikan `active_only=false` hanya boleh dipakai user yang berhak.
- [x] Tambahkan test read province.
- [x] Tambahkan test read regency by province.
- [x] Tambahkan test read district by regency.
- [x] Tambahkan test read village by district.

### Definition of Done

- [x] Mobile bisa membuka form create pembanding dari nol hanya dengan data API.
- [x] Mobile tidak perlu hardcode dictionary.
- [x] Mobile tidak perlu hardcode lokasi.

## Phase 3 - CRUD Pembanding API

Tujuan: mobile bisa create/update data pembanding untuk semua `jenis_listing`.

### Endpoint

- [x] `POST /api/v1/pembandings`
- [x] `POST /api/v1/pembandings/{id}` dengan `_method=PUT`
- [x] `PUT /api/v1/pembandings/{id}`
- [x] `PATCH /api/v1/pembandings/{id}`
- [x] `POST /api/v1/pembandings/{id}/delete-request`
- [x] `DELETE /api/v1/pembandings/{id}`

### Checklist

- [x] Buat method create pembanding API.
- [x] Buat method update pembanding API.
- [x] Buat method delete request pembanding API.
- [x] Buat method direct delete pembanding API.
- [x] Reuse atau ekstrak validasi `PembandingStoreRequest`.
- [x] Reuse atau ekstrak validasi `PembandingUpdateRequest`.
- [x] Pastikan create menyimpan `created_by` dari token user.
- [x] Pastikan update tidak menghapus image lama jika tidak ada file baru.
- [x] Pastikan upload image memakai disk/path yang sama dengan web.
- [x] Pastikan create mengembalikan `PembandingResource`.
- [x] Pastikan update mengembalikan `PembandingResource`.
- [x] Pastikan request hapus memakai `PembandingDeleteRequest` yang sama dengan web.
- [x] Pastikan direct delete hanya untuk `delete_data::pembanding`.

### Checklist Khusus `jenis_listing = sewa`

- [x] `harga` wajib diisi.
- [x] `jangka_waktu_sewa` wajib diisi jika listing sewa.
- [x] `satuan_waktu_sewa` wajib diisi jika listing sewa.
- [x] `satuan_waktu_sewa` hanya boleh `Bulan` atau `Tahun`.
- [x] Harga per bulan bisa disimpan.
- [x] Harga per beberapa bulan bisa disimpan, contoh 3 Bulan.
- [x] Harga per tahun bisa disimpan.
- [x] Non-sewa otomatis membersihkan `jangka_waktu_sewa`.
- [x] Non-sewa otomatis membersihkan `satuan_waktu_sewa`.

### Permission

- [x] index/show memakai `view_any_data::pembanding` / akses lihat pembanding.
- [x] create memakai `create_data::pembanding`.
- [x] update memakai `update_data::pembanding` atau `update_own_data::pembanding`.
- [x] direct delete memakai `delete_data::pembanding`.
- [x] delete request minimal mengikuti akses lihat pembanding.

### Definition of Done

- [x] Test API create jual berhasil.
- [x] Test API create sewa tanpa periode gagal.
- [x] Test API create sewa dengan `Bulan` berhasil.
- [x] Test API create sewa dengan `Tahun` berhasil.
- [x] Test API non-sewa membersihkan field periode sewa.
- [x] Test API upload image create berhasil.
- [x] Test API upload image update berhasil.
- [x] Test API user tanpa permission mendapat 403 JSON.
- [x] Test API validasi gagal mendapat 422 JSON.

## Phase 4 - CRUD Master Data API (DIBATALKAN)

Tujuan: mobile bisa menambah master data ketika nilai tidak tersedia, tanpa memakai endpoint web. 
**Status**: Dibatalkan atas permintaan user, master data tidak perlu dikelola via mobile.

### Endpoint

- [x] (Dibatalkan) `POST /api/v1/dictionaries/{type}`
- [x] (Dibatalkan) `PUT /api/v1/dictionaries/{type}/{id}`
- [x] (Dibatalkan) `PATCH /api/v1/dictionaries/{type}/{id}`
- [x] (Dibatalkan) `DELETE /api/v1/dictionaries/{type}/{id}`
- [x] (Dibatalkan) `POST /api/v1/dictionaries/{type}/reorder`

### Checklist

- [x] (Dibatalkan) Port logic dari `App\DictionaryApiController` ke namespace API atau service bersama.
- [x] (Dibatalkan) Gunakan `DictionaryTypeMap` sebagai satu-satunya resolver type.
- [x] (Dibatalkan) Normalisasi `name`.
- [x] (Dibatalkan) Generate `slug` dari `name`.
- [x] (Dibatalkan) Validasi unique slug.
- [x] (Dibatalkan) Clear cache setelah create.
- [x] (Dibatalkan) Clear cache setelah update.
- [x] (Dibatalkan) Clear cache setelah delete.
- [x] (Dibatalkan) Clear cache setelah reorder.
- [x] (Dibatalkan) Batasi `badge_color_token` hanya untuk type yang mendukung.
- [x] (Dibatalkan) Batasi `marker_icon_url` hanya untuk type yang mendukung.
- [x] (Dibatalkan) Putuskan apakah surveyor boleh create master data langsung.
- [x] (Dibatalkan) Pastikan update/delete/reorder tidak terbuka untuk surveyor kecuali memang diputuskan.

### Permission

- [x] (Dibatalkan) Create minimal `manage_master_data` atau role yang diputuskan.
- [x] (Dibatalkan) Update minimal `manage_master_data`.
- [x] (Dibatalkan) Delete minimal `manage_master_data`.
- [x] (Dibatalkan) Reorder minimal `manage_master_data`.

### Definition of Done

- [x] (Dibatalkan) Mobile bisa create master data baru.
- [x] (Dibatalkan) Master data baru muncul saat fetch dictionary berikutnya.
- [x] (Dibatalkan) Duplicate name/slug ditolak.
- [x] (Dibatalkan) User tanpa permission mendapat 403 JSON.

## Phase 5 - CRUD Lokasi API (DIBATALKAN)

Tujuan: mobile bisa menambah lokasi saat data wilayah belum tersedia.
**Status**: Dibatalkan atas permintaan user, master data/lokasi tidak perlu dikelola via mobile.

### Endpoint

- [x] (Dibatalkan) `POST /api/v1/locations/provinces`
- [x] (Dibatalkan) `PUT /api/v1/locations/provinces/{province}`
- [x] (Dibatalkan) `PATCH /api/v1/locations/provinces/{province}`
- [x] (Dibatalkan) `DELETE /api/v1/locations/provinces/{province}`
- [x] (Dibatalkan) `POST /api/v1/locations/regencies`
- [x] (Dibatalkan) `PUT /api/v1/locations/regencies/{regency}`
- [x] (Dibatalkan) `PATCH /api/v1/locations/regencies/{regency}`
- [x] (Dibatalkan) `DELETE /api/v1/locations/regencies/{regency}`
- [x] (Dibatalkan) `POST /api/v1/locations/districts`
- [x] (Dibatalkan) `PUT /api/v1/locations/districts/{district}`
- [x] (Dibatalkan) `PATCH /api/v1/locations/districts/{district}`
- [x] (Dibatalkan) `DELETE /api/v1/locations/districts/{district}`
- [x] (Dibatalkan) `POST /api/v1/locations/villages`
- [x] (Dibatalkan) `PUT /api/v1/locations/villages/{village}`
- [x] (Dibatalkan) `PATCH /api/v1/locations/villages/{village}`
- [x] (Dibatalkan) `DELETE /api/v1/locations/villages/{village}`

### Checklist

- [x] (Dibatalkan) Reuse `LocationIdGenerator` untuk id kabupaten.
- [x] (Dibatalkan) Reuse `LocationIdGenerator` untuk id kecamatan.
- [x] (Dibatalkan) Reuse `LocationIdGenerator` untuk id desa.
- [x] (Dibatalkan) Normalisasi nama lokasi ke uppercase seperti web.
- [x] (Dibatalkan) Validasi parent id provinsi untuk kabupaten.
- [x] (Dibatalkan) Validasi parent id kabupaten untuk kecamatan.
- [x] (Dibatalkan) Validasi parent id kecamatan untuk desa.
- [x] (Dibatalkan) Tambahkan duplicate check provinsi berdasarkan normalized name.
- [x] (Dibatalkan) Tambahkan duplicate check kabupaten berdasarkan parent + normalized name.
- [x] (Dibatalkan) Tambahkan duplicate check kecamatan berdasarkan parent + normalized name.
- [x] (Dibatalkan) Tambahkan duplicate check desa berdasarkan parent + normalized name.
- [x] (Dibatalkan) Tolak delete provinsi jika masih punya kabupaten.
- [x] (Dibatalkan) Tolak delete kabupaten jika masih punya kecamatan.
- [x] (Dibatalkan) Tolak delete kecamatan jika masih punya desa.
- [x] (Dibatalkan) Tolak delete lokasi jika masih dipakai data pembanding.
- [x] (Dibatalkan) Putuskan apakah surveyor boleh create lokasi langsung.

### Permission

- [x] (Dibatalkan) Create minimal `manage_master_data` atau role yang diputuskan.
- [x] (Dibatalkan) Update minimal `manage_master_data`.
- [x] (Dibatalkan) Delete minimal `manage_master_data`.

### Definition of Done

- [x] (Dibatalkan) Mobile bisa create province.
- [x] (Dibatalkan) Mobile bisa create regency sesuai province.
- [x] (Dibatalkan) Mobile bisa create district sesuai regency.
- [x] (Dibatalkan) Mobile bisa create village sesuai district.
- [x] (Dibatalkan) Duplicate location ditolak.
- [x] (Dibatalkan) Lokasi yang masih dipakai pembanding tidak bisa dihapus.
- [x] (Dibatalkan) User tanpa permission mendapat 403 JSON.

## Phase 6 - Audit, Moderasi, dan Konsistensi Error

Tujuan: operasi mobile bisa ditelusuri dan gagal dengan format yang bisa diproses aplikasi.

### Checklist

- [x] Semua endpoint API menerima `Accept: application/json`.
- [x] Unauthorized mengembalikan 401 JSON.
- [x] Forbidden mengembalikan 403 JSON.
- [x] Validation error mengembalikan 422 JSON.
- [x] Not found mengembalikan 404 JSON.
- [x] Activity log mencatat create pembanding dari API.
- [x] Activity log mencatat update pembanding dari API.
- [x] Activity log mencatat delete pembanding dari API jika direct delete dipakai.
- [x] Delete request API memakai model `PembandingDeleteRequest`.
- [x] Tambahkan rate limit create pembanding.
- [x] Tambahkan rate limit update pembanding.
- [x] (Dibatalkan) Tambahkan rate limit create master data.
- [x] (Dibatalkan) Tambahkan rate limit create lokasi.

### Definition of Done

- [x] Tidak ada endpoint mobile yang mengembalikan HTML error page.
- [x] Semua write bisa dilacak ke user token.
- [x] Semua response error bisa diproses Flutter secara konsisten.

## Phase 7 - Dokumentasi dan Test Gate

Tujuan: backend dan Flutter tidak saling tebak.

### Dokumentasi

- [x] Update `docs/API.md`.
- [x] Tambahkan contoh login.
- [x] Tambahkan contoh `/me` dengan roles dan permissions.
- [x] Tambahkan contoh request multipart create pembanding.
- [x] Tambahkan contoh request update pembanding.
- [x] Tambahkan contoh request delete request.
- [x] Tambahkan contoh request sewa harga per bulan.
- [x] Tambahkan contoh request sewa harga per 3 bulan.
- [x] Tambahkan contoh request sewa harga per tahun.
- [x] Tambahkan daftar dictionary type valid.
- [x] Tambahkan daftar permission untuk UI mobile.
- [x] (Dibatalkan) Tambahkan contoh create master data.
- [x] (Dibatalkan) Tambahkan contoh create lokasi.

### Test

- [x] Feature test auth roles/permissions.
- [x] Feature test pembanding API create.
- [x] Feature test pembanding API update.
- [x] Feature test pembanding API delete request.
- [x] Feature test pembanding API direct delete.
- [x] Feature test rent fields.
- [x] Feature test master data API.
- [x] Feature test location API.
- [x] Regression test JSON 401.
- [x] Regression test JSON 403.
- [x] Regression test JSON 422.

### Command Gate

- [x] Jalankan `vendor\bin\pint.bat` untuk file PHP yang disentuh.
- [x] Jalankan `php artisan test`.
- [x] (Tidak diperlukan, frontend tidak disentuh) Jalankan `npm run build` jika frontend ikut berubah.
- [x] Pastikan dokumentasi cocok dengan route aktual.

## Urutan Eksekusi Prioritas

### Prioritas 1 - Auth Context dan Read Dependency

- [x] Selesaikan `/me` roles/permissions.
- [x] Selesaikan read lokasi API.
- [x] Rapikan response dictionary bila standar wrapper dipilih.
- [x] Update dokumentasi awal.

Alasan: tanpa ini Flutter akan hardcode permission, dictionary, dan lokasi. Itu bukan hemat waktu, itu memindahkan masalah ke mobile.

### Prioritas 2 - Create Pembanding

- [x] Selesaikan `POST /api/v1/pembandings`.
- [x] Selesaikan upload image.
- [x] Selesaikan validasi semua `jenis_listing`.
- [x] Selesaikan validasi khusus `sewa`.
- [x] Tambahkan test create.

Alasan: nilai utama mobile adalah input lapangan. Update/delete penting, tapi create adalah bottleneck pertama.

### Prioritas 3 - Update Pembanding dan Delete Request

- [x] Selesaikan update pembanding.
- [x] Selesaikan delete request.
- [x] Selesaikan direct delete khusus permission.
- [x] Tambahkan test permission.

Alasan: setelah input jalan, user butuh memperbaiki data salah dan mengajukan penghapusan tanpa merusak kontrol data.

### Prioritas 4 - Create Master Data dan Lokasi

- [x] (Dibatalkan) Selesaikan create master data.
- [x] (Dibatalkan) Selesaikan create lokasi.
- [x] (Dibatalkan) Selesaikan duplicate prevention.
- [x] (Dibatalkan) Selesaikan permission gate.

Alasan: ini produktif, tapi berisiko mengotori database. Jangan dibuka sebelum create pembanding stabil.

### Prioritas 5 - Update/Delete Master Data dan Lokasi

- [x] (Dibatalkan) Selesaikan update master data jika benar-benar dibutuhkan.
- [x] (Dibatalkan) Selesaikan delete master data jika benar-benar dibutuhkan.
- [x] (Dibatalkan) Selesaikan update lokasi jika benar-benar dibutuhkan.
- [x] (Dibatalkan) Selesaikan delete lokasi jika benar-benar dibutuhkan.

Alasan: tidak semua user mobile perlu kemampuan ini. Membukanya terlalu cepat meningkatkan risiko data referensi rusak.

## Sprint Tracking

### Sprint 1 - Fondasi Mobile API

- [x] `/api/auth/me` mengembalikan roles.
- [x] `/api/auth/me` mengembalikan permissions.
- [x] `/api/auth/login` mengembalikan roles dan permissions.
- [x] `/api/auth/login` menolak role non-mobile.
- [x] API lokasi read tersedia.
- [x] Form dependency mobile lengkap.
- [x] `docs/API.md` mencatat kontrak awal.
- [x] Test Sprint 1 pass.

Output:

- [x] Mobile bisa login.
- [x] Mobile tahu hak akses user.
- [x] Mobile bisa fetch master data.
- [x] Mobile bisa fetch lokasi.
- [x] Mobile bisa render form create.

### Sprint 2 - Create Pembanding

- [x] `POST /api/v1/pembandings` tersedia.
- [x] Upload image multipart berjalan.
- [x] Semua jenis listing didukung.
- [x] `sewa` wajib punya periode harga.
- [x] Test create pembanding lengkap.
- [x] Dokumentasi create pembanding selesai.

Output:

- [x] Mobile bisa input data pembanding lapangan.

### Sprint 3 - Update dan Delete Flow

- [x] Update pembanding tersedia.
- [x] Delete request tersedia.
- [x] Direct delete tersedia hanya untuk permission yang benar.
- [x] Error 403 konsisten JSON.
- [x] Error 422 konsisten JSON.
- [x] Test Sprint 3 pass.

Output:

- [x] Mobile bisa memperbaiki data.
- [x] Mobile bisa mengajukan penghapusan data.

### Sprint 4 - Master Data dan Lokasi Write

- [x] (Dibatalkan) Create master data API tersedia.
- [x] (Dibatalkan) Create lokasi API tersedia.
- [x] (Dibatalkan) Duplicate prevention master data tersedia.
- [x] (Dibatalkan) Duplicate prevention lokasi tersedia.
- [x] (Dibatalkan) Cache invalidation dictionary tersedia.
- [x] (Dibatalkan) Permission write ketat.
- [x] (Dibatalkan) Test Sprint 4 pass.

Output:

- [x] (Dibatalkan) Mobile bisa menambah nilai referensi saat data belum ada.
- [x] Mobile tidak memakai endpoint web `/home/master-data/...`.

### Sprint 5 - Hardening

- [x] (Dibatalkan) Update/delete master data diputuskan.
- [x] (Dibatalkan) Update/delete lokasi diputuskan.
- [x] Rate limit write tersedia.
- [x] Audit behavior dicek.
- [x] Dokumentasi final.
- [x] Full test pass.

Output:

- [x] API layak dipakai untuk pilot mobile.

## Keputusan Yang Masih Harus Diambil

- [x] (Dibatalkan) Putuskan apakah surveyor boleh create master data dan lokasi langsung, atau hanya mengusulkan.
- [!] Putuskan apakah mobile butuh offline draft.
- [!] Putuskan apakah create pembanding perlu idempotency key untuk retry.
- [x] Putuskan apakah delete pembanding dari mobile boleh direct delete untuk role tertentu.
- [x] Putuskan apakah semua API response wajib memakai wrapper `status/message/data`.
- [x] (Dibatalkan) Putuskan apakah update/delete master data perlu tersedia di mobile tahap awal.
- [x] (Dibatalkan) Putuskan apakah update/delete lokasi perlu tersedia di mobile tahap awal.

Rekomendasi saat ini:

- [x] (Dibatalkan) Surveyor boleh create lokasi/master data dengan duplicate check ketat.
- [x] (Dibatalkan) Update/delete master data hanya admin atau role pengelola.
- [x] (Dibatalkan) Update/delete lokasi hanya admin atau role pengelola.
- [x] Delete pembanding user biasa lewat delete request.
- [x] Direct delete pembanding hanya untuk permission tinggi.
- [x] Semua API response dibuat konsisten memakai wrapper.

## Risiko Utama

- [x] (Dibatalkan) Risiko kualitas data master/lokasi dikontrol dengan duplicate prevention.
- [x] Risiko permission drift dikontrol dengan policy/middleware yang konsisten.
- [x] Risiko validasi drift dikontrol dengan shared validation rules.
- [x] Risiko dokumentasi basi dikontrol dengan update `docs/API.md` setiap phase.
- [x] Risiko Flutter membuat workaround dikontrol dengan menunggu Sprint 1 dan Sprint 2 sebelum submit data.

## Kriteria API Disebut Ready Untuk Mobile

- [x] Login berjalan dengan Bearer token.
- [x] Refresh token berjalan.
- [x] Logout berjalan.
- [x] `/me` memuat roles.
- [x] `/me` memuat permissions.
- [x] Login mobile hanya untuk role `data_contributor`, `pimpinan`, dan `surveyor`.
- [x] Mobile bisa membaca semua dictionary untuk form.
- [x] Mobile bisa membaca semua lokasi untuk form.
- [x] Mobile bisa create pembanding jual.
- [x] Mobile bisa create pembanding sewa per bulan.
- [x] Mobile bisa create pembanding sewa per beberapa bulan.
- [x] Mobile bisa create pembanding sewa per tahun.
- [x] Mobile bisa update pembanding.
- [x] Mobile bisa mengajukan hapus pembanding.
- [x] Mobile bisa direct delete sesuai permission.
- [x] (Dibatalkan) Mobile bisa create master data bila tidak tersedia.
- [x] (Dibatalkan) Mobile bisa create lokasi bila tidak tersedia.
- [x] Semua error API berbentuk JSON.
- [x] Semua endpoint write punya test permission.
- [x] Semua endpoint write punya test validation.
- [x] `php artisan test` pass.
- [x] `docs/API.md` mencerminkan route aktual.

## Parallel Work Yang Aman Untuk Flutter

Flutter boleh mulai dari item ini sebelum backend CRUD selesai:

- [ ] Auth UI.
- [ ] Token storage.
- [ ] Refresh token handling.
- [x] Read dictionary.
- [x] Read location.
- [ ] Local draft form.
- [x] Role-aware navigation dari `/me` setelah Phase 1 selesai.

Flutter belum boleh mengunci flow submit sebelum ini selesai:

- [x] `POST /api/v1/pembandings`
- [x] Dokumentasi create pembanding.
- [x] Test create pembanding.
- [x] Validasi `sewa` di API.
