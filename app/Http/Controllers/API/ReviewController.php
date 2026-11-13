<?php


/**
 * Controlador API para gestão de avaliações de petsitters. Controller vazio reservado para desenvolvimento futuro.
 * API controller for petsitter review management. Empty controller reserved for future development.
 */
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Booking;
use App\Models\Petsitter;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReviewController extends Controller
{
    // GET /api/petsitters/{petsitter}/reviews: só as visíveis, mais recentes primeiro
    public function byPetsitter(Petsitter $petsitter)
    {
        return ReviewResource::collection(
            $petsitter->reviews()->where('visible', true)->latest()->paginate(15)
        );
    }

    // POST /api/bookings/{booking}/review
    public function store(Request $request, Booking $booking)
    {
        // 403 se não for o dono da reserva ou se a reserva não estiver concluída
        Gate::authorize('create', [Review::class, $booking]);

        // 409 se esta reserva já tem review (booking_id é único na tabela)
        if ($booking->review()->exists()) {
            return response()->json(['message' => 'Esta reserva já foi avaliada.'], 409);
        }

        $data = $request->validate([
            'star_rating' => 'required|integer|between:1,5',
            'comment' => 'nullable|string|max:2000',
        ]);

        // o petsitter_id vem da reserva, não do pedido
        $review = $booking->review()->create([
            ...$data,
            'petsitter_id' => $booking->petsitter_id,
        ]);

        // atualiza a coluna average_rating do petsitter
        $review->petsitter->refreshAverageRating();

        return (new ReviewResource($review))->response()->setStatusCode(201);
    }

    // GET /api/reviews/{review}
    public function show(Review $review)
    {
        Gate::authorize('view', $review);

        return new ReviewResource($review);
    }

    // PATCH /api/reviews/{review}: só o autor
    public function update(Request $request, Review $review)
    {
        Gate::authorize('update', $review);

        $data = $request->validate([
            'star_rating' => 'sometimes|required|integer|between:1,5',
            'comment' => 'nullable|string|max:2000',
        ]);

        $review->update($data);

        // a nota pode ter mudado
        $review->petsitter->refreshAverageRating();

        return new ReviewResource($review);
    }

    // DELETE /api/reviews/{review}: autor ou admin
    public function destroy(Review $review)
    {
        Gate::authorize('delete', $review);

        // guarda o petsitter antes de apagar, para recalcular a média depois
        $petsitter = $review->petsitter;

        $review->delete();

        $petsitter->refreshAverageRating();

        return response()->noContent();
    }

    // PATCH /api/reviews/{review}/hide: só o admin
    public function hide(Review $review)
    {
        Gate::authorize('hide', $review);

        $review->update(['visible' => false]);

        // uma review escondida deixa de contar para a média
        $review->petsitter->refreshAverageRating();

        return new ReviewResource($review);
    }
}
