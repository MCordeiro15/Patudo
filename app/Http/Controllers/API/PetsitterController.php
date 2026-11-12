<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PetsitterResource;
use App\Models\Petsitter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PetsitterController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Petsitter::class);

        $query = Petsitter::with('user');

        if (! $request->user()?->isAdmin()) {
            $query->where('approval_status', 'approved');
        }

        return PetsitterResource::collection($query->get());
    }

    /**
     * Retorna apenas os petsitters aprovados, sem necessidade de autenticação.
     */
    public function publicIndex()
    {
        $query = Petsitter::with('user')->where('approval_status', 'approved');

        return PetsitterResource::collection($query->get());
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Petsitter::class);

        $data = $request->validate([
            'user_id' => 'sometimes|exists:users,id|unique:petsitters,user_id',
            'biography' => 'nullable|string',
            'experience' => 'required|string',
            'service_location' => 'required|string|max:255',
            'age' => 'required|integer|min:18|max:99',
            'price_per_day' => 'required|numeric|min:0',
            'accepted_sizes' => 'nullable|array',
            'accepted_sizes.*' => 'string',
            'accepted_animal_types' => 'nullable|array',
            'accepted_animal_types.*' => 'string',
            'sex' => 'string|in:male,female,other',
            'identification_documents' => 'required|string|max:255',
        ]);

        if (! $request->user()?->isAdmin() || empty($data['user_id'])) {
            $data['user_id'] = $request->user()->id;
        }

        $petsitter = Petsitter::create($data);
        $petsitter->refresh();

        return (new PetsitterResource($petsitter->load('user')))->response()->setStatusCode(201);
    }

    public function show(Petsitter $petsitter)
    {
        Gate::authorize('view', $petsitter);

        return new PetsitterResource($petsitter->load('user'));
    }

    public function update(Request $request, Petsitter $petsitter)
    {
        Gate::authorize('update', $petsitter);

        $data = $request->validate([
            'biography' => 'nullable|string',
            'experience' => 'sometimes|string',
            'service_location' => 'sometimes|string|max:255',
            'price_per_day' => 'sometimes|numeric|min:0',
            'age' => 'sometimes|integer|min:18|max:99',
            'accepted_sizes' => 'nullable|array',
            'accepted_sizes.*' => 'string',
            'sex' => 'sometimes|string|in:male,female,other',
            'accepted_animal_types' => 'nullable|array',
            'accepted_animal_types.*' => 'string',
            'identification_documents' => 'sometimes|string|max:255',
        ]);

        $petsitter->update($data);

        return new PetsitterResource($petsitter->load('user'));
    }

    /**
     * Atualiza os dias da semana de disponibilidade do petsitter.
     * Se um petsitter_id for enviado, atualiza esse perfil (admin ou dono).
     * Caso contrário, atualiza o perfil do utilizador autenticado.
     */
    public function updateAvailabilityDays(Request $request): JsonResponse|PetsitterResource
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Não autenticado.'], 401);
        }

        // Se um petsitter_id for fornecido, tenta encontrar esse petsitter
        if ($request->has('petsitter_id')) {
            $petsitter = Petsitter::where('user_id', $request->input('petsitter_id'))->first();
            if (! $petsitter) {
                return response()->json(['message' => 'Perfil de petsitter não encontrado.'], 404);
            }
            // Verificar permissão: só admin ou o próprio dono podem editar
            Gate::authorize('update', $petsitter);
        } else {
            // Comportamento original: atualiza o próprio perfil
            $petsitter = Petsitter::where('user_id', $user->id)->first();
            if (! $petsitter) {
                return response()->json(['message' => 'Perfil de petsitter não encontrado.'], 404);
            }
        }

        $validated = $request->validate([
            'days' => [
                'present',
                'array',
                'max:7',
            ],
            'days.*' => [
                'string',
                Rule::in(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']),
            ],
        ]);

        // Verificar duplicatas
        $days = $validated['days'];
        $uniqueDays = array_unique($days);
        if (count($days) !== count($uniqueDays)) {
            return response()->json([
                'message' => 'Os dias da semana não podem ser repetidos.',
                'errors' => ['days' => ['Os dias da semana não podem ser repetidos.']],
            ], 422);
        }

        $petsitter->update([
            'availability_days' => $days,
        ]);

        return new PetsitterResource($petsitter->load('user'));
    }

    public function destroy(Petsitter $petsitter)
    {
        Gate::authorize('delete', $petsitter);

        $petsitter->delete();

        return response()->noContent();
    }

    public function approve(Petsitter $petsitter)
    {
        Gate::authorize('approve', $petsitter);

        if (! $petsitter->hasIdentificationDocuments()) {
            return response()->json([
                'message' => 'Não é possível aprovar um petsitter sem documentos de identificação.',
            ], 422);
        }

        $petsitter->approve();

        return new PetsitterResource($petsitter->load('user'));
    }

    public function reject(Petsitter $petsitter)
    {
        Gate::authorize('approve', $petsitter);

        $petsitter->reject();

        return new PetsitterResource($petsitter->load('user'));
    }
}
