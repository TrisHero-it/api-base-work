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
        Schema::table('workflows', function (Blueprint $table) {
            $table->boolean('is_key_workflow')->default(false);
            $table->dropForeign(['workflow_id']);
            $table->dropColumn('workflow_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workflows', function (Blueprint $table) {
            $table->dropColumn('is_key_workflow');
            $table->dropColumn('workflow_category_id');
        });
    }
};
