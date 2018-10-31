<?php

namespace App\Http\Requests\Api;


class PasswordResetBySmsRequest extends Request
{

    public function rules()
    {
        return [
            'verification_key' => 'required|string',
            'verification_code' => 'required|string',
        ];
    }

    public function attributes()
    {
        return [
            'verification_key' => '短信验证码 key',
            'verification_code' => '短信验证码',
        ];
    }
}
