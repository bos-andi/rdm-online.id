<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rdm_instances', function (Blueprint $table) {
            $table->timestamp('expired_at')->nullable()->after('expires_at');
            $table->timestamp('purge_at')->nullable()->after('expired_at');
            $table->timestamp('purge_warning_sent_at')->nullable()->after('purge_at');
        });

        \App\Models\RdmInstance::query()
            ->where('status', 'expired')
            ->each(function (\App\Models\RdmInstance $instance) {
                $expiredAt = $instance->expires_at && $instance->expires_at->isPast()
                    ? $instance->expires_at
                    : $instance->updated_at;

                $instance->updateQuietly([
                    'expired_at' => $expiredAt,
                    'purge_at' => $expiredAt->copy()->addDays((int) config('subscription.grace_days', 30)),
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('rdm_instances', function (Blueprint $table) {
            $table->dropColumn(['expired_at', 'purge_at', 'purge_warning_sent_at']);
        });
    }
};
