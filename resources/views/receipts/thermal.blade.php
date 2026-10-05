@php
    $tx = $receiptData['transaction'];
    $paperClass = ($receiptData['paper_size'] ?? '58mm') === '80mm' ? 'thermal-80mm' : 'thermal-58mm';
@endphp

<div class="thermal-receipt-container {{ $paperClass }} printable-area" id="printable-receipt">
    <div class="receipt-center" style="margin-bottom: 6px;">
        <div style="font-size: 14px; font-weight: bold; text-transform: uppercase;">
            {{ $receiptData['store_name'] }}
        </div>
        @if (!empty($receiptData['store_address']))
            <div style="font-size: 11px; margin-top: 2px;">{{ $receiptData['store_address'] }}</div>
        @endif
        @if (!empty($receiptData['store_phone']))
            <div style="font-size: 11px;">Telp: {{ $receiptData['store_phone'] }}</div>
        @endif
    </div>

    <div class="receipt-double-divider"></div>

    <div class="receipt-row" style="font-size: 11px;">
        <span>No: {{ $tx->transaction_number }}</span>
    </div>
    <div class="receipt-row" style="font-size: 11px;">
        <span>Tgl: {{ $receiptData['formatted_date'] }}</span>
    </div>

    @if ($tx->customer_name || $tx->pet_name)
        <div class="receipt-row" style="font-size: 11px; margin-top: 2px;">
            <span>Pelanggan: {{ $tx->customer_name ?: '-' }}</span>
        </div>
        @if ($tx->pet_name)
            <div class="receipt-row" style="font-size: 11px;">
                <span>Hewan: {{ $tx->pet_name }} {{ $tx->pet_type ? "({$tx->pet_type})" : '' }}</span>
            </div>
        @endif
    @endif

    <div class="receipt-divider"></div>

    <!-- Items List -->
    <div style="margin: 4px 0;">
        @foreach ($tx->items as $item)
            <div class="receipt-item-line">
                @if ($item->item_type === 'service')
                    <span style="font-size: 10px; border: 1px solid #000; padding: 0 2px; margin-right: 2px;">JASA</span>
                @endif
                {{ $item->item_name }}
            </div>
            <div class="receipt-sub-row">
                <span>{{ $item->quantity }} x {{ number_format($item->unit_price, 0, ',', '.') }}</span>
                <span>{{ number_format($item->subtotal, 0, ',', '.') }}</span>
            </div>
        @endforeach
    </div>

    <div class="receipt-divider"></div>

    <!-- Subtotals, Discount, Tax, Grand Total -->
    <div class="receipt-row">
        <span>Subtotal</span>
        <span>{{ number_format($tx->subtotal, 0, ',', '.') }}</span>
    </div>

    @if ($tx->discount_amount > 0)
        <div class="receipt-row">
            <span>Diskon {{ $tx->discount_type === 'percent' ? "({$tx->discount_value}%)" : '' }}</span>
            <span>-{{ number_format($tx->discount_amount, 0, ',', '.') }}</span>
        </div>
    @endif

    @if ($tx->tax_amount > 0)
        <div class="receipt-row">
            <span>Pajak ({{ $tx->tax_percentage }}%)</span>
            <span>{{ number_format($tx->tax_amount, 0, ',', '.') }}</span>
        </div>
    @endif

    <div class="receipt-double-divider"></div>

    <div class="receipt-row" style="font-size: 13px; font-weight: bold;">
        <span>TOTAL</span>
        <span>{{ $receiptData['currency'] }} {{ number_format($tx->grand_total, 0, ',', '.') }}</span>
    </div>

    <div class="receipt-row" style="margin-top: 2px;">
        <span>Bayar ({{ $tx->payment_method_label }})</span>
        <span>{{ number_format($tx->payment_amount, 0, ',', '.') }}</span>
    </div>

    @if ($tx->payment_method === 'cash')
        <div class="receipt-row" style="font-weight: bold;">
            <span>Kembalian</span>
            <span>{{ number_format($tx->change_amount, 0, ',', '.') }}</span>
        </div>
    @endif

    <div class="receipt-double-divider"></div>

    @if (!empty($receiptData['receipt_footer']))
        <div class="receipt-center" style="margin-top: 6px; font-size: 11px; white-space: pre-line;">
            {{ $receiptData['receipt_footer'] }}
        </div>
    @endif

    <div class="receipt-center" style="margin-top: 8px; font-size: 10px; color: #555;">
        *** LUNAS ***
    </div>
</div>
