<?php

namespace App\Http\Controllers\Tenant\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TenantAuthController extends Controller
{
    /**
     * Display the tenant login form.
     */
    public function create(): View
    {
        return view('tenant.auth.login');
    }

    /**
     * Handle an incoming authentication request for a tenant user.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $email = mb_strtolower($validated['email']);
        $user = User::query()->where('email', $email)->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => 'Your account is inactive. Please contact your administrator.',
            ]);
        }

        // Verify tenant account is active
        $tenant = Tenant::query()->find($user->tenant_id);
        if (! $tenant || ! $tenant->isActive()) {
            throw ValidationException::withMessages([
                'email' => 'This business account is inactive or suspended.',
            ]);
        }

        Auth::guard('web')->login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        // If user is assigned to a shop, pre-set session active_shop_id
        $primaryShop = $user->shops()->wherePivot('is_primary', true)->first()
            ?? $user->shops()->first();

        if ($primaryShop) {
            $request->session()->put('active_shop_id', $primaryShop->id);
        }

        return redirect()->intended(route('tenant.dashboard'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('tenant.login');
    }
}
