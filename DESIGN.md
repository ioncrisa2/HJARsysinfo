---
name: HJAR Sysinfo Web
description: Antarmuka operasional yang tepercaya untuk pengelolaan dan analisis data pembanding properti.
colors:
  brand-amber: "#F59E0B"
  brand-amber-strong: "#D97706"
  brand-amber-soft: "#FFFBEB"
  action-primary: "#B45309"
  ink-strong: "#0F172A"
  ink-body: "#334155"
  ink-muted: "#64748B"
  canvas: "#F8FAFC"
  surface: "#FFFFFF"
  surface-inset: "#F1F5F9"
  border: "#CBD5E1"
  border-soft: "#E2E8F0"
  info: "#1D4ED8"
  success: "#15803D"
  warning-text: "#92400E"
  danger: "#DC2626"
typography:
  display:
    fontFamily: "Instrument Sans, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1.75rem"
    fontWeight: 700
    lineHeight: 1.2
    letterSpacing: "-0.02em"
  headline:
    fontFamily: "Instrument Sans, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1.25rem"
    fontWeight: 650
    lineHeight: 1.3
    letterSpacing: "-0.01em"
  title:
    fontFamily: "Instrument Sans, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1rem"
    fontWeight: 650
    lineHeight: 1.4
  body:
    fontFamily: "Instrument Sans, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 400
    lineHeight: 1.5
  label:
    fontFamily: "Instrument Sans, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.75rem"
    fontWeight: 600
    lineHeight: 1.4
    letterSpacing: "0.01em"
rounded:
  control: "10px"
  surface: "14px"
  overlay: "16px"
  pill: "999px"
spacing:
  xs: "4px"
  sm: "8px"
  md: "12px"
  lg: "16px"
  xl: "24px"
  2xl: "32px"
  3xl: "48px"
components:
  button-primary:
    backgroundColor: "{colors.action-primary}"
    textColor: "{colors.surface}"
    typography: "{typography.body}"
    rounded: "{rounded.control}"
    padding: "10px 16px"
    height: "40px"
  button-secondary:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink-body}"
    typography: "{typography.body}"
    rounded: "{rounded.control}"
    padding: "10px 16px"
    height: "40px"
  input-default:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink-strong}"
    typography: "{typography.body}"
    rounded: "{rounded.control}"
    padding: "10px 12px"
    height: "40px"
  surface-default:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink-body}"
    rounded: "{rounded.surface}"
    padding: "16px"
---

# Design System: HJAR Sysinfo Web

## Overview

**Creative North Star: "Meja Operasional Properti"**

HJAR Sysinfo adalah meja kerja digital untuk pengguna yang memeriksa, membandingkan, dan menjaga kualitas data properti sepanjang hari. Antarmuka harus terasa presisi, terang, tenang, dan dapat dipercaya seperti perangkat kerja profesional: informasi padat tetap mudah dipindai, tindakan utama terlihat jelas, dan status sistem tidak pernah ambigu.

Sistem mempertahankan karakter visual aplikasi saat ini: shell slate gelap, kanvas terang, serta amber sebagai penanda tindakan dan perhatian. Amber bukan dekorasi. Warna ini digunakan secara hemat untuk aksi utama, fokus, pilihan aktif, dan status yang memang memerlukan perhatian. Dark mode tidak menjadi target fase pertama karena pengguna utama bekerja dengan tabel, formulir, peta, dan dokumen pada lingkungan kantor bercahaya.

Antarmuka menolak glassmorphism, gradient dekoratif, bayangan berat, card bersarang, animasi teatrikal, dan kontrol yang mengorbankan familiaritas demi gaya. Konsistensi, aksesibilitas, serta kecepatan pemindaian lebih penting daripada kejutan visual.

**Key Characteristics:**

- Restrained, light-first, dan berorientasi tugas.
- Slate membentuk struktur; amber menandai aksi dan perhatian.
- Tipografi tunggal, rapat, tetapi tetap terbaca.
- Permukaan datar dengan elevasi hanya pada batas konteks yang nyata.
- Desktop efisien, mobile tetap lengkap tanpa menyembunyikan aksi kritis.
- Semua status loading, empty, error, success, disabled, dan forbidden dirancang eksplisit.

## Colors

Palet menggabungkan slate netral untuk kepercayaan dan keterbacaan dengan amber sebagai aksen operasional yang terkontrol.

### Primary

- **Amber Operasional** (`brand-amber`): indikator fokus, pilihan aktif pada shell gelap, titik data chart, dan penekanan kecil.
- **Amber Tindakan** (`action-primary`): latar tombol utama dengan teks putih; shade ini lebih gelap agar kontras teks memadai.
- **Amber Dalam** (`brand-amber-strong`): hover atau pressed state pada aksen, bukan warna teks kecil di atas putih.
- **Amber Kabut** (`brand-amber-soft`): latar selection, warning ringan, dan filter aktif.

### Secondary

- **Biru Informasi** (`info`): tautan tekstual, status informasi, dan affordance yang tidak bersaing dengan primary action.
- **Hijau Valid** (`success`): status selesai atau sukses yang sudah terkonfirmasi.
- **Merah Bahaya** (`danger`): destructive action dan error; tidak digunakan untuk dekorasi.

