<?php

use App\Models\Account;
use App\Models\AssetCategory;
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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->date('buy_date')->nullable();
            $table->integer('price')->nullable();
            $table->string('brand')->nullable();
            $table->foreignId('buyer_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('seller_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->date('warranty_date')->nullable();
            $table->date('sell_date')->nullable();
            $table->integer('sell_price')->nullable();
            $table->foreignIdFor(Account::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(AssetCategory::class)->nullable()->constrained()->cascadeOnDelete();
            $table->string('serial_number')->nullable();
            $table->string('description')->nullable();
            $table->string('status')->nullable()->default('using');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
