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
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('sex')->nullable();
            $table->string('identity_card')->nullable();
            $table->string('temporary_address')->nullable();
            $table->string('passport')->nullable();
            $table->string('name_bank')->nullable();
            $table->string('bank_number')->nullable();
            $table->string('marital_status')->nullable();
            $table->text('files')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('sex');
            $table->dropColumn('identity_card');
            $table->dropColumn('temporary_address');
            $table->dropColumn('passport');
            $table->dropColumn('name_bank');
            $table->dropColumn('bank_number');
            $table->dropColumn('marital_status');
            $table->dropColumn('files');
        });
    }
};
