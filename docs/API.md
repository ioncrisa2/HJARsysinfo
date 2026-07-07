# Dokumentasi API Bank Data Pembanding

Semua endpoint berada di bawah prefix `/api` dan merespons JSON.

## Basis URL dan autentikasi
- Skema token: Bearer (Laravel Sanctum personal access token).
- Login mobile hanya diizinkan untuk user dengan salah satu role:
  - `data_contributor`
  - `pimpinan` (role untuk pemimpin/pimpinan di aplikasi ini)
  - `surveyor`
- User dengan role lain, termasuk `super_admin`, tidak bisa login lewat API mobile.
- Header wajib untuk endpoint terlindungi:
  - `Authorization: Bearer <access_token>`
- `access_token` default berlaku sekitar 3600 detik (mengikuti konfigurasi Sanctum).
- `refresh_token` adalah string 64 karakter, kedaluwarsa 30 hari, dan diputar setiap kali `/auth/refresh` dipanggil.
- Login dibatasi 5 percobaan per email+IP tiap 15 menit. Jika melebihi batas, API merespons HTTP 429.

## Pola respons standar
Respons sukses:

```json
{
  "status": "success",
  "message": "<deskripsi>",
  "data": "<payload>"
}
```

Respons error validasi/umum:

```json
{
  "status": "error",
  "message": "<penjelasan>",
  "errors": { "field": ["pesan"] }
}
```

Semua error API `/api/*` dikembalikan sebagai JSON. Untuk endpoint yang memakai validasi Laravel, detail field ada di `errors`.

## Endpoint autentikasi (`/api/auth`)

### POST `/api/auth/login`
- Tanpa autentikasi.
- Body:
  - `email` (string, wajib, format email)
  - `password` (string, wajib, min 6)
  - `device_name` (string, wajib, max 255)
- Respons 200 mengandung:
  - `token_type`, `access_token`, `refresh_token`, `expires_in`, `user`
  - `user.roles`
  - `user.permissions`
- Respons 403 jika user valid tetapi tidak termasuk role mobile yang diizinkan.
- Respons 422 jika kredensial salah.

### POST `/api/auth/refresh`
- Tanpa autentikasi.
- Body:
  - `refresh_token` (string, wajib, 64 karakter)
  - `device_name` (string, opsional, default `api-refresh`)
- Respons 200: token baru.
- Respons 401: token tidak valid atau kedaluwarsa.

### GET `/api/auth/me`
- Wajib Bearer token.
- Mengembalikan profil user login, termasuk `roles` dan `permissions`.

### POST `/api/auth/logout`
- Wajib Bearer token.
- Mencabut access token aktif dan semua refresh token user.

## Endpoint dictionary (`/api/v1`, wajib Bearer token)

### GET `/api/v1/dictionaries/{type}`
- Fungsi: sumber referensi nilai master data untuk integrasi lintas sistem.
- Path param `{type}` yang valid:
  - `jenis-listing`
  - `jenis-objek`
  - `status-pemberi-informasi`
  - `bentuk-tanah`
  - `kondisi-tanah`
  - `posisi-tanah`
  - `topografi`
  - `dokumen-tanah`
  - `peruntukan`
- Query param:
  - `active_only` (boolean, default `true`)
    - `true`/tidak diisi: hanya data aktif (`is_active = true`)
    - `false` atau `0`: termasuk data nonaktif, hanya untuk user dengan permission `view_master_data`
- Respons:
  - Wrapper standar dengan `data` berisi array item: `id`, `name`, `slug`, `sort_order`, `is_active`, `badge_color_token`, `marker_icon_url`
- Respons 404 jika `type` tidak dikenal.
- Respons 403 jika meminta `active_only=0` tanpa permission.

## Endpoint lokasi (`/api/v1/locations`, wajib Bearer token)

Semua endpoint lokasi read-only, memakai wrapper standar, dan menerima `limit` maksimum 200.

### GET `/api/v1/locations/provinces`
- Query params opsional:
  - `q` (string, pencarian nama)
  - `limit` (integer, default 50, maksimum 200)

### GET `/api/v1/locations/regencies`
- Query params opsional:
  - `province_id` (string)
  - `q` (string, pencarian nama)
  - `limit` (integer, default 50, maksimum 200)

### GET `/api/v1/locations/districts`
- Query params opsional:
  - `regency_id` (string)
  - `q` (string, pencarian nama)
  - `limit` (integer, default 50, maksimum 200)

