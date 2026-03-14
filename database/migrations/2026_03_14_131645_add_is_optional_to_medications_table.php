<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('medications', function (Blueprint $table) {
            $table->boolean('is_optional')->default(false)->after('frequency');
        });

        // Migrate existing "(If Needed)" frequencies
        DB::table('medications')
            ->where('frequency', 'like', '%(If Needed)%')
            ->update([
                'is_optional' => true,
                'frequency' => DB::raw("REPLACE(frequency, ' (If Needed)', '')"),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore "(If Needed)" suffix before dropping column
        DB::table('medications')
            ->where('is_optional', true)
            ->update([
                'frequency' => DB::raw("CONCAT(frequency, ' (If Needed)')"),
            ]);

        Schema::table('medications', function (Blueprint $table) {
            $table->dropColumn('is_optional');
        });
    }
};
