<?php

use App\Http\Controllers\MpesaCallbackController;
use App\Http\Controllers\NotificationCenterController;
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
use App\Http\Controllers\Platform\PlatformMpesaController;
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
use App\Http\Controllers\Tenant\Agrovet\AgrovetController;
use App\Http\Controllers\Tenant\Auth\TenantAuthController;
use App\Http\Controllers\Tenant\Auth\TenantMfaController;
use App\Http\Controllers\Tenant\CashierShiftController;
use App\Http\Controllers\Tenant\CategoryController;
use App\Http\Controllers\Tenant\Fleet\FleetController;
use App\Http\Controllers\Tenant\Garage\GarageController;
use App\Http\Controllers\Tenant\Gym\GymController;
use App\Http\Controllers\Tenant\InventoryController;
use App\Http\Controllers\Tenant\MpesaCredentialController;
use App\Http\Controllers\Tenant\ParcelController;
use App\Http\Controllers\Tenant\PharmacyController;
use App\Http\Controllers\Tenant\PosController;
use App\Http\Controllers\Tenant\Printing\PrintingController;
use App\Http\Controllers\Tenant\ProductController;
use App\Http\Controllers\Tenant\TableController;
use App\Http\Controllers\Tenant\TenantActivityController;
use App\Http\Controllers\Tenant\TenantAnalyticsController;
use App\Http\Controllers\Tenant\TenantAnnouncementController;
use App\Http\Controllers\Tenant\TenantDashboardController;
use App\Http\Controllers\Tenant\TenantLegalDocumentController;
use App\Http\Controllers\Tenant\TenantSubscriptionController;
use App\Http\Controllers\Tenant\TenantSupportCaseController;
use App\Http\Controllers\Tenant\TenantUserController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/platform-console/login');

Route::post('/api/mpesa/callback/{token}', [MpesaCallbackController::class, 'handle'])
    ->middleware('throttle:mpesa-callback')
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('mpesa.callback');

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
            Route::get('/notifications', [NotificationCenterController::class, 'index'])->name('notifications.index');
            Route::post('/notifications/{notification}/read', [NotificationCenterController::class, 'read'])->name('notifications.read');
            Route::post('/notifications/read-all', [NotificationCenterController::class, 'readAll'])->name('notifications.read-all');
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

            Route::prefix('mpesa')->name('mpesa.')->middleware('platform.permission:platform.tenants.view')->group(function (): void {
                Route::get('/', [PlatformMpesaController::class, 'index'])->name('index');
                Route::get('/create', [PlatformMpesaController::class, 'create'])->name('create');
                Route::post('/', [PlatformMpesaController::class, 'store'])->name('store');
                Route::get('/{credential}/edit', [PlatformMpesaController::class, 'edit'])->name('edit');
                Route::put('/{credential}', [PlatformMpesaController::class, 'update'])->name('update');
                Route::delete('/{credential}', [PlatformMpesaController::class, 'destroy'])->name('destroy');
            });

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
            Route::post('/invoices/{invoice}/payments/{payment}/reconcile', [PlatformInvoiceController::class, 'reconcilePendingPayment'])
                ->middleware(['platform.permission:platform.billing.subscriptions.manage', 'platform.step-up'])->name('invoices.payment.reconcile');
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
            Route::get('/templates/create', [PlatformIndustryTemplateController::class, 'create'])
                ->middleware('platform.permission:platform.modules.manage')
                ->name('templates.create');
            Route::post('/templates', [PlatformIndustryTemplateController::class, 'store'])
                ->middleware(['platform.permission:platform.modules.manage', 'platform.step-up'])
                ->name('templates.store');
            Route::get('/templates/{template}', [PlatformIndustryTemplateController::class, 'show'])
                ->middleware('platform.permission:platform.modules.view')
                ->name('templates.show');
            Route::get('/templates/{template}/edit', [PlatformIndustryTemplateController::class, 'edit'])
                ->middleware('platform.permission:platform.modules.manage')
                ->name('templates.edit');
            Route::put('/templates/{template}', [PlatformIndustryTemplateController::class, 'update'])
                ->middleware(['platform.permission:platform.modules.manage', 'platform.step-up'])
                ->name('templates.update');
            Route::delete('/templates/{template}', [PlatformIndustryTemplateController::class, 'destroy'])
                ->middleware(['platform.permission:platform.modules.manage', 'platform.step-up'])
                ->name('templates.destroy');
            Route::post('/templates/{template}/toggle-active', [PlatformIndustryTemplateController::class, 'toggleActive'])
                ->middleware(['platform.permission:platform.modules.manage', 'platform.step-up'])
                ->name('templates.toggle-active');
            Route::get('/templates/{template}/tenants/{tenant}/preview', [PlatformIndustryTemplateController::class, 'preview'])
                ->middleware('platform.permission:platform.modules.view')
                ->name('templates.preview');
            Route::post('/templates/{template}/apply', [PlatformIndustryTemplateController::class, 'applyToTenant'])
                ->middleware(['platform.permission:platform.modules.manage', 'platform.step-up'])
                ->name('templates.apply');
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

