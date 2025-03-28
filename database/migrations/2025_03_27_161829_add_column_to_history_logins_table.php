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
        Schema::table('history_logins', function (Blueprint $table) {
            $table->string('ip_address')->default('127.0.0.1');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('history_logins', function (Blueprint $table) {
            $table->dropColumn('ip_address');
        });
    }
};
