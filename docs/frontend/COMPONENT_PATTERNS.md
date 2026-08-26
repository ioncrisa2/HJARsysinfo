# Pola Komponen Frontend HJAR Sysinfo

Status: baseline arsitektur untuk proyek Vue terpisah  
Target: Vue 3 Composition API + TypeScript  
Referensi visual normatif: [`DESIGN.md`](../../DESIGN.md)

## Tujuan

Dokumen ini menetapkan batas tanggung jawab komponen agar migrasi dari Inertia tidak menghasilkan page component besar, pemanggilan API yang tersebar, atau komponen reusable yang diam-diam bergantung pada router dan autentikasi.

Aturan utamanya:

1. Page mengorkestrasi route dan use case.
2. Query composable memiliki server state.
3. Feature component memahami satu domain.
4. UI component tidak memahami domain, router, endpoint, atau permission backend.
5. Store tidak menjadi cache kedua untuk response API.

## Lapisan komponen

| Lapisan | Lokasi | Tanggung jawab | Boleh mengakses API/router? |
|---|---|---|---|
| Application shell | `src/app/layouts`, `src/app/components` | Sidebar, topbar, breadcrumbs, toast host, error boundary | Router dan auth store; API hanya melalui bootstrap/notification query |
| Route page | `src/features/*/pages` | Membaca params/query, menyusun query/mutation, memilih layout dan permission state | Ya |
| Feature component | `src/features/*/components` | UI dan interaksi satu domain seperti filter pembanding atau import rows | Tidak langsung; menerima data/callback atau memakai composable feature yang eksplisit |
| Shared pattern | `src/shared/components/patterns` | Data table shell, filter bar, form actions, async panel | Tidak |
| UI primitive | `src/shared/components/ui` | Button, field, surface, icon button, status, skeleton | Tidak |
| Headless composable | `src/shared/composables` | Perilaku generik seperti debounce, selection, focus trap | Tidak, kecuali nama composable secara eksplisit menunjukkan transport |

Dependency hanya mengarah ke bawah. `shared` tidak boleh mengimpor dari `features`, dan satu feature tidak boleh mengimpor internal feature lain. Jika dua feature memerlukan kontrak yang sama, ekstrak kontrak minimal ke `shared` atau sediakan public entry point feature.

## Struktur folder yang disarankan

```text
src/
├── app/
│   ├── App.vue
│   ├── layouts/
│   │   ├── AppLayout.vue
│   │   ├── AuthLayout.vue
│   │   └── PublicLayout.vue
│   ├── components/
│   │   ├── AppSidebar.vue
│   │   ├── AppTopbar.vue
│   │   ├── AppBreadcrumbs.vue
│   │   └── AppRouteAnnouncer.vue
│   └── providers/
│       ├── installPinia.ts
│       ├── installPrimeVue.ts
│       └── installQueryClient.ts
├── features/
│   ├── auth/
│   ├── dashboard/
│   ├── pembanding/
│   ├── bulk-import/
│   ├── moderation/
│   ├── master-data/
│   ├── geo-data/
│   ├── exports/
│   ├── users/
│   ├── access-control/
│   ├── contributor-invitations/
│   ├── backup/
│   ├── settings/
│   ├── activity-logs/
│   ├── search/
│   └── profile/
├── shared/
│   ├── api/
│   ├── components/
│   │   ├── ui/
│   │   └── patterns/
│   ├── composables/
│   ├── formatters/
│   ├── schemas/
│   ├── types/
│   └── utils/
├── router/
├── stores/
├── styles/
└── main.ts
```

Struktur internal satu feature:

```text
features/pembanding/
├── api/
│   ├── pembanding.api.ts
│   └── pembanding.keys.ts
├── components/
├── composables/
│   ├── usePembandingListQuery.ts
│   └── useSavePembandingMutation.ts
├── pages/
├── schemas/
├── types/
└── index.ts
```

`index.ts` adalah public API feature. File dari luar feature dilarang mengimpor path internal seperti `features/pembanding/components/internal/...`.

## Konvensi nama

