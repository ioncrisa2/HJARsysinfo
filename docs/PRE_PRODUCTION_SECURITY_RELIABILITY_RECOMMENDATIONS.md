# Rekomendasi Keamanan, Reliability, dan Kesiapan Production

Tanggal penyusunan: 2026-07-26

Dokumen ini merangkum hasil audit repository sebelum deployment production. Fokus
utamanya adalah risiko keamanan, konsistensi database, recovery, operasional,
observability, dan performa. Dokumen ini tidak menggantikan:

- `docs/BACKUP_RESTORE.md`, yang menjelaskan perilaku sistem backup;
- `docs/RBAC_PERMISSION_AUDIT.md`, yang menjelaskan desain permission;
- `docs/SCORING_PEMBANDING_REFACTOR_IMPLEMENTATION_PLAN.md`, yang menjelaskan
  program scoring pembanding.

Temuan dalam dokumen ini adalah snapshot repository pada tanggal penyusunan.
Status dependency, environment production, dan migration harus diperiksa kembali
ketika pekerjaan dilaksanakan.

## Ringkasan Eksekutif

Repository berhasil melewati pemeriksaan dasar berikut:

- 260 automated test lulus dengan 1.671 assertion;
- build frontend berhasil;
- Laravel config cache dan route cache berhasil dibuat;
- `composer.json` valid.

Hasil tersebut belum cukup untuk menyatakan aplikasi siap production. Automated
test saat ini terutama berjalan dengan SQLite in-memory, synchronous queue, serta
array cache/session. Konfigurasi tersebut tidak menguji perilaku MySQL, worker
asynchronous, concurrency, filesystem production, dan batas proses shared
hosting.

Keputusan audit adalah **conditional no-go**: deployment tidak disarankan sebelum
seluruh item P0 selesai atau diverifikasi secara eksplisit. Pekerjaan P1 dapat
dilanjutkan segera setelah blocker ditutup. P2 dan P3 dilakukan bertahap
berdasarkan metrik, bukan asumsi.

## Klasifikasi Prioritas

| Prioritas | Makna |
| --- | --- |
| P0 | Blocker keamanan atau deployment; wajib sebelum production |
| P1 | Risiko operasional tinggi; diselesaikan segera setelah P0 |
| P2 | Optimasi pertumbuhan dan performa |
| P3 | Maintainability dan peningkatan quality gate |

## P0 — Blocker Sebelum Production

### P0.1 Perbarui Dependency yang Memiliki Security Advisory

Audit `composer.lock` menemukan 35 security advisory pada 12 package. Package
yang terdampak mencakup Laravel Framework, PHPSpreadsheet, Symfony, Guzzle, dan
Dompdf. PHPSpreadsheet memiliki relevansi langsung karena aplikasi menerima dan
memproses file Excel dari pengguna.

Risiko utamanya bukan hanya kegagalan import. Parser dokumen adalah attack
surface yang dapat digunakan untuk exhaustion, decompression bomb, SSRF, atau
eksploitasi terhadap parser yang belum ditambal.

Rekomendasi:

1. Perbarui dependency ke patched release yang masih kompatibel dengan Laravel
   12 dan PHP yang digunakan.
2. Jangan menggabungkan major-version upgrade yang tidak diperlukan ke security
   release ini.
3. Setelah update, jalankan seluruh automated test dan uji manual:
   - import `.xlsx` dan `.xlsm`;
   - penolakan file tidak valid dan file terlalu besar;
   - ekspor Excel dan PDF;
   - pengiriman email;
   - pembuatan serta verifikasi backup.
4. Jalankan ulang `composer audit --locked` sampai tidak ada advisory yang masih
   dapat ditindaklanjuti.
5. Ulangi `npm audit`; pemeriksaan frontend sebelumnya belum dapat disimpulkan
   karena respons registry gagal diproses.

Referensi advisory utama:

- <https://github.com/advisories/GHSA-87m4-826x-3crx>
- <https://github.com/advisories/GHSA-xh5m-36r6-47m3>
- <https://github.com/advisories/GHSA-5vg9-5847-vvmq>
- <https://symfony.com/blog/cve-2026-45067-email-header-smtp-command-injection-via-crlf-in-symfony-component-mime-address>

Kondisi selesai:

- audit dependency bersih dari advisory yang memiliki patch kompatibel;
- seluruh automated test dan build lulus;
- alur import/export/email/backup lolos smoke test.

### P0.2 Tutup Akses API untuk User Nonaktif

