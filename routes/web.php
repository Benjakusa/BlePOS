<?php

use App\Http\Controllers\Platform\AuthController;
use App\Http\Controllers\Platform\DashboardController;
use App\Http\Controllers\Platform\MfaController;
use App\Http\Controllers\Platform\PlatformAnalyticsController;
use App\Http\Controllers\Platform\PlatformAnnouncementController;
use App\Http\Controllers\Platform\PlatformApprovalController;
use App\Http\Controllers\Platform\PlatformAuditController;
use App\Http\Controllers\Platform\PlatformComplianceController;
use App\Http\Controllers\Platform\PlatformDataProtectionController;
use App\Http\Controllers\Platform\PlatformDunningController;
use App\Http\Controllers\Platform\PlatformIndustryTemplateController;
use App\Http\Controllers\Platform\PlatformInfrastructureController;
use App\Http\Controllers\Platform\PlatformInvoiceController;
use App\Http\Controllers\Platform\PlatformLegalDocumentController;
use App\Http\Controllers\Platform\PlatformModuleController;
use App\Http\Controllers\Platform\PlatformPasswordResetController;
use App\Http\Controllers\Platform\PlatformPlanController;
use App\Http\Controllers\Platform\PlatformProvisioningController;
use App\Http\Controllers\Platform\PlatformRoleController;
use App\Http\Controllers\Platform\PlatformSecurityController;
use App\Http\Controllers\Platform\PlatformSecurityEventController;
use App\Http\Controllers\Platform\PlatformSecurityIncidentController;
use App\Http\Controllers\Platform\PlatformSecurityIpRuleController;
use App\Http\Controllers\Platform\PlatformSecurityKeyController;
use App\Http\Controllers\Platform\PlatformSessionController;
use App\Http\Controllers\Platform\PlatformSubscriptionController;
use App\Http\Controllers\Platform\PlatformSupportAssistController;
use App\Http\Controllers\Platform\PlatformSupportCaseController;
use App\Http\Controllers\Platform\PlatformTenantController;
use App\Http\Controllers\Platform\PlatformTenantModuleController;
use App\Http\Controllers\Platform\PlatformUserController;
use App\Http\Controllers\Platform\StepUpController;
use App\Http\Controllers\Tenant\Accounting\AccountingReportController;
use App\Http\Controllers\Tenant\Accounting\ChartOfAccountsController;
use App\Http\Controllers\Tenant\Accounting\ExpenseController;
use App\Http\Controllers\Tenant\Accounting\JournalEntryController;
use App\Http\Controllers\Tenant\CashierShiftController;
use App\Http\Controllers\Tenant\CategoryController;
use App\Http\Controllers\Tenant\InventoryController;
use App\Http\Controllers\Tenant\PosController;
use App\Http\Controllers\Tenant\ProductController;
use App\Http\Controllers\Tenant\TenantDashboardController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/platform-console/login');

