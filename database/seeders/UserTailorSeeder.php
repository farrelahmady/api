<?php

namespace Database\Seeders;

use App\Models\ManagementAccess\UserTailorDetail;
use App\Models\User\UserTailor;
use Database\Factories\User\UserTailorFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserTailorSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    UserTailorFactory::new()->count(10)->create()->each(function ($userTailor) {
      if ($userTailor->is_premium) {
        $userTailor->update([
          'max_schedule_slot' => 5,
        ]);
      }
      UserTailorDetail::factory()->create([
        'user_tailor_id' => $userTailor->id,
      ]);
    });
  }
}
