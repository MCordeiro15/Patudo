<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PetsitterAvailability extends Model
{
    use HasFactory;

    protected $fillable = ['petsitter_id', 'date'];

    protected function casts(): array
    {
        return ['date' => 'date:Y-m-d'];
    }

    public function petsitter()
    {
        return $this->belongsTo(Petsitter::class, 'petsitter_id', 'user_id');
    }
}
