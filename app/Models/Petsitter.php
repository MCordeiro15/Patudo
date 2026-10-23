<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Petsitter extends Model
{
    /*The `approval_status` is not in `$fillable`, for the same reason as `is_admin`.
     Only an administrator should be able to change it, and it should not be possible to submit it via a form.*/
    protected $fillable = [
        'user_id', 'biography', 'experience', 'service_location',
        'price_per_day', 'accepted_sizes', 'accepted_animal_types',
        'identification_documents',
    ];

    protected function casts(): array
    {
        return [
            'accepted_sizes' => 'array',
            'accepted_animal_types' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function availabilities()
    {
        return $this->hasMany(Petsistters_Availbality::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);   // Petsitter 1 — 0..* Post
    }
}
