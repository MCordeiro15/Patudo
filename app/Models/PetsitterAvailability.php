<?php


/**
 * Modelo Eloquent para datas de disponibilidade individual de cada petsitter. Permite ativar/desativar datas específicas no calendário.
 * Eloquent model for individual availability dates of each petsitter. Allows enabling/disabling specific dates on the calendar.
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PetsitterAvailability extends Model
{
    use HasFactory;

    /**
     * Campos que podem ser preenchidos em massa via create() ou fill().
     * Fields that can be mass-assigned via create() or fill().
     */
    protected $fillable = ['petsitter_id', 'date'];

    protected function casts(): array
    {
        return ['date' => 'date:Y-m-d'];
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
