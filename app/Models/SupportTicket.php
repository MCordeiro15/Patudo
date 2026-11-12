<?php


/**
 * Modelo Eloquent para tickets de suporte. Contém tipo (complaint/help), estado (open, claimed, closed), título e descrição. Relaciona-se com o criador e o utilizador reportado.
 * Eloquent model for support tickets. Contains type (complaint/help), status (open, claimed, closed), title, and description. Relates to the creator and the reported user.
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    /**
     * Campos que podem ser preenchidos em massa via create() ou fill().
     * Fields that can be mass-assigned via create() or fill().
     */
    protected $fillable = [
        'user_id',
        'claimed_by',
        'reported_user_id',
        'subject',
        'description',
        'type',
        'status',
    ];

    /**
 * @return BelongsTo<User, $this>
 * @return BelongsTo<User, $this>
 */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function claimant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reportedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }
}
