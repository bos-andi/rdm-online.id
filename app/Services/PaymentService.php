<?php

namespace App\Services;

use App\Jobs\DeployRdmJob;
use App\Models\Payment;
use App\Models\RdmInstance;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PaymentService
{
    public function __construct(
        private XenditService $xendit,
        private TripayService $tripay,
        private SubscriptionService $subscriptions,
    ) {}

    public function gateway(): string
    {
        return config('payment.gateway') === 'tripay' ? 'tripay' : 'xendit';
    }

    public function usesTripay(): bool
    {
        return $this->gateway() === 'tripay';
    }

    public function gatewayConfigured(): bool
    {
        return $this->usesTripay() ? $this->tripay->isConfigured() : $this->xendit->isConfigured();
    }

    public function refreshAvailableChannels(): void
    {
        $this->usesTripay()
            ? $this->tripay->availableChannels(refresh: true)
            : $this->xendit->availableChannels(refresh: true);
    }

    public function methodConfig(string $methodCode): ?array
    {
        return config($this->gateway().'.methods.'.$methodCode);
    }

    public function isVirtualAccountMethod(string $methodCode): bool
    {
        return ($this->methodConfig($methodCode)['group'] ?? null) === 'Virtual Account';
    }

    /**
     * @return array{groups: array<string, list<string>>, synced_at: ?string, error?: string}
     */
    public function gatewayChannelSummary(): array
    {
        if ($this->usesTripay()) {
            $data = $this->tripay->availableChannels();

            return array_filter([
                'groups' => collect($data['channels'])
                    ->groupBy('group')
                    ->map(fn (Collection $channels) => $channels->pluck('name')->values()->all())
                    ->all(),
                'synced_at' => $data['synced_at'] ?? null,
                'error' => $data['error'] ?? null,
            ], fn ($value) => $value !== null);
        }

        $data = $this->xendit->availableChannels();

        return [
            'groups' => array_filter([
                'QRIS' => $data['qr'],
                'Virtual Account' => $data['banks'],
                'E-Wallet' => $data['ewallets'],
                'Retail' => $data['retail'],
            ]),
            'synced_at' => $data['synced_at'],
        ];
    }

    public function instanceForRenewal(string $subdomain): ?RdmInstance
    {
        return RdmInstance::query()
            ->where('subdomain', $subdomain)
            ->whereIn('status', ['active', 'expired', 'suspended'])
            ->first();
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
        $method = $this->methodConfig($methodCode);

        return (int) ($method['admin_fee'] ?? config($this->gateway().'.default_admin_fee', 0));
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
        if ($this->usesTripay()) {
            $methods = collect(config('tripay.methods', []));

            if (! $this->tripay->isConfigured()) {
                return $methods->filter(fn ($method) => ($method['enabled'] ?? true) !== false)->all();
            }

            $channels = $this->tripay->availableChannels()['channels'];

            return $methods
                ->filter(fn ($method, $code) => $this->tripay->isInternalMethodAvailable($code, $channels))
                ->all();
        }

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

    public function gatewayChannelsForMethod(string $methodCode): array
    {
        $gateway = $this->gateway();
        $available = $this->usesTripay()
            ? $this->tripay->isInternalMethodAvailable($methodCode)
            : $this->xendit->isInternalMethodAvailable($methodCode);

        if (! $available) {
            throw new InvalidArgumentException('Metode pembayaran tidak tersedia.');
        }

        $method = $this->methodConfig($methodCode);

        if (! $method || empty($method[$gateway])) {
            throw new InvalidArgumentException('Metode pembayaran tidak valid.');
        }

        return $method[$gateway];
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

        $gatewayChannels = $this->gatewayChannelsForMethod($paymentMethod);
        $methodConfig = $this->methodConfig($paymentMethod);

        $payment = Payment::create([
            'rdm_instance_id' => $instance->id,
            'external_id' => 'rdm-'.$instance->id.'-'.Str::lower(Str::random(10)),
            'gateway' => $this->gateway(),
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

        return $this->initializeCheckout($payment, $instance, $payerEmail, $paymentMethod, $gatewayChannels, $breakdown, 'renewal');
    }

    public function startDeployCheckout(RdmInstance $instance, ?string $payerEmail, string $paymentMethod): Payment
    {
        $breakdown = $this->checkoutBreakdown($instance, $paymentMethod);

        if ($breakdown['package_amount'] <= 0) {
            throw new InvalidArgumentException('Harga paket tidak valid.');
        }

        $gatewayChannels = $this->gatewayChannelsForMethod($paymentMethod);
        $methodConfig = $this->methodConfig($paymentMethod);

        $payment = Payment::create([
            'rdm_instance_id' => $instance->id,
            'external_id' => 'rdm-deploy-'.$instance->id.'-'.Str::lower(Str::random(10)),
            'gateway' => $this->gateway(),
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

        return $this->initializeCheckout($payment, $instance, $payerEmail, $paymentMethod, $gatewayChannels, $breakdown, 'deploy');
    }

    public function checkoutPresentation(Payment $payment): array
    {
        if ($this->isInlineQris($payment)) {
            return [
                'payment_mode' => 'qris_inline',
                'qr_string' => $payment->meta['qr_string'] ?? null,
                'amount' => $payment->meta['pay_amount'] ?? $payment->amount,
                'expires_at' => $payment->meta['qr_expires_at'] ?? null,
            ];
        }

        if ($this->isInlineVa($payment)) {
            return [
                'payment_mode' => 'va_inline',
                'va_number' => $payment->meta['va_number'] ?? null,
                'bank_label' => $payment->meta['bank_label'] ?? $payment->channelLabel(),
                'channel_code' => $payment->meta['channel_code'] ?? null,
                'amount' => $payment->meta['pay_amount'] ?? $payment->amount,
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
        if ($payment->gateway === 'tripay') {
            return $this->initializeTripayCheckout($payment, $instance, $payerEmail, $paymentMethod, $breakdown, $purpose);
        }

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

    private function initializeTripayCheckout(
        Payment $payment,
        RdmInstance $instance,
        ?string $payerEmail,
        string $paymentMethod,
        array $breakdown,
        string $purpose,
    ): Payment {
        $channelCode = $this->tripay->channelCodeFor($paymentMethod, (int) $breakdown['total']);

        if (! $channelCode) {
            throw new InvalidArgumentException('Metode pembayaran tidak tersedia untuk nominal ini. Pilih metode lain.');
        }

        $methodConfig = $this->methodConfig($paymentMethod) ?? [];
        $pkg = config('deploy.packages.'.$instance->package);
        $itemName = ($purpose === 'deploy' ? 'Deploy RDM' : 'Langganan RDM').' - Paket '.($pkg['label'] ?? $instance->package);
        $returnUrl = $purpose === 'deploy'
            ? route('deploy.payment.success', ['ref' => $payment->external_id])
            : route('renewal.success', ['subdomain' => $instance->subdomain, 'ref' => $payment->external_id]);

        $transaction = $this->tripay->createTransaction(
            $payment,
            $channelCode,
            $breakdown,
            $itemName,
            $instance->madrasah_name ?: ($payerEmail ?? 'Pelanggan RDM'),
            $payerEmail ?: ($instance->pic_email ?: config('mail.from.address')),
            $returnUrl,
            (int) ($methodConfig['expiry_minutes'] ?? config('tripay.expiry_minutes', 1440)),
        );

        $expiresAt = filled($transaction['expired_time'] ?? null)
            ? Carbon::createFromTimestamp((int) $transaction['expired_time'])->toIso8601String()
            : null;

        $meta = [
            'payment_type' => 'invoice',
            'channel_code' => $channelCode,
            'tripay_reference' => $transaction['reference'] ?? null,
            'pay_amount' => (int) ($transaction['amount'] ?? $breakdown['total']),
            'expires_at' => $expiresAt,
            'create' => $transaction,
        ];

        if (filled($transaction['qr_string'] ?? null)) {
            $meta = array_merge($meta, [
                'payment_type' => 'qris',
                'qr_string' => $transaction['qr_string'],
                'qr_expires_at' => $expiresAt,
            ]);
        } elseif ($this->isVirtualAccountMethod($paymentMethod) && filled($transaction['pay_code'] ?? null)) {
            $meta = array_merge($meta, [
                'payment_type' => 'virtual_account',
                'va_number' => (string) $transaction['pay_code'],
                'bank_label' => $methodConfig['label'] ?? ($transaction['payment_name'] ?? $channelCode),
                'va_expires_at' => $expiresAt,
            ]);
        }

        $payment->update([
            'gateway_reference' => $transaction['reference'] ?? null,
            'invoice_url' => $transaction['checkout_url'] ?? null,
            'meta' => array_merge($payment->meta ?? [], $meta),
        ]);

        return $payment->fresh();
    }

    public function findReusableDeployCheckout(RdmInstance $instance, string $paymentMethod): ?Payment
    {
        $payment = $instance->payments()
            ->where('status', 'pending')
            ->where('gateway', $this->gateway())
            ->where('payment_channel', $paymentMethod)
            ->where('meta->purpose', 'deploy')
            ->latest()
            ->first();

        if (! $payment) {
            return null;
        }

        $expiresAt = $payment->meta['expires_at'] ?? null;

        if (filled($expiresAt) && Carbon::parse($expiresAt)->subMinutes(5)->isPast()) {
            return null;
        }

        if ($paymentMethod === 'qris') {
            return $this->isInlineQris($payment) ? $payment : null;
        }

        if ($this->isVirtualAccountMethod($paymentMethod)) {
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

    public function handleTripayCallback(array $data): array
    {
        $merchantRef = $data['merchant_ref'] ?? null;

        if (! $merchantRef) {
            return ['success' => false, 'message' => 'merchant_ref kosong'];
        }

        $payment = Payment::query()
            ->where('external_id', $merchantRef)
            ->where('gateway', 'tripay')
            ->first();

        if (! $payment) {
            return ['success' => false, 'message' => 'Transaksi tidak ditemukan: '.$merchantRef];
        }

        if ($payment->gateway_reference && $payment->gateway_reference !== ($data['reference'] ?? null)) {
            return ['success' => false, 'message' => 'Reference tidak cocok'];
        }

        if ($payment->status !== 'paid') {
            $this->applyTripayStatus($payment, (string) ($data['status'] ?? ''), $data, 'webhook');
        }

        return ['success' => true];
    }

    public function syncTripayStatus(Payment $payment): bool
    {
        if ($payment->status === 'paid') {
            return true;
        }

        if (! $payment->gateway_reference || ! Cache::add('tripay.sync.'.$payment->id, true, 5)) {
            return false;
        }

        $transaction = $this->tripay->transactionDetail($payment->gateway_reference);
        $this->applyTripayStatus($payment, (string) ($transaction['status'] ?? ''), $transaction, 'sync');

        return $payment->fresh()->status === 'paid';
    }

    private function applyTripayStatus(Payment $payment, string $status, array $payload, string $source): void
    {
        $status = strtoupper($status);
        unset($payload['instructions'], $payload['order_items']);

        if ($status === 'PAID') {
            $paidAt = $payload['paid_at'] ?? null;
            $payload['paid_at'] = is_numeric($paidAt)
                ? Carbon::createFromTimestamp((int) $paidAt)->toIso8601String()
                : null;

            $this->markPaymentPaid($payment, $payload, $source);

            return;
        }

        if (in_array($status, ['EXPIRED', 'FAILED', 'REFUND'], true) && $payment->status === 'pending') {
            $payment->update([
                'status' => $status === 'EXPIRED' ? 'expired' : 'failed',
                'meta' => array_merge($payment->meta ?? [], [$source => $payload]),
            ]);
        }
    }

    public function syncPaymentStatus(Payment $payment): bool
    {
        if ($payment->gateway === 'tripay') {
            return $this->syncTripayStatus($payment);
        }

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

        try {
            app(AffiliateService::class)->recordCommission($payment->fresh());
        } catch (\Throwable $e) {
            Log::warning('Gagal mencatat komisi affiliate untuk '.$payment->external_id.': '.$e->getMessage());
        }
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
