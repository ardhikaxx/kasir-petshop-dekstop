<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Service;
use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Initial Store Settings
        $defaultSettings = [
            'store_name' => 'Pet Care & Shop',
            'store_address' => 'Jl. Flamboyan No. 18, Jakarta',
            'store_phone' => '0812-3456-7890',
            'store_email' => 'info@petcareshop.local',
            'receipt_footer' => "Terima kasih atas kunjungan Anda!\nKesehatan & kebahagiaan anabul adalah prioritas kami.",
            'receipt_paper_size' => '58mm',
            'tax_enabled' => '0',
            'tax_percentage' => '0',
            'currency' => 'Rp',
            'timezone' => 'Asia/Jakarta',
        ];

        foreach ($defaultSettings as $key => $value) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        // 2. Initial Categories
        $categories = [
            ['name' => 'Kucing', 'slug' => 'kucing', 'type' => 'product', 'description' => 'Perlengkapan, pakan, & kebutuhan kucing'],
            ['name' => 'Anjing', 'slug' => 'anjing', 'type' => 'product', 'description' => 'Perlengkapan, pakan, & kebutuhan anjing'],
            ['name' => 'Burung', 'slug' => 'burung', 'type' => 'product', 'description' => 'Pakan & perlengkapan burung'],
            ['name' => 'Ikan & Akuarium', 'slug' => 'ikan', 'type' => 'product', 'description' => 'Pakan ikan & aksesoris akuarium'],
            ['name' => 'Hewan Kecil & Kelinci', 'slug' => 'hewan-kecil', 'type' => 'product', 'description' => 'Kebutuhan hamster, kelinci, guinea pig'],
            ['name' => 'Makanan Hewan', 'slug' => 'makanan-hewan', 'type' => 'product', 'description' => 'Dry food, wet food, treat & snack'],
            ['name' => 'Obat & Vitamin', 'slug' => 'obat-vitamin', 'type' => 'product', 'description' => 'Vitamin bulu, obat kutu, obat cacing, suplemen'],
            ['name' => 'Pasir & Kebersihan', 'slug' => 'pasir-kebersihan', 'type' => 'product', 'description' => 'Pasir gumpal, tofu cat litter, shampo & spray'],
            ['name' => 'Aksesori & Mainan', 'slug' => 'aksesori-mainan', 'type' => 'product', 'description' => 'Kalung, tali harness, mainan gigit, scratcher'],
            ['name' => 'Kandang & Tas Hewan', 'slug' => 'kandang-tas', 'type' => 'product', 'description' => 'Kandang lipat, pet cargo, kasur hewan'],
            ['name' => 'Layanan & Grooming', 'slug' => 'layanan-grooming', 'type' => 'service', 'description' => 'Layanan mandi, potong kuku, salon hewan'],
            ['name' => 'Penitipan & Pet Hotel', 'slug' => 'penitipan-pet-hotel', 'type' => 'service', 'description' => 'Penitipan harian hewan kesayangan'],
        ];

        foreach ($categories as $cat) {
            Category::query()->updateOrCreate(
                ['slug' => $cat['slug']],
                $cat
            );
        }

        // 3. Initial Pet Shop Services
        $groomingCategory = Category::where('slug', 'layanan-grooming')->first();
        $hotelCategory = Category::where('slug', 'penitipan-pet-hotel')->first();

        $services = [
            [
                'category_id' => $groomingCategory?->id,
                'code' => 'SRV-001',
                'name' => 'Mandi Sehat Kucing',
                'price' => 50000,
                'cost_price' => 10000,
                'estimated_duration' => '45 Menit',
                'description' => 'Mandi bersih, potong kuku, pembersihan telinga',
                'is_active' => true,
            ],
            [
                'category_id' => $groomingCategory?->id,
                'code' => 'SRV-002',
                'name' => 'Grooming Lengkap Kucing (Kutu/Jamur)',
                'price' => 75000,
                'cost_price' => 15000,
                'estimated_duration' => '60 Menit',
                'description' => 'Mandi anti kutu/jamur, potong kuku, sisir bulu gimbal',
                'is_active' => true,
            ],
            [
                'category_id' => $groomingCategory?->id,
                'code' => 'SRV-003',
                'name' => 'Mandi Sehat Anjing Kecil',
                'price' => 65000,
                'cost_price' => 12000,
                'estimated_duration' => '60 Menit',
                'description' => 'Mandi bersih, potong kuku, pembersihan telinga anjing ras kecil',
                'is_active' => true,
            ],
            [
                'category_id' => $groomingCategory?->id,
                'code' => 'SRV-004',
                'name' => 'Potong Kuku & Rapikan Bulu Telapak',
                'price' => 25000,
                'cost_price' => 0,
                'estimated_duration' => '20 Menit',
                'description' => 'Potong kuku dan perapihan bulu telapak kaki',
                'is_active' => true,
            ],
            [
                'category_id' => $hotelCategory?->id,
                'code' => 'SRV-005',
                'name' => 'Penitipan Hewan (Pet Hotel / Hari)',
                'price' => 60000,
                'cost_price' => 15000,
                'estimated_duration' => '1 Hari',
                'description' => 'Penitipan harian ber-AC termasuk pemantauan dan makan',
                'is_active' => true,
            ],
        ];

        foreach ($services as $srv) {
            Service::query()->updateOrCreate(
                ['code' => $srv['code']],
                $srv
            );
        }
    }
}
