<?php

namespace App\Policies;

use App\Models\Asset;
use App\Models\User;

class AssetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ver-activos');
    }

    public function view(User $user, Asset $asset): bool
    {
        return $user->can('ver-activos');
    }

    public function create(User $user): bool
    {
        return $user->can('crear-activos');
    }

    public function update(User $user, Asset $asset): bool
    {
        return $user->can('editar-activos');
    }

    public function decommission(User $user, Asset $asset): bool
    {
        return $user->can('dar-de-baja-activos');
    }
}
