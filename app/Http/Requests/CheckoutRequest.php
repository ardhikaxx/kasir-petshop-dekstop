<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer'],
            'items.*.type' => ['required', 'in:product,service'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'discount_type' => ['nullable', 'in:none,percent,fixed'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:cash,qris,transfer'],
            'payment_amount' => ['required', 'numeric', 'min:0'],
            'customer_name' => ['nullable', 'string', 'max:150'],
            'pet_name' => ['nullable', 'string', 'max:150'],
            'pet_type' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Custom attribute names for Indonesian messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'items' => 'Item Keranjang Belanja',
            'items.*.id' => 'ID Item',
            'items.*.type' => 'Tipe Item',
            'items.*.quantity' => 'Jumlah Beli',
            'discount_type' => 'Tipe Diskon',
            'discount_value' => 'Nilai Diskon',
            'payment_method' => 'Metode Pembayaran',
            'payment_amount' => 'Jumlah Uang Dibayar',
            'customer_name' => 'Nama Pelanggan',
            'pet_name' => 'Nama Hewan',
            'pet_type' => 'Jenis Hewan',
            'notes' => 'Catatan Transaksi',
        ];
    }
}