Web middleware memeriksa `deactivated_at`, tetapi route API terproteksi terutama
oleh `auth:sanctum`. Login API dan rotasi refresh token belum menjadikan status
aktif user sebagai invariant pada setiap jalur.

Dampaknya:

- user yang dinonaktifkan berpotensi tetap menggunakan access token lama;
- login atau refresh token masih dapat berhasil selama kredensial dan role cocok;
- tindakan deactivation dari panel tidak menjamin pemutusan akses.

Rekomendasi:

1. Buat pemeriksaan active-user yang diterapkan setelah `auth:sanctum` pada
   seluruh API terproteksi.
2. Validasi status aktif ketika login dan refresh token.
3. Validasi kembali role yang diizinkan ketika refresh.
4. Cabut seluruh personal access token dan refresh token ketika user
   dinonaktifkan atau dihapus.
5. Tambahkan test untuk token lama, login user nonaktif, refresh user nonaktif,
   dan perubahan role setelah token diterbitkan.

Kondisi selesai:

- user nonaktif selalu menerima respons unauthorized/forbidden;
- token lama tidak dapat dipakai setelah deactivation;
- skenario tersebut dilindungi feature test.

### P0.3 Terapkan System Mode pada API

`CheckSystemMode` saat ini dipasang pada grup middleware web. Walaupun middleware
memiliki logika respons API, route API tidak melewatinya. Akibatnya aplikasi web
dapat terlihat dalam maintenance/off mode sementara consumer API tetap membaca
atau mengubah data.

Rekomendasi:

1. Terapkan system-mode guard pada API.
2. Buat allowlist eksplisit untuk endpoint yang tetap boleh hidup, misalnya
   health check minimal.
3. Bedakan respons maintenance untuk web dan JSON API.
4. Tambahkan test untuk setiap mode: normal, maintenance, dan off.

Kondisi selesai:

- web dan API tunduk pada sumber system mode yang sama;
- tidak ada write endpoint yang tetap terbuka ketika mode melarang operasi.

### P0.4 Tambahkan Rate Limit pada Login Web dan Refresh Token

Login web belum memiliki throttle yang cukup untuk menghambat brute force dan
credential stuffing.

Rekomendasi:

1. Gunakan rate-limit key berdasarkan kombinasi email ternormalisasi dan IP.
2. Terapkan cooldown yang meningkat setelah kegagalan berulang.
3. Reset counter setelah autentikasi berhasil.
4. Batasi endpoint refresh token dan endpoint autentikasi publik lain.
5. Catat kejadian throttling tanpa menyimpan password atau token.

Kondisi selesai:

- percobaan login berulang dibatasi secara deterministik;
- normal login tetap bekerja setelah masa cooldown;
- perilaku rate limit tercakup automated test.

### P0.5 Rekonsiliasi Migration dan Perbaiki Fresh Migration MySQL

Database lokal memiliki tabel aplikasi, tetapi migration ledger hanya mencatat
sebagian migration dasar. Banyak migration aplikasi terlihat `Pending` walaupun
tabelnya sudah ada. Jika production memiliki kondisi serupa, menjalankan
`migrate --force` secara langsung dapat mencoba membuat tabel existing dan
menggagalkan deployment.

Selain itu, fresh migration MySQL memiliki urutan foreign key yang tidak aman:
`data_pembanding` merujuk master table yang migration-nya dijalankan kemudian.
Test SQLite tidak menangkap kegagalan tersebut.

Rekomendasi:

1. Jalankan `php artisan migrate:status` di production sebelum deployment.
2. Bandingkan schema aktual dengan definisi migration:
   - tabel dan kolom;
   - tipe, nullable, dan default;
   - index dan unique constraint;
   - foreign key.
3. Jangan menandai migration sebagai selesai sebelum schema aktual terbukti
   ekuivalen.
4. Susun master table lebih awal atau pindahkan foreign key ke migration lanjutan.
5. Uji `migrate:fresh` pada MySQL kosong.
6. Tambahkan job integration test berbasis MySQL ke quality gate.

Kondisi selesai:

- `migrate:status` production dapat dijelaskan dan konsisten;
- fresh migration berhasil pada MySQL;
- deployment dry-run pada salinan/staging database berhasil;
- tidak ada rekonsiliasi migration ledger yang dilakukan secara buta.

## P1 — Keamanan Administratif dan Reliability

### P1.1 Lindungi Invariant Super-Admin

Permission granular tetap memerlukan business invariant yang tidak boleh
dilewati oleh kombinasi permission apa pun.

