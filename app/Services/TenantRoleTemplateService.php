<?php

namespace App\Services;

use App\Models\IndustryTemplate;
use App\Models\Tenant;
use App\Models\TenantPermission;
use App\Models\TenantRole;
use Illuminate\Support\Str;

/** Creates business-specific role templates without provisioning employee accounts. */
class TenantRoleTemplateService
{
    /** @var array<string, array{group: string, description: string}> */
    private const PERMISSIONS = [
        'pos.sale.create' => ['group' => 'Sales / POS', 'description' => 'Create and process POS sales.'],
        'pos.sales.view' => ['group' => 'Sales / POS', 'description' => 'View sales history and receipts.'],
        'pos.sale.void' => ['group' => 'Sales / POS', 'description' => 'Void completed sales.'],
        'shifts.view' => ['group' => 'Cash Management', 'description' => 'View cashier shifts.'],
        'shifts.manage' => ['group' => 'Cash Management', 'description' => 'Open, reconcile, and close cashier shifts.'],
        'catalog.products.manage' => ['group' => 'Catalog', 'description' => 'Create and edit products.'],
        'catalog.categories.manage' => ['group' => 'Catalog', 'description' => 'Create and edit product categories.'],
        'inventory.view' => ['group' => 'Inventory', 'description' => 'View stock levels and movements.'],
        'inventory.receive' => ['group' => 'Inventory', 'description' => 'Receive stock into a location.'],
        'inventory.adjust' => ['group' => 'Inventory', 'description' => 'Adjust stock quantities.'],
        'accounting.view' => ['group' => 'Finance', 'description' => 'View accounting ledgers and reports.'],
        'reports.sales.view' => ['group' => 'Reports', 'description' => 'View business sales and operational analytics.'],
        'users.manage' => ['group' => 'Administration', 'description' => 'Create staff and assign roles.'],
        'shops.all.manage' => ['group' => 'Administration', 'description' => 'Manage all business branches.'],
        'parcel.view' => ['group' => 'Parcels', 'description' => 'View parcel records.'],
        'parcel.receive' => ['group' => 'Parcels', 'description' => 'Receive parcels.'],
        'parcel.dispatch' => ['group' => 'Parcels', 'description' => 'Dispatch parcels.'],
        'parcel.deliver' => ['group' => 'Parcels', 'description' => 'Record parcel delivery.'],
        'gym.member.manage' => ['group' => 'Gym', 'description' => 'Manage gym members.'],
        'gym.view' => ['group' => 'Gym', 'description' => 'Open the gym operations workspace.'],
        'gym.subscription.manage' => ['group' => 'Gym', 'description' => 'Manage member subscriptions and plans.'],
        'gym.class.manage' => ['group' => 'Gym', 'description' => 'Manage classes and class bookings.'],
        'gym.checkin.manage' => ['group' => 'Gym', 'description' => 'Manage member check-ins.'],
        'gym.trainer.manage' => ['group' => 'Gym', 'description' => 'Manage trainers.'],
        'print.job.create' => ['group' => 'Printing', 'description' => 'Create and progress print jobs.'],
        'printing.view' => ['group' => 'Printing', 'description' => 'Open the printing operations workspace.'],
        'print.job.approve' => ['group' => 'Printing', 'description' => 'Approve or advance print jobs.'],
        'print.quote.manage' => ['group' => 'Printing', 'description' => 'Create and manage quotes.'],
        'print.material.manage' => ['group' => 'Printing', 'description' => 'Manage print materials.'],
        'print.payment.record' => ['group' => 'Printing', 'description' => 'Record job deposits and payments.'],
        'agrovet.product.manage' => ['group' => 'Agrovet', 'description' => 'Manage agrovet products.'],
        'agrovet.view' => ['group' => 'Agrovet', 'description' => 'Open the agrovet operations workspace.'],
        'agrovet.customer.manage' => ['group' => 'Agrovet', 'description' => 'Manage agrovet customers.'],
        'agrovet.sales.manage' => ['group' => 'Agrovet', 'description' => 'Create and view agrovet sales.'],
        'agrovet.credit.grant' => ['group' => 'Agrovet', 'description' => 'Manage credit ledgers and repayments.'],
        'agrovet.credit.approve' => ['group' => 'Agrovet', 'description' => 'Approve credit operations.'],
        'agrovet.purchase.manage' => ['group' => 'Agrovet', 'description' => 'Manage supplier purchases.'],
        'agrovet.report.view' => ['group' => 'Agrovet', 'description' => 'View agrovet operational reports.'],
        'garage.job.manage' => ['group' => 'Garage', 'description' => 'Create and manage repair jobs.'],
        'garage.view' => ['group' => 'Garage', 'description' => 'Open the garage operations workspace.'],
        'garage.estimate.approve' => ['group' => 'Garage', 'description' => 'Create and approve estimates.'],
        'garage.part.manage' => ['group' => 'Garage', 'description' => 'Manage parts and part consumption.'],
        'garage.payment.record' => ['group' => 'Garage', 'description' => 'Record repair payments.'],
        'garage.mechanic.manage' => ['group' => 'Garage', 'description' => 'Manage mechanics.'],
        'garage.vehicle.manage' => ['group' => 'Garage', 'description' => 'Manage customer vehicles.'],
        'garage.customer.manage' => ['group' => 'Garage', 'description' => 'Manage garage customers.'],
        'fleet.vehicle.manage' => ['group' => 'Fleet', 'description' => 'Manage fleet vehicles.'],
        'fleet.view' => ['group' => 'Fleet', 'description' => 'Open the fleet operations workspace.'],
        'fleet.booking.manage' => ['group' => 'Fleet', 'description' => 'Manage bookings and customers.'],
        'fleet.inspection.manage' => ['group' => 'Fleet', 'description' => 'Record vehicle inspections.'],
        'fleet.payment.manage' => ['group' => 'Fleet', 'description' => 'Record and refund booking payments.'],
        'fleet.maintenance.manage' => ['group' => 'Fleet', 'description' => 'Manage vehicle maintenance.'],
    ];

