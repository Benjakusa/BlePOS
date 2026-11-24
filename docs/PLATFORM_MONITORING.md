# BlePOS — Platform Infrastructure & Health Monitoring

## 1. Overview

BlePOS includes real-time infrastructure and health probe monitoring across all critical subsystems. Monitoring screens query active system state rather than static mock indicators.

## 2. Health Probes & Checks

| Component | Check Performed | Status Criteria |
|---|---|---|
| **Primary Database** | Read/write query execution | Latency < 100ms: OK; > 100ms: Degraded; Failure: Down |
| **Cache Subsystem** | Cache key write, read, and delete | Immediate roundtrip: OK; Error: Degraded |
| **Queue Backlog** | Pending job count in `jobs` table | < 50: Healthy; 50-200: Warning; > 200: Critical |
| **Failed Jobs** | Count of unprocessed records in `failed_jobs` | 0: Clear; > 0: Alert with replay option |
| **Scheduled Tasks** | Timestamp of last recorded scheduler run | Last run < 2 mins: Healthy; > 5 mins: Missed |
| **Disk Storage** | Free space on application and backup volumes | > 20% free: OK; < 10%: Warning; < 5%: Critical |

## 3. Backups & Restore Probes

- Automatic backup routines generate tar/gzip database dumps with SHA-256 integrity checksums.
- Restore verification is tested via automated test routines (`test_backup_sha256_checksum_and_restore_verification`).

## 4. Key Models & Code References

- Controller: `App\Http\Controllers\Platform\MonitoringController`
- Routes: `routes/web.php` (`platform.monitoring.*`)