Invariant yang direkomendasikan:

- super-admin aktif terakhir tidak dapat dinonaktifkan atau dihapus;
- user tidak dapat menghapus atau menonaktifkan dirinya sendiri;
- pemberian role super-admin memerlukan permission khusus;
- perubahan akses sensitif memerlukan re-authentication;
- permission wajib super-admin tidak dapat dikosongkan melalui UI biasa;
- perubahan role, permission, deactivation, dan token revocation masuk audit log.

Kondisi selesai:

- selalu tersisa minimal satu super-admin aktif;
- perubahan akses kritis dapat ditelusuri ke aktor, waktu, target, dan alasan;
- seluruh invariant dilindungi feature test, bukan hanya disembunyikan dari UI.

### P1.2 Jadikan Backup sebagai Recovery yang Teruji

Backup uploaded files yang berhasil belum membuktikan kesiapan disaster
recovery. Risiko yang masih perlu ditutup:

- proses create/import/verify/restore berjalan melalui HTTP request dan rentan
  timeout;
- paket berada pada disk/server yang sama dengan data utama;
- signature HMAC menjamin integritas, tetapi tidak mengenkripsi isi;
- fallback signing key ke `APP_KEY` dapat menyembunyikan konfigurasi production
  yang tidak lengkap;
- imported dan safety backup dapat tumbuh tanpa retention yang tegas;
- restore database masih sengaja belum diimplementasikan;
- restore drill belum menjadi prosedur rutin.

Rekomendasi:

1. Production wajib memiliki `SYSTEM_BACKUP_SIGNING_KEY` khusus dan stabil.
2. Generate operation reference sebelum exception dilaporkan, lalu sertakan
   reference tersebut ke log context.
3. Jalankan pekerjaan berat sebagai scheduled CLI/background process bila
   lingkungan hosting mengizinkan.
4. Simpan salinan terenkripsi pada storage yang terpisah dari hosting utama.
5. Terapkan retention berbeda untuk generated, imported, dan safety backup,
   disertai minimum-last-N dan disk watermark.
6. Verifikasi checksum/signature setelah backup dan setelah transfer.
7. Lakukan restore drill berkala pada environment non-production.
8. Pertahankan database restore dalam kondisi terkunci sampai lock non-database,
   runner, purge session/token/job, health check, dan rollback selesai.

Catatan shared hosting:

- gunakan Laravel scheduler melalui cron;
- queue worker dapat dijalankan secara short-lived dan dicegah overlap;
- backup besar tetap dapat dibunuh oleh limit CPU, memory, atau process time dari
  provider; kondisi ini harus diuji, bukan diasumsikan;
- backup bawaan provider dapat menjadi lapisan tambahan, bukan satu-satunya
  backup.

Kondisi selesai:

- backup database dan uploaded files dibuat serta diverifikasi otomatis;
- tersedia salinan offsite terenkripsi;
- reference error dapat dicari di log;
- restore drill menghasilkan bukti keberhasilan dan waktu pemulihan.

### P1.3 Bangun System Health untuk Super-Admin

Panel super-admin perlu menampilkan kondisi operasional yang dapat
ditindaklanjuti, bukan secret atau detail infrastruktur mentah.

Informasi minimum:

| Area | Informasi |
| --- | --- |
| Aplikasi | versi, environment, deploy terakhir, system mode |
| Database | koneksi, latency sederhana, ukuran, migration pending |
| Queue | pending, failed, job tertua, worker heartbeat |
| Scheduler | heartbeat terakhir dan keterlambatan |
| Storage | writable, kapasitas, pemakaian, ruang tersisa |
| Backup | backup terakhir, tipe, ukuran, verifikasi, umur, kegagalan terakhir |
| Runtime | versi PHP/Laravel/MySQL dan extension/binary penting |
| Error | jumlah error terbaru dan operation reference |

Status harus sederhana dan konsisten: normal, warning, critical, dan unknown.
Threshold tidak boleh hanya ditentukan di frontend.

Panel tidak boleh menampilkan:

- `APP_KEY`, signing key, password, token, atau isi `.env`;
- DSN lengkap;
- query yang mengandung data pribadi;
- stack trace mentah kepada seluruh super-admin;
- kemampuan menjalankan arbitrary shell command.

System Health bukan pengganti monitoring eksternal. Ketika aplikasi atau
database mati, panel ikut tidak dapat digunakan. Uptime endpoint, SSL expiry,
response time, dan heartbeat harus tetap dipantau dari luar aplikasi.

