<?php

use App\Models\Account;
use App\Models\PersonDocumnetCategory;
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
        Schema::create('person_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Account::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(PersonDocumnetCategory::class)->constrained()->cascadeOnDelete();
            $table->date('license_date')->nullable();
            $table->date('expiration_date')->nullable();
            $table->text('note')->nullable();   
            $table->text('files')->nullable();
            $table->string('place_of_issue')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('person_documents');
    }
};