    /**
     * @return array<int, TenantRole>
     */
    public function createTemplateRoles(Tenant $tenant): array
    {
        $template = IndustryTemplate::query()->where('slug', $tenant->industry)->first();
        $roleNames = collect($template?->default_roles ?? ['Cashier'])->filter(fn ($name) => is_string($name) && trim($name) !== '');
        $roleNames->prepend('Owner');

        $roles = [];
        foreach ($roleNames->unique(fn (string $name) => Str::slug($name))->values() as $name) {
            $slug = $this->roleSlug($name);
            $roles[] = TenantRole::query()->firstOrCreate(
                ['tenant_id' => $tenant->id, 'slug' => $slug],
                [
                    'name' => $slug === 'owner' ? 'Business Owner' : trim($name),
                    'description' => $this->roleDescription($name, $template?->name),
                    'is_system' => $slug === 'owner',
                ],
            );
        }

        return $roles;
    }

    /** Ensure permissions exist and apply only recognized, backend-enforced grants. */
    public function assignTemplatePermissions(Tenant $tenant): void
    {
        $this->ensurePermissionCatalog();

        $permissionIds = TenantPermission::query()->pluck('id', 'name');
        $roles = TenantRole::query()->where('tenant_id', $tenant->id)->get();

        foreach ($roles as $role) {
            if ($role->slug === 'owner') {
                $role->permissions()->sync($permissionIds->values()->all());
                continue;
            }

            $this->applyDefaultPermissions($role, $tenant, $permissionIds);
        }
    }

