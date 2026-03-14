<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\Helper;
use App\Models\User;

class DocumentPolicy
{
    /**
     * Determine whether the user can view documents for a helper.
     */
    public function viewAny(User $user, Helper $helper): bool
    {
        return $user->isAdmin() || $user->id === $helper->user_id;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Document $document): bool
    {
        if ($document->hidden_from_helper && ! $user->isAdmin()) {
            return false;
        }

        return $user->isAdmin() || $user->id === $document->helper->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Document $document): bool
    {
        return $user->isAdmin();
    }
}
