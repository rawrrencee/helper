<?php

namespace App\Policies;

use App\Models\Helper;
use App\Models\User;

class HelperPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Helper $helper): bool
    {
        return $user->isAdmin() || $user->id === $helper->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Helper $helper): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Helper $helper): bool
    {
        return $user->isAdmin();
    }
}
