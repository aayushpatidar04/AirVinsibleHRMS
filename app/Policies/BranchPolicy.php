<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    /** Admin can do everything */
    public function before(User $user): ?bool
    {
        if ($user->hasRole('admin'))
            return true;
        return null;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }
    public function view(User $user, Branch $branch): bool
    {
        return false;
    }
    public function create(User $user): bool
    {
        return false;
    }
    public function update(User $user, Branch $branch): bool
    {
        return false;
    }
    public function delete(User $user, Branch $branch): bool
    {
        return false;
    }
}