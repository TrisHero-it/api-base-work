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
        Schema::table('dayoff_accounts', function (Blueprint $table) {
            $table->renameColumn('dayoff_count', 'total_holiday_with_salary');
            $table->renameColumn('dayoff_long_time_worker', 'seniority_holiday');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dayoff_accounts', function (Blueprint $table) {
            //
        });
    }
};
