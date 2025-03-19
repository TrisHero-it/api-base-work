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
            $table->string('relationship')->nullable()->change();
            $table->string('dependent')->nullable()->change();
            $table->string('urgent')->nullable()->change();
            $table->string('household')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('family_members', function (Blueprint $table) {
            $table->string('relationship')->nullable(false)->change();
            $table->string('dependent')->nullable(false)->change();
            $table->string('urgent')->nullable(false)->change();
            $table->string('household')->nullable(false)->change();
        });
    }
};
