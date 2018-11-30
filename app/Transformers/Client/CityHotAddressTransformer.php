<?php

namespace App\Transformers\Client;

use App\Models\CityHotAddress;
use League\Fractal\TransformerAbstract;

class CityHotAddressTransformer extends TransformerAbstract
{
    public function transform(CityHotAddress $address)
    {
        return [
            'city' => $address->city,
            'address' => $address->address,
            'location' => $address->location,
        ];
    }
}