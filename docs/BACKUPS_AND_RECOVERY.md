# BlePOS — Backups and Recovery

## Current capability

The platform console can create an on-demand encrypted snapshot of the central database. It records the storage disk, byte count, SHA-256 checksum of the stored encrypted bytes, and verification time. SQLite uses `VACUUM INTO` to create a consistent snapshot. MySQL requires `mysqldump`; PostgreSQL requires `pg_dump`. `BACKUP_DISK` must point to durable off-host object storage in production. Production boot checks reject the local backup disk.

The restore workflow verifies the encrypted artifact checksum and can decrypt a SQLite snapshot into an isolated sandbox, then checks `PRAGMA integrity_check`. It never overwrites production data. The scheduler creates a central encrypted database snapshot daily at 01:00 and expires backup artifacts after `PLATFORM_BACKUP_RETENTION_DAYS` (30 days by default) at 02:00. Expired run metadata and checksums are retained for audit history. The host must run Laravel's scheduler every minute for these tasks to execute. MySQL/PostgreSQL isolated restore targets, tenant-only snapshots, uploaded-file snapshots, and point-in-time recovery are not implemented. A central database snapshot is not proof that tenant-specific recovery is available.

## Operator procedure

1. Configure the application key, an off-host `BACKUP_DISK`, and `PLATFORM_BACKUP_RETENTION_DAYS`; grant the application identity only the required object permissions.
2. Run the Laravel scheduler every minute and confirm `platform:backup` and `platform:backup-prune` appear in the scheduled task list.
3. Trigger a central database snapshot from Platform → Infrastructure → Backups, or run `php artisan platform:backup`.
4. Confirm the run is `verified`, inspect the recorded checksum and destination, and confirm the object exists in the configured storage provider.
5. Request a restore test and obtain a second administrator's approval. The current executable sandbox restore supports SQLite only and records `test_restored`; production recovery is never reported as complete.
6. After a separately managed recovery, run `php artisan platform:audit-verify` and the health probes before routing traffic.

## Production readiness gaps

The application schedules snapshots and applies artifact retention, but no RPO/RTO target or successful production restore drill is claimed until the host scheduler, storage lifecycle, and operators have verified a restore on the selected production database engine. Configure independent monitoring for backup age, failed snapshot jobs, object-storage errors, and restore-drill age. Keep database, application, and queue infrastructure on managed services with tested provider-level point-in-time recovery where required.
