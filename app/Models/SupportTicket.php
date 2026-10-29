<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    protected $fillable = [
        'user_id',
        'claimed_by',
        'reported_user_id',
        'subject',
        'description',
        'type',
        'status',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function claimant()
    {
        return $this->belongsTo(User::class, 'claimed_by');
    }

    public function reportedUser()
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }
}
