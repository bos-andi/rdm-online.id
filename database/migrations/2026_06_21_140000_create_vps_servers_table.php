<?php

use App\Models\VpsServer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vps_servers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('location', 100);
            $table->string('ip_address', 45);
            $table->unsignedSmallInteger('vcpu')->default(4);
            $table->unsignedSmallInteger('ram_gb')->default(8);
            $table->unsignedSmallInteger('max_rdm_capacity')->default(150);
            $table->string('deploy_base_path')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('rdm_instances', function (Blueprint $table) {
            $table->foreignId('vps_server_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });

        $primary = VpsServer::create([
            'code' => 'ID-01',
            'location' => 'Jakarta',
            'ip_address' => config('deploy.server_ip', '72.62.124.228'),
            'vcpu' => 4,
            'ram_gb' => 8,
            'max_rdm_capacity' => (int) config('admin.vps.max_rdm_capacity', 150),
            'deploy_base_path' => config('deploy.base_path', '/var/www/rdm-online'),
            'is_primary' => true,
            'is_active' => true,
            'notes' => 'Server utama — deploy RDM berjalan di sini.',
        ]);

        \App\Models\RdmInstance::query()->update(['vps_server_id' => $primary->id]);
    }

    public function down(): void
    {
        Schema::table('rdm_instances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vps_server_id');
        });

        Schema::dropIfExists('vps_servers');
    }
};