| Jenis | Pola | Contoh |
|---|---|---|
| UI primitive | `Ui{Name}` | `UiButton`, `UiField`, `UiDialog` |
| Application shell | `App{Name}` | `AppSidebar`, `AppTopbar` |
| Shared pattern | `Data{Name}` atau `{Purpose}` | `DataTableShell`, `FilterBar`, `AsyncPanel` |
| Feature component | `{Feature}{Purpose}` | `PembandingFilterPanel`, `ExportRunList` |
| Route page | `{Feature}{Action}Page` | `PembandingListPage`, `UserEditPage` |
| Query composable | `use{Resource}{Scope}Query` | `usePembandingDetailQuery` |
| Mutation composable | `use{Action}{Resource}Mutation` | `useApproveDeleteRequestMutation` |
| UI composable | `use{Behavior}` | `useVisibleSelection` |
| Pinia store | `use{Concern}Store` | `useAuthStore`, `useUiPreferencesStore` |

Nama `Base*`, `Common*`, `Helper*`, dan `Manager*` dilarang karena tidak menjelaskan ownership atau tujuan.

## Kontrak UI primitive

Primitive harus:

- Menggunakan typed props dan typed emits.
- Mendukung `class` dan attributes fallthrough yang wajar.
- Memiliki default, hover, focus-visible, active, disabled, dan loading state bila interaktif.
- Tidak mengimpor Vue Router, Pinia, query client, atau API client.
- Tidak menerima string permission lalu memeriksa auth store sendiri.
- Tidak memformat data domain seperti harga properti.
- Mengekspos slot hanya ketika slot lebih stabil daripada banyak boolean props.

Contoh kontrak tombol:

```vue
<script setup lang="ts">
type ButtonVariant = 'primary' | 'secondary' | 'ghost' | 'danger'
type ButtonSize = 'sm' | 'md'

withDefaults(defineProps<{
  type?: 'button' | 'submit' | 'reset'
  variant?: ButtonVariant
  size?: ButtonSize
  loading?: boolean
  disabled?: boolean
}>(), {
  type: 'button',
  variant: 'secondary',
  size: 'md',
  loading: false,
  disabled: false,
})

defineEmits<{ click: [event: MouseEvent] }>()
</script>
```

Jangan menambah prop seperti `deletePembanding`, `canApprove`, atau `redirectAfterSave` pada primitive. Itu tanggung jawab feature/page.

## Page sebagai composition root

Page bertugas untuk:

- Membaca route params dan query.
- Menjalankan query dan mutation composable.
- Menentukan 401/403/404 dan async state tingkat halaman.
- Menyusun breadcrumbs serta document title dari route meta/data.
- Menghubungkan event komponen dengan use case.
- Menjaga filter/sort/pagination tetap sinkron dengan URL.

Page tidak boleh:

- Mengandung implementasi tabel atau form ratusan baris.
- Menulis `fetch()`/Axios secara langsung.
- Menyusun ulang response mentah API di template.
- Menyalin formatter tanggal, currency, atau telepon.
- Memiliki hardcoded URL internal.

Target ukuran bukan aturan absolut, tetapi page di atas 250 baris harus ditinjau. Beberapa page sekarang—Geo Data, Access Control, Backup, Moderation, dan Bulk Import Show—harus dipecah saat dimigrasikan, bukan dipindahkan utuh.

## Props, events, dan `v-model`

- Props adalah readonly dan memakai tipe domain/presentation yang eksplisit.
- Event diberi nama berdasarkan kejadian pengguna: `submit`, `retry`, `remove`, bukan `setData`.
- Gunakan `v-model` hanya untuk nilai yang benar-benar two-way seperti dialog visibility atau satu input composite.
- Hindari lebih dari dua `v-model` pada satu komponen. Gunakan form object atau event yang lebih bermakna.
- Boolean prop harus dapat dibaca sebagai kalimat: `loading`, `disabled`, `readonly`, `destructive`.
- Komponen presentasional tidak menerima seluruh auth store atau route object.

## Pola server state

Setiap resource mempunyai query key factory:

```ts
export const pembandingKeys = {
  all: ['pembandings'] as const,
  lists: () => [...pembandingKeys.all, 'list'] as const,
  list: (filters: PembandingFilters) => [...pembandingKeys.lists(), filters] as const,
  details: () => [...pembandingKeys.all, 'detail'] as const,
  detail: (id: string) => [...pembandingKeys.details(), id] as const,
}
```

Aturan:

