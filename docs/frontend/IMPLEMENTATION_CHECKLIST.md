# Checklist Implementasi Frontend Terpisah HJAR Sysinfo

Status: ready for planning  
Target outcome: Vue SPA mencapai feature parity, stabil di production, lalu Inertia dapat dihentikan dengan aman  
Dokumen terkait: [`DESIGN.md`](../../DESIGN.md), [`COMPONENT_PATTERNS.md`](./COMPONENT_PATTERNS.md), [`FRONTEND_ARCHITECTURE.md`](./FRONTEND_ARCHITECTURE.md), [`API_ENDPOINTS.md`](./API_ENDPOINTS.md)

## Cara memakai checklist

Setiap item mempunyai ID stabil agar dapat dipindahkan ke issue tracker. Tambahkan owner, target release, dan link PR tanpa mengubah ID.

Status yang disarankan:

- `[ ]` belum dimulai
- `[-]` sedang dikerjakan
- `[x]` selesai dan memenuhi acceptance criteria
- `[!]` blocked; tulis blocker tepat di bawah item

Sebuah fase selesai hanya setelah quality gate fase terpenuhi. Jangan menandai halaman selesai hanya karena tampilannya sudah muncul; API contract, error state, permissions, tests, responsive behavior, dan accessibility termasuk definition of done.

## Definition of done global

Setiap feature wajib memenuhi semua berikut:

- [ ] `DOD-001` Route memakai named route, lazy loading, title, breadcrumb, auth meta, dan permission meta.
- [ ] `DOD-002` Tidak ada import Inertia, hardcoded backend base URL, atau pemanggilan `fetch` langsung dari page/component.
- [ ] `DOD-003` Request memakai typed API client dan tipe yang berasal dari OpenAPI atau schema resmi.
- [ ] `DOD-004` Server state memakai Vue Query; session/UI state saja yang memakai Pinia.
- [ ] `DOD-005` Filter/sort/page/per-page/shareable tab tersimpan di URL dan browser back/forward bekerja.
- [ ] `DOD-006` Loading, background refetch, empty, filtered-empty, error, retry, 401, 403, dan 404 ditangani sesuai konteks.
- [ ] `DOD-007` Mutation menangani loading, double submit, sukses, 409/422, 429, dan 5xx.
- [ ] `DOD-008` Permission visibility di UI cocok dengan permission API, tetapi backend tetap menjadi enforcement.
- [ ] `DOD-009` Keyboard, focus-visible, accessible name, error announcement, dan target sentuh diuji.
- [ ] `DOD-010` Layout diuji pada 360px, 768px, 1024px, dan 1440px tanpa horizontal page overflow.
- [ ] `DOD-011` Unit/component tests menutup logic penting; E2E menutup happy path dan satu failure path.
- [ ] `DOD-012` Tidak ada data sensitif, token, atau payload personal pada console/error telemetry.
- [ ] `DOD-013` Formatter tanggal, angka, IDR, telepon, dan label domain memakai helper terpusat.
- [ ] `DOD-014` Tidak ada regression yang diketahui terhadap flow Inertia yang digantikan.
- [ ] `DOD-015` Dokumentasi route, API dependency, dan operational note diperbarui.
- [ ] `DOD-016` Seluruh endpoint feature berstatus `READY` di `API_ENDPOINTS.md`, tersedia di OpenAPI, dan generated client tidak memerlukan handwritten `any` patch.

## Endpoint dependency map

`API_ENDPOINTS.md` adalah sumber tracking endpoint frontend. Status `READY` hanya diberikan setelah route, authorization, OpenAPI, tests, dan response error selesai.

| Feature phase | Endpoint group |
|---|---|
| Auth dan shell | `EP-AUTH-*`, `EP-APP-*`, `EP-NOTIF-*` |
| Reference utilities | `EP-DICT-*`, `EP-LOC-*` |
| Pembanding | `EP-PEM-*` |
| Dashboard/search | `EP-DASH-*`, `EP-SEARCH-*` |
| Master/geo | `EP-DICT-*`, `EP-LOC-*`, `EP-GEO-*` |
| Identity/access | `EP-USER-*`, `EP-ACL-*`, `EP-INV-*` |
| Moderation | `EP-MOD-*` |
| Import/export | `EP-IMPORT-*`, `EP-EXPORT-*`, `EP-NOTIF-*` |
| System modules | `EP-AUTH-005`–`006`, `EP-ACT-*`, `EP-SET-*`, `EP-NOTIF-*`, `EP-BACKUP-*` |

Sebelum menutup gate feature, verifikasi seluruh endpoint group terkait pada bagian "Mapping fase checklist ke endpoint" di [`API_ENDPOINTS.md`](./API_ENDPOINTS.md).

## Phase 0 — Baseline dan keputusan produk

Tujuan: mengunci scope dan baseline sehingga tim tidak membangun ulang UI sekaligus mengubah business rule tanpa kontrol.

