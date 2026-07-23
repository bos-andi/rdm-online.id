<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Hostinger DNS API — spec: public/api-1.json
 * GET  /api/dns/v1/zones/{domain}
 * PUT  /api/dns/v1/zones/{domain}
 */
class HostingerDnsService
{
    public function isConfigured(): bool
    {
        return filled(config('hostinger.api_token'));
    }

    private function client()
    {
        return Http::baseUrl(rtrim(config('hostinger.api_base'), '/'))
            ->withToken(config('hostinger.api_token'))
            ->acceptJson()
            ->asJson();
    }

    /**
     * @return array<int, array{name: string, type: string, ttl?: int, records: array<int, array{content: string, is_disabled?: bool}>}>
     */
    public function getRecords(): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        $domain = config('deploy.domain');
        $response = $this->client()->get("/api/dns/v1/zones/{$domain}");

        if (! $response->successful()) {
            Log::warning('Hostinger DNS GET failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [];
        }

        $data = $response->json();

        return is_array($data) ? $data : [];
    }

    public function subdomainExistsInDns(string $subdomain): bool
    {
        $subdomain = strtolower($subdomain);

        foreach ($this->getRecords() as $record) {
            $name = strtolower($record['name'] ?? '');
            $type = strtoupper($record['type'] ?? '');

            if ($name === $subdomain && in_array($type, ['A', 'AAAA', 'CNAME', 'ALIAS'], true)) {
                return true;
            }
        }

        return false;
    }

    public function createSubdomainARecord(string $subdomain, ?string $ip = null): void
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('Token API Hostinger belum dikonfigurasi.');
        }

        if ($this->subdomainExistsInDns($subdomain)) {
            return;
        }

        $domain = config('deploy.domain');
        $ip = $ip ?: \App\Models\VpsServer::deployIp();

        $payload = [
            'overwrite' => false,
            'zone' => [
                [
                    'name' => $subdomain,
                    'type' => 'A',
                    'ttl' => 14400,
                    'records' => [
                        ['content' => $ip],
                    ],
                ],
            ],
        ];

        $validate = $this->client()->post("/api/dns/v1/zones/{$domain}/validate", $payload);
        if (! $validate->successful()) {
            $message = $this->extractErrorMessage($validate);
            throw new \RuntimeException('Validasi DNS gagal: '.$message);
        }

        $response = $this->client()->put("/api/dns/v1/zones/{$domain}", $payload);

        if (! $response->successful()) {
            throw new \RuntimeException('Gagal membuat DNS di Hostinger: '.$this->extractErrorMessage($response));
        }
    }

    public function deleteSubdomainRecord(string $subdomain): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        if (! $this->subdomainExistsInDns($subdomain)) {
            return;
        }

        $domain = config('deploy.domain');
        $payload = [
            'filters' => [
                [
                    'name' => strtolower($subdomain),
                    'type' => 'A',
                ],
            ],
        ];

        $response = $this->client()->send('DELETE', "/api/dns/v1/zones/{$domain}", [
            'json' => $payload,
        ]);

        if (! $response->successful()) {
            Log::warning('Hostinger DNS DELETE failed', [
                'subdomain' => $subdomain,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('Gagal menghapus DNS: '.$this->extractErrorMessage($response));
        }
    }

    private function extractErrorMessage($response): string
    {
        return $response->json('message')
            ?? $response->json('error')
            ?? trim($response->body())
            ?: 'Unknown error';
    }
}
