<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'user_id',
        'petsitter_id',
        'start_date',
        'end_date',
        'status',
        'total_price',
    ];

    public function animalBookings()
    {
        return $this->hasMany(AnimalBooking::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function petsitter()
    {
        return $this->belongsTo(Petsitter::class, 'petsitter_id', 'user_id'
        );
    }

    public function animals()
    {
        return $this->belongsToMany(
            Animal::class,
            'animal_bookings',
            'booking_id',
            'animal_id'
        );
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }
}
