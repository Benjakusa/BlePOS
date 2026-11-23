# BlePOS — Assumptions

The previous assumptions register was absent when this session resumed. No historical entries could be recovered from the empty `.git` directory. Add explicit, dated assumptions here when a requirement cannot yet be verified; do not silently treat them as facts.

## Current unknowns
- Tenant database topology and production infrastructure are unspecified in the current progress ledger; provisioning must not assume a topology until it is deliberately selected and documented.
- Deployment-specific reverse proxy, cache, HTTPS termination, firewall, and HSTS behavior still require environment verification, as recorded in `docs/PROGRESS.md`.
