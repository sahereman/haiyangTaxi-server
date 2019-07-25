<?php

namespace App\Http\Controllers;

use App\Models\img;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function img(img $img)
    {
        return view('img',[
            'img' => $img
        ]);

    }

    public function demo()
    {
        if(isMobile())
        {
            return "<center><h1>请使用电脑访问</h1></center>";
        }

        return view('demo');
    }
}
