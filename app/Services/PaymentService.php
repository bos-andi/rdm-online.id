<?php

namespace App\Services;

use App\Jobs\DeployRdmJob;
use App\Models\Payment;
use App\Models\RdmInstance;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PaymentService
{
    public function __construct(
        private XenditService $xendit,
        private SubscriptionService $subscriptions,
    ) {}

    public function instanceForRenewal(string $subdomain): ?RdmInstance
    {
        $instance = RdmInstance::query()
            ->where('subdomain', $subdomain)
            ->whereIn('status', ['active', 'expired', 'suspended'])
            ->first();

        if (! $instance) {
            return null;
        }

        if ($instance->status === 'active' && ! $this->subscriptions->isExpired($instance)) {
            return null;
        }

        return $instance;
    }

    public function packageAmount(RdmInstance $instance): int
    {
        return $this->packageAmountFor($instance->package);
    }

    public function packageAmountFor(string $package): int
    {
        return (int) (config('deploy.packages.'.$package.'.price') ?? 0);
    }

    public function adminFeeForMethod(string $methodCode): int
    {
        $method = config('xendit.methods.'.$methodCode);

        return (int) ($method['admin_fee'] ?? config('xendit.default_admin_fee', 0));
    }

    /** @return array<string, int> */
    public function adminFeesMap(): array
    {
        return collect($this->availablePaymentMethods())
            ->mapWithKeys(fn (array $method, string $code) => [
                $code => $this->adminFeeForMethod($code),
            ])
            ->all();
    }

    /** @return array{package_amount: int, admin_fee: int, total: int} */
    public function checkoutBreakdownForPackage(string $package, string $methodCode): array
    {
        $packageAmount = $this->packageAmountFor($package);
        $adminFee = $this->adminFeeForMethod($methodCode);

        return [
            'package_amount' => $packageAmount,
            'admin_fee' => $adminFee,
            'total' => $packageAmount + $adminFee,
        ];
    }

    /** @return array{package_amount: int, admin_fee: int, total: int} */
    public function checkoutBreakdown(RdmInstance $instance, string $methodCode): array
    {
        return $this->checkoutBreakdownForPackage($instance->package, $methodCode);
    }

    public function availablePaymentMethods(): array
    {
        return collect(config('xendit.methods', []))
            ->filter(fn ($method, $code) => $this->xendit->isInternalMethodAvailable($code))
            ->all();
    }

    public function groupedPaymentMethods(): Collection
    {
        return collect($this->availablePaymentMethods())
            ->map(fn (array $method, string $code) => array_merge($method, ['code' => $code]))
            ->groupBy('group')
            ->filter(fn (Collection $methods) => $methods->isNotEmpty());
    }

    public function xenditChannelsForMethod(string $methodCode): array
    {
        if (! $this->xendit->isInternalMethodAvailable($methodCode)) {
            throw new InvalidArgumentException('Metode pembayaran tidak tersedia.');
        }

        $method = config('xendit.methods.'.$methodCode);

        if (! $method || empty($method['xendit'])) {
            throw new InvalidArgumentException('Metode pembayaran tidak valid.');
        }

        return $method['xendit'];
    }

    public function storeCheckoutSession(string $subdomain, ?string $email): void
    {
        session([
            "renewal_checkout.{$subdomain}" => [
                'email' => $email,
                'stored_at' => now()->timestamp,
            ],
        ]);
    }

    public function checkoutSession(string $subdomain): ?array
    {
        $session = session("renewal_checkout.{$subdomain}");

        if (! is_array($session)) {
            return null;
        }

        $maxAge = 3600;
        if (($session['stored_at'] ?? 0) < now()->subSeconds($maxAge)->timestamp) {
            session()->forget("renewal_checkout.{$subdomain}");

            return null;
        }

        return $session;
    }

    public function startRenewalCheckout(RdmInstance $instance, ?string $payerEmail, string $paymentMethod): Payment
    {
        $breakdown = $this->checkoutBreakdown($instance, $paymentMethod);

        if ($breakdown['package_amount'] <= 0) {
            throw new InvalidArgumentException('Harga paket tidak valid.');
        }

        $xenditChannels = $this->xenditChannelsForMethod($paymentMethod);
        $methodConfig = config('xendit.methods.'.$paymentMethod);

        $payment = Payment::create([
            'rdm_instance_id' => $instance->id,
            'external_id' => 'rdm-'.$instance->id.'-'.Str::lower(Str::random(10)),
            'amount' => $breakdown['total'],
            'package' => $instance->package,
            'payment_channel' => $paymentMethod,
            'status' => 'pending',
            'payer_email' => $payerEmail,
            'meta' => [
                'purpose' => 'renewal',
                'method_label' => $methodConfig['label'] ?? $paymentMethod,
                'package_amount' => $breakdown['package_amount'],
                'admin_fee' => $breakdown['admin_fee'],
            ],
        ]);

        return $this->initializeCheckout($payment, $instance, $payerEmail, $paymentMethod, $xenditChannels, $breakdown, 'renewal');
    }

    public function startDeployCheckout(RdmInstance $instance, ?string $payerEmail, string $paymentMethod): Payment
    {
        $breakdown = $this->checkoutBreakdown($instance, $paymentMethod);

        if ($breakdown['package_amount'] <= 0) {
            throw new InvalidArgumentException('Harga paket tidak valid.');
        }

        $xenditChannels = $this->xenditChannelsForMethod($paymentMethod);
        $methodConfig = config('xendit.methods.'.$paymentMethod);

        $payment = Payment::create([
            'rdm_instance_id' => $instance->id,
            'external_id' => 'rdm-deploy-'.$instance->id.'-'.Str::lower(Str::random(10)),
            'amount' => $breakdown['total'],
            'package' => $instance->package,
            'payment_channel' => $paymentMethod,
            'status' => 'pending',
            'payer_email' => $payerEmail,
            'meta' => [
                'purpose' => 'deploy',
                'method_label' => $methodConfig['label'] ?? $paymentMethod,
                'package_amount' => $breakdown['package_amount'],
                'admin_fee' => $breakdown['admin_fee'],
            ],
        ]);

        return $this->initializeCheckout($payment, $instance, $payerEmail, $paymentMethod, $xenditChannels, $breakdown, 'deploy');
    }

    public function checkoutPresentation(Payment $payment): array
    {
        if ($this->isInlineQris($payment)) {
            return [
                'payment_mode' => 'qris_inline',
                'qr_string' => $payment->meta['qr_string'] ?? null,
                'amount' => $payment->amount,
                'expires_at' => $payment->meta['qr_expires_at'] ?? null,
            ];
        }

        if ($this->isInlineVa($payment)) {
            return [
                'payment_mode' => 'va_inline',
                'va_number' => $payment->meta['va_number'] ?? null,
                'bank_label' => $payment->meta['bank_label'] ?? $payment->channelLabel(),
                'channel_code' => $payment->meta['channel_code'] ?? null,
                'amount' => $payment->amount,
                'expires_at' => $payment->meta['va_expires_at'] ?? null,
            ];
        }

        return [
            'payment_mode' => 'redirect',
            'invoice_url' => $payment->invoice_url,
        ];
    }

    public function isInlineQris(Payment $payment): bool
    {
        return ($payment->meta['payment_type'] ?? null) === 'qris'
            && filled($payment->meta['qr_string'] ?? null);
    }

    public function isInlineVa(Payment $payment): bool
    {
        return ($payment->meta['payment_type'] ?? null) === 'virtual_account'
            && filled($payment->meta['va_number'] ?? null);
    }

    public function isInlinePayment(Payment $payment): bool
    {
        return $this->isInlineQris($payment) || $this->isInlineVa($payment);
    }

    private function initializeCheckout(
        Payment $payment,
        RdmInstance $instance,
        ?string $payerEmail,
        string $paymentMethod,
        array $xenditChannels,
        array $breakdown,
        string $purpose,
    ): Payment {
        if ($paymentMethod === 'qris') {
            $description = $purpose === 'deploy'
                ? 'Deploy RDM Online — '.$instance->madrasah_name.' ('.$instance->fqdn.')'
                : 'Perpanjang RDM Online — '.$instance->madrasah_name.' ('.$instance->fqdn.')';

            $qr = $this->xendit->createDynamicQris($payment, $breakdown['total'], $description);

            if (! filled($qr['qr_string'] ?? null)) {
                throw new InvalidArgumentException('Xendit tidak mengembalikan kode QRIS. Coba lagi atau pilih metode lain.');
            }

            $payment->update([
                'xendit_invoice_id' => $qr['id'] ?? null,
                'meta' => array_merge($payment->meta ?? [], [
                    'payment_type' => 'qris',
                    'qr_id' => $qr['id'] ?? null,
                    'qr_string' => $qr['qr_string'] ?? null,
                    'qr_expires_at' => $qr['expires_at'] ?? null,
                    'create' => $qr,
                ]),
            ]);

            return $payment->fresh();
        }

        if ($this->xendit->isVirtualAccountMethod($paymentMethod)) {
            $channelCode = $this->xendit->virtualAccountChannelCode($paymentMethod);
            $customerName = $instance->madrasah_name ?: ($payerEmail ?? 'Pelanggan RDM');

            $vaPayment = $this->xendit->createVirtualAccountPayment(
                $payment,
                $breakdown['total'],
                $channelCode,
                $customerName,
            );

            $vaNumber = data_get($vaPayment, 'payment_method.virtual_account.channel_properties.virtual_account_number');

            if (! filled($vaNumber)) {
                throw new InvalidArgumentException('Xendit tidak mengembalikan nomor Virtual Account.');
            }

            $methodConfig = config('xendit.methods.'.$paymentMethod);

            $payment->update([
                'xendit_invoice_id' => $vaPayment['id'] ?? null,
                'meta' => array_merge($payment->meta ?? [], [
                    'payment_type' => 'virtual_account',
                    'payment_request_id' => $vaPayment['id'] ?? null,
                    'va_number' => $vaNumber,
                    'channel_code' => data_get($vaPayment, 'payment_method.virtual_account.channel_code', $channelCode),
                    'bank_label' => $methodConfig['label'] ?? $channelCode,
                    'va_expires_at' => data_get($vaPayment, 'payment_method.virtual_account.channel_properties.expires_at'),
                    'create' => $vaPayment,
                ]),
            ]);

            return $payment->fresh();
        }

        $invoice = $purpose === 'deploy'
            ? $this->xendit->createDeployInvoice($instance, $payment, $payerEmail, $xenditChannels, $breakdown)
            : $this->xendit->createRenewalInvoice($instance, $payment, $payerEmail, $xenditChannels, $breakdown);

        $payment->update([
            'xendit_invoice_id' => $invoice['id'] ?? null,
            'invoice_url' => $invoice['invoice_url'] ?? null,
            'meta' => array_merge($payment->meta ?? [], [
                'payment_type' => 'invoice',
                'create' => $invoice,
            ]),
        ]);

        return $payment->fresh();
    }

    public function findReusableDeployCheckout(RdmInstance $instance, string $paymentMethod): ?Payment
    {
        $payment = $instance->payments()
            ->where('status', 'pending')
            ->where('payment_channel', $paymentMethod)
            ->where('meta->purpose', 'deploy')
            ->latest()
            ->first();

        if (! $payment) {
            return null;
        }

        if ($paymentMethod === 'qris') {
            return $this->isInlineQris($payment) ? $payment : null;
        }

        if ($this->xendit->isVirtualAccountMethod($paymentMethod)) {
            return $this->isInlineVa($payment) ? $payment : null;
        }

        return filled($payment->invoice_url) ? $payment : null;
    }

    public function paymentForUser(string $externalId, int $userId): ?Payment
    {
        return Payment::query()
            ->where('external_id', $externalId)
            ->whereHas('rdmInstance', fn ($q) => $q->where('user_id', $userId))
            ->with('rdmInstance')
            ->first();
    }

    public function handleInvoiceWebhook(array $payload): void
    {
        $externalId = $payload['external_id'] ?? null;

        if (! $externalId) {
            return;
        }

        $payment = Payment::query()->where('external_id', $externalId)->first();

        if (! $payment || $payment->status === 'paid') {
            return;
        }

        $this->applyInvoiceStatus($payment, $payload, 'webhook');
    }

    public function handleQrCodeWebhook(array $payload): void
    {
        if (strtoupper($payload['status'] ?? '') !== 'COMPLETED') {
            return;
        }

        $referenceId = $payload['qr_code']['reference_id']
            ?? $payload['qr_code']['external_id']
            ?? $payload['reference_id']
            ?? null;

        if (! $referenceId) {
            return;
        }

        $payment = Payment::query()->where('external_id', $referenceId)->first();

        if (! $payment || $payment->status === 'paid') {
            return;
        }

        $this->markPaymentPaid($payment, $payload, 'qr_webhook');
    }

    public function handlePaymentRequestWebhook(array $payload): void
    {
        $data = $payload['data'] ?? $payload;
        $status = strtoupper($data['status'] ?? $payload['status'] ?? '');

        if (! in_array($status, ['SUCCEEDED', 'COMPLETED', 'PAID'], true)) {
            return;
        }

        $referenceId = $data['reference_id'] ?? $payload['reference_id'] ?? null;

        if (! $referenceId) {
            return;
        }

        $payment = Payment::query()->where('external_id', $referenceId)->first();

        if (! $payment || $payment->status === 'paid') {
            return;
        }

        $this->markPaymentPaid($payment, $data, 'payment_request_webhook');
    }

    public function syncPaymentStatus(Payment $payment): bool
    {
        if ($this->isInlineQris($payment)) {
            return $this->syncQrCodeStatus($payment);
        }

        if ($this->isInlineVa($payment)) {
            return $this->syncPaymentRequestStatus($payment);
        }

        return $this->syncInvoiceStatus($payment);
    }

    public function syncPaymentRequestStatus(Payment $payment): bool
    {
        if ($payment->status === 'paid') {
            return true;
        }

        $requestId = $payment->meta['payment_request_id'] ?? $payment->xendit_invoice_id;

        if (! $requestId) {
            return false;
        }

        $request = $this->xendit->fetchPaymentRequest($requestId);
        $status = strtoupper($request['status'] ?? '');

        if (in_array($status, ['SUCCEEDED', 'COMPLETED', 'PAID'], true)) {
            $this->markPaymentPaid($payment, $request, 'payment_request_sync');

            return true;
        }

        return false;
    }

    public function syncQrCodeStatus(Payment $payment): bool
    {
        if ($payment->status === 'paid') {
            return true;
        }

        $qrId = $payment->meta['qr_id'] ?? $payment->xendit_invoice_id;

        if (! $qrId) {
            return false;
        }

        $qr = $this->xendit->fetchQrCode($qrId);
        $status = strtoupper($qr['status'] ?? '');

        if (in_array($status, ['INACTIVE', 'COMPLETED'], true)) {
            $this->markPaymentPaid($payment, $qr, 'qr_sync');

            return true;
        }

        return false;
    }

    public function syncInvoiceStatus(Payment $payment): bool
    {
        if ($payment->status === 'paid' || ! $payment->xendit_invoice_id) {
            return $payment->status === 'paid';
        }

        $invoice = $this->xendit->fetchInvoice($payment->xendit_invoice_id);
        $this->applyInvoiceStatus($payment, $invoice, 'sync');

        return $payment->fresh()->status === 'paid';
    }

    private function applyInvoiceStatus(Payment $payment, array $payload, string $source): void
    {
        $status = strtoupper($payload['status'] ?? '');

        if ($status === 'EXPIRED') {
            $payment->update([
                'status' => 'expired',
                'meta' => array_merge($payment->meta ?? [], [$source => $payload]),
            ]);

            return;
        }

        if ($status !== 'PAID') {
            return;
        }

        $this->markPaymentPaid($payment, $payload, $source);
    }

    private function markPaymentPaid(Payment $payment, array $payload, string $source): void
    {
        DB::transaction(function () use ($payment, $payload, $source) {
            $payment->refresh();

            if ($payment->status === 'paid') {
                return;
            }

            $payment->markPaid(array_merge($payload, ['_source' => $source]));

            $instance = $payment->rdmInstance;

            if ($instance) {
                $this->fulfillPaidPayment($payment, $instance);
            }
        });
    }

    private function fulfillPaidPayment(Payment $payment, RdmInstance $instance): void
    {
        if (($payment->meta['purpose'] ?? 'renewal') === 'deploy') {
            $this->startDeployAfterPayment($instance);

            return;
        }

        $this->subscriptions->renewInstance($instance);
    }

    public function startDeployAfterPayment(RdmInstance $instance): void
    {
        if ($instance->status !== 'pending_payment') {
            return;
        }

        $instance->update([
            'status' => 'deploying',
            'progress' => 0,
            'error_message' => null,
        ]);

        DeployRdmJob::dispatch($instance->fresh());
    }
}
