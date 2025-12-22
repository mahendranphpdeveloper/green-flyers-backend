<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorsData extends Model
{
    use HasFactory;

    protected $table = 'vendorsdata'; 
    protected $fillable = [
        'name',
        'projects',
        'status',
        'description',
        'projectUrl',
        'email',
        'state',
        'country'
    ];
}
