<?php

namespace App\Http\Controllers;

use App\Http\Resources\PetsitterResource;
use App\Models\Petsitter;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PetsitterProfileController extends Controller
{
    /**
     * Display the petsitter profile page for the authenticated user.
     */
    public function show(Request $request)
    {
        $petsitter = Petsitter::where('user_id', $request->user()->id)->with('user')->first();

        return Inertia::render('Petsitter/Profile', [
            'petsitter' => $petsitter ? new PetsitterResource($petsitter) : null,
        ]);
    }
}
