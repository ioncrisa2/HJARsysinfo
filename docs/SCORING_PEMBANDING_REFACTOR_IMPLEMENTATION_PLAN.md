# Scoring Pembanding dan Kesiapan Laporan - Implementation Plan

Tanggal penyusunan: 2026-07-23

Status legend:

| Status | Arti |
| --- | --- |
| `[x]` | Keputusan sudah disepakati atau pekerjaan selesai dan terverifikasi |
| `[ ]` | Belum dikerjakan |
| `[~]` | Sedang dikerjakan atau sebagian selesai |
| `[!]` | Terblokir atau membutuhkan keputusan domain |

Status implementasi: implementasi fondasi V1/V2, service separation, coverage, evidence quality, dan report readiness selesai di local. Default deployment tetap `v2_shadow`; audit serta validasi production belum dijalankan.

Terakhir diperbarui: 2026-07-25

Catatan produksi: database lokal hanya memiliki 7 data aktif, sedangkan production dilaporkan memiliki hampir 2.000 data. Jumlah production cukup untuk audit distribusi dan eksperimen offline awal, tetapi tidak otomatis cukup untuk kalibrasi bobot. Kalibrasi tetap membutuhkan keputusan reviewer atau target validasi lain yang sah.

### Snapshot Status Implementasi

| Area | Status | Catatan |
| --- | --- | --- |
| Fondasi backend V1/V2 | Selesai lokal | Pipeline legacy, V2, dan shadow telah dipisahkan dan diuji |
| Candidate retrieval dan ranking V2 | Selesai lokal | Staged retrieval, candidate pool, deterministic ranking, dan fallback metadata tersedia |
| Coverage, evidence, dan report readiness | Sebagian selesai | Profile teknis tersedia; profile laporan final dan status verifikasi masih membutuhkan keputusan domain |
| Dokumentasi API | Selesai lokal | `docs/API.md` dan assertion schema scoring spesifik pada OpenAPI telah diperbarui |
| Audit dan benchmark production | Belum dimulai | Membutuhkan akses read-only atau salinan production yang disetujui |
| Validasi domain dan reviewer | Belum dimulai | Matriks dan bobot masih berstatus heuristic yang belum disetujui |
| Integrasi aplikasi reviewer | Belum dimulai | Feedback, selection, override, dan finalization berada di aplikasi consumer |
| Rollout V2 production | Belum dimulai | Production harus tetap pada `v2_shadow` sampai bukti validasi tersedia |

Tanda `[x]` di bawah berarti tersedia dan terverifikasi pada repository lokal, bukan berarti sudah tervalidasi pada data production atau disetujui valuation domain owner.

Hasil sinkronisasi repository per 2026-07-25: 114 item selesai, 19 item sebagian selesai, 88 item belum dikerjakan, dan 5 keputusan domain terblokir. Angka tersebut mencakup seluruh program sampai audit production, integrasi reviewer, rollout, dan governance—bukan hanya implementasi backend.

## Ringkasan Keputusan

- [x] Nilai `score` tidak boleh diposisikan sebagai probabilitas atau tingkat keyakinan.
- [x] Scoring dipisahkan menjadi eligibility, similarity, evidence quality, report readiness, dan confidence/coverage.
- [x] Harga objek acuan tidak menjadi komponen similarity.
- [x] `null` dibedakan dari nilai nol yang sah.
- [x] `result_limit` dipisahkan dari `candidate_pool_limit`.
- [x] Exact match peruntukan dibedakan dari substitusi dalam grup yang sama.
- [x] Komponen numerik menggunakan fungsi kontinu dan tidak memakai hard cliff seperti selisih lebar jalan 1,00 m versus 1,01 m.
- [x] Endpoint pencarian pembanding tidak diduplikasi untuk kebutuhan laporan.
- [x] Endpoint existing `POST /api/v1/pembandings/similar` dan `GET /api/v1/pembandings/{id}/similar` tetap menjadi pintu pencarian dan ranking.
- [x] Endpoint existing `GET /api/v1/pembandings/{id}` tetap menjadi pintu detail kandidat.
- [x] Kelengkapan scoring dan kelengkapan laporan dilaporkan sebagai dua hal berbeda.
- [x] Endpoint snapshot/finalisasi laporan hanya dibuat jika laporan formal harus immutable dan auditable.
- [x] V1 dan V2 harus berjalan dalam shadow mode sebelum V2 menggantikan ranking production.
- [x] Threshold seperti `score >= 80` tidak boleh ditetapkan sebelum validasi.

Konsekuensi utama keputusan ini adalah bahwa kandidat yang sangat mirip tetap dapat ditampilkan walaupun belum siap dimasukkan ke laporan. Reviewer harus melihat warning dan field laporan yang kosong, bukan menerima skor similarity yang diam-diam diturunkan oleh kekurangan administratif.

## Tujuan

Refaktor ini harus menghasilkan ranking pembanding yang lebih masuk akal, dapat dijelaskan, dan dapat diaudit. API harus membantu reviewer menjawab tiga pertanyaan yang berbeda:

1. Apakah kandidat layak dibandingkan dengan objek acuan?
2. Jika layak, seberapa mirip karakteristiknya?
3. Apakah datanya cukup kuat dan cukup lengkap untuk dipakai dalam laporan?

Hasil akhir tidak ditujukan untuk menggantikan pertimbangan profesional reviewer. Sistem harus mempersempit kandidat, menjelaskan alasan ranking, dan merekam keputusan reviewer agar metode dapat diperbaiki menggunakan data nyata.

## Non-Tujuan Fase Awal

Hal berikut tidak boleh menahan rilis scoring V2:

