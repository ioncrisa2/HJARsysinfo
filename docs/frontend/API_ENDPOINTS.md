# Panduan Endpoint API untuk Frontend HJAR Sysinfo

Status: target contract dan migration inventory  
Terakhir diverifikasi terhadap `routes/api.php` dan `routes/web.php`: 2026-08-26  
Checklist utama: [`IMPLEMENTATION_CHECKLIST.md`](./IMPLEMENTATION_CHECKLIST.md)  
Arsitektur: [`FRONTEND_ARCHITECTURE.md`](./FRONTEND_ARCHITECTURE.md)

## Tujuan dokumen

Dokumen ini adalah daftar endpoint yang akan digunakan `hjar-web`. Ia menghubungkan tiga hal:

1. Route JSON yang sudah tersedia di Laravel.
2. Route Inertia/web yang masih harus diekstrak menjadi API.
3. Task frontend/backend dalam `IMPLEMENTATION_CHECKLIST.md`.

Dokumen ini bukan pengganti OpenAPI. Path, method, request, response, dan error resmi tetap harus tersedia dalam spec Laravel. Katalog ini menjelaskan endpoint mana yang dipakai setiap feature frontend, status migrasinya, permission, query key, dan invalidation yang diperlukan.

## Aturan status endpoint

| Status | Arti |
|---|---|
| `READY` | Endpoint JSON sudah ada pada target path dan dapat mulai diintegrasikan setelah contract diverifikasi. |
| `ADAPT` | Logic/endpoint sudah ada, tetapi path, response, filter, auth mode, atau semantics belum memenuhi target SPA. |
| `NEW` | Belum ada endpoint JSON; harus dibuat dari use case/controller web saat ini. |
| `LEGACY` | Endpoint dipertahankan untuk mobile/client lama, tetapi tidak digunakan oleh web SPA baru. |
| `DEFERRED` | Tidak boleh diekspos ke frontend sampai security/runbook/feature flag siap. |

Status hanya berubah menjadi `READY` bila route, authorization, OpenAPI, feature tests, dan response error sudah selesai. Keberadaan method controller saja tidak cukup.

## Base URL dan versioning

```text
VITE_API_BASE_URL production : https://api.example.com
VITE_API_BASE_URL staging    : https://api.staging.example.com
VITE_API_BASE_URL local      : http://localhost:8000
Versioned API prefix         : /api/v1
```

Framework endpoint Sanctum berada di luar prefix API:

```text
GET https://api.example.com/sanctum/csrf-cookie
```

Frontend hanya membaca base URL dari `VITE_API_BASE_URL`. Feature/page dilarang merangkai origin sendiri.

Seluruh endpoint baru masuk `/api/v1`. Endpoint auth lama `/api/auth/*` tetap tersedia selama compatibility window mobile, tetapi tidak digunakan oleh web SPA kecuali adapter sementara yang tercatat.

## Authentication mode

### Web SPA

- Menggunakan Sanctum stateful session cookie.
- Setiap request memakai `credentials: 'include'`.
- Frontend tidak mengirim bearer token.
- Mutation didahului inisialisasi CSRF cookie.

### Mobile dan integrasi

- Menggunakan bearer access token dan refresh token.
- Endpoint token lama ditandai `LEGACY` dalam dokumen ini karena bukan dependency `hjar-web`.

## Header standar

```http
Accept: application/json
Content-Type: application/json
X-Requested-With: XMLHttpRequest
```

Untuk `FormData`, jangan menetapkan `Content-Type` secara manual. Browser harus membuat multipart boundary.

Header response yang harus dimanfaatkan bila tersedia:

```http
X-Request-Id: <correlation-id>
Retry-After: <seconds>
Content-Disposition: attachment; filename="..."
```

## Response contract

### Resource tunggal

```json
{
  "status": "success",
  "message": "Data berhasil diambil.",
  "data": {}
}
```

### Collection terpaginasikan

Target v1 harus konsisten untuk seluruh collection:

```json
{
  "status": "success",
  "message": "Data berhasil diambil.",
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 25,
    "from": 1,
    "to": 25,
    "total": 120,
    "last_page": 5
  },
  "links": {
    "first": null,
    "last": null,
    "prev": null,
    "next": null
  }
}
```

API Pembanding saat ini membungkus hasil paginator di dalam `data`. Task `API-0103` harus menetapkan apakah shape itu dipertahankan untuk compatibility atau dinormalisasi dengan adapter/version yang aman. Generated client mengikuti OpenAPI aktual, bukan contoh ini secara membabi buta.

### Validation/error

```json
{
  "status": "error",
  "code": "VALIDATION_FAILED",
  "message": "Validation failed",
  "errors": {
    "alamat_data": ["Alamat wajib diisi."]
  },
  "request_id": "optional-correlation-id"
}
```

Status yang harus ditangani client:

| HTTP | Makna frontend |
|---|---|
| 401 | Session tidak ada/expired; reset auth dan redirect login. |
| 403 | Session valid tetapi tidak berhak; tampilkan forbidden, jangan logout. |
| 404 | Resource/route tidak ditemukan. |
| 409 | Conflict, duplicate, stale transition, atau resource sedang dipakai. |
| 419 | CSRF/session mismatch; recovery satu kali bila request aman diulang. |
| 422 | Validation field/global. |
| 429 | Rate limited; baca `Retry-After`. |
| 5xx | Error sistem; tampilkan request ID dan retry hanya bila aman. |

