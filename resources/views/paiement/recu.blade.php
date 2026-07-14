<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reçu de paiement — {{ $paiement->codePaiement ?? '' }}</title>
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { 
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
        color:#111827; 
        max-width:680px; 
        margin:40px auto; 
        padding:0 20px; 
        background:#fafafa;
    }
    
    .receipt {
        background:#ffffff;
        border-radius:16px;
        box-shadow:0 4px 24px rgba(0,0,0,.06);
        padding:32px 36px 28px;
    }
    
    .header { 
        display:flex; 
        justify-content:space-between; 
        align-items:center; 
        border-bottom:3px solid #1D603D; 
        padding-bottom:20px; 
        margin-bottom:28px; 
    }
    .header-left { display:flex; align-items:center; gap:14px; }
    .header-left .logo { 
        width:44px; 
        height:44px; 
        background:#1D603D; 
        border-radius:12px; 
        display:flex; 
        align-items:center; 
        justify-content:center; 
        color:#fff; 
        font-weight:800; 
        font-size:18px; 
    }
    .header h1 { 
        font-size:22px; 
        margin:0; 
        color:#0B482F; 
        font-weight:800; 
        letter-spacing:-.3px;
    }
    .badge { 
        background:#e8f5ee; 
        color:#0B482F; 
        font-weight:700; 
        font-size:11px; 
        padding:5px 14px; 
        border-radius:20px; 
        text-transform:uppercase;
        letter-spacing:.05em;
        border:1px solid #cde6db;
    }
    
    .meta-grid { 
        display:grid; 
        grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); 
        gap:16px 24px; 
        margin-bottom:28px; 
        background:#f8faf9;
        padding:18px 20px;
        border-radius:12px;
        border:1px solid #eef2f0;
    }
    .meta-grid .item { display:flex; flex-direction:column; gap:3px; }
    .meta-grid .label { 
        color:#6b7280; 
        font-size:11px; 
        text-transform:uppercase; 
        letter-spacing:.05em; 
        font-weight:600;
    }
    .meta-grid .value { 
        font-weight:700; 
        font-size:14px; 
        color:#111827;
    }
    
    table { 
        width:100%; 
        border-collapse:collapse; 
        margin-bottom:24px; 
        font-size:13.5px;
    }
    thead th { 
        background:#f3f6f4; 
        color:#374151; 
        font-weight:700; 
        font-size:12px; 
        text-transform:uppercase; 
        letter-spacing:.04em;
        padding:12px 14px; 
        text-align:left; 
        border-bottom:2px solid #dce8e1;
    }
    tbody td { 
        padding:12px 14px; 
        border-bottom:1px solid #eef2f0; 
        vertical-align:middle;
    }
    tbody tr:last-child td { border-bottom:none; }
    
    .total-row td { 
        font-weight:800; 
        font-size:16px; 
        border-top:2px solid #1D603D !important; 
        border-bottom:none !important;
        padding-top:16px;
        padding-bottom:4px;
    }
    .total-row td:last-child { 
        color:#1D603D; 
    }
    
    .payment-summary {
        background:linear-gradient(135deg, #f0f7f3, #e6f1eb);
        border-radius:12px;
        padding:18px 20px;
        margin:16px 0 24px;
        display:flex;
        justify-content:space-between;
        align-items:center;
        border:1px solid #cde6db;
    }
    .payment-summary .label { font-size:13px; color:#1f4732; font-weight:600; }
    .payment-summary .amount { 
        font-size:22px; 
        font-weight:800; 
        color:#0B482F; 
        letter-spacing:-.3px;
    }
    
    .status-badge {
        display:inline-flex;
        align-items:center;
        gap:6px;
        background:#d1fae5;
        color:#065f46;
        font-weight:700;
        font-size:11px;
        padding:4px 12px 4px 10px;
        border-radius:20px;
        border:1px solid #a7f3d0;
    }
    .status-badge::before {
        content:"●";
        font-size:10px;
        color:#059669;
        animation: pulse-dot 1.5s ease-in-out infinite;
    }
    @keyframes pulse-dot {
        0%, 100% { opacity:1; }
        50% { opacity:.3; }
    }
    
    .actions { 
        margin-top:28px; 
        display:flex; 
        gap:12px;
        justify-content:center;
        flex-wrap:wrap;
    }
    .actions button, .actions .btn {
        background:#1D603D; 
        color:#fff; 
        border:none; 
        padding:12px 32px; 
        border-radius:10px; 
        font-weight:700; 
        cursor:pointer; 
        font-size:14px;
        transition:all .15s;
        font-family:inherit;
        text-decoration:none;
        display:inline-flex;
        align-items:center;
        gap:8px;
    }
    .actions button:hover, .actions .btn:hover {
        background:#0B482F;
        transform:translateY(-1px);
        box-shadow:0 4px 12px rgba(29,96,61,.25);
    }
    .actions .btn-secondary {
        background:#f3f4f6;
        color:#374151;
        border:1px solid #d1d5db;
    }
    .actions .btn-secondary:hover {
        background:#e5e7eb;
        box-shadow:0 4px 12px rgba(0,0,0,.08);
    }
    
    .footer-note { 
        margin-top:32px; 
        font-size:11.5px; 
        color:#9ca3af; 
        text-align:center; 
        border-top:1px solid #eef2f0;
        padding-top:20px;
    }
    
    /* Print styles */
    @media print {
        body { background:#fff; margin:0; padding:20px; }
        .receipt { box-shadow:none; padding:20px; border:1px solid #e5e7eb; }
        .actions, .no-print { display:none !important; }
        .meta-grid { background:#f9fafb; border-color:#e5e7eb; }
        .payment-summary { background:#f3f4f6; border-color:#d1d5db; }
        thead th { background:#f3f4f6; }
        .badge { background:#f3f4f6; border-color:#d1d5db; }
        .status-badge { background:#d1fae5; border-color:#a7f3d0; }
    }
    
    @media (max-width: 480px) {
        .receipt { padding:20px; }
        .header { flex-direction:column; align-items:flex-start; gap:12px; }
        .badge { align-self:flex-start; }
        .meta-grid { grid-template-columns:1fr 1fr; gap:10px; }
        .payment-summary { flex-direction:column; text-align:center; gap:6px; }
        .actions button, .actions .btn { width:100%; justify-content:center; }
        .total-row td { font-size:14px; }
    }
</style>
</head>
<body>
<div class="receipt">
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <div class="logo">Y</div>
            <div>
                <h1>Reçu de paiement</h1>
                <span style="font-size:12px; color:#6b7280; font-weight:500;">N° {{ $paiement->codePaiement ?? '' }}</span>
            </div>
        </div>
        <div style="display:flex; align-items:center; gap:10px;">
            <span class="badge">{{ $libelleType ?? '' }}</span>
            <span class="status-badge">Payé</span>
        </div>
    </div>

    <!-- Métadonnées -->
    <div class="meta-grid">
        <div class="item">
            <span class="label">Date de paiement</span>
            <span class="value">{{ $paiement->payment_validation_date ? date('d/m/Y à H:i', strtotime($paiement->payment_validation_date)) : '' }}</span>
        </div>
        <div class="item">
            <span class="label">Moyen de paiement</span>
            <span class="value">{{ ucfirst($paiement->payment_mode ?? '') }}</span>
        </div>
        @if($paiement->idproposition || $paiement->idContrat)
        <div class="item">
            <span class="label">Contrat</span>
            <span class="value">#{{ $paiement->idproposition ?? $paiement->idContrat ?? '' }}</span>
        </div>
        @endif
        @if($paiement->emailpayeur)
        <div class="item">
            <span class="label">Email</span>
            <span class="value">{{ $paiement->emailpayeur }}</span>
        </div>
        @endif
        @if($paiement->telpaiement)
        <div class="item">
            <span class="label">Téléphone</span>
            <span class="value">{{ $paiement->telpaiement }}</span>
        </div>
        @endif
        <div class="item">
            <span class="label">Référence transaction</span>
            <span class="value" style="font-size:12px; font-weight:500; color:#6b7280;">{{ $paiement->command_number ?? '—' }}</span>
        </div>
    </div>

    <!-- Détail des primes -->
    <table>
        <thead>
            <tr>
                <th style="width:35%;">Détail</th>
                <th style="width:30%;">Référence</th>
                <th style="width:35%; text-align:right;">Montant</th>
                <th style="width:35%; text-align:right;">Type</th>
            </tr>
        </thead>
        <tbody class="payment-summary">
            @forelse($factures as $i => $facture)
            <tr>
                <td>
                    @if(($paiement->typeReglement ?? '') === 'recoveryPrime')
                        <span style="display:flex; align-items:center; gap:6px;">
                            <span style="font-size:16px;">🔄</span>
                            Régularisation prime
                        </span>
                    @else
                        <span style="display:flex; align-items:center; gap:6px;">
                            <span style="font-size:16px;">📄</span>
                            Facture n°{{ $idFacture ?? '' }}
                        </span>
                    @endif
                </td>
                <td style="font-size:12px; color:#6b7280;">
                    {{ $facture->referenceSource ?: '—' }}
                    <!-- @if($facture->idFacture)
                        <div style="font-size:11px; color:#9ca3af;">{{ $facture->idFacture }}</div>
                    @endif -->
                </td>
                <td style="text-align:right; font-weight:600;">
                    {{ number_format($facture->prime, 0, ',', ' ') }} F CFA
                </td>
                <td style="text-align:right; font-weight:600;">
                    {{ $facture->libelleTypeFacture ?? '' }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="3" style="text-align:center; color:#6b7280; padding:20px;">
                    Aucune ligne de facture disponible.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Total -->
    <div class="payment-summary">
        <span class="label">Total réglé</span>
        <span class="amount">{{ number_format($paiement->montant ?? 0, 0, ',', ' ') }} XOF</span>
    </div>

    <!-- Informations supplémentaires -->
    @if(($paiement->typeReglement ?? '') === 'firstPayment' && isset($fraisAdhesion))
    <div style="font-size:12px; color:#6b7280; text-align:center; padding:8px 0 4px; border-top:1px solid #eef2f0;">
        <span>⚠️ Ce paiement comprend la première prime et les frais d'adhésion.</span>
    </div>
    @endif

    @if(($paiement->typeReglement ?? '') === 'recoveryPrime' && $factures->count() > 0)
    <div style="font-size:12px; color:#6b7280; text-align:center; padding:8px 0 4px; border-top:1px solid #eef2f0;">
        <span>📋 Ce paiement régularise {{ $factures->count() }} prime{{ $factures->count() > 1 ? 's' : '' }} impayée{{ $factures->count() > 1 ? 's' : '' }}.</span>
    </div>
    @endif

    <!-- Actions -->
    <div class="actions">
        <button onclick="window.print()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M6 9V3h12v6M6 21h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                <path d="M18 9V5H6v4"/>
            </svg>
            Imprimer / Télécharger
        </button>
    </div>

    <!-- Footer -->
    <div class="footer-note">
        Ce document tient lieu de justificatif de paiement.<br>
        En cas de question, contactez notre service client.
    </div>
</div>
</body>
</html>