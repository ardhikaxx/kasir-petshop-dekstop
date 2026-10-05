<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Record stock in (restock/penerimaan barang).
     */
    public function recordStockIn(
        Product $product,
        int $quantity,
        ?float $costPrice = null,
        ?string $referenceNumber = null,
        ?string $notes = null
    ): StockMovement {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Jumlah stok masuk harus lebih dari 0.');
        }

        return DB::transaction(function () use ($product, $quantity, $costPrice, $referenceNumber, $notes) {
            // Lock product for update
            $lockedProduct = Product::lockForUpdate()->findOrFail($product->id);
            $beforeStock = $lockedProduct->stock;
            $afterStock = $beforeStock + $quantity;

            // If cost price provided and differs, update product cost price
            if ($costPrice !== null && $costPrice > 0) {
                $lockedProduct->cost_price = $costPrice;
            }

            $lockedProduct->stock = $afterStock;
            $lockedProduct->save();

            return StockMovement::create([
                'product_id' => $lockedProduct->id,
                'type' => 'in',
                'quantity' => $quantity,
                'before_stock' => $beforeStock,
                'after_stock' => $afterStock,
                'cost_price' => $costPrice ?? $lockedProduct->cost_price,
                'reference_number' => $referenceNumber,
                'reference_type' => 'restock',
                'notes' => $notes ?? 'Penerimaan stok masuk',
            ]);
        });
    }

    /**
     * Record stock adjustment / manual stock correction.
     */
    public function recordAdjustment(
        Product $product,
        int $actualStock,
        string $reason,
        ?string $notes = null
    ): StockMovement {
        if ($actualStock < 0) {
            throw new \InvalidArgumentException('Stok aktual tidak boleh negatif.');
        }

        return DB::transaction(function () use ($product, $actualStock, $reason, $notes) {
            $lockedProduct = Product::lockForUpdate()->findOrFail($product->id);
            $beforeStock = $lockedProduct->stock;
            $diff = $actualStock - $beforeStock;

            $lockedProduct->stock = $actualStock;
            $lockedProduct->save();

            return StockMovement::create([
                'product_id' => $lockedProduct->id,
                'type' => 'adjustment',
                'quantity' => $diff,
                'before_stock' => $beforeStock,
                'after_stock' => $actualStock,
                'cost_price' => $lockedProduct->cost_price,
                'reference_type' => 'adjustment',
                'notes' => ($reason ? "[$reason] " : '').($notes ?? 'Penyesuaian stok fisik (stock opname)'),
            ]);
        });
    }

    /**
     * Get low stock products.
     *
     * @return Collection<int, Product>
     */
    public function getLowStockProducts(int $limit = 10): Collection
    {
        return Product::query()
            ->active()
            ->with('category')
            ->lowStock()
            ->orderBy('stock', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get all stock-alert products (both low-stock and out-of-stock).
     *
     * @return Collection<int, Product>
     */
    public function getAlertProducts(int $limit = 20): Collection
    {
        return Product::query()
            ->active()
            ->with('category')
            ->where(function ($q) {
                $q->whereColumn('stock', '<=', 'min_stock');
            })
            ->orderBy('stock', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get paginated stock movements with filters.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<StockMovement>
     */
    public function getStockMovements(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = StockMovement::query()
            ->with('product.category')
            ->latest();

        if (! empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        return $query->paginate($perPage);
    }
}
