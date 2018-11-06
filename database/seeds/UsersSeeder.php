<?php

use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 通过 factory 方法生成 x 个数据并保存到数据库中
        factory(\App\Models\User::class, 5)->create();


        //单独处理第一个用户的数据
        $user = \App\Models\User::find(1);
        $user->phone = '18600982820';
        $user->save();
    }
}
