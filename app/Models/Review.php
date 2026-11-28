<?php

/**
 * Modelo Eloquent para avaliações de petsitters. Contém nota em estrelas (1-5) e comentário textual. Relaciona-se com o owner que avaliou e o petsitter avaliado.
 * Eloquent model for petsitter reviews. Contains star rating (1-5) and textual comment. Relates to the owner who reviewed and the reviewed petsitter.
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    /** Estados de uma avaliação. / Review statuses. */
    public const PUBLISHED = 'published'; // aparece no perfil e conta para a média

    public const FLAGGED = 'flagged'; // sinalizada por um utilizador; continua a aparecer até o admin decidir

    public const REMOVED = 'removed'; // removida pelo admin: não aparece nem conta para a média

    public const STATUSES = [self::PUBLISHED, self::FLAGGED, self::REMOVED];

    /* `status` não está no $fillable: só muda pelas ações de sinalizar, publicar e remover.
       `status` is not fillable: it only changes through flag, publish and remove. */
    /**
     * Campos que podem ser preenchidos em massa via create() ou fill().
     * Fields that can be mass-assigned via create() or fill().
     */
    protected $fillable = [
        'booking_id',
        'petsitter_id',
        'star_rating',
        'comment',
    ];

    protected $attributes = [
        'status' => self::PUBLISHED,
    ];

    protected function casts(): array
    {
        return [
            'star_rating' => 'integer',
        ];
    }

    /**
     * Só as avaliações que aparecem no perfil e contam para a média (publicadas e sinalizadas).
     * Only the reviews shown on the profile and counted in the average (published and flagged).
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('status', '!=', self::REMOVED);
    }

    public function isVisible(): bool
    {
        return $this->status !== self::REMOVED;
    }

    /**
     * Compatibilidade com o antigo `visible` (seeders exportados antes da coluna status):
     * visible = false passa a `removed`; visible = true mantém o status. A coluna visible já não existe.
     * Compatibility with the old `visible` (seeders exported before the status column).
     */
    public function setVisibleAttribute(mixed $value): void
    {
        if (! $value) {
            $this->attributes['status'] = self::REMOVED;
        }
    }

    /**
     * Relação belongsTo com Booking. Retorna a reserva associada.
     * BelongsTo relationship with Booking. Returns the associated booking.
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Relação belongsTo com Petsitter. Retorna o perfil do petsitter associado.
     * BelongsTo relationship with Petsitter. Returns the associated petsitter profile.
     */
    public function petsitter()
    {
        return $this->belongsTo(Petsitter::class, 'petsitter_id', 'user_id');
    }
}
