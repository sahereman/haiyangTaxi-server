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

        foreach (Article::$slugMap as $key => $item)
        {
            factory(Article::class)->create([
                'name' => $item,
                'slug' => $key
            ]);
        }
    }
}
