<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SettingRequest extends FormRequest
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
            'store_name' => ['required', 'string', 'max:150'],
            'store_address' => ['nullable', 'string', 'max:300'],
            'store_phone' => ['nullable', 'string', 'max:50'],
            'store_email' => ['nullable', 'email', 'max:100'],
            'receipt_footer' => ['nullable', 'string', 'max:500'],
            'receipt_paper_size' => ['required', 'in:58mm,80mm'],
            'tax_enabled' => ['nullable', 'boolean'],
            'tax_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'currency' => ['nullable', 'string', 'max:10'],
            'timezone' => ['nullable', 'string', 'max:50'],
            'store_logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
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
            'store_name' => 'Nama Toko Pet Shop',
            'store_address' => 'Alamat Toko',
            'store_phone' => 'Nomor Telepon Toko',
            'store_email' => 'Email Toko',
            'receipt_footer' => 'Catatan Kaki Struk',
            'receipt_paper_size' => 'Ukuran Kertas Struk',
            'tax_enabled' => 'Status Pajak',
            'tax_percentage' => 'Persentase Pajak',
            'currency' => 'Mata Uang',
            'store_logo' => 'Logo Toko',
        ];
    }
}
