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
            $table->foreignIdFor(\App\Models\Task::class)->nullable()->constrained()->onDelete('cascade');
            $table->foreignIdFor(\App\Models\Stage::class)->nullable()->constrained()->onDelete('cascade');
            $table->foreignIdFor(\App\Models\Account::class)->nullable()->constrained()->onDelete('cascade');
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