## Konvensi query collection

Target parameter standar:

| Parameter | Format | Catatan |
|---|---|---|
| `page` | integer >= 1 | Sumber kebenaran berada di URL frontend. |
| `per_page` | integer dari allowlist | Backend selalu membatasi maksimum. |
| `q` | string | Trim client dan backend; debounce di frontend. |
| `sort` | stable field name | Hanya allowlist backend. |
| `direction` | `asc` atau `desc` | Default terdokumentasi per endpoint. |
| filter domain | scalar/date/enum | Nama mengikuti OpenAPI; nilai kosong tidak dikirim. |

Endpoint Pembanding API yang sekarang memakai `limit` harus tetap menerima parameter lama untuk mobile. Web memakai `per_page` setelah `PEM-0602` dan `API-0108` menyelesaikan normalisasi filter.

## Katalog endpoint

### 1. Session dan authentication

| ID | Method | Target path | Status | Dipakai untuk | Checklist |
|---|---|---|---|---|---|
| `EP-AUTH-001` | GET | `/sanctum/csrf-cookie` | `ADAPT` | Inisialisasi CSRF sebelum login/mutation web | `API-0120`–`API-0123`, `AUTH-0411` |
| `EP-AUTH-002` | POST | `/api/v1/auth/session` | `NEW` | Login web dengan email/password dan session cookie | `API-0124`–`API-0125`, `AUTH-0412` |
| `EP-AUTH-003` | GET | `/api/v1/auth/me` | `ADAPT` | Restore session, user, roles, permissions | `API-0127`, `AUTH-0413` |
| `EP-AUTH-004` | DELETE | `/api/v1/auth/session` | `NEW` | Logout web dan invalidasi session | `API-0126`, `AUTH-0414` |
| `EP-AUTH-005` | PUT | `/api/v1/auth/profile` | `ADAPT` | Update nama/email current user | `SYS-1201` |
| `EP-AUTH-006` | PUT | `/api/v1/auth/profile/password` | `ADAPT` | Update password current user | `SYS-1202` |
| `EP-AUTH-101` | POST | `/api/auth/login` | `LEGACY` | Bearer login mobile; bukan web SPA | `API-0130` |
| `EP-AUTH-102` | POST | `/api/auth/refresh` | `LEGACY` | Rotating refresh token mobile | `API-0130` |
| `EP-AUTH-103` | POST | `/api/auth/logout` | `LEGACY` | Revoke bearer/refresh token mobile | `API-0130` |

`EP-AUTH-003`, `005`, dan `006` sudah mempunyai implementasi pada path `/api/auth/*`. Backend harus menambah path versioned/alias yang menerima stateful cookie tanpa mematahkan path mobile lama.

Query/store ownership:

```text
Pinia auth store : user, roles, permissions, initialized
Query cache      : tidak menyimpan copy kedua auth/me
```

Login request minimum:

```json
{
  "email": "user@example.com",
  "password": "secret"
}
```

`device_name` dan refresh token tidak dikirim oleh web session flow.

### 2. Public application settings

| ID | Method | Target path | Status | Dipakai untuk | Checklist |
|---|---|---|---|---|---|
| `EP-APP-001` | GET | `/api/v1/settings/public` | `NEW` | Nama aplikasi, logo URL, version, safe branding | `API-0140`, `SHELL-0426` |

Endpoint ini public, cacheable, dan hanya mengembalikan field aman. System mode atau konfigurasi keamanan internal tidak boleh ikut hanya karena berada pada model setting yang sama.

Query key:

```ts
['settings', 'public']
```

### 3. Dashboard

| ID | Method | Target path | Status | Dipakai untuk | Permission | Checklist |
|---|---|---|---|---|---|---|
| `EP-DASH-001` | GET | `/api/v1/dashboard` | `NEW` | Summary dan widget yang diizinkan untuk current user | Authenticated + widget permissions | `API-0141`, `API-0142`, `DASH-0701`–`DASH-0709` |

Parameter yang disarankan:

```text
jenis_listing_id=<id>
period=12m
widgets[]=statsOverview
widgets[]=map
```

Tanpa `widgets[]`, backend mengembalikan seluruh widget yang diizinkan. Response harus menyertakan `dashboard_variant`, `can`, `can_widgets`, dan data widget dengan key stabil. Data untuk widget tanpa permission tidak dikirim, bukan dikirim lalu hanya disembunyikan frontend.

Query key:

```ts
['dashboard', { jenisListingId, period, widgets }]
```

### 4. Dictionaries dan master data

#### Read/reference endpoints

| ID | Method | Target path | Status | Dipakai untuk | Checklist |
|---|---|---|---|---|---|
| `EP-DICT-001` | GET | `/api/v1/dictionaries` | `NEW` | Daftar tipe dictionary, label, icon, count | `MASTER-0801`–`MASTER-0802` |
| `EP-DICT-002` | GET | `/api/v1/dictionaries/{type}` | `READY` | Options form/filter dan dictionary list | `FE-0501`–`FE-0502`, `MASTER-0803` |

Parameter `EP-DICT-002` yang sudah didukung/harus dipertahankan:

```text
active_only=1|0
```

Search `q` belum didukung endpoint ini. Tambahkan hanya bila pengukuran menunjukkan dictionary terlalu besar untuk difilter lokal.

