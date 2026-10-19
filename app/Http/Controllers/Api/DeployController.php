<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RdmInstance;
use App\Models\User;
use App\Services\AffiliateService;
use App\Services\DeployPrepareService;
use App\Services\DeployService;
use App\Services\DeployStatusService;
use App\Services\PaymentService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class DeployController extends Controller
{
    public function prepare(
        Request $request,
        DeployPrepareService $prepareService,
    ): JsonResponse {
        $user = $request->user();
        $isGuest = ! $user;

        $rules = [
            'subdomain' => ['required', 'string', 'max:30'],
            'madrasah_name' => ['required', 'string', 'max:150'],
            'jenjang' => ['required', 'in:RA,MI,MTs,MA'],
            'package' => ['required', 'in:monthly,yearly,biennial'],
        ];

        if ($isGuest) {
            $rules['pic_name'] = ['required', 'string', 'max:150'];
            $rules['email'] = ['required', 'email', 'max:150', Rule::unique('users', 'email')];
            $rules['password'] = ['required', 'string', Password::min(8), 'confirmed'];
            $rules['referral_code'] = ['nullable', 'string', 'max:30'];
        }

        $data = $request->validate($rules);

        if ($isGuest && filled($data['referral_code'] ?? null) && ! app(AffiliateService::class)->findActiveByCode($data['referral_code'])) {
            return response()->json(['message' => 'Kode referral tidak ditemukan. Periksa kembali atau kosongkan kolomnya.'], 422);
        }

        try {
            $result = $prepareService->prepare($data, $user, $isGuest);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $instance = $result['instance'];

        return response()->json([
            'message' => 'Akun dan RDM disiapkan. Silakan pilih metode pembayaran.',
            'instance_id' => $instance->id,
            'subdomain' => $instance->subdomain,
            'fqdn' => $instance->fqdn,
            'package' => $instance->package,
            'account_created' => $result['account_created'],
            'instance_created' => $result['instance_created'],
            'dashboard_url' => route('user.dashboard'),
            'resume_url' => route('deploy', ['resume' => $instance->id]),
        ], 201);
    }

    public function checkout(
        Request $request,
        PaymentService $payments,
    ): JsonResponse {
        if (! $payments->gatewayConfigured()) {
            return response()->json([
                'message' => 'Pembayaran via '.config('payment.label').' sedang dalam proses aktivasi. Akun & data Anda sudah tersimpan — silakan lanjutkan pembayaran nanti dari dashboard, atau hubungi '.config('company.email').'.',
            ], 503);
        }

        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Login diperlukan. Selesaikan langkah sebelumnya terlebih dahulu.'], 401);
        }

        $methodCodes = array_keys($payments->availablePaymentMethods());

        if ($methodCodes === []) {
            return response()->json(['message' => 'Tidak ada metode pembayaran aktif.'], 422);
        }

        $data = $request->validate([
            'instance_id' => ['required', 'integer'],
            'payment_method' => ['required', 'string', Rule::in($methodCodes)],
        ]);

        $instance = RdmInstance::query()
            ->where('id', $data['instance_id'])
            ->where('user_id', $user->id)
            ->first();

        if (! $instance) {
            return response()->json(['message' => 'RDM tidak ditemukan.'], 404);
        }

        if ($instance->status !== 'pending_payment') {
            return response()->json(['message' => 'RDM ini tidak menunggu pembayaran.'], 422);
        }

        if ($instance->pending_payment_expires_at && $instance->pending_payment_expires_at->isPast()) {
            return response()->json([
                'message' => 'Batas waktu pembayaran habis. Mulai deploy baru dari awal.',
            ], 422);
        }

        $payment = $payments->findReusableDeployCheckout($instance, $data['payment_method']);

        if (! $payment) {
            try {
                $payment = $payments->startDeployCheckout($instance, $user->email, $data['payment_method']);
            } catch (\Throwable $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        }

        return $this->checkoutResponse($payment, $instance, $payments);
    }

    private function checkoutResponse(
        \App\Models\Payment $payment,
        RdmInstance $instance,
        PaymentService $payments,
    ): JsonResponse {
        $presentation = $payments->checkoutPresentation($payment);

        if (($presentation['payment_mode'] ?? null) === 'redirect' && empty($presentation['invoice_url'])) {
            return response()->json([
                'message' => 'Gagal menyiapkan pembayaran. Silakan coba lagi.',
            ], 422);
        }

        if (($presentation['payment_mode'] ?? null) === 'qris_inline' && empty($presentation['qr_string'])) {
            return response()->json([
                'message' => 'Gagal membuat kode QRIS. Silakan coba lagi atau pilih metode lain.',
            ], 422);
        }

        if (($presentation['payment_mode'] ?? null) === 'va_inline' && empty($presentation['va_number'])) {
            return response()->json([
                'message' => 'Gagal membuat Virtual Account. Silakan coba lagi atau pilih metode lain.',
            ], 422);
        }

        return response()->json(array_merge([
            'external_id' => $payment->external_id,
            'instance_id' => $instance->id,
            'amount' => $payment->amount,
            'dashboard_url' => route('user.dashboard'),
        ], $presentation), 201);
    }

    public function paymentStatus(
        Request $request,
        string $externalId,
        PaymentService $payments,
    ): JsonResponse {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Login diperlukan.'], 401);
        }

        $payment = $payments->paymentForUser($externalId, $user->id);

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

        $instance = $payment->rdmInstance;

        return response()->json([
            'status' => $payment->status,
            'instance_id' => $instance?->id,
            'instance_status' => $instance?->status,
            'fqdn' => $instance?->fqdn,
            'amount' => $payment->meta['pay_amount'] ?? $payment->amount,
            'payment_label' => $payment->channelLabel(),
            'checkout_url' => $payment->status === 'pending' ? $payment->invoice_url : null,
        ]);
    }

    public function status(Request $request, RdmInstance $instance, DeployStatusService $statusService): JsonResponse
    {
        if ($instance->user_id !== $request->user()?->id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        return response()->json($statusService->payload($instance));
    }

    public function retry(Request $request, RdmInstance $instance, DeployService $deployService): JsonResponse
    {
        if ($instance->user_id !== $request->user()?->id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        try {
            $deployService->retryDeploy($instance);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Deploy dijalankan ulang.',
            'instance_id' => $instance->id,
        ]);
    }
}
