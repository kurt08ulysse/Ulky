<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentification de Quittance - ULKY</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #F3F4F6;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            background-color: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            max-width: 500px;
            width: 100%;
            padding: 30px;
            box-sizing: border-box;
            text-align: center;
        }
        .status-badge {
            background-color: #D1FAE5;
            color: #065F46;
            padding: 8px 16px;
            border-radius: 9999px;
            font-weight: 700;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 20px;
            border: 1px solid #10B981;
        }
        .icon {
            font-size: 48px;
            color: #10B981;
            margin-bottom: 15px;
        }
        h1 {
            font-size: 20px;
            font-weight: 800;
            color: #111827;
            margin: 0 0 10px 0;
            text-transform: uppercase;
        }
        .subtitle {
            font-size: 14px;
            color: #6B7280;
            margin-bottom: 25px;
        }
        .info-card {
            background-color: #F9FAFB;
            border: 1px solid #E5E7EB;
            border-radius: 12px;
            padding: 20px;
            text-align: left;
            margin-bottom: 25px;
            gap: 12px;
            display: flex;
            flex-direction: column;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px dashed #E5E7EB;
            padding-bottom: 8px;
        }
        .info-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .info-label {
            font-size: 13px;
            color: #4B5563;
            font-weight: 600;
        }
        .info-value {
            font-size: 13px;
            color: #111827;
            font-weight: 700;
            text-align: right;
        }
        .logo-footer {
            font-size: 16px;
            font-weight: bold;
            color: #10B981;
            margin-top: 10px;
        }
        .verified-by {
            font-size: 11px;
            color: #9CA3AF;
            margin-top: 5px;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="icon">🛡️</div>
        <h1>Quittance Authentique</h1>
        <div class="subtitle">Ce document a été généré et certifié par l'Administration Municipale</div>

        <div class="status-badge">
            ✓ Paiement Confirmé
        </div>

        <div class="info-card">
            <div class="info-row">
                <span class="info-label">Numéro de Quittance :</span>
                <span class="info-value" style="font-family: monospace; color: #10B981;">{{ $receipt->receipt_number }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Contribuable :</span>
                <span class="info-value">{{ $user->name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Désignation de l'acte :</span>
                <span class="info-value">{{ $tax->name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Montant Réglé :</span>
                <span class="info-value">{{ $taxNotice->total_amount_formatted }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">ID Transaction SingPay :</span>
                <span class="info-value" style="font-family: monospace;">{{ $payment->transaction_id ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Mode de règlement :</span>
                <span class="info-value">{{ $payment->operator === 'airtel_money' ? 'Airtel Money' : 'Moov Money' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Date du paiement :</span>
                <span class="info-value">{{ \Carbon\Carbon::parse($taxNotice->paid_at)->format('d/m/Y H:i:s') }}</span>
            </div>
        </div>

        <div class="logo-footer">ULKY Mairie</div>
        <div class="verified-by">Système de Vérification d'Authenticité Numérique</div>
    </div>

</body>
</html>