- network/route distance penuh;
- otomatisasi highest and best use;
- inferensi condition of sale tanpa data terverifikasi;
- model machine learning production;
- diversifikasi pembanding otomatis berdasarkan proyek atau sumber;
- penentuan final adjusted value;
- penetapan skor sebagai probabilitas kandidat diterima reviewer;
- pembuatan endpoint pencarian kedua khusus laporan.

Fitur tersebut dapat dikerjakan setelah fondasi similarity, coverage, observability, dan reviewer feedback terbukti.

## Masalah Sistem Saat Ini

### Candidate retrieval

`limit` request saat ini dipakai sebagai batas kandidat database sekaligus jumlah hasil akhir. Query mengurutkan jarak dan memotong kandidat sebelum scorer bekerja. Kandidat yang sedikit lebih jauh tetapi jauh lebih mirip dapat tidak pernah dinilai.

Filter district juga menjadi batas pasar yang terlalu keras. Kandidat buruk dalam district dapat menghentikan perluasan pencarian, sementara kandidat kuat tepat di seberang batas administratif tidak pernah masuk pool.

### Similarity

Scorer saat ini memiliki bobot nominal 100, tetapi angka akhirnya bukan persentase kemiripan yang terkalibrasi. Exact peruntukan tidak dibedakan dari kategori lain dalam grup yang sama, kategori ordinal dibentuk dari asumsi, dan beberapa komponen memakai batas diskrit yang menciptakan lompatan skor.

Harga ikut memberi poin similarity. Hal ini menciptakan circular reasoning karena kandidat dapat dipilih hanya karena harganya sudah mendekati ekspektasi harga objek acuan.

### Missing value

Field numerik opsional yang tidak dikirim diubah menjadi nol. Sistem tidak dapat membedakan data tidak diketahui dari nol yang sah. Payload yang minim juga memperoleh maksimum skor yang berbeda dari payload lengkap, sehingga score lintas request tidak dapat dibandingkan secara langsung.

### Explainability dan laporan

Response hanya memberikan score akhir, jarak, rank, priority, dan fallback. Reviewer tidak mengetahui komponen yang cocok, field yang kosong, versi metode, atau alasan kandidat masuk fallback.

`SimilarPembandingResource` saat ini menggabungkan `PembandingResource`, sehingga data kandidat lengkap sudah dapat ikut dikirim. Masalahnya bukan ketiadaan endpoint data, tetapi belum adanya kontrak eksplisit untuk scoring coverage dan report readiness.

## Terminologi Target

| Istilah | Definisi |
| --- | --- |
| Eligibility | Keputusan apakah kandidat boleh masuk proses pembandingan |
| Similarity | Kemiripan atribut pasar dan fisik antara acuan dengan kandidat |
| Evidence quality | Kekuatan bukti kandidat, misalnya transaksi/penawaran, recency, dan verifikasi |
| Reference coverage | Kelengkapan atribut objek acuan dibanding profil atribut yang seharusnya tersedia |
| Score coverage | Kelengkapan atribut kandidat terhadap komponen yang berlaku untuk objek acuan |
| Report readiness | Kesiapan field kandidat untuk dimasukkan ke jenis laporan tertentu |
| Similarity rank | Urutan berdasarkan similarity tanpa kebijakan evidence/diversification tambahan |
| Final rank | Urutan yang benar-benar dikirim ke reviewer setelah kebijakan eligibility dan evidence |
| Method version | Identitas immutable dari konfigurasi dan rumus scoring |
| Retrieval stage | Tahap perluasan area atau fallback saat kandidat ditemukan |

## Arsitektur Target

Alur target dijalankan dalam urutan berikut:

1. Request dinormalisasi tanpa mengubah missing value menjadi nol.
2. Sistem menentukan profil objek acuan dan market basis.
3. Kandidat yang jelas tidak eligible dikeluarkan.
4. Candidate pool diambil menggunakan radius dan tahap perluasan pasar yang eksplisit.
5. Seluruh kandidat dalam pool dihitung similarity dan coverage-nya.
6. Evidence quality dan report readiness dihitung terpisah.
7. Kandidat diurutkan menggunakan kebijakan ranking yang versioned.
8. API mengembalikan hasil beserta explanation dan warning.
9. Reviewer memilih atau menolak kandidat.
10. Keputusan reviewer dicatat sebagai data validasi.
11. Jika laporan difinalisasi, data kandidat terpilih disimpan sebagai snapshot.

Tidak boleh ada komponen yang mengubah similarity secara tersembunyi setelah ranking. Faktor fallback, evidence tier, atau diversifikasi harus tampil sebagai field tersendiri dan dapat dijelaskan.

## Kontrak Eligibility

Eligibility harus berisi aturan yang bersifat benar/salah atau tier eksplisit. Eligibility bukan tempat menaruh pengurangan skor kecil.

### Aturan awal

- [x] Kandidat soft-deleted dikeluarkan oleh global scope Eloquent; relasi taxonomy hanya memakai master data aktif.
- [~] Input wajib memiliki koordinat valid dan kandidat dibatasi bounding box/radius; audit koordinat invalid pada data production belum dilakukan.
- [x] Market basis sale dan rent tidak boleh dicampur ketika V2 aktif.
- [~] Jenis objek missing/mismatch ditempatkan pada secondary tier; matriks incompatible final belum disetujui.
- [x] Peruntukan yang tidak kompatibel dikeluarkan atau ditempatkan pada secondary tier.
- [~] Kandidat setelah `reference_date` dikeluarkan; kebijakan umur maksimum evidence belum ditentukan.
- [ ] Kandidat non-market atau condition of sale bermasalah tidak masuk primary evidence jika statusnya benar-benar terverifikasi.
- [~] Gudang dan ruko mempertahankan kebijakan konservatif existing; matriks substitusi masih menunggu persetujuan valuation domain owner.

