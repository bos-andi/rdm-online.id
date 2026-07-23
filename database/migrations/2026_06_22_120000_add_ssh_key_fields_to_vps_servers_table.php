<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vps_servers', function (Blueprint $table) {
            $table->string('ssh_key_name', 100)->nullable()->after('ssh_port');
            $table->text('ssh_public_key')->nullable()->after('ssh_key_name');
            $table->text('ssh_private_key')->nullable()->after('ssh_public_key');
        });
    }

    public function down(): void
    {
        Schema::table('vps_servers', function (Blueprint $table) {
            $table->dropColumn(['ssh_key_name', 'ssh_public_key', 'ssh_private_key']);
        });
    }
};
