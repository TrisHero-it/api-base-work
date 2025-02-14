<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('field_tasks', function (Blueprint $table) {
            $table->dropForeign(['account_id']);

            // Thêm lại khóa ngoại với ON DELETE CASCADE
            $table->foreign('account_id')
                ->references('id')
                ->on('accounts')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('field_tasks', function (Blueprint $table) {
            $table->dropForeign(['account_id']);

            // Thêm lại khóa ngoại cũ (không có ON DELETE CASCADE)
            $table->foreign('account_id')
                ->references('id')
                ->on('accounts');
        });
    }
};
