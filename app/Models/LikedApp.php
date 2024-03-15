<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LikedApp extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_id',
        'user_id',
    ];

    
}
