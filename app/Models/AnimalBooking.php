<?php

/**
 * Modelo Eloquent para a tabela intermédia entre animais e reservas. Representa a relação many-to-many permitindo que uma reserva inclua múltiplos animais.
 * Eloquent model for the intermediate table between animals and bookings. Represents the many-to-many relationship allowing a booking to include multiple animals.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnimalBooking extends Model
{
    /**
     * Campos que podem ser preenchidos em massa via create() ou fill().
     * Fields that can be mass-assigned via create() or fill().
     */
    protected $fillable = [
        'booking_id',
        'animal_id',
    ];

    /**
     * Relação belongsTo com Booking. Retorna a reserva associada.
     * BelongsTo relationship with Booking. Returns the associated booking.
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Relação belongsTo com Animal. Retorna o animal associado.
     * BelongsTo relationship with Animal. Returns the associated animal.
     */
    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }
}
