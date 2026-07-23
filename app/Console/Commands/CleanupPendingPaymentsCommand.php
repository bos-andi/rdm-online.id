<?php

namespace App\Console\Commands;

use App\Services\PendingPaymentService;
use Illuminate\Console\Command;

class CleanupPendingPaymentsCommand extends Command
{
    protected $signature = 'rdm:cleanup-pending-payments';

    protected $description = 'Hapus RDM pending_payment yang kedaluwarsa dan bebaskan subdomain';

    public function handle(PendingPaymentService $pendingPayments): int
    {
        $count = $pendingPayments->cleanupExpired();

        $this->info("{$count} RDM pending_payment dibersihkan.");

        return self::SUCCESS;
    }
}
