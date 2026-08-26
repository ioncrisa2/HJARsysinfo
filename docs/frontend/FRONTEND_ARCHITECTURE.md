# Arsitektur Frontend Terpisah HJAR Sysinfo

Status: proposed baseline  
Scope: routing, state management, reusable components, API integration, dan autentikasi  
Design system: [`DESIGN.md`](../../DESIGN.md)  
Component rules: [`COMPONENT_PATTERNS.md`](./COMPONENT_PATTERNS.md)

## Ringkasan keputusan

Frontend baru dibangun sebagai SPA Vue 3 terpisah. Laravel yang sekarang tidak ditulis ulang; Laravel berevolusi menjadi headless API serta tetap memiliki domain logic, policy, queue, storage, import/export, backup, dan audit trail.

Keputusan inti:

| Area | Keputusan |
|---|---|
| Language | TypeScript strict |
| Framework | Vue 3 Composition API + `<script setup>` |
| Build | Vite |
| Routing | Vue Router, named routes, lazy-loaded feature routes |
| Session/client state | Pinia |
| Server state | TanStack Vue Query |
| API contract | OpenAPI dari Laravel Scramble, lalu generate TypeScript types/client |
| UI | PrimeVue + Tailwind CSS, dibungkus oleh design-system primitives bila perlu |
| Web authentication | Laravel Sanctum SPA session cookie |
| Mobile/integration authentication | Bearer access token + rotating refresh token/abilities |
| Forms | Typed local draft + schema validation + Laravel 422 field errors |
| Testing | Vitest, Vue Test Utils, Mock Service Worker, Playwright |
| Deployment | `app.<domain>` untuk Vue dan `api.<domain>` untuk Laravel, tetap satu top-level domain |

