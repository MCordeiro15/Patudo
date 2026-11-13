<?php

/**
 * Modelo Eloquent para notificações de utilizadores. Contém tipo, mensagem e estado de leitura. Relaciona-se com o utilizador destinatário.
 * Eloquent model for user notifications. Contains type, message, and read status. Relates to the recipient user.
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string $message
 * @property Carbon|null $read_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
class Notification extends Model
{
    /**
     * Campos que podem ser preenchidos em massa via create() ou fill().
     * Fields that can be mass-assigned via create() or fill().
     */
    protected $fillable = [
        'user_id',
        'type',
        'message',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    /**
     * Verifica se a notificação já foi lida.
     * Checks if the notification has been read.
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Marca a notificação como lida.
     * Marks the notification as read.
     */
    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }

    /**
     * Relação belongsTo com User. Retorna o utilizador destinatário desta notificação.
     * BelongsTo relationship with User. Returns the recipient user of this notification.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
