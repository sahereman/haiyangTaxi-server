<?php

namespace App\Http\Controllers\Driver;


use App\Http\Requests\Driver\AuthorizationRequest;
use App\Models\DriverEquipment;
use Illuminate\Support\Facades\Auth;

class AuthorizationsController extends Controller
{
    public $driver_ttl = 480;  //司机端token有效时间 单位:(分钟)

    public function store(AuthorizationRequest $request)
    {
        $driver = DriverEquipment::where('imei', $request->imei)->first()->driver;

        $driver->update([
            'last_active_at' => now(),
        ]);

        $token = Auth::guard('driver')->login($driver);

        return $this->respondWithToken($token)->setStatusCode(201);
    }


    public function respondWithToken($token)
    {
        return $this->response->array([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $this->driver_ttl * 60 // token有效的时间(单位:秒)
        ]);
    }

}
