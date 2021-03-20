<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\Verified;

class EmailVerificationController extends ApiController
{
    public function __construct()
    {
        $this->middleware(['auth'])->only(['resend']);

        $this->middleware('auth:api');
    }

    /**
     * Mark the authenticated user's email address as verified.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function verify(Request $request)
    {
        if(! hash_equals((string) $request->route('id'), (string) $request->user()->getKey()))
        {
            throw new AuthorizationException;
        }

        if(! hash_equals((string) $request->route('hash'), sha1($request->user()->getEmailForVerification())))
        {
            throw new AuthorizationException;
        }

        if($request->user()->hasVerifiedEmail())
        {
            return $this->showMessage(['message' => 'User email has been already verified.'], 200);
        }

        if($request->user()->markEmailAsVerified())
        {
            event(new Verified($request->user()));
        }

        return $this->showMessage(['message' => 'User email has been verified.'], 200);
    }

    /**
     * Resend the email verification notification.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function resend(Request $request)
    {
        if($request->user()->hasVerifiedEmail())
        {
            return $this->showMessage(['message' => 'User email has been already verified.'], 422);
        }

        $request->user()->sendEmailVerificationNotification();

        return $this->showMessage(['message' => 'Email verification link has been sent to your email. Please check your email to verify.']);
    }
}
