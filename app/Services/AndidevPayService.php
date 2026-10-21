<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class AndidevPayService
{
    private const CHANNELS_CACHE_KEY = 'andidev.payment_channels';

    public function isConfigured(): bool
    {
        return filled(config('andidev.key'));
    }

    public function baseUrl(): string
    {
        return rtrim((string) config('andidev.url'), '/').'/api/v1';
    }

    /**
     * @return array{channels: array<string, array>, synced_at: ?string, error?: string}
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
            ($channels['channels'] ?? []) === [] ? now()->addMinutes(2) : now()->addHours(6),
        );

        return $channels;
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    public function createPayment(
        string $orderId,
        int $amount,
        string $channelCode,
        string $customerName,
        ?string $customerEmail,
        string $description,
        string $returnUrl,
        int $expiryMinutes,
        array $metadata = [],
    ): array {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Andidev Pay belum dikonfigurasi. Hubungi administrator.');
        }

        $payload = [
            'order_id' => $orderId,
            'amount' => $amount,
            'payment_channel' => $channelCode,
            'expires_in_hours' => max(1, min(24, (int) ceil($expiryMinutes / 60))),
            'description' => Str::limit($description, 255, ''),
            'customer' => array_filter([
                'name' => Str::limit($customerName, 255, ''),
                'email' => $customerEmail,
            ]),
            'success_return_url' => $returnUrl,
            'cancel_return_url' => $returnUrl,
        ];

        if ($metadata !== []) {
            $payload['metadata'] = $metadata;
        }

        $response = $this->client()->post($this->baseUrl().'/payments', $payload);

        if (! $response->successful() || ! is_array($response->json('data'))) {
            throw new RuntimeException(
                'Gagal membuat tagihan Andidev Pay: '.($response->json('error.message') ?: $response->body())
            );
        }

        return $response->json('data');
    }

    /**
     * @return array<string, mixed>
     */
    public function paymentDetail(string $id): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Andidev Pay belum dikonfigurasi.');
        }

        $response = $this->client()->get($this->baseUrl().'/payments/'.rawurlencode($id));

        if (! $response->successful() || ! is_array($response->json('data'))) {
            throw new RuntimeException(
                'Gagal mengambil tagihan Andidev Pay: '.($response->json('error.message') ?: $response->body())
            );
        }

        return $response->json('data');
    }

    public function verifyWebhook(string $rawBody, ?string $signatureHeader): bool
    {
        $secret = (string) config('andidev.webhook_secret');

        if ($secret === '' || ! filled($signatureHeader)) {
            return false;
        }

        parse_str(str_replace(',', '&', $signatureHeader), $parts);
        $timestamp = $parts['t'] ?? null;
        $signature = $parts['v1'] ?? null;

        if (! is_string($timestamp) || ! is_string($signature) || $signature === '') {
            return false;
        }

        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$rawBody, $secret);

        return hash_equals($expected, $signature);
    }

    private function client(): PendingRequest
    {
        return Http::withHeaders(['X-Api-Key' => (string) config('andidev.key')])
            ->acceptJson()
            ->timeout(30)
            ->withOptions(['force_ip_resolve' => 'v4']);
    }

    /**
     * @return array{channels: array<string, array>, synced_at: ?string, error?: string}
     */
    private function fetchChannels(): array
    {
        $groups = [
            'QRIS' => 'Pembayaran Instan',
            'VA' => 'Virtual Account',
            'RETAIL' => 'Gerai Retail',
        ];

        try {
            $response = $this->client()->get($this->baseUrl().'/channels');

            if (! $response->successful() || ! is_array($response->json('data'))) {
                return [
                    'channels' => [],
                    'synced_at' => now()->toIso8601String(),
                    'error' => $response->json('error.message') ?: 'HTTP '.$response->status(),
                ];
            }

            $channels = collect($response->json('data'))
                ->mapWithKeys(function ($channel) use ($groups) {
                    $code = strtoupper((string) ($channel['code'] ?? ''));
                    $method = strtoupper((string) ($channel['payment_method'] ?? ''));

                    return [$code => [
                        'code' => $code,
                        'name' => $channel['name'] ?? $code,
                        'group' => $groups[$method] ?? 'Lainnya',
                        'payment_method' => $method,
                        'fee_percent' => (float) ($channel['fee_percent'] ?? 0),
                        'fee_flat' => (int) ($channel['fee_flat'] ?? 0),
                        'fee_bearer' => ($channel['fee_bearer'] ?? 'merchant') === 'customer' ? 'customer' : 'merchant',
                    ]];
                })
                ->filter(fn ($channel) => $channel['code'] !== '')
                ->all();

            return ['channels' => $channels, 'synced_at' => now()->toIso8601String()];
        } catch (\Throwable $e) {
            return ['channels' => [], 'synced_at' => now()->toIso8601String(), 'error' => $e->getMessage()];
        }
    }
}
