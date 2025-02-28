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
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('tax_code')->nullable();
            $table->string('tax_reduced')->nullable();
            $table->string('tax_policy')->nullable();
            $table->string('BHXH')->nullable();
            $table->string('place_of_registration')->nullable();
            $table->string('salary_scale')->nullable();
            $table->string('insurance_policy')->nullable();
            $table->string('status')->nullable();
            $table->string('working_time')->nullable();
            $table->string('start_trial_date')->nullable();
            $table->string('start_work_date')->nullable();
            $table->text('contract_file')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('tax_code');
            $table->dropColumn('tax_reduced');
            $table->dropColumn('tax_policy');
            $table->dropColumn('BHXH');
            $table->dropColumn('place_of_registration');
            $table->dropColumn('salary_scale');
            $table->dropColumn('insurance_policy');
            $table->dropColumn('status');
            $table->dropColumn('working_time');
            $table->dropColumn('start_trial_date');
            $table->dropColumn('start_work_date');
            $table->dropColumn('contract_file');
        });
    }
};
