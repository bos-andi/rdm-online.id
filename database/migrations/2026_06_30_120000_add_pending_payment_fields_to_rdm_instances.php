<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rdm_instances', function (Blueprint $table) {
            $table->timestamp('pending_payment_expires_at')->nullable()->after('purge_warning_sent_at');
            $table->timestamp('payment_reminder_sent_at')->nullable()->after('pending_payment_expires_at');
            $table->timestamp('deploy_welcome_sent_at')->nullable()->after('payment_reminder_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('rdm_instances', function (Blueprint $table) {
            $table->dropColumn([
                'pending_payment_expires_at',
                'payment_reminder_sent_at',
                'deploy_welcome_sent_at',
            ]);
        });
    }
};
