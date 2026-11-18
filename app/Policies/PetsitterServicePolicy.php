<?php

/**
 * Política de autorização para os serviços dos petsitters. Só o próprio petsitter e o administrador criam, alteram e apagam.
 * Authorization policy for petsitter services. Only the petsitter themselves and the admin can create, update, and delete.
 */
namespace App\Policies;

use App\Models\Petsitter;
use App\Models\PetsitterService;
use App\Models\User;

class PetsitterServicePolicy
{
    /**
     * Permite criar um serviço para este petsitter (o próprio ou admin).
     * Allows creating a service for this petsitter (themselves or admin).
     */
    public function create(User $user, Petsitter $petsitter): bool
    {
        return $user->isAdmin() || $user->id === $petsitter->user_id;
    }

    /**
     * Permite alterar o serviço (o petsitter dono ou admin).
     * Allows updating the service (the owning petsitter or admin).
     */
    public function update(User $user, PetsitterService $service): bool
    {
        return $user->isAdmin() || $user->id === $service->petsitter_id;
    }

    /**
     * Permite apagar o serviço (o petsitter dono ou admin).
     * Allows deleting the service (the owning petsitter or admin).
     */
    public function delete(User $user, PetsitterService $service): bool
    {
        return $user->isAdmin() || $user->id === $service->petsitter_id;
    }
}
