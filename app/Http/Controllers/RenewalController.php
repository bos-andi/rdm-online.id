<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\PaymentService;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RenewalController extends Controller
{
    public function show(string $subdomain, PaymentService $payments, SubscriptionService $subscriptions): View
    {
        $instance = $payments->instanceForRenewal($subdomain);

        abort_unless($instance, 404, 'RDM tidak ditemukan.');

        $pkg = config('deploy.packages.'.$instance->package);

        return view('renewal.show', [
            'instance' => $instance,
            'pkg' => $pkg,
            'amount' => $payments->packageAmount($instance),
            'statusLabel' => $subscriptions->statusLabel($instance),
            'isExpired' => $subscriptions->isExpired($instance),
            'newExpiresAt' => $subscriptions->expiresAtAfterRenewal($instance),
        ]);
    }

    public function prepareCheckout(Request $request, string $subdomain, PaymentService $payments): RedirectResponse
    {
        $instance = $payments->instanceForRenewal($subdomain);

        abort_unless($instance, 404, 'RDM tidak ditemukan.');

        $data = $request->validate([
            'email' => ['nullable', 'email', 'max:150'],
        ]);

        $payments->storeCheckoutSession(
            $subdomain,
            $data['email'] ?? $instance->pic_email,
        );

        return redirect()->route('renewal.checkout', $subdomain);
    }

    public function checkoutForm(string $subdomain, PaymentService $payments, SubscriptionService $subscriptions): View|RedirectResponse
    {
        $instance = $payments->instanceForRenewal($subdomain);

        abort_unless($instance, 404, 'RDM tidak ditemukan.');

        $session = $payments->checkoutSession($subdomain);

        if (! $session) {
            return redirect()
                ->route('renewal.show', $subdomain)
                ->withErrors(['payment' => 'Sesi checkout kedaluwarsa. Silakan mulai lagi.']);
        }

        $pkg = config('deploy.packages.'.$instance->package);
        $amount = $payments->packageAmount($instance);

        $payments->refreshAvailableChannels();

        $methodGroups = $payments->groupedPaymentMethods();

        if ($methodGroups->isEmpty()) {
            return redirect()
                ->route('renewal.show', $subdomain)
                ->withErrors(['payment' => 'Metode pembayaran belum tersedia. Hubungi administrator.']);
        }

        $viewData = [
            'instance' => $instance,
            'pkg' => $pkg,
            'amount' => $amount,
            'adminFees' => $payments->adminFeesMap(),
            'email' => $session['email'] ?? $instance->pic_email,
            'methodGroups' => $methodGroups,
            'statusLabel' => $subscriptions->statusLabel($instance),
        ];

        if (session('admin_authenticated')) {
            $viewData['instancePayments'] = Payment::query()
                ->where('rdm_instance_id', $instance->id)
                ->where('external_id', 'not like', 'probe-%')
                ->latest()
                ->get();

            return view('admin.billing.checkout', $viewData);
        }

        return view('renewal.checkout', $viewData);
    }

    public function processPayment(Request $request, string $subdomain, PaymentService $payments): RedirectResponse
    {
        $instance = $payments->instanceForRenewal($subdomain);

        abort_unless($instance, 404, 'RDM tidak ditemukan.');

        if (! $payments->checkoutSession($subdomain)) {
            return redirect()
                ->route('renewal.show', $subdomain)
                ->withErrors(['payment' => 'Sesi checkout kedaluwarsa. Silakan mulai lagi.']);
        }

        $methodCodes = array_keys($payments->availablePaymentMethods());

        if ($methodCodes === []) {
            return back()->withErrors(['payment' => 'Tidak ada metode pembayaran aktif. Hubungi administrator.']);
        }

        $data = $request->validate([
            'payment_method' => ['required', 'string', Rule::in($methodCodes)],
            'email' => ['nullable', 'email', 'max:150'],
        ]);

        $email = $data['email'] ?? $instance->pic_email;

        try {
            $payment = $payments->startRenewalCheckout($instance, $email, $data['payment_method']);
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->withErrors(['payment' => $e->getMessage()]);
        }

        session()->forget("renewal_checkout.{$subdomain}");

        if ($payments->isInlinePayment($payment)) {
            return redirect()->route('renewal.pay', [
                'subdomain' => $subdomain,
                'ref' => $payment->external_id,
            ]);
        }

        if (! $payment->invoice_url) {
            return back()->withErrors(['payment' => 'URL pembayaran tidak tersedia.']);
        }

        return redirect()->away($payment->invoice_url);
    }

    public function success(Request $request, string $subdomain, PaymentService $payments): View
    {
        $payment = null;

        if ($ref = $request->query('ref')) {
            $payment = Payment::query()
                ->where('external_id', $ref)
                ->whereHas('rdmInstance', fn ($q) => $q->where('subdomain', $subdomain))
                ->first();

            if ($payment && $payment->status !== 'paid') {
                try {
                    $payments->syncPaymentStatus($payment);
                    $payment->refresh();
                } catch (\Throwable) {
                    // Webhook/sync may retry later; success page stays informative.
                }
            }
        }

        return view('renewal.success', [
            'subdomain' => $subdomain,
            'payment' => $payment,
            'fqdn' => $subdomain.'.'.config('deploy.domain'),
        ]);
    }

    public function pay(Request $request, string $subdomain, PaymentService $payments): View|RedirectResponse
    {
        $ref = $request->query('ref');

        abort_unless($ref, 404);

        $payment = Payment::query()
            ->where('external_id', $ref)
            ->whereHas('rdmInstance', fn ($q) => $q->where('subdomain', $subdomain))
            ->with('rdmInstance')
            ->firstOrFail();

        if (! $payments->isInlinePayment($payment)) {
            return redirect()->route('renewal.show', $subdomain);
        }

        if ($payment->status !== 'paid') {
            try {
                $payments->syncPaymentStatus($payment);
                $payment->refresh();
            } catch (\Throwable) {
                // polling will retry
            }
        }

        if ($payment->status === 'paid') {
            return redirect()->route('renewal.success', [
                'subdomain' => $subdomain,
                'ref' => $ref,
            ]);
        }

        return view('renewal.pay', [
            'instance' => $payment->rdmInstance,
            'payment' => $payment,
            'subdomain' => $subdomain,
            'presentation' => $payments->checkoutPresentation($payment),
        ]);
    }

    public function payStatus(Request $request, string $subdomain, PaymentService $payments): JsonResponse
    {
        $ref = $request->query('ref');

        if (! $ref) {
            return response()->json(['message' => 'Referensi tidak valid.'], 422);
        }

        $payment = Payment::query()
            ->where('external_id', $ref)
            ->whereHas('rdmInstance', fn ($q) => $q->where('subdomain', $subdomain))
            ->first();

        if (! $payment) {
            return response()->json(['message' => 'Pembayaran tidak ditemukan.'], 404);
        }

        if ($payment->status !== 'paid') {
            try {
                $payments->syncPaymentStatus($payment);
                $payment->refresh();
            } catch (\Throwable) {
                // polling will retry
            }
        }

        return response()->json([
            'status' => $payment->status,
            'redirect_url' => $payment->status === 'paid'
                ? route('renewal.success', ['subdomain' => $subdomain, 'ref' => $ref])
                : null,
        ]);
    }

    public function qris(Request $request, string $subdomain, PaymentService $payments): View|RedirectResponse
    {
        return $this->pay($request, $subdomain, $payments);
    }

    public function qrisStatus(Request $request, string $subdomain, PaymentService $payments): JsonResponse
    {
        return $this->payStatus($request, $subdomain, $payments);
    }
}
