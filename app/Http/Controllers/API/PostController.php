<?php

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

    public function store(Request $request)
    {
        Gate::authorize('create', Post::class);

        $data = $request->validate([
            'description' => 'required|string|max:2000',
            'photos' => 'nullable|array|max:5',
            'photos.*' => 'image|mimes:jpg,jpeg,png|max:5120',
        ]);

        $paths = [];
        foreach ($request->file('photos', []) as $file) {
            $paths[] = $file->store('posts', 'public');
        }

        $post = $request->user()->petsitter->posts()->create([
            'description' => $data['description'],
            'photos' => $paths,
        ]);

        return (new PostResource($post))->response()->setStatusCode(201);
    }

    public function show(Post $post)
    {
        Gate::authorize('view', $post);

        return new PostResource($post);
    }

    public function update(Request $request, Post $post)
    {
        Gate::authorize('update', $post);

        $data = $request->validate([
            'description' => 'required|string|max:2000',
        ]);

        $post->update($data);

        return new PostResource($post);
    }

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
