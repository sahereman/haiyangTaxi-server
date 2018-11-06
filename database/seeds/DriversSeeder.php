<?php

use Illuminate\Database\Seeder;
use App\Models\Driver;


class DriversSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * @return void
     */
    public function run()
    {
        // 通过 factory 方法生成 x 个数据并保存到数据库中
        factory(Driver::class, 5)->create();
    }
}