#### Admin mutation endpoints

| ID | Method | Target path | Status | Permission | Checklist |
|---|---|---|---|---|---|
| `EP-DICT-010` | POST | `/api/v1/dictionaries/{type}` | `ADAPT` | `create_master_data` | `API-0147`, `MASTER-0804` |
| `EP-DICT-011` | PUT | `/api/v1/dictionaries/{type}/{id}` | `ADAPT` | `update_master_data` | `API-0147`, `MASTER-0804` |
| `EP-DICT-012` | PATCH | `/api/v1/dictionaries/{type}/{id}/status` | `ADAPT` | `update_master_data_status` | `API-0147`, `MASTER-0804` |
| `EP-DICT-013` | POST | `/api/v1/dictionaries/{type}/reorder` | `ADAPT` | `reorder_master_data` | `API-0147`, `MASTER-0805` |
| `EP-DICT-014` | DELETE | `/api/v1/dictionaries/{type}/{id}` | `ADAPT` | `delete_master_data` atau `delete_any_master_data` | `API-0147`, `MASTER-0804`–`MASTER-0806` |

Logic JSON CRUD sudah ada di `DictionaryApiController`, tetapi masih berada di namespace `/app/master-data/dictionaries/*` dengan web-session route. Pindahkan adapter route ke `/api/v1` dan pertahankan service/type map yang sama.

Mutation invalidation:

```text
EP-DICT-010..014 -> invalidate ['dictionaries', type]
                    invalidate ['dictionaries', 'definitions'] bila count/status berubah
                    invalidate form-options yang menyertakan type terkait
```

### 5. Locations dan geo administration

#### Cascading lookup

| ID | Method | Target path | Status | Parameter utama | Checklist |
|---|---|---|---|---|---|
| `EP-LOC-001` | GET | `/api/v1/locations/provinces` | `READY` | `q`, `limit` | `FE-0503`–`FE-0508` |
| `EP-LOC-002` | GET | `/api/v1/locations/regencies` | `READY` | `province_id`, `q`, `limit` | `FE-0503`–`FE-0508` |
| `EP-LOC-003` | GET | `/api/v1/locations/districts` | `READY` | `regency_id`, `q`, `limit` | `FE-0503`–`FE-0508` |
| `EP-LOC-004` | GET | `/api/v1/locations/villages` | `READY` | `district_id`, `q`, `limit` | `FE-0503`–`FE-0508` |

#### Geo CRUD

`{resource}` dibatasi ke `provinces`, `regencies`, `districts`, atau `villages`.

| ID | Method | Target path | Status | Permission | Checklist |
|---|---|---|---|---|---|
| `EP-GEO-001` | GET | `/api/v1/geo/{resource}` | `NEW` | `view_geo_data` | `API-0148`, `GEO-0810`–`GEO-0811` |
| `EP-GEO-002` | POST | `/api/v1/geo/{resource}` | `ADAPT` | `create_geo_data` | `API-0148`, `GEO-0812` |
| `EP-GEO-003` | PUT | `/api/v1/geo/{resource}/{id}` | `ADAPT` | `update_geo_data` | `API-0148`, `GEO-0812` |
| `EP-GEO-004` | DELETE | `/api/v1/geo/{resource}/{id}` | `ADAPT` | `delete_geo_data` | `API-0148`, `GEO-0812`–`GEO-0814` |

Query keys:

```ts
['locations', 'provinces', params]
['locations', 'regencies', params]
['locations', 'districts', params]
['locations', 'villages', params]
['geo', resource, filters]
```

Geo mutation harus meng-invalidasi admin list dan lookup parent/child yang terdampak.

### 6. Pembanding

#### List, map, options, dan similarity

| ID | Method | Target path | Status | Dipakai untuk | Checklist |
|---|---|---|---|---|---|
| `EP-PEM-001` | GET | `/api/v1/pembandings` | `ADAPT` | List/filter/pagination | `PEM-0601`–`PEM-0606` |
| `EP-PEM-002` | GET | `/api/v1/pembandings/map` | `READY` | Marker/cluster map | `PEM-0607`, `DASH-0704` bila reuse sesuai contract |
| `EP-PEM-003` | GET | `/api/v1/pembandings/form-options` | `NEW` | Options create/edit dan initial cascading data | `API-0143`, `PEM-0623` |
| `EP-PEM-004` | GET | `/api/v1/pembandings/creators` | `NEW` | Filter berdasarkan pembuat | `API-0144`, `PEM-0602` |
| `EP-PEM-005` | POST | `/api/v1/pembandings/similar` | `READY` | Similarity berdasarkan payload draft | Feature tambahan/pembanding |
| `EP-PEM-006` | GET | `/api/v1/pembandings/{id}/similar` | `READY` | Similarity record tersimpan | Feature tambahan/pembanding |

Target filter `EP-PEM-001` untuk parity web:

```text
page
per_page=8|16|32|64
q
province_id
regency_id
district_id
village_id
dari_tanggal
sampai_tanggal
jenis_listing_id
jenis_objek_id
created_by
sort
direction
```

API saat ini baru memvalidasi `district_id`, `peruntukan`, `jenis_objek`, rentang harga, dan `limit`. `PEM-0602` tidak selesai sampai filter web dan API disatukan secara backward-compatible.

