<?php

/**
 * Controlador API para gestão de perfis de petsitter. Fornece endpoints CRUD, aprovação/rejeição por admin e atualização de dias de disponibilidade.
 * API controller for petsitter profile management. Provides CRUD endpoints, admin approval/rejection, and availability days update.
 */

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PetsitterResource;
use App\Models\Petsitter;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PetsitterController extends Controller
{
    /**
     * Lista petsitters aprovados; admin pode filtrar candidaturas para gestão.
     * Lists approved petsitters; admins can filter applications for management.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Petsitter::class);

        $query = Petsitter::with('user', 'services')
            ->whereHas('user', fn ($query) => $query->where('is_admin', false));

        if ($request->user()?->isAdmin()) {
            // A listagem padrão é a pesquisa da home; os filtros explícitos servem à gestão de candidaturas.
            $filters = $request->validate([
                'approval_status' => ['sometimes', Rule::in(['pending', 'approved', 'rejected'])],
            ]);

            if (isset($filters['approval_status'])) {
                $query->where('approval_status', $filters['approval_status']);
            } else {
                $query->where('approval_status', 'approved')
                    ->whereHas('user', fn ($query) => $query->where('is_petsitter', true));
            }
        } else {
            $query->where('approval_status', 'approved')
                ->whereHas('user', fn ($query) => $query->where('is_petsitter', true));
        }

        return PetsitterResource::collection($query->get());
    }

    /**
     * Retorna apenas os petsitters aprovados, sem necessidade de autenticação.
     */
    public function publicIndex()
    {
        $query = Petsitter::with('user')
            ->where('approval_status', 'approved')
            ->whereHas('user', fn ($query) => $query
                ->where('is_admin', false)
                ->where('is_petsitter', true)
                ->where(function ($query) {
                    $query
                        ->where('account_status', 'active')
                        ->orWhereNull('account_status');
                })
            );

        return PetsitterResource::collection($query->get());
    }

    /**
     * Cria um novo registo após validação dos dados do pedido. Retorna o recurso criado com status 201.
     * Creates a new record after validating request data. Returns the created resource with status 201.
     */
    public function store(Request $request)
    {
        Gate::authorize('create', Petsitter::class);

        $data = $request->validate([
            'user_id' => 'sometimes|exists:users,id|unique:petsitters,user_id',
            'name' => ['sometimes', 'string', 'max:255', 'regex:/^[\p{L} ]+$/u'],
            'biography' => 'nullable|string',
            'experience' => 'required|string|max:500',
            'service_location' => 'required|string|max:255',
            'postal_code' => ['required', 'string', 'regex:/^\d{4}-\d{3}$/'],
            'age' => 'required|integer|min:18|max:99',
            'price_per_day' => ['required', ...$this->pricePerDayRule()],
            'accepted_sizes' => 'nullable|array',
            'accepted_sizes.*' => 'string|in:small,medium,large',
            'accepted_animal_types' => 'nullable|array',
            'accepted_animal_types.*' => 'string',
            'sex' => 'nullable|string|in:male,female,other',
            // o primeiro serviço, escrito pelo candidato (ex.: "Passeios ao fim de semana"); é criado logo,
            // ligado ao novo petsitter, com o preço de price_per_day. Os outros serviços criam-se no perfil.
            'service' => ['required', 'string', 'max:50'],
            // Cartão de Cidadão: JPG, PNG ou PDF até 10 MB
            'identification_documents' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ], $this->pricePerDayMessages());

        if (! $request->user()?->isAdmin() || empty($data['user_id'])) {
            $data['user_id'] = $request->user()->id;
        }

        // sem isto, um segundo pedido do mesmo utilizador dava erro 500 (user_id é a chave primária)
        if (Petsitter::where('user_id', $data['user_id'])->exists()) {
            return response()->json(['message' => 'Este utilizador já tem um perfil de petsitter.'], 409);
        }

        // guarda o documento no disco privado (storage/app/private), nunca no público
        $documentPath = $request->file('identification_documents')
            ->store('identification-documents/'.$data['user_id'], 'local');

        try {
            $petsitter = DB::transaction(function () use ($data, $documentPath) {
                if (isset($data['name'])) {
                    User::whereKey($data['user_id'])->update(['name' => $data['name']]);
                }

                $petsitter = Petsitter::create([
                    ...Arr::except($data, ['name', 'service']),
                    'identification_documents' => $documentPath,
                ]);

                $petsitter->services()->create([
                    'type' => $data['service'],
                    'price' => $data['price_per_day'],
                ]);

                return $petsitter;
            });
        } catch (\Throwable $e) {
            // se a base de dados falhar, não deixa o ficheiro perdido no disco
            Storage::disk('local')->delete($documentPath);
            throw $e;
        }

        $petsitter->refresh();

        return (new PetsitterResource($petsitter->load(['user', 'services'])))->response()->setStatusCode(201);
    }

    /**
     * Retorna os detalhes de um registo específico. Verifica permissão antes de exibir.
     * Returns details of a specific record. Checks permission before displaying.
     */
    public function show(Petsitter $petsitter)
    {
        Gate::authorize('view', $petsitter);

        return new PetsitterResource($petsitter->load('user'));
    }

    /**
     * Atualiza um registo existente com os dados validados do pedido.
     * Updates an existing record with validated request data.
     */
    public function update(Request $request, Petsitter $petsitter)
    {
        Gate::authorize('update', $petsitter);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255', 'regex:/^[\p{L} ]+$/u'],
            'phone' => 'sometimes|nullable|string|max:20',
            'biography' => 'sometimes|nullable|string',
            'experience' => 'sometimes|string',
            'service_location' => 'sometimes|string|max:255',
            'postal_code' => ['sometimes', 'nullable', 'string', 'regex:/^\d{4}-\d{3}$/'],
            'price_per_day' => ['sometimes', ...$this->pricePerDayRule()],
            'age' => 'sometimes|integer|min:18|max:99',
            'accepted_sizes' => 'sometimes|nullable|array',
            'accepted_sizes.*' => 'string',
            'sex' => 'sometimes|nullable|string|in:male,female,other',
            'accepted_animal_types' => 'sometimes|nullable|array',
            'accepted_animal_types.*' => 'string',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png|max:20480',
        ], $this->pricePerDayMessages());

        // Separar os dados do usuário dos dados do petsitter
        $userData = [];
        $petsitterData = [];

        if (isset($data['name'])) {
            $userData['name'] = $data['name'];
            unset($data['name']);
        }
        if (isset($data['phone'])) {
            // Remove qualquer caractere não numérico e garante apenas 9 dígitos
            $userData['phone'] = preg_replace('/\D/', '', $data['phone']);
            $userData['phone'] = substr($userData['phone'], 0, 9);
            unset($data['phone']);
        }

        // Atualizar o usuário se houver dados
        if (! empty($userData)) {
            $petsitter->user->update($userData);
        }

        // Processar remoção de foto de perfil
        if ($request->input('_remove_avatar')) {
            $petsitterData['profile_photo'] = null;
        }
        // Processar upload de foto de perfil - guardar como base64 na base de dados
        elseif ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $content = file_get_contents($file->getRealPath());
            $mimeType = $file->getMimeType();
            $petsitterData['profile_photo'] = 'data:'.$mimeType.';base64,'.base64_encode($content);
        }

        // Atualizar o petsitter com os dados restantes
        $petsitter->update($data);

        // Aplicar dados adicionais (profile_photo)
        if (! empty($petsitterData)) {
            $petsitter->update($petsitterData);
        }

        return new PetsitterResource($petsitter->load('user'));
    }

    /**
     * Regra do preço por dia: um número entre Petsitter::MIN_PRICE_PER_DAY e MAX_PRICE_PER_DAY.
     *
     * @return array<int, string>
     */
    private function pricePerDayRule(): array
    {
        return ['numeric', 'between:'.Petsitter::MIN_PRICE_PER_DAY.','.Petsitter::MAX_PRICE_PER_DAY];
    }

    /**
     * Mensagens em português do preço por dia e do serviço (aparecem por baixo do campo no formulário).
     *
     * @return array<string, string>
     */
    private function pricePerDayMessages(): array
    {
        return [
            'price_per_day.required' => 'Indica o preço por dia.',
            'price_per_day.numeric' => 'O preço tem de ser um número.',
            'price_per_day.between' => 'O preço por dia tem de estar entre '.Petsitter::MIN_PRICE_PER_DAY
                .' € e '.Petsitter::MAX_PRICE_PER_DAY.' €.',
            'service.required' => 'Escreve o teu serviço.',
            'service.max' => 'O serviço pode ter no máximo 50 caracteres.',
        ];
    }

    /**
     * Atualiza os dias da semana de disponibilidade do petsitter.
     * Se um petsitter_id for enviado, atualiza esse perfil (admin ou dono).
     * Caso contrário, atualiza o perfil do utilizador autenticado.
     */
    public function updateAvailabilityDays(Request $request): JsonResponse|PetsitterResource
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Não autenticado.'], 401);
        }

        // Se um petsitter_id for fornecido, tenta encontrar esse petsitter
        if ($request->has('petsitter_id')) {
            $petsitter = Petsitter::where('user_id', $request->input('petsitter_id'))->first();
            if (! $petsitter) {
                return response()->json(['message' => 'Perfil de petsitter não encontrado.'], 404);
            }
            // Verificar permissão: só admin ou o próprio dono podem editar
            Gate::authorize('update', $petsitter);
        } else {
            // Comportamento original: atualiza o próprio perfil
            $petsitter = Petsitter::where('user_id', $user->id)->first();
            if (! $petsitter) {
                return response()->json(['message' => 'Perfil de petsitter não encontrado.'], 404);
            }
        }

        $validated = $request->validate([
            'days' => [
                'present',
                'array',
                'max:7',
            ],
            'days.*' => [
                'string',
                Rule::in(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']),
            ],
        ]);

        // Verificar duplicatas
        $days = $validated['days'];
        $uniqueDays = array_unique($days);
        if (count($days) !== count($uniqueDays)) {
            return response()->json([
                'message' => 'Os dias da semana não podem ser repetidos.',
                'errors' => ['days' => ['Os dias da semana não podem ser repetidos.']],
            ], 422);
        }

        $petsitter->update([
            'availability_days' => $days,
        ]);

        return new PetsitterResource($petsitter->load('user'));
    }

    /**
     * Elimina um registo após verificar permissão. Retorna resposta 204 No Content.
     * Deletes a record after checking permission. Returns 204 No Content response.
     */
    public function destroy(Petsitter $petsitter)
    {
        Gate::authorize('delete', $petsitter);

        $petsitter->delete();

        return response()->noContent();
    }

    /**
     * Aprova o perfil de um petsitter (apenas admin). Altera o status para 'approved'.
     * Approves a petsitter's profile (admin only). Changes status to 'approved'.
     */
    public function approve(Petsitter $petsitter)
    {
        Gate::authorize('approve', $petsitter);

        if (! $petsitter->hasIdentificationDocuments()) {
            return response()->json([
                'message' => 'Não é possível aprovar um petsitter sem documentos de identificação.',
            ], 422);
        }

        $petsitter->approve();

        return new PetsitterResource($petsitter->load('user'));
    }

    /**
     * Rejeita o perfil de um petsitter (apenas admin). Altera o status para 'rejected'.
     * Rejects a petsitter's profile (admin only). Changes status to 'rejected'.
     */
    public function reject(Petsitter $petsitter)
    {
        Gate::authorize('approve', $petsitter);

        $petsitter->reject();

        return new PetsitterResource($petsitter->load('user'));
    }

    /**
     * Mostra o documento de identificação do petsitter (apenas admin), para validar a candidatura.
     * Shows the petsitter's identification document (admin only), to review the application.
     */
    public function identificationDocument(Petsitter $petsitter)
    {
        // mesma permissão de aprovar/rejeitar: só admin
        Gate::authorize('approve', $petsitter);

        $path = $petsitter->identification_documents;

        if (! $path || ! Storage::disk('local')->exists($path)) {
            return response()->json(['message' => 'Documento de identificação não encontrado.'], 404);
        }

        // response() abre no browser (PDF/imagem) em vez de forçar o download
        return Storage::disk('local')->response($path);
    }
}
