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
        'sni_flag',
        'is_flagged',
        'ja4_hash',
        'ja4s_hash',
        'ja4x_hash',
        'custom_hashes',
        'custom_hash_type_id',
        'ip_src',
        'port_src',
        'ip_dest',
        'port_dest',
    ];

    /**
     * @brief The attributes that should be cast
     * @var array
     */
    protected $casts = [
        'ja4x_hash' => 'array',
        'custom_hashes' => 'array',
    ];

    /**
     * @brief Get the custom hash type that owns the hash
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function customHashType(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CustomHashType::class);
    }
}
