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
        Schema::table('account_workflows', function (Blueprint $table) {
            $table->foreignIdFor(\App\Models\Department::class)->nullable()->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_workflows', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(\App\Models\Department::class);
        });
    }
};
