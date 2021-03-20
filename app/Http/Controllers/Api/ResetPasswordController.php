<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\ApiPasswordResetTokenModel;
use App\Notifications\ApiPasswordResetNotification;
use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class ResetPasswordController extends ApiController
{
    //Sending Pasword Reset Code To The User.
    public function sendPasswordResetToken(Request $request)
    {
        $rules = [
            'email' => 'required|email|exists:users,email',
        ];

        $this->validate($request, $rules);

        $data = $request->all();

        $user = User::where('email', $data['email'])->first();

        $reset_code_sent = $this->sendPasswordResetCode($user);

        if($reset_code_sent)
        {
            return $this->showMessage(['message' => 'Email has been sent successfully. Please check your email for reset code.']);
        }
    }

    //Validating Password Reset Code For The User.
    public function validatePasswordResetToken(Request $request)
    {
        $rules = [
            'reset_code' => 'required|size:6'
        ];

        $this->validate($request, $rules);

        $reset_token = ApiPasswordResetTokenModel::where([
            ['token_signature', hash('md5', $request->reset_code)],
            ['token_type', ApiPasswordResetTokenModel::PASSWORD_RESET_TOKEN]
        ])->first();
        
        if($reset_token == null || $reset_token->count() <= 0)
        {
            return $this->errorResponse('Invalid password reset code.', 402);
        }

        if(Carbon::now()->greaterThan($reset_token->expires_at))
        {
            return $this->errorResponse('The password reset code given has expired',422);
        }

        $userId = $reset_token->user_id;

        $verificationToken = $this->getVerificationCode($userId);

        if($verificationToken)
        {
            $reset_token->update([
                'expires_at' => Carbon::now()
            ]);

            return $this->showMessage(['message' => 'success', 'verification_token' => $verificationToken], 200);
        }

        return true;
    }


    //Validating Password Verification Code And Resetting New Password For User
    public function resetPassword(Request $request)
    {
        $rules = [
            'password_verification_code' => 'required|size:6',
            'password' => 'required|min:8|confirmed'
        ];

        $this->validate($request, $rules);

        $verification_token = ApiPasswordResetTokenModel::where([
            ['token_signature', hash('md5', $request->password_verification_code)],
            ['token_type', ApiPasswordResetTokenModel::PASSWORD_VERIFICATION_TOKEN],
        ])->first();

        if($verification_token == null || $verification_token->count() <= 0)
        {
            return $this->errorResponse('Invalid token for resetting password.',402);
        }

        $user = User::where('id', $verification_token->user_id)->first();


        if($user == null || $user->count() <= 0)
        {
            return $this->errorResponse('Token dosenot corresponds to any existing user.', 404);
        }
        
        else if(Carbon::now()->greaterThan($verification_token->expires_at))
        {
            return $this->errorResponse('The reset password token has expired.', 403);
        }

        $newPassword = bcrypt($request->password);
        $user->password = $newPassword;
        $user->save();

        $verification_token->update([
            'expires_at' => Carbon::now()
        ]);

        return response()->json(['message' => 'Password reset success', 'code' => 200, 'data' => $user]);
        
    }

    //Helper functions....

    public function sendPasswordResetCode($user)
    {
        $reset_token_initial = Str::random(6);

        $reset_token_final = strtoupper($reset_token_initial);
        
        $signature = hash('md5', $reset_token_final);
        
        try { 
            $user->notify(new ApiPasswordResetNotification($reset_token_final, $user));

            ApiPasswordResetTokenModel::create([
                'user_id' => $user->id,
                'token_signature' => $signature,
                'expires_at' => Carbon::now()->addMinutes(5),
            ]);
        }
        catch(\Throwable $throwable) {
            $this->errorResponse($throwable->getMessage(), 422);
        }

        return true;
    }

    public function getVerificationCode($userId)
    {
        $verification_token_initial = Str::random(6);
        $verification_token_final = strtoupper($verification_token_initial);
        $signature = hash('md5', $verification_token_final);

        try {
            ApiPasswordResetTokenModel::create([
                'user_id' => $userId,
                'token_signature' => $signature,
                'token_type' => ApiPasswordResetTokenModel::PASSWORD_VERIFICATION_TOKEN,
                'used_token' => $userId,
                'expires_at' => Carbon::now()->addMinutes(5),
            ]);

            return $verification_token_final;
        }
        catch(\Throwable $throwable){
            return $this->errorResponse($throwable->getMessage(), 422);
        }
    }

}
