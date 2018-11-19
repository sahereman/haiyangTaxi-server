<?php

namespace App\Http\Requests\Client;


class PasswordResetRequest extends Request
{

    public function rules()
    {
        return [
            'reset_key' => 'required|string',
            'password' => 'required|string|min:6',
        ];
    }

    public function attributes()
    {
        return [
            'reset_key' => '重置密码凭证',
            'password' => '密码',
        ];
    }
}
