<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Emulator extends Model {

    use HasFactory;

    /**
     * @brief The attributes that are mass assignable
     * @var array
     */
    protected $fillable = [
        'name',
        'network_interface',
        'is_running',
    ];

}