- [ ] `FE-0001` Tetapkan nama repository frontend: `hjar-web`.
- [ ] `FE-0002` Tetapkan owner teknis frontend dan owner kontrak API.
- [ ] `FE-0003` Tetapkan domain development, staging, dan production untuk web/API.
- [ ] `FE-0004` Setujui Vue SPA tanpa SSR untuk authenticated app.
- [ ] `FE-0005` Setujui TypeScript strict sebagai requirement.
- [ ] `FE-0006` Setujui Vue Router + Pinia + TanStack Vue Query.
- [ ] `FE-0007` Setujui first-party web auth memakai Sanctum session cookie.
- [ ] `FE-0008` Setujui mobile/integration tetap memakai bearer + refresh token.
- [ ] `FE-0009` Setujui URL canonical baru dan redirect URL `/app/*` lama.
- [ ] `FE-0010` Inventarisasi role dan permission efektif production.
- [ ] `FE-0011` Rekam screenshot dan behavior baseline seluruh route Inertia desktop/mobile.
- [ ] `FE-0012` Catat response, validation, empty state, dan error behavior setiap flow lama.
- [ ] `FE-0013` Tetapkan browser support matrix.
- [ ] `FE-0014` Tetapkan WCAG 2.2 AA sebagai target flow utama.
- [ ] `FE-0015` Tetapkan performance budget dan error-rate SLO awal.
- [ ] `FE-0016` Setujui [`DESIGN.md`](../../DESIGN.md) sebagai baseline atau revisi creative north star/palet bersama stakeholder.
- [ ] `FE-0017` Buat `PRODUCT.md` bila tim ingin menangkap persona, lingkungan kerja, prioritas produk, dan anti-reference secara formal.
- [ ] `FE-0018` Tetapkan freeze: tidak ada redesign besar saat parity migration kecuali tercatat sebagai scope eksplisit.

Quality gate Phase 0:

- [ ] `GATE-00` Arsitektur, auth model, URL strategy, scope parity, dan design baseline disetujui.

## Phase 1 — Kontrak API dan backend prerequisites

Tujuan: frontend tidak mengimplementasikan layar berdasarkan response ad hoc.

### Contract foundation

- [ ] `API-0101` Ekspor OpenAPI spec dari Laravel CI sebagai artifact versioned.
- [ ] `API-0102` Tambahkan breaking-change detection terhadap baseline API.
- [ ] `API-0103` Dokumentasikan envelope sukses/error v1 dan pagination secara konsisten.
- [ ] `API-0104` Tambahkan stable machine `code` untuk duplicate/conflict/job failure yang perlu branching UI.
- [ ] `API-0105` Tambahkan `request_id` pada error response/log correlation.
- [ ] `API-0106` Standarkan ISO 8601 timezone, date-only, boolean, integer currency, dan nullable fields.
- [ ] `API-0107` Pastikan semua list menerima pagination limit yang dibatasi server.
- [ ] `API-0108` Standarkan search/filter/sort parameter dan invalid combination response.
- [ ] `API-0109` Audit semua API Resource agar tidak membocorkan field internal atau secret.
- [ ] `API-0110` Tambahkan contract/feature test untuk 401, 403, 404, 409, 422, 429, dan 5xx shape.
- [ ] `API-0111` Sinkronkan status endpoint `READY/ADAPT/NEW/LEGACY/DEFERRED` pada `API_ENDPOINTS.md` dengan route dan OpenAPI aktual.
- [ ] `API-0112` Pastikan setiap endpoint `READY` dapat dihasilkan menjadi TypeScript client tanpa response type manual yang duplikatif.

### Web session

- [ ] `API-0120` Aktifkan Laravel `statefulApi()`.
- [ ] `API-0121` Konfigurasi `SANCTUM_STATEFUL_DOMAINS` per environment termasuk dev port.
- [ ] `API-0122` Konfigurasi CORS dengan allowlist origin eksplisit dan credentials.
- [ ] `API-0123` Konfigurasi session cookie domain, Secure, HttpOnly, dan SameSite.
- [ ] `API-0124` Implementasikan `POST /api/v1/auth/session` untuk login cookie/session.
- [ ] `API-0125` Regenerate session ID setelah login.
- [ ] `API-0126` Implementasikan `DELETE /api/v1/auth/session` dan invalidasi session.
- [ ] `API-0127` Normalisasi `GET /api/v1/auth/me` berisi user, roles, permissions efektif.
- [ ] `API-0128` Rate-limit login dan gunakan pesan credential failure generik.
- [ ] `API-0129` Tambahkan tests login, logout, CSRF, expired session, disabled user, dan role access.
- [ ] `API-0130` Pastikan bearer flow mobile lama tetap lulus regression tests.

### Parity endpoints P0

- [ ] `API-0140` Endpoint public settings minimal: app name/logo/version yang aman.
- [ ] `API-0141` Dashboard summary endpoint.
- [ ] `API-0142` Dashboard widget endpoints/scoped response.
- [ ] `API-0143` Pembanding form-options endpoint.
- [ ] `API-0144` Pembanding creator-options endpoint.
- [ ] `API-0145` Duplicate submission detail/image endpoint.
- [ ] `API-0146` Duplicate use-existing dan replace endpoints dengan 409 semantics yang jelas.
- [ ] `API-0147` Master-data admin CRUD/status/reorder endpoints pada `/api/v1`.
- [ ] `API-0148` Geo-data admin list/filter/CRUD endpoints pada `/api/v1`.
- [ ] `API-0149` Tambahkan authorization test untuk setiap endpoint P0.

