<?php

/**
 * Candidatura de um utilizador a petsitter. Cada candidatura é um registo próprio: quando um candidato recusado
 * volta a candidatar-se, é criada uma nova e as antigas ficam guardadas (o admin continua a vê-las).
 * A petsitter application. Each application is its own row: re-applying creates a new one and keeps the old ones.
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $status
 * @property array<int, string> $identification_documents
 * @property array<int, string>|null $reasons
 * @property string|null $message
 * @property int|null $decided_by
 * @property Carbon|null $decided_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read User|null $decidedBy
 */
class PetsitterApplication extends Model
{
    use HasFactory;

    public const PENDING = 'pending';

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    public const STATUSES = [self::PENDING, self::APPROVED, self::REJECTED];

    /** Máximo de candidaturas por utilizador (a seguinte devolve 422). / Max applications per user. */
    public const MAX_PER_USER = 3;

    /** Máximo de ficheiros de identificação por candidatura. / Max identification files per application. */
    public const MAX_DOCUMENTS = 5;

    /** Motivos de recusa: código => texto (com "other" a mensagem é obrigatória). / Rejection reasons. */
    public const REASON_LABELS = [
        'illegible_document' => 'Documento ilegível',
        'identity_mismatch' => 'A identidade não confere',
        'inappropriate_photos' => 'Fotografias inadequadas',
        'external_contacts' => 'Contactos externos na descrição',
        'other' => 'Outro motivo',
    ];

    /** Códigos dos motivos de recusa. / Rejection reason codes. */
    public const REASONS = ['illegible_document', 'identity_mismatch', 'inappropriate_photos', 'external_contacts', 'other'];

    /* `status`, `reasons`, `message`, `decided_by` e `decided_at` não estão no $fillable:
       só mudam pelas ações do admin (aprovar, recusar, pedir informação).
       They are not fillable: only the admin actions change them. */
    protected $fillable = ['user_id', 'identification_documents'];

    protected $attributes = [
        'status' => self::PENDING,
    ];

    protected function casts(): array
    {
        return [
            'identification_documents' => 'array',
            'reasons' => 'array',
            'decided_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** O admin que aprovou ou recusou. / The admin who approved or rejected. */
    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }
}
