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
        Schema::table('history_move_tasks', function (Blueprint $table) {
            // // Xóa khóa ngoại hiện tại
            $table->dropForeign(['account_id']);

            // Thêm lại khóa ngoại với ON DELETE CASCADE
            $table->foreign('account_id')
                ->references('id')->on('accounts')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('history_move_tasks', function (Blueprint $table) {
            //
        });
    }
};