### Keputusan domain yang harus disetujui

- [!] Matriks kompatibilitas `jenis_objek`.
- [!] Matriks exact/substitution untuk `peruntukan`.
- [!] Umur maksimum primary dan secondary evidence per segmen.
- [!] Kebijakan transaksi, penawaran, dan sewa.
- [!] Kebijakan forced sale, related party, bulk sale, dan kondisi non-arm's-length setelah field tersedia.

## Perbaikan Taxonomy Jenis Listing

Master data saat ini mencampur dua konsep:

- `penawaran` dan `transaksi` menjelaskan jenis/status bukti;
- `sewa` menjelaskan basis pasar atau bentuk nilai.

Target domain harus memisahkan:

| Konsep | Contoh nilai |
| --- | --- |
| `market_basis` | `sale`, `rent` |
| `evidence_type` | `transaction`, `offer`, `unknown` |

Migrasi tidak boleh langsung menghapus `jenis_listing_id`. Sistem perlu mapping backward-compatible, audit nilai existing, dan periode dual-read sampai seluruh consumer API siap.

## Model Similarity V2

Setiap komponen menghasilkan nilai kontinu 0 sampai 1. Nilai akhir menggunakan weighted mixed-attribute similarity:

`similarity_score = weighted_sum / applicable_weight x 100`

Bobot awal tetap merupakan expert heuristic dan harus disimpan dalam konfigurasi versioned. Bobot tidak boleh dianggap final sebelum ada hasil audit production dan keputusan reviewer.

### Aturan missing value

| Kondisi | Perlakuan |
| --- | --- |
| Field acuan tidak diketahui | Komponen dikeluarkan dari applicable weight |
| Field acuan tersedia, kandidat tidak diketahui | Similarity komponen 0 dan warning ditambahkan |
| Field tidak berlaku untuk profil objek | Komponen dikeluarkan |
| Kedua nilai tersedia | Hitung similarity |
| Nilai nol sah | Tetap dihitung sebagai nol, bukan missing |

`reference_coverage` dihitung terhadap profil atribut ideal untuk jenis objek acuan. `score_coverage` dihitung dari bobot atribut kandidat yang tersedia terhadap applicable weight objek acuan. Dua metrik ini tidak boleh digabungkan ke similarity.

### Komponen awal

| Komponen | Perlakuan target |
| --- | --- |
| Lokasi/jarak | Continuous decay, parameter dapat berbeda per segmen |
| Peruntukan | Exact match, approved substitution, atau incompatible |
| Jenis objek | Exact match atau matriks substitusi |
| Luas tanah | Continuous ratio/log-ratio jika berlaku |
| Luas bangunan | Continuous ratio/log-ratio jika berlaku |
| Dokumen/legalitas | Matriks domain, bukan rank universal yang diasumsikan |
| Lebar jalan | Fungsi kontinu tanpa hard cliff |
| Posisi tanah | Exact/matrix similarity berdasarkan dampak pasar |
| Kondisi tanah | Matriks domain; rawa tidak otomatis setara dengan belum berkembang |
| Harga | Tidak masuk similarity |

### Harga setelah selection

Harga kandidat tetap dibutuhkan untuk laporan dan analisis. Setelah kandidat dipilih, sistem dapat menyediakan:

- harga per meter yang sesuai jenis objek;
- perbedaan terhadap median kandidat;
- indikasi outlier;
- informasi basis harga sale/rent;
- penyesuaian waktu atau karakteristik pada fase lanjutan.

Analisis harga tersebut tidak boleh digunakan untuk memilih kandidat hanya karena mendekati harga objek acuan.

## Evidence Quality

Evidence quality dihitung setelah kandidat dinyatakan eligible. Komponen awal:

- jenis bukti transaksi, penawaran, atau unknown;
- recency terhadap reference date;
- status verifikasi;
- kelengkapan sumber informasi;
- condition of sale jika sudah tersedia dan terverifikasi.

Evidence quality tidak boleh menyamarkan kandidat yang tidak eligible. Kandidat forced sale terverifikasi tidak boleh berubah menjadi primary evidence hanya karena datanya baru dan lengkap.

## Ranking Policy

Ranking harus deterministik, versioned, dan dapat dijelaskan. Urutan awal yang disarankan:

1. eligibility/evidence tier;
2. scoring status yang memadai;
3. similarity score descending;
4. evidence quality descending;
5. score coverage descending;
6. recency descending;
7. distance ascending;
8. ID stabil sebagai tie-breaker terakhir.

API perlu membedakan `similarity_rank` dari `rank`. Dengan demikian reviewer dapat melihat apakah kandidat turun karena similarity atau karena kualitas evidence.

Kebijakan gudang yang memakai priority rank dapat dipertahankan sementara, tetapi tier dan alasan prioritas harus muncul dalam explanation. Uniform fallback penalty `0.75` tidak dipertahankan sebagai manipulasi similarity; fallback dinyatakan melalui retrieval stage dan evidence/eligibility tier.

## Candidate Pool dan Perluasan Area

`candidate_pool_limit` dan `result_limit` harus menjadi parameter internal yang berbeda:

- `result_limit` adalah jumlah maksimum hasil ke consumer;
- `candidate_pool_limit` adalah jumlah kandidat yang benar-benar dinilai.

Tidak ada angka final yang diputuskan sebelum benchmark production. Audit harus membandingkan beberapa ukuran pool, misalnya 100, 300, 500, dan 1.000, lalu memilih batas terkecil yang mempertahankan candidate recall dan latency yang dapat diterima.

