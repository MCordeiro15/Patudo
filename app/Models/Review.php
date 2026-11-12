<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'booking_id',
        'petsitter_id',
        'star_rating',
        'comment',
        'visible',
    ];

    protected function casts(): array
    {
        return [
            'visible' => 'boolean',
            'star_rating' => 'integer',
        ];
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function petsitter()
    {
        return $this->belongsTo(Petsitter::class, 'petsitter_id', 'user_id');
    }
}
