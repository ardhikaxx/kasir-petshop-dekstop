<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'transaction_number',
        'transaction_date',
        'customer_name',
        'pet_name',
        'pet_type',
        'subtotal',
        'discount_type',
        'discount_value',
        'discount_amount',
        'tax_percentage',
        'tax_amount',
        'grand_total',
        'total_cost',
        'payment_method',
        'payment_amount',
        'change_amount',
        'status',
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
            'transaction_date' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_percentage' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'payment_amount' => 'decimal:2',
            'change_amount' => 'decimal:2',
        ];
    }

    /**
     * Items in this transaction.
     *
     * @return HasMany<TransactionItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    /**
     * Scope for completed transactions.
     *
     * @param  Builder<Transaction>  $query
     */
    public function scopeCompleted(Builder $query): void
    {
        $query->where('status', 'completed');
    }

    /**
     * Scope for cancelled transactions.
     *
     * @param  Builder<Transaction>  $query
     */
    public function scopeCancelled(Builder $query): void
    {
        $query->where('status', 'cancelled');
    }

    /**
     * Scope for today's transactions.
     *
     * @param  Builder<Transaction>  $query
     */
    public function scopeToday(Builder $query): void
    {
        $query->whereDate('transaction_date', today());
    }

    /**
     * Estimated gross profit.
     * (Revenue excluding tax minus total HPP cost)
     */
    public function getEstimatedGrossProfitAttribute(): float
    {
        // Revenue before tax = grand_total - tax_amount
        $revenueBeforeTax = (float) $this->grand_total - (float) $this->tax_amount;

        return max(0, $revenueBeforeTax - (float) $this->total_cost);
    }

    /**
     * Readable payment method label.
     */
    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'cash' => 'Tunai',
            'qris' => 'QRIS',
            'transfer' => 'Transfer Bank',
            default => ucfirst($this->payment_method),
        };
    }

    /**
     * Badge UI styling for status.
     *
     * @return array{label: string, class: string}
     */
    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'completed' => ['label' => 'Selesai', 'class' => 'badge-success'],
            'cancelled' => ['label' => 'Dibatalkan', 'class' => 'badge-danger'],
            default => ['label' => ucfirst($this->status), 'class' => 'badge-secondary'],
        };
    }
}
