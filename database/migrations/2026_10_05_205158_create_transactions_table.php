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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number')->unique()->index();
            $table->dateTime('transaction_date')->index();
            $table->string('customer_name')->nullable();
            $table->string('pet_name')->nullable();
            $table->string('pet_type')->nullable(); // e.g. Kucing, Anjing, Burung, dll
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->string('discount_type')->default('none'); // 'none', 'fixed', 'percent'
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->decimal('total_cost', 12, 2)->default(0); // Sum of HPP items
            $table->string('payment_method')->default('cash')->index(); // 'cash', 'qris', 'transfer'
            $table->decimal('payment_amount', 12, 2)->default(0);
            $table->decimal('change_amount', 12, 2)->default(0);
            $table->string('status')->default('completed')->index(); // 'completed', 'cancelled'
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
