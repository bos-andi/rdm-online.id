<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rdm_instances', function (Blueprint $table) {
            $table->id();
            $table->string('subdomain', 63)->unique();
            $table->string('fqdn');
            $table->string('madrasah_name');
            $table->string('jenjang', 10);
            $table->string('pic_email');
            $table->string('package', 20);
            $table->string('deploy_path');
            $table->string('db_name');
            $table->string('db_user');
            $table->text('db_password');
            $table->string('status', 20)->default('deploying');
            $table->unsignedTinyInteger('progress')->default(0);
            $table->string('current_step')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('deployed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('deploy_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rdm_instance_id')->constrained()->cascadeOnDelete();
            $table->string('step');
            $table->string('message');
            $table->unsignedTinyInteger('progress')->default(0);
            $table->string('level', 20)->default('info');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deploy_logs');
        Schema::dropIfExists('rdm_instances');
    }
};
