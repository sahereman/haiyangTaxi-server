<?php

namespace App\Http\Controllers\Client;

use App\Http\Requests\Client\SmsVerificationRequest;
use Illuminate\Support\Facades\Log;
use Overtrue\EasySms\EasySms;

class SmsController extends Controller
{
    public $verification_templet_code = 'SMS_151996714';

    /**
     * @param SmsVerificationRequest $request
     * @param EasySms $easySms
     * @throws \Exception
     */
    public function verification(SmsVerificationRequest $request, EasySms $easySms)
    {
        $phone = $request->phone;

        if (!app()->environment('production'))
        {
            $code = '1234';
        } else
        {
            // 生成4位随机数，左侧补0
            $code = str_pad(random_int(1, 9999), 4, 0, STR_PAD_LEFT);

            try
            {
                $result = $easySms->send($phone, [
                    'content' => "您的验证码为：{$code}，该验证码 5 分钟内有效，请勿泄漏于他人。",
                    'template' => $this->verification_templet_code,
                    'data' => [
                        'code' => $code
                    ],
                ]);
            } catch (\Exception $exception)
            {
                Log::error($exception->getException('aliyun'));
                return $this->response->errorInternal('短信发送异常');
            }
        }

        $key = 'SmsVerification_' . str_random(15);
        $expiredAt = now()->addMinutes(10);
        // 缓存验证码 10分钟过期。
        \Cache::put($key, ['phone' => $phone, 'code' => $code], $expiredAt);

        return $this->response->array([
            'key' => $key,
            'expired_at' => $expiredAt->toDateTimeString(),
        ])->setStatusCode(201);
    }
}
