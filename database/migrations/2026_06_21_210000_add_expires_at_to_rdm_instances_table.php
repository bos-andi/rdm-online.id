<?php

use App\Models\RdmInstance;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rdm_instances', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('deployed_at');
        });

        RdmInstance::query()
            ->whereNotNull('deployed_at')
            ->each(function (RdmInstance $instance) {
                $days = $instance->package === 'yearly' ? 365 : 30;

                $instance->update([
                    'expires_at' => $instance->deployed_at->copy()->addDays($days),
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('rdm_instances', function (Blueprint $table) {
            $table->dropColumn('expires_at');
        });
    }
};
