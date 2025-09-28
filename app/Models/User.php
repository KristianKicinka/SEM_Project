<?php
/**
 * @file User.php
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject {

    use HasApiTokens, HasFactory, Notifiable;

    /**
     * @brief The attributes that are mass assignable
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'surname',
        'email',
        'phone',
        'password',
        'role',
        'api_auth_key',
        'profile_photo'
    ];

    /**
     * @brief The attributes that should be hidden for serialization
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * @brief The function ensures getting the identifier that will be stored in the subject claim of the JWT
     * @return mixed
     */
    public function getJWTIdentifier(): mixed {
        return $this->getKey();
    }

    /**
     * @brief The function ensures the returning a key value array, containing custom claims to be added to the JWT
     * @return array
     */
    public function getJWTCustomClaims(): array {
        return [];
    }
}
