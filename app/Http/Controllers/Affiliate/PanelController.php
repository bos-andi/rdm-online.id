<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Services\AffiliateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PanelController extends Controller
{
    public function __construct(private AffiliateService $affiliates) {}

    public function dashboard(Request $request): View
    {
        $affiliate = $this->affiliate($request);

        if (! $affiliate->isActive()) {
            return view('affiliate.panel.inactive', ['affiliate' => $affiliate]);
        }

        $referrals = $affiliate->referredUsers();

        return view('affiliate.panel.dashboard', [
            'affiliate' => $affiliate,
            'balance' => $this->affiliates->balance($affiliate),
            'rate' => $this->affiliates->rateFor($affiliate),
            'settings' => $this->affiliates->settings(),
            'referralCount' => (clone $referrals)->count(),
            'payingCount' => (clone $referrals)->whereHas('rdmInstances.payments', fn ($q) => $q->where('status', 'paid'))->count(),
            'recentCommissions' => $affiliate->commissions()->latest()->limit(5)->get(),
        ]);
    }

    public function referrals(Request $request): View
    {
        $affiliate = $this->affiliate($request);

        return view('affiliate.panel.referrals', [
            'affiliate' => $affiliate,
            'referrals' => $affiliate->referredUsers()
                ->with(['rdmInstances' => fn ($q) => $q->select('id', 'user_id', 'subdomain', 'status', 'expires_at')])
                ->withSum(['affiliateCommissions as commission_total' => fn ($q) => $q->where('affiliate_id', $affiliate->id)->where('status', '!=', 'cancelled')], 'amount')
                ->latest('referred_at')
                ->paginate(20),
        ]);
    }

    public function commissions(Request $request): View
    {
        $affiliate = $this->affiliate($request);

        return view('affiliate.panel.commissions', [
            'affiliate' => $affiliate,
            'balance' => $this->affiliates->balance($affiliate),
            'commissions' => $affiliate->commissions()->latest()->paginate(20),
        ]);
    }

    public function payouts(Request $request): View
    {
        $affiliate = $this->affiliate($request);

        return view('affiliate.panel.payouts', [
            'affiliate' => $affiliate,
            'balance' => $this->affiliates->balance($affiliate),
            'settings' => $this->affiliates->settings(),
            'hasOpenRequest' => $affiliate->payouts()->where('status', 'requested')->exists(),
            'payouts' => $affiliate->payouts()->latest()->paginate(20),
        ]);
    }

    public function requestPayout(Request $request): RedirectResponse
    {
        try {
            $payout = $this->affiliates->requestPayout($this->affiliate($request));
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['payout' => $e->getMessage()]);
        }

        return back()->with('status', 'Pengajuan pencairan '.$this->affiliates->rupiah($payout->amount).' terkirim. Admin akan memproses transfer ke rekening Anda.');
    }

    public function profile(Request $request): View
    {
        return view('affiliate.panel.profile', ['affiliate' => $this->affiliate($request)]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\s-]{8,30}$/'],
        ], [
            'phone.regex' => 'Nomor WhatsApp tidak valid.',
        ]);

        $this->affiliate($request)->update($data);

        return back()->with('status', 'Data diri tersimpan.');
    }

    public function updateBank(Request $request): RedirectResponse
    {
        $affiliate = $this->affiliate($request);
        $document = ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'];

        $data = $request->validate([
            'bank_name' => ['required', 'string', 'max:100'],
            'bank_account_number' => ['required', 'string', 'max:50', 'regex:/^[0-9\s-]+$/'],
            'bank_account_name' => ['required', 'string', 'max:150'],
            'ktp' => $affiliate->ktp_path ? $document : ['required', ...array_slice($document, 1)],
            'bank_book' => $document,
        ], [
            'bank_account_number.regex' => 'Nomor rekening hanya boleh berisi angka.',
            'ktp.required' => 'Upload foto KTP terlebih dahulu.',
            'ktp.mimes' => 'Foto KTP harus berupa JPG, PNG, WEBP, atau PDF.',
            'ktp.max' => 'Ukuran foto KTP maksimal 8 MB.',
            'bank_book.mimes' => 'Foto buku tabungan harus berupa JPG, PNG, WEBP, atau PDF.',
            'bank_book.max' => 'Ukuran foto buku tabungan maksimal 8 MB.',
        ], [
            'ktp' => 'foto KTP',
            'bank_book' => 'foto buku tabungan',
        ]);

        $bank = [
            'bank_name' => trim($data['bank_name']),
            'bank_account_number' => preg_replace('/[\s-]+/', '', $data['bank_account_number']),
            'bank_account_name' => trim($data['bank_account_name']),
        ];

        $bankChanged = collect($bank)->contains(fn ($value, $key) => (string) $affiliate->{$key} !== $value);
        $files = ['ktp' => $request->file('ktp'), 'bank_book' => $request->file('bank_book')];

        if (($bankChanged || ! $affiliate->bank_book_path) && ! $files['bank_book']) {
            return back()->withInput()->withErrors([
                'bank_book' => $affiliate->bank_book_path
                    ? 'Data rekening berubah. Upload foto buku tabungan untuk rekening yang baru.'
                    : 'Upload foto buku tabungan terlebih dahulu.',
            ]);
        }

        if (! $bankChanged && ! $files['ktp'] && ! $files['bank_book']) {
            return back()->with('status', 'Tidak ada perubahan data rekening.');
        }

        $this->affiliates->submitBankAccount($affiliate, $bank, $files);

        return back()->with('status', 'Data rekening dan dokumen terkirim. Admin akan memverifikasi sebelum Anda bisa mengajukan pencairan.');
    }

    public function document(Request $request, string $type): Response
    {
        return $this->affiliates->documentResponse($this->affiliate($request), $type);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $affiliate = $this->affiliate($request);

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
        ]);

        if (! Hash::check($data['current_password'], $affiliate->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini salah.']);
        }

        $affiliate->update(['password' => $data['password']]);

        return back()->with('status', 'Password berhasil diubah.');
    }

    private function affiliate(Request $request): Affiliate
    {
        return $request->user('affiliate');
    }
}
