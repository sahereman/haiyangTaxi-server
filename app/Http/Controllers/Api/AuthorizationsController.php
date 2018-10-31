<?php

namespace App\Http\Controllers\Api;


use App\Http\Requests\Api\AuthorizationRequest;
use Dingo\Api\Exception\StoreResourceFailedException;
use Illuminate\Support\Facades\Auth;

class AuthorizationsController extends Controller
{

    public function store(AuthorizationRequest $request)
    {
        $username = $request->username;

        filter_var($username, FILTER_VALIDATE_EMAIL) ?
            $credentials['email'] = $username :
            $credentials['phone'] = $username;

        $credentials['password'] = $request->password;

        if (!$token = Auth::guard('api')->attempt($credentials))
        {
            throw new StoreResourceFailedException(null, [
                'username' => '用户名或密码错误'
            ]);
        }

        return $this->respondWithToken($token)->setStatusCode(201);
    }

    public function update()
    {
        $token = Auth::guard('api')->refresh();
        return $this->respondWithToken($token);
    }

    public function destroy()
    {
        Auth::guard('api')->logout();
        return $this->response->noContent();
    }

    protected function respondWithToken($token)
    {
        return $this->response->array([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 600 // 600分钟 有效
        ]);
    }

}
