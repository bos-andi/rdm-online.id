<?php

namespace App\Jobs;

use App\Models\RdmInstance;
use App\Services\DeployService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeployRdmJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public RdmInstance $instance,
    ) {}

    public function handle(DeployService $deployService): void
    {
        $deployService->run($this->instance);
    }
}
