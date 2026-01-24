<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'userdata';

    protected $primaryKey = 'userId';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = true;

    protected $fillable = [
        'userName',
        'userEmail',
        'profilePic',
        'google_token',
        'facebook_token',
        'otp_code',
        'otp_expired_at',
        'lastModification',
        'offsetCredit',
        'treeCredit',
    ];

    protected $hidden = [
        'otp_code',          // security
        'google_token',
        'facebook_token',
    ];

    protected $casts = [
        'otp_expired_at' => 'datetime',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
    ];
}
