<?php


/**
 * Controlador API para gestão de posts de petsitters. Permite criar posts com até 5 fotos, listar posts, ver detalhes, atualizar e eliminar.
 * API controller for petsitter posts management. Allows creating posts with up to 5 photos, listing posts, viewing details, updating, and deleting.
 */
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Petsitter;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    /**
     * Lista todos os registos com paginação. Admin vê todos, utilizador normal vê apenas os seus.
     * Lists all records with pagination. Admin sees all, normal user sees only their own.
     */
    public function index()
    {
        Gate::authorize('viewAny', Post::class);

        return PostResource::collection(Post::latest()->paginate(15));
    }

    public function byPetsitter(Petsitter $petsitter)
    {
        Gate::authorize('viewAny', Post::class);

        return PostResource::collection(
            $petsitter->posts()->latest()->paginate(15)
        );
    }

    /**
     * Cria um novo registo após validação dos dados do pedido. Retorna o recurso criado com status 201.
     * Creates a new record after validating request data. Returns the created resource with status 201.
     */
    public function store(Request $request)
    {
        Gate::authorize('create', Post::class);

        $data = $request->validate([
            'title' => 'required|string|max:100',
            'description' => 'required|string|max:2000',
            'photos' => 'nullable|array|max:5',
            'photos.*' => 'image|mimes:jpg,jpeg,png|max:5120',
        ]);

        $paths = [];
        foreach ($request->file('photos', []) as $file) {
            $paths[] = $file->store('posts', 'public');
        }

        $post = $request->user()->petsitter->posts()->create([
            'title' => $data['title'],
            'description' => $data['description'],
            'photos' => $paths,
        ]);

        return (new PostResource($post))->response()->setStatusCode(201);
    }

    /**
     * Retorna os detalhes de um registo específico. Verifica permissão antes de exibir.
     * Returns details of a specific record. Checks permission before displaying.
     */
    public function show(Post $post)
    {
        Gate::authorize('view', $post);

        return new PostResource($post);
    }

    /**
     * Atualiza um registo existente com os dados validados do pedido.
     * Updates an existing record with validated request data.
     */
    public function update(Request $request, Post $post)
    {
        Gate::authorize('update', $post);

        $data = $request->validate([
            'title' => 'sometimes|required|string|max:100',
            'description' => 'sometimes|string|max:2000',
        ]);

        $post->update($data);

        return new PostResource($post);
    }

    /**
     * Elimina um registo após verificar permissão. Retorna resposta 204 No Content.
     * Deletes a record after checking permission. Returns 204 No Content response.
     */
    public function destroy(Post $post)
    {
        Gate::authorize('delete', $post);

        foreach ($post->photos ?? [] as $path) {
            Storage::disk('public')->delete($path);
        }

        $post->delete();

        return response()->noContent();
    }
}
