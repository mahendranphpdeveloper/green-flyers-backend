<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeleteItinerary extends Model
{
    use HasFactory;

    protected $table = 'deleteitinerary';

    protected $fillable = [
        'origin',
        'originCity',
        'destination',
        'destinationCity',
        'class',
        'userName',
        'deleted_date'
    ];
}
