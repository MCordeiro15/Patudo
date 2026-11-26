<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminUserResource;
use App\Http\Resources\NotificationResource;
use App\Models\Animal;
use App\Models\Booking;
use App\Models\Notification;
use App\Models\Post;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\UserNote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $this->validatedFilters($request);

        return AdminUserResource::collection(
            $this->usersQuery($filters)->paginate(8)
        );
    }

    public function export(Request $request)
    {
        $query = $this->usersQuery($this->validatedFilters($request));

        return Response::streamDownload(function () use ($query): void {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                return;
            }

            fputcsv($output, ['id', 'name', 'email', 'phone', 'type', 'account_status', 'created_at'], ',', '"', '');

            foreach ($query->cursor() as $user) {
                fputcsv($output, [
                    $user->id,
                    $this->csvCell($user->name),
                    $this->csvCell($user->email),
                    $this->csvCell($user->phone),
                    $this->userType($user),
                    $this->csvCell($user->account_status),
                    $user->created_at?->toIso8601String(),
                ], ',', '"', '');
            }

            fclose($output);
        }, 'users.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function show(Request $request, User $user)
    {
        $data = (new AdminUserResource($user))->resolve($request);
        $data['counts'] = [
            'animals' => Animal::where('user_id', $user->id)->count(),
            'bookings' => Booking::where(fn (Builder $query) => $query
                ->where('user_id', $user->id)
                ->orWhere('petsitter_id', $user->id))->count(),
            'bookings_as_owner' => Booking::where('user_id', $user->id)->count(),
            'bookings_as_petsitter' => Booking::where('petsitter_id', $user->id)->count(),
            'posts' => Post::where('petsitter_id', $user->id)->count(),
            'support_tickets' => SupportTicket::where('user_id', $user->id)->count(),
            'notifications' => Notification::where('user_id', $user->id)->count(),
        ];
        $data['recent_activity'] = $this->recentActivity($user);

        return response()->json(['data' => $data]);
    }

    public function suspend(User $user): AdminUserResource
    {
        $user->update(['account_status' => 'suspended']);

        return new AdminUserResource($user->refresh());
    }

    public function sendWarning(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $notification = $user->notifications()->create([
            'type' => 'admin_warning',
            'message' => $validated['message'],
        ]);

        return (new NotificationResource($notification))->response()->setStatusCode(201);
    }

    public function notes(User $user)
    {
        $notes = UserNote::where('user_id', $user->id)
            ->with('admin:id,name')
            ->latest()
            ->get()
            ->map(fn (UserNote $note): array => $this->noteData($note));

        return response()->json(['data' => $notes]);
    }

    public function storeNote(Request $request, User $user)
    {
        $validated = $request->validate([
            'note' => ['required', 'string', 'max:5000'],
        ]);

        $note = UserNote::create([
            'user_id' => $user->id,
            'admin_id' => $request->user()->getAuthIdentifier(),
            'note' => $validated['note'],
        ])->load('admin:id,name');

        return response()->json(['data' => $this->noteData($note)], 201);
    }

    /** @return array<string, mixed> */
    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', Rule::in(['owner', 'user', 'customer', 'petsitter', 'admin'])],
            'status' => ['nullable', Rule::in(['active', 'suspended'])],
        ]);
    }

    /** @param array<string, mixed> $filters */
    private function usersQuery(array $filters): Builder
    {
        $query = User::query()->orderByDesc('created_at')->orderByDesc('id');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(fn (Builder $query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        if (! empty($filters['type'])) {
            match ($filters['type']) {
                'petsitter' => $query->where('is_petsitter', true),
                'admin' => $query->where('is_admin', true),
                default => $query->where('is_admin', false)->where('is_petsitter', false),
            };
        }

        if (! empty($filters['status'])) {
            $query->where('account_status', $filters['status']);
        }

        return $query;
    }

    /** @return array<int, array<string, mixed>> */
    private function recentActivity(User $user): array
    {
        $activities = collect();

        foreach (Animal::where('user_id', $user->id)->latest()->limit(10)->get(['id', 'name', 'created_at']) as $animal) {
            $activities->push([
                'type' => 'animal',
                'id' => $animal->id,
                'description' => 'Animal adicionado: '.$animal->name,
                'created_at' => $animal->created_at,
            ]);
        }

        foreach (Booking::where(fn (Builder $query) => $query
            ->where('user_id', $user->id)
            ->orWhere('petsitter_id', $user->id))
            ->latest()->limit(10)->get(['id', 'user_id', 'petsitter_id', 'status', 'created_at']) as $booking) {
            $activities->push([
                'type' => 'booking',
                'id' => $booking->id,
                'description' => $booking->user_id === $user->id ? 'Reserva criada' : 'Reserva recebida',
                'status' => $booking->status,
                'created_at' => $booking->created_at,
            ]);
        }

        foreach (Post::where('petsitter_id', $user->id)->latest()->limit(10)->get(['id', 'title', 'created_at']) as $post) {
            $activities->push([
                'type' => 'post',
                'id' => $post->id,
                'description' => 'Publicação criada'.($post->title ? ': '.$post->title : ''),
                'created_at' => $post->created_at,
            ]);
        }

        foreach (SupportTicket::where('user_id', $user->id)->latest()->limit(10)->get(['id', 'subject', 'type', 'status', 'created_at']) as $ticket) {
            $activities->push([
                'type' => 'support_ticket',
                'id' => $ticket->id,
                'description' => 'Pedido de suporte: '.$ticket->subject,
                'status' => $ticket->status,
                'created_at' => $ticket->created_at,
            ]);
        }

        foreach (Notification::where('user_id', $user->id)->latest()->limit(10)->get(['id', 'type', 'read_at', 'created_at']) as $notification) {
            $activities->push([
                'type' => 'notification',
                'id' => $notification->id,
                'description' => $notification->type === 'admin_warning'
                    ? 'Aviso enviado pela administração'
                    : 'Notificação recebida',
                'read' => $notification->read_at !== null,
                'created_at' => $notification->created_at,
            ]);
        }

        return $activities
            ->sortByDesc('created_at')
            ->take(10)
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    private function noteData(UserNote $note): array
    {
        return [
            'id' => $note->id,
            'user_id' => $note->user_id,
            'admin_id' => $note->admin_id,
            'note' => $note->note,
            'admin' => $note->admin?->only(['id', 'name']),
            'created_at' => $note->created_at,
            'updated_at' => $note->updated_at,
        ];
    }

    private function userType(User $user): string
    {
        if ($user->isAdmin()) {
            return 'admin';
        }

        return $user->isPetsitter() ? 'petsitter' : 'owner';
    }

    private function csvCell(?string $value): string
    {
        $value ??= '';

        return preg_match('/^[\t\r ]*[=+\-@]/', $value) === 1 ? "'{$value}" : $value;
    }
}