Catatan routing Laravel: route statis `/form-options`, `/creators`, `/map`, dan `/similar` harus dideklarasikan sebelum `/pembandings/{id}`.

#### CRUD, history, dan delete request

| ID | Method | Target path | Status | Permission/policy | Checklist |
|---|---|---|---|---|---|
| `EP-PEM-010` | GET | `/api/v1/pembandings/{id}` | `READY` | `view` policy | `PEM-0610`–`PEM-0615` |
| `EP-PEM-011` | POST | `/api/v1/pembandings` | `READY` | `create` policy | `PEM-0620`–`PEM-0630` |
| `EP-PEM-012` | PUT/PATCH | `/api/v1/pembandings/{id}` | `READY` | `update` policy | `PEM-0620`–`PEM-0630` |
| `EP-PEM-013` | POST | `/api/v1/pembandings/{id}` dengan `_method=PUT` | `READY` | `update` policy | Multipart image workaround, `PEM-0625` |
| `EP-PEM-014` | DELETE | `/api/v1/pembandings/{id}` | `READY` | `delete` policy | `PEM-0612`, destructive tests |
| `EP-PEM-015` | GET | `/api/v1/pembandings/{id}/history` | `READY` | `view` policy | `PEM-0613` |
| `EP-PEM-016` | POST | `/api/v1/pembandings/{id}/delete-request` | `READY` | Current implementation authorizes delete | `PEM-0612`, `PEM-0630` |

Create/update menggunakan `multipart/form-data` bila ada image. DTO edit harus berasal dari `EP-PEM-010` atau endpoint edit resource yang contract-nya eksplisit; frontend tidak boleh bergantung pada Inertia-only record shape.

#### Duplicate resolution

| ID | Method | Target path | Status | Dipakai untuk | Checklist |
|---|---|---|---|---|---|
| `EP-PEM-020` | GET | `/api/v1/pembanding-submissions/{submissionId}` | `NEW` | Submission dan candidates duplicate | `API-0145`, `PEM-0628`–`PEM-0629` |
| `EP-PEM-021` | GET | `/api/v1/pembanding-submissions/{submissionId}/image` | `NEW` | Preview image staged, authorized blob | `API-0145`, `PEM-0629` |
| `EP-PEM-022` | POST | `/api/v1/pembanding-submissions/{submissionId}/resolution` | `NEW` | Resolve dengan strategy dan target pembanding | `API-0146`, `PEM-0628`–`PEM-0630` |

Request resolution:

```json
{
  "strategy": "use_existing",
  "pembanding_id": 123
}
```

atau:

```json
{
  "strategy": "replace_existing",
  "pembanding_id": 123
}
```

Frontend branching harus menggunakan error `code`, misalnya `DUPLICATE_REVIEW_REQUIRED`, bukan mencocokkan teks pesan manusia.

Query keys dan invalidation:

```text
list       ['pembandings', 'list', filters]
map        ['pembandings', 'map', filters]
detail     ['pembandings', 'detail', id]
history    ['pembandings', 'detail', id, 'history']
options    ['pembandings', 'form-options', context]
duplicate  ['pembanding-submissions', submissionId]

create/update/delete/resolve
  -> invalidate relevant list/map/detail/history/dashboard/moderation keys
```

### 7. Bulk Excel import

| ID | Method | Target path | Status | Dipakai untuk | Checklist |
|---|---|---|---|---|---|
| `EP-IMPORT-001` | GET | `/api/v1/pembanding-imports` | `NEW` | Batch list/pagination | `IMPORT-1101` |
| `EP-IMPORT-002` | POST | `/api/v1/pembanding-imports` | `ADAPT` | Upload workbook/create batch | `IMPORT-1102`–`IMPORT-1103` |
| `EP-IMPORT-003` | GET | `/api/v1/pembanding-imports/{batchId}` | `NEW` | Batch summary, rows, processing status | `IMPORT-1104`–`IMPORT-1105` |
| `EP-IMPORT-004` | PATCH | `/api/v1/pembanding-imports/{batchId}/selection` | `ADAPT` | Update row selection | `IMPORT-1105`, `IMPORT-1107` |
| `EP-IMPORT-005` | PATCH | `/api/v1/pembanding-imports/{batchId}/bulk-apply` | `ADAPT` | Apply value ke selected rows | `IMPORT-1107` |
| `EP-IMPORT-006` | POST | `/api/v1/pembanding-imports/{batchId}/finalize` | `ADAPT` | Finalize selected ready rows | `IMPORT-1109`–`IMPORT-1111` |
| `EP-IMPORT-007` | GET | `/api/v1/pembanding-imports/{batchId}/rows/{rowId}` | `NEW` | Row draft edit data | `IMPORT-1106` |
| `EP-IMPORT-008` | PUT | `/api/v1/pembanding-imports/{batchId}/rows/{rowId}` | `ADAPT` | Update staged row | `IMPORT-1106` |
| `EP-IMPORT-009` | GET | `/api/v1/pembanding-imports/{batchId}/rows/{rowId}/image` | `ADAPT` | Authorized staged image blob | `IMPORT-1106`, `IMPORT-1110` |
| `EP-IMPORT-010` | POST | `/api/v1/pembanding-imports/{batchId}/rows/{rowId}/retry` | `ADAPT` | Retry parsing/finalization row | `IMPORT-1108`–`IMPORT-1110` |

