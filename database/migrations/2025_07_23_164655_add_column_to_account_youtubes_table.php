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
        Schema::table('account_youtubes', function (Blueprint $table) {
            $table->string('type')->default('1');
            $table->integer('index')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_youtubes', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->dropColumn('index');
        });
    }
};
