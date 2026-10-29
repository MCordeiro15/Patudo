<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', User::class);

        return UserResource::collection(User::all());
    }

    public function store(Request $request)
    {
        Gate::authorize('create', User::class);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:9',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
        ]);

        return (new UserResource($user))->response()->setStatusCode(201);
    }

    public function show(User $user)
    {
        Gate::authorize('view', $user);

        return new UserResource($user);
    }

    public function update(Request $request, User $user)
    {
        Gate::authorize('update', $user);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,'.$user->id,
            'phone' => 'nullable|string|max:9',
        ]);

        $user->update($data);

        return new UserResource($user);
    }

    public function destroy(User $user)
    {
        Gate::authorize('delete', $user);

        $user->delete();

        return response()->noContent();
    }

    public function suspend(User $user)
    {
        Gate::authorize('suspend', $user);

        $user->account_status = 'suspended';
        $user->save();

        return new UserResource($user);
    }
}
