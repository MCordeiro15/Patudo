<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Favourite extends Model
{
    protected $fillable = ['user_id', 'petsitter_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function petsitter()
    {
        return $this->belongsTo(Petsitter::class, 'petsitter_id', 'user_id');
    }
}