### GET `/api/v1/locations/villages`
- Query params opsional:
  - `district_id` (string)
  - `q` (string, pencarian nama)
  - `limit` (integer, default 50, maksimum 200)

## Endpoint pembanding (`/api/v1`, wajib Bearer token)

### GET `/api/v1/pembandings`
- Fungsi: daftar data pembanding dengan filter dasar.
- Permission: `view_any_data::pembanding`.
- Query params (opsional):
  - `district_id` (string)
  - `peruntukan` (string, slug dictionary `peruntukan`)
  - `jenis_objek` (string, slug dictionary `jenis_objek`)
  - `min_harga` (number >= 0)
  - `max_harga` (number >= `min_harga`)
  - `limit` (integer, min 1, default 50, maksimum 200)
- Urutan: `tanggal_data` desc.
- Menggunakan pagination Laravel.

### GET `/api/v1/pembandings/{id}`
- Mengambil satu data pembanding lengkap.
- Permission: `view_data::pembanding` atau `view_any_data::pembanding`.
- 404 jika ID tidak ditemukan.

### GET `/api/v1/pembandings/{id}/similar`
- Mengembalikan daftar pembanding terdekat yang mirip terhadap pembanding acuan `{id}`.
- Permission: akses lihat terhadap data acuan.
- Query params:
  - `limit` (int, default 100, maksimum 1000)
  - `range_km` (number, 0.1-100, default 10 km)
- Field tambahan per item:
  - `score` (float)
  - `distance` (meter)
  - `priority_rank` (int)
  - `is_fallback` (bool)

### POST `/api/v1/pembandings/similar`
- Cari data pembanding mirip berdasarkan payload kriteria tanpa ID referensi.
- Permission: `view_any_data::pembanding`.
- Body:
  - Wajib:
    - `latitude` (number, -90..90)
    - `longitude` (number, -180..180)
    - `district_id` (string)
    - `peruntukan` (slug dictionary aktif `peruntukan`)
  - Opsional:
    - `luas_tanah`, `luas_bangunan`, `lebar_jalan`, `harga` (number >= 0)
    - `dokumen_tanah` (slug dictionary aktif `dokumen_tanah`)
    - `posisi_tanah` (slug dictionary aktif `posisi_tanah`)
    - `kondisi_tanah` (slug dictionary aktif `kondisi_tanah`)
  - Paging:
    - `limit` (int 1-1000, default 100)
    - `range_km` (0.1-100, default 10 km)
- Respons identik dengan endpoint `/pembandings/{id}/similar`.

### POST `/api/v1/pembandings`
- Fungsi: membuat data pembanding baru.
- Permission: `create_data::pembanding`.
- Content-Type:
  - `multipart/form-data` untuk upload foto.
- Body minimum:
  - `jenis_listing_id` (integer, wajib)
  - `jenis_objek_id` (integer, wajib)
  - `nama_pemberi_informasi` (string, wajib)
  - `tanggal_data` (date `YYYY-MM-DD`, wajib)
  - `alamat_data` (string, wajib)
  - `province_id`, `regency_id`, `district_id`, `village_id` (string, wajib)
  - `latitude`, `longitude` (number, wajib)
  - `image` (file gambar, wajib, max 15 MB)
  - `luas_tanah`, `lebar_depan`, `lebar_jalan`, `harga` (number, wajib)
  - `bentuk_tanah_id`, `posisi_tanah_id`, `kondisi_tanah_id`, `topografi_id`, `dokumen_tanah_id`, `peruntukan_id` (integer, wajib)
- Opsional:
  - `nomer_telepon_pemberi_informasi`
  - `status_pemberi_informasi_id`
  - `luas_bangunan`
  - `tahun_bangun`
  - `rasio_tapak`
  - `catatan`
- Khusus `jenis_listing` dengan slug `sewa`:
  - `harga` tetap wajib.
  - `jangka_waktu_sewa` wajib, number minimal 1.
  - `satuan_waktu_sewa` wajib, hanya `Bulan` atau `Tahun`.
- Non-sewa otomatis membersihkan `jangka_waktu_sewa` dan `satuan_waktu_sewa`.
- Response sukses mengembalikan `PembandingResource`.
- Exact duplicate prevention membandingkan seluruh field bisnis yang sudah dinormalisasi, termasuk checksum isi gambar.
- Koordinat yang sama tetap diterima jika ada field bisnis lain yang berbeda.
- Jika seluruh isi identik dengan record aktif atau soft-deleted, API mengembalikan HTTP `409 Conflict`:

