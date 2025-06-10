<?php

use App\Models\Task;
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
        Schema::create('youtube_up_loads', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('hashtags')->nullable();
            $table->text('video_url')->nullable();
            $table->string('title_game')->nullable();
            $table->dateTime('upload_date')->nullable();
            $table->string('status')->default('pending');
            $table->foreignIdFor(Task::class)->constrained()->cascadeOnDelete();
            $table->string('channel_name')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('youtube_up_loads');
    }
};
