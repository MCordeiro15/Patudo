<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Petsitter;
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
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Booking::class);

        $userId = $request->user()->id;

        return BookingResource::collection(
            Booking::with('animals')
                // o where com função agrupa o OR entre parênteses
                ->where(fn ($q) => $q->where('user_id', $userId)->orWhere('petsitter_id', $userId))
                ->latest()
                ->paginate(15)
        );
    }

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
            'animal_ids' => 'required|array|min:1',
            // cada animal tem de existir E ser do utilizador autenticado
            'animal_ids.*' => ['integer', 'distinct', Rule::exists('animals', 'id')->where('user_id', $user->id)],
        ]);

        $petsitter = Petsitter::findOrFail($data['petsitter_id']);

        // +1 porque os dois dias contam (1 a 3 dezembro = 3 dias)
        $days = (int) Carbon::parse($data['start_date'])->diffInDays(Carbon::parse($data['end_date'])) + 1;

        // transaction: ou grava a reserva E os animais, ou não grava nada
        $booking = DB::transaction(function () use ($data, $user, $petsitter, $days) {
            $booking = Booking::create([
                'user_id' => $user->id,            // vem da sessão, não do pedido
                'petsitter_id' => $petsitter->user_id,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'status' => 'pending',             // sempre pending ao criar
                'total_price' => $petsitter->price_per_day * $days, // calculado no servidor
            ]);

            // escreve as linhas na tabela animal_bookings
            $booking->animals()->attach($data['animal_ids']);

            return $booking;
        });

        return (new BookingResource($booking->load('animals')))->response()->setStatusCode(201);
    }

    public function show(Booking $booking)
    {
        Gate::authorize('view', $booking);

        return new BookingResource($booking->load('animals', 'review'));
    }

    // PATCH /api/bookings/{booking}/status
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
