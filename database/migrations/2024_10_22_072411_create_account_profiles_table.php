<?php

use App\Models\Role;
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
        Schema::create('account_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('position')->nullable();
            $table->string('number_phone')->nullable();
            $table->foreignId('email')->constrained('accounts')->onDelete('cascade');
            $table->string('address')->nullable();
            $table->string('avatar')->default('/images/avatar.png');
            $table->dateTime('birthday')->nullable();
            $table->foreignId('manager_id')->nullable()->constrained('accounts')->onDelete('set null');
            $table->foreignIdFor(Role::class)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_profiles');
    }
};
