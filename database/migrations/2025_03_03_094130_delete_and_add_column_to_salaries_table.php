<?php

use App\Models\Account;
use App\Models\JobPosition;
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
        Schema::table('salaries', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(Account::class);
            $table->foreignIdFor(JobPosition::class)->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salaries', function (Blueprint $table) {
            $table->foreignIdFor(Account::class)->constrained()->cascadeOnDelete();
            $table->dropConstrainedForeignIdFor(JobPosition::class);
        });
    }
};
