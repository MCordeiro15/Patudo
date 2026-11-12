<?php


/**
 * Controlador API para gestão de tickets de suporte. Permite criar reclamações, listar tickets, claim por admins e fechar tickets.
 * API controller for support tickets management. Allows creating complaints, listing tickets, admin claim, and closing tickets.
 */
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\SupportTicketsResource;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;

class SupportTicketsController extends Controller
{
    /**
 * Open a new support ticket (complaint or help request).
 * Open a new support ticket (complaint or help request).
 */
    public function store(Request $request): JsonResponse
    {
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

        return new SupportTicketsResource($ticket->load(['creator', 'reportedUser']))->response()->setStatusCode(201);
    }

    /**
     * Allow an admin to claim (take ownership of) a support ticket.
     */
    public function claim(Request $request, SupportTicket $ticket): JsonResponse|Response
    {
        $user = $request->user();

        if (! $user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($ticket->claimed_by !== null) {
            return response()->json(['message' => 'Ticket already claimed'], 409);
        }

        $ticket->update([
            'claimed_by' => $user->id,
            'status' => 'in_progress',
        ]);

        $ticket->refresh();

        return new SupportTicketsResource($ticket->load(['creator', 'claimant', 'reportedUser']))->response();
    }

    /**
     * List all support tickets. Admins see all; regular users see only their own.
     */
    public function index(Request $request): ResourceCollection
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            $tickets = SupportTicket::with(['creator', 'claimant', 'reportedUser'])->get();
        } else {
            $tickets = SupportTicket::where('user_id', $user->id)
                ->with(['creator', 'claimant', 'reportedUser'])
                ->get();
        }

        return SupportTicketsResource::collection($tickets);
    }

    /**
     * Show a single support ticket.
     */
    public function show(Request $request, SupportTicket $ticket): JsonResponse|Response
    {
        $user = $request->user();

        if (! $user->isAdmin() && $ticket->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return new SupportTicketsResource($ticket->load(['creator', 'claimant', 'reportedUser']))->response();
    }

    /**
     * Close a support ticket (admin only).
     */
    public function close(Request $request, SupportTicket $ticket): JsonResponse|Response
    {
        $user = $request->user();

        if (! $user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $ticket->update(['status' => 'closed']);
        $ticket->refresh();

        return new SupportTicketsResource($ticket->load(['creator', 'claimant', 'reportedUser']))->response();
    }
}
