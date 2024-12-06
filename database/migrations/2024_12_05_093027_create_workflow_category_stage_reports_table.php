<?php

use App\Models\WorkflowCategoryStage;
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
        Schema::create('workflow_category_stage_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_stage_id')->constrained('workflow_category_stages')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_category_stage_reports');
    }
};
