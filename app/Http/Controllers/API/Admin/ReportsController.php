<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminUserResource;
use App\Http\Resources\BookingResource;
use App\Http\Resources\NotificationResource;
use App\Http\Resources\ReviewResource;
use App\Http\Resources\SupportTicketsResource;
use App\Models\Booking;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\UserNote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'tab' => ['nullable', Rule::in(['open', 'closed', 'archived'])],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $baseQuery = SupportTicket::query()->where('type', 'complaint');
        $counts = [
            'open' => (clone $baseQuery)->whereIn('status', ['open', 'in_progress'])->count(),
            'closed' => (clone $baseQuery)->where('status', 'closed')->count(),
            'archived' => (clone $baseQuery)->where('status', 'archived')->count(),
        ];

        $query = (clone $baseQuery)->with(['creator', 'reportedUser']);
        $tab = $filters['tab'] ?? 'open';

        match ($tab) {
            'open' => $query->whereIn('status', ['open', 'in_progress']),
            'closed' => $query->where('status', 'closed'),
            'archived' => $query->where('status', 'archived'),
        };

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(fn (Builder $query) => $query
                ->where('subject', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhereHas('creator', fn (Builder $users) => $users
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"))
                ->orWhereHas('reportedUser', fn (Builder $users) => $users
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")));
        }

        $reports = $query
            ->latest()
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return response()->json([
            'data' => SupportTicketsResource::collection($reports->getCollection())->resolve($request),
            'counts' => $counts,
            'meta' => [
                'current_page' => $reports->currentPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
                'last_page' => $reports->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, SupportTicket $report): JsonResponse
    {
        $this->ensureComplaint($report);
        $report->load(['creator', 'claimant', 'reportedUser']);

        $reportedUserData = null;

        if ($report->reportedUser) {
            $reportedUser = $report->reportedUser;
            $reportedUserData = (new AdminUserResource($reportedUser))->resolve($request);
            $bookings = Booking::query()
                ->with(['user', 'petsitter.user', 'service', 'animals', 'review'])
                ->where(fn (Builder $query) => $query
                    ->where('user_id', $reportedUser->id)
                    ->orWhere('petsitter_id', $reportedUser->id))
                ->latest()
                ->get();

            $reportedUserData['bookings'] = BookingResource::collection($bookings)->resolve($request);
            $reportedUserData['reviews'] = ReviewResource::collection(
                $reportedUser->is_petsitter
                    ? $reportedUser->petsitter?->reviews()->with('booking')->latest()->get() ?? collect()
                    : collect()
            )->resolve($request);
            $reportedUserData['previous_reports'] = SupportTicketsResource::collection(
                SupportTicket::query()
                    ->with(['creator', 'reportedUser'])
                    ->where('type', 'complaint')
                    ->where('reported_user_id', $reportedUser->id)
                    ->whereKeyNot($report->id)
                    ->latest()
                    ->get()
            )->resolve($request);
        }

        return response()->json(['data' => [
            'id' => $report->id,
            'subject' => $report->subject,
            'description' => $report->description,
            'status' => $report->status,
            'created_at' => $report->created_at,
            'reporter' => $report->creator ? (new AdminUserResource($report->creator))->resolve($request) : null,
            'reported_user' => $reportedUserData,
        ]]);
    }

    public function archive(Request $request, SupportTicket $report): SupportTicketsResource
    {
        return $this->updateStatus($request, $report, 'archived');
    }

    public function close(Request $request, SupportTicket $report): SupportTicketsResource
    {
        return $this->updateStatus($request, $report, 'closed');
    }

    public function sendWarning(Request $request, SupportTicket $report): JsonResponse
    {
        $this->ensureComplaint($report);
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'note' => ['nullable', 'string', 'max:5000'],
        ]);

        abort_if($report->reportedUser === null, 422, 'A denúncia não tem um utilizador denunciado.');

        $this->storeInternalNote($request, $report->reportedUser, $validated['note'] ?? null);
        $notification = $report->reportedUser->notifications()->create([
            'type' => 'admin_warning',
            'message' => $validated['message'],
        ]);

        return (new NotificationResource($notification))->response()->setStatusCode(201);
    }

    private function updateStatus(Request $request, SupportTicket $report, string $status): SupportTicketsResource
    {
        $this->ensureComplaint($report);
        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:5000'],
        ]);

        $report->update(['status' => $status]);
        $this->storeInternalNote($request, $report->reportedUser, $validated['note'] ?? null);

        return new SupportTicketsResource($report->load(['creator', 'claimant', 'reportedUser']));
    }

    private function storeInternalNote(Request $request, ?User $user, ?string $note): void
    {
        if ($user && filled($note)) {
            UserNote::create([
                'user_id' => $user->id,
                'admin_id' => $request->user()->id,
                'note' => $note,
            ]);
        }
    }

    private function ensureComplaint(SupportTicket $report): void
    {
        abort_unless($report->type === 'complaint', 404);
    }
}
