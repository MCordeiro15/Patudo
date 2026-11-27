<?php

/**
 * Classe base abstrata para todos os controladores da aplicação. Fornece funcionalidade comum.
 * Abstract base class for all application controllers. Provides common functionality.
 */

namespace App\Http\Controllers;

use App\Models\Petsitter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PetsitterProfileController extends Controller
{
    /**
     * Display the petsitter profile page for the authenticated user.
     */
    public function showOwn(Request $request)
    {
        $user = $request->user();

        abort_if(in_array($user->account_status, ['suspended', 'banned'], true), 403);

        // Só mostra o perfil de petsitter quando o papel e a aprovação estão ativos.
        $petsitter = Petsitter::where('user_id', $user->id)->first();
        if (! $petsitter || ! $user->isPetsitter()) {
            return redirect()->route('owner.profile');
        }

        $petsitter->load('user');

        return $this->renderProfile($petsitter, true, true, $user->isAdmin());
    }

    /**
     * Display another petsitter's public profile.
     */
    public function show(Request $request, Petsitter $petsitter)
    {
        $viewer = $request->user();

        abort_if(in_array($viewer->account_status, ['suspended', 'banned'], true), 403);
        abort_if(
            in_array($petsitter->user?->account_status, ['suspended', 'banned'], true) && ! $viewer->isAdmin(),
            403,
        );

        $isOwnProfile = $viewer->id === $petsitter->user_id;

        // O próprio só vê o perfil de petsitter se tiver uma aprovação ativa.
        if ($isOwnProfile && ! $viewer->isAdmin() && ! $viewer->isPetsitter()) {
            return redirect()->route('owner.profile');
        }

        $isPublicPetsitter = $petsitter->approval_status === 'approved' && $petsitter->user?->isPetsitter();
        abort_unless(
            $viewer->isAdmin() || $isOwnProfile || $isPublicPetsitter,
            404,
        );

        $petsitter->load('user');

        if (! $isOwnProfile && ! $viewer->isAdmin()) {
            return Inertia::render('Petsitter/ProfileOwnerView', [
                'petsitterId' => $petsitter->user_id,
            ]);
        }

        return $this->renderProfile(
            $petsitter,
            $isOwnProfile,
            $isOwnProfile || $viewer->isAdmin(),
            $viewer->isAdmin(),
        );
    }

    private function renderProfile(?Petsitter $petsitter, bool $isOwnProfile, bool $canSeePrivate, bool $isAdmin = false)
    {
        $profileData = null;

        if ($petsitter) {
            $profileData = [
                'id' => $petsitter->user_id,
                'user_id' => $petsitter->user_id,
                'name' => $petsitter->user->name,
                'phone' => $petsitter->user->phone,
                'avatar_url' => $petsitter->profile_photo
                    ? (str_starts_with($petsitter->profile_photo, 'data:')
                        ? $petsitter->profile_photo
                        : Storage::disk('public')->url($petsitter->profile_photo))
                    : 'https://ui-avatars.com/api/?name='.urlencode($petsitter->user->name ?? 'P').'&background=random',
                'biography' => $petsitter->biography,
                'experience' => $petsitter->experience ?? '',
                'service_location' => $petsitter->service_location,
                'age' => $petsitter->age,
                'price_per_day' => $petsitter->price_per_day ?? 0,
                'accepted_sizes' => $petsitter->accepted_sizes ?? [],
                'accepted_animal_types' => $petsitter->accepted_animal_types ?? [],
                'sex' => $petsitter->sex,
                'approval_status' => $petsitter->approval_status,
                'availability_days' => $petsitter->availability_days ?? [],
                'created_at' => $petsitter->created_at?->toIso8601String(),
                'updated_at' => $petsitter->updated_at?->toIso8601String(),
            ];

            if ($canSeePrivate) {
                $profileData['identification_documents'] = $petsitter->identification_documents;
            }
        }

        return Inertia::render('Petsitter/Profile', [
            'petsitter' => $profileData,
            'isOwnProfile' => $isOwnProfile,
            'isAdmin' => $isAdmin,
        ]);
    }
}
