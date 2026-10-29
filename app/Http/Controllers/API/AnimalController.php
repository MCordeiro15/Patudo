<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\AnimalResource;
use App\Models\Animal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AnimalController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Animal::class);

        $query = Animal::with('user');

        if (! $request->user()?->isAdmin()) {
            $query->where('user_id', $request->user()?->id);
        }

        return AnimalResource::collection($query->get());
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Animal::class);

        $data = $request->validate([
            'user_id' => 'sometimes|exists:users,id',
            'name' => 'required|string|max:255',
            'species' => 'required|string|max:255',
            'size' => 'required|string|max:255',
            'special_needs' => 'nullable|string',
        ]);

        if (! $request->user()?->isAdmin() || empty($data['user_id'])) {
            $data['user_id'] = $request->user()->id;
        }

        $animal = Animal::create($data);

        return (new AnimalResource($animal->load('user')))->response()->setStatusCode(201);
    }

    public function show(Animal $animal)
    {
        Gate::authorize('view', $animal);

        return new AnimalResource($animal->load('user'));
    }

    public function update(Request $request, Animal $animal)
    {
        Gate::authorize('update', $animal);

        $data = $request->validate([
            'user_id' => 'sometimes|exists:users,id',
            'name' => 'sometimes|string|max:255',
            'species' => 'sometimes|string|max:255',
            'size' => 'sometimes|string|max:255',
            'special_needs' => 'nullable|string',
        ]);

        if (! $request->user()?->isAdmin()) {
            unset($data['user_id']);
        }

        $animal->update($data);

        return new AnimalResource($animal->load('user'));
    }

    public function destroy(Animal $animal)
    {
        Gate::authorize('delete', $animal);

        $animal->delete();

        return response()->noContent();
    }
}
