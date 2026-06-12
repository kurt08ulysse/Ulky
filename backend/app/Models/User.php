<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Les champs mass-assignables.
     * Pas de password : Clerk porte l'authentification.
     *
     * @var list<string>
     */
    protected $fillable = [
        'clerk_id',
        'name',
        'email',
        'phone',
        'taxpayer_type',
        'commune_id',
    ];

    /**
     * Aucun champ caché : pas de password, pas de remember_token.
     *
     * @var list<string>
     */
    protected $hidden = [];

    /**
     * Casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'commune_id' => 'integer',
        ];
    }
}
