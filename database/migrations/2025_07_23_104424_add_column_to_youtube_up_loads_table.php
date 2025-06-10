<?php

use App\Models\YoutubeChannel;
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
        Schema::table('youtube_up_loads', function (Blueprint $table) {
            $table->foreignIdFor(YoutubeChannel::class)->nullable()->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('youtube_up_loads', function (Blueprint $table) {
            //
        });
    }
};