Route::prefix('platform-console')->name('platform.')->middleware(['platform.headers', 'platform.https', 'platform.ip-allowlist'])->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:platform-login')->name('login.store');
    Route::get('/password/forgot', [PlatformPasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/password/forgot', [PlatformPasswordResetController::class, 'sendLink'])->middleware('throttle:platform-password-reset')->name('password.email');
    Route::get('/password/reset/{token}', [PlatformPasswordResetController::class, 'showResetForm'])->middleware('throttle:platform-password-reset')->name('password.reset');
    Route::post('/password/reset', [PlatformPasswordResetController::class, 'reset'])->middleware('throttle:platform-password-reset')->name('password.update');
    Route::get('/invitations/{token}', [PlatformUserController::class, 'showInvitation'])->middleware(['signed', 'throttle:10,1'])->name('invitation.accept');
    Route::post('/invitations/{token}', [PlatformUserController::class, 'acceptInvitation'])->middleware(['signed', 'throttle:10,1'])->name('invitation.accept.store');

    Route::middleware('auth:platform')->group(function (): void {
        Route::get('/password/change-required', [PlatformPasswordResetController::class, 'forceChangeForm'])->name('password.change');
        Route::post('/password/change-required', [PlatformPasswordResetController::class, 'forceChange'])->middleware('throttle:platform-password-reset')->name('password.change.store');
        Route::get('/mfa/setup', [MfaController::class, 'setup'])->middleware('throttle:platform-mfa')->name('mfa.setup');
        Route::post('/mfa/setup', [MfaController::class, 'confirmSetup'])->middleware('throttle:platform-mfa')->name('mfa.setup.confirm');
        Route::get('/mfa/challenge', [MfaController::class, 'challenge'])->middleware('throttle:platform-mfa')->name('mfa.challenge');
        Route::post('/mfa/challenge', [MfaController::class, 'verify'])->middleware('throttle:platform-mfa')->name('mfa.verify');
        Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

        Route::middleware(['platform.mfa', 'platform.session'])->group(function (): void {
            Route::get('/security/step-up', [StepUpController::class, 'create'])->name('step-up');
            Route::post('/security/step-up', [StepUpController::class, 'store'])->middleware('throttle:platform-step-up')->name('step-up.store');
            Route::get('/', DashboardController::class)
                ->middleware('platform.permission:platform.dashboard.view')
                ->name('dashboard');

            // Platform Analytics & Reports (Annex A Part 32)
            Route::get('/analytics', [PlatformAnalyticsController::class, 'index'])
                ->middleware('platform.permission:platform.dashboard.view')
                ->name('analytics.index');
            Route::post('/analytics/export', [PlatformAnalyticsController::class, 'export'])
                ->middleware(['platform.permission:platform.dashboard.view', 'platform.step-up'])
                ->name('analytics.export');

            Route::get('/tenants', [PlatformTenantController::class, 'index'])
                ->middleware('platform.permission:platform.tenants.view')->name('tenants.index');
            Route::get('/tenants/create', [PlatformTenantController::class, 'create'])
                ->middleware('platform.permission:platform.tenants.create')->name('tenants.create');
            Route::post('/tenants', [PlatformTenantController::class, 'store'])
                ->middleware(['platform.permission:platform.tenants.create', 'platform.step-up'])->name('tenants.store');
            Route::get('/tenants/{tenant}', [PlatformTenantController::class, 'show'])
                ->middleware('platform.permission:platform.tenants.view')->name('tenants.show');
            Route::post('/tenants/{tenant}/suspend', [PlatformTenantController::class, 'suspend'])
                ->middleware(['platform.permission:platform.tenants.suspend', 'platform.step-up'])->name('tenants.suspend');
            Route::post('/tenants/{tenant}/reactivate', [PlatformTenantController::class, 'reactivate'])
                ->middleware(['platform.permission:platform.tenants.reactivate', 'platform.step-up'])->name('tenants.reactivate');

            Route::get('/provisioning', [PlatformProvisioningController::class, 'index'])
                ->middleware('platform.permission:platform.provisioning.view')->name('provisioning.index');
            Route::get('/provisioning/{provisioningRun}', [PlatformProvisioningController::class, 'show'])
                ->middleware('platform.permission:platform.provisioning.view')->name('provisioning.show');
            Route::post('/provisioning/{provisioningRun}/retry', [PlatformProvisioningController::class, 'retry'])
                ->middleware(['platform.permission:platform.provisioning.retry', 'platform.step-up'])->name('provisioning.retry');

            // Plans Catalogue
            Route::get('/plans', [PlatformPlanController::class, 'index'])
                ->middleware('platform.permission:platform.billing.view')->name('plans.index');
            Route::get('/plans/create', [PlatformPlanController::class, 'create'])
                ->middleware('platform.permission:platform.billing.plans.manage')->name('plans.create');
            Route::post('/plans', [PlatformPlanController::class, 'store'])
                ->middleware(['platform.permission:platform.billing.plans.manage', 'platform.step-up'])->name('plans.store');
            Route::get('/plans/{plan}/edit', [PlatformPlanController::class, 'edit'])
                ->middleware('platform.permission:platform.billing.plans.manage')->name('plans.edit');
            Route::put('/plans/{plan}', [PlatformPlanController::class, 'update'])
                ->middleware(['platform.permission:platform.billing.plans.manage', 'platform.step-up'])->name('plans.update');
            Route::post('/plans/{plan}/toggle', [PlatformPlanController::class, 'toggleStatus'])
                ->middleware(['platform.permission:platform.billing.plans.manage', 'platform.step-up'])->name('plans.toggle');

            // Tenant Subscription Assignment & Lifecycle
            Route::post('/tenants/{tenant}/subscription', [PlatformSubscriptionController::class, 'assign'])
                ->middleware(['platform.permission:platform.billing.subscriptions.manage', 'platform.step-up'])->name('tenants.subscription.assign');
            Route::post('/tenants/{tenant}/subscription/{subscription}/transition', [PlatformSubscriptionController::class, 'transition'])
                ->middleware(['platform.permission:platform.billing.subscriptions.manage', 'platform.step-up'])->name('tenants.subscription.transition');

            // Platform Invoices & Billing
            Route::get('/invoices', [PlatformInvoiceController::class, 'index'])
                ->middleware('platform.permission:platform.billing.view')->name('invoices.index');
            Route::get('/invoices/create', [PlatformInvoiceController::class, 'create'])
                ->middleware('platform.permission:platform.billing.subscriptions.manage')->name('invoices.create');
            Route::post('/invoices', [PlatformInvoiceController::class, 'store'])
                ->middleware(['platform.permission:platform.billing.subscriptions.manage', 'platform.step-up'])->name('invoices.store');
            Route::get('/invoices/{invoice}', [PlatformInvoiceController::class, 'show'])
                ->middleware('platform.permission:platform.billing.view')->name('invoices.show');
            Route::post('/invoices/{invoice}/payments', [PlatformInvoiceController::class, 'recordPayment'])
                ->middleware(['platform.permission:platform.billing.subscriptions.manage', 'platform.step-up'])->name('invoices.payment');
            Route::post('/invoices/{invoice}/payments/{payment}/refund', [PlatformInvoiceController::class, 'recordRefund'])
                ->middleware(['platform.permission:platform.billing.subscriptions.manage', 'platform.step-up'])->name('invoices.refund');
            Route::post('/invoices/{invoice}/void', [PlatformInvoiceController::class, 'void'])
                ->middleware(['platform.permission:platform.billing.subscriptions.manage', 'platform.step-up'])->name('invoices.void');

            // Platform Dunning Engine
            Route::get('/dunning', [PlatformDunningController::class, 'index'])
                ->middleware('platform.permission:platform.billing.view')->name('dunning.index');
            Route::post('/dunning/trigger', [PlatformDunningController::class, 'trigger'])
                ->middleware(['platform.permission:platform.billing.subscriptions.manage', 'platform.step-up'])->name('dunning.trigger');

            Route::get('/security/users', [PlatformUserController::class, 'index'])
                ->middleware('platform.permission:platform.security.users.view')->name('users.index');
            Route::post('/security/users/invitations', [PlatformUserController::class, 'invite'])
                ->middleware(['platform.permission:platform.security.users.invite', 'platform.step-up'])->name('users.invite');
            Route::post('/security/users/invitations/{platformInvitation}/resend', [PlatformUserController::class, 'resendInvitation'])
                ->middleware(['platform.permission:platform.security.users.invite', 'platform.step-up'])->name('users.invitations.resend');
            Route::delete('/security/users/invitations/{platformInvitation}', [PlatformUserController::class, 'cancelInvitation'])
                ->middleware(['platform.permission:platform.security.users.invite', 'platform.step-up'])->name('users.invitations.cancel');
            Route::patch('/security/users/{platformUser}/status', [PlatformUserController::class, 'updateStatus'])
                ->middleware(['platform.permission:platform.security.users.status', 'platform.step-up'])->name('users.status');
            Route::post('/security/users/{platformUser}/mfa-reset', [PlatformUserController::class, 'requestMfaReset'])
                ->middleware(['platform.permission:platform.security.users.mfa-reset.request', 'platform.step-up'])->name('users.mfa-reset');
            Route::post('/security/users/{platformUser}/password-reset', [PlatformUserController::class, 'requirePasswordReset'])
                ->middleware(['platform.permission:platform.security.users.password-reset', 'platform.step-up'])->name('users.password-reset');
            Route::post('/security/users/{platformUser}/roles', [PlatformRoleController::class, 'assign'])
                ->middleware(['platform.permission:platform.security.users.roles.manage', 'platform.step-up'])->name('users.roles.assign');
            Route::delete('/security/users/{platformUser}/roles/{platformRole}', [PlatformRoleController::class, 'remove'])
                ->middleware(['platform.permission:platform.security.users.roles.manage', 'platform.step-up'])->name('users.roles.remove');

            Route::middleware('platform.permission:platform.security.roles.manage')->group(function (): void {
                Route::get('/security/roles', [PlatformRoleController::class, 'index'])->name('roles.index');
                Route::post('/security/roles', [PlatformRoleController::class, 'store'])->middleware('platform.step-up')->name('roles.store');
                Route::put('/security/roles/{platformRole}', [PlatformRoleController::class, 'update'])->middleware('platform.step-up')->name('roles.update');
                Route::delete('/security/roles/{platformRole}', [PlatformRoleController::class, 'destroy'])->middleware('platform.step-up')->name('roles.destroy');
            });

            Route::middleware('platform.permission:platform.security.approvals.review')->group(function (): void {
                Route::get('/security/approvals', [PlatformApprovalController::class, 'index'])->name('approvals.index');
                Route::post('/security/approvals/{platformApproval}/approve', [PlatformApprovalController::class, 'approve'])->middleware('platform.step-up')->name('approvals.approve');
                Route::post('/security/approvals/{platformApproval}/cancel', [PlatformApprovalController::class, 'cancel'])->middleware('platform.step-up')->name('approvals.cancel');
            });

            Route::middleware('platform.permission:platform.security.sessions.revoke')->group(function (): void {
                Route::get('/security/sessions', [PlatformSessionController::class, 'index'])->name('sessions.index');
                Route::delete('/security/sessions/{platformSession}', [PlatformSessionController::class, 'destroy'])->middleware('platform.step-up')->name('sessions.destroy');
                Route::delete('/security/users/{platformUser}/sessions', [PlatformSessionController::class, 'revokeAll'])->middleware('platform.step-up')->name('users.sessions.revoke-all');
            });

            // Security Centre Console
            Route::get('/security', [PlatformSecurityController::class, 'index'])
                ->middleware('platform.permission:platform.security.view')
                ->name('security.index');

            // Security Incidents
            Route::get('/security/incidents', [PlatformSecurityIncidentController::class, 'index'])
                ->middleware('platform.permission:platform.security.view')
                ->name('security.incidents.index');
            Route::get('/security/incidents/create', [PlatformSecurityIncidentController::class, 'create'])
                ->middleware('platform.permission:platform.security.view')
                ->name('security.incidents.create');
            Route::post('/security/incidents', [PlatformSecurityIncidentController::class, 'store'])
                ->middleware(['platform.permission:platform.security.view', 'platform.step-up'])
                ->name('security.incidents.store');
            Route::get('/security/incidents/{incident}', [PlatformSecurityIncidentController::class, 'show'])
                ->middleware('platform.permission:platform.security.view')
                ->name('security.incidents.show');
            Route::post('/security/incidents/{incident}/status', [PlatformSecurityIncidentController::class, 'updateStatus'])
                ->middleware(['platform.permission:platform.security.view', 'platform.step-up'])
                ->name('security.incidents.status');
            Route::post('/security/incidents/{incident}/notes', [PlatformSecurityIncidentController::class, 'addNote'])
                ->middleware(['platform.permission:platform.security.view', 'platform.step-up'])
                ->name('security.incidents.notes');

            // IP & Abuse Rules
            Route::get('/security/ip-rules', [PlatformSecurityIpRuleController::class, 'index'])
                ->middleware('platform.permission:platform.security.view')
                ->name('security.ip-rules.index');
            Route::post('/security/ip-rules', [PlatformSecurityIpRuleController::class, 'store'])
                ->middleware(['platform.permission:platform.security.view', 'platform.step-up'])
                ->name('security.ip-rules.store');
            Route::post('/security/ip-rules/{rule}/toggle', [PlatformSecurityIpRuleController::class, 'toggle'])
                ->middleware(['platform.permission:platform.security.view', 'platform.step-up'])
                ->name('security.ip-rules.toggle');
            Route::delete('/security/ip-rules/{rule}', [PlatformSecurityIpRuleController::class, 'destroy'])
                ->middleware(['platform.permission:platform.security.view', 'platform.step-up'])
                ->name('security.ip-rules.destroy');

            // Cryptographic Key Management
            Route::get('/security/keys', [PlatformSecurityKeyController::class, 'index'])
                ->middleware('platform.permission:platform.security.view')
                ->name('security.keys.index');
            Route::post('/security/keys/{key}/rotate', [PlatformSecurityKeyController::class, 'rotate'])
                ->middleware(['platform.permission:platform.security.view', 'platform.step-up'])
                ->name('security.keys.rotate');

            Route::get('/security/events', [PlatformSecurityEventController::class, 'index'])
                ->middleware('platform.permission:platform.security.view')
                ->name('security.events.index');

            Route::get('/audit/export', [PlatformAuditController::class, 'export'])
                ->middleware('platform.permission:platform.audit.export')
                ->name('audit.export');
            Route::get('/audit', [PlatformAuditController::class, 'index'])
                ->middleware('platform.permission:platform.audit.view')
                ->name('audit.index');

            // Module Catalogue & Entitlements
            Route::get('/modules', [PlatformModuleController::class, 'index'])
                ->middleware('platform.permission:platform.modules.view')
                ->name('modules.index');
            Route::get('/modules/{module}', [PlatformModuleController::class, 'show'])
                ->middleware('platform.permission:platform.modules.view')
                ->name('modules.show');
            Route::post('/tenants/{tenant}/modules/enable', [PlatformTenantModuleController::class, 'enable'])
                ->middleware(['platform.permission:platform.modules.manage', 'platform.step-up'])
                ->name('tenants.modules.enable');
            Route::post('/tenants/{tenant}/modules/{module}/disable', [PlatformTenantModuleController::class, 'disable'])
                ->middleware(['platform.permission:platform.modules.manage', 'platform.step-up'])
                ->name('tenants.modules.disable');

            // Industry Templates
            Route::get('/templates', [PlatformIndustryTemplateController::class, 'index'])
                ->middleware('platform.permission:platform.modules.view')
                ->name('templates.index');
            Route::post('/tenants/{tenant}/templates/apply', [PlatformIndustryTemplateController::class, 'apply'])
                ->middleware(['platform.permission:platform.modules.manage', 'platform.step-up'])
                ->name('tenants.templates.apply');

            // Support Centre & Cases
            Route::get('/support/cases', [PlatformSupportCaseController::class, 'index'])
                ->middleware('platform.permission:platform.support.view')->name('support.cases.index');
            Route::get('/support/cases/create', [PlatformSupportCaseController::class, 'create'])
                ->middleware('platform.permission:platform.support.view')->name('support.cases.create');
            Route::post('/support/cases', [PlatformSupportCaseController::class, 'store'])
                ->middleware(['platform.permission:platform.support.view', 'platform.step-up'])->name('support.cases.store');
            Route::get('/support/cases/{case}', [PlatformSupportCaseController::class, 'show'])
                ->middleware('platform.permission:platform.support.view')->name('support.cases.show');
            Route::post('/support/cases/{case}/assign', [PlatformSupportCaseController::class, 'assign'])
                ->middleware(['platform.permission:platform.support.view', 'platform.step-up'])->name('support.cases.assign');
            Route::post('/support/cases/{case}/status', [PlatformSupportCaseController::class, 'updateStatus'])
                ->middleware(['platform.permission:platform.support.view', 'platform.step-up'])->name('support.cases.status');
            Route::post('/support/cases/{case}/notes', [PlatformSupportCaseController::class, 'addNote'])
                ->middleware(['platform.permission:platform.support.view', 'platform.step-up'])->name('support.cases.notes.store');

            // Non-impersonating Tenant Assist Diagnostics (Annex A 13.2, 13.3)
            Route::get('/support/assist/{tenant}', [PlatformSupportAssistController::class, 'overview'])
                ->middleware('platform.permission:platform.support.view')->name('support.assist.overview');
            Route::post('/support/assist/{tenant}/integrity-check', [PlatformSupportAssistController::class, 'integrityCheck'])
                ->middleware(['platform.permission:platform.support.view', 'platform.step-up'])->name('support.assist.integrity');

            // Announcements & Bulletins
            Route::get('/announcements', [PlatformAnnouncementController::class, 'index'])
                ->middleware('platform.permission:platform.support.view')->name('announcements.index');
            Route::get('/announcements/create', [PlatformAnnouncementController::class, 'create'])
                ->middleware('platform.permission:platform.support.view')->name('announcements.create');
            Route::post('/announcements', [PlatformAnnouncementController::class, 'store'])
                ->middleware(['platform.permission:platform.support.view', 'platform.step-up'])->name('announcements.store');
            Route::post('/announcements/{announcement}/archive', [PlatformAnnouncementController::class, 'archive'])
                ->middleware(['platform.permission:platform.support.view', 'platform.step-up'])->name('announcements.archive');

            // Infrastructure, Probes, Queues, Migrations & Recovery
            Route::get('/infrastructure', [PlatformInfrastructureController::class, 'index'])
                ->middleware('platform.permission:platform.infrastructure.view')->name('infrastructure.index');
            Route::post('/infrastructure/probes', [PlatformInfrastructureController::class, 'runProbes'])
                ->middleware(['platform.permission:platform.infrastructure.view', 'platform.step-up'])->name('infrastructure.probes');
            Route::get('/infrastructure/queues', [PlatformInfrastructureController::class, 'queues'])
                ->middleware('platform.permission:platform.infrastructure.view')->name('infrastructure.queues');
            Route::post('/infrastructure/queues/{id}/retry', [PlatformInfrastructureController::class, 'retryFailedJob'])
                ->middleware(['platform.permission:platform.infrastructure.view', 'platform.step-up'])->name('infrastructure.queues.retry');
            Route::get('/infrastructure/migrations', [PlatformInfrastructureController::class, 'migrations'])
                ->middleware('platform.permission:platform.infrastructure.view')->name('infrastructure.migrations');
            Route::get('/infrastructure/backups', [PlatformInfrastructureController::class, 'backups'])
                ->middleware('platform.permission:platform.infrastructure.view')->name('infrastructure.backups');
            Route::post('/infrastructure/backups/trigger', [PlatformInfrastructureController::class, 'triggerBackup'])
                ->middleware(['platform.permission:platform.infrastructure.view', 'platform.step-up'])->name('infrastructure.backups.trigger');
            Route::get('/infrastructure/restores', [PlatformInfrastructureController::class, 'restoreRequests'])
                ->middleware('platform.permission:platform.infrastructure.view')->name('infrastructure.restores');
            Route::post('/infrastructure/restores', [PlatformInfrastructureController::class, 'storeRestoreRequest'])
                ->middleware(['platform.permission:platform.infrastructure.view', 'platform.step-up'])->name('infrastructure.restores.store');
            Route::post('/infrastructure/restores/{restore}/approve', [PlatformInfrastructureController::class, 'approveRestore'])
                ->middleware(['platform.permission:platform.infrastructure.view', 'platform.step-up'])->name('infrastructure.restores.approve');

            // Compliance & eTIMS Console (Annex A Part 9, 15)
            Route::get('/compliance', [PlatformComplianceController::class, 'index'])
                ->middleware('platform.permission:platform.compliance.view')->name('compliance.index');
            Route::post('/compliance/etims-certifications/{item}', [PlatformComplianceController::class, 'updateCertification'])
                ->middleware(['platform.permission:platform.compliance.view', 'platform.step-up'])->name('compliance.etims-cert.update');

            // Data Protection Requests (DSAR / Kenya DPA 2019)
            Route::get('/compliance/data-protection', [PlatformDataProtectionController::class, 'index'])
                ->middleware('platform.permission:platform.compliance.view')->name('compliance.dpa.index');
            Route::get('/compliance/data-protection/create', [PlatformDataProtectionController::class, 'create'])
                ->middleware('platform.permission:platform.compliance.view')->name('compliance.dpa.create');
            Route::post('/compliance/data-protection', [PlatformDataProtectionController::class, 'store'])
                ->middleware(['platform.permission:platform.compliance.view', 'platform.step-up'])->name('compliance.dpa.store');
            Route::get('/compliance/data-protection/{dpa}', [PlatformDataProtectionController::class, 'show'])
                ->middleware('platform.permission:platform.compliance.view')->name('compliance.dpa.show');
            Route::post('/compliance/data-protection/{dpa}/status', [PlatformDataProtectionController::class, 'updateStatus'])
                ->middleware(['platform.permission:platform.compliance.view', 'platform.step-up'])->name('compliance.dpa.status');

            // Legal & Policy Documents
            Route::get('/compliance/legal', [PlatformLegalDocumentController::class, 'index'])
                ->middleware('platform.permission:platform.compliance.view')->name('compliance.legal.index');
            Route::get('/compliance/legal/create', [PlatformLegalDocumentController::class, 'create'])
                ->middleware('platform.permission:platform.compliance.view')->name('compliance.legal.create');
            Route::post('/compliance/legal', [PlatformLegalDocumentController::class, 'store'])
                ->middleware(['platform.permission:platform.compliance.view', 'platform.step-up'])->name('compliance.legal.store');
            Route::get('/compliance/legal/{legal}', [PlatformLegalDocumentController::class, 'show'])
                ->middleware('platform.permission:platform.compliance.view')->name('compliance.legal.show');
        });
    });
});

