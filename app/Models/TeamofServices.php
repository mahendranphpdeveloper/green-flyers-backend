<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamofServices extends Model
{
    use HasFactory;

    protected $table = 'team_of_services';
    protected $primaryKey = 'id';
    public $timestamps = true;

    // Allow mass assignment
    protected $fillable = [
        'title',
        'content',
        'order',
        'isActive',
    ];

     protected $casts = [
        'content'  => 'array',
        'isActive' => 'boolean',
    ];
}
