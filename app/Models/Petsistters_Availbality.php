<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Petsistters_Availbality extends Model
{
    protected $fillable = ['petsitter_id', 'date'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function petsitter()
    {
        return $this->belongsTo(Petsitter::class);
    }
}
