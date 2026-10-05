<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductService
{
    /**
     * Generate unique SKU.
     */
    public function generateSku(): string
    {
        do {
            $sku = 'PRD-'.strtoupper(Str::random(6));
        } while (Product::withTrashed()->where('sku', $sku)->exists());

        return $sku;
    }

    /**
     * Create a new product and record initial stock movement if stock > 0.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            if (empty($data['sku'])) {
                $data['sku'] = $this->generateSku();
            }

            $initialStock = (int) ($data['stock'] ?? 0);
            $product = Product::create($data);

            if ($initialStock > 0) {
                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'initial',
                    'quantity' => $initialStock,
                    'before_stock' => 0,
                    'after_stock' => $initialStock,
                    'cost_price' => $product->cost_price,
                    'reference_type' => 'initial',
                    'notes' => 'Pencatatan stok awal produk',
                ]);
            }

            return $product;
        });
    }

    /**
     * Update an existing product.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $oldStock = (int) $product->stock;
            $newStock = isset($data['stock']) ? (int) $data['stock'] : $oldStock;

            $product->update($data);

            if ($newStock !== $oldStock) {
                $diff = $newStock - $oldStock;
                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'adjustment',
                    'quantity' => $diff,
                    'before_stock' => $oldStock,
                    'after_stock' => $newStock,
                    'cost_price' => $product->cost_price,
                    'reference_type' => 'correction',
                    'notes' => 'Penyesuaian stok dari edit produk',
                ]);
            }

            return $product->fresh();
        });
    }

    /**
     * Safely delete a product.
     * If the product has transaction items, soft delete is preserved.
     */
    public function delete(Product $product): bool
    {
        return DB::transaction(function () use ($product) {
            $hasTransactions = $product->transactionItems()->exists();

            if ($hasTransactions) {
                $product->update(['is_active' => false]);

                return (bool) $product->delete();
            }

            // Also delete stock movements if no transaction ever occurred
            $product->stockMovements()->delete();

            return (bool) $product->forceDelete();
        });
    }

    /**
     * Toggle product active state.
     */
    public function toggleActive(Product $product): Product
    {
        $product->update(['is_active' => ! $product->is_active]);

        return $product->fresh();
    }
}