// Tenant Authentication
Route::middleware('web')->group(function (): void {
    Route::get('/tenant/login', [TenantAuthController::class, 'create'])->name('tenant.login');
    Route::post('/tenant/login', [TenantAuthController::class, 'store'])->middleware('throttle:10,1')->name('tenant.login.store');
    Route::post('/tenant/logout', [TenantAuthController::class, 'destroy'])->name('tenant.logout');
});

Route::middleware(['web', 'auth'])->prefix('tenant/mfa')->name('tenant.mfa.')->group(function (): void {
    Route::get('/setup', [TenantMfaController::class, 'setup'])->name('setup');
    Route::post('/setup', [TenantMfaController::class, 'confirmSetup'])->middleware('throttle:10,1')->name('setup.confirm');
    Route::get('/challenge', [TenantMfaController::class, 'challenge'])->name('challenge');
    Route::post('/challenge', [TenantMfaController::class, 'verify'])->middleware('throttle:10,1')->name('verify');
});

Route::prefix('tenant')->name('tenant.')->middleware(['web', 'auth', 'tenant.resolve', 'tenant.context-required', 'tenant.mfa', 'tenant.isolate', 'shop.isolate', 'tenant.activity'])->group(function (): void {
    Route::get('/', TenantDashboardController::class)->name('dashboard');
    Route::get('/activity', [TenantActivityController::class, 'index'])->middleware('tenant.permission:users.manage')->name('activity.index');
    Route::get('/notifications', [NotificationCenterController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationCenterController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationCenterController::class, 'readAll'])->name('notifications.read-all');

    Route::get('/announcements', [TenantAnnouncementController::class, 'index'])->name('announcements.index');
    Route::get('/announcements/{announcement}', [TenantAnnouncementController::class, 'show'])->name('announcements.show');

    Route::get('/support/cases', [TenantSupportCaseController::class, 'index'])->name('support.cases.index');
    Route::get('/support/cases/create', [TenantSupportCaseController::class, 'create'])->name('support.cases.create');
    Route::post('/support/cases', [TenantSupportCaseController::class, 'store'])->middleware('throttle:10,1')->name('support.cases.store');
    Route::get('/support/cases/{case}', [TenantSupportCaseController::class, 'show'])->whereNumber('case')->name('support.cases.show');
    Route::post('/support/cases/{case}/replies', [TenantSupportCaseController::class, 'reply'])->middleware('throttle:20,1')->whereNumber('case')->name('support.cases.reply');

    // Legal & Regulatory Compliance (Kenya DPA 2019 & Terms of Service)
    Route::prefix('legal')->name('legal.')->group(function (): void {
        Route::get('/', [TenantLegalDocumentController::class, 'index'])->name('index');
        Route::get('/privacy-policy', [TenantLegalDocumentController::class, 'privacy'])->name('privacy');
        Route::get('/terms-of-service', [TenantLegalDocumentController::class, 'terms'])->name('terms');
        Route::get('/dpa', [TenantLegalDocumentController::class, 'dpa'])->name('dpa');
        Route::get('/{slug}', [TenantLegalDocumentController::class, 'show'])->name('show');
    });

    // Business Analytics & Data Export Center
    Route::prefix('analytics')->name('analytics.')->group(function (): void {
        Route::get('/', [TenantAnalyticsController::class, 'index'])->name('index');
        Route::get('/export', [TenantAnalyticsController::class, 'exportCenter'])->name('export-center');
        Route::post('/export', [TenantAnalyticsController::class, 'export'])->name('export');
    });

    // Staff & Team Management (Owner & Staff Manager)
    Route::resource('users', TenantUserController::class)
        ->middleware('tenant.permission:users.manage')
        ->except(['show', 'destroy']);

    // Catalog - Categories
    Route::resource('categories', CategoryController::class)
        ->middleware('tenant.permission:catalog.categories.manage')
        ->except(['show']);

    // Catalog - Products
    Route::get('/products/lookup', [ProductController::class, 'lookup'])->name('products.lookup');
    Route::resource('products', ProductController::class)
        ->middleware('tenant.permission:catalog.products.manage');

    Route::middleware('module.enabled:mod-pharmacy')->prefix('pharmacy')->name('pharmacy.')->group(function (): void {
        Route::get('/', [PharmacyController::class, 'index'])->middleware('tenant.permission:inventory.view')->name('index');
        Route::get('/batches', [PharmacyController::class, 'batches'])->middleware('tenant.permission:inventory.view')->name('batches.index');
        Route::post('/batches', [PharmacyController::class, 'storeBatch'])->middleware('tenant.permission:inventory.receive')->name('batches.store');
        Route::get('/prescriptions', [PharmacyController::class, 'prescriptions'])->middleware('tenant.permission:inventory.view')->name('prescriptions.index');
        Route::post('/prescriptions', [PharmacyController::class, 'storePrescription'])->middleware('tenant.permission:users.manage')->name('prescriptions.store');
        Route::get('/dispensing', [PharmacyController::class, 'dispensing'])->middleware('tenant.permission:inventory.view')->name('dispensing.index');
    });

    // Point of Sale & Checkout
    Route::get('/pos/checkout', [PosController::class, 'checkout'])->middleware('tenant.permission:pos.sale.create')->name('pos.checkout');
    Route::post('/pos/process', [PosController::class, 'processSale'])->middleware('tenant.permission:pos.sale.create')->name('pos.process');
    Route::post('/pos/mpesa/stk-push', [PosController::class, 'initiateMpesaStkPush'])->middleware(['tenant.permission:pos.sale.create', 'throttle:mpesa-stk'])->name('pos.mpesa.stk-push');
    Route::get('/pos/mpesa/transactions/{transaction}/status', [PosController::class, 'mpesaStkStatus'])->middleware('tenant.permission:pos.sale.create')->name('pos.mpesa.status');
    Route::get('/pos/receipt/{sale}', [PosController::class, 'receipt'])->middleware('tenant.permission:pos.sales.view')->name('pos.receipt');
    Route::get('/pos/sales', [PosController::class, 'sales'])->middleware('tenant.permission:pos.sales.view')->name('pos.sales');
    Route::post('/pos/sales/{sale}/void', [PosController::class, 'voidSale'])->middleware('tenant.permission:pos.sale.void')->name('pos.void');

    // Industry-Specific POS Terminal Operations
    Route::post('/pos/parcels/intake', [PosController::class, 'processParcelIntake'])->middleware('tenant.permission:pos.sale.create')->name('pos.parcels.intake');
    Route::get('/pos/parcels/{parcel}/waybill', [PosController::class, 'waybill'])->middleware('tenant.permission:pos.sales.view')->name('pos.waybill');
    Route::post('/pos/gym/action', [PosController::class, 'processGymAction'])->middleware('tenant.permission:pos.sale.create')->name('pos.gym.action');
    Route::get('/pos/gym/slip/{payment}', [PosController::class, 'gymSlip'])->middleware('tenant.permission:pos.sales.view')->name('pos.gym-slip');
    Route::post('/pos/fleet/dispatch', [PosController::class, 'processFleetDispatch'])->middleware('tenant.permission:pos.sale.create')->name('pos.fleet.dispatch');
    Route::get('/pos/fleet/slip/{booking}', [PosController::class, 'fleetSlip'])->middleware('tenant.permission:pos.sales.view')->name('pos.fleet-slip');
    Route::post('/pos/garage/intake', [PosController::class, 'processGarageIntake'])->middleware('tenant.permission:pos.sale.create')->name('pos.garage.intake');
    Route::get('/pos/garage/slip/{jobCard}', [PosController::class, 'garageSlip'])->middleware('tenant.permission:pos.sales.view')->name('pos.garage-slip');

    // Cashier Shifts
    Route::get('/shifts', [CashierShiftController::class, 'index'])->middleware('tenant.permission:shifts.view')->name('shifts.index');
    Route::get('/shifts/my-shift', [CashierShiftController::class, 'myShift'])->name('shifts.my-shift');
    Route::get('/shifts/create', [CashierShiftController::class, 'create'])->middleware('tenant.permission:shifts.manage')->name('shifts.create');
    Route::post('/shifts', [CashierShiftController::class, 'store'])->middleware('tenant.permission:shifts.manage')->name('shifts.store');
    Route::get('/shifts/{shift}', [CashierShiftController::class, 'show'])->middleware('tenant.permission:shifts.view')->name('shifts.show');
    Route::get('/shifts/{shift}/z-report', [CashierShiftController::class, 'printZReport'])->middleware('tenant.permission:shifts.view')->name('shifts.z-report');
    Route::post('/shifts/{shift}/cash-movement', [CashierShiftController::class, 'cashMovement'])->middleware('tenant.permission:shifts.manage')->name('shifts.cash-movement');
    Route::post('/shifts/{shift}/close', [CashierShiftController::class, 'close'])->middleware('tenant.permission:shifts.manage')->name('shifts.close');

    // Inventory Stock & Movements
    Route::get('/inventory', [InventoryController::class, 'index'])->middleware('tenant.permission:inventory.view')->name('inventory.index');
    Route::get('/inventory/adjust', [InventoryController::class, 'adjustForm'])->middleware('tenant.permission:inventory.adjust')->name('inventory.adjust');
    Route::post('/inventory/adjust', [InventoryController::class, 'adjust'])->middleware('tenant.permission:inventory.adjust')->name('inventory.adjust.store');
    Route::get('/inventory/receive', [InventoryController::class, 'receiveForm'])->middleware('tenant.permission:inventory.receive')->name('inventory.receive');
    Route::post('/inventory/receive', [InventoryController::class, 'receive'])->middleware('tenant.permission:inventory.receive')->name('inventory.receive.store');
    Route::get('/inventory/movements', [InventoryController::class, 'movements'])->middleware('tenant.permission:inventory.view')->name('inventory.movements');

    // Financial Ledgers & Double-Entry Accounting
    Route::prefix('accounting')->name('accounting.')->middleware('tenant.permission:accounting.view')->group(function (): void {
        Route::resource('accounts', ChartOfAccountsController::class)->only(['index', 'create', 'store']);
        Route::resource('journals', JournalEntryController::class)->only(['index', 'create', 'store', 'show']);
        Route::resource('expenses', ExpenseController::class)->only(['index', 'create', 'store']);
        Route::get('/reports/trial-balance', [AccountingReportController::class, 'trialBalance'])->name('reports.trial-balance');
        Route::get('/reports/profit-loss', [AccountingReportController::class, 'profitLoss'])->name('reports.profit-loss');
        Route::get('/reports/balance-sheet', [AccountingReportController::class, 'balanceSheet'])->name('reports.balance-sheet');
        Route::get('/reports/general-ledger', [AccountingReportController::class, 'generalLedger'])->name('reports.general-ledger');
    });

    // Restaurant Dining Tables
    Route::middleware('tenant.industry:restaurant')->group(function (): void {
        Route::get('/tables', [TableController::class, 'index'])->middleware('tenant.permission:pos.sale.create')->name('tables.index');
        Route::post('/tables', [TableController::class, 'store'])->middleware('tenant.permission:users.manage')->name('tables.store');
        Route::patch('/tables/{table}/status', [TableController::class, 'updateStatus'])->middleware('tenant.permission:pos.sale.create')->name('tables.status');
        Route::delete('/tables/{table}', [TableController::class, 'destroy'])->middleware('tenant.permission:users.manage')->name('tables.destroy');
    });

    // M-Pesa Credential Vault
    Route::middleware('tenant.permission:users.manage')->group(function (): void {
        Route::get('/mpesa', [MpesaCredentialController::class, 'index'])->name('mpesa.index');
        Route::get('/mpesa/create', [MpesaCredentialController::class, 'create'])->name('mpesa.create');
        Route::post('/mpesa', [MpesaCredentialController::class, 'store'])->name('mpesa.store');
        Route::get('/mpesa/{mpesaCredential}/edit', [MpesaCredentialController::class, 'edit'])->name('mpesa.edit');
        Route::put('/mpesa/{mpesaCredential}', [MpesaCredentialController::class, 'update'])->name('mpesa.update');
        Route::delete('/mpesa/{mpesaCredential}', [MpesaCredentialController::class, 'destroy'])->name('mpesa.destroy');
        Route::post('/mpesa/{mpesaCredential}/test', [MpesaCredentialController::class, 'testConnection'])->name('mpesa.test')->middleware('throttle:5,1');

        // Self-service Subscription & Billing
        Route::get('/subscription', [TenantSubscriptionController::class, 'index'])->name('subscription.index');
        Route::post('/subscription/pay', [TenantSubscriptionController::class, 'pay'])->name('subscription.pay');
    });

    // Gym & Fitness Studio
    Route::middleware('module.enabled:gym')->prefix('gym')->name('gym.')->group(function (): void {
        Route::get('/', [GymController::class, 'index'])->name('index');
        Route::get('/members', [GymController::class, 'members'])->name('members');
        Route::get('/members/create', [GymController::class, 'createMember'])->name('members.create');
        Route::post('/members', [GymController::class, 'storeMember'])->name('members.store');
        Route::get('/members/{member}/edit', [GymController::class, 'editMember'])->name('members.edit');
        Route::put('/members/{member}', [GymController::class, 'updateMember'])->name('members.update');
        Route::get('/plans', [GymController::class, 'plans'])->name('plans');
        Route::get('/plans/create', [GymController::class, 'createPlan'])->name('plans.create');
        Route::post('/plans', [GymController::class, 'storePlan'])->name('plans.store');
        Route::get('/subscriptions', [GymController::class, 'subscriptions'])->name('subscriptions');
        Route::get('/subscriptions/create', [GymController::class, 'createSubscription'])->name('subscriptions.create');
        Route::post('/subscriptions', [GymController::class, 'storeSubscription'])->name('subscriptions.store');
        Route::post('/subscriptions/{subscription}/freeze', [GymController::class, 'freezeSubscription'])->name('subscriptions.freeze');
        Route::post('/subscriptions/{subscription}/renew', [GymController::class, 'renewSubscription'])->name('subscriptions.renew');
        Route::get('/checkin', [GymController::class, 'checkin'])->name('checkin');
        Route::post('/checkin', [GymController::class, 'processCheckin'])->name('checkin.store');
        Route::get('/classes', [GymController::class, 'classes'])->name('classes');
        Route::get('/classes/create', [GymController::class, 'createClass'])->name('classes.create');
        Route::post('/classes', [GymController::class, 'storeClass'])->name('classes.store');
        Route::post('/classes/book', [GymController::class, 'bookClass'])->name('classes.book');
        Route::post('/bookings/{booking}/attend', [GymController::class, 'markAttendance'])->name('classes.attend');
        Route::get('/trainers', [GymController::class, 'trainers'])->name('trainers');
        Route::get('/trainers/create', [GymController::class, 'createTrainer'])->name('trainers.create');
        Route::post('/trainers', [GymController::class, 'storeTrainer'])->name('trainers.store');
    });

    // Printing & Branding
    Route::middleware('module.enabled:printing')->prefix('printing')->name('printing.')->group(function (): void {
        Route::get('/', [PrintingController::class, 'index'])->name('index');
        Route::get('/jobs', [PrintingController::class, 'jobs'])->name('jobs');
        Route::get('/jobs/create', [PrintingController::class, 'createJob'])->name('jobs.create');
        Route::post('/jobs', [PrintingController::class, 'storeJob'])->name('jobs.store');
        Route::post('/jobs/{job}/quote', [PrintingController::class, 'storeQuote'])->name('jobs.quote');
        Route::post('/jobs/{job}/accept-quote', [PrintingController::class, 'acceptQuote'])->name('jobs.accept-quote');
        Route::post('/jobs/{job}/deposit', [PrintingController::class, 'recordDeposit'])->name('jobs.deposit');
        Route::post('/jobs/{job}/advance', [PrintingController::class, 'advanceStatus'])->name('jobs.advance');
        Route::get('/materials', [PrintingController::class, 'materials'])->name('materials');
        Route::get('/materials/create', [PrintingController::class, 'createMaterial'])->name('materials.create');
        Route::post('/materials', [PrintingController::class, 'storeMaterial'])->name('materials.store');
    });

    // Agrovet Operations
    Route::middleware('module.enabled:agrovet')->prefix('agrovet')->name('agrovet.')->group(function (): void {
        Route::get('/', [AgrovetController::class, 'index'])->name('index');
        Route::get('/products', [AgrovetController::class, 'products'])->name('products');
        Route::get('/products/create', [AgrovetController::class, 'createProduct'])->name('products.create');
        Route::post('/products', [AgrovetController::class, 'storeProduct'])->name('products.store');
        Route::get('/customers', [AgrovetController::class, 'customers'])->name('customers');
        Route::get('/customers/create', [AgrovetController::class, 'createCustomer'])->name('customers.create');
        Route::post('/customers', [AgrovetController::class, 'storeCustomer'])->name('customers.store');
        Route::get('/customers/{customer}/ledger', [AgrovetController::class, 'creditLedger'])->name('credit-ledger');
        Route::post('/credit/repay', [AgrovetController::class, 'repayCredit'])->name('credit.repay');
        Route::get('/purchases', [AgrovetController::class, 'purchases'])->name('purchases');
        Route::get('/purchases/create', [AgrovetController::class, 'createPurchase'])->name('purchases.create');
        Route::post('/purchases', [AgrovetController::class, 'storePurchase'])->name('purchases.store');
        Route::get('/sales', [AgrovetController::class, 'sales'])->name('sales');
        Route::get('/sales/create', [AgrovetController::class, 'createSale'])->name('sales.create');
        Route::post('/sales', [AgrovetController::class, 'storeSale'])->name('sales.store');
    });

    // Auto Garage & Repair Shop
    Route::middleware('module.enabled:garage')->prefix('garage')->name('garage.')->group(function (): void {
        Route::get('/', [GarageController::class, 'index'])->name('index');
        Route::get('/jobs', [GarageController::class, 'jobs'])->name('jobs');
        Route::get('/jobs/create', [GarageController::class, 'createJob'])->name('jobs.create');
        Route::post('/jobs', [GarageController::class, 'storeJob'])->name('jobs.store');
        Route::get('/jobs/{jobCard}', [GarageController::class, 'showJob'])->name('jobs.show');
        Route::post('/jobs/{jobCard}/advance', [GarageController::class, 'advanceStatus'])->name('jobs.advance');
        Route::post('/jobs/{jobCard}/estimate', [GarageController::class, 'storeEstimate'])->name('estimates.store');
        Route::post('/jobs/{jobCard}/estimate/approve', [GarageController::class, 'approveEstimate'])->name('estimates.approve');
        Route::post('/jobs/{jobCard}/consume-part', [GarageController::class, 'consumePart'])->name('parts.consume');
        Route::post('/jobs/{jobCard}/payment', [GarageController::class, 'recordPayment'])->name('payments.store');
        Route::get('/vehicles/{vehicle}/history', [GarageController::class, 'vehicleHistory'])->name('vehicles.history');
        Route::get('/vehicles/create', [GarageController::class, 'createVehicle'])->name('vehicles.create');
        Route::post('/vehicles', [GarageController::class, 'storeVehicle'])->name('vehicles.store');
        Route::get('/customers', [GarageController::class, 'customers'])->name('customers');
        Route::get('/customers/create', [GarageController::class, 'createCustomer'])->name('customers.create');
        Route::post('/customers', [GarageController::class, 'storeCustomer'])->name('customers.store');
        Route::get('/parts', [GarageController::class, 'parts'])->name('parts');
        Route::get('/parts/create', [GarageController::class, 'createPart'])->name('parts.create');
        Route::post('/parts', [GarageController::class, 'storePart'])->name('parts.store');
        Route::get('/mechanics', [GarageController::class, 'mechanics'])->name('mechanics');
        Route::get('/mechanics/create', [GarageController::class, 'createMechanic'])->name('mechanics.create');
        Route::post('/mechanics', [GarageController::class, 'storeMechanic'])->name('mechanics.store');
    });

    // Fleet & Car Hire
    Route::middleware('module.enabled:fleet')->prefix('fleet')->name('fleet.')->group(function (): void {
        Route::get('/', [FleetController::class, 'index'])->name('index');
        Route::get('/vehicles', [FleetController::class, 'vehicles'])->name('vehicles');
        Route::get('/vehicles/create', [FleetController::class, 'createVehicle'])->name('vehicles.create');
        Route::post('/vehicles', [FleetController::class, 'storeVehicle'])->name('vehicles.store');
        Route::get('/bookings', [FleetController::class, 'bookings'])->name('bookings');
        Route::get('/bookings/create', [FleetController::class, 'createBooking'])->name('bookings.create');
        Route::post('/bookings', [FleetController::class, 'storeBooking'])->name('bookings.store');
        Route::post('/bookings/{booking}/payment', [FleetController::class, 'recordPayment'])->name('bookings.payment');
        Route::post('/bookings/{booking}/complete', [FleetController::class, 'completeBooking'])->name('bookings.complete');
        Route::post('/bookings/{booking}/refund-deposit', [FleetController::class, 'refundDeposit'])->name('bookings.refund-deposit');
        Route::post('/bookings/{booking}/inspection', [FleetController::class, 'storeInspection'])->name('bookings.inspection');
        Route::get('/maintenance', [FleetController::class, 'maintenance'])->name('maintenance');
        Route::post('/maintenance', [FleetController::class, 'logMaintenance'])->name('maintenance.store');
        Route::post('/maintenance/{maintenance}/complete', [FleetController::class, 'completeMaintenance'])->name('maintenance.complete');
        Route::get('/customers', [FleetController::class, 'customers'])->name('customers');
        Route::get('/customers/create', [FleetController::class, 'createCustomer'])->name('customers.create');
        Route::post('/customers', [FleetController::class, 'storeCustomer'])->name('customers.store');
        Route::get('/drivers', [FleetController::class, 'drivers'])->name('drivers');
        Route::get('/drivers/create', [FleetController::class, 'createDriver'])->name('drivers.create');
        Route::post('/drivers', [FleetController::class, 'storeDriver'])->name('drivers.store');
    });

    // Parcel intake, office handoffs, and delivery
    Route::middleware('module.enabled:parcel-delivery')->prefix('parcels')->name('parcels.')->group(function (): void {
        Route::get('/', [ParcelController::class, 'index'])->middleware('tenant.permission:parcel.view')->name('index');
        Route::get('/create', [ParcelController::class, 'create'])->middleware('tenant.permission:parcel.receive')->name('create');
        Route::post('/', [ParcelController::class, 'store'])->middleware('tenant.permission:parcel.receive')->name('store');
        Route::get('/{parcel}', [ParcelController::class, 'show'])->middleware('tenant.permission:parcel.view')->name('show');
        Route::post('/{parcel}/dispatch', [ParcelController::class, 'dispatch'])->middleware('tenant.permission:parcel.dispatch')->name('dispatch');
        Route::post('/{parcel}/receive-destination', [ParcelController::class, 'receiveAtDestination'])->middleware('tenant.permission:parcel.receive')->name('receive-destination');
        Route::post('/{parcel}/deliver', [ParcelController::class, 'deliver'])->middleware('tenant.permission:parcel.deliver')->name('deliver');
    });
});
