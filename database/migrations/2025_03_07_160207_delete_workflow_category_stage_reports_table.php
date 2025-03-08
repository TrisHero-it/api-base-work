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
        Schema::dropIfExists('workflow_category_stage_reports');
        Schema::dropIfExists('workflow_category_stages');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
