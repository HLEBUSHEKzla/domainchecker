<?php

namespace App\Policies;

use App\Models\Domain;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DomainPolicy
{
    public function view(User $user, Domain $domain): bool
    {
        return $user->id === $domain->user_id;
    }

    public function update(User $user, Domain $domain): bool
    {
        return $user->id === $domain->user_id;
    }

    public function delete(User $user, Domain $domain): bool
    {
        return $user->id === $domain->user_id;
    }
}