Seluruh logic sudah mempunyai web controller/actions, tetapi response page/redirect harus diganti JSON/202 semantics. Poll `EP-IMPORT-003` hanya selama batch berada pada non-terminal state.

Query keys:

```ts
['pembanding-imports', 'list', filters]
['pembanding-imports', 'detail', batchId, rowFilters]
['pembanding-imports', 'row', batchId, rowId]
```

### 8. Users

| ID | Method | Target path | Status | Permission | Checklist |
|---|---|---|---|---|---|
| `EP-USER-001` | GET | `/api/v1/users` | `NEW` | `view_any_user` | `USER-0901` |
| `EP-USER-002` | GET | `/api/v1/users/{id}` | `NEW` | `view_user`/edit authorization | `USER-0903` |
| `EP-USER-003` | POST | `/api/v1/users` | `ADAPT` | `create_user` | `USER-0903` |
| `EP-USER-004` | PUT | `/api/v1/users/{id}` | `ADAPT` | `update_user` | `USER-0903` |
| `EP-USER-005` | PATCH | `/api/v1/users/{id}/status` | `ADAPT` | `update_user` | `USER-0904`–`USER-0905` |
| `EP-USER-006` | DELETE | `/api/v1/users/{id}` | `ADAPT` | `delete_user` | `USER-0905` |
| `EP-USER-007` | POST | `/api/v1/users/bulk-delete` | `ADAPT` | `delete_any_user` | `USER-0902`, `USER-0905` |
| `EP-USER-008` | GET | `/api/v1/roles/options` | `NEW` | User form role options | `USER-0903` |

List parameter minimum: `page`, `per_page`, `q`, `role`, `status`, `sort`, `direction`.

Mutation user/role assignment yang menyentuh current user harus memicu refetch `EP-AUTH-003` dan clear query yang tidak lagi diizinkan.

### 9. Access control

| ID | Method | Target path | Status | Permission | Checklist |
|---|---|---|---|---|---|
| `EP-ACL-001` | GET | `/api/v1/roles` | `NEW` | `view_access_control`/role permissions | `ACL-0910`–`ACL-0911` |
| `EP-ACL-002` | POST | `/api/v1/roles` | `ADAPT` | `create_role` | `ACL-0912` |
| `EP-ACL-003` | PUT | `/api/v1/roles/{id}` | `ADAPT` | `update_role` | `ACL-0912`–`ACL-0915` |
| `EP-ACL-004` | DELETE | `/api/v1/roles/{id}` | `ADAPT` | `delete_role` | `ACL-0912`, `ACL-0915` |
| `EP-ACL-005` | GET | `/api/v1/permissions` | `NEW` | `view_access_control` | `ACL-0910`–`ACL-0911` |
| `EP-ACL-006` | POST | `/api/v1/permissions` | `ADAPT` | `create_permission` | `ACL-0913` |
| `EP-ACL-007` | DELETE | `/api/v1/permissions/{id}` | `ADAPT` | `delete_permission` | `ACL-0913`, `ACL-0915` |

Response role harus menyertakan permission names stable. Frontend tidak mengarang permission group dari underscore bila backend sudah mempunyai grouping/labels yang lebih benar.

### 10. Contributor invitations dan public registration

| ID | Method | Target path | Status | Akses | Checklist |
|---|---|---|---|---|---|
| `EP-INV-001` | GET | `/api/v1/data-contributor-invitations` | `NEW` | `manage_data_contributor_invitations` | `INV-0920` |
| `EP-INV-002` | POST | `/api/v1/data-contributor-invitations` | `ADAPT` | Same | `INV-0921`–`INV-0922` |
| `EP-INV-003` | DELETE | `/api/v1/data-contributor-invitations/{id}` | `ADAPT` | Same | `INV-0921` |
| `EP-INV-004` | GET | `/api/v1/data-contributor-registration-requests` | `NEW` | Same | `INV-0920` |
| `EP-INV-005` | POST | `/api/v1/data-contributor-registration-requests/{id}/accept` | `ADAPT` | Same | `INV-0923` |
| `EP-INV-006` | POST | `/api/v1/data-contributor-registration-requests/{id}/reject` | `ADAPT` | Same | `INV-0923` |
| `EP-INV-010` | GET | `/api/v1/public/data-contributor-registration/{token}` | `NEW` | Public token + throttle | `INV-0924` |
| `EP-INV-011` | POST | `/api/v1/public/data-contributor-registration/{token}` | `ADAPT` | Public token + throttle | `INV-0924`–`INV-0925` |

Invitation token hanya tampil penuh pada response creation saat diperlukan. List berikutnya sebaiknya tidak membocorkan raw reusable token. Registration endpoint membedakan valid, expired, used, dan invalid melalui stable `code` tanpa membocorkan informasi yang tidak perlu.

### 11. Moderation

| ID | Method | Target path | Status | Permission | Checklist |
|---|---|---|---|---|---|
| `EP-MOD-001` | GET | `/api/v1/moderation` | `NEW` | `view_moderation` | `MOD-1001`–`MOD-1002` |
| `EP-MOD-002` | POST | `/api/v1/moderation/delete-requests/{id}/approve` | `ADAPT` | `approve_delete_request` | `MOD-1003`, `MOD-1007` |
| `EP-MOD-003` | POST | `/api/v1/moderation/delete-requests/{id}/reject` | `ADAPT` | `reject_delete_request` | `MOD-1004`, `MOD-1007` |
| `EP-MOD-004` | POST | `/api/v1/moderation/pembandings/{id}/restore` | `ADAPT` | `restore_data::pembanding` | `MOD-1005` |
| `EP-MOD-005` | DELETE | `/api/v1/moderation/pembandings/{id}` | `ADAPT` | `force_delete_data::pembanding` | `MOD-1006`–`MOD-1009` |

