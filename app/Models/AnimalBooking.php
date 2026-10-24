<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnimalBooking extends Model
{
    protected $fillable = [
        'booking_id',
        'animal_id',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }
}
