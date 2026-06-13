<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Promotion d'un utilisateur au statut commerçant.
 *
 * Décision métier : on ne se déclare pas commerçant soi-même — c'est la mairie
 * qui valide (allocation d'un emplacement, ou promotion explicite au back-office).
 * Un commerçant reste le MÊME compte (clerk_id/email) ; on ajoute simplement le
 * rôle 'merchant' et un numéro de commerçant unique.
 */
class MerchantService
{
    /**
     * Garantit que l'utilisateur est commerçant (rôle + numéro). Idempotent.
     */
    public function ensureMerchant(User $user): User
    {
        if (! $user->hasRole('merchant')) {
            $user->assignRole('merchant');
        }

        if (empty($user->merchant_number)) {
            $user->merchant_number = $this->generateNumber();
            $user->save();
        }

        return $user;
    }

    /** Numéro de commerçant unique et lisible (ex. COM-2026-7K3F9A). */
    private function generateNumber(): string
    {
        do {
            $number = 'COM-'.now()->year.'-'.Str::upper(Str::random(6));
        } while (User::where('merchant_number', $number)->exists());

        return $number;
    }
}