### Neutral

- **Slate Inti** (`ink-strong`): heading, nilai penting, shell sidebar, dan teks dengan prioritas tertinggi.
- **Slate Isi** (`ink-body`): body copy, label data, dan kontrol.
- **Slate Redam** (`ink-muted`): metadata dan bantuan; tetap harus memenuhi kontras minimum 4.5:1 pada permukaan terang.
- **Kanvas Kerja** (`canvas`): latar aplikasi.
- **Permukaan Utama** (`surface`): tabel, formulir, panel, dropdown, dan dialog.
- **Permukaan Cekung** (`surface-inset`): toolbar, placeholder data, group pasif, dan skeleton.
- **Batas Tegas** (`border`) dan **Batas Halus** (`border-soft`): pemisah struktural tanpa menambah bayangan.

**The Amber Budget Rule.** Amber jenuh tidak boleh mengisi lebih dari sekitar 10% sebuah layar. Jika dua aksi sama-sama berwarna amber, prioritas aksi belum diselesaikan.

**The Semantic Color Rule.** Success, warning, danger, dan info hanya menandai makna status yang sesuai. Warna tidak boleh menjadi satu-satunya pembeda; selalu sertakan teks, ikon, atau bentuk.

**The Contrast Rule.** Body text dan placeholder wajib mencapai kontras 4.5:1. Teks putih dilarang di atas `brand-amber`; gunakan `action-primary` untuk tombol bertulisan putih.

## Typography

**Display Font:** Instrument Sans dengan fallback `ui-sans-serif` dan `system-ui`  
**Body Font:** Instrument Sans dengan fallback `ui-sans-serif` dan `system-ui`  
**Label/Mono Font:** Instrument Sans; gunakan `ui-monospace` hanya untuk ID teknis, token, checksum, atau nilai yang memang perlu alignment karakter.

**Character:** Satu keluarga sans membuat tabel, peta, form, dan dashboard terasa sebagai satu alat kerja. Kontras dibangun lewat ukuran, weight, dan ruang; bukan dengan memasangkan display font dekoratif.

### Hierarchy

- **Display** (700, 28px, 1.2): judul halaman autentikasi atau empty state besar; bukan untuk setiap halaman admin.
- **Headline** (650, 20px, 1.3): judul halaman utama.
- **Title** (650, 16px, 1.4): judul panel, dialog, dan section penting.
- **Body** (400, 14px, 1.5): teks aplikasi; prose dibatasi 65–75 karakter per baris.
- **Label** (600, 12px, 1.4): label form, metadata, header tabel ringkas; sentence case adalah default.
- **Dense Data** (400–600, 12–13px, 1.35): tabel berisi banyak kolom, dengan angka `tabular-nums`.

**The Fixed Product Scale Rule.** Ukuran tipografi aplikasi menggunakan skala tetap, bukan heading fluid `clamp()`. Responsiveness ditangani oleh struktur layout, bukan mengecilkan judul secara kontinu.

**The Sentence Case Rule.** Uppercase hanya untuk kode, singkatan resmi, atau label status yang sangat pendek. Navigation group tidak boleh mengandalkan tracked uppercase kecil sebagai dekorasi berulang.

## Elevation

Sistem menggunakan layering tonal dan border sebagai pembeda utama. Shadow hanya muncul ketika sebuah permukaan benar-benar berada di atas konteks lain—dropdown, popover, dialog, sticky toolbar yang melintas di atas konten, atau surface interaktif yang terangkat saat hover. Surface biasa tetap datar.

### Shadow Vocabulary

- **Surface Rest** (`0 1px 0 rgba(15,23,42,.04), 0 6px 8px rgba(15,23,42,.05)`): panel penting yang membutuhkan pemisahan halus; jangan dipasangkan dengan border dekoratif tebal.
- **Overlay** (`0 12px 24px rgba(15,23,42,.14)`): dropdown, popover, dan dialog.
- **Focus Ring** (`0 0 0 3px rgba(245,158,11,.28)`): indikasi focus-visible pada kontrol ketika outline native tidak cukup.

**The Flat-by-Default Rule.** Tabel, toolbar, filter, dan form tidak mendapat shadow hanya karena dibungkus container. Gunakan background atau divider lebih dahulu.

**The One Boundary Rule.** Sebuah surface tidak boleh memakai border halus dan shadow lebar sekaligus sebagai dekorasi. Pilih boundary yang paling menjelaskan hierarki.

## Components

### Buttons

- **Shape:** sudut jelas tetapi ramah (`rounded.control`, 10px), tinggi 40px pada desktop dan minimum target 44px pada perangkat coarse pointer.
- **Primary:** `action-primary` dengan teks putih, padding horizontal 16px, satu primary action maksimal per action group.
- **Hover / Focus:** hover mengarah ke amber yang lebih dalam; focus-visible memakai ring 3px; active memberi perubahan tonal instan tanpa bounce.
- **Secondary:** surface putih, teks `ink-body`, dan border `border`; cocok untuk aksi sejajar yang bukan default.
- **Ghost:** tanpa container saat rest, mempunyai hover tonal yang jelas.
- **Danger:** `danger` digunakan untuk konfirmasi destructive final; pemicu dialog boleh berupa secondary/ghost agar bahaya tidak mendominasi layar.
- **Loading / Disabled:** label tetap stabil, spinner muncul setelah delay singkat, klik ganda dicegah, dan disabled tidak pernah menjadi satu-satunya penjelasan mengapa aksi tidak tersedia.