Kondisi selesai:

- kegagalan scheduler, queue, storage, database, atau backup terlihat jelas;
- tersedia alert eksternal ketika panel tidak dapat dibuka;
- setiap status merah memiliki tindakan operator yang terdokumentasi.

### P1.4 Perbaiki Logging, Alerting, dan Retention

Single log file akan terus membesar dan menyulitkan pencarian insiden. Activity
log juga berpotensi menyimpan field properti yang mengandung alamat atau nomor
telepon tanpa retention yang jelas.

Rekomendasi:

1. Gunakan daily log dengan batas retention.
2. Tentukan field PII yang boleh dan tidak boleh masuk activity log.
3. Tambahkan pruning untuk activity log, failed jobs, temporary exports, dan
   backup.
4. Gunakan operation reference yang konsisten pada error penting.
5. Kirim alert untuk:
   - lonjakan application error;
   - failed job;
   - scheduler heartbeat terlambat;
   - disk melewati threshold;
   - backup gagal atau terlalu lama tidak dibuat.

Kondisi selesai:

- log tidak tumbuh tanpa batas;
- incident dapat dilacak dari reference UI ke log;
- data sensitif tidak tercatat tanpa kebutuhan dan retention yang sah.

### P1.5 Perketat Konfigurasi Production dan HTTP Security

Konfigurasi production minimum:

- `APP_ENV=production`;
- `APP_DEBUG=false`;
- `APP_URL` memakai HTTPS;
- secure session cookie aktif;
- proxy yang dipercaya dibatasi atau origin server dilindungi;
- host yang diterima dibatasi bila infrastruktur memungkinkan;
- secret memiliki permission file yang ketat;
- MySQL tidak diekspos publik.

Tambahkan security header yang sesuai, antara lain HSTS setelah HTTPS stabil,
frame protection, content type protection, referrer policy, dan Content Security
Policy yang diuji agar tidak merusak Inertia/Vue.

Kondisi selesai:

- production tidak membocorkan debug output;
- request melalui host/proxy tidak sah ditolak atau tidak dapat mencapai origin;
- header keamanan tervalidasi melalui browser/network inspection.

### P1.6 Batasi System Setting yang Dikirim ke Frontend

`SystemSetting::getAll()` berpotensi membagikan semua setting pada setiap
response Inertia, termasuk halaman login. Jika secret ditambahkan ke tabel
setting pada masa depan, secret tersebut dapat ikut terkirim ke browser.

Rekomendasi:

1. Buat allowlist eksplisit untuk public frontend settings.
2. Pisahkan setting publik, setting operator, dan secret.
3. Jangan menggunakan endpoint aplikasi untuk menjalankan `config:clear` atau
   `route:clear` sebagai tindakan rutin.
4. Cache system mode dengan TTL pendek dan invalidasi saat setting berubah agar
   tidak membaca database pada setiap request.

Kondisi selesai:

- hanya setting yang dinyatakan publik yang muncul pada Inertia props;
- perubahan system mode cepat terlihat tanpa query database berulang;
- optimasi config/route dikelola oleh proses deployment.

## P2 — Optimasi Performa dan Pertumbuhan

### P2.1 Hilangkan Global Eager Loading pada Pembanding

Model `Pembanding` memiliki global eager loading sejumlah relasi. Semua query,
termasuk candidate retrieval dan scoring, dapat ikut memuat relasi yang tidak
dibutuhkan. Pada pipeline bertahap, biaya ini berulang dan meningkatkan query,
memory, serta serialisasi.

Rekomendasi:

- hilangkan global `$with`;
- load relasi secara eksplisit per use case;
- candidate retrieval hanya mengambil kolom yang diperlukan untuk eligibility
  dan similarity;
- detail kandidat memuat relasi lengkap setelah candidate dipilih;
- ukur query count, memory, dan latency sebelum serta sesudah perubahan.

Kondisi selesai:

- output API tetap kompatibel;
- query scoring tidak memuat relasi laporan yang belum diperlukan;
- tersedia benchmark dengan data production-like.

### P2.2 Batasi Payload dan Query Map

Endpoint map mengambil seluruh marker tanpa viewport. Dua ribu data masih dapat
ditangani, tetapi pola ini tidak memiliki batas pertumbuhan.

Rekomendasi bertahap:

1. Gunakan DTO marker ringkas.
2. Tambahkan filter bounding box berdasarkan viewport.
3. Terapkan limit dan respons saat hasil terlalu padat.
4. Tambahkan clustering berdasarkan zoom bila kebutuhan UI menuntut.
5. Gunakan cache/ETag untuk query yang stabil.

