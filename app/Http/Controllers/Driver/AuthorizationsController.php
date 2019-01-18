<?php

namespace App\Http\Controllers\Driver;


use App\Http\Requests\Driver\AuthorizationRequest;
use App\Models\Driver;
use App\Models\DriverEquipment;
use App\Models\DriverSocketToken;
use Illuminate\Support\Facades\Auth;

class AuthorizationsController extends Controller
{
    public function store(AuthorizationRequest $request)
    {
        $equ = DriverEquipment::where('imei', $request->imei)->first();
        $driver = Driver::find($equ->driver_id);

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
        $driver_token->expired_at = now()->addMinutes(Auth::guard('driver')->factory()->getTTL());
        $driver_token->save();

        return $this->respondWithToken($token)->setStatusCode(201);
    }


    public function respondWithToken($token)
    {
        return $this->response->array([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => Auth::guard('driver')->factory()->getTTL() * 60 // token有效的时间(单位:秒)
        ]);
    }

}
