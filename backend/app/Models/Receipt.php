<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receipt extends Model
{
    protected $fillable = [
        'payment_id',
        'receipt_number',
        'qr_code_token',
        'pdf_path',
    ];

    /**
     * Relation avec le paiement.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Attribut calculé pour l'URL de vérification publique.
     */
    public function getVerificationUrlAttribute(): string
    {
        return route('receipts.verify', ['token' => $this->qr_code_token]);
    }
}
