<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PetsitterResource;
use App\Models\Petsitter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

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

    public function store(Request $request)
    {
        Gate::authorize('create', Petsitter::class);

        $data = $request->validate([
            'user_id' => 'sometimes|exists:users,id|unique:petsitters,user_id',
            'biography' => 'nullable|string',
            'experience' => 'required|string',
            'service_location' => 'required|string|max:255',
            'price_per_day' => 'required|numeric|min:0',
            'accepted_sizes' => 'nullable|array',
            'accepted_sizes.*' => 'string',
            'accepted_animal_types' => 'nullable|array',
            'accepted_animal_types.*' => 'string',
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
            'accepted_sizes' => 'nullable|array',
            'accepted_sizes.*' => 'string',
            'accepted_animal_types' => 'nullable|array',
            'accepted_animal_types.*' => 'string',
            'identification_documents' => 'sometimes|string|max:255',
        ]);

        $petsitter->update($data);

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

        $petsitter->approval_status = 'approved';
        $petsitter->save();

        return new PetsitterResource($petsitter->load('user'));
    }

    public function reject(Petsitter $petsitter)
    {
        Gate::authorize('approve', $petsitter);

        $petsitter->approval_status = 'rejected';
        $petsitter->save();

        return new PetsitterResource($petsitter->load('user'));
    }
}
