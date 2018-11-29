<?php

namespace App\Http\Controllers\Client;

use App\Models\Article;
use App\Transformers\Client\ArticleTransformer;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ArticlesController extends Controller
{
    public function show($slug)
    {
        $article = Article::where('slug', $slug)->first();

        if (!$article)
        {
            throw new NotFoundHttpException();
        }

        return $this->response->item($article, new ArticleTransformer());
    }
}
