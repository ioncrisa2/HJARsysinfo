# Dokumentasi API Bank Data Pembanding

Semua endpoint berada di bawah prefix `/api` dan merespons JSON.

## Basis URL dan autentikasi
- Skema token: Bearer (Laravel Sanctum personal access token).
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

Pengecualian:
- Login gagal: `{ "message": "Invalid credentials." }` (422)
- Refresh token tidak valid: `{ "message": "Invalid or expired refresh token." }` (401)

## Endpoint autentikasi (`/api/auth`)

### POST `/api/auth/login`
- Tanpa autentikasi.
- Body:
  - `email` (string, wajib, format email)
  - `password` (string, wajib, min 6)
  - `device_name` (string, wajib, max 255)
- Respons 200 mengandung:
  - `token_type`, `access_token`, `refresh_token`, `expires_in`, `user`

### POST `/api/auth/refresh`
- Tanpa autentikasi.
- Body:
  - `refresh_token` (string, wajib, 64 karakter)
  - `device_name` (string, opsional, default `api-refresh`)
- Respons 200: token baru.
- Respons 401: token tidak valid atau kedaluwarsa.

### GET `/api/auth/me`
- Wajib Bearer token.
- Mengembalikan profil user login.

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
    - `false` atau `0`: termasuk data nonaktif
- Respons:
  - Array item dengan field: `id`, `name`, `slug`, `sort_order`, `is_active`, `badge_color_token`, `marker_icon_url`
- Respons 404 jika `type` tidak dikenal.

## Endpoint pembanding (`/api/v1`, wajib Bearer token)

### GET `/api/v1/pembandings`
- Fungsi: daftar data pembanding dengan filter dasar.
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
- 404 jika ID tidak ditemukan.

### GET `/api/v1/pembandings/{id}/similar`
- Mengembalikan daftar pembanding terdekat yang mirip terhadap pembanding acuan `{id}`.
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
