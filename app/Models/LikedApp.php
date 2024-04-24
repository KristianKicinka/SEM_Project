<?php
/**
 * @file LikedApp.php
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LikedApp extends Model {
    use HasFactory;

    /**
     * @brief The attributes that are mass assignable
     * @var array
     */
    protected $fillable = [
        'app_id',
        'user_id',
    ];
}
