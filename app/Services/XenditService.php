<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\RdmInstance;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class XenditService
{
    private const CHANNELS_CACHE_KEY = 'xendit.available_channels';

    public function isConfigured(): bool
    {
        return filled(config('xendit.secret_key'));
    }

    public function availableChannels(bool $refresh = false): array
    {
        if (! $this->isConfigured()) {
            return $this->emptyChannels();
        }

        if ($refresh) {
            Cache::forget(self::CHANNELS_CACHE_KEY);
        }

        return Cache::remember(
            self::CHANNELS_CACHE_KEY,
            now()->addHours(6),
            fn () => $this->probeAvailableChannels(),
        );
    }

    public function isXenditCodeAvailable(string $xenditCode, ?array $channels = null): bool
    {
        $channels ??= $this->availableChannels();
        $code = strtoupper($xenditCode);

        return in_array($code, $channels['banks'], true)
            || in_array($code, $channels['ewallets'], true)
            || in_array($code, $channels['qr'], true)
            || in_array($code, $channels['retail'], true);
    }

    public function isInternalMethodAvailable(string $methodCode, ?array $channels = null): bool
    {
        $method = config('xendit.methods.'.$methodCode);

        if (! $method || ($method['enabled'] ?? true) === false) {
            return false;
        }

        $channels ??= $this->availableChannels();

        foreach ($method['xendit'] as $xenditCode) {
            if ($this->isXenditCodeAvailable($xenditCode, $channels)) {
                return true;
            }
        }

        return false;
    }

    public function createRenewalInvoice(
        RdmInstance $instance,
        Payment $payment,
        ?string $payerEmail = null,
        array $paymentMethods = [],
        ?array $breakdown = null,
    ): array {
        $pkg = config('deploy.packages.'.$instance->package);
        $description = 'Perpanjang RDM Online — '.$instance->madrasah_name.' ('.$instance->fqdn.')';

        return $this->createInvoice(
            $payment,
            $description,
            'Langganan RDM — Paket '.($pkg['label'] ?? $instance->package),
            $payerEmail,
            $paymentMethods,
            $breakdown,
            route('renewal.success', [
                'subdomain' => $instance->subdomain,
                'ref' => $payment->external_id,
            ]),
            route('renewal.checkout', $instance->subdomain),
        );
    }

    public function createDeployInvoice(
        RdmInstance $instance,
        Payment $payment,
        ?string $payerEmail = null,
        array $paymentMethods = [],
        ?array $breakdown = null,
    ): array {
        $pkg = config('deploy.packages.'.$instance->package);
        $description = 'Deploy RDM Online — '.$instance->madrasah_name.' ('.$instance->fqdn.')';

        return $this->createInvoice(
            $payment,
            $description,
            'Deploy RDM — Paket '.($pkg['label'] ?? $instance->package),
            $payerEmail,
            $paymentMethods,
            $breakdown,
            route('deploy.payment.success', ['ref' => $payment->external_id]),
            route('deploy'),
        );
    }

    private function createInvoice(
        Payment $payment,
        string $description,
        string $itemName,
        ?string $payerEmail,
        array $paymentMethods,
        ?array $breakdown,
        string $successRedirectUrl,
        string $failureRedirectUrl,
    ): array {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Xendit belum dikonfigurasi. Hubungi administrator.');
        }

        $breakdown ??= [
            'package_amount' => $payment->amount,
            'admin_fee' => 0,
            'total' => $payment->amount,
        ];

        $items = [
            [
                'name' => $itemName,
                'quantity' => 1,
                'price' => $breakdown['package_amount'],
                'category' => 'Subscription',
            ],
        ];

        if (($breakdown['admin_fee'] ?? 0) > 0) {
            $items[] = [
                'name' => 'Biaya admin',
                'quantity' => 1,
                'price' => $breakdown['admin_fee'],
                'category' => 'Fee',
            ];
        }

        $payload = [
            'external_id' => $payment->external_id,
            'amount' => $breakdown['total'],
            'description' => $description,
            'invoice_duration' => 86400,
            'currency' => 'IDR',
            'success_redirect_url' => $successRedirectUrl,
            'failure_redirect_url' => $failureRedirectUrl,
            'items' => $items,
        ];

        if ($paymentMethods !== []) {
            $payload['payment_methods'] = array_values(array_map('strtoupper', $paymentMethods));
        }

        if ($payerEmail) {
            $payload['payer_email'] = $payerEmail;
        }

        $response = Http::withBasicAuth(config('xendit.secret_key'), '')
            ->acceptJson()
            ->post(rtrim(config('xendit.api_url'), '/').'/v2/invoices', $payload);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Gagal membuat invoice Xendit: '.($response->json('message') ?? $response->body())
            );
        }

        return $response->json();
    }

    public function createDynamicQris(Payment $payment, int $amount, string $description): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Xendit belum dikonfigurasi. Hubungi administrator.');
        }

        $payload = [
            'reference_id' => $payment->external_id,
            'type' => 'DYNAMIC',
            'currency' => 'IDR',
            'amount' => $amount,
            'description' => Str::limit($description, 250),
        ];

        $response = Http::withBasicAuth(config('xendit.secret_key'), '')
            ->acceptJson()
            ->withHeaders(['api-version' => config('xendit.qr_api_version', '2022-07-31')])
            ->post(rtrim(config('xendit.api_url'), '/').'/qr_codes', $payload);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Gagal membuat QRIS Xendit: '.($response->json('message') ?? $response->body())
            );
        }

        return $response->json();
    }

    public function fetchQrCode(string $qrId): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Xendit belum dikonfigurasi.');
        }

        $response = Http::withBasicAuth(config('xendit.secret_key'), '')
            ->acceptJson()
            ->withHeaders(['api-version' => config('xendit.qr_api_version', '2022-07-31')])
            ->get(rtrim(config('xendit.api_url'), '/').'/qr_codes/'.$qrId);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Gagal mengambil status QRIS: '.($response->json('message') ?? $response->body())
            );
        }

        return $response->json();
    }

    public function isVirtualAccountMethod(string $methodCode): bool
    {
        return (config('xendit.methods.'.$methodCode.'.group') ?? null) === 'Virtual Account';
    }

    public function virtualAccountChannelCode(string $methodCode): string
    {
        $codes = config('xendit.methods.'.$methodCode.'.xendit', []);

        if ($codes === []) {
            throw new RuntimeException('Metode VA tidak valid.');
        }

        return strtoupper($codes[0]);
    }

    public function createVirtualAccountPayment(
        Payment $payment,
        int $amount,
        string $channelCode,
        string $customerName,
    ): array {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Xendit belum dikonfigurasi. Hubungi administrator.');
        }

        $payload = [
            'reference_id' => $payment->external_id,
            'currency' => 'IDR',
            'amount' => $amount,
            'country' => 'ID',
            'payment_method' => [
                'type' => 'VIRTUAL_ACCOUNT',
                'reusability' => 'ONE_TIME_USE',
                'reference_id' => $payment->external_id.'-pm',
                'virtual_account' => [
                    'channel_code' => strtoupper($channelCode),
                    'channel_properties' => [
                        'customer_name' => Str::limit($customerName, 50),
                        'expires_at' => now()->addDay()->toIso8601String(),
                    ],
                ],
            ],
        ];

        $response = Http::withBasicAuth(config('xendit.secret_key'), '')
            ->acceptJson()
            ->withHeaders(['api-version' => config('xendit.payment_request_api_version', '2022-07-31')])
            ->post(rtrim(config('xendit.api_url'), '/').'/payment_requests', $payload);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Gagal membuat Virtual Account: '.($response->json('message') ?? $response->body())
            );
        }

        return $response->json();
    }

    public function fetchPaymentRequest(string $paymentRequestId): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Xendit belum dikonfigurasi.');
        }

        $response = Http::withBasicAuth(config('xendit.secret_key'), '')
            ->acceptJson()
            ->withHeaders(['api-version' => config('xendit.payment_request_api_version', '2022-07-31')])
            ->get(rtrim(config('xendit.api_url'), '/').'/payment_requests/'.$paymentRequestId);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Gagal mengambil status pembayaran: '.($response->json('message') ?? $response->body())
            );
        }

        return $response->json();
    }

    public function fetchInvoice(string $invoiceId): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Xendit belum dikonfigurasi.');
        }

        $response = Http::withBasicAuth(config('xendit.secret_key'), '')
            ->acceptJson()
            ->get(rtrim(config('xendit.api_url'), '/').'/v2/invoices/'.$invoiceId);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Gagal mengambil invoice Xendit: '.($response->json('message') ?? $response->body())
            );
        }

        return $response->json();
    }

    public function verifyCallbackToken(?string $token): bool
    {
        $expected = config('xendit.callback_token');

        if (! filled($expected)) {
            return app()->environment('local');
        }

        return hash_equals($expected, (string) $token);
    }

    private function probeAvailableChannels(): array
    {
        try {
            $response = Http::withBasicAuth(config('xendit.secret_key'), '')
                ->acceptJson()
                ->post(rtrim(config('xendit.api_url'), '/').'/v2/invoices', [
                    'external_id' => 'probe-'.Str::lower(Str::random(12)),
                    'amount' => 15000,
                    'currency' => 'IDR',
                    'description' => 'Probe payment channels',
                    'invoice_duration' => 60,
                ]);

            if (! $response->successful()) {
                return $this->fallbackChannels();
            }

            $data = $response->json();

            return [
                'banks' => array_values(array_map('strtoupper', array_column($data['available_banks'] ?? [], 'bank_code'))),
                'ewallets' => array_values(array_map('strtoupper', array_column($data['available_ewallets'] ?? [], 'ewallet_type'))),
                'qr' => array_values(array_map('strtoupper', array_column($data['available_qr_codes'] ?? [], 'qr_code_type'))),
                'retail' => array_values(array_map('strtoupper', array_column($data['available_retail_outlets'] ?? [], 'retail_outlet_name'))),
                'synced_at' => now()->toIso8601String(),
            ];
        } catch (\Throwable) {
            return $this->fallbackChannels();
        }
    }

    private function fallbackChannels(): array
    {
        return array_merge($this->emptyChannels(), [
            'banks' => ['BNI', 'BRI', 'MANDIRI', 'PERMATA', 'CIMB', 'BSI', 'BJB'],
            'qr' => ['QRIS'],
            'retail' => ['INDOMARET'],
            'synced_at' => now()->toIso8601String(),
            'fallback' => true,
        ]);
    }

    private function emptyChannels(): array
    {
        return [
            'banks' => [],
            'ewallets' => [],
            'qr' => [],
            'retail' => [],
            'synced_at' => null,
        ];
    }
}
