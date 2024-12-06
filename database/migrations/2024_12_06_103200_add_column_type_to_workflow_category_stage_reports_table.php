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
        Schema::table('workflow_category_stage_reports', function (Blueprint $table) {
            $table->string('type')->after('name')->default('paragraph');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workflow_category_stage_reports', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
