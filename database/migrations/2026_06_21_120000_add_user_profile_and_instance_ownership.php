<?php

use App\Models\RdmInstance;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('madrasah_name')->nullable()->after('name');
            $table->string('jenjang', 10)->nullable()->after('madrasah_name');
        });

        Schema::table('rdm_instances', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        RdmInstance::query()->each(function (RdmInstance $instance) {
            $user = User::query()->firstOrCreate(
                ['email' => $instance->pic_email],
                [
                    'name' => $instance->madrasah_name,
                    'madrasah_name' => $instance->madrasah_name,
                    'jenjang' => $instance->jenjang,
                    'password' => bcrypt(str()->password(16, symbols: false)),
                ]
            );

            $instance->update(['user_id' => $user->id]);
        });
    }

    public function down(): void
    {
        Schema::table('rdm_instances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['madrasah_name', 'jenjang']);
        });
    }
};
