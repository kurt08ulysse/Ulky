<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Les champs mass-assignables.
     * Le password n'existe QUE pour le personnel accédant au back-office Filament ;
     * l'authentification des citoyens reste portée par Clerk (password null).
     *
     * @var list<string>
     */
    protected $fillable = [
        'clerk_id',
        'name',
        'email',
        'phone',
        'password',
        'taxpayer_type',
        'merchant_number',
        'commune_id',
    ];

    /**
     * Champs masqués dans les sérialisations.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'commune_id' => 'integer',
            'password' => 'hashed',
        ];
    }

    /**
     * Contrôle d'accès au back-office Filament (deny-by-default).
     *
     * Réservé au personnel d'encadrement disposant d'un mot de passe local.
     * Les citoyens / commerçants (auth Clerk, sans password) sont refusés.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return ! empty($this->password)
            && $this->hasAnyRole(['commune_admin', 'super_admin']);
    }
}
