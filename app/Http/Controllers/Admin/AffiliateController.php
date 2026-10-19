<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\AffiliatePayout;
use App\Services\AffiliateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AffiliateController extends Controller
{
    public function __construct(private AffiliateService $affiliates) {}

    public function index(Request $request): View
    {
        $status = $request->query('status');
        $verification = $request->query('verifikasi') === 'pending' ? 'pending' : null;

        return view('admin.affiliate.index', [
            'affiliates' => Affiliate::query()
                ->when(array_key_exists((string) $status, Affiliate::STATUS_LABELS), fn ($q) => $q->where('status', $status))
                ->when($verification, fn ($q) => $q->where('bank_verification_status', 'pending'))
                ->withCount('referredUsers')
                ->withSum(['commissions as earned_total' => fn ($q) => $q->where('status', '!=', 'cancelled')], 'amount')
                ->withSum(['commissions as unpaid_total' => fn ($q) => $q->whereIn('status', ['available', 'requested'])], 'amount')
                ->orderByRaw("FIELD(status, 'pending', 'active', 'suspended', 'rejected')")
                ->latest()
                ->paginate(25)
                ->withQueryString(),
            'status' => $status,
            'verification' => $verification,
            'pendingVerifications' => Affiliate::query()->where('bank_verification_status', 'pending')->count(),
            'counts' => Affiliate::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
            'pendingPayouts' => AffiliatePayout::query()->where('status', 'requested')->count(),
            'totals' => [
                'unpaid' => (int) AffiliateCommission::query()->whereIn('status', ['available', 'requested'])->sum('amount'),
                'paid' => (int) AffiliateCommission::query()->where('status', 'paid')->sum('amount'),
            ],
            'settings' => $this->affiliates->settings(),
        ]);
    }

    public function show(Affiliate $affiliate): View
    {
        return view('admin.affiliate.show', [
            'affiliate' => $affiliate,
            'balance' => $this->affiliates->balance($affiliate),
            'rate' => $this->affiliates->rateFor($affiliate),
            'settings' => $this->affiliates->settings(),
            'referrals' => $affiliate->referredUsers()->withCount('rdmInstances')->latest('referred_at')->limit(50)->get(),
            'commissions' => $affiliate->commissions()->latest()->limit(50)->get(),
            'payouts' => $affiliate->payouts()->latest()->limit(20)->get(),
        ]);
    }

    public function update(Request $request, Affiliate $affiliate): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9]+$/', Rule::unique('affiliates', 'code')->ignore($affiliate->id)],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ], [
            'code.regex' => 'Kode hanya boleh huruf dan angka.',
            'code.unique' => 'Kode sudah dipakai affiliate lain.',
        ]);

        $affiliate->update([
            'code' => strtoupper($data['code']),
            'commission_rate' => $data['commission_rate'] === null || $data['commission_rate'] === '' ? null : $data['commission_rate'],
            'admin_note' => $data['admin_note'] ?? null,
        ]);

        return back()->with('status', 'Data affiliate tersimpan.');
    }

    public function setStatus(Request $request, Affiliate $affiliate): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'rejected', 'suspended'])],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $previous = $affiliate->status;

        if ($data['status'] === 'active' && ! $affiliate->hasVerifiedEmail()) {
            return back()->withErrors(['status' => 'Affiliate belum memverifikasi emailnya, jadi belum bisa disetujui.']);
        }

        $affiliate->update([
            'status' => $data['status'],
            'approved_at' => $data['status'] === 'active' ? ($affiliate->approved_at ?? now()) : $affiliate->approved_at,
        ]);

        if ($data['status'] === 'active' && $previous !== 'active') {
            $this->affiliates->notify(
                $affiliate->email,
                'Selamat, akun affiliate RDM Online Anda aktif',
                'Akun Affiliate Disetujui',
                [
                    'Halo '.$affiliate->name.',',
                    'Pendaftaran Anda sebagai affiliate RDM Online sudah **disetujui**.',
                    "Kode referral Anda: **{$affiliate->code}**",
                    'Link referral: '.$affiliate->referralUrl(),
                    'Bagikan link tersebut ke madrasah. Setiap pembayaran dari madrasah yang mendaftar lewat link atau kode Anda akan menghasilkan komisi **'
                        .$this->affiliates->formatRate($this->affiliates->rateFor($affiliate)).'**.',
                ],
                route('affiliate.dashboard'),
                'Buka Panel Affiliate',
            );
        }

        if ($data['status'] === 'rejected' && $previous === 'pending') {
            $this->affiliates->notify(
                $affiliate->email,
                'Pendaftaran affiliate RDM Online',
                'Pendaftaran Affiliate Belum Disetujui',
                array_filter([
                    'Halo '.$affiliate->name.',',
                    'Terima kasih atas minat Anda. Saat ini pendaftaran affiliate Anda belum dapat kami setujui.',
                    filled($data['reason'] ?? null) ? 'Catatan: '.$data['reason'] : null,
                    'Silakan hubungi '.config('company.email').' jika ada pertanyaan.',
                ]),
            );
        }

        $labels = ['active' => 'diaktifkan', 'rejected' => 'ditolak', 'suspended' => 'dinonaktifkan'];

        return back()->with('status', 'Affiliate '.$affiliate->name.' '.$labels[$data['status']].'.');
    }

    public function resetPassword(Affiliate $affiliate): RedirectResponse
    {
        $password = Str::password(10, symbols: false);
        $affiliate->update(['password' => $password]);

        return back()->with('status', "Password baru untuk {$affiliate->email}: {$password} — sampaikan ke affiliate, password ini tidak ditampilkan lagi.");
    }

    public function verifyBank(Affiliate $affiliate): RedirectResponse
    {
        try {
            $this->affiliates->verifyBankAccount($affiliate);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['bank' => $e->getMessage()]);
        }

        return back()->with('status', 'Rekening '.$affiliate->name.' terverifikasi.');
    }

    public function rejectBank(Request $request, Affiliate $affiliate): RedirectResponse
    {
        $data = $request->validate([
            'note' => ['required', 'string', 'max:500'],
        ], [
            'note.required' => 'Isi alasan penolakan agar affiliate tahu apa yang perlu diperbaiki.',
        ]);

        $this->affiliates->rejectBankAccount($affiliate, $data['note']);

        return back()->with('status', 'Verifikasi rekening '.$affiliate->name.' ditolak.');
    }

    public function document(Affiliate $affiliate, string $type): \Symfony\Component\HttpFoundation\Response
    {
        return $this->affiliates->documentResponse($affiliate, $type);
    }

    public function cancelCommission(Request $request, AffiliateCommission $commission): RedirectResponse
    {
        if ($commission->status !== 'available') {
            return back()->withErrors(['commission' => 'Hanya komisi berstatus Siap Dicairkan yang bisa dibatalkan.']);
        }

        $commission->update(['status' => 'cancelled']);

        return back()->with('status', 'Komisi dibatalkan.');
    }

    public function payouts(Request $request): View
    {
        $status = $request->query('status', 'requested');

        return view('admin.affiliate.payouts', [
            'status' => $status,
            'payouts' => AffiliatePayout::query()
                ->with('affiliate')
                ->when($status !== 'all', fn ($q) => $q->where('status', $status))
                ->latest()
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function payPayout(Request $request, AffiliatePayout $payout): RedirectResponse
    {
        $data = $request->validate([
            'transfer_reference' => ['nullable', 'string', 'max:150'],
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->affiliates->markPayoutPaid($payout, $data['transfer_reference'] ?? null, $data['admin_note'] ?? null);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['payout' => $e->getMessage()]);
        }

        return back()->with('status', 'Pencairan '.$this->affiliates->rupiah($payout->amount).' ke '.$payout->affiliate->name.' ditandai sudah ditransfer.');
    }

    public function rejectPayout(Request $request, AffiliatePayout $payout): RedirectResponse
    {
        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->affiliates->rejectPayout($payout, $data['admin_note'] ?? null);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['payout' => $e->getMessage()]);
        }

        return back()->with('status', 'Pengajuan pencairan ditolak, saldo dikembalikan ke affiliate.');
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'default_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'min_payout' => ['required', 'integer', 'min:0'],
            'cookie_days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        $this->affiliates->updateSettings((float) $data['default_rate'], (int) $data['min_payout'], (int) $data['cookie_days']);

        return back()->with('status', 'Pengaturan affiliate tersimpan.');
    }
}
