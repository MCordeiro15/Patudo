<?php


/**
 * Modelo Eloquent para posts de petsitters. Contém título, descrição e array de fotos. Relaciona-se com o petsitter que o criou.
 * Eloquent model for petsitter posts. Contains title, description, and photo array. Relates to the petsitter who created it.
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $petsitter_id
 * @property string $description
 * @property array<string>|null $photos
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Petsitter $petsitter
 */
class Post extends Model
{
    /**
     * Campos que podem ser preenchidos em massa via create() ou fill().
     * Fields that can be mass-assigned via create() or fill().
     */
    protected $fillable = ['title','description', 'photos'];

    protected function casts(): array
    {
        return ['photos' => 'array'];
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
