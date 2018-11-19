<?php

use Illuminate\Database\Seeder;
use App\Models\Driver;
use App\Models\DriverEquipment;

class DriverEquipmentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * @return void
     */
    public function run()
    {
        Driver::all()->each(function (Driver $driver) {

            factory(DriverEquipment::class, random_int(2, 3))->create([
                'driver_id' => $driver->id,
            ]);
        });

        //单独处理第一个数据
        $driver_equ = DriverEquipment::find(1);
        $driver_equ->imei = '123456';
        $driver_equ->save();
    }
}
