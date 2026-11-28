<?php

/**
 * Controlador API para gestão de perfis de petsitter. Fornece endpoints CRUD, aprovação/rejeição por admin e atualização de dias de disponibilidade.
 * API controller for petsitter profile management. Provides CRUD endpoints, admin approval/rejection, and availability days update.
 */

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PetsitterResource;
use App\Models\Petsitter;
use App\Models\PetsitterApplication;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
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
                // a página de verificações precisa das candidaturas (atual e anteriores)
                $query->where('approval_status', $filters['approval_status'])
                    ->with('applications.decidedBy');
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

        // aceita um ficheiro (o formulário atual) ou vários (ex.: frente e verso do Cartão de Cidadão)
        $rawDocuments = $request->files->get('identification_documents');
        if ($rawDocuments !== null && ! is_array($rawDocuments)) {
            $request->files->set('identification_documents', [$rawDocuments]);
        }

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
            // Cartão de Cidadão (um ou mais ficheiros): JPG, PNG ou PDF até 10 MB cada
            'identification_documents' => ['required', 'array', 'min:1', 'max:'.PetsitterApplication::MAX_DOCUMENTS, $this->identificationFilesRule()],
        ], $this->pricePerDayMessages());

        if (! $request->user()?->isAdmin() || empty($data['user_id'])) {
            $data['user_id'] = $request->user()->id;
        }

        // só quem foi recusado pode voltar a candidatar-se (user_id é a chave primária de petsitters)
        $existing = Petsitter::find($data['user_id']);
        if ($existing && $existing->approval_status !== 'rejected') {
            return response()->json(['message' => 'Este utilizador já tem um perfil de petsitter.'], 409);
        }

        if (PetsitterApplication::where('user_id', $data['user_id'])->count() >= PetsitterApplication::MAX_PER_USER) {
            return response()->json([
                'message' => 'Atingiste o limite de '.PetsitterApplication::MAX_PER_USER.' candidaturas a petsitter.',
            ], 422);
        }

        // guarda os documentos no disco privado (storage/app/private), nunca no público
        $documentPaths = array_map(
            fn ($file) => $file->store('identification-documents/'.$data['user_id'], 'local'),
            $request->file('identification_documents'),
        );

        try {
            $petsitter = DB::transaction(function () use ($data, $documentPaths, $existing) {
                if (isset($data['name'])) {
                    User::whereKey($data['user_id'])->update(['name' => $data['name']]);
                }

                $profile = [
                    ...Arr::except($data, ['name', 'service', 'identification_documents']),
                    // compatibilidade: o perfil guarda o primeiro documento (usado ao aprovar)
                    'identification_documents' => $documentPaths[0],
                ];

                if ($existing) {
                    // nova candidatura depois de uma recusa: o perfil volta a pendente
                    $petsitter = $existing;
                    $petsitter->fill($profile);
                    $petsitter->approval_status = 'pending';
                    $petsitter->save();
                } else {
                    $petsitter = Petsitter::create($profile);
                }

                $petsitter->services()->firstOrCreate(
                    ['type' => $data['service']],
                    ['price' => $data['price_per_day']],
                );

                // cada candidatura fica num registo próprio; as antigas não são apagadas
                PetsitterApplication::create([
                    'user_id' => $petsitter->user_id,
                    'identification_documents' => $documentPaths,
                ]);

                return $petsitter;
            });
        } catch (\Throwable $e) {
            // se a base de dados falhar, não deixa ficheiros perdidos no disco
            Storage::disk('local')->delete($documentPaths);
            throw $e;
        }

        $petsitter->refresh();

        return (new PetsitterResource($petsitter->load(['user', 'services', 'applications'])))->response()->setStatusCode(201);
    }

    /**
     * Retorna os detalhes de um registo específico. Verifica permissão antes de exibir.
     * Returns details of a specific record. Checks permission before displaying.
     */
    public function show(Petsitter $petsitter)
    {
        Gate::authorize('view', $petsitter);

        // o admin e o próprio candidato veem as candidaturas (estado, motivos, mensagem)
        $viewer = request()->user();
        if ($viewer && ($viewer->isAdmin() || $viewer->id === $petsitter->user_id)) {
            $petsitter->load('applications.decidedBy');
        }

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
    public function approve(Request $request, Petsitter $petsitter)
    {
        Gate::authorize('approve', $petsitter);

        if (! $petsitter->hasIdentificationDocuments()) {
            return response()->json([
                'message' => 'Não é possível aprovar um petsitter sem documentos de identificação.',
            ], 422);
        }

        DB::transaction(function () use ($request, $petsitter) {
            $petsitter->approve();

            $this->currentApplication($petsitter)->forceFill([
                'status' => PetsitterApplication::APPROVED,
                'reasons' => null,
                'decided_by' => $request->user()->id,
                'decided_at' => now(),
            ])->save();

            $petsitter->user->notifications()->create([
                'type' => 'verification_approved',
                'message' => 'A tua candidatura a petsitter foi aprovada. Já podes receber pedidos de reserva.',
            ]);
        });

        return new PetsitterResource($this->loadForAdmin($petsitter));
    }

    /**
     * Rejeita o perfil de um petsitter (apenas admin), com pelo menos um motivo. Com o motivo "Outro"
     * a mensagem é obrigatória. Guarda os motivos na candidatura e avisa o candidato.
     * Rejects a petsitter's profile (admin only) with at least one reason; "other" requires a message.
     */
    public function reject(Request $request, Petsitter $petsitter)
    {
        Gate::authorize('approve', $petsitter);

        $data = $request->validate([
            'reasons' => ['required', 'array', 'min:1'],
            'reasons.*' => ['string', 'distinct', Rule::in(PetsitterApplication::REASONS)],
            // com o motivo "Outro" a mensagem é obrigatória
            'message' => [
                Rule::requiredIf(fn () => in_array('other', (array) $request->input('reasons', []), true)),
                'nullable', 'string', 'max:2000',
            ],
        ], [
            'reasons.required' => 'Escolhe pelo menos um motivo.',
            'reasons.min' => 'Escolhe pelo menos um motivo.',
            'reasons.*.in' => 'Motivo inválido.',
            'message.required' => 'Com o motivo "Outro" tens de escrever uma mensagem.',
        ]);

        DB::transaction(function () use ($request, $petsitter, $data) {
            $petsitter->reject();

            $this->currentApplication($petsitter)->forceFill([
                'status' => PetsitterApplication::REJECTED,
                'reasons' => $data['reasons'],
                'message' => $data['message'] ?? null,
                'decided_by' => $request->user()->id,
                'decided_at' => now(),
            ])->save();

            $labels = array_map(fn ($code) => PetsitterApplication::REASON_LABELS[$code], $data['reasons']);
            $text = 'A tua candidatura a petsitter foi recusada. Motivo: '.implode(', ', $labels).'.';
            if (! empty($data['message'])) {
                $text .= ' Mensagem do administrador: '.$data['message'];
            }

            $petsitter->user->notifications()->create([
                'type' => 'verification_rejected',
                'message' => $text,
            ]);
        });

        return new PetsitterResource($this->loadForAdmin($petsitter));
    }

    /**
     * Pede mais informação ao candidato (apenas admin): envia-lhe uma notificação com a mensagem e a
     * candidatura continua pendente.
     * Asks the applicant for more information (admin only): notifies them; the application stays pending.
     */
    public function requestInfo(Request $request, Petsitter $petsitter)
    {
        Gate::authorize('approve', $petsitter);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ], [
            'message.required' => 'Escreve a mensagem para o candidato.',
        ]);

        $application = $this->currentApplication($petsitter);

        if (! $application->isPending()) {
            return response()->json(['message' => 'Só se pode pedir informação a uma candidatura pendente.'], 422);
        }

        DB::transaction(function () use ($petsitter, $application, $data) {
            // guarda o último pedido na candidatura, para o admin ver o que já pediu
            $application->forceFill(['message' => $data['message']])->save();

            $petsitter->user->notifications()->create([
                'type' => 'verification_info_requested',
                'message' => 'O administrador pediu mais informação sobre a tua candidatura a petsitter: '.$data['message'],
            ]);
        });

        return new PetsitterResource($this->loadForAdmin($petsitter));
    }

    /**
     * Abre um dos documentos de identificação de uma candidatura (apenas admin), pela posição na lista.
     * Opens one of an application's identification documents (admin only), by its position in the list.
     */
    public function applicationDocument(PetsitterApplication $application, int $index)
    {
        Gate::authorize('approve', Petsitter::findOrFail($application->user_id));

        $path = $application->identification_documents[$index] ?? null;

        if (! $path || ! Storage::disk('local')->exists($path)) {
            return response()->json(['message' => 'Documento de identificação não encontrado.'], 404);
        }

        return Storage::disk('local')->response($path);
    }

    /**
     * A candidatura mais recente; os petsitters antigos sem candidatura ficam com uma, feita a partir do perfil.
     * The most recent application; older petsitters without one get one built from their profile.
     */
    private function currentApplication(Petsitter $petsitter): PetsitterApplication
    {
        $application = $petsitter->latestApplication()->first();

        if ($application) {
            return $application;
        }

        $application = new PetsitterApplication([
            'user_id' => $petsitter->user_id,
            'identification_documents' => $petsitter->identification_documents ? [$petsitter->identification_documents] : [],
        ]);
        // fica com o estado que o perfil já tem (um petsitter aprovado não volta a pendente)
        $application->forceFill(['status' => $petsitter->approval_status ?: PetsitterApplication::PENDING])->save();

        return $application;
    }

    /** Petsitter com o que a página de verificações precisa. / Petsitter with what the verifications page needs. */
    private function loadForAdmin(Petsitter $petsitter): Petsitter
    {
        return $petsitter->fresh()->load(['user', 'services', 'applications.decidedBy']);
    }

    /** Valida cada ficheiro de identificação (o erro fica no campo identification_documents). */
    private function identificationFilesRule(): \Closure
    {
        return function (string $attribute, mixed $files, \Closure $fail) {
            foreach ((array) $files as $file) {
                $valid = $file instanceof UploadedFile
                    && $file->isValid()
                    && in_array(strtolower($file->getClientOriginalExtension()), ['jpg', 'jpeg', 'png', 'pdf'], true)
                    && in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'application/pdf'], true)
                    && $file->getSize() <= 10240 * 1024;

                if (! $valid) {
                    $fail('Cada documento tem de ser JPG, PNG ou PDF, até 10 MB.');

                    return;
                }
            }
        };
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
