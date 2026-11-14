<?php

/**
 * Modelo Eloquent principal para utilizadores. Suporta autenticação, 2FA, passkeys, roles (admin, petsitter) e relações com animais, reservas, posts, petsitter, reviews e favoritos.
 * Main Eloquent model for users. Supports authentication, 2FA, passkeys, roles (admin, petsitter), and relationships with animals, bookings, posts, petsitter, reviews, and favorites.
 */
namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property bool $is_petsitter
 * @property-read Petsitter|null $petsitter
 */
#[Fillable(['name', 'email', 'password', 'phone', 'location', 'about', 'avatar', 'is_admin', 'is_petsitter', 'account_status'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'is_admin' => 'boolean',
            'is_petsitter' => 'boolean',
        ];
    }

    /**
     * Verifica se o utilizador tem permissões de administrador.
     * Checks if the user has administrator permissions.
     */
    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    /**
     * Verifica se o utilizador tem perfil de petsitter ativo.
     * Checks if the user has an active petsitter profile.
     */
    public function isPetsitter(): bool
    {
        return (bool) $this->is_petsitter;
    }

    /**
     * Relação belongsToMany com Animal. Retorna todos os animais associados a este registo.
     * BelongsToMany relationship with Animal. Returns all animals associated with this record.
     */
    public function animals()
    {
        return $this->hasMany(Animal::class);
    }

    /**
     * Relação belongsTo com Petsitter. Retorna o perfil do petsitter associado.
     * BelongsTo relationship with Petsitter. Returns the associated petsitter profile.
     */
    public function petsitter()
    {
        return $this->hasOne(Petsitter::class);
    }

    public function favouritePetsitters()
    {
        return $this->belongsToMany(
            Petsitter::class,
            'favourites',
            'user_id',
            'petsitter_id',
            'id',
            'user_id'
        );
    }

    public function createdTickets()
    {
        return $this->hasMany(SupportTicket::class, 'user_id');
    }

    public function claimedTickets()
    {
        return $this->hasMany(SupportTicket::class, 'claimed_by');
    }

    public function reportedTickets()
    {
        return $this->hasMany(SupportTicket::class, 'reported_user_id');
    }

    /**
     * Relação hasMany com Notification. Retorna todas as notificações deste utilizador.
     * HasMany relationship with Notification. Returns all notifications for this user.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
