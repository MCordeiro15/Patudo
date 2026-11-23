<?php

/**
 * Controlador API para gestão de reservas de petsitters. Permite criar reservas, listar reservas do utilizador, ver detalhes e alterar o estado (pending, approved, rejected, completed, cancelled).
 * API controller for petsitter booking management. Allows creating bookings, listing user's bookings, viewing details, and changing status (pending, approved, rejected, completed, cancelled).
 */
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Petsitter;
use App\Models\PetsitterService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    /*
     * Mapa das mudanças de estado permitidas:
     * estado atual => [novo estado => quem pode fazer a mudança].
     * Se o estado atual não está aqui (rejected, cancelled, completed),
     * a reserva já terminou e não muda mais.
     */
    private const TRANSITIONS = [
        'pending' => ['approved' => 'petsitter', 'rejected' => 'petsitter', 'cancelled' => 'owner'],
        'approved' => ['completed' => 'petsitter', 'cancelled' => 'owner'],
    ];

    // Lista as reservas em que o utilizador é o dono OU o petsitter.
    /**
     * Lista todos os registos com paginação. Admin vê todos, utilizador normal vê apenas os seus.
     * Lists all records with pagination. Admin sees all, normal user sees only their own.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Booking::class);

        // ?pending=1 -> só pendentes (página Pedidos); ?pending=0 -> todas menos as pendentes (página Reservas)
        $request->validate(['pending' => 'sometimes|boolean']);

        $userId = $request->user()->id;

        return BookingResource::collection(
            Booking::with('animals', 'service', 'user', 'petsitter.user', 'review')
                // o where com função agrupa o OR entre parênteses
                ->where(fn ($q) => $q->where('user_id', $userId)->orWhere('petsitter_id', $userId))
                ->when($request->has('pending'), fn ($q) => $request->boolean('pending')
                    ? $q->where('status', 'pending')
                    : $q->where('status', '!=', 'pending'))
                ->latest()
                ->paginate(15)
                ->withQueryString()
        );
    }

    /**
     * Cria um novo registo após validação dos dados do pedido. Retorna o recurso criado com status 201.
     * Creates a new record after validating request data. Returns the created resource with status 201.
     */
    public function store(Request $request)
    {
        Gate::authorize('create', Booking::class);

        $user = $request->user();

        $data = $request->validate([
            'petsitter_id' => [
                'required', 'integer',
                // tem de existir em petsitters (coluna user_id) e estar aprovado
                Rule::exists('petsitters', 'user_id')->where('approval_status', 'approved'),
                // não podes reservar a ti próprio
                Rule::notIn([$user->id]),
            ],
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'service_id' => [
                'required', 'integer',
                // o serviço tem de existir E ser do petsitter escolhido
                Rule::exists('petsitter_services', 'id')->where('petsitter_id', $request->input('petsitter_id')),
            ],
            'animal_ids' => 'required|array|min:1',
            // cada animal tem de existir E ser do utilizador autenticado
            'animal_ids.*' => ['integer', 'distinct', Rule::exists('animals', 'id')->where('user_id', $user->id)],
            'message' => ['nullable', 'string', 'max:1000'],
            'zone' => ['nullable', 'string', 'max:255'],
            'entrance_hour' => ['nullable', 'date_format:H:i'],
            'exit_hour' => ['nullable', 'date_format:H:i'],
        ]);

        if (
            $data['start_date'] === $data['end_date'] &&
            !empty($data['entrance_hour']) &&
            !empty($data['exit_hour']) &&
            $data['exit_hour'] <= $data['entrance_hour']
        ) {
            return response()->json([
                'message' => 'A hora de saída deve ser posterior à hora de entrada.',
                'errors' => [
                    'exit_hour' => [
                        'A hora de saída deve ser posterior à hora de entrada.',
                    ],
                ],
            ], 422);
        }

        $petsitter = Petsitter::findOrFail($data['petsitter_id']);
        $service = PetsitterService::findOrFail($data['service_id']);

        // +1 porque os dois dias contam (1 a 3 dezembro = 3 dias)
        $days = (int) Carbon::parse($data['start_date'])->diffInDays(Carbon::parse($data['end_date'])) + 1;

        // transaction: ou grava a reserva E os animais, ou não grava nada
        $booking = DB::transaction(function () use ($data, $user, $petsitter, $service, $days) {
            $booking = Booking::create([
                'user_id' => $user->id,            // vem da sessão, não do pedido
                'petsitter_id' => $petsitter->user_id,
                'service_id' => $service->id,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'status' => 'pending',             // sempre pending ao criar
                'total_price' => $service->price * $days, // calculado no servidor, com o preço do serviço
                'message' => $data['message'] ?? null,
                'zone' => $data['zone'] ?? null,
                'entrance_hour' => $data['entrance_hour'] ?? null,
                'exit_hour' => $data['exit_hour'] ?? null,
            ]);

            // escreve as linhas na tabela animal_bookings
            $booking->animals()->attach($data['animal_ids']);

            return $booking;
        });

        return (new BookingResource($booking->load('animals', 'service')))->response()->setStatusCode(201);
    }

    /**
     * Retorna os detalhes de um registo específico. Verifica permissão antes de exibir.
     * Returns details of a specific record. Checks permission before displaying.
     */
    public function show(Booking $booking)
    {
        Gate::authorize('view', $booking);

        // user e petsitter.user: nome, localidade e foto de cada um (detalhe da reserva e Pagamento Confirmado)
        return new BookingResource($booking->load('animals', 'service', 'review', 'user', 'petsitter.user'));
    }

    // PATCH /api/bookings/{booking}/status
    /**
     * Altera o estado de uma reserva seguindo as transições permitidas (pending → approved/rejected, approved → completed).
     * Changes a booking's status following allowed transitions (pending → approved/rejected, approved → completed).
     */
    public function updateStatus(Request $request, Booking $booking)
    {
        // 403 se não for nem o dono nem o petsitter desta reserva
        Gate::authorize('updateStatus', $booking);

        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected', 'cancelled', 'completed'])],
        ]);

        // que papel tem quem faz o pedido nesta reserva?
        $who = $request->user()->id === $booking->petsitter_id ? 'petsitter' : 'owner';

        // quem pode fazer esta mudança? (null se a mudança nem existe no mapa)
        $allowed = self::TRANSITIONS[$booking->status][$data['status']] ?? null;

        if ($allowed !== $who) {
            return response()->json([
                'message' => "Não é possível passar de {$booking->status} para {$data['status']}.",
            ], 422);
        }

        $booking->update(['status' => $data['status']]);

        return new BookingResource($booking);
    }
}
