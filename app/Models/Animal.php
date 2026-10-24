<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Animal extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'species',
        'size',
        'special_needs',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bookings()
    {
        return $this->belongsToMany(
            Booking::class,
            'animal_bookings',
            'animal_id',
            'booking_id'
        );
    }
}
