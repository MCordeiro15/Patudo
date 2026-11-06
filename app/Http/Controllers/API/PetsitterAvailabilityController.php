<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PetsitterAvailabilityResource;
use App\Models\Petsitter;
use App\Models\PetsitterAvailability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class PetsitterAvailabilityController extends Controller
{
    /**
     * Obtem lista de dias disponíveis de um petsitter numa janela de tempo.
     */
    public function availability(Request $request, string|int $id): AnonymousResourceCollection|JsonResponse
    {
        $petsitter = Petsitter::find($id);

        if (! $petsitter) {
            return response()->json(['message' => 'Petsitter não encontrado.'], 404);
        }

        $validated = $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
        ]);

        $query = PetsitterAvailability::where('petsitter_id', $petsitter->user_id);

        if (isset($validated['start_date'])) {
            $query->whereDate('date', '>=', $validated['start_date']);
        }

        if (isset($validated['end_date'])) {
            $query->whereDate('date', '<=', $validated['end_date']);
        }

        $availabilities = $query->orderBy('date')->get();

        return PetsitterAvailabilityResource::collection($availabilities);
    }

    /**
     * Altera a disponibilidade do petsitter para uma data específica.
     */
    public function toggle(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Não autenticado.'], 401);
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'petsitter_id' => 'sometimes|exists:petsitters,user_id',
        ]);

        $petsitterId = $validated['petsitter_id'] ?? $user->id;

        // Se passar o petsitter_id diferente, apenas o admin pode gerir o calendário de outro petsitter
        if ($petsitterId != $user->id && ! $user->isAdmin()) {
            return response()->json(['message' => 'Não autorizado a alterar este calendário.'], 403);
        }

        // Verifica se existe o perfil de petsitter
        $petsitter = Petsitter::find($petsitterId);
        if (! $petsitter) {
            return response()->json(['message' => 'Perfil de petsitter não encontrado.'], 404);
        }

        $formattedDate = date('Y-m-d', strtotime($validated['date']));

        $availability = PetsitterAvailability::where('petsitter_id', $petsitterId)
            ->whereDate('date', $formattedDate)
            ->first();

        if ($availability) {
            $availability->delete();

            return response()->json([
                'message' => 'Disponibilidade desativada para a data.',
                'activated' => false,
                'date' => $formattedDate,
            ], 200);
        }

        $newAvailability = PetsitterAvailability::create([
            'petsitter_id' => $petsitterId,
            'date' => $formattedDate,
        ]);

        return response()->json([
            'message' => 'Disponibilidade ativada para a data.',
            'activated' => true,
            'date' => $formattedDate,
            'data' => new PetsitterAvailabilityResource($newAvailability),
        ], 200);
    }

    /**
     * Cria uma disponibilidade para uma data.
     */
    public function store(Request $request, Petsitter $petsitter): JsonResponse
    {
        if (! $request->user()->can('update', $petsitter)) {
            return response()->json(['message' => 'Não autorizado a alterar este calendário.'], 403);
        }

        $validated = $request->validate([
            'date' => [
                'required',
                'date_format:Y-m-d',
                Rule::unique('petsitter_availabilities', 'date')
                    ->where('petsitter_id', $petsitter->user_id),
            ],
        ]);

        $availability = PetsitterAvailability::create([
            'petsitter_id' => $petsitter->user_id,
            'date' => $validated['date'],
        ]);

        return (new PetsitterAvailabilityResource($availability))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Atualiza a data de uma disponibilidade existente (identificada por petsitter_id + date).
     */
    public function update(Request $request, Petsitter $petsitter, string $date): JsonResponse
    {
        if (! $request->user()->can('update', $petsitter)) {
            return response()->json(['message' => 'Não autorizado a alterar este calendário.'], 403);
        }

        $availability = PetsitterAvailability::where('petsitter_id', $petsitter->user_id)
            ->whereDate('date', $date)
            ->first();

        if (! $availability) {
            return response()->json(['message' => 'Disponibilidade não encontrada.'], 404);
        }

        $validated = $request->validate([
            'date' => [
                'required',
                'date_format:Y-m-d',
                Rule::unique('petsitter_availabilities', 'date')
                    ->where('petsitter_id', $petsitter->user_id)
                    ->ignore($availability->id),
            ],
        ]);

        $availability->update(['date' => $validated['date']]);

        return (new PetsitterAvailabilityResource($availability))->response();
    }

    /**
     * Apaga a disponibilidade de uma data (identificada por petsitter_id + date).
     */
    public function destroy(Request $request, Petsitter $petsitter, string $date): JsonResponse
    {
        if (! $request->user()->can('update', $petsitter)) {
            return response()->json(['message' => 'Não autorizado a alterar este calendário.'], 403);
        }

        $deleted = PetsitterAvailability::where('petsitter_id', $petsitter->user_id)
            ->whereDate('date', $date)
            ->delete();

        if (! $deleted) {
            return response()->json(['message' => 'Disponibilidade não encontrada.'], 404);
        }

        return response()->json(null, 204);
    }
}
