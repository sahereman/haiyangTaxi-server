<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\PasswordResetBySmsRequest;
use App\Http\Requests\Api\PasswordResetRequest;
use App\Models\User;
use Dingo\Api\Exception\StoreResourceFailedException;

class PasswordResetController extends Controller
{
    public function resetBySms(PasswordResetBySmsRequest $request)
    {
        $verify_data = \Cache::get($request->verification_key);

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
            throw new StoreResourceFailedException(null, [
                'phone' => '该手机未注册'
            ]);
        }

        $key = 'PasswordReset_' . str_random(15);
        $expiredAt = now()->addMinutes(10);

        // 缓存重置凭证 10分钟过期。
        \Cache::put($key, ['auth' => $user], $expiredAt);

        // 清除验证码缓存
        \Cache::forget($request->verification_key);

        return $this->response->array([
            'key' => $key,
            'expired_at' => $expiredAt->toDateTimeString(),
        ])->setStatusCode(201);
    }


    public function reset(PasswordResetRequest $request)
    {
        $reset_data = \Cache::get($request->reset_key);

        if (!$reset_data)
        {
            throw new StoreResourceFailedException(null, [
                'reset_key' => '重置密码凭证已失效'
            ]);
        }

        $user = User::find($reset_data['auth']->id);
        $user->password = bcrypt($request->password);
        $user->save();

        // 清除reset key缓存
        \Cache::forget($request->reset_key);

        return $this->response->noContent();
    }

}
