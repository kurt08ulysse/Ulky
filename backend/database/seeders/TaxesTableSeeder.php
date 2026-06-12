<?php

namespace Database\Seeders;

use App\Models\Tax;
use Illuminate\Database\Seeder;

class TaxesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Insère les 39 actes administratifs municipaux réels.
     * Les montants sont exprimés en centimes (FCFA * 100).
     */
    public function run(): void
    {
        $taxes = [
            ['name' => 'Attestation de cession', 'base' => 5000, 'stamp' => 1000],
            ['name' => 'Attestation de succession', 'base' => 10000, 'stamp' => 1000],
            ['name' => 'Attestation de lieu d\'habitation', 'base' => 5000, 'stamp' => 1000],
            ['name' => 'Attestation d\'habitation, de vie et de bonnes mœurs', 'base' => 1000, 'stamp' => 1000],
            ['name' => 'Autorisation de passage (river)', 'base' => 10000, 'stamp' => 1000],
            ['name' => 'Autorisation de passage (électrocité)', 'base' => 10000, 'stamp' => 1000],
            ['name' => 'Autorisation parentale', 'base' => 5000, 'stamp' => 1000],
            ['name' => 'Autorisation provisoire d\'exercer', 'base' => 10000, 'stamp' => 1000],
            ['name' => 'Certificat de célibat', 'base' => 5000, 'stamp' => 1000],
            ['name' => 'Certificat de concubinage', 'base' => 10000, 'stamp' => 1000],
            ['name' => 'Certificat de coutume', 'base' => 1000, 'stamp' => 1000],
            ['name' => 'Certificat de déménagement', 'base' => 20000, 'stamp' => 1000],
            ['name' => 'Certificat de résidence (Adulte)', 'base' => 500, 'stamp' => 1000],
            ['name' => 'Certificat de résidence (Enfant)', 'base' => 1000, 'stamp' => 1000],
            ['name' => 'Certificat de survie', 'base' => 1000, 'stamp' => 1000],
            ['name' => 'Certificat de vie et d\'entretien', 'base' => 2000, 'stamp' => 1000],
            ['name' => 'Certificat de consentement', 'base' => 2000, 'stamp' => 1000],
            ['name' => 'Contrat de bail', 'base' => 10000, 'stamp' => 1000],
            ['name' => 'Contrat de location de véhicule', 'base' => 10000, 'stamp' => 1000],
            ['name' => 'Certificat de vente de véhicule', 'base' => 5000, 'stamp' => 1000],
            ['name' => 'Convention / Contrat de partenariat', 'base' => 10000, 'stamp' => 1000],
            ['name' => 'Délégation ou attestation de pouvoir', 'base' => 10000, 'stamp' => 1000],
            ['name' => 'Fiche familiale d\'état civil', 'base' => 2000, 'stamp' => 1000],
            ['name' => 'Fiche individuelle d\'état civil', 'base' => 1000, 'stamp' => 1000],
            ['name' => 'Livret de ménage (monogamie)', 'base' => 10000, 'stamp' => 0],
            ['name' => 'Livret de ménage (polygamie)', 'base' => 10000, 'stamp' => 0],
            ['name' => 'Permis d\'inhumer', 'base' => 5000, 'stamp' => 1000],
            ['name' => 'Certificat de transfert de corps', 'base' => 10000, 'stamp' => 1000],
            ['name' => 'Certificat matrimonial portant garde d\'enfant', 'base' => 10000, 'stamp' => 1000],
            ['name' => 'Certificat portant rapprochement d\'époux', 'base' => 5000, 'stamp' => 1000],
            ['name' => 'Certificat de concordance', 'base' => 2000, 'stamp' => 1000],
            ['name' => 'Certificat de non concordance ou d\'invalidité', 'base' => 2000, 'stamp' => 1000],
            ['name' => 'Certificat d\'hébergement', 'base' => 10000, 'stamp' => 1000],
            ['name' => 'Procuration', 'base' => 5000, 'stamp' => 1000],
            ['name' => 'Transcription de jugement supplétif', 'base' => 10000, 'stamp' => 0],
            ['name' => 'Recherche motivée (naissance, décès, mariage)', 'base' => 2000, 'stamp' => 0],
            ['name' => 'Lettre d\'invitation à séjourner au Gabon', 'base' => 5000, 'stamp' => 1000],
            ['name' => 'Autorisation de débranchement (compteur électrique)', 'base' => 5000, 'stamp' => 1000],
            ['name' => 'Publication de bans', 'base' => 1000, 'stamp' => 0],
        ];

        foreach ($taxes as $tax) {
            Tax::updateOrCreate(
                ['name' => $tax['name']],
                [
                    'base_amount' => $tax['base'] * 100, // Conversion en centimes
                    'stamp_amount' => $tax['stamp'] * 100, // Conversion en centimes
                    'periodicity' => 'one_time',
                    'commune_id' => null, // Multi-communes (optionnel pour l'instant)
                ]
            );
        }

        $this->command->info(count($taxes).' actes et taxes initialisés.');
    }
}
