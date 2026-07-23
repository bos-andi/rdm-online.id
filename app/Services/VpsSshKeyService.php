<?php

namespace App\Services;

use App\Models\VpsServer;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class VpsSshKeyService
{
    public function generateAndStore(VpsServer $server, ?string $keyName = null): array
    {
        $comment = 'rdm-'.Str::lower($server->code).'@rdm-online.id';
        $pair = $this->generateKeyPair($comment);

        $this->storeKeyPair($server, $pair['private'], $pair['public'], $keyName ?? $server->code);

        return $pair;
    }

    public function storeFromPrivateKey(VpsServer $server, string $privateKey, ?string $keyName = null): void
    {
        $privateKey = trim($privateKey);

        if (! str_contains($privateKey, 'PRIVATE KEY')) {
            throw new \InvalidArgumentException('Format private key tidak valid. Paste isi file .pem atau id_ed25519.');
        }

        $publicKey = $this->derivePublicKey($privateKey);
        $this->storeKeyPair($server, $privateKey, $publicKey, $keyName);
    }

    public function storeKeyPair(VpsServer $server, string $privateKey, string $publicKey, ?string $keyName = null): void
    {
        $server->update([
            'ssh_key_name' => $keyName ?: $server->ssh_key_name ?: $server->code,
            'ssh_public_key' => trim($publicKey),
            'ssh_private_key' => trim($privateKey),
        ]);

        $this->materializeKeyFile($server->fresh());
    }

    public function materializeKeyFile(VpsServer $server): ?string
    {
        if ($server->isLocal() || blank($server->ssh_private_key)) {
            return null;
        }

        $path = $this->keyFilePath($server);
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0700, true);
        }

        file_put_contents($path, $server->ssh_private_key."\n");
        chmod($path, 0600);

        $server->updateQuietly(['ssh_private_key_path' => $path]);

        return $path;
    }

    public function keyFilePath(VpsServer $server): string
    {
        return storage_path('deploy/keys/server-'.$server->id);
    }

    public function deleteKeyFile(VpsServer $server): void
    {
        $path = $this->keyFilePath($server);

        if (is_file($path)) {
            unlink($path);
        }
    }

    /**
     * @return array{private: string, public: string}
     */
    private function generateKeyPair(string $comment): array
    {
        $directory = storage_path('deploy/keys/tmp-'.Str::random(16));
        mkdir($directory, 0700, true);

        $keyPath = $directory.'/key';

        try {
            $process = new Process([
                'ssh-keygen',
                '-t', 'ed25519',
                '-f', $keyPath,
                '-N', '',
                '-C', $comment,
            ]);
            $process->setTimeout(30);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new \RuntimeException(trim($process->getErrorOutput() ?: $process->getOutput()));
            }

            return [
                'private' => trim(file_get_contents($keyPath)),
                'public' => trim(file_get_contents($keyPath.'.pub')),
            ];
        } finally {
            @unlink($keyPath);
            @unlink($keyPath.'.pub');
            @rmdir($directory);
        }
    }

    private function derivePublicKey(string $privateKey): string
    {
        $directory = storage_path('deploy/keys/tmp-'.Str::random(16));
        mkdir($directory, 0700, true);

        $keyPath = $directory.'/key';

        try {
            file_put_contents($keyPath, $privateKey."\n");
            chmod($keyPath, 0600);

            $process = new Process(['ssh-keygen', '-y', '-f', $keyPath]);
            $process->setTimeout(15);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new \RuntimeException('Private key tidak valid: '.trim($process->getErrorOutput()));
            }

            return trim($process->getOutput());
        } finally {
            @unlink($keyPath);
            @rmdir($directory);
        }
    }
}
