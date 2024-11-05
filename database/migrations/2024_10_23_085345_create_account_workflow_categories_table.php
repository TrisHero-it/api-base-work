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
        Schema::create('account_workflow_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\Account::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(\App\Models\WorkflowCategory::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_workflow_categories');
    }
};
