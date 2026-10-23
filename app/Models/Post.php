<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = ['petsitter_id', 'description', 'photos'];

    protected function casts(): array
    {
        return ['photos' => 'array'];
    }

    public function petsitter()
    {
        return $this->belongsTo(Petsitter::class);
    }
}