    /** Add defaults only to newly-created templates during a safe existing-tenant upgrade. */
    public function assignDefaultsToNewRoles(Tenant $tenant, array $roles): void
    {
        $this->ensurePermissionCatalog();
        $permissionIds = TenantPermission::query()->pluck('id', 'name');

        foreach ($roles as $role) {
            if ($role instanceof TenantRole && $role->slug !== 'owner') {
                $this->applyDefaultPermissions($role, $tenant, $permissionIds);
            }
        }
    }

    public function ensurePermissionCatalog(): void
    {
        foreach (self::PERMISSIONS as $name => $definition) {
            TenantPermission::query()->firstOrCreate(
                ['name' => $name],
                ['group' => $definition['group'], 'description' => $definition['description']],
            );
        }
    }

    /** @return array<string, array{group: string, description: string}> */
    public function permissionDefinitions(): array
    {
        return self::PERMISSIONS;
    }

    /** @return list<string>|null Null keeps an existing customized role unchanged. */
    private function permissionsForRole(TenantRole $role, ?string $industry): ?array
    {
        $slug = $role->slug;

        if (str_contains($slug, 'cashier')) {
            return ['pos.sale.create', 'pos.sales.view', 'shifts.view', 'shifts.manage'];
        }

        if (str_contains($slug, 'pharmacist')) {
            return ['pos.sale.create', 'pos.sales.view', 'shifts.view', 'shifts.manage', 'catalog.products.manage', 'inventory.view', 'inventory.receive'];
        }

        if (in_array($industry, ['gym-fitness', 'gym'], true)) {
            if (str_contains($slug, 'front-desk')) return ['gym.view', 'gym.member.manage', 'gym.subscription.manage', 'gym.checkin.manage'];
            if (str_contains($slug, 'trainer')) return ['gym.view', 'gym.class.manage', 'gym.trainer.manage'];
            if (str_contains($slug, 'gym-manager')) return ['gym.view', 'gym.member.manage', 'gym.subscription.manage', 'gym.class.manage', 'gym.checkin.manage', 'gym.trainer.manage', 'reports.sales.view'];
        }

        if (in_array($industry, ['printing-branding', 'printing'], true)) {
            if (str_contains($slug, 'designer')) return ['printing.view', 'print.job.create', 'print.quote.manage'];
            if (str_contains($slug, 'production-operator')) return ['printing.view', 'print.job.create', 'print.material.manage'];
            if (str_contains($slug, 'print-shop-manager')) return ['printing.view', 'print.job.create', 'print.job.approve', 'print.quote.manage', 'print.material.manage', 'print.payment.record', 'reports.sales.view'];
        }

        if ($industry === 'agrovet') {
            if (str_contains($slug, 'agrovet-attendant')) return ['agrovet.view', 'agrovet.product.manage', 'agrovet.customer.manage', 'agrovet.sales.manage'];
            if (str_contains($slug, 'credit-officer')) return ['agrovet.view', 'agrovet.customer.manage', 'agrovet.credit.grant', 'agrovet.report.view'];
            if (str_contains($slug, 'branch-manager')) return ['agrovet.view', 'agrovet.product.manage', 'agrovet.customer.manage', 'agrovet.sales.manage', 'agrovet.credit.grant', 'agrovet.credit.approve', 'agrovet.purchase.manage', 'agrovet.report.view', 'reports.sales.view'];
        }

        if (in_array($industry, ['auto-garage', 'garage'], true)) {
            if (str_contains($slug, 'service-advisor')) return ['garage.view', 'garage.job.manage', 'garage.estimate.approve', 'garage.payment.record', 'garage.customer.manage'];
            if (str_contains($slug, 'lead-mechanic')) return ['garage.view', 'garage.job.manage', 'garage.part.manage', 'garage.mechanic.manage', 'garage.vehicle.manage'];
            if (str_contains($slug, 'parts-clerk')) return ['garage.view', 'garage.part.manage'];
        }

        if (in_array($industry, ['car-hire-fleet', 'fleet'], true)) {
            if (str_contains($slug, 'fleet-coordinator')) return ['fleet.view', 'fleet.vehicle.manage', 'fleet.booking.manage', 'fleet.inspection.manage', 'fleet.payment.manage', 'fleet.maintenance.manage', 'reports.sales.view'];
            if (str_contains($slug, 'inspector')) return ['fleet.view', 'fleet.inspection.manage'];
            if (str_contains($slug, 'dispatcher')) return ['fleet.view', 'fleet.booking.manage'];
        }

        if (preg_match('/inventory|stock|storekeeper|warehouse|receiving/', $slug)) {
            return ['inventory.view', 'inventory.receive', 'inventory.adjust', 'catalog.products.manage'];
        }

        if (preg_match('/manager|supervisor|coordinator/', $slug)) {
            return ['pos.sale.create', 'pos.sales.view', 'shifts.view', 'shifts.manage', 'inventory.view', 'inventory.receive', 'reports.sales.view'];
        }

        if (str_contains($slug, 'accountant')) {
            return ['accounting.view', 'pos.sales.view'];
        }

        if (preg_match('/dispatcher|courier|counter-clerk/', $slug)) {
            return ['parcel.view', 'parcel.receive', 'parcel.dispatch', 'parcel.deliver'];
        }

        if (str_contains($slug, 'sales-representative')) {
            return ['pos.sales.view'];
        }

        return null;
    }

