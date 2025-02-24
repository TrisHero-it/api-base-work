<?php

use App\Models\CategoryResource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->foreignIdFor(CategoryResource::class)->constrained()->cascadeOnDelete();
            $table->text('thumbnail')->nullable();
            $table->text('note')->nullable();
            $table->text('text_content')->nullable();
            $table->string('account')->nullable();
            $table->string('password')->nullable();
            $table->string('expired_type')->nullable();
            $table->dateTime('expired_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};