### Tahap retrieval

- [x] Tahap 1 mengambil kandidat pada area pasar utama atau lokasi administratif terdekat.
- [x] Tahap 2 memperluas dalam regency/radius jika jumlah kandidat memadai belum tercapai.
- [x] Tahap 3 memperluas lintas batas administratif tetapi tetap dalam radius pasar.
- [x] Tahap fallback lintas peruntukan hanya berjalan jika policy mengizinkan.
- [x] Setiap hasil menyimpan `retrieval_stage` dan alasan fallback/relaksasi.
- [x] Sedikit kandidat buruk pada tahap awal tidak otomatis menghentikan perluasan.

District tidak boleh dianggap identik dengan market area. Sampai market area formal tersedia, district digunakan sebagai sinyal retrieval, bukan satu-satunya hard boundary.

## Kontrak API Target

Tidak ada endpoint pencarian baru. Endpoint existing dipertahankan:

| Endpoint | Tanggung jawab |
| --- | --- |
| `POST /api/v1/pembandings/similar` | Ranking berdasarkan payload acuan |
| `GET /api/v1/pembandings/{id}/similar` | Ranking berdasarkan record acuan |
| `GET /api/v1/pembandings/{id}` | Detail lengkap satu kandidat |

### Field additive untuk hasil similar

| Field | Tujuan |
| --- | --- |
| `similarity_score` | Similarity V2 pada skala 0-100 |
| `score` | Alias backward-compatible selama masa transisi |
| `reference_coverage` | Kelengkapan payload/record acuan |
| `score_coverage` | Kelengkapan kandidat untuk komponen similarity |
| `scoring_status` | `scored`, `insufficient_input`, atau status versioned lain |
| `method_version` | Versi metode dan konfigurasi |
| `similarity_rank` | Urutan similarity mentah |
| `rank` | Urutan final |
| `eligibility_tier` | Tier kelayakan kandidat |
| `evidence_quality` | Nilai atau struktur kualitas evidence |
| `evidence_tier` | Primary, secondary, atau reviewer-only |
| `retrieval_stage` | Tahap query/fallback |
| `component_scores` | Nilai, weight, contribution, dan alasan per komponen |
| `warnings` | Missing data, fallback, policy, atau kualitas data |
| `report_readiness` | Status kesiapan laporan |
| `report_completeness` | Persentase field laporan yang tersedia |
| `report_missing_fields` | Daftar field wajib laporan yang kosong |
| `record_version` | Versi/timestamp record untuk mencegah stale report |

Field baru bersifat additive. Consumer lama tetap menerima `score`, `distance`, `rank`, `priority_rank`, dan `is_fallback` selama masa kompatibilitas.

### Response size

Karena `SimilarPembandingResource` saat ini mengirim resource pembanding lengkap, ukuran response production harus diukur. Jika response terlalu besar:

- hasil similar hanya mengirim summary dan metadata scoring;
- detail kandidat terpilih diambil dari endpoint detail;
- bila reviewer memilih banyak kandidat sekaligus, tambahkan batch detail berdasarkan ID, bukan melakukan satu request per ID.

Perubahan dari full resource ke summary tidak boleh dilakukan tanpa koordinasi dan versioning karena dapat memutus aplikasi reviewer yang sudah bergantung pada field lengkap.

## Report Readiness

Report readiness tidak boleh menggunakan satu daftar field universal. Kebutuhan laporan tanah kosong, rumah, apartemen, gudang, transaksi, dan sewa dapat berbeda.

### Report profile

- [~] Profile laporan teknis berdasarkan jenis objek tersedia; variasi berdasarkan market basis dan persetujuan pemilik laporan belum tersedia.
- [~] Field wajib dan tidak berlaku telah dipisahkan pada profile teknis; daftar field opsional eksplisit belum didefinisikan.
- [x] Hitung `report_completeness` berdasarkan profile.
- [x] Kembalikan `report_missing_fields` dengan nama field kontrak API.
- [x] Kandidat incomplete tetap dapat ditampilkan jika eligible.
- [ ] Kandidat incomplete tidak dapat difinalisasi ke laporan jika field wajib belum dipenuhi.
- [x] Field laporan yang tidak memengaruhi similarity tidak mengurangi similarity score.

### Snapshot laporan

Endpoint snapshot/finalisasi baru hanya dibuat jika laporan harus immutable dan auditable. Endpoint ini bukan endpoint pencarian kedua. Tanggung jawabnya:

- menerima kandidat yang sudah dipilih reviewer;
- memvalidasi report profile;
- memastikan `record_version` belum berubah;
- menyimpan salinan field yang benar-benar dipakai laporan;
- menyimpan method version, score, coverage, user, dan waktu pemilihan;
- menolak finalisasi jika field wajib hilang;
- mempertahankan laporan lama walaupun master pembanding kemudian diedit.

Jika laporan tidak memerlukan audit trail atau persistence, endpoint existing detail sudah cukup dan endpoint snapshot tidak perlu dibuat.

## Reviewer Feedback

Hampir 2.000 data production adalah candidate corpus, bukan ground truth ranking. Sistem harus mulai merekam:

- request atau subject anonim;
- kandidat yang ditampilkan dan urutannya;
- method version;
- kandidat yang dibuka;
- kandidat yang dipilih;
- kandidat yang ditolak;
- alasan penolakan/override;
- kandidat manual yang dicari di luar hasil API;
- waktu keputusan reviewer;
- laporan atau assignment yang terkait.

Data sensitif tidak boleh masuk log analitik tanpa kebutuhan. Gunakan ID dan snapshot teknis yang minimum.

