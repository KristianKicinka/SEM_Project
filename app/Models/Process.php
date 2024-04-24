<?php
/**
 * @file Process.php
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Process extends Model {

    use HasFactory;

     /**
     * @brief The name of table which is connected with this model
     * @var string $table
     */
    protected $table = 'processes';

     /**
     * @brief The attributes that are mass assignable
     * @var array
     */
    protected $fillable = [
        'job_id',
        'ip_address',
        'status',
        'progress',
        'message',
    ];

}
