# BlePOS — Backups, Disaster Recovery & Business Continuity

## 1. Objectives & Metrics

- **RPO (Recovery Point Objective)**: Maximum 15 minutes of transactional data loss.
- **RTO (Recovery Time Objective)**:
  - Complete Platform Infrastructure Recovery: Under 4 hours.
  - Individual Tenant Database Restore: Under 1 hour.

---

## 2. Backup Architecture

### 2.1 Scope
1. **Central Platform Database**: User accounts, tenant registry, provisioning history, plans, billing records, and cryptographic audit log.
2. **Tenant Databases**: Operational transactions, sales ledger, stock movements, customers, and general ledger.
3. **Application State & Secrets**: Encrypted environment configuration, eTIMS certificates, and key rotation history.
4. **Tenant Assets**: Uploaded receipts, invoices, product images, and compliance documents.

### 2.2 Schedule & Retention
- **Continuous / High Frequency**: Transaction write-ahead logs (WAL) streamed continuously.
- **Daily Snapshots**: Automated full backup executed daily during off-peak hours (02:00 EAT), encrypted with AES-256 before upload to secondary object storage.
- **Retention Tiers**:
  - Daily backups retained for 30 days.
  - Monthly snapshots retained for 12 months.
  - End-of-year financial archive snapshots retained for 7 years (statutory tax compliance).

---

## 3. Restoration & Verification Protocol

### 3.1 Verification & Synthetic Restores
A backup is not considered complete merely because a dump file was generated. BlePOS mandates regular automated restore testing:
- Backups are restored into an isolated sandbox environment.
- Table row counts, schema migrations, and audit chain cryptographic integrity are systematically verified.
- Restoration metrics are recorded to `backup_runs` and visible in the platform console.

### 3.2 Tenant Restore Workflow
Restoring a tenant's database to a previous point in time is an audited, safeguarded workflow:
1. Operator submits a `TenantRestoreRequest` specifying target tenant, point-in-time timestamp, and justification.
2. Step-up authentication + Two-person approval (Security Officer + Operations Administrator).
3. The restore is executed into an isolated staging schema and validated against consistency checks.
4. Upon confirmation, traffic is routed to the recovered database and the operation is recorded in the platform audit log.
