<?php

/**
 * Política de autorização para tickets de suporte. Define quem pode visualizar, criar, assumir e fechar tickets.
 * Authorization policy for support tickets. Defines who can view, create, claim, and close tickets.
 */
namespace App\Policies;

use App\Models\SupportTicket;
use App\Models\User;

class SupportTicketPolicy
{
    /**
     * Apenas admins podem listar todos os tickets.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Admin vê qualquer ticket; utilizador normal vê apenas os seus.
     */
    public function view(User $user, SupportTicket $ticket): bool
    {
        return $user->isAdmin() || $user->id === $ticket->user_id;
    }

    /**
     * Qualquer utilizador autenticado pode criar tickets.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Apenas admins podem assumir um ticket.
     */
    public function claim(User $user, SupportTicket $ticket): bool
    {
        return $user->isAdmin();
    }

    /**
     * Apenas admins podem fechar um ticket.
     */
    public function close(User $user, SupportTicket $ticket): bool
    {
        return $user->isAdmin();
    }
}