## Phase 0 - Audit Production dan Baseline

Tujuan: memahami apakah hampir 2.000 data benar-benar menyediakan kandidat yang cukup per segmen dan membuat baseline V1 sebelum perubahan.

### Checklist

- [ ] Jalankan seluruh audit secara read-only pada salinan production atau query agregat yang disetujui.
- [ ] Catat jumlah aktif, soft-deleted, dan duplikat.
- [ ] Hitung distribusi per province, regency, district, dan village.
- [ ] Hitung distribusi per jenis objek, peruntukan, jenis listing, dan market basis.
- [ ] Hitung missing rate seluruh atribut scoring dan laporan.
- [ ] Hitung distribusi tanggal data dan umur evidence.
- [ ] Hitung jumlah kandidat per radius 1, 3, 5, 10, dan 25 km.
- [ ] Hitung jumlah kandidat eligible per kombinasi segmen utama.
- [ ] Audit koordinat kosong, invalid, dan duplikat.
- [ ] Audit harga/unit price nol, ekstrem, dan tidak konsisten dengan sale/rent.
- [ ] Audit konsentrasi record pada koordinat, proyek, atau sumber yang sama.
- [ ] Simpan sample output V1 untuk kasus representatif dan kasus batas.
- [ ] Ukur latency query dengan candidate pool 100, 300, 500, dan 1.000.
- [ ] Dokumentasikan distribusi tanpa memasukkan data sensitif ke repository.

### Definition of Done

- [ ] Ada baseline jumlah kandidat dan latency per segmen utama.
- [ ] Diketahui segmen yang cukup data dan segmen yang tetap sparse.
- [ ] Diketahui atribut yang tidak layak diberi bobot karena terlalu sering kosong.
- [ ] Candidate pool awal dipilih berdasarkan data, bukan tebakan.
- [~] Pipeline V1 dapat direplay dan parity `v2_shadow` diuji secara lokal; sample baseline production belum disimpan.

## Phase 1 - Stabilkan V1 dan Tambahkan Observability

Tujuan: menutup defect ranking yang tidak bergantung pada formula V2 dan membuat perilaku existing dapat diukur.

### Checklist

- [~] V2 memisahkan `candidate_pool_limit` dari `result_limit`; V1 sengaja mempertahankan retrieval legacy untuk rollback identik.
- [x] Tambahkan stable tie-breaker.
- [x] Catat retrieval stage dan alasan fallback.
- [x] Hentikan konversi field opsional yang tidak dikirim menjadi nol.
- [x] Buat scoring result terstruktur, bukan hanya float.
- [x] Tambahkan method version untuk V1.
- [ ] Tambahkan component breakdown internal untuk V1.
- [x] Tambahkan warning untuk field yang hilang pada hasil V2.
- [x] Tambahkan telemetry scoring latency, pool size, result size, fallback rate, coverage, dan missing rate.
- [x] Pastikan tidak ada perubahan response breaking pada fase ini.

### Definition of Done

- [x] Pada V2, seluruh candidate pool dinilai sebelum `result_limit` diterapkan.
- [x] Ranking dengan input dan data sama selalu deterministik.
- [x] Missing dan nol menghasilkan status yang berbeda.
- [x] Setiap hasil dapat ditelusuri ke method version dan retrieval stage.
- [x] V1 baseline tetap tersedia untuk rollback.

## Phase 2 - Implementasikan Similarity V2

Tujuan: mengganti penjumlahan poin kasar dengan mixed-attribute similarity yang dapat dijelaskan.

### Checklist

- [x] Buat konfigurasi bobot dan policy yang versioned.
- [x] Implementasikan reference profile teknis per jenis objek; validasi domain tetap diperlukan.
- [x] Implementasikan eligibility pipeline.
- [~] Implementasikan exact/substitution matrix peruntukan; konfigurasi teknis tersedia, persetujuan domain masih diperlukan.
- [~] Implementasikan compatibility matrix jenis objek; konfigurasi teknis tersedia, persetujuan domain masih diperlukan.
- [x] Pisahkan luas tanah dan luas bangunan.
- [x] Ubah lebar jalan menjadi fungsi kontinu.
- [~] Ubah posisi, kondisi, dan legalitas menjadi matrix similarity; implementasi selesai, persetujuan domain masih diperlukan.
- [x] Pertahankan distance decay sebagai fungsi kontinu dan buat parameternya configurable.
- [x] Keluarkan harga dari similarity.
- [x] Hitung reference coverage dan score coverage.
- [x] Kembalikan `insufficient_input` jika informasi acuan terlalu rendah.
- [x] Hasilkan component score, contribution, dan reason.
- [x] Hitung similarity rank dan final rank secara terpisah.
- [x] Pertahankan `score` sebagai alias selama masa kompatibilitas.

### Definition of Done

- [x] Exact peruntukan memperoleh nilai lebih tinggi dari sekadar satu grup.
- [x] Apartemen dan vila tidak dapat memperoleh 100 hanya karena berada dalam grup perumahan.
- [x] Perbedaan lebar jalan 1 cm tidak menyebabkan lompatan lima poin.
- [x] Missing field tidak disamakan dengan nol.
- [x] Payload minim tidak menghasilkan label kuat tanpa warning reference coverage.
- [x] Harga acuan tidak memengaruhi pemilihan kandidat.
- [x] Seluruh komponen score dapat dijelaskan kepada reviewer.

## Phase 3 - Evidence Quality dan Report Readiness

Tujuan: memberi reviewer konteks kualitas bukti dan kesiapan laporan tanpa merusak similarity.

### Checklist

