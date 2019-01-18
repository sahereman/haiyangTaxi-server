<?php

use Illuminate\Database\Seeder;

use App\Models\Config;

class ConfigsSeeder extends Seeder
{
    private $config_groups =
        [
            //站点设置
            [
                'name' => '站点设置',
                'type' => "group",
                'sort' => 1000,
                'configs' =>
                    [
                        ['name' => '网站标题', 'code' => 'title', 'type' => "text", 'sort' => 10, 'value' => '网站标题',
                            'help' => '默认的网站SEO标题',],
                        ['name' => '网站关键字', 'code' => 'keywords', 'type' => "text", 'sort' => 20],
                        ['name' => '网站描述', 'code' => 'description', 'type' => "text", 'sort' => 30],
                        ['name' => '司机端 Android Apk', 'code' => 'driver_android_apk', 'type' => "file", 'sort' => 40],
                        ['name' => '小程序二维码', 'code' => 'client_qrcode', 'type' => "image", 'sort' => 50],

                        //                        ['name' => '网站关闭', 'code' => 'site_close', 'type' => "radio", 'sort' => 50,
                        //                            'select_range' => [['value' => 0, 'name' => '开启'], ['value' => 1, 'name' => '关闭']],
                        //                            'help' => '网站开启临时维护时,请关闭站点',
                        //                        ],
                    ]
            ],

            //订单通知
            [
                'name' => '订单通知',
                'type' => "group",
                'sort' => 2000,
                'configs' =>
                    [
                        ['name' => '第一次通知司机', 'code' => 'order_notify_1', 'type' => "number", 'sort' => 10, 'value' => '500',
                            'help' => '第一次通知订单距离最近的司机(单位:米)'],
                        ['name' => '第二次通知司机', 'code' => 'order_notify_2', 'type' => "number", 'sort' => 20, 'value' => '1000',
                            'help' => '第二次通知距离第一次通知之后的司机(单位:米)'],
                        ['name' => '第三次通知司机', 'code' => 'order_notify_3', 'type' => "number", 'sort' => 30, 'value' => '2000',
                            'help' => '第三次通知距离第二次通知之后的司机(单位:米)'],
                        ['name' => '最后次通知司机', 'code' => 'order_notify_4', 'type' => "number", 'sort' => 40, 'value' => '9999',
                            'help' => '最后次通知距离第三次通知之后的司机(单位:米)'],
                        ['name' => '通知间隔时间', 'code' => 'order_notify_interval', 'type' => "number", 'sort' => 50, 'value' => '5',
                            'help' => '每一次通知距上一次通知的延迟时间,首次通知无延迟(单位:秒)'],
                    ]
            ],


            //站点设置2
            //            [
            //                'name' => '站点设置2',
            //                'type' => "group",
            //                'sort' => 2000,
            //                'configs' =>
            //                    [
            //                        ['name' => '网站标题', 'code' => 'title2', 'type' => "text", 'sort' => 10, 'value' => '网站标题'],
            //                        ['name' => '网站关键字', 'code' => 'keywords2', 'type' => "text", 'sort' => 20],
            //                        ['name' => '网站描述', 'code' => 'description2', 'type' => "text", 'sort' => 30],
            //                        ['name' => '网站Logo', 'code' => 'logo2', 'type' => "image", 'sort' => 40],
            //                        ['name' => '网站关闭', 'code' => 'site_close2', 'type' => "radio", 'sort' => 50,
            //                            'select_range' => [['value' => 0, 'name' => '开启'], ['value' => 1, 'name' => '关闭']],
            //                        ],
            //                    ]
            //            ],
        ];

    public function run()
    {
        Config::truncate();
        Cache::forget(Config::$cache_key);

        foreach ($this->config_groups as $item)
        {
            $group = Config::create(array_except($item, 'configs'));

            foreach ($item['configs'] as $config)
            {
                Config::create(array_merge($config, ['parent_id' => $group->id]));
            }
        }

    }
}
