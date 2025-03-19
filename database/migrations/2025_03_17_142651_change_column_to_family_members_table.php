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
        Schema::table('family_members', function (Blueprint $table) {
            $table->dropColumn('dependent');
            $table->dropColumn('urgent');
            $table->dropColumn('household');
            $table->boolean('is_dependent')->default(false);
            $table->boolean('is_urgent')->default(false);
            $table->boolean('is_household')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('family_members', function (Blueprint $table) {
            $table->dropColumn('is_dependent');
            $table->dropColumn('is_urgent');
            $table->dropColumn('is_household');
            $table->string('dependent')->nullable();
            $table->string('urgent')->nullable();
            $table->string('household')->nullable();
        });
    }
};
