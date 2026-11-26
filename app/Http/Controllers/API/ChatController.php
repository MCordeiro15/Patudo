<?php

/**
 * PHP file: ChatController.php
 */

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UserAvatarController;
use App\Models\Booking;
use App\Models\Petsitter;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller do chat (TalkJS). Só prepara os dados de que o frontend precisa: a sessão (com token
 * opcional) e a lista de contactos permitidos. As mensagens passam diretamente entre o browser e o TalkJS.
 * Chat controller (TalkJS). Only prepares the data the frontend needs: the session (with optional token)
 * and the list of allowed contacts. Messages go directly between the browser and TalkJS.
 */
class ChatController extends Controller
{
    /**
     * GET /api/chat/session
     * Devolve o appId, o utilizador autenticado e (se existir chave secreta) um token JWT para o TalkJS.
     * O utilizador vem sempre da sessão, nunca do pedido, por isso ninguém consegue pedir um token de outra pessoa.
     * Returns the appId, the authenticated user and (if a secret key exists) a JWT token for TalkJS.
     */
    public function session(Request $request): JsonResponse
    {
        $user = $request->user();
        $appId = config('services.talkjs.app_id');

        abort_if(empty($appId), 503, 'O chat não está configurado.');

        return response()->json([
            'app_id' => $appId,
            'user' => $this->chatUser($user),
            'token' => $this->makeToken($user, $appId),
        ]);
    }

    /**
     * GET /api/chat/contacts
     * Regra: um dono pode falar com qualquer petsitter aprovado. Um petsitter pode falar com os donos
     * das reservas que lhe fizeram (botão "Conversar" nas Reservas); aos outros só responde se lhe escreverem
     * (essas conversas vêm do TalkJS).
     * Rule: an owner can message any approved petsitter. A petsitter can message the owners of the bookings
     * made with them ("Conversar" button on Reservas); anyone else only if they write first (from TalkJS).
     */
    public function contacts(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isPetsitter()) {
            // donos (ativos) das reservas deste petsitter; bookings.petsitter_id guarda o id do user
            $owners = User::whereIn('id', Booking::where('petsitter_id', $user->id)->select('user_id'))
                ->where('id', '!=', $user->id)
                ->where('account_status', 'active')
                ->get()
                ->map(fn (User $owner) => $this->chatUser($owner, 'Dono'))
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();

            return response()->json(['data' => $owners]);
        }

        $contacts = Petsitter::with('user')
            ->where('approval_status', 'approved')
            ->where('user_id', '!=', $user->id)
            ->get()
            ->filter(fn (Petsitter $petsitter) => $petsitter->user !== null
                && $petsitter->user->account_status === 'active')
            // liga o perfil já carregado ao user, para a foto não fazer uma query por petsitter
            ->map(fn (Petsitter $petsitter) => $this->chatUser(
                $petsitter->user->setRelation('petsitter', $petsitter),
                'Petsitter',
            ))
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return response()->json(['data' => $contacts]);
    }

    /**
     * Dados mínimos de um utilizador para o chat (sem email nem telefone).
     * Minimal user data for the chat (no email or phone).
     */
    private function chatUser(User $user, ?string $role = null): array
    {
        return [
            'id' => (string) $user->id,
            'name' => $user->name,
            'role' => $role ?? ($user->isPetsitter() ? 'Petsitter' : 'Dono'),
            // URL da foto de perfil (null se não tiver), para o TalkJS e para a lista de conversas
            'photo_url' => $this->chatPhotoUrl($user),
        ];
    }

    private function chatPhotoUrl(User $user): ?string
    {
        // No perfil público do petsitter, só a foto do perfil conta como foto; não usar avatar genérico do user.
        if ($user->isPetsitter() && ! $user->petsitter?->profile_photo) {
            return 'https://ui-avatars.com/api/?name='.urlencode($user->name ?? 'P').'&background=random';
        }

        return UserAvatarController::urlFor($user);
    }

    /**
     * Cria o JWT (HS256) que o TalkJS pede quando a autenticação está ativa no dashboard.
     * Sem TALKJS_SECRET_KEY devolve null (modo de desenvolvimento, sem autenticação).
     * Creates the HS256 JWT TalkJS asks for when authentication is enabled in the dashboard.
     * Without TALKJS_SECRET_KEY it returns null (development mode, no authentication).
     */
    private function makeToken(User $user, string $appId): ?string
    {
        $secret = config('services.talkjs.secret_key');

        if (empty($secret)) {
            return null;
        }

        $header = $this->base64Url(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = $this->base64Url(json_encode([
            'tokenType' => 'user',
            'iss' => $appId,
            'sub' => (string) $user->id,
            'exp' => time() + 3600,
        ]));
        $signature = $this->base64Url(hash_hmac('sha256', $header.'.'.$payload, $secret, true));

        return $header.'.'.$payload.'.'.$signature;
    }

    private function base64Url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