Quality gate Phase 1:

- [ ] `GATE-01` Web session bekerja lintas subdomain pada staging, OpenAPI dapat menghasilkan client, API P0 lulus contract/security tests, dan mobile regression tetap hijau.

## Phase 2 — Scaffold proyek `hjar-web`

Tujuan: repository siap menerima feature tanpa menambah technical debt baru.

- [ ] `FE-0201` Buat Vue 3 + Vite + TypeScript project.
- [ ] `FE-0202` Aktifkan TypeScript strict, `noUncheckedIndexedAccess`, dan consistent casing.
- [ ] `FE-0203` Konfigurasi path aliases (`@/app`, `@/features`, `@/shared`).
- [ ] `FE-0204` Pasang Vue Router, Pinia, Vue Query, PrimeVue, PrimeIcons, dan Tailwind.
- [ ] `FE-0205` Konfigurasi ESLint, formatter, dan import ordering.
- [ ] `FE-0206` Pasang Vitest, Vue Test Utils, Mock Service Worker, dan Playwright.
- [ ] `FE-0207` Buat struktur folder sesuai architecture document.
- [ ] `FE-0208` Buat typed environment schema dan gagal cepat jika variable wajib kosong.
- [ ] `FE-0209` Buat `.env.example` tanpa secret.
- [ ] `FE-0210` Konfigurasi dev proxy atau local domains untuk Sanctum cookie.
- [ ] `FE-0211` Integrasikan OpenAPI type/client generation.
- [ ] `FE-0212` Tambahkan check agar generated client tidak stale terhadap spec.
- [ ] `FE-0213` Buat normalized `ApiError` dan API client tunggal.
- [ ] `FE-0214` Implementasikan abort/cancellation support.
- [ ] `FE-0215` Konfigurasi QueryClient defaults dan retry policy per status.
- [ ] `FE-0216` Buat global application error boundary.
- [ ] `FE-0217` Buat base document `lang=id`, metadata, favicon, CSP compatibility.
- [ ] `FE-0218` Self-host Instrument Sans atau putuskan system font stack dan sesuaikan design token.
- [ ] `FE-0219` Tambahkan CI: typecheck, lint, unit, component, build, bundle budget.
- [ ] `FE-0220` Tambahkan preview/staging deploy otomatis per PR bila infrastructure mendukung.

Quality gate Phase 2:

- [ ] `GATE-02` Fresh clone dapat install, generate API client, typecheck, test, build, dan deploy preview secara deterministik.

## Phase 3 — Design system dan reusable foundation

Tujuan: feature migration memakai vocabulary yang sama sejak awal.

### Tokens dan global styles

- [ ] `DS-0301` Implementasikan color tokens dari `DESIGN.md` sebagai CSS custom properties.
- [ ] `DS-0302` Implementasikan typography, spacing, radius, shadow, motion, dan z-index tokens.
- [ ] `DS-0303` Tambahkan `prefers-reduced-motion` global policy.
- [ ] `DS-0304` Tambahkan focus-visible global yang tidak bentrok dengan PrimeVue.
- [ ] `DS-0305` Pastikan placeholder/body contrast minimum 4.5:1.
- [ ] `DS-0306` Buat story/showcase route internal untuk semua primitives dan states.

### UI primitives

- [ ] `DS-0310` Port dan type `UiButton`; perbaiki primary contrast.
- [ ] `DS-0311` Port dan type `UiIconButton`; pastikan accessible name dan coarse-pointer target.
- [ ] `DS-0312` Port dan type `UiField`; hubungkan help/error dengan `aria-describedby`.
- [ ] `DS-0313` Port `UiSurface`; hilangkan default shadow lebar pada semua surface.
- [ ] `DS-0314` Port `UiSectionHeader` dengan action wrapping.
- [ ] `DS-0315` Port `UiEmptyState` dengan action slot dan filtered-empty variant.
- [ ] `DS-0316` Buat `UiInlineAlert` untuk info/success/warning/error.
- [ ] `DS-0317` Buat `UiSkeleton` primitives.
- [ ] `DS-0318` Buat `UiStatusBadge` dengan icon/text non-color signal.
- [ ] `DS-0319` Buat standard `UiDialog`/confirm wrapper di atas PrimeVue.
- [ ] `DS-0320` Buat `UiPagination` yang sinkron dengan route query.

### Shared patterns

- [ ] `DS-0330` Buat `AsyncPanel` dengan initial/error/retry/success slots.
- [ ] `DS-0331` Buat `DataTableShell` untuk toolbar/loading/empty/error/pagination.
- [ ] `DS-0332` Buat `FilterBar` dan active filter chips.
- [ ] `DS-0333` Buat `FormActions` dengan dirty/loading/submit/cancel behavior.
- [ ] `DS-0334` Buat `PermissionGate`/`Can` presentation helper.
- [ ] `DS-0335` Buat formatter currency, number, percent, date, datetime, phone.
- [ ] `DS-0336` Port `useDebouncedWatch` dengan cancellation-friendly usage.
- [ ] `DS-0337` Port `useVisibleSelection` dan tests.
- [ ] `DS-0338` Port `useClickOutside` hanya jika PrimeVue/platform primitive belum cukup.
- [ ] `DS-0339` Tambahkan axe/accessibility tests untuk primitives utama.
- [ ] `DS-0340` Tambahkan visual regression snapshots untuk design-system route.

