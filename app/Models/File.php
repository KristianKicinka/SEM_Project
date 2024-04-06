<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model {

    use HasFactory;

    /**
     * @brief The attributes that are mass assignable
     * @var array
     */
    protected $fillable = [
        'name',
        'type',
        'path',
        'app_id'
    ];

}