Parameter list: `tab=requests|trash`, `q`, `page`, `per_page`, status/date filters bila backend mendukung. Approve/reject harus menolak transition ganda sebagai 409, bukan sukses palsu.

### 12. Export

Browse/select data menggunakan `EP-PEM-001`; export endpoint hanya mengurus configuration, preview, run, dan file.

| ID | Method | Target path | Status | Permission | Checklist |
|---|---|---|---|---|---|
| `EP-EXPORT-001` | GET | `/api/v1/exports/configuration` | `NEW` | `view_export` | `EXPORT-1120`–`EXPORT-1122` |
| `EP-EXPORT-002` | POST | `/api/v1/exports/preview` | `ADAPT` | `export_data::pembanding` | `EXPORT-1123` |
| `EP-EXPORT-003` | GET | `/api/v1/exports/runs` | `NEW` | `view_export` atau export permission sesuai policy | `EXPORT-1125`–`EXPORT-1126` |
| `EP-EXPORT-004` | POST | `/api/v1/exports/runs` | `ADAPT` | `export_data::pembanding` | `EXPORT-1124` |
| `EP-EXPORT-005` | GET | `/api/v1/exports/runs/{id}` | `ADAPT` | Run ownership/audit policy | `EXPORT-1125`–`EXPORT-1126` |
| `EP-EXPORT-006` | POST | `/api/v1/exports/runs/{id}/retry` | `ADAPT` | Same | `EXPORT-1126` |
| `EP-EXPORT-007` | GET | `/api/v1/exports/runs/{id}/download` | `ADAPT` | Same | `EXPORT-1127` |
| `EP-EXPORT-008` | GET | `/api/v1/exports/download` | `ADAPT` | `export_data::pembanding` | Synchronous export path, `PEM-0608`, `EXPORT-1129` |

Payload preview/run mengikuti field yang saat ini divalidasi oleh `PembandingExportRequest`:

```text
format=excel|pdf|csv|geojson|kml
mode=summary|detail
profile=ringkas|lengkap|kontak|geospasial|audit
scope=selected|filtered
dataset=all|complete|issues
ids[]
columns[]
filter pembanding
```

`EP-EXPORT-004` idealnya mengembalikan 202 untuk queued run. Poll `EP-EXPORT-005` hanya pada state queued/processing.

### 13. Global search

| ID | Method | Target path | Status | Permission | Checklist |
|---|---|---|---|---|---|
| `EP-SEARCH-001` | GET | `/api/v1/search` | `NEW` | `view_search` | `SEARCH-0710`–`SEARCH-0713` |

Parameter: `q`, `menu_group`, `menu_name`, `resource_name`, `page`, `per_page`. Response item harus mempunyai typed target descriptor atau canonical frontend route name/params, bukan backend web URL `/app/*`.

### 14. Notifications

| ID | Method | Target path | Status | Akses | Checklist |
|---|---|---|---|---|---|
| `EP-NOTIF-001` | GET | `/api/v1/notifications` | `NEW` | Current user | `SHELL-0427`, `SYS-1213` |
| `EP-NOTIF-002` | PATCH | `/api/v1/notifications/{id}/read` | `NEW` | Notification ownership | `SYS-1214` |
| `EP-NOTIF-003` | POST | `/api/v1/notifications/read-all` | `ADAPT` | Current user | `SHELL-0427`, `SYS-1214` |

Parameter list: `unread=1|0`, `limit`, `page`. Notification payload harus mengandung typed `type` dan target resource data, bukan hardcoded `/app/export` URL.

Query key:

```ts
['notifications', filters]
```

### 15. Activity logs

| ID | Method | Target path | Status | Permission | Checklist |
|---|---|---|---|---|---|
| `EP-ACT-001` | GET | `/api/v1/activity-logs` | `NEW` | `view_activity_log` | `SYS-1210`, `SYS-1212` |
| `EP-ACT-002` | GET | `/api/v1/activity-logs/{id}` | `NEW` | `view_activity_log` | `SYS-1211`–`SYS-1212` |

List mendukung `q`, event/subject/user/date filters, `page`, `per_page`. Backend meredaksi password, token, secret, dan field personal yang tidak layak ditampilkan sebelum response dibuat.

### 16. System settings

| ID | Method | Target path | Status | Permission | Checklist |
|---|---|---|---|---|---|
| `EP-SET-001` | GET | `/api/v1/settings` | `NEW` | `view_settings` | `SYS-1203`–`SYS-1205` |
| `EP-SET-002` | PUT | `/api/v1/settings` | `ADAPT` | `update_settings` | `SYS-1203`–`SYS-1205` |
| `EP-SET-003` | POST | `/api/v1/settings/clear-cache` | `ADAPT` | `clear_cache` | `SYS-1206` |

Update memakai `multipart/form-data` bila ada logo. Jangan menggunakan value `primary_color` arbitrary sebagai action color frontend sampai `SYS-1204` memutuskan contrast-safe runtime theming.