- [x] Pisahkan market basis dari evidence type pada kontrak scoring.
- [x] Tambahkan mapping backward-compatible dari `jenis_listing_id`.
- [x] Hitung recency terhadap reference date.
- [x] Definisikan evidence tier awal.
- [ ] Tambahkan status verifikasi jika sumber datanya tersedia.
- [ ] Definisikan report profile bersama pemilik format laporan.
- [x] Hitung report completeness dan missing fields dengan profile dasar sementara.
- [x] Tambahkan `record_version`.
- [ ] Verifikasi field sensitif hanya tampil untuk permission yang sesuai.
- [ ] Putuskan berdasarkan kebutuhan bisnis apakah snapshot laporan diperlukan.
- [ ] Jika diperlukan, desain penyimpanan snapshot dan lifecycle-nya.

### Definition of Done

- [x] Sale dan rent tidak bercampur ketika V2 aktif.
- [x] Transaction dan offer tidak disamakan sebagai konsep yang identik.
- [x] Reviewer dapat membedakan kandidat mirip tetapi bukti lemah.
- [x] Reviewer dapat melihat kandidat mirip tetapi belum siap laporan.
- [x] Similarity tidak berubah hanya karena field administratif laporan kosong.
- [ ] Keputusan snapshot laporan sudah eksplisit, bukan tersirat.

## Phase 4 - Shadow Mode dan Validasi

Tujuan: membuktikan bahwa V2 lebih berguna daripada V1 sebelum mengubah ranking production.

### Shadow mode

- [x] V1 tetap menjadi response aktif.
- [~] V2 dijalankan sinkron hanya pada request yang lolos sampling tanpa mengubah
  hasil yang dilihat consumer; eksekusi queue/asinkron belum diterapkan.
- [x] Simpan perbedaan kandidat teratas, overlap, rank/score delta, scoring latency, coverage distribution, dan fallback.
- [x] Jangan menyimpan payload sensitif mentah jika tidak diperlukan.
- [ ] Sediakan laporan perbandingan V1 versus V2.

### Validasi offline

- [ ] Replay sample production yang telah dianonimkan.
- [ ] Lakukan leave-one-out dengan menyembunyikan harga subject.
- [ ] Pisahkan train/calibration dan validation berdasarkan waktu serta wilayah.
- [ ] Ukur candidate recall untuk beberapa ukuran pool.
- [ ] Ukur recall@K, MRR, dan NDCG jika reviewer labels tersedia.
- [ ] Ukur acceptance rate dan override rate.
- [ ] Gunakan error estimasi harga hanya sebagai validasi sekunder.
- [ ] Audit hasil per jenis objek, wilayah, market basis, dan coverage band.
- [ ] Review manual kasus rank berubah besar antara V1 dan V2.

### Definition of Done

- [ ] V2 tidak memiliki regresi kritis pada segmen utama.
- [ ] Candidate recall memenuhi target yang disetujui.
- [ ] Latency berada dalam batas operasional.
- [x] Kasus sparse/minimal input memberikan `insufficient_input`, `rankable=false`, dan warning pada pengujian lokal.
- [ ] Domain reviewer menyetujui sample ranking V2.
- [x] Tidak ada threshold kuat/sedang/lemah yang ditetapkan tanpa bukti.

## Phase 5 - Integrasi Reviewer dan Pengumpulan Label

Tujuan: mengubah keputusan reviewer menjadi data yang dapat digunakan untuk memperbaiki metode.

### Checklist

- [ ] Aplikasi reviewer menampilkan similarity, evidence tier, coverage, dan warnings.
- [ ] Aplikasi reviewer tidak menampilkan similarity sebagai probabilitas.
- [ ] Reviewer dapat melihat component breakdown.
- [ ] Reviewer dapat menandai kandidat dipilih atau ditolak.
- [ ] Alasan override menggunakan kategori terstruktur plus catatan opsional.
- [ ] Kandidat manual di luar hasil API dapat direkam.
- [ ] Method version ikut tersimpan bersama keputusan.
- [ ] Report readiness terlihat sebelum kandidat difinalisasi.
- [ ] Dokumentasikan retensi dan akses data feedback.

### Definition of Done

- [ ] Setiap keputusan reviewer dapat dikaitkan dengan ranking dan method version.
- [ ] Sistem mengetahui kandidat yang dipilih, bukan hanya kandidat yang dikirim.
- [ ] Ada cukup feedback lintas segmen sebelum kalibrasi bobot dilakukan.
- [ ] Reviewer tetap dapat melanjutkan pekerjaan ketika scoring status insufficient.

## Phase 6 - Rollout V2

Tujuan: mengganti ranking production secara terkendali dan dapat dibatalkan.

### Checklist

- [x] Sediakan mode `v1`, `v2_shadow`, dan `v2`.
- [ ] Koordinasikan field additive dengan seluruh consumer API.
- [x] `docs/API.md`, schema OpenAPI scoring, dan assertion tipe field telah diperbarui.
- [ ] Jalankan canary pada kelompok reviewer terbatas.
- [ ] Pantau error rate, latency, fallback rate, acceptance, dan override.
- [x] Pertahankan V1 sebagai mode rollback melalui konfigurasi.
- [ ] Naikkan traffic secara bertahap.
- [x] Alias `score` dan behavior legacy masih dipertahankan; belum ada penghapusan breaking.

### Rollback trigger

Rollback dilakukan jika terjadi salah satu kondisi berikut:

- latency atau error rate melewati batas yang disepakati;
- candidate recall turun material;
- sale dan rent tercampur;
- ranking menghasilkan kandidat tidak eligible sebagai primary;
- aplikasi consumer gagal membaca response;
- reviewer override meningkat tajam tanpa penjelasan data.

