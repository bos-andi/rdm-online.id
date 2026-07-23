<?php

namespace App\Services;

use App\Models\RdmInstance;
use Illuminate\Support\Str;

class SubdomainService
{
    public function __construct(
        private HostingerDnsService $hostingerDns,
    ) {}

    public function normalize(string $subdomain): string
    {
        return Str::lower(trim($subdomain));
    }

    public function validate(string $subdomain): ?string
    {
        $subdomain = $this->normalize($subdomain);

        if (strlen($subdomain) < 3 || strlen($subdomain) > 30) {
            return 'Subdomain harus 3–30 karakter.';
        }

        if (! preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/', $subdomain)) {
            return 'Hanya huruf kecil, angka, dan tanda hubung. Tidak boleh diawali/diakhiri dengan hubung.';
        }

        if (in_array($subdomain, config('deploy.reserved_subdomains', []), true)) {
            return 'Subdomain ini tidak dapat digunakan.';
        }

        return null;
    }

    public function fqdn(string $subdomain): string
    {
        return $subdomain.'.'.config('deploy.domain');
    }

    public function isAvailable(string $subdomain, ?int $exceptInstanceId = null, bool $forRetry = false): bool
    {
        return $this->check($subdomain, $exceptInstanceId, $forRetry)['available'];
    }

    public function check(string $subdomain, ?int $exceptInstanceId = null, bool $forRetry = false): array
    {
        $subdomain = $this->normalize($subdomain);
        $error = $this->validate($subdomain);

        if ($error) {
            return [
                'available' => false,
                'subdomain' => $subdomain,
                'fqdn' => $this->fqdn($subdomain),
                'message' => $error,
            ];
        }

        $query = RdmInstance::query()
            ->where('subdomain', $subdomain)
            ->whereNotIn('status', ['failed']);

        if ($exceptInstanceId) {
            $query->where('id', '!=', $exceptInstanceId);
        }

        if ($query->exists()) {
            return [
                'available' => false,
                'subdomain' => $subdomain,
                'fqdn' => $this->fqdn($subdomain),
                'message' => 'Subdomain sudah terdaftar di sistem.',
            ];
        }

        if ($forRetry) {
            return [
                'available' => true,
                'subdomain' => $subdomain,
                'fqdn' => $this->fqdn($subdomain),
                'message' => 'Subdomain tersedia untuk deploy ulang.',
            ];
        }

        $deployPath = rtrim(config('deploy.base_path'), '/').'/'.$subdomain;
        if (is_dir($deployPath)) {
            return [
                'available' => false,
                'subdomain' => $subdomain,
                'fqdn' => $this->fqdn($subdomain),
                'message' => 'Folder deploy sudah ada di server.',
            ];
        }

        $nginxConfig = "/etc/nginx/sites-available/{$subdomain}.".config('deploy.domain');
        if (file_exists($nginxConfig)) {
            return [
                'available' => false,
                'subdomain' => $subdomain,
                'fqdn' => $this->fqdn($subdomain),
                'message' => 'Konfigurasi nginx untuk subdomain ini sudah ada.',
            ];
        }

        if ($this->hostingerDns->isConfigured() && $this->hostingerDns->subdomainExistsInDns($subdomain)) {
            return [
                'available' => false,
                'subdomain' => $subdomain,
                'fqdn' => $this->fqdn($subdomain),
                'message' => 'Subdomain sudah ada di DNS Hostinger.',
            ];
        }

        return [
            'available' => true,
            'subdomain' => $subdomain,
            'fqdn' => $this->fqdn($subdomain),
            'message' => 'Subdomain tersedia.',
        ];
    }
}