### 17. Backup dan restore

Semua endpoint bersifat high risk, wajib audit event, rate limit, permission, dan exact-target confirmation.

| ID | Method | Target path | Status | Permission | Checklist |
|---|---|---|---|---|---|
| `EP-BACKUP-001` | GET | `/api/v1/backup/artifacts` | `NEW` | `view_backup` | `BACKUP-1220`–`BACKUP-1221` |
| `EP-BACKUP-002` | POST | `/api/v1/backup/artifacts` | `ADAPT` | create database/uploads backup | `BACKUP-1222` |
| `EP-BACKUP-003` | POST | `/api/v1/backup/imports` | `ADAPT` | `import_backup` | `BACKUP-1223` |
| `EP-BACKUP-004` | POST | `/api/v1/backup/artifacts/{id}/verify` | `ADAPT` | `verify_backup` | `BACKUP-1224` |
| `EP-BACKUP-005` | GET | `/api/v1/backup/artifacts/{id}/download` | `ADAPT` | `download_backup` | `BACKUP-1225` |
| `EP-BACKUP-006` | DELETE | `/api/v1/backup/artifacts/{id}` | `ADAPT` | `delete_backup` | `BACKUP-1226` |
| `EP-BACKUP-007` | POST | `/api/v1/backup/artifacts/{id}/restore-uploads` | `ADAPT` | super admin + `restore_uploads_backup` | `BACKUP-1227`, `BACKUP-1229` |
| `EP-BACKUP-008` | POST | `/api/v1/backup/artifacts/{id}/restore-database` | `DEFERRED` | super admin + flag + `restore_database_backup` | `BACKUP-1228` |

Frontend dilarang menampilkan `EP-BACKUP-008` hanya karena permission ada. Backend feature flag, environment policy, dan runbook harus sama-sama aktif.

## Frontend endpoint modules

Setiap group di atas mempunyai satu module API dan query/mutation composable. Contoh struktur:

```text
features/pembanding/
├── api/
│   ├── pembanding.api.ts
│   ├── pembanding.keys.ts
│   └── pembanding.contract.ts
├── composables/
│   ├── usePembandingListQuery.ts
│   ├── usePembandingDetailQuery.ts
│   ├── useCreatePembandingMutation.ts
│   └── useUpdatePembandingMutation.ts
└── ...
```

`*.api.ts` adalah satu-satunya tempat feature memanggil generated client. Page dan visual component tidak memanggil endpoint langsung.

## Query key registry

| Domain | Root query key |
|---|---|
| Public settings | `['settings', 'public']` |
| Dashboard | `['dashboard']` |
| Dictionaries | `['dictionaries']` |
| Locations | `['locations']` |
| Geo admin | `['geo']` |
| Pembanding | `['pembandings']` |
| Duplicate submissions | `['pembanding-submissions']` |
| Imports | `['pembanding-imports']` |
| Users | `['users']` |
| Roles | `['roles']` |
| Permissions | `['permissions']` |
| Invitations | `['data-contributor-invitations']` |
| Registration requests | `['data-contributor-registration-requests']` |
| Moderation | `['moderation']` |
| Export runs | `['exports']` |
| Search | `['search']` |
| Notifications | `['notifications']` |
| Activity logs | `['activity-logs']` |
| System settings | `['settings', 'system']` |
| Backup artifacts | `['backup', 'artifacts']` |

Query key selalu array dan memasukkan seluruh variable yang mengubah response. Object filter harus dinormalisasi agar nilai kosong tidak menciptakan cache entry berbeda secara tidak sengaja.

## Mutation invalidation matrix

| Mutation group | Query yang minimal di-invalidasi |
|---|---|
| Pembanding create/update/delete/resolve | Pembanding list/map/detail/history, dashboard, search, moderation sesuai dampak |
| Dictionary mutation | Dictionary type, definitions/counts, form-options terkait |
| Geo mutation | Geo resource, child/parent location lookup, form-options terkait |
| User mutation | Users; auth/me jika current user terdampak |
| Role/permission mutation | Roles, permissions, users terkait, auth/me bila current user terdampak |
| Invitation/request mutation | Invitations dan registration requests |
| Moderation transition | Moderation, pembanding, dashboard, activity logs |
| Import row/finalize | Import batch/row; pembanding/dashboard setelah finalization menghasilkan data |
| Export run/retry | Export run list/detail dan notifications |
| Notification read | Notifications summary/list |
| Setting update | System settings dan public settings bila field publik berubah |
| Backup mutation | Backup artifacts/status dan activity logs |

Invalidation dilakukan setelah mutation sukses. Optimistic update hanya dipakai bila rollback sederhana dan aman; destructive, moderation, import finalize, export, serta backup tidak dioptimistic-update.

## Download dan blob endpoints

Endpoint berikut mengembalikan file/blob, bukan JSON:

- `EP-PEM-021` staged duplicate image.
- `EP-IMPORT-009` staged import row image.
- `EP-EXPORT-007` export run file.
- `EP-EXPORT-008` synchronous export file.
- `EP-BACKUP-005` backup artifact.

Aturan frontend:

- Request tetap mengirim session cookie.
- Periksa status dan `Content-Type` sebelum membuat object URL.
- Ambil nama file dari `Content-Disposition` yang sudah disanitasi.
- Revoke object URL setelah dipakai.
- 401/403/404/410 menghasilkan feedback aplikasi, bukan tab kosong.
- File besar sebaiknya di-stream atau memakai short-lived signed URL yang terikat authorization.

