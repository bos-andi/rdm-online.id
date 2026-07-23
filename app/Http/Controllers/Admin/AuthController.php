<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(Request $request): View|RedirectResponse
    {
        if (session('admin_authenticated')) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login', [
            'redirect' => $request->query('redirect'),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'redirect' => ['nullable', 'string'],
        ]);

        $adminEmail = config('admin.email');
        $adminPassword = config('admin.password');

        if (
            ! $adminEmail
            || ! $adminPassword
            || ! hash_equals($adminEmail, $credentials['email'])
            || ! hash_equals($adminPassword, $credentials['password'])
        ) {
            return back()
                ->withInput($request->only('email', 'redirect'))
                ->withErrors(['email' => 'Email atau password salah.']);
        }

        $request->session()->regenerate();
        $request->session()->put('admin_authenticated', true);
        $request->session()->put('admin_email', $adminEmail);

        $redirect = $credentials['redirect'] ?? $request->query('redirect');

        if ($redirect && $this->isSafeRedirect($redirect)) {
            return redirect($redirect);
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    private function isSafeRedirect(string $url): bool
    {
        if (! str_starts_with($url, '/')) {
            return false;
        }

        return ! str_starts_with($url, '//');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(['admin_authenticated', 'admin_email']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
