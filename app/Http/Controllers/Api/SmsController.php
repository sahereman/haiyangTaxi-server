<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\SmsVerificationRequest;
use Illuminate\Support\Facades\Log;
use Overtrue\EasySms\EasySms;

class SmsController extends Controller
{
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
                    'content' => "【Dima商城】您的验证码是{$code}。如非本人操作，请忽略本短信"
                ]);
            } catch (\Exception $exception)
            {
                Log::error($exception->getException('yunpian'));
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
