<?php

use App\Models\Department;
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
        Schema::table('workflow_categories', function (Blueprint $table) {
            $table->foreignIdFor(Department::class, 'department_id')->nullable()->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workflow_categories', function (Blueprint $table) {
            $table->dropColumn('department_id');
            $table->dropForeign(['department_id']);
        });
    }
};
