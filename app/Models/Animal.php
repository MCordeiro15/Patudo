<?php

/**
 * Modelo Eloquent para animais de estimação. Representa um animal com dados como espécie, raça, tamanho, necessidades especiais, peso e foto. Relaciona-se com o proprietário (User) e com reservas (Booking).
 * Eloquent model for pets. Represents an animal with data like species, breed, size, special needs, weight, and photo. Relates to the owner (User) and bookings (Booking).
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $species
 * @property string $breed
 * @property string $size
 * @property string|null $special_needs
 * @property string|null $aggression
 * @property string|null $medication
 * @property string|null $sex
 * @property string|null $photo
 * @property float|null $weight
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
class Animal extends Model
{
    use HasFactory;

    /**
     * Campos que podem ser preenchidos em massa via create() ou fill().
     * Fields that can be mass-assigned via create() or fill().
     */
    protected $fillable = [
        'user_id',
        'name',
        'species',
        'breed',
        'size',
        'special_needs',
        'aggression',
        'medication',
        'sex',
        'photo',
        'weight',
    ];

    /**
     * Definições de conversão automática de tipos para atributos do modelo.
     * Automatic type casting definitions for model attributes.
     */
    protected $casts = [
        'weight' => 'decimal:2',
    ];

    /**
     * Relação belongsTo com User. Retorna o proprietário/criador deste registo.
     * BelongsTo relationship with User. Returns the owner/creator of this record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relação belongsToMany com Booking via tabela intermédia animal_bookings. Retorna todas as reservas deste animal.
     * BelongsToMany relationship with Booking via intermediate table animal_bookings. Returns all bookings of this animal.
     */
    public function bookings(): BelongsToMany
    {
        return $this->belongsToMany(
            Booking::class,
            'animal_bookings',
            'animal_id',
            'booking_id'
        );
    }
}