```json
{
  "status": "error",
  "message": "Data identik sudah tersedia pada record #123. Gunakan menu update pada record tersebut.",
  "errors": null,
  "duplicate": {
    "id": 123,
    "status": "active",
    "url": "https://example.test/api/v1/pembandings/123"
  }
}
```

- Untuk record soft-deleted, `duplicate.status` bernilai `deleted` dan `duplicate.url` bernilai `null`; record harus dipulihkan oleh pihak berwenang.

Contoh field sewa:

```json
{
  "harga": 30000000,
  "jangka_waktu_sewa": 3,
  "satuan_waktu_sewa": "Bulan"
}
```

```json
{
  "harga": 120000000,
  "jangka_waktu_sewa": 1,
  "satuan_waktu_sewa": "Tahun"
}
```

### PUT/PATCH `/api/v1/pembandings/{id}`
- Fungsi: update data pembanding.
- Permission:
  - `update_data::pembanding` untuk semua data, atau
  - `update_own_data::pembanding` jika `created_by` sama dengan user login.
- Body mengikuti field create.
- `image` opsional. Jika tidak dikirim, foto lama tidak dihapus.
- Update yang membuat seluruh isi identik dengan record lain juga mengembalikan HTTP `409 Conflict`.

### POST `/api/v1/pembandings/{id}`
- Fungsi: workaround update multipart untuk mobile client.
- Kirim body multipart dengan `_method=PUT`.
- Permission dan validasi sama dengan `PUT/PATCH`.

### GET `/api/v1/pembandings/{id}/history`
- Fungsi: melihat riwayat perubahan data pembanding.
- Permission: akses lihat terhadap data.
- Response sukses berisi maksimal 100 activity terbaru.

### POST `/api/v1/pembandings/{id}/delete-request`
- Fungsi: mengajukan penghapusan data pembanding ke moderation desk.
- Permission: akses lihat terhadap data.
- Body:
  - `reason` (string, wajib, max 1000)
- Sistem menolak pengajuan baru jika masih ada request pending untuk data yang sama.

### DELETE `/api/v1/pembandings/{id}`
- Fungsi: direct soft delete data pembanding.
- Permission: `delete_data::pembanding`.
- Direct delete ini untuk user yang memang diberi permission tinggi; user biasa memakai `delete-request`.

## Permission UI mobile

Gunakan `roles` dan `permissions` dari `/api/auth/me` atau response login.

- Tombol lihat/list: `view_any_data::pembanding`.
- Tombol detail: `view_data::pembanding` atau `view_any_data::pembanding`.
- Tombol tambah: `create_data::pembanding`.
- Tombol edit semua data: `update_data::pembanding`.
- Tombol edit data sendiri: `update_own_data::pembanding`, lalu cek ownership dari `created_by.id`.
- Tombol hapus langsung: `delete_data::pembanding`.
- Opsi dictionary nonaktif: `view_master_data`.

## Referensi nilai dictionary (slug)
Nilai slug dapat berubah mengikuti master data aktif di sistem. Untuk sinkronisasi lintas sistem, gunakan endpoint:
- `GET /api/v1/dictionaries/{type}`

Contoh slug bawaan seed:
- `peruntukan`: `unit_apartemen`, `rumah_tinggal`, `ruko`, `perkantoran`, `kios`, `gudang`, `pabrik`, `tanah_kosong`, `rukan`, `townhouse`, `villa`, `mall`, `campuran`, `lainnya`
- `dokumen_tanah`: `sertifikat_hak_milik`, `sertifikat_hak_guna_bangunan`, `sertifikat_hak_guna_usaha`, `akta_jual_beli`, `girik`, `petok_desa`, `surat_camat`, `peta_bidang_tanah`, `lainnya`
- `posisi_tanah`: `kuldesak_lot`, `interior_lot`, `t_section_lot`, `corner_lot`, `key_lot`, `flag_lot`, `tanpa_akses`
- `kondisi_tanah`: `matang`, `rawa`, `sawah`, `belum_berkembang`, `lainnya`
- `jenis_objek`: `tanah`, `rumah_tinggal`, `ruko`, `apartement`, `kios`, `gudang`, `kantor`, `pabrik`, `tanah_kebun`, `tanah_dan_bangunan`, `sawah`

## Catatan teknis
- Set header `Accept: application/json` dan `Content-Type: application/json` saat mengirim body.
- Pencarian similar menghitung jarak dengan Haversine (meter) dan membatasi kandidat dalam radius + bounding box.
- Jika tidak ada hasil cocok, API tetap mengembalikan `status: success` dengan `data: []`.