### Definition of Done

- [ ] V2 menjadi ranking default.
- [x] V1 masih dapat diaktifkan sebagai rollback melalui `PEMBANDING_SCORING_MODE`.
- [ ] Consumer API sudah menggunakan field semantic baru.
- [ ] Monitoring menunjukkan performa dan kualitas stabil.
- [ ] Keputusan rilis dan bukti validasi terdokumentasi.

## Phase 7 - Governance dan Pengembangan Lanjutan

Fase ini dimulai setelah V2 stabil:

- [ ] Kalibrasi bobot menggunakan reviewer feedback.
- [ ] Evaluasi learning-to-rank jika volume label memadai.
- [ ] Tambahkan condition of sale yang terverifikasi.
- [ ] Tambahkan concentration diagnostics.
- [ ] Evaluasi market area formal per segmen.
- [ ] Evaluasi route/network distance untuk segmen yang memerlukannya.
- [ ] Evaluasi development potential dan highest and best use.
- [ ] Evaluasi adjusted price dan confidence hasil nilai.
- [ ] Review method version secara berkala dan dokumentasikan perubahan.

Setiap perubahan bobot atau policy yang memengaruhi ranking harus menghasilkan method version baru. Hasil lama tidak boleh dihitung ulang diam-diam menggunakan konfigurasi terbaru.

## Test Plan

### Unit test scorer

- [x] Properti identik menghasilkan similarity maksimum dengan coverage memadai.
- [x] Exact peruntukan lebih tinggi dari approved substitution.
- [ ] Incompatible peruntukan gagal eligibility.
- [x] Apartemen dan vila tidak memperoleh similarity 100 hanya karena satu grup.
- [ ] Fungsi luas simetris dan kontinu.
- [x] Fungsi lebar jalan tidak memiliki lompatan pada batas diskrit legacy.
- [x] Missing input mengurangi reference coverage, bukan dianggap nol.
- [x] Missing kandidat mengurangi score coverage dan menambah warning.
- [x] Harga input tidak mengubah similarity.
- [ ] Hasil selalu berada pada rentang 0-100.
- [x] Tie-breaker selalu deterministik.

### Feature test API

- [ ] Endpoint by ID dan by payload memakai engine serta method version yang sama.
- [x] Response lama tetap tersedia selama masa transisi dan parity shadow diuji.
- [x] Field scoring V2 memiliki tipe dan nilai yang konsisten pada response feature test.
- [x] Sale dan rent tidak bercampur.
- [x] Candidate pool lebih besar dari result limit.
- [x] Kandidat di luar nearest result limit dapat naik ke hasil akhir.
- [x] Retrieval lintas district mengikuti stage dan policy.
- [x] Fallback mengembalikan alasan, bukan hanya score penalty.
- [ ] Permission endpoint tidak berubah tanpa keputusan.
- [x] Report readiness tidak memengaruhi similarity.

### Database parity

SQLite test saat ini menggunakan pendekatan jarak yang berbeda dari MySQL production. Test geospasial yang memengaruhi radius atau ranking wajib diverifikasi menggunakan rumus yang sama atau integration test MySQL. Test yang lulus dengan Manhattan approximation tidak cukup untuk membuktikan ranking production benar.

### Regression cases wajib

- [x] Lebar jalan 6 m versus 7 m dan 7,01 m.
- [x] Payload hanya berisi field minimum.
- [~] Candidate missing fields telah diuji terhadap candidate lengkap; scenario API end-to-end lengkap versus kosong belum ada.
- [x] Kandidat kuat tepat di luar district.
- [x] Kandidat kuat berada setelah nearest-N awal.
- [~] Gudang dengan substitusi tanah kosong dan area metric telah diuji; tier campuran belum diuji.
- [x] Ruko tanpa kandidat exact.
- [ ] Tanggal data lama tetapi karakteristik sangat mirip.
- [ ] Offer baru versus transaction lama.
- [x] Dua kandidat dengan similarity dan coverage sama menggunakan ID sebagai tie-breaker.

## Monitoring Production

Dashboard minimal harus memantau:

- request count dan error rate;
- p50, p95, dan p99 latency;
- candidate pool size dan result size;
- persentase fallback per retrieval stage;
- reference coverage dan score coverage distribution;
- scoring status distribution;
- evidence tier distribution;
- report readiness distribution;
- rank disagreement V1 versus V2;
- reviewer acceptance dan override rate;
- kandidat manual di luar hasil API;
- distribusi hasil per wilayah dan jenis objek.

Alert harus berfokus pada perubahan distribusi dan regresi, bukan hanya exception aplikasi.

## Risiko dan Pengendalian

| Risiko | Pengendalian |
| --- | --- |
| Dua ribu data ternyata terkonsentrasi pada sedikit wilayah | Audit per segmen dan tampilkan sparse-data warning |
| Bobot terlihat ilmiah tetapi belum terkalibrasi | Method status `heuristic_unvalidated`, shadow mode, dan reviewer feedback |
| Response membesar karena breakdown dan full resource | Ukur payload, gunakan summary/detail atau batch detail bila perlu |
| Consumer lama rusak | Field additive, alias `score`, dan rollout bertahap |
| Jenis listing salah dimigrasikan | Dual-read, mapping audit, dan persetujuan domain |
| Missing data menghasilkan score menyesatkan | Reference coverage, score coverage, dan scoring status |
| District masih memblokir kandidat baik | Staged retrieval dan candidate recall benchmark |
| Harga bocor ke selection | Sembunyikan harga subject pada validasi dan keluarkan dari similarity |
| Laporan berubah setelah data pembanding diedit | Record version dan snapshot final bila laporan harus auditable |
| SQLite dan MySQL memberi urutan berbeda | Integration test geospasial pada engine production |
| Reviewer menganggap score sebagai confidence | Label UI dan dokumentasi semantic yang eksplisit |

