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
        $files = collect(Storage::disk('public')->allFiles())->filter(fn ($file) => strpos($file, 'images/tailor/catalog/') !== false)->values();
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