Quality gate Phase 3:

- [ ] `GATE-03` Semua primitive/pattern utama mempunyai complete states, typed API, accessibility test, responsive showcase, dan mengikuti `DESIGN.md`.

## Phase 4 — Router, auth, dan application shell

Tujuan: private SPA shell aman dan siap menjadi host seluruh feature.

### Router

- [ ] `FE-0401` Implementasikan canonical route tree dan named route constants.
- [ ] `FE-0402` Type route meta: title, layout, auth, permissions, breadcrumb.
- [ ] `FE-0403` Implementasikan route lazy loading per feature.
- [ ] `FE-0404` Implementasikan global auth/permission guard tanpa infinite redirect.
- [ ] `FE-0405` Implementasikan 403, 404, maintenance, dan unexpected-error routes.
- [ ] `FE-0406` Implementasikan scroll behavior dan route focus/announcement.
- [ ] `FE-0407` Implementasikan safe `redirect` query setelah login.
- [ ] `FE-0408` Tambahkan router tests untuk deep link, redirect, auth, permission, dan back/forward.

### Auth

- [ ] `AUTH-0410` Implementasikan `useAuthStore` tanpa token persistence.
- [ ] `AUTH-0411` Implementasikan CSRF initialization.
- [ ] `AUTH-0412` Implementasikan login session cookie.
- [ ] `AUTH-0413` Implementasikan current-session bootstrap.
- [ ] `AUTH-0414` Implementasikan logout dan query cache clearing.
- [ ] `AUTH-0415` Implementasikan 401 global handling.
- [ ] `AUTH-0416` Implementasikan bounded 419 CSRF recovery.
- [ ] `AUTH-0417` Implementasikan disabled/ineligible app user state.
- [ ] `AUTH-0418` Uji cookie flags/CORS pada staging browser, bukan hanya unit test.
- [ ] `AUTH-0419` E2E login, invalid credential, logout, session expiry, and return-to route.

### Shell

- [ ] `SHELL-0420` Port `AppLayout` tanpa `usePage`/Inertia router.
- [ ] `SHELL-0421` Port sidebar memakai frontend route definitions + permissions.
- [ ] `SHELL-0422` Port mobile drawer focus trap, inert state, Escape, dan return focus.
- [ ] `SHELL-0423` Port topbar, global search entry, profile menu, dan responsive actions.
- [ ] `SHELL-0424` Implementasikan breadcrumbs dari route meta + fetched record.
- [ ] `SHELL-0425` Implementasikan toast host dengan deduplication dan semantic copy.
- [ ] `SHELL-0426` Implementasikan public app settings/logo query.
- [ ] `SHELL-0427` Implementasikan notification summary query dan mark-read mutation setelah API tersedia.
- [ ] `SHELL-0428` Persist sidebar preference dengan versioned local schema.
- [ ] `SHELL-0429` E2E keyboard navigation shell dan mobile drawer.

Quality gate Phase 4:

- [ ] `GATE-04` Pengguna dapat login, refresh deep private route, berpindah route sesuai permission, logout, dan menggunakan shell via keyboard/mobile tanpa token JavaScript.

## Phase 5 — Reference data dan domain utilities

Tujuan: semua feature berikutnya memakai options/formatter/location behavior yang sama.

- [ ] `FE-0501` Generate types dictionary dan location dari OpenAPI.
- [ ] `FE-0502` Buat dictionary query key factory dan composables.
- [ ] `FE-0503` Buat province/regency/district/village query key factory.
- [ ] `FE-0504` Port `useCascadingLocation` ke typed queries.
- [ ] `FE-0505` Tambahkan AbortSignal atau stale-response protection.
- [ ] `FE-0506` Definisikan reset child value saat parent location berubah.
- [ ] `FE-0507` Tambahkan cache policy untuk reference data.
- [ ] `FE-0508` Buat reusable `LocationFields` tanpa endpoint hardcoded.
- [ ] `FE-0509` Port phone normalization/display utilities.
- [ ] `FE-0510` Port date bridge dengan timezone/date-only tests.
- [ ] `FE-0511` Port image preview/cropper dengan object URL cleanup.
- [ ] `FE-0512` Port Leaflet composables dengan lazy import dan typed markers.
- [ ] `FE-0513` Port Chart.js composables dengan lazy import dan accessible summary.
- [ ] `FE-0514` Uji cascading race conditions, failed lookup, dan edit preload.

Quality gate Phase 5:

- [ ] `GATE-05` Reference data, location, date, currency, phone, image, map, dan chart utilities stabil, bertipe, dan diuji independen dari page.

## Phase 6 — Pembanding end-to-end

Tujuan: migrasikan domain utama lebih dahulu untuk membuktikan arsitektur.

