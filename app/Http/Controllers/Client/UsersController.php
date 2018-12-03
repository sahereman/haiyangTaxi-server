<?php

namespace App\Http\Controllers\Client;


use App\Handlers\ImageUploadHandler;
use App\Http\Requests\Client\UserRequest;
use App\Models\User;
use App\Transformers\Client\UserTransformer;
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
            'phone' => $verify_data['phone'],
            'last_active_at' => now(),
        ]);

        // 清除验证码缓存
        \Cache::forget($request->verification_key);

        $authorizations = new AuthorizationsController();

        return $authorizations->respondWithToken(Auth::guard('client')->fromUser($user))->setStatusCode(201);

    }

    public function me()
    {
        $user = Auth::guard('client')->user();

        return $this->response->item($user, new UserTransformer());
    }

    public function update(UserRequest $request, ImageUploadHandler $handler)
    {
        $user = Auth::guard('client')->user();

        $attributes = $request->only(['avatar']);

        if ($request->avatar)
        {
            $attributes['avatar'] = $handler->uploadOriginal($request->avatar, 'avatar/' . date('Ym', now()->timestamp), $request->avatar->hashName());
        }

        $user->update($attributes);

        return $this->response->item($user, new UserTransformer());
    }
}
