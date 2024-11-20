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
        Schema::table('kpis', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(\App\Models\Task::class);
            $table->dropConstrainedForeignIdFor(\App\Models\Stage::class);
            $table->dropConstrainedForeignIdFor(\App\Models\Account::class);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kpis', function (Blueprint $table) {
            //
        });
    }
};
