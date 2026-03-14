<?php

namespace App\Policies;

use App\Models\SalaryPayment;
use App\Models\User;

class SalaryPaymentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SalaryPayment $salaryPayment): bool
    {
        return $user->isAdmin() || $user->id === $salaryPayment->helper->user_id;
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
    public function update(User $user, SalaryPayment $salaryPayment): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SalaryPayment $salaryPayment): bool
    {
        return $user->isAdmin();
    }
}