## Polling endpoints

| Endpoint | Mulai polling | Berhenti polling |
|---|---|---|
| `EP-IMPORT-003` | Batch queued/processing | completed/failed/cancelled/expired |
| `EP-EXPORT-005` | Run queued/processing | completed/failed/expired |
| `EP-BACKUP-001` | Artifact sedang dibuat/imported/verified | ready/failed/corrupt/deleted |
| `EP-NOTIF-001` | Setelah auth bila polling dipilih | Logout/hidden policy/real-time replacement |

Gunakan backoff, pause saat offline, dan hindari polling ganda dari topbar serta page yang sama.

## Mapping fase checklist ke endpoint

| Checklist phase | Endpoint wajib READY sebelum gate |
|---|---|
| Phase 1 / `GATE-01` | `EP-AUTH-001`–`EP-AUTH-006`, `EP-APP-001`, `EP-DASH-001`, `EP-PEM-003`–`004`, `EP-PEM-020`–`022`, `EP-DICT-010`–`014`, `EP-GEO-001`–`004` sesuai P0 scope |
| Phase 4 / `GATE-04` | `EP-AUTH-001`–`006`, `EP-APP-001`, `EP-NOTIF-001` dan `003` bila notification masuk shell pertama |
| Phase 5 / `GATE-05` | `EP-DICT-002`, `EP-LOC-001`–`004` |
| Phase 6 / `GATE-06` | Seluruh `EP-PEM-*` yang dipakai parity Pembanding dan `EP-EXPORT-008` bila export-by-filter masuk pilot |
| Phase 7 / `GATE-07` | `EP-DASH-001`, `EP-SEARCH-001` |
| Phase 8 / `GATE-08` | Seluruh `EP-DICT-*`, `EP-LOC-*`, dan `EP-GEO-*` |
| Phase 9 / `GATE-09` | Seluruh `EP-USER-*`, `EP-ACL-*`, dan `EP-INV-*` |
| Phase 10 / `GATE-10` | Seluruh `EP-MOD-*` |
| Phase 11 / `GATE-11` | Seluruh `EP-IMPORT-*`, `EP-EXPORT-*`, dan notification completion dependency |
| Phase 12 / `GATE-12` | `EP-AUTH-005`–`006`, seluruh `EP-ACT-*`, `EP-SET-*`, `EP-NOTIF-*`, dan `EP-BACKUP-*` kecuali deferred database restore |

## Workflow sinkronisasi dengan checklist

Saat satu endpoint dikerjakan:

1. Ubah status di dokumen ini dari `NEW/ADAPT` menjadi `READY` hanya setelah route, policy, OpenAPI, dan tests tersedia.
2. Generate ulang client TypeScript di `hjar-web`.
3. Selesaikan task API terkait di checklist.
4. Implementasikan query/mutation frontend terkait.
5. Verifikasi invalidation, permission, error, dan async state.
6. Tandai task feature selesai.
7. Tutup gate fase hanya bila seluruh endpoint wajib pada mapping di atas sudah `READY`.

## Endpoint readiness checklist

Checklist berikut berlaku untuk setiap endpoint sebelum status `READY`:

- [ ] Route berada pada target versioned path.
- [ ] Method dan status HTTP sesuai semantics.
- [ ] Request validation memakai Form Request atau validator yang dapat didokumentasikan.
- [ ] Authorization policy/middleware diuji untuk allow dan deny.
- [ ] Response memakai Resource/DTO, bukan model mentah.
- [ ] OpenAPI memuat request, response, auth, pagination, dan error penting.
- [ ] Response tidak membocorkan secret/PII yang tidak diperlukan.
- [ ] Rate limit diterapkan untuk login/write/high-risk operations.
- [ ] Idempotency/conflict behavior terdokumentasi untuk mutation sensitif.
- [ ] Feature/contract tests hijau.
- [ ] Generated TypeScript client berhasil tanpa manual `any` patch.
- [ ] Query key/invalidation frontend sudah ditetapkan.
- [ ] Loading/error/permission UI sudah tersedia.
- [ ] Existing mobile/client regression tetap hijau bila endpoint lama terdampak.

## Endpoint yang tidak boleh dipakai langsung frontend

- Route `/app/*` yang mengembalikan Inertia props atau redirects.
- `/api/auth/login` dan `/api/auth/refresh` untuk web SPA.
- Public filesystem path untuk file berizin.
- Endpoint backup database restore sebelum `EP-BACKUP-008` keluar dari `DEFERRED`.
- Controller URL yang belum masuk OpenAPI hanya karena response kebetulan JSON.
- URL yang dikirim backend dan masih hardcoded `/app/*`; gunakan typed resource target atau frontend route name/params.

## OpenAPI integration gate

Frontend integration dianggap aman bila:

- `api.json` dapat diambil dari artifact build backend.
- Generated types tidak mempunyai path/method yang hilang dari katalog `READY`.
- Tidak ada endpoint feature yang memakai handwritten duplicate response type.
- Breaking-change check membedakan additive dan breaking change.
- Backend dan frontend CI menguji compatibility window release.
- Katalog ini dan `IMPLEMENTATION_CHECKLIST.md` diperbarui pada PR yang mengubah dependency endpoint.
