<?php

/**
 * Controlador API para gestão de utilizadores (admin). Fornece endpoints para listar, ver, atualizar e suspender contas de utilizadores.
 * API controller for user management (admin). Provides endpoints to list, view, update, and suspend user accounts.
 */
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Lista todos os registos com paginação. Admin vê todos, utilizador normal vê apenas os seus.
     * Lists all records with pagination. Admin sees all, normal user sees only their own.
     */
    public function index()
    {
        Gate::authorize('viewAny', User::class);

        return UserResource::collection(User::all());
    }

    /**
     * Cria um novo registo após validação dos dados do pedido. Retorna o recurso criado com status 201.
     * Creates a new record after validating request data. Returns the created resource with status 201.
     */
    public function store(Request $request)
    {
        Gate::authorize('create', User::class);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|min:8|confirmed',
        ]);

        // Limpar o phone para apenas 9 dígitos numéricos
        if (!empty($data['phone'])) {
            $data['phone'] = preg_replace('/\D/', '', $data['phone']);
            $data['phone'] = substr($data['phone'], 0, 9);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
        ]);

        return new UserResource($user)->response()->setStatusCode(201);
    }

    /**
     * Retorna os detalhes de um registo específico. Verifica permissão antes de exibir.
     * Returns details of a specific record. Checks permission before displaying.
     */
    public function show(User $user)
    {
        Gate::authorize('view', $user);

        return new UserResource($user);
    }

    /**
     * Atualiza um registo existente com os dados validados do pedido.
     * Updates an existing record with validated request data.
     */
    public function update(Request $request, User $user)
    {
        Gate::authorize('update', $user);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
        ]);

        // Limpar o phone para apenas 9 dígitos numéricos
        if (isset($data['phone'])) {
            $data['phone'] = preg_replace('/\D/', '', $data['phone']);
            $data['phone'] = substr($data['phone'], 0, 9);
        }

        $user->update($data);

        return new UserResource($user);
    }

    /**
     * Elimina um registo após verificar permissão. Retorna resposta 204 No Content.
     * Deletes a record after checking permission. Returns 204 No Content response.
     */
    public function destroy(User $user)
    {
        Gate::authorize('delete', $user);

        $user->delete();

        return response()->noContent();
    }

    /**
     * Suspende a conta de um utilizador (apenas admin). Impede login até ser reativada.
     * Suspends a user's account (admin only). Prevents login until reactivated.
     */
    public function suspend(User $user)
    {
        Gate::authorize('suspend', $user);

        $user->account_status = 'suspended';
        $user->save();

        return new UserResource($user);
    }
}
