<?php

namespace App\Http\Requests\Driver;


use Illuminate\Validation\Rule;

class AuthorizationRequest extends Request
{

    /**
     * Get the validation rules that apply to the request.
     * @return array
     */
    public function rules()
    {
        return [
            'imei' => [
                'required',
                'string',
                Rule::exists('driver_equipments', 'imei'),
            ],
        ];
    }

    public function attributes()
    {
        return [
            'imei' => 'IMEI设备码',
        ];
    }
}
