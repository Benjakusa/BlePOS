# Platform Backup and Recovery Status

The platform supports on-demand and scheduled encrypted central database snapshots, byte-level SHA-256 verification, configurable artifact retention, and an isolated SQLite restore check. A daily snapshot runs at 01:00 and daily artifact pruning runs at 02:00; the default retention is 30 days. Snapshot creation for MySQL and PostgreSQL depends on `mysqldump` and `pg_dump` being installed and usable by the application process. Production requires `BACKUP_DISK` to be an off-host object store and the host scheduler to run every minute.

Restore requests are executed only into an isolated SQLite sandbox and finish with status `test_restored`. The production database is never overwritten by this workflow. MySQL/PostgreSQL sandbox restore execution, tenant-only restore, uploaded-file backup, point-in-time recovery, and periodic restore-drill automation remain unavailable. Do not use the console as a production disaster-recovery procedure until a production-engine restore drill has passed.

The console stores the checksum of encrypted artifact bytes in `platform_backup_runs`. Restore verifies that checksum before decryption, then checks SQLite integrity. See [Backups and Recovery](BACKUPS_AND_RECOVERY.md) for the operator steps and known limits.
