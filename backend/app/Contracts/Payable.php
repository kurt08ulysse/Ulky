<?php

namespace App\Contracts;

/**
 * Objet réglable par un Payment (relation polymorphe payable).
 *
 * Implémenté par TaxNotice, StallRent, AdministrativeRequest. Permet au webhook
 * SingPay de confirmer un paiement sans connaître le type concret : chaque
 * payable décide de l'effet de la confirmation (markAsPaid) — utile car, pour
 * une démarche, le paiement ne touche PAS le statut de workflow.
 */
interface Payable
{
    /** Marque l'objet comme payé (effet propre à chaque type). Ne sauvegarde pas. */
    public function markAsPaid(): void;
}
