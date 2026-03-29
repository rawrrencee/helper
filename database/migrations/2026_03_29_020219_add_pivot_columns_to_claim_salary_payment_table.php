<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('claim_salary_payment', function (Blueprint $table) {
            $table->boolean('paid_separately')->default(false)->after('salary_payment_id');
            $table->string('payment_method')->nullable()->after('paid_separately');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('claim_salary_payment', function (Blueprint $table) {
            $table->dropColumn(['paid_separately', 'payment_method']);
        });
    }
};
