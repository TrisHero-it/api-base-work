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
            $table->text('label_new_values')->nullable();
            $table->text('label_old_values')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proposes', function (Blueprint $table) {
            $table->dropColumn('label_new_values');
            $table->dropColumn('label_old_values');
        });
    }
};
