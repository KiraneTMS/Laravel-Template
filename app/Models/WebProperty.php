<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebProperty extends Model
{
    protected $fillable = [
        'webname',
        'style',
        'icon',
        'welcome_msg',
        'color_scheme',
        'tagline',
        'description',
        'status',
        'packages',
    ];

    protected $casts = [
        'color_scheme' => 'array',
        'packages' => 'array',
    ];
}
