<?php

namespace App\Http\Controllers\Client;


use App\Handlers\ImageUploadHandler;
use App\Http\Requests\Client\UserRequest;
use App\Models\User;
use App\Transformers\UserTransformer;
use Dingo\Api\Exception\StoreResourceFailedException;
use Illuminate\Support\Facades\Auth;

class UsersController extends Controller
{
    public function store(UserRequest $request)
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

        if (User::where('phone', $verify_data['phone'])->first())
        {
            throw new StoreResourceFailedException(null, [
                'phone' => '手机号已注册'
            ]);
        }


        $user = User::create([
            'name' => $request->name,
            'phone' => $verify_data['phone'],
            'password' => bcrypt($request->password),
        ]);

        // 清除验证码缓存
        \Cache::forget($request->verification_key);

        return $this->response->item($user, new UserTransformer())
            ->setMeta([
                'access_token' => Auth::guard('api')->fromUser($user),
                'token_type' => 'Bearer',
                'expires_in' => Auth::guard('api')->factory()->getTTL() * 60
            ])
            ->setStatusCode(201);
    }

    public function me()
    {
        return $this->response->item($this->user(), new UserTransformer());
    }

    public function update(UserRequest $request, ImageUploadHandler $handler)
    {
        $user = $this->user();

        $attributes = $request->only(['name', 'email', 'avatar']);

        if ($request->avatar)
        {
            $attributes['avatar'] = $handler->uploadOriginal($request->avatar, 'avatar/' . date('Ym', now()->timestamp), $request->avatar->hashName());
        }

        $user->update($attributes);

        return $this->response->item($user, new UserTransformer());
    }
}
