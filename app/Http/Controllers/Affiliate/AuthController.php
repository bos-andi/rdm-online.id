<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Services\AffiliateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use InvalidArgumentException;

class AuthController extends Controller
{
    public function program(AffiliateService $affiliates): View
    {
        return view('affiliate.program', [
            'settings' => $affiliates->settings(),
            'prices' => app(\App\Services\PackagePricingService::class)->prices(),
        ]);
    }

    public function showRegister(AffiliateService $affiliates): View|RedirectResponse
    {
        if (Auth::guard('affiliate')->check()) {
            return redirect()->route('affiliate.dashboard');
        }

        return view('affiliate.auth.register', ['settings' => $affiliates->settings()]);
    }

    public function register(Request $request, AffiliateService $affiliates): RedirectResponse
    {
        // Abandoned unverified sign-ups must not lock the real owner out of their email.
        if (is_string($request->input('email'))) {
            Affiliate::query()
                ->where('email', strtolower(trim($request->input('email'))))
                ->whereNull('email_verified_at')
                ->where('created_at', '<', now()->subDay())
                ->delete();
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email:rfc,dns', 'max:150', 'unique:affiliates,email'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\s-]{8,30}$/'],
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
            'agree' => ['accepted'],
        ], [
            'email.email' => 'Alamat email tidak valid atau domainnya tidak bisa menerima email.',
            'email.unique' => 'Email ini sudah terdaftar sebagai affiliate.',
            'phone.regex' => 'Nomor WhatsApp tidak valid.',
            'agree.accepted' => 'Anda perlu menyetujui syarat program affiliate.',
        ]);

        $affiliate = Affiliate::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'phone' => $data['phone'],
            'password' => $data['password'],
            'code' => $affiliates->generateCode(),
            'status' => 'pending',
        ]);

        Auth::guard('affiliate')->login($affiliate);
        $request->session()->regenerate();

        try {
            $affiliates->sendEmailCode($affiliate);
        } catch (InvalidArgumentException $e) {
            return redirect()->route('affiliate.verification')->withErrors(['code' => $e->getMessage()]);
        }

        return redirect()->route('affiliate.verification')
            ->with('status', "Kode verifikasi sudah dikirim ke {$affiliate->email}.");
    }

    public function showVerification(Request $request, AffiliateService $affiliates): View|RedirectResponse
    {
        $affiliate = $request->user('affiliate');

        if ($affiliate->hasVerifiedEmail()) {
            return redirect()->route('affiliate.dashboard');
        }

        if (! $affiliates->hasUsableEmailCode($affiliate) && $affiliates->emailCodeCooldown($affiliate) === 0) {
            try {
                $affiliates->sendEmailCode($affiliate);
                session()->now('status', "Kode verifikasi baru sudah dikirim ke {$affiliate->email}.");
            } catch (InvalidArgumentException $e) {
                session()->now('send_error', $e->getMessage());
            }
        }

        return view('affiliate.auth.verify-email', [
            'affiliate' => $affiliate->fresh(),
            'cooldown' => $affiliates->emailCodeCooldown($affiliate->fresh()),
        ]);
    }

    public function verify(Request $request, AffiliateService $affiliates): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'regex:/^\s*\d{6}\s*$/'],
        ], [
            'code.required' => 'Masukkan 6 digit kode verifikasi.',
            'code.regex' => 'Kode verifikasi berupa 6 digit angka.',
        ]);

        try {
            $affiliates->verifyEmailCode($request->user('affiliate'), trim($data['code']));
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }

        return redirect()->route('affiliate.dashboard')
            ->with('status', 'Email berhasil diverifikasi. Pendaftaran Anda sedang ditinjau admin.');
    }

    public function resendVerification(Request $request, AffiliateService $affiliates): RedirectResponse
    {
        $affiliate = $request->user('affiliate');

        try {
            $affiliates->sendEmailCode($affiliate);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }

        return back()->with('status', "Kode verifikasi baru sudah dikirim ke {$affiliate->email}.");
    }

    public function changeVerificationEmail(Request $request, AffiliateService $affiliates): RedirectResponse
    {
        $affiliate = $request->user('affiliate');

        $data = $request->validate([
            'email' => ['required', 'email:rfc,dns', 'max:150', Rule::unique('affiliates', 'email')->ignore($affiliate->id)],
        ], [
            'email.email' => 'Alamat email tidak valid atau domainnya tidak bisa menerima email.',
            'email.unique' => 'Email ini sudah terdaftar sebagai affiliate.',
        ]);

        try {
            $affiliates->changeUnverifiedEmail($affiliate, $data['email']);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['email' => $e->getMessage()]);
        }

        return back()->with('status', "Email diubah. Kode verifikasi sudah dikirim ke {$affiliate->email}.");
    }

    public function showLogin(): View|RedirectResponse
    {
        if (Auth::guard('affiliate')->check()) {
            return redirect()->route('affiliate.dashboard');
        }

        return view('affiliate.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('affiliate')->attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Email atau password salah.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('affiliate.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('affiliate')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('affiliate.login');
    }
}
