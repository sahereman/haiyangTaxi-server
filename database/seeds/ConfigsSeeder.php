<?php

use Illuminate\Database\Seeder;

use App\Models\Config;

class ConfigsSeeder extends Seeder
{
    private $config_groups =
        [
            //站点设置
            [
                'parent_id' => '0',
                'name' => '站点设置',
                'type' => "group",
                'sort' => 1000,
                'configs' =>
                    [
                        ['name' => '网站标题', 'code' => 'title', 'type' => "text", 'sort' => 10, 'value' => '网站标题',
                            'help' => '默认的网站SEO标题',],
                        ['name' => '网站关键字', 'code' => 'keywords', 'type' => "text", 'sort' => 20],
                        ['name' => '网站描述', 'code' => 'description', 'type' => "text", 'sort' => 30],
                        ['name' => '网站Logo', 'code' => 'logo', 'type' => "image", 'sort' => 40,
                            'help' => '网站首页的Logo',],
                        ['name' => '网站关闭', 'code' => 'site_close', 'type' => "radio", 'sort' => 50,
                            'select_range' => [['value' => 0, 'name' => '开启'], ['value' => 1, 'name' => '关闭']],
                            'help' => '网站开启临时维护时,请关闭站点',
                        ],
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
