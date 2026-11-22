<?php

use App\Http\Controllers\Platform\AuthController;
use App\Http\Controllers\Platform\DashboardController;
use App\Http\Controllers\Platform\MfaController;
use App\Http\Controllers\Platform\PlatformApprovalController;
use App\Http\Controllers\Platform\PlatformAuditController;
use App\Http\Controllers\Platform\PlatformPasswordResetController;
use App\Http\Controllers\Platform\PlatformRoleController;
use App\Http\Controllers\Platform\PlatformSecurityEventController;
use App\Http\Controllers\Platform\PlatformSessionController;
use App\Http\Controllers\Platform\PlatformUserController;
use App\Http\Controllers\Platform\StepUpController;
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

            Route::get('/security/events', [PlatformSecurityEventController::class, 'index'])
                ->middleware('platform.permission:platform.security.view')
                ->name('security.events.index');

            Route::get('/audit/export', [PlatformAuditController::class, 'export'])
                ->middleware('platform.permission:platform.audit.export')
                ->name('audit.export');
            Route::get('/audit', [PlatformAuditController::class, 'index'])
                ->middleware('platform.permission:platform.audit.view')
                ->name('audit.index');
        });
    });
});
