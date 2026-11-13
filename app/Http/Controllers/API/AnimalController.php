<?php


/**
 * Controlador API para gestão de animais de estimação. Fornece endpoints CRUD (criar, listar, ver detalhes, atualizar, eliminar) para os animais dos utilizadores.
 * API controller for pet management. Provides CRUD endpoints (create, list, view details, update, delete) for users' pets.
 */
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\AnimalResource;
use App\Models\Animal;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class AnimalController extends Controller
{
    /**
     * Lista todos os registos com paginação. Admin vê todos, utilizador normal vê apenas os seus.
     * Lists all records with pagination. Admin sees all, normal user sees only their own.
     */
    public function index(Request $request): Responsable
    {
        Gate::authorize('viewAny', Animal::class);

        $query = Animal::with('user');

        if (!$request->user()?->isAdmin()) {
            $query->where('user_id', $request->user()?->id);
        }

        return AnimalResource::collection($query->get());
    }

    /**
     * Cria um novo registo após validação dos dados do pedido. Retorna o recurso criado com status 201.
     * Creates a new record after validating request data. Returns the created resource with status 201.
     */
    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', Animal::class);

        $data = $request->validate([
            'user_id' => 'sometimes|exists:users,id',
            'name' => 'required|string|max:255',
            'species' => 'required|string|max:255',
            'breed' => 'required|string|max:255',
            'size' => 'required|string|max:255',
            'special_needs' => 'nullable|string',
            'aggression' => 'nullable|string|max:255',
            'medication' => 'nullable|string',
            'sex' => 'nullable|string|max:255',
            'photo' => 'nullable|string|max:255',
            'weight' => 'nullable|numeric|min:0|max:999.99',
        ]);

        if (!$request->user()?->isAdmin() || empty($data['user_id'])) {
            $data['user_id'] = $request->user()->id;
        }

        $animal = Animal::create($data);

        return new AnimalResource($animal->load('user'))->response()->setStatusCode(201);
    }

    /**
     * Retorna os detalhes de um registo específico. Verifica permissão antes de exibir.
     * Returns details of a specific record. Checks permission before displaying.
     */
    public function show(Animal $animal): Responsable
    {
        Gate::authorize('view', $animal);

        return new AnimalResource($animal->load('user'));
    }

    /**
     * Atualiza um registo existente com os dados validados do pedido.
     * Updates an existing record with validated request data.
     */
    public function update(Request $request, Animal $animal): Responsable
    {
        Gate::authorize('update', $animal);

        $data = $request->validate([
            'user_id' => 'sometimes|exists:users,id',
            'name' => 'sometimes|string|max:255',
            'species' => 'sometimes|string|max:255',
            'breed' => 'sometimes|string|max:255',
            'size' => 'sometimes|string|max:255',
            'special_needs' => 'nullable|string',
            'aggression' => 'nullable|string|max:255',
            'medication' => 'nullable|string',
            'sex' => 'nullable|string|max:255',
            'photo' => 'nullable|string|max:255',
            'weight' => 'nullable|numeric|min:0|max:999.99',
        ]);

        if (!$request->user()?->isAdmin()) {
            unset($data['user_id']);
        }

        $animal->update($data);

        return new AnimalResource($animal->load('user'));
    }

    /**
     * Elimina um registo após verificar permissão. Retorna resposta 204 No Content.
     * Deletes a record after checking permission. Returns 204 No Content response.
     */
    public function destroy(Animal $animal): Response
    {
        Gate::authorize('delete', $animal);

        $animal->delete();

        return response()->noContent();
    }
}