- Semua variable yang memengaruhi request masuk query key.
- Mutation meng-invalidasi key paling sempit yang benar.
- Optimistic update hanya untuk aksi mudah dipulihkan seperti toggle ringan; destructive, approval, restore, export, dan backup menunggu response server.
- Jangan menyalin `query.data` ke Pinia atau `ref` kecuali sedang membuat editable draft.
- Pilih `staleTime` per domain. Dictionary dan provinces dapat lebih lama; notifications, export runs, dan moderation jauh lebih singkat.
- Polling hanya aktif saat ada job pending dan berhenti ketika tab tidak relevan atau semua job terminal.

## Pola form

Form dibagi menjadi empat lapisan:

1. Schema input client untuk feedback cepat.
2. Draft state lokal pada page/form composable.
3. Mapping draft ke payload API pada feature API layer.
4. Error 422 dari Laravel sebagai sumber validasi final.

Kontrak standar `UiField`:

- `label`, `for`, `required`, `help`, dan `error`.
- `aria-invalid="true"` ketika error.
- `aria-describedby` menunjuk help/error yang aktif.
- Error server dipetakan dengan key field yang sama; error global tampil di form summary.
- Tombol submit mencegah double submission.
- `beforeRouteLeave` aktif ketika form dirty dan belum tersimpan.

Form create/edit Pembanding harus memakai satu `PembandingForm` dengan initial value dan mode berbeda, bukan dua salinan struktur. Tab hanya mengelompokkan bidang; submit dan validation summary tetap satu konteks form.

## Pola tabel dan koleksi

Gunakan satu shared pattern `DataTableShell` untuk:

- Toolbar dan bulk selection.
- Loading skeleton.
- Empty state tanpa filter.
- Empty state karena filter.
- Error + retry.
- Pagination dan total.
- Horizontal overflow serta announcement jumlah hasil.

Column definition adalah milik feature karena label, formatter, dan action permission bersifat domain-specific.

State yang wajib berada di URL:

- Search query.
- Filter terapan.
- Sort field/direction.
- Page dan per-page.
- Tab yang harus dapat di-bookmark.

Draft filter pada drawer boleh lokal sampai pengguna memilih Terapkan. Setelah diterapkan, URL adalah sumber kebenaran.

## Pola feedback dan async state

Gunakan komponen/pattern berikut:

- `AsyncPanel`: initial loading, error, retry, success content.
- `UiSkeleton`: mempertahankan layout agar tidak terjadi content jump.
- `UiEmptyState`: menjelaskan apa yang kosong dan memberi next action yang valid.
- `UiInlineAlert`: pesan yang harus tetap terlihat dalam konteks.
- Toast: acknowledgement singkat setelah mutation; bukan tempat validation error panjang.
- `UiConfirmDialog`: hanya untuk aksi irreversible atau berdampak luas.

Bedakan dengan jelas:

| State | Tampilan |
|---|---|
| Initial loading | Skeleton sesuai bentuk konten |
| Background refetch | Konten lama tetap terlihat + indikator halus |
| Empty | Penjelasan dan CTA yang relevan |
| Filtered empty | Ringkasan filter + reset filter |
| Validation error | Pesan per field + summary bila perlu |
| Permission denied | Halaman/panel 403, bukan empty state |
| Network/offline | Data cache bila ada + retry |
| Job pending | Progress/status dan polling terbatas |

## Permission-aware UI

Gunakan directive atau component kecil seperti `Can` hanya untuk presentation:

```vue
<Can permission="create_data::pembanding">
  <UiButton variant="primary">Tambah data</UiButton>
</Can>
```

Aturannya:

- Client membaca permission dari auth/bootstrap response.
- Route meta melakukan guard UX sebelum page dimuat.
- Komponen action tetap memeriksa permission untuk menentukan visibility/disabled state.
- API Laravel selalu mengotorisasi ulang melalui policy/middleware.
- `super_admin` tidak di-hardcode pada frontend; kemampuan berasal dari permission efektif.

## Dialog, dropdown, dan overlay

- Gunakan PrimeVue overlay atau portal ke `body`; jangan menaruh dropdown absolute di container `overflow-hidden`.
- Setiap dialog mempunyai title, initial focus, Escape-to-close bila aman, focus trap, dan return focus.
- Dialog destructive menyebut objek dan dampaknya secara spesifik.
- Drawer mobile untuk filter boleh dipakai karena filter adalah area kerja luas; edit satu field ringan sebaiknya inline.
- Gunakan skala z-index: map (400), sticky (500), dropdown (600), backdrop (700), modal (800), toast (900), tooltip (1000). Jangan memakai `9999` atau `2147483647`.

