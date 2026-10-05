<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'type',
        'quantity',
        'before_stock',
        'after_stock',
        'cost_price',
        'reference_number',
        'reference_type',
        'reference_id',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'before_stock' => 'integer',
            'after_stock' => 'integer',
            'cost_price' => 'decimal:2',
        ];
    }

    /**
     * The product this movement belongs to.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Human-readable label for movement type.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'initial' => 'Stok Awal',
            'in' => 'Stok Masuk',
            'sale' => 'Penjualan',
            'adjustment' => 'Koreksi/Penyesuaian',
            'reversal' => 'Pembatalan Transaksi',
            default => ucfirst($this->type),
        };
    }

    /**
     * Badge UI styling.
     *
     * @return array{label: string, class: string}
     */
    public function getTypeBadgeAttribute(): array
    {
        return match ($this->type) {
            'initial' => ['label' => 'Stok Awal', 'class' => 'badge-info'],
            'in' => ['label' => 'Stok Masuk', 'class' => 'badge-success'],
            'sale' => ['label' => 'Penjualan', 'class' => 'badge-primary'],
            'adjustment' => ['label' => 'Penyesuaian', 'class' => 'badge-warning'],
            'reversal' => ['label' => 'Pembatalan', 'class' => 'badge-secondary'],
            default => ['label' => ucfirst($this->type), 'class' => 'badge-secondary'],
        };
    }
}
