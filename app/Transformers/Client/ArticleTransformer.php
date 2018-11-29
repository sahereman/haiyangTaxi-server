<?php

namespace App\Transformers\Client;

use App\Models\Article;
use League\Fractal\TransformerAbstract;

class ArticleTransformer extends TransformerAbstract
{
    public function transform(Article $article)
    {
        return [
            'id' => $article->id,
            'name' => $article->name,
            'slug' => $article->slug,
            'content' => $article->content,
            'created_at' => $article->created_at->toDateTimeString(),
        ];
    }
}