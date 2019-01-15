<?php

namespace App\Http\Controllers\Driver;


use App\Http\Requests\Driver\AuthorizationRequest;
use App\Models\DriverEquipment;
use App\Models\DriverSocketToken;
use Illuminate\Support\Facades\Auth;

class AuthorizationsController extends Controller
{
    public $driver_ttl = 480;  //司机端token有效时间 单位:(分钟)

    public function store(AuthorizationRequest $request)
    {
        $equ = DriverEquipment::where('imei', $request->imei)->first();
        $driver = $equ->driver;

        $driver->update([
            'name' => $equ->name,
            'phone' => $equ->phone,
            'last_active_at' => now(),
        ]);

        $token = Auth::guard('driver')->login($driver);

        // 加入DriverSocketToken表
        DriverSocketToken::where('driver_id', $driver->id)->delete();

        $driver_token = new DriverSocketToken();
        $driver_token->token = $token;
        $driver_token->driver()->associate($driver);
        $driver_token->expired_at = now()->addMinutes($this->driver_ttl);
        $driver_token->save();

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
