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
        Schema::create('helpers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('fin')->unique();
            $table->string('passport_no')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('nationality')->nullable();
            $table->string('occupation')->nullable();
            $table->date('date_of_application')->nullable();
            $table->string('work_permit_no')->nullable();
            $table->string('sb_transmission_ref_no')->nullable();
            $table->string('employer_name')->nullable();
            $table->string('employment_agency')->nullable();
            $table->decimal('monthly_salary', 10, 2)->default(0);
            $table->decimal('monthly_levy_rate', 10, 2)->default(0);
            $table->integer('rest_days_per_month')->default(4);
            $table->boolean('round_up_rest_day_rate')->default(false);
            $table->decimal('fees_payable_to_ea', 10, 2)->default(0);
            $table->string('bank_name')->nullable();
            $table->string('bank_account_no')->nullable();
            $table->boolean('paynow_enabled')->default(false);
            $table->string('paynow_identifier')->nullable();
            $table->longText('family_information')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('helpers');
    }
};
