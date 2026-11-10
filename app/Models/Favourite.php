<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favourite extends Model
{
    protected $fillable = ['user_id', 'petsitter_id'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Petsitter, $this>
     */
    public function petsitter(): BelongsTo
    {
        return $this->belongsTo(Petsitter::class, 'petsitter_id', 'user_id');
    }
}
