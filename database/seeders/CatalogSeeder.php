<?php

namespace Database\Seeders;

use App\Models\User\UserTailor;
use Illuminate\Database\Seeder;
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
        UserTailor::all()->each(function ($tailor) {
            CatalogFactory::new()->count(5)->create([
                'user_tailor_id' => $tailor->uuid,
            ])->each(function ($catalog) {
                CatalogItem::factory()->count(5)->create([
                    'catalog_id' => $catalog->id,
                ]);
            });
        });
    }
}
