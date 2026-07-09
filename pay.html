<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Reçu de paiement — {{ $paiement->codePaiement }}</title>
<style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color:#111827; max-width:680px; margin:40px auto; padding:0 20px; }
    .header { display:flex; justify-content:space-between; align-items:center; border-bottom:3px solid #1D603D; padding-bottom:16px; margin-bottom:24px; }
    .header h1 { font-size:20px; margin:0; color:#0B482F; }
    .badge { background:#e8f5ee; color:#0B482F; font-weight:700; font-size:12px; padding:6px 12px; border-radius:20px; }
    .meta-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px 24px; margin-bottom:24px; font-size:14px; }
    .meta-grid .label { color:#6b7280; font-size:12px; text-transform:uppercase; letter-spacing:.03em; }
    .meta-grid .value { font-weight:600; }
    table { width:100%; border-collapse:collapse; margin-bottom:24px; }
    th, td { text-align:left; padding:10px 12px; font-size:13.5px; border-bottom:1px solid #e5e7eb; }
    th { background:#f9fafb; color:#374151; font-weight:600; }
    .total-row td { font-weight:700; font-size:15px; border-top:2px solid #111827; border-bottom:none; }
    .actions { margin-top:24px; text-align:center; }
    .actions button { background:#1D603D; color:#fff; border:none; padding:12px 28px; border-radius:8px; font-weight:700; cursor:pointer; font-size:14px; }
    .footer-note { margin-top:32px; font-size:11.5px; color:#9ca3af; text-align:center; }
    @media print {
        .actions { display:none; }
        body { margin:0; }
    }
</style>
</head>
<body>
    <div class="header">
        <h1>Reçu de paiement</h1>
        <span class="badge">{{ $libelleType }}</span>
    </div>

    <div class="meta-grid">
        <div>
            <div class="label">Référence</div>
            <div class="value">{{ $paiement->codePaiement }}</div>
        </div>
        <div>
            <div class="label">Date</div>
            <div class="value">{{ \Illuminate\Support\Carbon::parse($paiement->datepaiement)->format('d/m/Y H:i') }}</div>
        </div>
        <div>
            <div class="label">Moyen de paiement</div>
            <div class="value">{{ ucfirst($paiement->payment_mode) }}</div>
        </div>
        <div>
            <div class="label">Statut</div>
            <div class="value">{{ $paiement->etat == 1 ? 'Payé' : ($paiement->etat == 2 ? 'Échec' : 'En attente') }}</div>
        </div>
        @if($paiement->referenceSource)
        <div>
            <div class="label">Contrat</div>
            <div class="value">{{ $paiement->referenceSource }}</div>
        </div>
        @endif
        @if($paiement->emailpayeur)
        <div>
            <div class="label">Email</div>
            <div class="value">{{ $paiement->emailpayeur }}</div>
        </div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Détail</th>
                <th>Référence origine</th>
                <th style="text-align:right">Montant</th>
            </tr>
        </thead>
        <tbody>
            @forelse($factures as $i => $facture)
            <tr>
                <td>{{ $paiement->typePaiement === 'recoveryPrime' ? 'Régularisation prime' : 'Prime n°' . ($i + 1) }}</td>
                <td>{{ $facture->referenceSource ?: '—' }}</td>
                <td style="text-align:right">{{ number_format($facture->prime, 0, ',', ' ') }} XOF</td>
            </tr>
            @empty
            <tr><td colspan="3">Aucune ligne de facture.</td></tr>
            @endforelse
            <tr class="total-row">
                <td colspan="2">Total réglé</td>
                <td style="text-align:right">{{ number_format($paiement->montant, 0, ',', ' ') }} XOF</td>
            </tr>
        </tbody>
    </table>

    <div class="actions">
        <button onclick="window.print()">Télécharger / Imprimer le reçu</button>
    </div>

    <p class="footer-note">Ce document tient lieu de justificatif de paiement. Conservez-le.</p>
</body>
</html>