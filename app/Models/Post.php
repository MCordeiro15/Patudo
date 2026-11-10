<?php

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
    protected $fillable = ['description', 'photos'];

    protected function casts(): array
    {
        return ['photos' => 'array'];
    }

    public function petsitter()
    {
        return $this->belongsTo(Petsitter::class, 'petsitter_id', 'user_id');
    }
}
