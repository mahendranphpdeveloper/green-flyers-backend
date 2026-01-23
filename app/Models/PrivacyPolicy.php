<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrivacyPolicy extends Model
{
    use HasFactory;

    protected $table = 'privacy_policy';
    protected $primaryKey = 'id';

    public $timestamps = true; // enables created_at and updated_at

    protected $guarded = []; // allow mass assignment
}