### List/filter/map

- [ ] `PEM-0601` Implementasikan list query dari `/api/v1/pembandings`.
- [ ] `PEM-0602` Samakan filter API dengan filter Inertia existing; catat perbedaan.
- [ ] `PEM-0603` Implementasikan URL serializer/parser untuk seluruh filter.
- [ ] `PEM-0604` Implementasikan search debounce dan cancellation.
- [ ] `PEM-0605` Port quick filters, detailed filter drawer, active chips, reset.
- [ ] `PEM-0606` Port result panel dan pagination.
- [ ] `PEM-0607` Implementasikan map/list mode bila parity membutuhkan.
- [ ] `PEM-0608` Port export-by-filter entry point.
- [ ] `PEM-0609` Uji filtered empty, invalid range, large result, and back/forward.

### Detail/history

- [ ] `PEM-0610` Implementasikan detail query dan DTO presentation mapping.
- [ ] `PEM-0611` Port detail header, stats, info sections, media, notes, dan map.
- [ ] `PEM-0612` Implementasikan permission-aware edit/delete-request actions.
- [ ] `PEM-0613` Implementasikan history query dan activity change labels.
- [ ] `PEM-0614` Implementasikan image missing/broken fallback.
- [ ] `PEM-0615` Uji 403, 404, deleted/changed record, and partial nullable data.

### Create/edit

- [ ] `PEM-0620` Definisikan typed `PembandingFormValues` dan payload mapper.
- [ ] `PEM-0621` Buat satu reusable `PembandingForm` untuk create/edit.
- [ ] `PEM-0622` Port general, location, property, dan notes tabs/sections.
- [ ] `PEM-0623` Integrasikan form-options query dan cascading location.
- [ ] `PEM-0624` Implementasikan client schema dan Laravel 422 mapping.
- [ ] `PEM-0625` Implementasikan multipart create/update workaround yang didokumentasikan API.
- [ ] `PEM-0626` Implementasikan upload preview, cropper, replace/remove, MIME/size feedback.
- [ ] `PEM-0627` Implementasikan unsaved-change route guard.
- [ ] `PEM-0628` Implementasikan duplicate 409 response handling.
- [ ] `PEM-0629` Implementasikan duplicate review/use existing/replace flow.
- [ ] `PEM-0630` E2E create, edit, validation, duplicate resolution, image, dan delete request.

Quality gate Phase 6:

- [ ] `GATE-06` Seluruh flow Pembanding mencapai parity dan dapat digunakan sebagai pilot production untuk kelompok user terbatas.

## Phase 7 — Dashboard dan global search

- [ ] `DASH-0701` Definisikan dashboard query per widget atau aggregate yang terukur.
- [ ] `DASH-0702` Port permission-based widget visibility.
- [ ] `DASH-0703` Port stats overview dengan tabular numbers.
- [ ] `DASH-0704` Port map widget dan selected marker behavior.
- [ ] `DASH-0705` Port monthly/listing charts dengan accessible summary.
- [ ] `DASH-0706` Port recent data, contributor, freshness, area, dan object-type tables.
- [ ] `DASH-0707` Tangani dashboard variant data contributor.
- [ ] `DASH-0708` Tangani no-widget-permission sebagai intentional state.
- [ ] `DASH-0709` Ukur query count, payload size, render time, dan chart/map chunk.
- [ ] `SEARCH-0710` Implementasikan global search route + URL state.
- [ ] `SEARCH-0711` Port filters, pagination, result grouping, dan permission handling.
- [ ] `SEARCH-0712` Sinkronkan topbar search dengan search page tanpa duplicate state.
- [ ] `SEARCH-0713` E2E dashboard roles dan global search/back-forward.

Quality gate Phase 7:

- [ ] `GATE-07` Dashboard scoped sesuai role, tidak waterfall berlebihan, dan search dapat dibookmark serta dinavigasi keyboard.

## Phase 8 — Master data dan geo data

### Master data

- [ ] `MASTER-0801` Implementasikan master-data overview query.
- [ ] `MASTER-0802` Implementasikan dynamic dictionary route validation.
- [ ] `MASTER-0803` Port Dictionary CRUD list/form.
- [ ] `MASTER-0804` Implementasikan create/update/status/delete mutations.
- [ ] `MASTER-0805` Implementasikan reorder dengan rollback pada failure.
- [ ] `MASTER-0806` Tangani active/inactive, protected record, duplicate slug/name, dan in-use conflict.
- [ ] `MASTER-0807` E2E permission matrix master data.

### Geo data

- [ ] `GEO-0810` Implementasikan resource-aware geo list/filter query.
- [ ] `GEO-0811` Pecah page GeoData lama menjadi toolbar, table, form panel, dan location selector.
- [ ] `GEO-0812` Implementasikan create/update/delete mutations.
- [ ] `GEO-0813` Tangani hierarchical dependency dan record in-use conflict.
- [ ] `GEO-0814` Uji ID generation/validation yang terlihat di frontend.
- [ ] `GEO-0815` E2E province sampai village CRUD sesuai permission.

Quality gate Phase 8:

- [ ] `GATE-08` Semua reference admin flow mencapai parity tanpa page monolitik dan cache reference terinvalidasi tepat setelah mutation.

## Phase 9 — Users, access control, dan contributor invitations

### Users

- [ ] `USER-0901` Implementasikan user list/filter/pagination query.
- [ ] `USER-0902` Port selection dan bulk delete dengan hasil parsial yang jelas.
- [ ] `USER-0903` Implementasikan create/edit form serta role assignment.
- [ ] `USER-0904` Implementasikan active status mutation.
- [ ] `USER-0905` Tangani self-deactivation/delete dan protected super-admin rules.
- [ ] `USER-0906` E2E permission matrix users.

### Access control

- [ ] `ACL-0910` Implementasikan roles/permissions queries.
- [ ] `ACL-0911` Pecah AccessControl page lama menjadi role list/editor dan permission list/editor.
- [ ] `ACL-0912` Implementasikan role create/update/delete.
- [ ] `ACL-0913` Implementasikan permission create/delete sesuai backend capability.
- [ ] `ACL-0914` Invalidasi auth/session permission bila current user terdampak.
- [ ] `ACL-0915` Tangani protected/in-use role-permission conflict.
- [ ] `ACL-0916` E2E access-control critical operations.

### Contributor invitations

- [ ] `INV-0920` Implementasikan invitation/request list queries dan shareable active tab.
- [ ] `INV-0921` Implementasikan invitation generation/revoke.
- [ ] `INV-0922` Implementasikan safe copy-to-clipboard fallback dan feedback.
- [ ] `INV-0923` Implementasikan accept/reject request dengan reason validation.
- [ ] `INV-0924` Migrasikan public registration token states: valid, invalid, expired, used, submitted.
- [ ] `INV-0925` E2E invitation-to-registration-to-approval flow.

Quality gate Phase 9:

- [ ] `GATE-09` Identity/access administrative flows mencapai parity dan protected-account edge cases telah diuji.

## Phase 10 — Moderation

- [ ] `MOD-1001` Implementasikan pending request dan deleted data queries.
- [ ] `MOD-1002` Simpan tab/search/page moderation di URL.
- [ ] `MOD-1003` Port approve mutation dengan target summary.
- [ ] `MOD-1004` Port reject mutation dengan mandatory review note.
- [ ] `MOD-1005` Port restore mutation.
- [ ] `MOD-1006` Port force-delete dengan high-friction confirmation dan exact target.
- [ ] `MOD-1007` Tangani record changed/already reviewed conflict.
- [ ] `MOD-1008` Invalidasi dashboard, pembanding, moderation, dan relevant audit queries.
- [ ] `MOD-1009` E2E concurrent moderation conflict dan permission matrix.

Quality gate Phase 10:

- [ ] `GATE-10` Moderation state transitions akurat, auditable, tahan double-submit, dan conflict-safe.

## Phase 11 — Bulk import dan export

### Bulk import

- [ ] `IMPORT-1101` Implementasikan batch list/pagination/status.
- [ ] `IMPORT-1102` Implementasikan file upload dengan progress/cancel bila transport mendukung.
- [ ] `IMPORT-1103` Validasi extension/size client tanpa menggantikan server validation.
- [ ] `IMPORT-1104` Implementasikan batch detail dan bounded polling saat processing.
- [ ] `IMPORT-1105` Port row table, visible selection, select-all semantics, dan result summary.
- [ ] `IMPORT-1106` Implementasikan row edit form/image preview.
- [ ] `IMPORT-1107` Implementasikan selection patch dan bulk apply.
- [ ] `IMPORT-1108` Implementasikan row retry dan retry status.
- [ ] `IMPORT-1109` Implementasikan finalize confirmation/idempotency.
- [ ] `IMPORT-1110` Tangani partial failure, stale batch, invalid row, missing staged image, dan expired artifact.
- [ ] `IMPORT-1111` E2E upload-to-finalize dengan success dan partial failure fixture.

### Export

- [ ] `EXPORT-1120` Implementasikan export dataset query/filter/pagination.
- [ ] `EXPORT-1121` Port export configuration profiles/columns.
- [ ] `EXPORT-1122` Implementasikan selection semantics yang tidak ambigu lintas page/filter.
- [ ] `EXPORT-1123` Implementasikan preview endpoint dan limit warning.
- [ ] `EXPORT-1124` Implementasikan create export run dengan idempotency/double-click protection.
- [ ] `EXPORT-1125` Implementasikan running jobs list dan bounded polling.
- [ ] `EXPORT-1126` Implementasikan completed/failed/retry states.
- [ ] `EXPORT-1127` Implementasikan authorized download/expired file behavior.
- [ ] `EXPORT-1128` Sinkronkan completion dengan notification query.
- [ ] `EXPORT-1129` E2E synchronous dan queued export path.

Quality gate Phase 11:

- [ ] `GATE-11` Import/export flow berhasil untuk file realistis, job panjang, retry, partial failure, refresh page, dan expired artifacts.

## Phase 12 — Profile, settings, activity log, notifications, dan backup

### Profile dan settings