Kondisi selesai:

- map tidak mengirim seluruh dataset ketika pengguna hanya melihat satu wilayah;
- payload dan response time terukur pada volume data target.

### P2.3 Optimalkan Pencarian Wilayah

Tabel village memiliki sekitar 80 ribu baris. Pencarian tanpa parent filter dan
wildcard `%query%` dapat melakukan full scan serta sorting mahal.

Rekomendasi:

- wajibkan parent ID yang relevan untuk pencarian district/village;
- tambahkan composite index sesuai pola filter dan order aktual;
- utamakan prefix search bila sesuai UX;
- beri limit dan pagination;
- validasi hasil menggunakan `EXPLAIN` pada MySQL.

Kondisi selesai:

- query lokasi menggunakan index pada pola penggunaan utama;
- pencarian tidak melakukan scan seluruh village untuk input pendek.

### P2.4 Profiling Scoring dan Query Browse

Normal B-tree tidak membantu pencarian teks yang diawali wildcard. Selama data
masih kecil, optimasi agresif belum dibutuhkan. Jika jumlah data dan concurrency
bertambah, pertimbangkan full-text index atau search service berdasarkan hasil
profiling.

Perbaikan sederhana yang perlu diperiksa:

- hindari `whereDate()` pada kolom yang sudah bertipe `DATE` jika perbandingan
  langsung dapat menggunakan index;
- pisahkan candidate pool limit dari result limit;
- log waktu retrieval, jumlah kandidat, waktu scoring, dan fallback stage tanpa
  mencatat payload sensitif;
- gunakan slow-query log atau instrumentation setara pada staging/production.

Kondisi selesai:

- tersedia baseline p50/p95, query count, candidate count, dan memory;
- keputusan index atau search engine didasarkan pada data pengukuran.

## P3 — Maintainability dan Quality Gate

### P3.1 Tambahkan CI di Luar Hosting

CI tidak perlu berjalan pada shared hosting. Pipeline dapat berjalan pada
repository host atau mesin build terpisah.

Quality gate minimum:

1. `composer validate`;
2. `composer audit --locked`;
3. PHP automated test;
4. fresh migration dan integration test dengan MySQL;
5. frontend dependency audit;
6. frontend production build;
7. formatting/lint setelah baseline legacy disepakati.

Global Pint check saat audit masih gagal pada banyak file legacy. Jangan
memformat seluruh repository tepat sebelum deployment. Tetapkan baseline dan
terapkan quality gate pada file yang berubah terlebih dahulu, kemudian lunasi
legacy debt bertahap.

### P3.2 Tambahkan Frontend Quality Gate

Frontend build berhasil, tetapi belum ada lint dan test frontend yang memadai.

Rekomendasi:

- tambahkan ESLint untuk JavaScript/Vue;
- tambahkan component test pada alur kritis;
- tambahkan browser test untuk login, pembanding, import, export, dan backup;
- kurangi penggunaan `v-html`, termasuk pagination label, agar tidak menjadi XSS
  sink ketika sumber label berubah.

### P3.3 Pecah Controller Besar Secara Bertahap

Beberapa controller memiliki tanggung jawab besar pada query, orchestration,
validation, transformasi response, dan side effect.

Refactor dilakukan setelah perilaku dilindungi test:

- query dipindahkan ke query service;
- operasi bisnis ke action/service;
- transformasi API ke resource/DTO;
- controller tetap menjadi pengatur request dan response;
- jangan melakukan big-bang refactor menjelang deployment.

Kondisi selesai:

- controller kritis lebih kecil tanpa mengubah kontrak API;
- unit dan feature test melindungi service boundary baru.

### P3.4 Lindungi File yang Seharusnya Privat

Uploaded property photos saat ini dapat tersedia melalui public storage URL.
Jika foto atau dokumen bersifat internal, random filename bukan access control.

Rekomendasi:

- klasifikasikan file publik dan privat;
- file privat disimpan di private disk;
- file diberikan melalui authorized controller atau signed temporary URL;
- akses download dicatat bila data memiliki sensitivitas tinggi.

## Rencana Pelaksanaan

### Fase 1 — Security Release dan Database Safety

Mulai dari dependency update karena patch yang tersedia memengaruhi attack
surface file upload. Setelah itu tutup user-deactivation bypass, system-mode
bypass, dan brute-force surface. Fase yang sama harus menyelesaikan rekonsiliasi
migration karena tidak masuk akal mengamankan aplikasi tetapi tetap melakukan
deployment dengan schema yang tidak dapat diprediksi.

