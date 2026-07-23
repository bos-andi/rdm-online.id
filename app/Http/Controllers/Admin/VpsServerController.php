<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VpsServer;
use App\Services\RemoteDeployExecutor;
use App\Services\VpsSpecService;
use App\Services\VpsSshKeyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\Rule;

class VpsServerController extends Controller
{
    public function store(Request $request, VpsSshKeyService $sshKeys, VpsSpecService $specs): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9-]+$/', Rule::unique('vps_servers', 'code')],
            'location' => ['required', 'string', 'max:100'],
            'ip_address' => ['required', 'ip'],
            'max_rdm_capacity' => ['required', 'integer', 'min:1', 'max:10000'],
            'deploy_base_path' => ['nullable', 'string', 'max:255'],
            'ssh_key_name' => ['nullable', 'string', 'max:100'],
            'ssh_user' => ['nullable', 'string', 'max:64'],
            'ssh_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'ssh_private_key' => ['nullable', 'string'],
            'generate_ssh_key' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_local' => ['sometimes', 'boolean'],
            'accepts_deploy' => ['sometimes', 'boolean'],
        ]);

        if ($request->boolean('is_local')) {
            VpsServer::query()->update(['is_local' => false, 'is_primary' => false]);
        }

        $server = VpsServer::create([
            'code' => $data['code'],
            'location' => $data['location'],
            'ip_address' => $data['ip_address'],
            'vcpu' => 0,
            'ram_gb' => 0,
            'max_rdm_capacity' => $data['max_rdm_capacity'],
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

        $message = 'Server VPS '.$data['code'].' berhasil ditambahkan.';

        if (! $server->is_local) {
            try {
                if ($request->boolean('generate_ssh_key')) {
                    $sshKeys->generateAndStore($server, $data['ssh_key_name'] ?? $data['code']);
                    $message .= ' Key SSH dibuat — salin public key ke portal VPS, lalu Tes SSH untuk deteksi vCPU/RAM.';
                } elseif (filled($data['ssh_private_key'] ?? null)) {
                    $sshKeys->storeFromPrivateKey($server, $data['ssh_private_key'], $data['ssh_key_name'] ?? $data['code']);
                    $message .= ' Key SSH disimpan. Klik Tes SSH untuk deteksi vCPU/RAM.';
                } else {
                    $message .= ' Klik Tes SSH untuk deteksi vCPU/RAM otomatis.';
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
            ->with('status', $message.' Aktifkan server ini untuk deploy jika ingin dipakai pelanggan baru.');
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
        ]);

        $vpsServer->update([
            'ssh_key_name' => $data['ssh_key_name'],
            'ssh_user' => $data['ssh_user'],
            'ssh_port' => $data['ssh_port'],
        ]);

        try {
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
        $result = $executor->testConnection($vpsServer->fresh());

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
}
