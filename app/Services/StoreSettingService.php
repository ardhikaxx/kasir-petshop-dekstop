<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StoreSettingService
{
    /**
     * Default settings dictionary.
     *
     * @var array<string, string>
     */
    protected array $defaults = [
        'store_name' => 'Pet Care & Shop',
        'store_address' => 'Jl. Flamboyan No. 18, Jakarta',
        'store_phone' => '0812-3456-7890',
        'store_email' => 'info@petcareshop.local',
        'store_logo' => '',
        'receipt_footer' => "Terima kasih atas kunjungan Anda!\nKesehatan & kebahagiaan anabul adalah prioritas kami.",
        'receipt_paper_size' => '58mm',
        'tax_enabled' => '0',
        'tax_percentage' => '0',
        'currency' => 'Rp',
        'timezone' => 'Asia/Jakarta',
    ];

    /**
     * Get all store settings as key-value pairs with defaults.
     *
     * @return array<string, string>
     */
    public function getAll(): array
    {
        $saved = Setting::query()->pluck('value', 'key')->toArray();

        return array_merge($this->defaults, $saved);
    }

    /**
     * Get a specific setting value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default ?? ($this->defaults[$key] ?? null));
    }

    /**
     * Update multiple settings.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateMany(array $data): void
    {
        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }
    }

    /**
     * Save store logo file locally and update setting.
     */
    public function saveLogo(UploadedFile $file): string
    {
        $existing = Setting::get('store_logo');
        if ($existing && Storage::disk('public')->exists($existing)) {
            Storage::disk('public')->delete($existing);
        }

        $path = $file->store('logos', 'public');
        Setting::set('store_logo', $path);

        return $path;
    }
}
