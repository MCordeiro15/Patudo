<?php

/**
 * Controlador API para os serviços dos petsitters. Lista, cria, altera e apaga serviços (tipo e preço).
 * API controller for petsitter services. Lists, creates, updates, and deletes services (type and price).
 */
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PetsitterServiceResource;
use App\Models\Petsitter;
use App\Models\PetsitterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PetsitterServiceController extends Controller
{
    /**
     * Lista os serviços de um petsitter. Usa a mesma regra de visibilidade do perfil do petsitter.
     * Lists a petsitter's services. Uses the same visibility rule as the petsitter profile.
     */
    public function index(Petsitter $petsitter)
    {
        Gate::authorize('view', $petsitter);

        return PetsitterServiceResource::collection($petsitter->services()->orderBy('type')->get());
    }

    /**
     * Cria um serviço para o petsitter. Um petsitter não pode ter o mesmo tipo duas vezes (422).
     * Creates a service for the petsitter. A petsitter cannot have the same type twice (422).
     */
    public function store(Request $request, Petsitter $petsitter)
    {
        Gate::authorize('create', [PetsitterService::class, $petsitter]);

        $data = $request->validate([
            'type' => [
                'required',
                'string',
                'max:255',
                Rule::unique('petsitter_services', 'type')->where('petsitter_id', $petsitter->user_id),
            ],
            'price' => 'required|numeric|min:0',
        ]);

        $service = $petsitter->services()->create($data);

        return (new PetsitterServiceResource($service))->response()->setStatusCode(201);
    }

    /**
     * Altera o tipo e/ou o preço de um serviço (PUT e PATCH chegam aqui).
     * Updates a service's type and/or price (both PUT and PATCH land here).
     */
    public function update(Request $request, PetsitterService $service)
    {
        Gate::authorize('update', $service);

        $data = $request->validate([
            'type' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('petsitter_services', 'type')
                    ->where('petsitter_id', $service->petsitter_id)
                    ->ignore($service->id),
            ],
            'price' => 'sometimes|required|numeric|min:0',
        ]);

        $service->update($data);

        return new PetsitterServiceResource($service);
    }

    /**
     * Apaga um serviço. Retorna 204 No Content.
     * Deletes a service. Returns 204 No Content.
     */
    public function destroy(PetsitterService $service)
    {
        Gate::authorize('delete', $service);

        // um serviço com reservas não se apaga, para não perder o histórico
        if ($service->bookings()->exists()) {
            return response()->json([
                'message' => 'Este serviço já tem reservas e não pode ser apagado.',
            ], 409);
        }

        $service->delete();

        return response()->noContent();
    }
}
