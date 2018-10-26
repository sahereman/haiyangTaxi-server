<?php

namespace App\Admin\Controllers;

use App\Handlers\ImageUploadHandler;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WangEditorController extends Controller
{

    public function images(Request $request, ImageUploadHandler $uploader)
    {
        $data = $this->validate($request, [
            'images.*' => 'required|image|mimes:jpeg,png,gif',
        ], [], [
            'images.*' => '图片',
        ]);

        $paths = array();
        foreach ($data['images'] as $item)
        {
            $paths[] = \Storage::url($uploader->uploadOriginal($item));
        }

        return response()->json([
            'errno' => 0,
            'data' => $paths
        ]);
    }

}
