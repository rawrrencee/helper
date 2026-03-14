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
        Schema::create('salary_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('helper_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->decimal('base_salary', 10, 2);
            $table->date('working_days_start')->nullable();
            $table->date('working_days_end')->nullable();
            $table->unsignedInteger('total_calendar_days')->default(0);
            $table->unsignedInteger('sundays_in_period')->default(0);
            $table->unsignedInteger('rest_days_taken')->default(0);
            $table->decimal('pro_rated_amount', 10, 2)->default(0);
            $table->unsignedInteger('extra_rest_days_worked')->default(0);
            $table->decimal('rest_day_rate', 10, 2)->default(0);
            $table->decimal('extra_rest_day_pay', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('payment_method')->default('bank_transfer');
            $table->string('payment_screenshot_path')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['helper_id', 'month', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_payments');
    }
};
