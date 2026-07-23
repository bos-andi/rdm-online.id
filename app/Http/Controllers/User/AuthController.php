<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Services\SubscriptionService;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('user.dashboard');
        }

        return view('user.auth.login');
    }

    public function login(Request $request, SubscriptionService $subscriptions): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Email atau password salah.']);
        }

        $request->session()->regenerate();

        $subscriptions->syncExpiredInstancesForUser($request->user());

        if ($request->user()->must_change_password) {
            $request->session()->forget('password_prompt_dismissed');
        }

        $user = $request->user();
        $hasPaidInstances = $user->rdmInstances()
            ->whereIn('status', ['active', 'expired', 'suspended'])
            ->exists();

        return redirect()->intended(
            ! $hasPaidInstances || $subscriptions->userHasActiveSubscription($user)
                ? route('user.dashboard')
                : route('user.subscription.expired')
        );
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('user.login');
    }
}
