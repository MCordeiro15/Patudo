<?php

/**
 * Serve a foto de perfil de um utilizador num URL próprio (/users/{id}/avatar).
 * As fotos estão guardadas em base64 na base de dados; o chat (TalkJS) precisa de um URL e não de base64.
 * Serves a user's profile photo at its own URL (/users/{id}/avatar).
 * Photos are stored as base64 in the database; the chat (TalkJS) needs a URL, not base64.
 */

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class UserAvatarController extends Controller
{
    /** Tipos de imagem aceites (sem SVG, que pode ter scripts). */
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    public function __invoke(User $user): Response
    {
        $photo = self::photoOf($user);

        abort_if($photo === null, 404);

        // foto em base64: data:image/jpeg;base64,....
        if (str_starts_with($photo, 'data:')) {
            if (! preg_match('#^data:(image/[a-z0-9.+-]+);base64,(.+)$#is', $photo, $matches)
                || ! in_array(strtolower($matches[1]), self::ALLOWED_TYPES, true)) {
                abort(404);
            }

            $binary = base64_decode($matches[2], true);
            abort_if($binary === false, 404);

            return response($binary, 200, [
                'Content-Type' => strtolower($matches[1]),
                'Cache-Control' => 'private, max-age=86400',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        // URL externo: redireciona
        if (str_starts_with($photo, 'http://') || str_starts_with($photo, 'https://')) {
            return redirect()->away($photo);
        }

        // caminho no disco público
        abort_unless(Storage::disk('public')->exists($photo), 404);

        return Storage::disk('public')->response($photo, null, [
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    /**
     * A foto do utilizador: a do perfil de petsitter tem prioridade, depois a foto do user. null se não tiver.
     * The user's photo: the petsitter profile photo first, then the user's own photo. null if none.
     */
    public static function photoOf(User $user): ?string
    {
        $petsitterPhoto = $user->petsitter?->profile_photo;

        return $petsitterPhoto ?: ($user->avatar ?: null);
    }

    /**
     * URL da foto, ou null se o utilizador não tiver foto. O ?v= muda quando a foto muda, para o browser
     * (e o TalkJS) não ficarem com a foto antiga em cache.
     * Photo URL, or null if the user has no photo. ?v= changes when the photo changes (cache busting).
     */
    public static function urlFor(User $user): ?string
    {
        $photo = self::photoOf($user);

        if ($photo === null) {
            return null;
        }

        return route('users.avatar', ['user' => $user->id, 'v' => substr(md5($photo), 0, 8)]);
    }
}