Alasan pemisahan state: [Pinia](https://pinia.vuejs.org/introduction.html) cocok untuk state aplikasi yang dimiliki client, sedangkan [TanStack Vue Query](https://tanstack.com/query/latest/docs/framework/vue) mempunyai lifecycle cache, query keys, invalidation, background refetch, dan mutation khusus server state. Menggunakan Pinia untuk semua response API akan membuat cache manual yang sulit dijaga konsistensinya.

## Context dan batas sistem

```text
┌──────────────────────────────┐
│ hjar-web                     │
│ Vue Router + Pinia + Query   │
└──────────────┬───────────────┘
               │ HTTPS JSON / multipart / file stream
┌──────────────▼───────────────┐
│ hjar-api                     │
│ Laravel API + Sanctum        │
│ policies + services/actions  │
│ jobs + storage + audit       │
└──────────────┬───────────────┘
               │
       ┌───────┴────────┐
       │ DB / cache /   │
       │ queue / files  │
       └────────────────┘

Mobile dan aplikasi lain menggunakan hjar-api secara langsung.
```

Frontend tidak mempunyai business rule otoritatif. Ia boleh memberi validasi cepat dan menyembunyikan aksi yang tidak relevan, tetapi Laravel tetap menentukan validitas, ownership, permission, duplicate handling, state transition, dan batas operasi.

## Strategi repository

Rekomendasi akhir adalah dua repository/deployable project:

```text
hjar-api   # evolusi dari repository Laravel sekarang
hjar-web   # proyek Vue baru
```

Selama migrasi, repository sekarang tetap menjalankan Inertia. `hjar-web` dibangun terpisah dan modul dipindahkan satu per satu. Jangan memindahkan folder `resources/js` sekaligus lalu memutus aplikasi lama sebelum parity tercapai.

Kontrak OpenAPI adalah integration boundary. Pipeline `hjar-api` mempublikasikan `api.json`; pipeline `hjar-web` menghasilkan types/client dan gagal bila ada breaking contract yang belum diakomodasi.

## Stack dan dependency policy

### Dependency utama

- `vue`
- `vue-router`
- `pinia`
- `@tanstack/vue-query`
- `primevue`, `primeicons`, dan theme preset yang dikendalikan token
- `tailwindcss`
- `openapi-typescript` dan client tipis seperti `openapi-fetch`, atau generator setara yang disepakati tim
- Library schema form yang mendukung TypeScript; pilih satu saja dan standarkan

### Dependency rule

- Gunakan exact major range yang disetujui dan lockfile wajib di-commit.
- Library baru membutuhkan alasan yang tidak bisa dipenuhi dengan Vue/platform API sederhana.
- Satu masalah tidak boleh mempunyai dua library global, misalnya Axios dan Fetch wrapper berjalan paralel tanpa masa transisi jelas.
- Leaflet, Chart.js, Cropper, dan library berat lain di-lazy-load per feature.
- Jalankan audit license dan vulnerability pada CI.

## Routing

Vue Router menyediakan global/per-route navigation guards dan typed route meta untuk auth/permission UX; dokumentasi resminya menjelaskan [navigation guards](https://router.vuejs.org/guide/advanced/navigation-guards.html), [route meta](https://router.vuejs.org/guide/advanced/meta.html), dan [lazy-loaded routes](https://router.vuejs.org/guide/advanced/lazy-loading.html).

### Prinsip

- Semua route memakai `name`; komponen tidak menyebarkan path literal.
- Route feature di-lazy-load dengan dynamic import.
- Parent route menentukan layout dan default `requiresAuth`.
- Permission dideklarasikan dalam typed `meta.permissions`.
- Guard client meningkatkan UX, tetapi bukan security boundary.
- Filter, sort, page, per-page, dan shareable tab berada pada query string.
- Breadcrumb label statis berasal dari route meta; nama record berasal dari query detail.
- Form dirty memasang `onBeforeRouteLeave`.
- `afterEach` memperbarui title, mengumumkan navigasi untuk assistive technology, dan mengirim telemetry bila tersedia.

### Canonical route map

Karena SPA berada pada host sendiri, prefix `/app` tidak diperlukan. Redirect permanen dari URL lama dijaga selama masa transisi.

| Name | Path baru | Page | Permission/akses |
|---|---|---|---|
| `auth.login` | `/login` | Login | Guest |
| `contributor.register` | `/register-data-contributor/:token` | Contributor registration | Public token |
| `contributor.submitted` | `/register-data-contributor/submitted` | Registration submitted | Public |
| `dashboard` | `/` | Dashboard | Authenticated |
| `pembanding.list` | `/pembandings` | List/filter/map | `view_any_data::pembanding` |
| `pembanding.create` | `/pembandings/new` | Create | `create_data::pembanding` |
| `pembanding.detail` | `/pembandings/:id` | Detail | Policy result dari API |
| `pembanding.edit` | `/pembandings/:id/edit` | Edit | update permission + policy |
| `pembanding.history` | `/pembandings/:id/history` | History | View policy |
| `pembanding.duplicate-review` | `/pembanding-submissions/:submissionId/duplicates` | Duplicate review | Create permission |
| `imports.list` | `/imports` | Bulk import list | `bulk_import_data::pembanding` |
| `imports.detail` | `/imports/:batchId` | Batch detail | Same |
| `imports.row-edit` | `/imports/:batchId/rows/:rowId/edit` | Staged row edit | Same |
| `moderation` | `/moderation` | Moderation desk | `view_moderation` |
| `master-data.index` | `/master-data` | Dictionary overview | `view_master_data` |
| `master-data.detail` | `/master-data/:type` | Dictionary CRUD | `view_master_data` |
| `geo-data` | `/geo/:resource?` | Geo CRUD | `view_geo_data` |
| `exports` | `/exports` | Export builder/runs | `view_export` |
| `users.list` | `/users` | Users | `view_any_user` |
| `users.create` | `/users/new` | Create user | `create_user` |
| `users.edit` | `/users/:id/edit` | Edit user | `update_user` |
| `contributor-invitations` | `/contributor-invitations` | Invitations/requests | `manage_data_contributor_invitations` |
| `access-control` | `/access-control` | Roles/permissions | `view_access_control` |
| `backup` | `/system/backup` | Backup/restore | `view_backup` |
| `settings` | `/system/settings` | System settings | `view_settings` |
| `activity-logs.list` | `/system/activity-logs` | Audit list | `view_activity_log` |
| `activity-logs.detail` | `/system/activity-logs/:id` | Audit detail | `view_activity_log` |
| `search` | `/search` | Global search | `view_search` |
| `profile` | `/profile` | Current user profile | Authenticated |
| `forbidden` | `/403` | Forbidden | Public shell |
| `not-found` | `/:pathMatch(.*)*` | Not found | Public shell |

### Typed route meta

```ts
declare module 'vue-router' {
  interface RouteMeta {
    title: string
    layout?: 'app' | 'auth' | 'public'
    requiresAuth: boolean
    permissions?: string[]
    permissionMode?: 'all' | 'any'
    breadcrumb?: string
  }
}
```

Guard flow:

```text
navigation requested
  -> ensure auth session initialized once
  -> guest accessing private route? redirect login?redirect=<fullPath>
  -> authenticated user accessing guest-only route? redirect dashboard
  -> permission metadata unmet? show /403
  -> lazy-load route component
  -> page query asks API; API policy remains authoritative
```

## State management

### Ownership matrix

| State | Owner | Contoh | Persistence |
|---|---|---|---|
| Server state | Vue Query | Pembanding list/detail, dictionaries, dashboard, export runs | Memory cache; optional persisted cache hanya bila ada kebutuhan offline yang jelas |
| Session state | `useAuthStore` | User, roles, permissions, initialization status | Memory; session dipulihkan dari cookie melalui `/me` |
| UI preference | `useUiPreferencesStore` | Sidebar collapsed, density | Local storage dengan schema/version |
| URL state | Vue Router | Filter, sort, page, selected tab yang shareable | URL/history |
| Local component state | `ref/reactive` | Dialog open, hover, draft filter sebelum apply | Tidak persisten |
| Form draft | Form composable/page | Create/edit values dan dirty state | Memory; session storage hanya untuk flow panjang yang disepakati |
| Job state | Vue Query | Import/export/backup status | Polling/cache dari API |

### Store yang diperbolehkan

`useAuthStore`:

- `user`, `roles`, `permissions`
- `initialized`, `authenticated`
- `initialize()`, `login()`, `logout()`, `can()`, `canAny()`

`useUiPreferencesStore`:

- Sidebar expanded/collapsed.
- Table density jika product memerlukannya.
- Preference yang benar-benar lintas route.

Store yang tidak dibuat:

- `usePembandingStore` hanya untuk menyimpan hasil list/detail API.
- `useLoadingStore` global yang menyatukan semua request.
- `useFormStore` generik yang menahan seluruh form aplikasi.
- Store per page tanpa kebutuhan lintas page.

### Query defaults

Baseline yang harus dituning lewat pengukuran:

- Retry query GET maksimal 2 kali untuk network/5xx; jangan retry 401/403/404/422.
- Mutation tidak auto-retry kecuali endpoint idempotent dan strategi eksplisit tersedia.
- `refetchOnWindowFocus` aktif untuk data operasional yang cepat berubah; nonaktif untuk dictionary stabil bila tidak diperlukan.
- Dictionary/location parent list dapat memakai stale time 15–60 menit.
- Dashboard/list normal 30–120 detik.
- Notifications, moderation, dan running jobs 5–15 detik selama panel relevan.
- Query key selalu memasukkan seluruh filter yang mengubah response. Ini mengikuti prinsip query key resmi TanStack Query.

## API client dan kontrak data

### Satu client, satu error model

`src/shared/api/client.ts` bertanggung jawab atas:

- Base URL dari environment.
- `Accept: application/json`.
- `credentials: 'include'` untuk web.
- Correlation/request ID bila backend menyediakannya.
- Parsing response envelope.
- Normalisasi network error, timeout/abort, 401, 403, 404, 409, 419, 422, 429, dan 5xx.
- Tidak menampilkan toast; presentation tetap milik caller.

```ts
export type ApiError = {
  status: number | null
  code: string
  message: string
  fieldErrors: Record<string, string[]>
  requestId?: string
  retryAfterSeconds?: number
}
```

### Compatibility policy

API v1 sekarang memakai envelope `status`, `message`, `data` melalui `ApiResponse`. Jangan mengubah shape yang sudah dipakai mobile secara diam-diam. Rapikan v1 secara backward-compatible dan gunakan contract tests. Breaking change membutuhkan versi baru atau masa deprecation terukur.

Standar minimal:

- Resource tunggal: `data` object.
- Collection: `data` array ditambah `meta` dan `links` yang konsisten.
- Validation: status 422, `message`, dan map `errors`.
- Conflict/duplicate: 409 dengan machine-readable `code` dan payload resolusi.
- Rate limit: 429 dengan `Retry-After`.
- Async job: 202 dengan `job/run` resource URL dan status awal.
- Date/time: ISO 8601 dengan timezone; date-only tetap `YYYY-MM-DD`.
- Currency: integer minor/whole unit yang didokumentasikan; frontend tidak menebak string berformat.
- Enum/status: stable machine value dan label terpisah bila dibutuhkan.

### API gap untuk parity web

API yang sudah ada mencakup auth token, current user/profile, dictionaries read, locations, pembanding CRUD/map/similarity/history/delete request. Endpoint berikut masih dibutuhkan untuk SPA penuh:

| Domain | Kebutuhan endpoint | Prioritas |
|---|---|---|
| Web session | CSRF cookie flow, login session, logout session, current session | P0 |
| App context | Public settings minimal, effective permissions/capabilities; menu dibangun frontend dari permission | P0 |
| Dashboard | Summary dan widget endpoints, mendukung permission/scoped data | P0 |
| Pembanding | Form options, creator options, duplicate submission/review/resolve, export by filter | P0 |
| Master data | Admin CRUD, status, reorder pada namespace API resmi | P0 |
| Geo data | Admin list/filter/CRUD dan cascading lookup yang konsisten | P0 |
| Users | List/filter/create/update/status/delete/bulk delete | P1 |
| Access control | Roles/permissions CRUD dan assignment | P1 |
| Contributor invitations | List/create/revoke serta registration request accept/reject | P1 |
| Moderation | Queue list, approve, reject, restore, force delete | P1 |
| Bulk import | Upload, batch list/detail, row edit/image/retry, selection, bulk apply, finalize | P1 |
| Export | Preview, create run, status/list, retry, authorized download | P1 |
| Notifications | List unread, mark one/all read, optional real-time transport | P1 |
| Search | Global search/filter/pagination | P2 |
| Profile | Current profile/password, session/device management bila dibutuhkan | P1 |
| Activity logs | List/filter/detail | P2 |
| Settings | Read/update/logo upload/cache clear | P2 |
| Backup | Catalog/create/import/verify/download/restore/delete dengan step-up confirmation | P2/high risk |

Endpoint admin tidak boleh menjadi controller JSON tipis yang menduplikasi logic controller Inertia. Ekstrak use case ke Action/Service lalu panggil dari adapter API selama periode coexistence.

## Autentikasi

Laravel Sanctum secara resmi merekomendasikan session-cookie authentication untuk first-party SPA dan bearer token untuk mobile/third party. SPA dan API boleh berada di subdomain berbeda selama berbagi top-level domain dan stateful/CORS/cookie dikonfigurasi benar; lihat [Laravel Sanctum SPA authentication](https://laravel.com/docs/12.x/sanctum#spa-authentication).

### Web first-party flow

```text
1. GET https://api.example.com/sanctum/csrf-cookie
2. POST https://api.example.com/api/v1/auth/session
   credentials: include
3. Laravel memvalidasi credentials, membuat session, regenerate session ID
4. GET /api/v1/auth/me
5. Auth store menyimpan user/roles/permissions di memory
6. Request berikutnya memakai HttpOnly session cookie otomatis
```

Requirement backend:

- Aktifkan `statefulApi()` pada bootstrap Laravel.
- `SANCTUM_STATEFUL_DOMAINS` memuat origin frontend beserta port development.
- Session cookie memakai `Secure` di production, domain parent yang benar, dan SameSite yang sesuai topologi.
- CORS hanya mengizinkan origin frontend yang eksplisit dan `supports_credentials=true`; jangan wildcard dengan credentials.
- Login session mempunyai rate limit, generic credential error, session regeneration, dan audit event.
- Logout menginvalidasi session dan regenerate CSRF token.

Requirement frontend:

- Selalu `credentials: 'include'`.
- Jangan menyimpan access/refresh token di local storage, session storage, IndexedDB, Pinia persistence, atau cookie yang dapat dibaca JavaScript.
- Pada 401: hentikan protected queries, reset auth memory, simpan destination aman, redirect login.
- Pada 419: refresh CSRF cookie satu kali untuk mutation yang aman diulang; jangan loop.
- Pada 403: pertahankan session dan tampilkan forbidden state.
- Login redirect hanya menerima relative internal path untuk mencegah open redirect.

### Mobile dan integration flow

- Gunakan bearer access token dengan device name dan ability/scope minimum.
- Refresh token diputar dan reuse harus terdeteksi/revoked.
- Mobile menyimpan token pada secure platform storage.
- Integrasi service-to-service memakai credential terpisah dari akun manusia jika use case tersedia.
- Token endpoint mobile tidak digunakan oleh SPA web.

### Permission model

- `/auth/me` mengembalikan permission efektif, bukan hanya role.
- Route meta mendeklarasikan permission UX.
- Action component menggunakan `can/canAny` dari auth store.
- Laravel policy/middleware selalu mengecek ulang.
- Perubahan permission me-reset/refetch session context dan query sensitif.
- Response yang pernah di-cache untuk user A tidak boleh terlihat setelah login user B; query cache dibersihkan saat logout/session change.

## Navigation dan application bootstrap

Hindari satu bootstrap response raksasa yang mengikat semua client. Startup web menjalankan request paralel minimum:

1. `/api/v1/auth/me` untuk user dan permission.
2. `/api/v1/settings/public` untuk nama/logo/branding aman.
3. `/api/v1/notifications?unread=1&limit=5` setelah auth berhasil dan jika feature aktif.

Menu disusun dari typed frontend route definitions dan difilter oleh permission efektif. Backend tetap menjadi sumber authorization; frontend menjadi sumber information architecture web.

## File upload, download, dan async jobs

### Upload

- Gunakan `FormData`; jangan menetapkan `Content-Type` manual agar boundary benar.
- Validasi client hanya memberi feedback awal; backend tetap memeriksa MIME, ukuran, dan isi.
- Tampilkan progress hanya bila transport benar-benar menyediakannya.
- Abort request saat user membatalkan atau meninggalkan flow jika aman.

### Download

- Download berizin tidak boleh mengandalkan public storage URL.
- Pilih stream dengan cookie auth atau short-lived signed URL.
- Nama file berasal dari `Content-Disposition` yang disanitasi.
- Expired/forbidden download mempunyai feedback, bukan membuka tab kosong.

### Jobs

- Import/export/backup menggunakan resource status dengan state machine terdokumentasi: queued, processing, completed, failed, cancelled/expired bila relevan.
- Polling memakai backoff dan hanya aktif untuk job non-terminal.
- Retry adalah mutation terpisah dan tidak membuat job ganda tanpa konfirmasi/idempotency.
- Notification boleh menjadi enhancement; halaman status tetap sumber kebenaran.

## Error handling

| Status | UX |
|---|---|
| Network/offline | Pertahankan cache jika ada, banner offline, retry |
| 401 | Reset session state dan redirect login |
| 403 | Forbidden page/panel; jangan logout |
| 404 | Not found dengan kembali ke collection |
| 409 | Conflict/duplicate resolution UI |
| 419 | CSRF recovery satu kali untuk request repeatable |
| 422 | Map field errors + form summary |
| 429 | Disable retry sementara, tampilkan waktu tunggu |
| 5xx | Error state dengan request ID dan retry yang aman |

Unhandled error ditangkap pada application boundary dan dikirim ke observability tanpa merekam password, token, nomor telepon lengkap, atau payload sensitif.

## Internationalization dan formatting

- UI fase pertama memakai Bahasa Indonesia, tetapi string tidak disebarkan ke helper/domain logic.
- Gunakan satu formatter module berbasis `Intl` untuk IDR, angka, persentase, date, dan date-time.
- API mengirim raw value; frontend memformat menurut locale.
- Field name audit log memiliki dictionary label terpusat.
- Jangan parse formatted currency kembali menjadi angka tanpa input adapter yang diuji.

## Testing strategy

### Unit

- Formatter, URL serialization, permission helpers, schema mapping, query key factories.
- Composable headless seperti selection, debounce, dan cascading location.

### Component

- UI primitive states dan accessibility contract.
- Complex form section, filter panel, data table states, dialogs.
- API di-mock pada network boundary, bukan dengan memalsukan internal implementation.

### Integration/E2E

- Login/logout/session expiry.
- Permission-based routes/actions.
- Pembanding list/create/edit/detail/delete request/duplicate resolution.
- Bulk import dan async completion/failure.
- Export create/poll/download/retry.
- Backup destructive safeguards.
- Deep link, browser back/forward, refresh pada route dinamis.
- Keyboard-only flow dan mobile viewport untuk flow kritis.

### Contract

- Generate client dari OpenAPI dalam CI.
- Jalankan breaking-change detection terhadap API baseline.
- Fixture response harus berasal dari contract yang sama.
- E2E staging menggunakan build API dan web yang compatible.

## Observability

- Setiap frontend error event memuat release version, route name, request ID, dan user ID pseudonymous bila diizinkan.
- Web vitals dan route load timing dicatat tanpa PII.
- API client mencatat latency/status teragregasi, bukan request body.
- Source map production diunggah ke error tracker dan tidak dipublikasikan bebas.
- Feature flag/cutover telemetry membandingkan error rate Inertia vs SPA selama migrasi.

## Environment dan deployment

Contoh environment frontend:

```dotenv
VITE_APP_NAME="HJAR Sysinfo"
VITE_API_BASE_URL="https://api.example.com"
VITE_RELEASE="local"
```

Semua `VITE_*` bersifat publik. Secret, signing key, database credential, mail credential, dan token integrasi dilarang berada dalam frontend environment.

Deployment requirements:

- SPA server fallback hanya untuk route frontend, bukan asset/API path.
- Static assets memakai content hash dan cache immutable.
- `index.html` memakai no-cache/revalidation agar release baru terambil.
- CSP, HSTS, frame-ancestors, referrer policy, dan permissions policy dikonfigurasi.
- Health check frontend memverifikasi asset release; health check API terpisah.
- Rollback frontend dan API dapat dilakukan independen selama contract compatibility dijaga.

## Keputusan yang sengaja ditunda

- SSR/Nuxt: belum dibutuhkan untuk authenticated internal application; evaluasi hanya bila public/SEO surface berkembang.
- Offline write/sync: berisiko konflik pada sumber data penting; jangan dibuat tanpa conflict model dan audit requirement.
- WebSocket: notifications/job status mulai dengan bounded polling; adopsi real-time setelah kebutuhan dan beban terukur.
- Micro-frontend: tidak sesuai ukuran tim/modul sekarang.
- Dynamic runtime theme penuh: primary color setting saat ini belum mengendalikan keseluruhan token; jangan menerapkan warna arbitrary ke tombol tanpa contrast generation dan validation.

## Definition of done arsitektur

Arsitektur dianggap diterapkan bila:

- Tidak ada dependency Inertia pada `hjar-web`.
- Tidak ada hardcoded backend URL di page/component.
- Semua server state menggunakan query layer dan tidak diduplikasi di Pinia.
- Auth web memakai HttpOnly session cookie; token tidak dapat dibaca JavaScript.
- Semua protected route memiliki meta dan API tetap mengotorisasi.
- OpenAPI-generated types menjadi bagian CI.
- Deep link/refresh/back-forward bekerja.
- Loading/error/empty/forbidden states lengkap.
- Flow kritis lulus component, E2E, accessibility, dan responsive tests.
