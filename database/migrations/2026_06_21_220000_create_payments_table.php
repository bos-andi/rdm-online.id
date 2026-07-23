<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rdm_instance_id')->constrained()->cascadeOnDelete();
            $table->string('external_id')->unique();
            $table->string('xendit_invoice_id')->nullable()->index();
            $table->unsignedInteger('amount');
            $table->string('package', 20);
            $table->string('status', 20)->default('pending');
            $table->string('payer_email')->nullable();
            $table->text('invoice_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
