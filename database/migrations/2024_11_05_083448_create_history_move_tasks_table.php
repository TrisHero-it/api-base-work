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
        Schema::create('history_move_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\Account::class)->constrained();
            $table->foreignIdFor(\App\Models\Task::class)->constrained()->cascadeOnDelete();
            $table->foreignId('old_stage')->constrained('stages')->cascadeOnDelete();
            $table->foreignId('new_stage')->constrained('stages')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('history_move_tasks');
    }
};
