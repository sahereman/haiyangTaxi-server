<?php

namespace App\Http\Controllers\Client;

use App\Models\CityHotAddress;
use App\Transformers\Client\CityHotAddressTransformer;
use Dingo\Api\Exception\StoreResourceFailedException;
use Illuminate\Http\Request;

class CityHotAddressesController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->has('city'))
        {
            throw new StoreResourceFailedException(null, [
                'city' => '城市 不能为空'
            ]);
        }

        $addresses = CityHotAddress::where('city', $request->input('city'))->get();


        return $this->response->item($addresses, new CityHotAddressTransformer());
    }
}
