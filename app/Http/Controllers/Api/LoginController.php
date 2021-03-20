<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LoginController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        //
    }

    /**
     * Authenticating a user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $loginData = $request->validate([
            'email' => 'email|required',
            'password' => 'required'
        ]);

        if(!auth()->attempt($loginData))
        {
            return $this->errorResponse('Your credentials do not match', 401);
        }

        $accessToken = auth()->user()->createToken('authToken',['read-general', 'manage-products', 'purchase-product', 'manage-account'])
        ->accessToken;

        return $this->showMessage(['message' => 'login success', 'access_token' => $accessToken]);
    }
    /**
     * Uset log out
     */
    public function logout()
    {
        if(Auth::check())
        {
            //Revoking Access Token 
            DB::table('oauth_access_tokens')
                ->where('user_id', Auth::user()->id)
                ->latest()
                ->update([
                    'revoked' => true
                ]);

            Auth::logout();    

            return $this->showMessage(['message' => 'Logged out successfully'], 200);
        }

        return $this->showMessage(['message' => 'Already logout']);
    }
}
