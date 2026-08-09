# Backup dan Restore Sistem

Menu **Backup Sistem** mengelola restore point private berformat `.sbackup`.
Format ini bukan file ZIP/SQL biasa: setiap paket memiliki manifest, checksum
SHA-256, dan signature HMAC. Import dari UI hanya menerima paket yang dibuat oleh
sistem dengan signing key yang sama.

## Kemampuan

- membuat backup database, uploaded files, atau paket lengkap;
- menyimpan katalog private dan metadata pembuat;
- download ulang, verifikasi checksum/signature, dan hapus paket;
- import paket `.sbackup` terverifikasi;
- menampilkan backup `.sql`/`.zip` lama sebagai download-only;
- restore uploaded files melalui staging, safety backup, atomic directory swap,
  rollback saat gagal, serta audit file di luar database;
- membersihkan backup generated yang melewati masa retensi melalui scheduler.

Backup uploads hanya mencakup direktori durable yang diizinkan pada
`system_backup.upload_roots`. Pembuatan backup ditolak bila menemukan dotfile,
tipe file di luar allowlist, executable, symlink, atau path di luar allowlist;
sistem tidak akan menerbitkan paket yang diam-diam kehilangan file.

## Konfigurasi production

```env
SYSTEM_BACKUP_SIGNING_KEY=<random-secret-yang-disimpan-di-secret-manager>
SYSTEM_BACKUP_RESTORE_ENABLED=false
SYSTEM_BACKUP_DATABASE_RESTORE_ENABLED=false
SYSTEM_BACKUP_MAX_PACKAGE_MB=1024
SYSTEM_BACKUP_RETENTION_DAYS=30
MYSQLDUMP_BINARY=mysqldump
MYSQL_BINARY=mysql
```

Gunakan signing key khusus yang stabil dan simpan terpisah dari file backup.
Jika key hilang, paket yang sudah dibuat tidak dapat diverifikasi untuk restore.

Setelah deployment, jalankan seeder `AppAccessPermissionSeeder` agar permission
backup terbaru tersedia dan pastikan scheduler Laravel aktif untuk
`backups:prune`.

## Batas keamanan restore database

Restore database dari UI sengaja fail-closed. Konfigurasi default proyek memakai
database yang sama untuk session, cache, dan queue. Menimpa database aktif dapat
menghapus lock/job/audit yang sedang mengendalikan restore dan menghidupkan
kembali session atau job lama.

Aktivasi restore database production memerlukan runner dan lock non-database
(misalnya Redis), credential restore khusus, integration test MySQL, purge
session/cache/job/token setelah restore, health check, serta prosedur cutover dan
rollback operator. File `.sql` mentah tidak pernah diterima dari UI.

## Prosedur restore uploads

1. Import atau pilih paket yang sudah terverifikasi.
2. Pastikan operator memiliki role `super_admin` dan permission
   `restore_uploads_backup`.
3. Aktifkan `SYSTEM_BACKUP_RESTORE_ENABLED=true`.
4. Masukkan password aktif dan frasa konfirmasi yang ditampilkan.
5. Sistem membuat safety backup, memvalidasi ZIP, mengekstrak secara streaming
   ke staging, lalu mengganti direktori durable secara atomik per direktori.
6. Jika swap gagal, direktori sebelumnya dikembalikan dan aplikasi dikeluarkan
   dari proses restore. Jika rollback filesystem juga gagal, workspace recovery
   tidak dihapus dan lokasinya dicatat di audit untuk pemulihan operator.

Audit restore disimpan di `storage/app/backups/audit/operations.jsonl`, sehingga
tidak ikut hilang bila database berubah.
