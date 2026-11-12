<?php

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

    protected $casts = [
        'weight' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

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