Route::prefix('tenant')->name('tenant.')->middleware(['web', 'auth', 'tenant.resolve', 'tenant.isolate', 'shop.isolate'])->group(function (): void {
    Route::get('/', TenantDashboardController::class)->name('dashboard');

    // Catalog - Categories
    Route::resource('categories', CategoryController::class)->except(['show']);

    // Catalog - Products
    Route::get('/products/lookup', [ProductController::class, 'lookup'])->name('products.lookup');
    Route::resource('products', ProductController::class);

    // Point of Sale & Checkout
    Route::get('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
    Route::post('/pos/process', [PosController::class, 'processSale'])->name('pos.process');
    Route::get('/pos/receipt/{sale}', [PosController::class, 'receipt'])->name('pos.receipt');
    Route::get('/pos/sales', [PosController::class, 'sales'])->name('pos.sales');
    Route::post('/pos/sales/{sale}/void', [PosController::class, 'voidSale'])->name('pos.void');

    // Cashier Shifts
    Route::get('/shifts', [CashierShiftController::class, 'index'])->name('shifts.index');
    Route::get('/shifts/create', [CashierShiftController::class, 'create'])->name('shifts.create');
    Route::post('/shifts', [CashierShiftController::class, 'store'])->name('shifts.store');
    Route::get('/shifts/{shift}', [CashierShiftController::class, 'show'])->name('shifts.show');
    Route::post('/shifts/{shift}/cash-movement', [CashierShiftController::class, 'cashMovement'])->name('shifts.cash-movement');
    Route::post('/shifts/{shift}/close', [CashierShiftController::class, 'close'])->name('shifts.close');

    // Inventory Stock & Movements
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/adjust', [InventoryController::class, 'adjustForm'])->name('inventory.adjust');
    Route::post('/inventory/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust.store');
    Route::get('/inventory/receive', [InventoryController::class, 'receiveForm'])->name('inventory.receive');
    Route::post('/inventory/receive', [InventoryController::class, 'receive'])->name('inventory.receive.store');
    Route::get('/inventory/movements', [InventoryController::class, 'movements'])->name('inventory.movements');

    // Financial Ledgers & Double-Entry Accounting
    Route::prefix('accounting')->name('accounting.')->group(function (): void {
        Route::resource('accounts', ChartOfAccountsController::class)->only(['index', 'create', 'store']);
        Route::resource('journals', JournalEntryController::class)->only(['index', 'create', 'store', 'show']);
        Route::resource('expenses', ExpenseController::class)->only(['index', 'create', 'store']);
        Route::get('/reports/trial-balance', [AccountingReportController::class, 'trialBalance'])->name('reports.trial-balance');
        Route::get('/reports/profit-loss', [AccountingReportController::class, 'profitLoss'])->name('reports.profit-loss');
        Route::get('/reports/balance-sheet', [AccountingReportController::class, 'balanceSheet'])->name('reports.balance-sheet');
        Route::get('/reports/general-ledger', [AccountingReportController::class, 'generalLedger'])->name('reports.general-ledger');
    });
});
