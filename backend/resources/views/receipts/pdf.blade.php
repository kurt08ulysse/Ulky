<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Quittance de Paiement - {{ $receipt->receipt_number }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #10B981; /* Green color primary */
            padding-bottom: 15px;
        }
        .republic {
            font-weight: bold;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .motto {
            font-style: italic;
            font-size: 12px;
            margin-top: 5px;
        }
        .receipt-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 20px;
            margin-bottom: 20px;
            color: #111827;
        }
        .receipt-number {
            color: #10B981;
            font-family: monospace;
            font-size: 18px;
        }
        .meta-table, .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .meta-table td {
            padding: 8px;
            vertical-align: top;
        }
        .meta-label {
            font-weight: bold;
            color: #4B5563;
            width: 150px;
        }
        .details-table th, .details-table td {
            border: 1px solid #E5E7EB;
            padding: 12px;
            text-align: left;
        }
        .details-table th {
            background-color: #F9FAFB;
            font-weight: bold;
            color: #374151;
        }
        .total-row {
            font-weight: bold;
            background-color: #ECFDF5;
        }
        .total-row td {
            color: #065F46;
            border-top: 2px solid #10B981;
        }
        .footer {
            margin-top: 50px;
            border-top: 1px solid #E5E7EB;
            padding-top: 15px;
            font-size: 11px;
            color: #6B7280;
            text-align: center;
        }
        .qr-section {
            float: right;
            text-align: center;
            margin-top: 10px;
        }
        .qr-image {
            width: 120px;
            height: 120px;
            border: 1px solid #E5E7EB;
            padding: 5px;
            background-color: #fff;
        }
        .qr-text {
            font-size: 9px;
            color: #6B7280;
            margin-top: 5px;
            max-width: 130px;
        }
        .stamp-watermark {
            position: absolute;
            top: 40%;
            left: 35%;
            font-size: 64px;
            color: rgba(16, 185, 129, 0.08);
            border: 5px solid rgba(16, 185, 129, 0.08);
            padding: 10px;
            transform: rotate(-30deg);
            font-weight: bold;
            text-transform: uppercase;
            pointer-events: none;
        }
    </style>
</head>
<body>

    <div class="stamp-watermark">PAYÉ</div>

    <div class="header">
        <div class="republic">République Gabonaise</div>
        <div class="motto">Union - Travail - Justice</div>
        <div style="margin-top: 8px; font-weight: 600; color: #4B5563;">
            ADMINISTRATION MUNICIPALE DE LIBREVILLE
        </div>
    </div>

    <div class="receipt-title">
        Quittance Officielle de Recettes<br>
        <span class="receipt-number">{{ $receipt->receipt_number }}</span>
    </div>

    <!-- QR Code Section aligned to the right -->
    <div class="qr-section">
        <img class="qr-image" src="{{ $qr_code_url }}" alt="QR Verification">
        <div class="qr-text">Scannez ce QR Code pour vérifier l'authenticité de ce document</div>
    </div>

    <!-- Metadata Section -->
    <table class="meta-table">
        <tr>
            <td class="meta-label">Date d'émission :</td>
            <td>{{ now()->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td class="meta-label">Contribuable :</td>
            <td>
                <strong>{{ $user->name }}</strong><br>
                Tél : {{ $user->phone ?? 'Non renseigné' }}<br>
                Type : {{ $user->taxpayer_type === 'business' ? 'Entreprise / Commerce' : 'Particulier' }}
            </td>
        </tr>
        <tr>
            <td class="meta-label">Identifiant Unique :</td>
            <td style="font-family: monospace;">{{ $user->clerk_id ?? 'N/A' }}</td>
        </tr>
    </table>

    <div style="clear: both; height: 10px;"></div>

    <!-- Details Table -->
    <table class="details-table">
        <thead>
            <tr>
                <th>Désignation de la Taxe / Acte</th>
                <th style="text-align: right; width: 150px;">Montant de base</th>
                <th style="text-align: right; width: 150px;">Timbre fiscal</th>
                <th style="text-align: right; width: 150px;">Montant total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong>{{ $tax->name }}</strong><br>
                    <span style="font-size: 12px; color: #6B7280;">Avis de taxe #{{ $taxNotice->id }}</span>
                </td>
                <td style="text-align: right;">{{ $taxNotice->base_amount_formatted }}</td>
                <td style="text-align: right;">{{ $taxNotice->stamp_amount_formatted }}</td>
                <td style="text-align: right; font-weight: 600;">{{ $taxNotice->total_amount_formatted }}</td>
            </tr>
            <tr class="total-row">
                <td>Montant Net Réglé</td>
                <td colspan="2" style="text-align: right; font-weight: bold;">TOTAL PAYÉ :</td>
                <td style="text-align: right;">{{ $taxNotice->total_amount_formatted }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Payment details -->
    <table class="meta-table" style="background-color: #F9FAFB; padding: 15px; border-radius: 8px; border: 1px solid #E5E7EB;">
        <tr>
            <td class="meta-label" style="width: 180px;">Méthode de paiement :</td>
            <td>{{ $payment->operator === 'airtel_money' ? 'Airtel Money' : 'Moov Money' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Numéro de transaction :</td>
            <td style="font-family: monospace; font-weight: bold;">{{ $payment->transaction_id ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Date du règlement :</td>
            <td>{{ $taxNotice->paid_at ? \Carbon\Carbon::parse($taxNotice->paid_at)->format('d/m/Y H:i:s') : now()->format('d/m/Y H:i:s') }}</td>
        </tr>
    </table>

    <div class="footer">
        Cette quittance est un document officiel généré électroniquement par le système ULKY. <br>
        Toute falsification est passible de sanctions conformément aux dispositions légales en vigueur. <br>
        Vous pouvez vérifier l'authenticité de ce document à l'adresse publique : <br>
        <span style="font-family: monospace; color: #10B981;">{{ $verification_url }}</span>
    </div>

</body>
</html>
