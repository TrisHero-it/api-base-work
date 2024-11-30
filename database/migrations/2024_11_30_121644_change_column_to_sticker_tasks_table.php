<?php

use App\Models\Sticker;
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
        Schema::table('sticker_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(Sticker::class);
            $table->dropConstrainedForeignIdFor(Task::class);


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sticker_tasks', function (Blueprint $table) {
            //
        });
    }
};
