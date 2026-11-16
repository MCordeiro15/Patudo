<?php

/**
 * Controlador API para gestão de tickets de suporte. Permite criar reclamações, listar tickets, claim por admins e fechar tickets.
 * API controller for support tickets management. Allows creating complaints, listing tickets, admin claim, and closing tickets.
 */
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\SupportTicketsResource;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Gate;

class SupportTicketsController extends Controller
{
    /**
     * Open a new support ticket (complaint or help request).
     */
    public function store(Request $request)
    {
        Gate::authorize('create', SupportTicket::class);

        $data = $request->validate([
            'reported_user_id' => 'nullable|exists:users,id',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|string|in:complaint,help',
        ]);

        $data['user_id'] = $request->user()->id;
        $data['status'] = 'open';

        $ticket = SupportTicket::create($data);
        $ticket->refresh();

        return (new SupportTicketsResource($ticket->load(['creator', 'reportedUser'])))->response()->setStatusCode(201);
    }

    /**
     * Allow an admin to claim (take ownership of) a support ticket.
     */
    public function claim(Request $request, SupportTicket $ticket)
    {
        Gate::authorize('claim', $ticket);

        if ($ticket->claimed_by !== null) {
            return response()->json(['message' => 'Ticket already claimed'], 409);
        }

        $ticket->update([
            'claimed_by' => $request->user()->id,
            'status' => 'in_progress',
        ]);

        $ticket->refresh();

        return new SupportTicketsResource($ticket->load(['creator', 'claimant', 'reportedUser']));
    }

    /**
     * List all support tickets. Admins see all; regular users see only their own.
     */
    public function index(Request $request): ResourceCollection
    {
        Gate::authorize('viewAny', SupportTicket::class);

        $user = $request->user();

        $query = SupportTicket::with(['creator', 'claimant', 'reportedUser']);

        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        return SupportTicketsResource::collection($query->get());
    }

    /**
     * Show a single support ticket.
     */
    public function show(SupportTicket $ticket)
    {
        Gate::authorize('view', $ticket);

        return new SupportTicketsResource($ticket->load(['creator', 'claimant', 'reportedUser']));
    }

    /**
     * Close a support ticket (admin only).
     */
    public function close(SupportTicket $ticket)
    {
        Gate::authorize('close', $ticket);

        $ticket->update(['status' => 'closed']);
        $ticket->refresh();

        return new SupportTicketsResource($ticket->load(['creator', 'claimant', 'reportedUser']));
    }
}
