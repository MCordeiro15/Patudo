<?php


/**
 * Modelo Eloquent para a tabela intermédia de favoritos. Representa a relação many-to-many entre utilizadores e petsitters favoritos.
 * Eloquent model for the favorites intermediate table. Represents the many-to-many relationship between users and favorite petsitters.
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favourite extends Model
{
    /**
     * Campos que podem ser preenchidos em massa via create() ou fill().
     * Fields that can be mass-assigned via create() or fill().
     */
    protected $fillable = ['user_id', 'petsitter_id'];

    /**
 * @return BelongsTo<User, $this>
 * @return BelongsTo<User, $this>
 */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Petsitter, $this>
     */
    public function petsitter(): BelongsTo
    {
        return $this->belongsTo(Petsitter::class, 'petsitter_id', 'user_id');
    }
}
