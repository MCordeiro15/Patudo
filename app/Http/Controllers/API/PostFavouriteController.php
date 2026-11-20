<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;

class PostFavouriteController extends Controller
{
    /**
     * Lista os posts favoritos do utilizador autenticado.
     * Lists the authenticated user's favourite posts.
     */
    public function index(Request $request)
    {
        $posts = $request->user()
            ->favouritePosts()
            ->with('petsitter')
            ->get();

        return PostResource::collection($posts);
    }


    /**
     * Adiciona um post aos favoritos do utilizador.
     * Adds a post to the user's favourites.
     */
    public function store(Request $request, Post $post)
    {
        $user = $request->user();

        if ($user->favouritePosts()->whereKey($post->id)->exists()) {
            return response()->json([
                'message' => 'Post already exists in favourites.',
            ], 409);
        }

        $user->favouritePosts()->attach($post->id);

        return response()->json([
            'message' => 'Post added to favourites successfully.',
        ], 201);
    }

    /**
     * Remove um post dos favoritos do utilizador.
     * Removes a post from the user's favourites.
     */
    public function destroy(Request $request, Post $post)
    {
        $user = $request->user();

        if (!$user->favouritePosts()->whereKey($post->id)->exists()) {
            return response()->json([
                'message' => 'Favourite not found.',
            ], 404);
        }

        $user->favouritePosts()->detach($post->id);

        return response()->noContent();
    }
}
