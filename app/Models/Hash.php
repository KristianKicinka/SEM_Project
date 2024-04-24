<?php
/**
 * @file Hash.php
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hash extends Model {

    use HasFactory;

     /**
     * @brief The attributes that are mass assignable
     * @var array
     */
    protected $fillable = [
        'app_id',
        'process_id',
        'ja3_hash',
        'ja3s_hash',
        'hash_type',
        'sni',
        'ja4_hash',
        'ja4s_hash',
        'ja4x_hash',
        'ip_src',
        'port_src',
        'ip_dest',
        'port_dest',
    ];
}