## Responsive behavior

Breakpoint mengikuti `DESIGN.md`: 640, 768, 1024, dan 1280px.

- `<768px`: sidebar menjadi drawer, form satu kolom, action bar boleh sticky di bawah.
- `768–1023px`: sidebar collapsed, content dua kolom hanya jika tiap kolom tetap minimal 280px.
- `>=1024px`: desktop navigation penuh dan filter dapat menjadi panel tetap bila ruang cukup.
- Peta tidak boleh lebih tinggi dari viewport efektif tanpa kontrol keluar yang terlihat.
- Toolbar menggunakan `flex-wrap`; jangan mengecilkan touch target demi mempertahankan satu baris.
- Tabel kompleks memilih strategi per use case, tidak otomatis diubah menjadi card list.

## Accessibility contract

- Root document memakai `lang="id"`.
- Skip link menuju `#main-content` tetap dipertahankan.
- Setelah navigasi, route announcer menyebut judul halaman dan fokus dipindah secara terkontrol hanya bila dibutuhkan.
- Semua icon-only button mempunyai accessible name.
- Focus order mengikuti urutan visual.
- Error tidak disampaikan melalui warna saja.
- Chart mempunyai summary tekstual atau table equivalent.
- Peta bukan satu-satunya cara memilih atau membaca lokasi.
- Motion menyediakan fallback `prefers-reduced-motion`.
- Target WCAG 2.2 AA untuk flow utama.

## Performance contract

- Semua route feature di-lazy-load.
- Leaflet, Chart.js, Cropper, dan export-related code hanya dimuat pada route yang memerlukannya.
- Jangan mengimpor seluruh PrimeVue secara global jika component-level import/tree-shaking dapat dipakai.
- Image preview memakai object URL yang selalu di-revoke.
- Search dan lookup memakai debounce serta request cancellation.
- Long list/tabel mengevaluasi virtual scrolling hanya setelah pengukuran membuktikan kebutuhan.
- Bundle budget awal: entry gzip <= 180 KB di luar font; setiap lazy feature chunk idealnya <= 250 KB gzip. Peta/cropper boleh menjadi chunk terpisah.

## Strategi reuse dari proyek sekarang

| Implementasi sekarang | Keputusan migrasi |
|---|---|
| `UiButton`, `UiField`, `UiSurface`, `UiSectionHeader`, `UiEmptyState`, `UiIconButton` | Port ke TypeScript, selaraskan dengan token baru, lengkapi seluruh states dan tests |
| `AppLayout`, `AppSidebar`, `AppTopbar` | Pertahankan struktur UX; ganti `usePage`, `Link`, dan Inertia router dengan auth store, Vue Router, dan query composables |
| `useVisibleSelection` | Port sebagai shared headless composable |
| `useCascadingLocation` | Port ke feature/shared query composables; hapus endpoint hardcoded dan tambahkan cancellation |
| `useChartJs`, `useResponsiveCanvasChart` | Port; tambah accessible summary dan lazy import |
| Leaflet composables | Port; isolasi popup renderer, typed marker DTO, cleanup, dan lazy import |
| Image upload/cropper composables | Port; audit memory cleanup, MIME/size error, keyboard flow, dan mobile behavior |
| `apiRequest.js` | Ganti dengan satu typed API client dan normalized `ApiError` |
| Page yang mengimpor Inertia | Jangan port langsung; pindahkan data access ke query/mutation layer dan route state ke Vue Router |

## Review checklist per komponen

- [ ] Ownership/layer komponen jelas.
- [ ] Nama menjelaskan domain atau fungsi.
- [ ] Props dan emits bertipe.
- [ ] Tidak ada hardcoded endpoint atau URL internal.
- [ ] Tidak ada akses store/query yang tersembunyi pada UI primitive.
- [ ] Default, hover, focus, active, disabled, loading, error tersedia sesuai kebutuhan.
- [ ] Keyboard dan screen reader flow diuji.
- [ ] Mobile dan long-content behavior diuji.
- [ ] Tidak membuat nested card atau modal yang tidak perlu.
- [ ] Test sesuai level komponen tersedia.
