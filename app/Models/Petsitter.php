<?php


/**
 * Modelo Eloquent para perfis de petsitter. Contém biografia, experiência, preço por dia, tamanhos de animais aceites, localização, estado de aprovação e dias de disponibilidade.
 * Eloquent model for petsitter profiles. Contains biography, experience, price per day, accepted animal sizes, location, approval status, and availability days.
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $user_id
 * @property string|null $biography
 * @property string|null $profile_photo
 * @property string|null $short_bio
 * @property string $experience
 * @property string $service_location
 * @property string|null $city
 * @property int $age
 * @property float $price_per_day
 * @property float|null $average_rating
 * @property array<string>|null $accepted_sizes
 * @property array<string>|null $accepted_animal_types
 * @property string|null $sex
 * @property string $approval_status
 * @property string $identification_documents
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
class Petsitter extends Model
{
    use HasFactory;

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    /*The `approval_status` is not in `$fillable`, for the same reason as `is_admin`.
     Only an administrator should be able to change it, and it should not be possible to submit it via a form.*/
    /**
     * Campos que podem ser preenchidos em massa via create() ou fill().
     * Fields that can be mass-assigned via create() or fill().
     */
    protected $fillable = [
        'user_id', 'biography', 'profile_photo', 'short_bio', 'experience', 'service_location', 'city',
        'age',
        'price_per_day', 'average_rating', 'accepted_sizes', 'sex', 'accepted_animal_types',
        'identification_documents', 'availability_days',
    ];

    protected function casts(): array
    {
        return [
            'accepted_sizes' => 'array',
            'accepted_animal_types' => 'array',
            'availability_days' => 'array',
        ];
    }

    /**
     * Relação belongsTo com User. Retorna o proprietário/criador deste registo.
     * BelongsTo relationship with User. Returns the owner/creator of this record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    /**
     * Relação hasMany com Post. Retorna todas as publicações deste petsitter.
     * HasMany relationship with Post. Returns all posts by this petsitter.
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'petsitter_id', 'user_id');    // Petsitter 1 — 0..* Post
    }

    public function favouritedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'favourites',
            'petsitter_id',
            'user_id',
            'user_id',
            'id'
        );
    }

    /**
     * Relação hasMany com Review. Retorna todas as avaliações deste petsitter.
     * HasMany relationship with Review. Returns all reviews of this petsitter.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(
            Review::class,
            'petsitter_id',
            'user_id'
        );
    }

    /**
 * Approve this petsitter profile.
 * Approve this petsitter profile.
 */
    public function approve(): void
    {
        $this->approval_status = 'approved';
        $this->save();
    }

    /**
     * Reject this petsitter profile.
     */
    public function reject(): void
    {
        $this->approval_status = 'rejected';
        $this->save();
    }

    /**
     * Check if identification documents are present.
     */
    public function hasIdentificationDocuments(): bool
    {
        return ! empty($this->identification_documents);
    }
}
