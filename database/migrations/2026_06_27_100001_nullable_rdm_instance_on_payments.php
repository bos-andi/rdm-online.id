<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rdm_instance_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('rdm_instance_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rdm_instance_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('rdm_instance_id')->constrained()->cascadeOnDelete();
        });
    }
};
