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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('type')->index(); // 'initial', 'in', 'sale', 'adjustment', 'reversal'
            $table->integer('quantity'); // positive or negative
            $table->integer('before_stock');
            $table->integer('after_stock');
            $table->decimal('cost_price', 12, 2)->nullable();
            $table->string('reference_number')->nullable()->index();
            $table->string('reference_type')->nullable(); // 'transaction', 'restock', 'correction', 'initial'
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
