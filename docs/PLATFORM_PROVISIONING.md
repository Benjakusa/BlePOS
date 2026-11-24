# BlePOS — Platform Provisioning Engine

## 1. Provisioning Workflow

The tenant provisioning engine automates the onboarding of new organizations through a deterministic, idempotent, multi-step pipeline.

## 2. Pipeline Execution Steps

1. **Tenant Record Initialization**: Create `Tenant` record with status `provisioning`.
2. **Domain & Subdomain Allocation**: Validate unique subdomain, register primary routing entry.
3. **Database / Schema Allocation**: Verify database connectivity and schema readiness.
4. **Tenant Configuration Seeding**: Set currency (default `KES`), locale (`en_KE`), fiscal settings.
5. **Default Shop / Branch Creation**: Create headquarters / main branch record.
6. **Register / Terminal Setup**: Initialize primary POS register and cash drawer.
7. **Warehouse / Storage Location**: Initialize primary inventory warehouse.
8. **Owner User Creation & Invitation**: Provision initial administrative identity.
9. **Role & Permission Setup**: Seed tenant-level standard roles (`owner`, `manager`, `cashier`, `accountant`).
10. **Industry Blueprint Application**: Apply selected industry template (retail, pharmacy, restaurant, etc.).
11. **Module Entitlements Provisioning**: Enable licensed modules according to chosen plan.
12. **Double-Entry Chart of Accounts**: Seed standard Kenyan chart of accounts.
13. **Health & Readiness Verification**: Verify shop, register, and user associations.
14. **Transition to Active**: Update status to `active` and send activation credentials/welcome link.

## 3. Idempotency & Failure Recovery

- Each run is tracked via `ProvisioningRun` and individual `ProvisioningStep` records.
- Completed steps are never re-executed on retry.
- Injected failures transition the run to `failed` without leaving partial orphan state.
- Step-by-step progress and sanitised error messages are visible in the Platform Console.

## 4. Key Models & Code References

- Service: `App\Services\ProvisioningEngine`
- Models: `App\Models\ProvisioningRun`, `App\Models\ProvisioningStep`
- Controller: `App\Http\Controllers\Platform\TenantController@store`
