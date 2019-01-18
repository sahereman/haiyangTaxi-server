<?php

namespace App\Http\Controllers\Client;


use App\Http\Requests\Client\AuthorizationRequest;
use App\Models\User;
use App\Models\UserSocketToken;
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
                'verification_code' => '验证码错误'
            ]);
        }

        $user = User::where('phone', $verify_data['phone'])->first();

        if (!$user)
        {
            $user = User::create([
                'phone' => $verify_data['phone'],
                'last_active_at' => now(),
            ]);
        } else
        {
            $user->update([
                'last_active_at' => now(),
            ]);
        }

        // 清除验证码缓存
        Cache::forget($request->verification_key);

        $token = Auth::guard('client')->login($user);

        // 加入UserSocketToken表
        UserSocketToken::where('user_id', $user->id)->delete();

        $user_token = new UserSocketToken();
        $user_token->token = $token;
        $user_token->user()->associate($user);
        $user_token->expired_at = now()->addMinutes(Auth::guard('client')->factory()->getTTL());
        $user_token->save();

        return $this->respondWithToken($token)->setStatusCode(201);
    }

    public function update()
    {
        $token = Auth::guard('client')->refresh();

        $user = Auth::guard('client')->setToken($token)->user();

        // 加入UserSocketToken表
        UserSocketToken::where('user_id', $user->id)->delete();

        $user_token = new UserSocketToken();
        $user_token->token = $token;
        $user_token->user()->associate($user);
        $user_token->expired_at = now()->addMinutes(Auth::guard('client')->factory()->getTTL());
        $user_token->save();

        return $this->respondWithToken($token);
    }

    public function destroy()
    {
        Auth::guard('client')->logout();
        return $this->response->noContent();
    }

    public function respondWithToken($token)
    {
        return $this->response->array([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => Auth::guard('client')->factory()->getTTL() * 60 // token有效的时间(单位:秒)
        ]);
    }

}
