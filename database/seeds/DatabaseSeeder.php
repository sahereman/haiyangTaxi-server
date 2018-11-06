<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * @return void
     */
    public function run()
    {
        //系统
        $this->call(AdminTablesSeeder::class);
        $this->call(ConfigsSeeder::class);

        
        //用户
        $this->call(UsersSeeder::class);


        //司机
        $this->call(DriversSeeder::class);
        $this->call(DriverEquipmentsSeeder::class);


        //订单
        $this->call(OrdersSeeder::class);


        //其他
        $this->call(ArticlesSeeder::class);
    }
}
