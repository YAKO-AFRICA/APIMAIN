<!-- recu_pdf.blade.php -->
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu de paiement — {{ $paiement->codePaiement ?? '' }}</title>

    <style>
        /* CSS2 uniquement : pas de flexbox, pas de grid (compatibilité moteurs PDF) */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #111827;
            max-width: 680px;
            margin: 0 auto;
            padding: 20px;
            background: #fafafa;
        }

        .receipt {
            background: #ffffff;
            border-radius: 16px;
            padding: 32px 36px 28px;
        }

        /* Clearfix générique pour remplacer les containers flex */
        .clearfix:after {
            content: "";
            display: table;
            clear: both;
        }

        /* ===== Header ===== */
        .header {
            border-bottom: 3px solid #1D603D;
            padding-bottom: 20px;
            margin-bottom: 28px;
        }

        .header-left {
            float: left;
        }

        .header-left .logo {
            float: left;
            width: 55px;
            height: 55px;
            padding: 2px;
            background: #1D603D;
            border-radius: 15%;
            text-align: center;
            color: #fff;
            font-weight: 800;
            font-size: 18px;
            overflow: hidden;
        }

        .header-left .logo img {
            display: block;
        }

        .header-left .title-block {
            float: left;
            margin-left: 70px;
        }

        .header h1 {
            font-size: 22px;
            margin: 0;
            color: #0B482F;
            font-weight: 800;
            letter-spacing: -.3px;
        }

        .header-right {
            float: right;
            text-align: right;
        }

        .header-right .badge,
        .header-right .status-badge {
            display: inline-block;
            vertical-align: middle;
        }

        .badge {
            background: #e8f5ee;
            color: #0B482F;
            font-weight: 700;
            font-size: 11px;
            padding: 5px 14px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: .05em;
            border: 1px solid #cde6db;
            margin-right: 8px;
        }

        .status-badge {
            background: #d1fae5;
            color: #065f46;
            font-weight: 700;
            font-size: 11px;
            padding: 4px 12px 4px 10px;
            border-radius: 20px;
            border: 1px solid #a7f3d0;
        }

        .status-badge .dot {
            color: #059669;
            font-size: 10px;
            margin-right: 4px;
        }

        /* ===== Meta grid (remplacé par inline-block) ===== */
        .meta-grid {
            margin-bottom: 28px;
            background: #f8faf9;
            padding: 18px 20px;
            border-radius: 12px;
            border: 1px solid #eef2f0;
            display: inline-block;
            width: 100%;
        }

        .meta-grid .item {
            display: inline-block;
            width: 48.5%;
            vertical-align: top;
            /* padding-right: 3%; */
            padding-bottom: 12px;
        }

        .meta-grid .label {
            display: block;
            color: #6b7280;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .05em;
            font-weight: 600;
            margin-bottom: 3px;
        }

        .meta-grid .value {
            display: block;
            font-weight: 700;
            font-size: 14px;
            color: #111827;
        }

        /* ===== Table des factures ===== */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
            font-size: 13.5px;
        }

        thead th {
            background: #f3f6f4;
            color: #374151;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .04em;
            padding: 12px 14px;
            text-align: left;
            border-bottom: 2px solid #dce8e1;
        }

        tbody td {
            padding: 12px 14px;
            border-bottom: 1px solid #eef2f0;
            vertical-align: middle;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .total-row td {
            font-weight: 800;
            font-size: 16px;
            border-top: 2px solid #1D603D !important;
            border-bottom: none !important;
            padding-top: 16px;
            padding-bottom: 4px;
        }

        .total-row td:last-child {
            color: #1D603D;
        }

        /* ===== Résumé du paiement (remplacé par float) ===== */
        .payment-summary {
            background: #eef6f1;
            border-radius: 12px;
            padding: 18px 20px;
            margin: 16px 0 24px;
            border: 1px solid #cde6db;
        }

        .payment-summary .label {
            float: left;
            font-size: 13px;
            color: #1f4732;
            font-weight: 600;
            line-height: 28px;
        }

        .payment-summary .amount {
            float: right;
            font-size: 22px;
            font-weight: 800;
            color: #0B482F;
            letter-spacing: -.3px;
        }

        .footer-note {
            margin-top: 32px;
            font-size: 11.5px;
            color: #9ca3af;
            text-align: center;
            border-top: 1px solid #eef2f0;
            padding-top: 20px;
        }
    </style>
</head>

<body>
    <div class="receipt">

        <div class="header clearfix">
            <div class="header-left clearfix">
                <div class="logo">
                    <img src="data:image/jpg;base64,{{ base64_encode(file_get_contents(public_path('assets/images/ynov-logo.jpg'))) }}" width="50" height="50" style="border-radius:15%;" alt="">
                </div>
                <div class="title-block">
                    <h1>Reçu de paiement</h1>
                    <span style="font-size:12px; color:#6b7280; font-weight:500;">N° {{ $paiement->command_number ?? '' }}</span>
                </div>
            </div>
            <div class="header-right">
                <span class="badge">{{ $libelleType ?? '' }}</span>
                <span class="status-badge"><span class="dot">●</span>Payé</span>
            </div>
        </div>

        <div class="meta-grid clearfix">
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
                <span class="value" style="font-size:12px; font-weight:500; color:#6b7280;">{{ $paiement->codePaiement ?? '—' }}</span>
            </div>
        </div>

        <table class="payment-factures">
            <thead>
                <tr>
                    <th style="width:35%;">Détail</th>
                    <th style="width:30%;">Référence</th>
                    <th style="width:20%; text-align:right;">Montant</th>
                    <th style="width:15%; text-align:right;">Type</th>
                </tr>
            </thead>
            <tbody>
                @forelse($factures as $i => $facture)
                <tr>
                    <td>
                        @if(($paiement->typeReglement ?? '') === 'recoveryPrime')
                        Régularisation prime
                        @else
                        Facture n°{{ $facture->idFacture ?? '' }}
                        @endif
                    </td>
                    <td style="font-size:12px; color:#6b7280;">
                        {{ $facture->referenceSource ?: '—' }}
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
                    <td colspan="4" style="text-align:center; color:#6b7280; padding:20px;">
                        Aucune ligne de facture disponible.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="payment-summary clearfix">
            <span class="label">Total réglé</span>
            <span class="amount">{{ number_format($paiement->montant ?? 0, 0, ',', ' ') }} F CFA</span>
        </div>

        @if(($paiement->typeReglement ?? '') === 'firstPayment' && isset($fraisAdhesion))
        <div style="font-size:12px; color:#6b7280; text-align:center; padding:8px 0 4px; border-top:1px solid #eef2f0;">
            <span>Ce paiement comprend la première prime et les frais d'adhésion.</span>
        </div>
        @endif

        @if(($paiement->typeReglement ?? '') === 'recoveryPrime' && $factures->count() > 0)
        <div style="font-size:12px; color:#6b7280; text-align:center; padding:8px 0 4px; border-top:1px solid #eef2f0;">
            <span>Ce paiement régularise {{ $factures->count() }} prime{{ $factures->count() > 1 ? 's' : '' }} impayée{{ $factures->count() > 1 ? 's' : '' }}.</span>
        </div>
        @endif

        <div class="footer-note">
            Ce document tient lieu de justificatif de paiement.<br>
            En cas de question, contactez notre service client.
        </div>
    </div>
</body>

</html>