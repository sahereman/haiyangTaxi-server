<?php

use Illuminate\Database\Seeder;
use App\Models\Article;


class ArticlesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * @return void
     */
    public function run()
    {
        $slug_arr = [
            ['关于我们', 'about'],
            ['礼品活动', 'event'],
        ];

        foreach ($slug_arr as $item)
        {
            factory(Article::class)->create([
                'name' => $item[0],
                'slug' => $item[1]
            ]);
        }
    }
}
