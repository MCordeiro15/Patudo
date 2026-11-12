<?php


/**
 * Modelo Eloquent para reservas de petsitters. Contém datas de início/fim, estado (pending, approved, rejected, completed, cancelled), preço total e notas. Relaciona-se com o owner, petsitter, animais e review.
 * Eloquent model for petsitter bookings. Contains start/end dates, status (pending, approved, rejected, completed, cancelled), total price, and notes. Relates to owner, petsitter, animals, and review.
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    /**
     * Campos que podem ser preenchidos em massa via create() ou fill().
     * Fields that can be mass-assigned via create() or fill().
     */
    protected $fillable = [
        'user_id',
        'petsitter_id',
        'start_date',
        'end_date',
        'status',
        'total_price',
    ];

    public function animalBookings()
    {
        return $this->hasMany(AnimalBooking::class);
    }

    /**
     * Relação belongsTo com User. Retorna o proprietário/criador deste registo.
     * BelongsTo relationship with User. Returns the owner/creator of this record.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relação belongsTo com Petsitter. Retorna o perfil do petsitter associado.
     * BelongsTo relationship with Petsitter. Returns the associated petsitter profile.
     */
    public function petsitter()
    {
        return $this->belongsTo(Petsitter::class, 'petsitter_id', 'user_id'
        );
    }

    /**
     * Relação belongsToMany com Animal. Retorna todos os animais associados a este registo.
     * BelongsToMany relationship with Animal. Returns all animals associated with this record.
     */
    public function animals()
    {
        return $this->belongsToMany(
            Animal::class,
            'animal_bookings',
            'booking_id',
            'animal_id'
        );
    }

    /**
     * Relação hasOne com Review. Retorna a avaliação associada a este registo.
     * HasOne relationship with Review. Returns the review associated with this record.
     */
    public function review()
    {
        return $this->hasOne(Review::class);
    }
}
