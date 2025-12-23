<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class AdminData extends Model
{
    use HasApiTokens;
    use HasFactory;

    protected $table = 'admindata';

    protected $guarded = [];

    protected $hidden = [
        'password',
    ];
}