Fase ini selesai ketika automated test dan build lulus, fresh MySQL migration
berhasil, serta production migration status telah diverifikasi.

### Fase 2 — Administrative Safety dan Recovery

Terapkan invariant super-admin dan token revocation sebelum menambah aksi
operasional baru. Berikutnya perbaiki reference error, retention, offsite backup,
dan restore drill. Aksi restore database tetap terkunci.

Fase ini selesai ketika administrator tidak dapat kehilangan akses terakhir,
backup dapat ditemukan dan diverifikasi, serta satu restore drill berhasil.

### Fase 3 — Observability dan Operasional Shared Hosting

Bangun health-check service dan heartbeat lebih dulu, baru tampilkan System
Health di panel. Scheduler dijalankan oleh cron. Jika long-running worker tidak
didukung, gunakan short-lived queue worker dengan overlap protection dan ukur
apakah job berat dapat selesai dalam limit provider.

Fase ini selesai ketika kegagalan database, storage, queue, scheduler, dan backup
dapat diketahui tanpa menunggu laporan pengguna.

### Fase 4 — Optimasi Berbasis Pengukuran

Hapus global eager loading, ringkas payload map, dan optimalkan pencarian wilayah.
Sebelum serta sesudah perubahan, rekam query count, memory, dan p95 response.
Hindari penambahan cache atau search engine tanpa bukti bottleneck.

### Fase 5 — Maintainability

Tambahkan CI dan frontend quality gate, lalu refactor controller besar secara
bertahap. Fase ini tidak boleh menjadi alasan menunda P0 atau melakukan big-bang
rewrite.

## Go-Live Gate

Deployment production hanya dilakukan setelah seluruh item berikut memiliki
bukti:

- [ ] Tidak ada unreviewed high/critical dependency advisory yang memiliki patch.
- [ ] Automated test dan frontend build lulus setelah dependency update.
- [ ] User nonaktif tidak dapat login, refresh, atau memakai token lama.
- [ ] Maintenance/off mode berlaku konsisten pada web dan API.
- [ ] Login web dan refresh token memiliki rate limit yang teruji.
- [ ] `migrate:status` production sudah diaudit.
- [ ] Fresh migration MySQL berhasil.
- [ ] Backup database dan uploaded files berhasil serta terverifikasi.
- [ ] Signing key production khusus sudah dikonfigurasi.
- [ ] `APP_DEBUG=false`, HTTPS, dan secure cookie telah diverifikasi.
- [ ] Cron scheduler berjalan dan heartbeat terlihat.
- [ ] Log rotation dan minimum alerting aktif.
- [ ] Smoke test login, pembanding, API, import, export, dan backup lulus.
- [ ] Rollback file dan prosedur penanganan migration gagal terdokumentasi.

## Pemeriksaan Setelah Deployment

Dalam satu jam pertama:

- periksa error log dan operation reference;
- periksa response time endpoint utama;
- periksa login web dan API;
- periksa queue serta scheduler heartbeat;
- pastikan tidak ada migration pending yang tidak dijelaskan;
- verifikasi satu backup pascadeploy.

Dalam 24 jam pertama:

- periksa failed jobs, disk usage, dan pertumbuhan log;
- periksa slow query dan query scoring;
- verifikasi alert eksternal dapat diterima;
- pastikan tidak ada user nonaktif yang masih memiliki token aktif.

Dalam tujuh hari pertama:

- review p95 response time, error rate, queue wait, dan backup age;
- evaluasi threshold System Health;
- jalankan atau jadwalkan restore drill;
- prioritaskan optimasi berikutnya berdasarkan metrik yang terkumpul.

## Definisi Kesiapan Production

Aplikasi dianggap siap production bukan ketika halaman dapat dibuka atau test
sekadar hijau, melainkan ketika:

1. akses yang dicabut benar-benar berhenti pada web dan API;
2. deployment database dapat diprediksi dan diulang;
3. kegagalan dapat dideteksi sebelum dilaporkan pengguna;
4. backup berada di lokasi terpisah dan terbukti dapat dipulihkan;
5. pekerjaan background memiliki mekanisme eksekusi serta kegagalan yang jelas;
6. optimasi dilakukan berdasarkan bukti beban nyata;
7. operator memiliki prosedur deploy, rollback, dan recovery yang dapat diikuti.
