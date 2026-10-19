<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affiliates', function (Blueprint $table) {
            $table->string('ktp_path')->nullable()->after('bank_account_name');
            $table->string('bank_book_path')->nullable()->after('ktp_path');
            $table->string('bank_verification_status', 20)->default('unsubmitted')->after('bank_book_path');
            $table->text('bank_verification_note')->nullable()->after('bank_verification_status');
            $table->timestamp('bank_submitted_at')->nullable()->after('bank_verification_note');
            $table->timestamp('bank_verified_at')->nullable()->after('bank_submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('affiliates', function (Blueprint $table) {
            $table->dropColumn([
                'ktp_path',
                'bank_book_path',
                'bank_verification_status',
                'bank_verification_note',
                'bank_submitted_at',
                'bank_verified_at',
            ]);
        });
    }
};
