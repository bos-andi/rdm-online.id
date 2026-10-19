<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SetupVpsServerJob;
use App\Models\VpsServer;
use App\Services\RemoteDeployExecutor;
use App\Services\VpsSetupService;
use App\Services\VpsSpecService;
use App\Services\VpsSshKeyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class VpsServerController extends Controller
{
    public function store(Request $request, VpsSshKeyService $sshKeys, VpsSpecService $specs): RedirectResponse
    {
        $request->merge(['code' => $request->filled('code') ? strtoupper(trim($request->input('code'))) : null]);

        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:20', 'regex:/^[A-Z0-9-]+$/', Rule::unique('vps_servers', 'code')],
            'location' => ['nullable', 'string', 'max:100'],
            'ip_address' => ['required', 'ip', Rule::unique('vps_servers', 'ip_address')],
            'max_rdm_capacity' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'deploy_base_path' => ['nullable', 'string', 'max:255'],
            'ssh_key_name' => ['nullable', 'string', 'max:100'],
            'ssh_user' => ['nullable', 'string', 'max:64'],
            'ssh_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'ssh_key_mode' => ['nullable', 'in:shared,generate,paste'],
            'ssh_private_key' => ['nullable', 'required_if:ssh_key_mode,paste', 'string'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_local' => ['sometimes', 'boolean'],
        ], [
            'ip_address.unique' => 'IP ini sudah terdaftar sebagai server.',
            'ssh_private_key.required_if' => 'Paste private key, atau pilih opsi key lain.',
        ]);

        if ($request->boolean('is_local')) {
            VpsServer::query()->update(['is_local' => false, 'is_primary' => false]);
        }

        $code = $data['code'] ?? $this->nextServerCode();

        $server = VpsServer::create([
            'code' => $code,
            'location' => $data['location'] ?? $this->detectLocation($data['ip_address']),
            'ip_address' => $data['ip_address'],
            'vcpu' => 0,
            'ram_gb' => 0,
            'max_rdm_capacity' => $data['max_rdm_capacity'] ?? 0,
            'deploy_base_path' => $data['deploy_base_path'] ?? null,
            'ssh_key_name' => $data['ssh_key_name'] ?? null,
            'ssh_user' => $data['ssh_user'] ?? 'root',
            'ssh_port' => $data['ssh_port'] ?? 22,
            'notes' => $data['notes'] ?? null,
            'is_local' => $request->boolean('is_local'),
            'is_primary' => false,
            'accepts_deploy' => true,
            'is_active' => true,
        ]);

        $message = 'Server VPS '.$code.' ('.$server->ip_address.') berhasil ditambahkan.';
        $keyMode = $data['ssh_key_mode'] ?? ($sshKeys->sharedKeyPath() ? 'shared' : 'generate');

        if (! $server->is_local) {
            try {
                if ($keyMode === 'shared') {
                    $sshKeys->useSharedKey($server);
                    $message .= ' Memakai SSH key '.$sshKeys->sharedKeyLabel().' — jika VPS sudah terdaftar di '.$sshKeys->sharedKeyLabel().', langsung klik Setup Otomatis.';
                } elseif ($keyMode === 'paste') {
                    $sshKeys->storeFromPrivateKey($server, $data['ssh_private_key'], $data['ssh_key_name'] ?? $code);
                    $message .= ' Key SSH disimpan. Klik Setup Otomatis.';
                } else {
                    $sshKeys->generateAndStore($server, $data['ssh_key_name'] ?? $code);
                    $message .= ' Key SSH dibuat — pasang public key di VPS (portal provider atau Salin perintah Console), lalu klik Setup Otomatis.';
                }
            } catch (\Throwable $e) {
                return redirect()
                    ->route('admin.vps')
                    ->withErrors(['vps' => 'Server tersimpan, tapi key SSH gagal: '.$e->getMessage()]);
            }
        }

        if ($server->is_local) {
            $specs->detectAndStore($server);
        }

        return redirect()
            ->route('admin.vps')
            ->with('status', $server->is_local
                ? $message.' Aktifkan server ini untuk deploy jika ingin dipakai pelanggan baru.'
                : $message.' Setelah setup selesai, klik Aktifkan untuk Deploy.');
    }

    private function nextServerCode(): string
    {
        $max = VpsServer::query()->pluck('code')
            ->map(fn ($code) => preg_match('/^ID-(\d+)$/', $code, $m) ? (int) $m[1] : 0)
            ->max() ?? 0;

        return sprintf('ID-%02d', $max + 1);
    }

    private function detectLocation(string $ip): string
    {
        try {
            $info = Http::timeout(5)->get('https://ipinfo.io/'.$ip.'/json')->json();
            $place = collect([$info['city'] ?? null, $info['country'] ?? null])->filter()->implode(', ');

            return $place !== '' ? mb_substr($place, 0, 100) : '-';
        } catch (\Throwable) {
            return '-';
        }
    }

    public function updateSsh(Request $request, VpsServer $vpsServer, VpsSshKeyService $sshKeys): RedirectResponse
    {
        if ($vpsServer->is_local) {
            return back()->withErrors(['vps' => 'Server lokal tidak memerlukan SSH.']);
        }

        $data = $request->validate([
            'ssh_key_name' => ['nullable', 'string', 'max:100'],
            'ssh_user' => ['required', 'string', 'max:64'],
            'ssh_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'ssh_private_key' => ['nullable', 'string'],
            'generate_ssh_key' => ['sometimes', 'boolean'],
            'use_shared_ssh_key' => ['sometimes', 'boolean'],
        ]);

        $vpsServer->update([
            'ssh_key_name' => $data['ssh_key_name'],
            'ssh_user' => $data['ssh_user'],
            'ssh_port' => $data['ssh_port'],
        ]);

        try {
            if ($request->boolean('use_shared_ssh_key')) {
                $sshKeys->useSharedKey($vpsServer);

                return back()->with('status', $vpsServer->code.': Sekarang memakai SSH key '.$sshKeys->sharedKeyLabel().'. Klik Tes SSH untuk memastikan.');
            }

            if ($request->boolean('generate_ssh_key')) {
                $sshKeys->generateAndStore($vpsServer, $data['ssh_key_name'] ?? $vpsServer->code);

                return back()->with('status', $vpsServer->code.': Key SSH baru dibuat. Salin public key ke portal VPS.');
            }

            if (filled($data['ssh_private_key'] ?? null)) {
                $sshKeys->storeFromPrivateKey($vpsServer, $data['ssh_private_key'], $data['ssh_key_name'] ?? $vpsServer->code);

                return back()->with('status', $vpsServer->code.': Private key diperbarui.');
            }

            $sshKeys->materializeKeyFile($vpsServer->fresh());

            return back()->with('status', $vpsServer->code.': Pengaturan SSH disimpan.');
        } catch (\Throwable $e) {
            return back()->withErrors(['vps' => $e->getMessage()]);
        }
    }

    public function generateKey(VpsServer $vpsServer, VpsSshKeyService $sshKeys): RedirectResponse
    {
        if ($vpsServer->is_local) {
            return back()->withErrors(['vps' => 'Server lokal tidak memerlukan SSH key.']);
        }

        try {
            $sshKeys->generateAndStore($vpsServer, $vpsServer->ssh_key_name ?: $vpsServer->code);
        } catch (\Throwable $e) {
            return back()->withErrors(['vps' => 'Gagal generate key: '.$e->getMessage()]);
        }

        return back()->with('status', $vpsServer->code.': Key SSH baru dibuat. Salin public key ke Biznet/VPS.');
    }

    public function destroy(VpsServer $vpsServer, VpsSshKeyService $sshKeys): RedirectResponse
    {
        if ($vpsServer->is_local) {
            return back()->withErrors(['vps' => 'Server lokal panel tidak dapat dihapus.']);
        }

        if ($vpsServer->is_primary) {
            return back()->withErrors(['vps' => 'Set server lain sebagai deploy aktif terlebih dahulu.']);
        }

        if ($vpsServer->rdmInstances()->exists()) {
            return back()->withErrors(['vps' => 'Server masih memiliki instance RDM. Pindahkan atau hapus instance terlebih dahulu.']);
        }

        $sshKeys->deleteKeyFile($vpsServer);
        $vpsServer->delete();

        return redirect()
            ->route('admin.vps')
            ->with('status', 'Server VPS dihapus.');
    }

    public function setPrimary(VpsServer $vpsServer): RedirectResponse
    {
        if (! $vpsServer->is_active) {
            return back()->withErrors(['vps' => 'Server tidak aktif.']);
        }

        if (! $vpsServer->isDeployReady()) {
            return back()->withErrors(['vps' => $vpsServer->code.' belum siap. Selesaikan SSH & Sync Deploy terlebih dahulu.']);
        }

        VpsServer::query()->update(['is_primary' => false]);
        $vpsServer->update(['is_primary' => true]);

        return redirect()
            ->route('admin.vps')
            ->with('status', $vpsServer->code.' sekarang server deploy aktif. RDM baru & DNS mengarah ke '.$vpsServer->ip_address.'. RDM yang sudah ada tidak dipindahkan.');
    }

    public function testConnection(VpsServer $vpsServer, RemoteDeployExecutor $executor, VpsSshKeyService $sshKeys, VpsSpecService $specs): RedirectResponse
    {
        $sshKeys->materializeKeyFile($vpsServer);
        $result = $executor->testConnectionDetectingUser($vpsServer->fresh());

        if (! $result['ok']) {
            return back()->withErrors(['vps' => $vpsServer->code.': '.$result['message']]);
        }

        $specMessage = '';
        if ($specs->detectAndStore($vpsServer->fresh())) {
            $vpsServer->refresh();
            $specMessage = ' Spesifikasi: '.$vpsServer->vcpu.' vCPU, '.$vpsServer->ram_gb.'GB RAM.';
        }

        return redirect()
            ->route('admin.vps')
            ->with('status', $vpsServer->code.': '.$result['message'].$specMessage);
    }

    public function detectSpecs(VpsServer $vpsServer, VpsSshKeyService $sshKeys, VpsSpecService $specs): RedirectResponse
    {
        $sshKeys->materializeKeyFile($vpsServer);

        if (! $specs->detectAndStore($vpsServer->fresh())) {
            return back()->withErrors(['vps' => $vpsServer->code.': Gagal deteksi spesifikasi. Pastikan SSH sudah terhubung.']);
        }

        $vpsServer->refresh();

        return back()->with('status', $vpsServer->code.': '.$vpsServer->vcpu.' vCPU, '.$vpsServer->ram_gb.'GB RAM terdeteksi.');
    }

    public function syncDeploy(VpsServer $vpsServer, VpsSshKeyService $sshKeys, VpsSpecService $specs): RedirectResponse
    {
        if ($vpsServer->is_local) {
            return back()->withErrors(['vps' => 'Server lokal tidak perlu sync via SSH.']);
        }

        $sshKeys->materializeKeyFile($vpsServer);

        $exitCode = Artisan::call('rdm:sync-vps', ['code' => $vpsServer->code]);
        $output = trim(Artisan::output());

        if ($exitCode !== 0) {
            return back()->withErrors(['vps' => $vpsServer->code.': '.($output ?: 'Sync gagal.')]);
        }

        $specs->detectAndStore($vpsServer->fresh());

        return back()->with('status', $vpsServer->code.': Sync deploy berhasil. '.$output);
    }

    public function startSetup(VpsServer $vpsServer, VpsSetupService $setup): JsonResponse
    {
        if ($vpsServer->is_local) {
            return response()->json(['message' => 'Server lokal panel tidak perlu setup.'], 422);
        }

        if ($setup->isRunning($vpsServer)) {
            return response()->json(['message' => 'Setup sedang berjalan.'] + $setup->payload($vpsServer));
        }

        if (! $vpsServer->isRemoteReady()) {
            return response()->json(['message' => 'Key SSH belum ada. Buat key di Kelola SSH, lalu pasang public key di VPS.'], 422);
        }

        $setup->markQueued($vpsServer);
        SetupVpsServerJob::dispatch($vpsServer);

        return response()->json(['message' => 'Setup dimulai.'] + $setup->payload($vpsServer));
    }

    public function setupStatus(VpsServer $vpsServer, VpsSetupService $setup): JsonResponse
    {
        return response()->json($setup->payload($vpsServer));
    }
}
