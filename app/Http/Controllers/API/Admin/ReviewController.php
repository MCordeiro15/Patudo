<?php

/**
 * Controlador da página Avaliações do admin: lista com filtros e pesquisa, publicar e remover avaliações.
 * Admin Reviews page controller: list with filters and search, publish and remove reviews.
 */
namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminReviewResource;
use App\Models\Review;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ReviewController extends Controller
{
    /**
     * GET /api/admin/reviews: lista paginada (mais recentes primeiro), com filtro por estado (?status=all|flagged|
     * published|removed) e pesquisa no comentário, no autor ou no petsitter (?search=). Os contadores de cada
     * filtro vêm em meta.counts (o das Sinalizadas é o número da bolinha).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['all', ...Review::STATUSES])],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $query = Review::query()
            ->with(['booking.user', 'petsitter.user'])
            ->when(($filters['status'] ?? 'all') !== 'all', fn (Builder $q) => $q->where('status', $filters['status']))
            ->when($filters['search'] ?? null, function (Builder $q, string $search) {
                $like = '%'.$search.'%';
                $q->where(fn (Builder $q) => $q
                    ->where('comment', 'like', $like)
                    ->orWhereHas('booking.user', fn (Builder $u) => $u->where('name', 'like', $like))
                    ->orWhereHas('petsitter.user', fn (Builder $u) => $u->where('name', 'like', $like)));
            })
            ->latest()
            ->orderByDesc('id');

        // contadores dos filtros (sem a pesquisa, como as abas da página)
        $byStatus = Review::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $counts = ['all' => (int) $byStatus->sum()];
        foreach (Review::STATUSES as $status) {
            $counts[$status] = (int) ($byStatus[$status] ?? 0);
        }

        return AdminReviewResource::collection($query->paginate(10))->additional(['meta' => ['counts' => $counts]]);
    }

    /**
     * POST /api/admin/reviews/{review}/publish: volta a publicar (ex.: uma sinalização sem razão, ou uma removida
     * por engano). Volta a contar para a média.
     */
    public function publish(Review $review): AdminReviewResource
    {
        return $this->setStatus($review, Review::PUBLISHED);
    }

    /**
     * POST /api/admin/reviews/{review}/remove: deixa de aparecer no perfil do petsitter e de contar para a média.
     */
    public function remove(Review $review): AdminReviewResource
    {
        return $this->setStatus($review, Review::REMOVED);
    }

    private function setStatus(Review $review, string $status): AdminReviewResource
    {
        Gate::authorize('moderate', $review);

        $review->forceFill(['status' => $status])->save();

        // a média do petsitter muda quando uma review entra ou sai
        $review->petsitter->refreshAverageRating();

        return new AdminReviewResource($review->load(['booking.user', 'petsitter.user']));
    }
}
