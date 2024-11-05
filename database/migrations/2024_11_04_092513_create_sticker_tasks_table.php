<?php

use App\Models\Sticker;
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
        Schema::create('sticker_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Sticker::class)->constrained();
            $table->foreignIdFor(\App\Models\Task::class)->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sticker_tasks');
    }
};
