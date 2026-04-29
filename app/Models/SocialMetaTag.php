<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialMetaTag extends Model
{
    use HasFactory;

    protected $table = 'social_meta_tags';

    protected $fillable = [
        'title',
        'description',
        'image_url',
    ];
}
