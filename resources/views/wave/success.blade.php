<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement Wave - Succès</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background:linear-gradient(135deg, #1DC8FF 0%, #4A90E2 100%);
            min-height:100vh; display:flex; align-items:center; justify-content:center; padding:20px;
        }
        .card {
            background:#fff; border-radius:20px; box-shadow:0 20px 60px rgba(0,0,0,.2);
            padding:48px 40px; max-width:480px; width:100%; text-align:center;
        }
        .icon {
            width:90px; height:90px; margin:0 auto 24px; border-radius:50%;
            display:flex; align-items:center; justify-content:center; font-size:48px;
        }
        .icon.success { background:linear-gradient(135deg,#d1fae5,#a7f3d0); color:#059669; }
        .icon.pending { background:linear-gradient(135deg,#fef3c7,#fde68a); color:#d97706; }
        .icon.error   { background:linear-gradient(135deg,#fee2e2,#fecaca); color:#dc2626; }
        h1 { font-size:24px; font-weight:700; color:#111827; margin-bottom:12px; }
        p { font-size:14.5px; color:#6b7280; line-height:1.6; margin-bottom:8px; }
        .amount-box {
            background:linear-gradient(135deg,#F0F9FF,#E0F2FE); border:2px solid #1DC8FF;
            border-radius:14px; padding:20px; margin:24px 0;
        }
        .amount-label {
            font-size:12px; text-transform:uppercase; letter-spacing:1px;
            color:#0369a1; font-weight:600; margin-bottom:6px;
        }
        .amount-value { font-size:30px; font-weight:800; color:#0c4a6e; }
        .details {
            background:#f9fafb; border-radius:12px; padding:16px; margin:20px 0;
            text-align:left; border:1px solid #f0f0f0;
        }
        .detail-row { display:flex; justify-content:space-between; padding:6px 0; font-size:13px; }
        .detail-row .key { color:#6b7280; font-weight:600; }
        .detail-row .value { color:#111827; font-weight:700; }
        .btn {
            display:inline-block; margin-top:20px; padding:14px 32px;
            background:linear-gradient(135deg,#1DC8FF,#0EA5E9); color:#fff;
            text-decoration:none; border-radius:12px; font-weight:700; font-size:15px;
            box-shadow:0 8px 20px rgba(29,200,255,.35);
        }
    </style>
</head>
<body>
    <div class="card">
        @php $status = $paiement->payment_status ?? 'unknown'; @endphp

        @if ($status === 'succeeded')
            <div class="icon success">✓</div>
            <h1>Paiement réussi</h1>
            <p>Votre paiement a bien été confirmé par Wave.</p>

            <div class="amount-box">
                <div class="amount-label">Montant payé</div>
                <div class="amount-value">{{ number_format($paiement->montant ?? 0, 0, ',', ' ') }} XOF</div>
            </div>

            <div class="details">
                <div class="detail-row">
                    <span class="key">Référence</span>
                    <span class="value">{{ $paiement->codePaiement ?? '—' }}</span>
                </div>
                @if ($paiement->payment_token)
                <div class="detail-row">
                    <span class="key">Session Wave</span>
                    <span class="value">{{ $paiement->payment_token }}</span>
                </div>
                @endif
                @if ($paiement->payment_validation_date)
                <div class="detail-row">
                    <span class="key">Date</span>
                    <span class="value">{{ $paiement->payment_validation_date }}</span>
                </div>
                @endif
            </div>

        @elseif (in_array($status, ['processing', 'pending']))
            <div class="icon pending">⏳</div>
            <h1>Paiement en cours</h1>
            <p>Votre paiement est en cours de traitement par Wave.</p>

            <div class="details">
                <div class="detail-row">
                    <span class="key">Référence</span>
                    <span class="value">{{ $paiement->codePaiement ?? '—' }}</span>
                </div>
                <div class="detail-row">
                    <span class="key">Statut</span>
                    <span class="value">{{ ucfirst($status) }}</span>
                </div>
            </div>

        @else
            <div class="icon error">✕</div>
            <h1>Paiement non confirmé</h1>
            <p>Votre paiement n'a pas pu être confirmé.</p>

            <div class="details">
                <div class="detail-row">
                    <span class="key">Référence</span>
                    <span class="value">{{ $paiement->codePaiement ?? '—' }}</span>
                </div>
                <div class="detail-row">
                    <span class="key">Statut</span>
                    <span class="value">{{ ucfirst($status) }}</span>
                </div>
            </div>
        @endif

        <a href="/" class="btn">Retour à l'accueil</a>
    </div>
</body>
</html>
