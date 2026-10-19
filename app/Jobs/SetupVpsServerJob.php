<?php

namespace App\Jobs;

use App\Models\VpsServer;
use App\Services\VpsSetupService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SetupVpsServerJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3000;

    public int $tries = 1;

    public function __construct(
        public VpsServer $server,
    ) {}

    public function handle(VpsSetupService $setup): void
    {
        $setup->run($this->server);
    }

    public function failed(?\Throwable $e): void
    {
        app(VpsSetupService::class)->markFailed($this->server, $e?->getMessage() ?? 'Proses setup berhenti.');
    }
}
