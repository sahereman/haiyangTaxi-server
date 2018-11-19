<?php

namespace App\Http\Controllers\Client;


use App\Http\Requests\Client\AuthorizationRequest;
use App\Models\User;
use Dingo\Api\Exception\StoreResourceFailedException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class AuthorizationsController extends Controller
{

    public function store(AuthorizationRequest $request)
    {
        $verify_data = Cache::get($request->verification_key);

        if (!$verify_data)
        {
            throw new StoreResourceFailedException(null, [
                'verification_key' => '验证码已失效'
            ]);
        }

        if (!hash_equals($verify_data['code'], $request->verification_code))
        {
            throw new StoreResourceFailedException(null, [
                'verification_c200ode' => '验证码错误'
            ]);
        }

        $user = User::where('phone', $verify_data['phone'])->first();

        if (!$user)
        {
            throw new StoreResourceFailedException(null, [
                'phone' => '该手机未注册'
            ]);
        }

        // 清除验证码缓存
        Cache::forget($request->verification_key);

        $token = Auth::guard('client')->login($user);

        return $this->respondWithToken($token)->setStatusCode(201);
    }

    public function update()
    {
        $token = Auth::guard('client')->refresh();
        return $this->respondWithToken($token);
    }

    public function destroy()
    {
        Auth::guard('client')->logout();
        return $this->response->noContent();
    }

    protected function respondWithToken($token)
    {
        return $this->response->array([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => Auth::guard('client')->factory()->getTTL() * 60 // 60分钟 有效
        ]);
    }

}
