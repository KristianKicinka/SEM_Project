<?php
/**
 * @file CustomHashTypePolicy.php
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

namespace App\Policies;

use App\Models\CustomHashType;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CustomHashTypePolicy
{
    use HandlesAuthorization;

    /**
     * @brief Determine whether the user can view any custom hash types
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user)
    {
        return true; // All authenticated users can view custom hash types
    }

    /**
     * @brief Determine whether the user can view the custom hash type
     * @param User $user
     * @param CustomHashType $customHashType
     * @return bool
     */
    public function view(User $user, CustomHashType $customHashType)
    {
        return $user->id === $customHashType->user_id || $customHashType->is_public;
    }

    /**
     * @brief Determine whether the user can create custom hash types
     * @param User $user
     * @return bool
     */
    public function create(User $user)
    {
        return true; // All authenticated users can create custom hash types
    }

    /**
     * @brief Determine whether the user can update the custom hash type
     * @param User $user
     * @param CustomHashType $customHashType
     * @return bool
     */
    public function update(User $user, CustomHashType $customHashType)
    {
        return $user->id === $customHashType->user_id;
    }

    /**
     * @brief Determine whether the user can delete the custom hash type
     * @param User $user
     * @param CustomHashType $customHashType
     * @return bool
     */
    public function delete(User $user, CustomHashType $customHashType)
    {
        return $user->id === $customHashType->user_id;
    }

    /**
     * @brief Determine whether the user can test the custom hash type
     * @param User $user
     * @param CustomHashType $customHashType
     * @return bool
     */
    public function test(User $user, CustomHashType $customHashType)
    {
        return $user->id === $customHashType->user_id || $customHashType->is_public;
    }
}

