<?php

namespace App\Http\Requests\Client;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserRequest extends Request
{

    public function rules()
    {
        if ($this->routeIs('api.users.store'))
        {
            return [
                'name' => 'required|string|max:255',
                'password' => 'required|string|min:6',
                'verification_key' => 'required|string',
                'verification_code' => 'required|string',
            ];
        } elseif ($this->routeIs('api.users.update'))
        {
            return [
                'name' => [
                    'string', 'max:255',
                    Rule::unique('users')->ignore(Auth::guard('api')->id())
                ],
                'email' => 'email',
                'avatar' => 'image',
            ];
        } else
        {
            throw new NotFoundHttpException();
        }
    }

    public function attributes()
    {
        return [
            'name' => '用户名',
            'email' => '邮箱',
            'avatar' => '头像',
            'password' => '密码',
            'verification_key' => '短信验证码 key',
            'verification_code' => '短信验证码',
        ];
    }
}