<?php

namespace App\Console\Commands;

use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class ExpireSubscriptionsCommand extends Command
{
    protected $signature = 'rdm:expire-subscriptions';

    protected $description = 'Nonaktifkan langganan habis, kirim peringatan purge, dan hapus permanen RDM expired';

    public function handle(SubscriptionService $subscriptions): int
    {
        $expired = $subscriptions->expireDueInstances();
        $warnings = $subscriptions->sendPurgeWarnings();
        $purged = $subscriptions->purgeExpiredInstances();

        $this->info("{$expired} langganan dinonaktifkan.");
        $this->info("{$warnings} peringatan penghapusan dikirim.");
        $this->info("{$purged} RDM dihapus permanen.");

        return self::SUCCESS;
    }
}
