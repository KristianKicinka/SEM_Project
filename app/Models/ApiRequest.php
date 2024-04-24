<?php
/**
 * @file ApiRequest.php
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiRequest extends Model {

    use HasFactory;

    /**
     * @brief The attributes that are mass assignable
     * @var array
     */
    protected $fillable = [
        'user_id',
        'ip_address',
        'type',
        'description',
        'status',
    ];

}
