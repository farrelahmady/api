<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Database\Factories\ManagementAccess\UserCustomerDetailFactory;

class UserCustomerDetailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        UserCustomerDetailFactory::new()->count(10)->create()->each(function ($userCustomerDetail) {
            $response = collect(Http::get('https://dev.farizdotid.com/api/daerahindonesia/provinsi')->collect()['provinsi']);
            $data["province"] = $response->random();

            $provinsiId = $data["province"]["id"];
            $data["province"] = $data["province"]["nama"];
            $data["city"] = collect(Http::get("http://dev.farizdotid.com/api/daerahindonesia/kota?id_provinsi=$provinsiId")->collect()['kota_kabupaten'])->random();

            $kotaId = $data["city"]["id"];
            $data["city"] = $data["city"]["nama"];
            $data["district"] = collect(Http::get("http://dev.farizdotid.com/api/daerahindonesia/kecamatan?id_kota=$kotaId")->collect()['kecamatan'])->random()["nama"];

            // var_dump($data);
            $userCustomerDetail->update($data);
        });
    }
}
