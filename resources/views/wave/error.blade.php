<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement Wave - Échec</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background:linear-gradient(135deg,#f87171,#dc2626);
            min-height:100vh; display:flex; align-items:center; justify-content:center; padding:20px;
        }
        .card {
            background:#fff; border-radius:20px; box-shadow:0 20px 60px rgba(0,0,0,.25);
            padding:48px 40px; max-width:480px; width:100%; text-align:center;
        }
        .icon {
            width:90px; height:90px; margin:0 auto 24px; border-radius:50%;
            background:linear-gradient(135deg,#fee2e2,#fecaca); color:#dc2626;
            display:flex; align-items:center; justify-content:center; font-size:48px;
        }
        h1 { font-size:24px; font-weight:700; color:#111827; margin-bottom:12px; }
        p { font-size:14.5px; color:#6b7280; line-height:1.6; margin-bottom:8px; }
        .details {
            background:#fef2f2; border:1px solid #fecaca; border-radius:12px;
            padding:16px; margin:24px 0; text-align:left;
        }
        .detail-row { display:flex; justify-content:space-between; padding:6px 0; font-size:13px; }
        .detail-row .key { color:#991b1b; font-weight:600; }
        .detail-row .value { color:#7f1d1d; font-weight:700; }
        .actions { display:flex; gap:10px; margin-top:24px; }
        .btn {
            flex:1; display:inline-block; padding:14px 20px; border-radius:12px;
            font-weight:700; font-size:14.5px; text-decoration:none; text-align:center;
        }
        .btn-primary {
            background:linear-gradient(135deg,#1DC8FF,#0EA5E9); color:#fff;
            box-shadow:0 8px 20px rgba(29,200,255,.35);
        }
        .btn-secondary { background:#fff; color:#374151; border:2px solid #e5e7eb; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">✕</div>
        <h1>Paiement échoué</h1>
        <p>Votre paiement n'a pas pu aboutir.</p>
        <p style="font-size:13px;color:#9ca3af;">Aucun montant ne vous a été prélevé.</p>

        @if (!empty($paiement))
            <div class="details">
                <div class="detail-row">
                    <span class="key">Référence</span>
                    <span class="value">{{ $paiement->codePaiement ?? '—' }}</span>
                </div>
                <div class="detail-row">
                    <span class="key">Statut</span>
                    <span class="value">{{ ucfirst($paiement->payment_status ?? 'unknown') }}</span>
                </div>
                @if ($paiement->montant)
                <div class="detail-row">
                    <span class="key">Montant</span>
                    <span class="value">{{ number_format($paiement->montant, 0, ',', ' ') }} XOF</span>
                </div>
                @endif
            </div>
        @endif

        <div class="actions">
            <a href="/" class="btn btn-secondary">Accueil</a>
            <a href="javascript:history.back()" class="btn btn-primary">Réessayer</a>
        </div>
    </div>
</body>
</html>
