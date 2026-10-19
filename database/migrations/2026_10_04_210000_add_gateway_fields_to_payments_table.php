<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('gateway', 20)->default('xendit')->after('external_id');
            $table->string('gateway_reference')->nullable()->index()->after('xendit_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['gateway_reference']);
            $table->dropColumn(['gateway', 'gateway_reference']);
        });
    }
};
