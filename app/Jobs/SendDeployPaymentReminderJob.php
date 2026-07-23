<?php

namespace App\Jobs;

use App\Models\RdmInstance;
use App\Services\PendingPaymentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendDeployPaymentReminderJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $instanceId,
    ) {}

    public function handle(PendingPaymentService $pendingPayments): void
    {
        $instance = RdmInstance::query()->find($this->instanceId);

        if (! $instance) {
            return;
        }

        $pendingPayments->sendReminderIfDue($instance);
    }
}
