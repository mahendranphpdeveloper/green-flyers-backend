<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FormContent extends Model
{
    use HasFactory;

    protected $table = 'form_content';

    protected $fillable = [
        'title',
        'description',
    ];
}