- [ ] `SYS-1201` Implementasikan current profile update.
- [ ] `SYS-1202` Implementasikan password update dengan current-password error.
- [ ] `SYS-1203` Implementasikan settings query/update/logo upload.
- [ ] `SYS-1204` Putuskan behavior `primary_color`: logo accent saja atau runtime palette tervalidasi.
- [ ] `SYS-1205` Implementasikan system-mode transitions dan maintenance response.
- [ ] `SYS-1206` Implementasikan clear-cache confirmation dan result feedback.

### Activity logs dan notifications

- [ ] `SYS-1210` Implementasikan activity log list/filter/pagination.
- [ ] `SYS-1211` Implementasikan activity detail dan safe before/after rendering.
- [ ] `SYS-1212` Redact field sensitif pada API dan UI.
- [ ] `SYS-1213` Implementasikan notification list/unread count.
- [ ] `SYS-1214` Implementasikan mark-one/mark-all-read.
- [ ] `SYS-1215` Evaluasi polling vs real-time berdasarkan volume dan latency requirement.

### Backup high-risk flow

- [ ] `BACKUP-1220` Definisikan threat model dan authorization matrix backup.
- [ ] `BACKUP-1221` Implementasikan artifact catalog/status query.
- [ ] `BACKUP-1222` Implementasikan database/uploads backup creation.
- [ ] `BACKUP-1223` Implementasikan import dengan file validation/progress.
- [ ] `BACKUP-1224` Implementasikan verification dan tampilkan checksum/size/status.
- [ ] `BACKUP-1225` Implementasikan authorized download.
- [ ] `BACKUP-1226` Implementasikan delete dengan exact artifact confirmation.
- [ ] `BACKUP-1227` Implementasikan restore uploads dengan step-up/high-friction confirmation.
- [ ] `BACKUP-1228` Jangan expose database restore UI sebelum backend flag dan runbook resmi tersedia.
- [ ] `BACKUP-1229` Uji expired, corrupt, signature mismatch, disabled restore, insufficient permission, dan double submit.
- [ ] `BACKUP-1230` E2E backup flow pada environment disposable, bukan production data.

Quality gate Phase 12:

- [ ] `GATE-12` Semua system flow mencapai parity; backup/restore memiliki audit, permission, safety confirmation, dan runbook operasional.

## Phase 13 — Hardening lintas aplikasi

### Security

- [ ] `HARD-1301` Review CSP dan pastikan Leaflet/PrimeVue/font/assets compatible tanpa unsafe wildcard yang tidak perlu.
- [ ] `HARD-1302` Verifikasi tidak ada auth token pada browser storage.
- [ ] `HARD-1303` Verifikasi cookie/CORS/CSRF pada production-like domains.
- [ ] `HARD-1304` Audit open redirect, unsafe HTML, URL injection, file name, dan spreadsheet formula risk di UI.
- [ ] `HARD-1305` Audit permission pada seluruh route dan action terhadap backend matrix.
- [ ] `HARD-1306` Bersihkan console logging dan redact telemetry.
- [ ] `HARD-1307` Dependency/license/security audit hijau atau mempunyai accepted risk tercatat.

### Accessibility dan responsive

- [ ] `HARD-1310` Automated axe scan seluruh route utama.
- [ ] `HARD-1311` Manual keyboard-only test seluruh critical path.
- [ ] `HARD-1312` Screen-reader smoke test auth, navigation, form errors, dialogs, table, dan status jobs.
- [ ] `HARD-1313` Contrast audit tokens dan runtime states.
- [ ] `HARD-1314` Zoom 200% dan text spacing test.
- [ ] `HARD-1315` Reduced-motion test.
- [ ] `HARD-1316` Mobile device test untuk map, cropper, tables, drawers, dan sticky actions.

### Performance dan reliability

- [ ] `HARD-1320` Analyze production bundle dan enforce entry/feature budgets.
- [ ] `HARD-1321` Verifikasi lazy chunks untuk Leaflet, Chart.js, Cropper, dan admin-only features.
- [ ] `HARD-1322` Measure route load, interaction latency, memory leak, dan long-session behavior.
- [ ] `HARD-1323` Test slow 3G/high latency, offline transition, timeout, abort, and retry.
- [ ] `HARD-1324` Test cache invalidation setelah setiap mutation class.
- [ ] `HARD-1325` Test rapid navigation dan stale-response race conditions.
- [ ] `HARD-1326` Test session expiry saat form dirty/upload/job polling.
- [ ] `HARD-1327` Test deployment dengan old frontend/new API dan new frontend/old API sesuai compatibility window.

### Observability dan operations

- [ ] `HARD-1330` Integrasikan frontend error tracking dengan release/source maps.
- [ ] `HARD-1331` Sertakan route name/request ID pada error event tanpa PII.
- [ ] `HARD-1332` Buat dashboard error rate, API failure rate, auth failure, dan web vitals.
- [ ] `HARD-1333` Buat alert threshold dan owner/on-call route.
- [ ] `HARD-1334` Dokumentasikan rollback frontend independen.
- [ ] `HARD-1335` Dokumentasikan cache invalidation/CDN behavior.
- [ ] `HARD-1336` Lakukan disaster/rollback rehearsal pada staging.