    /** @param \Illuminate\Support\Collection<string, int> $permissionIds */
    private function applyDefaultPermissions(TenantRole $role, Tenant $tenant, $permissionIds): void
    {
        $grants = $this->permissionsForRole($role, $tenant->industry);
        if ($grants !== null) {
            $role->permissions()->sync(collect($grants)->map(fn (string $name) => $permissionIds->get($name))->filter()->values()->all());
        }
    }

    private function roleSlug(string $name): string
    {
        if (Str::lower(trim($name)) === 'owner') {
            return 'owner';
        }

        if (Str::contains(Str::lower($name), 'cashier')) {
            return 'cashier';
        }

        return Str::slug($name);
    }

    private function roleDescription(string $name, ?string $businessType): string
    {
        if (Str::lower(trim($name)) === 'owner') {
            return 'Business ownership and administration. This account is not created as an employee.';
        }

        $slug = Str::slug($name);
        $responsibility = match (true) {
            str_contains($slug, 'cashier') => 'Processes permitted sales and manages assigned cashier shifts.',
            str_contains($slug, 'inventory'), str_contains($slug, 'stock-controller'), str_contains($slug, 'storekeeper'), str_contains($slug, 'warehouse') => 'Monitors stock and performs authorized receiving and adjustments.',
            str_contains($slug, 'pharmacist') => 'Handles pharmacy dispensing and stock operations within the configured pharmacy controls.',
            str_contains($slug, 'waiter') => 'Supports table service and customer orders where those workflows are enabled.',
            str_contains($slug, 'chef'), str_contains($slug, 'kitchen') => 'Handles kitchen preparation workflows where enabled.',
            str_contains($slug, 'front-desk'), str_contains($slug, 'receptionist') => 'Supports customer intake and assigned front-office workflows.',
            str_contains($slug, 'trainer') => 'Manages assigned gym classes and trainer records.',
            str_contains($slug, 'inspector') => 'Records inspections for assigned vehicles.',
            str_contains($slug, 'dispatcher') => 'Coordinates parcel or fleet dispatch workflows where enabled.',
            str_contains($slug, 'mechanic') => 'Manages assigned repair jobs and authorized parts operations.',
            str_contains($slug, 'accountant') => 'Views and maintains authorized financial records.',
            str_contains($slug, 'manager'), str_contains($slug, 'supervisor'), str_contains($slug, 'coordinator') => 'Coordinates authorized branch operations and reviews assigned business metrics.',
            default => 'Performs assigned '.$name.' duties only through explicitly granted permissions.',
        };

        return $responsibility.' Review the permission list before assigning employees.';
    }
}
