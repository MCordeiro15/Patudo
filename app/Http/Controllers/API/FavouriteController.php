<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\FavouriteResource;
use App\Http\Resources\PetsitterResource;
use App\Models\Favourite;
use App\Models\Petsitter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FavouriteController extends Controller
{
    /**
     * Get the authenticated user's favourite petsitters.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $favourites = Favourite::where('user_id', $user->id)
            ->with(['petsitter.user'])
            ->get();

        return PetsitterResource::collection($favourites->pluck('petsitter')->filter());
    }

    /**
     * Add a petsitter to the authenticated user's favourites.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'petsitter_id' => 'required|exists:petsitters,user_id',
        ]);

        $user = $request->user();
        $petsitter = Petsitter::where('user_id', $data['petsitter_id'])->first();

        $favourite = Favourite::firstOrCreate(
            ['user_id' => $user->id, 'petsitter_id' => $petsitter->user_id],
            ['user_id' => $user->id, 'petsitter_id' => $petsitter->user_id]
        );

        return new FavouriteResource($favourite->load(['user', 'petsitter']))->response()->setStatusCode(201);
    }

    /**
     * Remove a petsitter from the authenticated user's favourites.
     */
    public function destroy(Request $request, Petsitter $petsitter): Response|JsonResponse
    {
        $user = $request->user();

        $deleted = Favourite::where('user_id', $user->id)
            ->where('petsitter_id', $petsitter->user_id)
            ->delete();

        if ($deleted === 0) {
            return response()->json(['message' => 'Favourite not found'], 404);
        }

        return response()->noContent();
    }
}