### Chips

- **Style:** bentuk pill hanya untuk filter aktif, status pendek, dan taxonomy; bukan tombol utama.
- **State:** selected memakai `brand-amber-soft`, teks `warning-text`, dan ikon/label; removable chip memiliki target hapus tersendiri dengan accessible name.

### Cards / Containers

- **Corner Style:** surface 14px; overlay 16px. Jangan menggunakan radius di atas 16px untuk card atau section.
- **Background:** putih untuk surface utama, `surface-inset` untuk area sekunder.
- **Shadow Strategy:** flat secara default; shadow hanya saat ada kebutuhan elevation.
- **Border:** satu pixel `border-soft` atau tonal separation; tidak memakai side-stripe berwarna.
- **Internal Padding:** 16px default, 12px untuk dense panels, 24px untuk form section yang lapang.

### Inputs / Fields

- **Style:** tinggi 40px, background putih, border `border`, radius 10px, label selalu terlihat di luar kontrol.
- **Focus:** border amber dan focus ring yang terlihat jelas; tidak menghilangkan outline tanpa pengganti.
- **Error / Disabled:** error memakai pesan inline yang terhubung melalui `aria-describedby`; disabled dibedakan dari readonly dan memiliki alasan jika diperlukan.
- **Placeholder:** memakai `ink-muted`, bukan slate yang terlalu pucat.

### Navigation

- Sidebar memakai `ink-strong`; item aktif menggunakan amber lembut/transparan dengan teks amber yang terbaca. Label dan ikon tetap konsisten saat sidebar collapse.
- Topbar tetap terang dan struktural. Breadcrumb berasal dari metadata route, sedangkan nama record boleh dilengkapi setelah data detail tersedia.
- Mobile memakai navigation drawer dengan focus trap, Escape-to-close, backdrop, dan pengembalian fokus ke trigger.
- Semua link internal memakai named route; URL literal tidak tersebar di komponen.

### Data Table

- Header sticky hanya bila tabel memiliki scroll container yang jelas.
- Angka dan currency menggunakan tabular numbers serta alignment kanan.
- Mobile memilih salah satu pola per tabel: horizontal scroll dengan kolom kunci sticky, priority columns, atau list rows. Jangan otomatis mengubah setiap tabel menjadi card.
- Loading menggunakan skeleton yang mempertahankan ukuran tabel; empty dan error state tidak terlihat sama.

### Map and Location Controls

- Peta adalah surface data, sehingga overlay dan z-index mengikuti skala semantik aplikasi.
- Marker, cluster, popup, dan selected state wajib dapat dibedakan selain dari warna.
- Form lokasi cascading menonaktifkan child control secara eksplisit sampai parent valid dan menampilkan loading per tingkat.

## Do's and Don'ts

### Do:

- **Do** gunakan satu vocabulary komponen untuk semua modul: button, field, dialog, table, feedback, dan pagination harus memiliki state yang sama.
- **Do** simpan filter, sort, tab, dan pagination shareable di URL.
- **Do** desain loading, empty, error, stale, forbidden, offline, dan retry state untuk setiap permintaan data.
- **Do** gunakan target sentuh minimum 44px pada perangkat coarse pointer dan focus-visible pada seluruh kontrol keyboard.
- **Do** pertahankan body text maksimal 65–75 karakter per baris dan gunakan `tabular-nums` untuk data angka.
- **Do** gunakan transisi 150–250ms hanya untuk menyampaikan perubahan state dan hormati `prefers-reduced-motion`.
- **Do** self-host Instrument Sans atau hapus deklarasinya dan gunakan system stack secara konsisten; jangan mengandalkan nama font yang tidak pernah dimuat.

### Don't:

- **Don't** gunakan gradient text, glassmorphism, decorative grid background, atau repeating diagonal stripes.
- **Don't** memakai colored side-stripe lebih dari 1px pada alert, card, atau list item.
- **Don't** membuat card di dalam card; gunakan section, divider, table group, atau whitespace.
- **Don't** memasangkan border 1px dengan shadow lembut ber-blur 16px atau lebih sebagai dekorasi surface biasa.
- **Don't** memakai radius 24–40px pada card, section, input, atau dialog.
- **Don't** memberi animasi entrance berurutan pada page load; pengguna harus langsung masuk ke tugas.
- **Don't** menggunakan modal sebagai solusi pertama untuk filter, edit ringan, atau informasi tambahan.
- **Don't** menyembunyikan aksi tanpa menjaga authorization di API; UI permission hanya meningkatkan UX, bukan batas keamanan.
- **Don't** menaruh response API ke Pinia hanya untuk membuatnya global; server state mempunyai cache dan lifecycle sendiri.
