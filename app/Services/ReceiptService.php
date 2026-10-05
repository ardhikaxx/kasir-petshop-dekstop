<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Transaction;
use Carbon\Carbon;

class ReceiptService
{
    /**
     * Format receipt data for view rendering.
     *
     * @return array<string, mixed>
     */
    public function formatReceiptData(Transaction $transaction): array
    {
        $transaction->load('items');

        $storeName = Setting::get('store_name', 'Pet Care & Shop');
        $storeAddress = Setting::get('store_address', 'Jl. Flamboyan No. 18, Jakarta');
        $storePhone = Setting::get('store_phone', '0812-3456-7890');
        $receiptFooter = Setting::get('receipt_footer', 'Terima kasih atas kunjungan Anda!');
        $paperSize = Setting::get('receipt_paper_size', '58mm');
        $currency = Setting::get('currency', 'Rp');

        return [
            'store_name' => $storeName,
            'store_address' => $storeAddress,
            'store_phone' => $storePhone,
            'receipt_footer' => $receiptFooter,
            'paper_size' => $paperSize,
            'currency' => $currency,
            'transaction' => $transaction,
            'formatted_date' => Carbon::parse($transaction->transaction_date)
                ->timezone('Asia/Jakarta')
                ->isoFormat('D MMMM Y, HH:mm').' WIB',
        ];
    }

    /**
     * Format receipt as mono-spaced plain text for clipboard copying or offline messaging.
     */
    public function formatPlainText(Transaction $transaction): string
    {
        $transaction->load('items');

        $storeName = Setting::get('store_name', 'PET CARE & SHOP');
        $storeAddress = Setting::get('store_address', 'Jl. Flamboyan No. 18, Jakarta');
        $storePhone = Setting::get('store_phone', '0812-3456-7890');
        $receiptFooter = Setting::get('receipt_footer', 'Terima kasih atas kunjungan Anda!');

        $dateStr = Carbon::parse($transaction->transaction_date)
            ->timezone('Asia/Jakarta')
            ->format('d/m/Y H:i').' WIB';

        $width = 32; // standard 58mm width
        $line = str_repeat('-', $width);
        $doubleLine = str_repeat('=', $width);

        $out = [];
        $out[] = str_pad($storeName, $width, ' ', STR_PAD_BOTH);
        if ($storeAddress) {
            $out[] = str_pad($storeAddress, $width, ' ', STR_PAD_BOTH);
        }
        if ($storePhone) {
            $out[] = str_pad("Telp: {$storePhone}", $width, ' ', STR_PAD_BOTH);
        }
        $out[] = $doubleLine;

        $out[] = "No    : {$transaction->transaction_number}";
        $out[] = "Tgl   : {$dateStr}";
        $out[] = 'Kasir : Offline Desktop';

        if ($transaction->customer_name || $transaction->pet_name) {
            $custInfo = $transaction->customer_name ?: '-';
            if ($transaction->pet_name) {
                $custInfo .= ' ('.$transaction->pet_name.($transaction->pet_type ? " - {$transaction->pet_type}" : '').')';
            }
            $out[] = "Pelanggan: {$custInfo}";
        }

        $out[] = $line;

        // Items
        foreach ($transaction->items as $item) {
            $typeTag = $item->item_type === 'service' ? '[Jasa]' : '';
            $out[] = trim("{$typeTag} {$item->item_name}");

            $qtyPrice = " {$item->quantity} x ".number_format($item->unit_price, 0, ',', '.');
            $subtotalStr = number_format($item->subtotal, 0, ',', '.');
            $spaceCount = max(1, $width - strlen($qtyPrice) - strlen($subtotalStr));
            $out[] = $qtyPrice.str_repeat(' ', $spaceCount).$subtotalStr;
        }

        $out[] = $line;

        // Calculations
        $formatRow = function (string $label, float $amount) use ($width): string {
            $amtStr = number_format($amount, 0, ',', '.');
            $spaceCount = max(1, $width - strlen($label) - strlen($amtStr));

            return $label.str_repeat(' ', $spaceCount).$amtStr;
        };

        $out[] = $formatRow('Subtotal', (float) $transaction->subtotal);

        if ($transaction->discount_amount > 0) {
            $discLabel = 'Diskon';
            if ($transaction->discount_type === 'percent') {
                $discLabel .= " ({$transaction->discount_value}%)";
            }
            $out[] = $formatRow($discLabel, -(float) $transaction->discount_amount);
        }

        if ($transaction->tax_amount > 0) {
            $out[] = $formatRow("Pajak ({$transaction->tax_percentage}%)", (float) $transaction->tax_amount);
        }

        $out[] = $doubleLine;
        $out[] = $formatRow('TOTAL', (float) $transaction->grand_total);
        $out[] = $formatRow('Bayar ('.$transaction->payment_method_label.')', (float) $transaction->payment_amount);

        if ($transaction->payment_method === 'cash') {
            $out[] = $formatRow('Kembalian', (float) $transaction->change_amount);
        }

        $out[] = $doubleLine;
        $footerLines = explode("\n", $receiptFooter);
        foreach ($footerLines as $fLine) {
            $out[] = str_pad(trim($fLine), $width, ' ', STR_PAD_BOTH);
        }

        return implode("\n", $out);
    }
}
