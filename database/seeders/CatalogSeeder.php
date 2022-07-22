<?php

namespace Database\Seeders;

use App\Models\User\UserTailor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use App\Models\ManagementAccess\Catalog;
use App\Models\ManagementAccess\CatalogItem;
use Database\Factories\ManagementAccess\CatalogFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $files = collect([
            "lower" => [
                "celanaPendek" => collect(Storage::disk('public')->allFiles())->filter(fn ($file) => strpos($file, 'images/tailor/catalog/lower/celana-pendek') !== false)->values(),
                "jeans" => collect(Storage::disk('public')->allFiles())->filter(fn ($file) => strpos($file, 'images/tailor/catalog/lower/jeans') !== false)->values(),

            ],
            "upper" => [
                "batik" => collect(Storage::disk('public')->allFiles())->filter(fn ($file) => strpos($file, 'images/tailor/catalog/upper/batik') !== false)->values(),
                "kaos" => collect(Storage::disk('public')->allFiles())->filter(fn ($file) => strpos($file, 'images/tailor/catalog/upper/kaos') !== false)->values(),
                "hoodie" => collect(Storage::disk('public')->allFiles())->filter(fn ($file) => strpos($file, 'images/tailor/catalog/upper/hoodie') !== false)->values()

            ]
        ]);

        // $files['lower']['celanaPendek'] = collect(Storage::disk('public')->allFiles())->filter(fn ($file) => strpos($file, 'images/tailor/catalog/lower/celana-pendek') !== false)->values();
        // $files['lower']['jeans'] = collect(Storage::disk('public')->allFiles())->filter(fn ($file) => strpos($file, 'images/tailor/catalog/lower/jeans') !== false)->values();
        // $files['upper']['batik'] = collect(Storage::disk('public')->allFiles())->filter(fn ($file) => strpos($file, 'images/tailor/catalog/upper/batik') !== false)->values();
        // $files['upper']['hoodie'] = collect(Storage::disk('public')->allFiles())->filter(fn ($file) => strpos($file, 'images/tailor/catalog/upper/hoodie') !== false)->values();
        // $files['upper']['kaos'] = collect(Storage::disk('public')->allFiles())->filter(fn ($file) => strpos($file, 'images/tailor/catalog/upper/kaos') !== false)->values();

        $keys = $files->keys();
        // var_dump($keys);
        UserTailor::all()->each(function ($tailor) {
            CatalogFactory::new()->count(rand(1, 5))->create([
                'user_tailor_id' => $tailor->uuid,
            ])->each(function ($catalog) {
                CatalogItem::factory()->count(5)->create([
                    'catalog_id' => $catalog->id,
                ]);
            });
        });
    }
}
