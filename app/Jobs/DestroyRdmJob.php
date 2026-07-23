<?php

namespace App\Jobs;

use App\Models\RdmInstance;
use App\Services\RdmDestroyService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DestroyRdmJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(
        public RdmInstance $instance,
    ) {}

    public function handle(RdmDestroyService $destroyService): void
    {
        $destroyService->destroyWithProgress($this->instance);
    }
}
