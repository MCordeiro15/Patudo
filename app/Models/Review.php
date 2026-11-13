<?php


/**
 * Modelo Eloquent para avaliações de petsitters. Contém nota em estrelas (1-5) e comentário textual. Relaciona-se com o owner que avaliou e o petsitter avaliado.
 * Eloquent model for petsitter reviews. Contains star rating (1-5) and textual comment. Relates to the owner who reviewed and the reviewed petsitter.
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    /**
     * Campos que podem ser preenchidos em massa via create() ou fill().
     * Fields that can be mass-assigned via create() or fill().
     */
    protected $fillable = [
        'booking_id',
        'petsitter_id',
        'star_rating',
        'comment',
        'visible',
    ];

    protected function casts(): array
    {
        return [
            'visible' => 'boolean',
            'star_rating' => 'integer',
        ];
    }

    /**
     * Relação belongsTo com Booking. Retorna a reserva associada.
     * BelongsTo relationship with Booking. Returns the associated booking.
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Relação belongsTo com Petsitter. Retorna o perfil do petsitter associado.
     * BelongsTo relationship with Petsitter. Returns the associated petsitter profile.
     */
    public function petsitter()
    {
        return $this->belongsTo(Petsitter::class, 'petsitter_id', 'user_id');
    }
}
