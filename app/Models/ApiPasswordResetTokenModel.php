<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiPasswordResetTokenModel extends Model
{
    use HasFactory;

    const PASSWORD_RESET_TOKEN = 10;

    const PASSWORD_VERIFICATION_TOKEN = 20;

    protected $table = 'api_password_reset_token';

    protected $fillable = ['user_id', 'token_signature', 'token_type', 'used_token', 'expires_at'];
}
