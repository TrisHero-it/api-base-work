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
            $table->dropConstrainedForeignIdFor(\App\Models\Color::class);
            $table->dropConstrainedForeignIdFor(\App\Models\Workflow::class);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stickers', function (Blueprint $table) {
            //
        });
    }
};