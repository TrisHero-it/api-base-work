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
            Schema::dropIfExists('report_dailies');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('report_dailies', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\Kpi::class)->constrained()->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->string('rate')->nullable();
            $table->string('feedback')->nullable();
            $table->timestamps();
        });
    }
};
