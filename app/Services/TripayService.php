<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class TripayService
{
    private const CHANNELS_CACHE_KEY = 'tripay.payment_channels';

    public function isConfigured(): bool
    {
        return filled(config('tripay.api_key'))
            && filled(config('tripay.private_key'))
            && filled(config('tripay.merchant_code'));
    }

    public function baseUrl(): string
    {
        if (filled(config('tripay.api_url'))) {
            return rtrim(config('tripay.api_url'), '/');
        }

        return config('tripay.mode') === 'production'
            ? 'https://tripay.co.id/api'
            : 'https://tripay.co.id/api-sandbox';
    }

    /**
     * @return array{channels: array<string, array>, synced_at: ?string}
     */
    public function availableChannels(bool $refresh = false): array
    {
        if (! $this->isConfigured()) {
            return ['channels' => [], 'synced_at' => null];
        }

        if ($refresh) {
            Cache::forget(self::CHANNELS_CACHE_KEY);
        }

        $cached = Cache::get(self::CHANNELS_CACHE_KEY);

        if (is_array($cached)) {
            return $cached;
        }

        $channels = $this->fetchChannels();

        Cache::put(
            self::CHANNELS_CACHE_KEY,
            $channels,
            $channels['channels'] === [] ? now()->addMinutes(2) : now()->addHours(6),
        );

        return $channels;
    }

    public function channelCodeFor(string $methodCode, ?int $amount = null, ?array $channels = null): ?string
    {
        $method = config('tripay.methods.'.$methodCode);

        if (! $method || ($method['enabled'] ?? true) === false) {
            return null;
        }

        $channels ??= $this->availableChannels()['channels'];

        foreach ($method['tripay'] ?? [] as $code) {
            $channel = $channels[strtoupper($code)] ?? null;

            if (! $channel) {
                continue;
            }

            if ($amount !== null) {
                $min = (int) ($channel['minimum_amount'] ?? 0);
                $max = (int) ($channel['maximum_amount'] ?? 0);

                if (($min > 0 && $amount < $min) || ($max > 0 && $amount > $max)) {
                    continue;
                }
            }

            return strtoupper($code);
        }

        return null;
    }

    public function isInternalMethodAvailable(string $methodCode, ?array $channels = null): bool
    {
        return $this->channelCodeFor($methodCode, null, $channels) !== null;
    }

    public function createTransaction(
        Payment $payment,
        string $channelCode,
        array $breakdown,
        string $itemName,
        string $customerName,
        string $customerEmail,
        string $returnUrl,
        int $expiryMinutes,
    ): array {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Tripay belum dikonfigurasi. Hubungi administrator.');
        }

        $amount = (int) $breakdown['total'];

        $items = [
            [
                'sku' => 'RDM-'.strtoupper($payment->package),
                'name' => Str::limit($itemName, 100),
                'price' => (int) $breakdown['package_amount'],
                'quantity' => 1,
            ],
        ];

        if (($breakdown['admin_fee'] ?? 0) > 0) {
            $items[] = [
                'sku' => 'ADMIN-FEE',
                'name' => 'Biaya admin',
                'price' => (int) $breakdown['admin_fee'],
                'quantity' => 1,
            ];
        }

        $payload = [
            'method' => $channelCode,
            'merchant_ref' => $payment->external_id,
            'amount' => $amount,
            'customer_name' => Str::limit($customerName, 50, ''),
            'customer_email' => $customerEmail,
            'order_items' => $items,
            'callback_url' => route('webhooks.tripay'),
            'return_url' => $returnUrl,
            'expired_time' => now()->addMinutes($expiryMinutes)->timestamp,
            'signature' => $this->transactionSignature($payment->external_id, $amount),
        ];

        $response = $this->client()
            ->asForm()
            ->post($this->baseUrl().'/transaction/create', $payload);

        if (! $response->successful() || $response->json('success') !== true) {
            throw new RuntimeException(
                'Gagal membuat transaksi Tripay: '.($response->json('message') ?: $response->body())
            );
        }

        return $response->json('data') ?? [];
    }

    public function transactionDetail(string $reference): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Tripay belum dikonfigurasi.');
        }

        $response = $this->client()->get($this->baseUrl().'/transaction/detail', [
            'reference' => $reference,
        ]);

        if (! $response->successful() || $response->json('success') !== true) {
            throw new RuntimeException(
                'Gagal mengambil transaksi Tripay: '.($response->json('message') ?: $response->body())
            );
        }

        return $response->json('data') ?? [];
    }

    public function transactionSignature(string $merchantRef, int $amount): string
    {
        return hash_hmac(
            'sha256',
            config('tripay.merchant_code').$merchantRef.$amount,
            (string) config('tripay.private_key'),
        );
    }

    public function verifyCallbackSignature(string $rawBody, ?string $signature): bool
    {
        if (! filled(config('tripay.private_key')) || ! filled($signature)) {
            return false;
        }

        return hash_equals(
            hash_hmac('sha256', $rawBody, (string) config('tripay.private_key')),
            (string) $signature,
        );
    }

    private function client(): PendingRequest
    {
        return Http::withToken((string) config('tripay.api_key'))
            ->acceptJson()
            ->timeout(30)
            ->withOptions(['force_ip_resolve' => 'v4']);
    }

    /**
     * @return array{channels: array<string, array>, synced_at: ?string}
     */
    private function fetchChannels(): array
    {
        try {
            $response = $this->client()->get($this->baseUrl().'/merchant/payment-channel');

            if (! $response->successful() || $response->json('success') !== true) {
                return ['channels' => [], 'synced_at' => now()->toIso8601String(), 'error' => $response->json('message') ?: 'HTTP '.$response->status()];
            }

            $channels = collect($response->json('data') ?? [])
                ->filter(fn ($channel) => ($channel['active'] ?? false) === true)
                ->mapWithKeys(fn ($channel) => [strtoupper($channel['code']) => [
                    'code' => strtoupper($channel['code']),
                    'name' => $channel['name'] ?? $channel['code'],
                    'group' => $channel['group'] ?? 'Lainnya',
                    'type' => $channel['type'] ?? null,
                    'total_fee' => $channel['total_fee'] ?? null,
                    'minimum_amount' => $channel['minimum_amount'] ?? null,
                    'maximum_amount' => $channel['maximum_amount'] ?? null,
                ]])
                ->all();

            return ['channels' => $channels, 'synced_at' => now()->toIso8601String()];
        } catch (\Throwable $e) {
            return ['channels' => [], 'synced_at' => now()->toIso8601String(), 'error' => $e->getMessage()];
        }
    }
}
