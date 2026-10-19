<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('email', 150)->unique();
            $table->string('phone', 30)->nullable();
            $table->string('password');
            $table->string('code', 30)->unique();
            $table->string('status', 20)->default('pending')->index();
            $table->decimal('commission_rate', 5, 2)->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account_number', 50)->nullable();
            $table->string('bank_account_name', 150)->nullable();
            $table->unsignedInteger('clicks')->default(0);
            $table->text('admin_note')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('referred_by_affiliate_id')->nullable()->after('jenjang')
                ->constrained('affiliates')->nullOnDelete();
            $table->timestamp('referred_at')->nullable()->after('referred_by_affiliate_id');
        });

        Schema::create('affiliate_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('status', 20)->default('requested')->index();
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account_number', 50)->nullable();
            $table->string('bank_account_name', 150)->nullable();
            $table->string('transfer_reference', 150)->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('affiliate_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('rdm_instance_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('affiliate_payout_id')->nullable()->constrained()->nullOnDelete();
            $table->string('purpose', 20);
            $table->string('description', 191)->nullable();
            $table->unsignedBigInteger('base_amount');
            $table->decimal('rate', 5, 2);
            $table->unsignedBigInteger('amount');
            $table->string('status', 20)->default('available')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_commissions');
        Schema::dropIfExists('affiliate_payouts');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referred_by_affiliate_id');
            $table->dropColumn('referred_at');
        });

        Schema::dropIfExists('affiliates');
    }
};
