<?php
/**
 * @file CustomHashType.php
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomHashType extends Model
{
    use HasFactory;

    /**
     * @brief The attributes that are mass assignable
     * @var array
     */
    protected $fillable = [
        'user_id',
        'name',
        'display_name',
        'description',
        'type',
        'configuration',
        'script_path',
        'is_active',
        'is_public',
        'usage_count',
    ];

    /**
     * @brief The attributes that should be cast
     * @var array
     */
    protected $casts = [
        'configuration' => 'array',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'usage_count' => 'integer',
    ];

    /**
     * @brief Get the user that owns the custom hash type
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @brief Get the hashes that use this custom hash type
     * @return HasMany
     */
    public function hashes(): HasMany
    {
        return $this->hasMany(Hash::class, 'custom_hash_type_id');
    }

    /**
     * @brief Scope for active custom hash types
     * @param $query
     * @return mixed
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * @brief Scope for public custom hash types
     * @param $query
     * @return mixed
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * @brief Scope for user's custom hash types
     * @param $query
     * @param $userId
     * @return mixed
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * @brief Increment usage count
     * @return void
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * @brief Get the full script path
     * @return string|null
     */
    public function getFullScriptPathAttribute(): ?string
    {
        if ($this->script_path && $this->type === 'python_script') {
            return base_path($this->script_path);
        }
        return null;
    }

    /**
     * @brief Check if the custom hash type is usable
     * @return bool
     */
    public function isUsable(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->type === 'python_script' && $this->script_path) {
            return file_exists($this->getFullScriptPathAttribute());
        }

        return true;
    }

    /**
     * @brief Get configuration for hash generator
     * @return array
     */
    public function getGeneratorConfig(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description,
            'script_path' => $this->script_path,
            ...$this->configuration
        ];
    }
}

