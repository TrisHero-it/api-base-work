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
        Schema::table('proposes', function (Blueprint $table) {
            $table->foreignId('approved_by')->nullable()->constrained('accounts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proposes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
        });
    }
};
