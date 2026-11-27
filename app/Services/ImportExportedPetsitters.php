<?php

namespace App\Services;

use App\Models\Petsitter;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class ImportExportedPetsitters
{
    /**
     * @param  array<int, array<string, mixed>>  $users
     * @param  array<int, array<string, mixed>>  $petsitters
     */
    public function import(array $users, array $petsitters): void
    {
        $petsittersByUserId = collect($petsitters)->keyBy('user_id');
        $petsitterColumns = array_flip(Schema::getColumnListing('petsitters'));

        foreach ($users as $userData) {
            $user = User::where('email', $userData['email'])->first();

            if (! $user) {
                continue;
            }

            $petsitterData = $petsittersByUserId->get($userData['id']);
            $petsitter = Petsitter::firstOrNew(['user_id' => $user->id]);

            if ($petsitterData) {
                $attributes = array_intersect_key([
                    'user_id' => $user->id,
                    'biography' => $petsitterData['biography'] ?? null,
                    'short_bio' => $petsitterData['short_bio'] ?? null,
                    'experience' => $petsitterData['experience'] ?? '',
                    'service_location' => $petsitterData['service_location'] ?? '',
                    'city' => $petsitterData['city'] ?? null,
                    'postal_code' => $petsitterData['postal_code'] ?? null,
                    'age' => $petsitterData['age'] ?? 18,
                    'price_per_day' => $petsitterData['price_per_day'] ?? Petsitter::MIN_PRICE_PER_DAY,
                    'average_rating' => $petsitterData['average_rating'] ?? null,
                    'accepted_sizes' => $petsitterData['accepted_sizes'] ?? null,
                    'sex' => $petsitterData['sex'] ?? null,
                    'accepted_animal_types' => $petsitterData['accepted_animal_types'] ?? null,
                    'identification_documents' => $petsitterData['identification_documents'] ?? '',
                    'approval_status' => $petsitterData['approval_status'] ?? 'pending',
                    'availability_days' => $petsitterData['availability_days'] ?? null,
                    'profile_photo' => $petsitterData['profile_photo'] ?? null,
                    'created_at' => $petsitterData['created_at'] ?? null,
                    'updated_at' => $petsitterData['updated_at'] ?? null,
                ], $petsitterColumns);

                foreach (['accepted_sizes', 'accepted_animal_types', 'availability_days'] as $arrayAttribute) {
                    if (array_key_exists($arrayAttribute, $attributes) && is_string($attributes[$arrayAttribute])) {
                        $decoded = json_decode($attributes[$arrayAttribute], true);
                        $attributes[$arrayAttribute] = is_array($decoded) ? $decoded : null;
                    }
                }

                if (! ($userData['is_petsitter'] ?? false) && $attributes['approval_status'] === 'approved') {
                    $attributes['approval_status'] = 'rejected';
                }

                $petsitter->forceFill($attributes)->save();
            } elseif ($petsitter->exists && $petsitter->approval_status === 'approved') {
                $petsitter->forceFill(['approval_status' => 'rejected'])->save();
            }

            $user->forceFill([
                'is_petsitter' => $petsitter->exists && $petsitter->approval_status === 'approved',
            ])->save();
        }
    }

    public function synchronizeCurrentDatabase(): void
    {
        Petsitter::query()
            ->where('approval_status', 'approved')
            ->whereHas('user', fn ($query) => $query->where('is_petsitter', false))
            ->update(['approval_status' => 'rejected']);

        User::query()
            ->where('is_petsitter', true)
            ->whereDoesntHave('petsitter', fn ($query) => $query->where('approval_status', 'approved'))
            ->update(['is_petsitter' => false]);
    }
}