Quality gate Phase 13:

- [ ] `GATE-13` Security, accessibility, reliability, performance, observability, dan rollback review disetujui; tidak ada P0/P1 defect terbuka.

## Phase 14 — Pilot, cutover, dan decommission Inertia

### Pilot

- [ ] `CUT-1401` Pilih cohort pilot berdasarkan role dan risiko.
- [ ] `CUT-1402` Aktifkan feature flag/alternate entry untuk cohort pilot.
- [ ] `CUT-1403` Jalankan parity script/manual scenario pada Inertia dan SPA.
- [ ] `CUT-1404` Bandingkan auth failure, API errors, latency, dan task completion.
- [ ] `CUT-1405` Kumpulkan feedback terstruktur untuk flow, bukan preferensi visual umum.
- [ ] `CUT-1406` Selesaikan seluruh P0/P1 dan klasifikasikan P2/P3.

### Cutover

- [ ] `CUT-1410` Freeze perubahan route/contract selama cutover window.
- [ ] `CUT-1411` Verifikasi backup dan rollback plan.
- [ ] `CUT-1412` Deploy compatible API terlebih dahulu.
- [ ] `CUT-1413` Deploy SPA production.
- [ ] `CUT-1414` Aktifkan redirect `/app/*` lama ke canonical SPA routes dengan parameter aman.
- [ ] `CUT-1415` Verifikasi login, deep link, static assets, downloads, upload, queue status, dan error tracking production.
- [ ] `CUT-1416` Monitor metrics/logs secara intensif pada cutover window.
- [ ] `CUT-1417` Komunikasikan perubahan URL/session behavior kepada pengguna/support.

### Decommission

- [ ] `CUT-1420` Tunggu stability window yang disepakati tanpa critical regression.
- [ ] `CUT-1421` Pastikan tidak ada client aktif yang masih meminta Inertia routes.
- [ ] `CUT-1422` Hapus Inertia page rendering dari controller setelah API adapter parity terbukti.
- [ ] `CUT-1423` Hapus `@inertiajs/vue3` dari frontend lama.
- [ ] `CUT-1424` Hapus `inertiajs/inertia-laravel` setelah tidak ada consumer.
- [ ] `CUT-1425` Hapus Vite/Node build dari Laravel bila Laravel tidak lagi menyajikan asset frontend.
- [ ] `CUT-1426` Pertahankan Blade hanya untuk error/public surface yang memang diputuskan tetap di API host.
- [ ] `CUT-1427` Hapus web routes lama setelah redirect/deprecation window selesai.
- [ ] `CUT-1428` Perbarui README, deployment, backup, incident, dan onboarding docs.
- [ ] `CUT-1429` Arsipkan screenshot/behavior baseline dan migration decisions.
- [ ] `CUT-1430` Lakukan post-migration review dan catat follow-up non-parity improvements.

Final gate:

- [ ] `GATE-14` SPA melayani seluruh user production, API melayani web/mobile/client lain secara stabil, rollback tervalidasi, dan Inertia telah dihapus tanpa endpoint/domain logic yang hilang.

## Recommended execution order dan dependency

```text
Phase 0
  -> Phase 1 API/session P0
  -> Phase 2 scaffold
  -> Phase 3 design foundation
  -> Phase 4 auth + shell
  -> Phase 5 reference utilities
  -> Phase 6 pembanding pilot
  -> Phase 7/8 dapat paralel setelah Phase 6 stabil
  -> Phase 9/10
  -> Phase 11 async file workflows
  -> Phase 12 high-risk system workflows
  -> Phase 13 hardening
  -> Phase 14 cutover/decommission
```

Phase 2 dan API work Phase 1 dapat berjalan paralel setelah auth/contract decision dikunci. Feature tidak boleh dinyatakan selesai bila endpoint masih sementara atau response fixture tidak cocok OpenAPI.

## Progress summary

Isi tabel ini pada setiap planning/release review.

| Phase | Status | Owner | Target | Gate | Notes/blocker |
|---|---|---|---|---|---|
| 0 Baseline | Not started |  |  | `GATE-00` |  |
| 1 API prerequisites | Not started |  |  | `GATE-01` |  |
| 2 Scaffold | Not started |  |  | `GATE-02` |  |
| 3 Design foundation | Not started |  |  | `GATE-03` |  |
| 4 Auth/shell | Not started |  |  | `GATE-04` |  |
| 5 Domain utilities | Not started |  |  | `GATE-05` |  |
| 6 Pembanding | Not started |  |  | `GATE-06` |  |
| 7 Dashboard/search | Not started |  |  | `GATE-07` |  |
| 8 Master/geo | Not started |  |  | `GATE-08` |  |
| 9 Identity/access | Not started |  |  | `GATE-09` |  |
| 10 Moderation | Not started |  |  | `GATE-10` |  |
| 11 Import/export | Not started |  |  | `GATE-11` |  |
| 12 System modules | Not started |  |  | `GATE-12` |  |
| 13 Hardening | Not started |  |  | `GATE-13` |  |
| 14 Cutover | Not started |  |  | `GATE-14` |  |
