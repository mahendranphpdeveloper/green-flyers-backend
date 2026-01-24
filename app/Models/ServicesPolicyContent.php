<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicesPolicyContent extends Model
{
    use HasFactory;

    protected $table = 'services_policy_content';
    protected $primaryKey = 'id';
    public $timestamps = true;

    // Allow mass assignment
    protected $fillable = [
        'content',
    ];
}