## Ownership Keputusan

| Area | Pemilik keputusan |
| --- | --- |
| Matriks peruntukan, jenis objek, legalitas, kondisi | Valuation domain owner |
| Report profile dan field wajib | Pemilik proses laporan |
| Kontrak API dan backward compatibility | Backend/API owner bersama consumer owner |
| UX explanation dan reviewer feedback | Pemilik aplikasi reviewer |
| Query, observability, dan deployment | Backend/operations |
| Validasi statistik dan metrik | Backend/data analyst bersama valuation reviewer |
| Security, PII, dan retensi snapshot | Security/data governance owner |

Codex atau developer tidak boleh mengisi keputusan domain final hanya berdasarkan tebakan teknis.

## Artefak yang Harus Dihasilkan

- [x] Dokumen master implementation plan ini.
- [ ] Laporan audit agregat production.
- [ ] Matriks eligibility dan substitution yang disetujui.
- [ ] Report profile per jenis objek/market basis.
- [ ] Dokumen method version V1 baseline.
- [ ] Dokumen method version V2.
- [x] Update `docs/API.md`.
- [x] OpenAPI/Swagger memuat schema scoring dengan tipe yang benar dan assertion spesifik.
- [~] Unit dan feature test matrix lokal tersedia; integration test parity MySQL production belum tersedia.
- [ ] Laporan shadow mode V1 versus V2.
- [ ] Runbook rollout dan rollback.
- [ ] Keputusan apakah report snapshot endpoint diperlukan.
- [ ] Laporan post-rollout.

## Definition of Done Keseluruhan

Refaktor dianggap selesai ketika:

- [x] Candidate pool dan result limit sudah terpisah pada V2.
- [x] Missing value dan nol tidak lagi disamakan.
- [x] Similarity V2 berjalan dengan method version dan explanation.
- [x] Harga acuan tidak memengaruhi similarity.
- [x] Eligibility, similarity, evidence quality, coverage, dan report readiness terpisah.
- [x] Exact peruntukan berbeda dari approved substitution secara teknis; nilai matriks final tetap memerlukan persetujuan domain.
- [x] Ranking deterministik dan dapat ditelusuri.
- [x] API tetap backward-compatible selama masa transisi.
- [~] API menyediakan explanation/warning; pemahaman reviewer belum diverifikasi pada aplikasi consumer.
- [~] API menyediakan report readiness dan missing fields; tampilannya pada aplikasi reviewer belum diterapkan.
- [ ] Shadow mode menunjukkan V2 tidak lebih buruk dari V1 pada metrik yang disetujui.
- [~] Rollback teknis ke V1 tersedia tanpa migration destruktif; runbook dan pembuktian rollout belum tersedia.
- [~] Dokumentasi API, OpenAPI, dan test telah diperbarui; runbook belum selesai.
- [x] Tidak ada klaim bahwa similarity adalah probability/confidence.

## Urutan Eksekusi yang Disarankan

Pelaksanaan dimulai dari audit production karena keputusan candidate pool, atribut yang layak dibobot, dan segmen prioritas tidak boleh dibuat hanya berdasarkan tujuh data lokal. Setelah baseline tersedia, stabilkan V1 terlebih dahulu dengan memisahkan pool dan result limit, memperbaiki null semantics, serta menambahkan observability. Perubahan ini menutup defect retrieval tanpa sekaligus mengambil risiko formula baru.

Similarity V2 kemudian dibangun di belakang method version baru dan dijalankan dalam shadow mode. Pada tahap ini valuation domain owner harus menyetujui matriks eligibility dan substitution; developer tidak boleh mengganti keputusan domain dengan rank ordinal buatan. Evidence quality dan report readiness ditambahkan setelah engine similarity stabil agar kegagalan dapat dilokalisasi dengan jelas.

Integrasi reviewer dilakukan sebelum kalibrasi bobot. Tanpa keputusan reviewer, hampir 2.000 record hanya menyediakan kandidat, bukan jawaban kandidat mana yang benar-benar paling berguna. Setelah feedback mencukupi dan hasil shadow mode diterima, V2 dirilis bertahap dengan V1 tetap tersedia sebagai rollback.

Snapshot laporan hanya dikerjakan jika proses laporan memang membutuhkan data immutable dan audit trail. Jika laporan hanya membaca detail terkini tanpa kebutuhan persistence, endpoint detail existing sudah cukup dan penambahan endpoint baru merupakan kompleksitas yang tidak memberi nilai.

## Referensi Metodologi

- International Association of Assessing Officers, *Standard on Automated Valuation Models*: https://www.iaao.org/media/standards/Standard_on_Automated_Valuation_Models.pdf
- RICS, *Comparable evidence in real estate valuation*: https://www.rics.org/profession-standards/rics-standards-and-guidance/sector-standards/valuation-standards/comparable-evidence-in-real-estate-valuation
- Kementerian Keuangan, PMK 173/PMK.06/2020: https://www.jdih.kemenkeu.go.id/dok/173-pmk-06-2020/view
- DJKN, *Persyaratan Kompetitif Bagi Data Pembanding Dalam Pendekatan Data Pasar*: https://www.djkn.kemenkeu.go.id/artikel/baca/10808/Persyaratan-Kompetitif-
- Agustin, Soewandi, dan Widjojo, *On the Weights for Characteristics and Comparables for Property Valuation using Quality Rating Valuation Estimation*: https://ced.petra.ac.id/index.php/civ/article/view/25864
