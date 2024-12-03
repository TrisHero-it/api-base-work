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
        Schema::table('stickers', function (Blueprint $table) {
            $table->foreignIdFor(\App\Models\Color::class)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stickers', function (Blueprint $table) {
            $table->foreignIdFor(\App\Models\Color::class)->nullable(false)->change();
        });
    }
};