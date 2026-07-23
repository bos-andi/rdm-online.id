<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vps_servers', function (Blueprint $table) {
            $table->boolean('is_local')->default(false)->after('is_active');
            $table->boolean('accepts_deploy')->default(true)->after('is_local');
            $table->string('ssh_user', 64)->default('root')->after('accepts_deploy');
            $table->unsignedSmallInteger('ssh_port')->default(22)->after('ssh_user');
            $table->string('ssh_private_key_path')->nullable()->after('ssh_port');
            $table->string('deploy_script_path')->nullable()->after('ssh_private_key_path');
            $table->string('source_zip_path')->nullable()->after('deploy_script_path');
            $table->string('sql_template_path')->nullable()->after('source_zip_path');
            $table->string('php_fpm_socket')->nullable()->after('sql_template_path');
            $table->string('suspended_root_path')->nullable()->after('php_fpm_socket');
        });

        \App\Models\VpsServer::query()
            ->where('is_primary', true)
            ->update(['is_local' => true]);
    }

    public function down(): void
    {
        Schema::table('vps_servers', function (Blueprint $table) {
            $table->dropColumn([
                'is_local',
                'accepts_deploy',
                'ssh_user',
                'ssh_port',
                'ssh_private_key_path',
                'deploy_script_path',
                'source_zip_path',
                'sql_template_path',
                'php_fpm_socket',
                'suspended_root_path',
            ]);
        });
    }
};
