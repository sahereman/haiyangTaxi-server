<?php

use Illuminate\Database\Seeder;
use App\Models\CityHotAddress;

class CityHotAddressesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * @return void
     */
    public function run()
    {

        foreach (CityHotAddress::$cityMap as $item)
        {
            factory(CityHotAddress::class, 6)->create([
                'city' => $item
            ]);
        }
    }
}
