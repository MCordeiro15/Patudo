<?php

/**
 * Modelo Eloquent para os serviços de um petsitter. Cada serviço tem um tipo (pet-sitting ou passeio) e um preço.
 * Eloquent model for a petsitter's services. Each service has a type (pet-sitting or passeio) and a price.
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PetsitterService extends Model
{
    /** Tipos de serviço aceites. / Accepted service types. */
    public const TYPES = ['pet-sitting', 'passeio'];

    protected $fillable = ['petsitter_id', 'type', 'price'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    /**
     * Relação belongsTo com Petsitter (a chave do petsitter é o user_id).
     * BelongsTo relationship with Petsitter (the petsitter key is user_id).
     */
    public function petsitter(): BelongsTo
    {
        return $this->belongsTo(Petsitter::class, 'petsitter_id', 'user_id');
    }

    /**
     * Relação hasMany com Booking. Retorna as reservas que escolheram este serviço (coluna bookings.service_id).
     * HasMany relationship with Booking. Returns the bookings that chose this service (bookings.service_id column).
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'service_id');
    }
}
