<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Jeko Payment Widget - Intégration</title>
    <style>
        /* ============================================================
           STYLES GLOBAUX
           ============================================================ */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 990px;
            margin: 40px auto;
            padding: 0 20px;
            color: #1f2937;
            background: #f9fafb;
        }

        .container {
            background: #fff;
            border-radius: 16px;
            padding: 32px 28px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        }

        /* ============================================================
           EN-TÊTE
           ============================================================ */
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid #f0f2f1;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-left .logo {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #1D603D, #0B482F);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 800;
            font-size: 18px;
        }

        .header-left h1 {
            font-size: 22px;
            font-weight: 700;
            color: #111827;
            margin: 0;
        }

        .header-left .subtitle {
            font-size: 13px;
            color: #6b7280;
            font-weight: 400;
        }

        .badge-version {
            background: #e8f5ee;
            color: #1D603D;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
        }

        /* ============================================================
           DÉMO WIDGET
           ============================================================ */
        .demo-section {
            background: #f8faf9;
            border-radius: 12px;
            padding: 20px 24px;
            margin-bottom: 24px;
            border: 1px solid #eef2f0;
        }

        .demo-section .demo-title {
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .demo-section .demo-title .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }

        .demo-section .demo-title .dot.green {
            background: #22c55e;
        }

        .demo-section .demo-title .dot.orange {
            background: #f59e0b;
        }

        .demo-section .demo-title .dot.purple {
            background: #8b5cf6;
        }

        .demo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }

        .demo-card {
            background: #fff;
            border-radius: 10px;
            padding: 16px 18px;
            border: 1px solid #e5e7eb;
            transition: all 0.2s;
            cursor: pointer;
        }

        .demo-card:hover {
            border-color: #1D603D;
            box-shadow: 0 4px 12px rgba(29, 96, 61, 0.1);
            transform: translateY(-2px);
        }

        .demo-card .card-badge {
            font-size: 11px;
            font-weight: 700;
            padding: 2px 10px;
            border-radius: 12px;
            display: inline-block;
            margin-bottom: 8px;
        }

        .demo-card .card-badge.green {
            background: #d1fae5;
            color: #065f46;
        }

        .demo-card .card-badge.orange {
            background: #fef3c7;
            color: #92400e;
        }

        .demo-card .card-badge.purple {
            background: #ede9fe;
            color: #5b21b6;
        }

        .demo-card .card-badge.blue {
            background: #dbeafe;
            color: #1e40af;
        }

        .demo-card .card-badge.red {
            background: #fee2e2;
            color: #991b1b;
        }

        .demo-card h4 {
            font-size: 14px;
            font-weight: 700;
            color: #111827;
            margin: 0 0 4px;
        }

        .demo-card p {
            font-size: 12.5px;
            color: #6b7280;
            margin: 0 0 10px;
        }

        .demo-card .btn-pay-sm {
            background: #1D603D;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            width: 100%;
            transition: all 0.2s;
        }

        .demo-card .btn-pay-sm:hover {
            background: #0B482F;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(29, 96, 61, 0.25);
        }

        .demo-card .btn-pay-sm:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }

        /* ============================================================
           ONGLETS D'INTÉGRATION
           ============================================================ */
        .tabs-container {
            margin-top: 28px;
        }

        .tabs-header {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 0;
            background: #f3f5f4;
            border-radius: 10px 10px 0 0;
            padding: 4px 4px 0;
        }

        .tab-btn {
            padding: 10px 20px;
            font-size: 13px;
            font-weight: 600;
            color: #6b7280;
            background: transparent;
            border: none;
            border-radius: 8px 8px 0 0;
            cursor: pointer;
            transition: all 0.2s;
            font-family: inherit;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tab-btn:hover {
            color: #1D603D;
            background: rgba(29, 96, 61, 0.06);
        }

        .tab-btn.active {
            color: #1D603D;
            background: #fff;
            box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.04);
        }

        .tab-btn .tab-icon {
            font-size: 16px;
        }

        .tab-content {
            display: none;
            padding: 20px 0 0;
            background: #fff;
            border-radius: 0 0 10px 10px;
        }

        .tab-content.active {
            display: block;
        }

        .tab-content .code-block {
            background: #0c1f15;
            border-radius: 10px;
            padding: 20px 24px;
            position: relative;
            overflow-x: auto;
            margin: 8px 0 16px;
        }

        .tab-content .code-block pre {
            margin: 0;
            color: #d7ecdf;
            font-family: 'JetBrains Mono', 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.7;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .tab-content .code-block .copy-btn {
            position: absolute;
            top: 10px;
            right: 12px;
            background: rgba(247, 164, 0, 0.15);
            border: 1px solid rgba(247, 164, 0, 0.3);
            color: #F7A400;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 11px;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 600;
        }

        .tab-content .code-block .copy-btn:hover {
            background: rgba(247, 164, 0, 0.3);
            color: #fff;
        }

        .tab-content .code-block .lang-tag {
            position: absolute;
            top: 10px;
            left: 14px;
            font-size: 10px;
            font-weight: 700;
            color: rgba(215, 236, 223, 0.4);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .tab-content .code-description {
            font-size: 13.5px;
            color: #4b5563;
            margin-bottom: 12px;
            line-height: 1.6;
        }

        .tab-content .code-description strong {
            color: #1D603D;
        }

        /* ============================================================
           INFOS
           ============================================================ */
        .info-box {
            background: #f0f7f3;
            border-left: 4px solid #1D603D;
            padding: 14px 18px;
            border-radius: 8px;
            font-size: 13px;
            color: #374151;
            margin-top: 20px;
            line-height: 1.6;
        }

        .info-box code {
            background: #e5e7eb;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
        }

        .info-box .separator {
            margin: 4px 0;
        }

        .info-box .highlight {
            color: #1D603D;
            font-weight: 600;
        }

        /* ============================================================
           RESPONSIVE
           ============================================================ */
        @media (max-width: 640px) {
            .container {
                padding: 20px 16px;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .demo-grid {
                grid-template-columns: 1fr;
            }

            .tabs-header {
                flex-wrap: nowrap;
                overflow-x: auto;
                gap: 2px;
            }

            .tab-btn {
                padding: 8px 14px;
                font-size: 12px;
                white-space: nowrap;
            }

            .tab-btn .tab-icon {
                font-size: 14px;
            }

            .tab-content .code-block {
                padding: 16px;
                font-size: 12px;
            }

            .tab-content .code-block pre {
                font-size: 12px;
            }
        }

        @media (max-width: 480px) {
            .header-left h1 {
                font-size: 18px;
            }

            .demo-card {
                padding: 14px;
            }

            .tab-btn {
                padding: 6px 10px;
                font-size: 11px;
            }
        }

    </style>
</head>
@verbatim

<body>

    <div class="container">
        <!-- ============================================================
        EN-TÊTE
        ============================================================ -->
        <div class="header">
            <div class="header-left">
                <div class="logo">💳</div>
                <div>
                    <h1>Jeko Payment Widget</h1>
                    <span class="subtitle">Intégration pour développeurs front-end</span>
                </div>
            </div>
            <span class="badge-version">v2.0</span>
        </div>

        <!-- ============================================================
        DÉMO DU WIDGET
        ============================================================ -->
        <div class="demo-section">
            <div class="demo-title">
                <span class="dot green"></span>
                Tester les 3 types de paiement
                <span style="font-weight:400; color:#6b7280; font-size:13px; margin-left:8px;">
                    — Cliquez sur un scénario pour ouvrir le widget
                </span>
            </div>

            <div class="demo-grid">
                <!-- Carte 1: Premier paiement -->
                <div class="demo-card" id="cardFirstPayment">
                    <span class="card-badge green">1️⃣ Souscription</span>
                    <h4>Premier paiement</h4>
                    <p>Après validation d'un nouveau contrat</p>
                    <div style="margin-bottom:8px;">
                        <input type="text" id="contractIdFirstPayment" value="27" style="width:100%; padding:6px 10px; border:1.5px solid #e5e7eb; border-radius:6px; font-size:13px; box-sizing:border-box;" readonly />
                    </div>
                    <button class="btn-pay-sm" id="btnFirstPayment">Payer la première prime</button>
                </div>

                <!-- Carte 2: Paiement anticipé -->
                <div class="demo-card" id="cardEarlyPayment">
                    <span class="card-badge orange">2️⃣ Avance</span>
                    <h4>Paiement anticipé</h4>
                    <p>Renseignez l'identifiant du contrat</p>
                    <div style="margin-bottom:8px;">
                        <input type="text" id="contractIdEarly" placeholder="Ex: 3593104" value="3593104" style="width:100%; padding:6px 10px; border:1.5px solid #e5e7eb; border-radius:6px; font-size:13px; box-sizing:border-box;" />
                    </div>
                    <button class="btn-pay-sm" id="btnEarlyPayment">Payer en avance</button>
                </div>

                <!-- Carte 3: Régularisation -->
                <div class="demo-card" id="cardRecoveryPrime">
                    <span class="card-badge purple">3️⃣ Régularisation</span>
                    <h4>Récupération d'impayés</h4>
                    <p>Renseignez l'identifiant du contrat et Sélectionnez les factures à régulariser</p>
                    <div style="margin-bottom:8px;">
                        <input type="text" id="contractIdRecovery" placeholder="Ex: 12452" value="82718" style="width:100%; padding:6px 10px; border:1.5px solid #e5e7eb; border-radius:6px; font-size:13px; box-sizing:border-box;" />
                    </div>
                    <button class="btn-pay-sm" id="btnRecoveryPrime">Régulariser mes impayés</button>
                </div>
            </div>

            <!-- Zone de pré-sélection des factures -->
            <div style="margin-top:16px; padding:12px 16px; background:#fff; border-radius:8px; border:1px solid #e5e7eb;">
                <p style="font-size:12px; font-weight:600; color:#374151; margin:0 0 8px;">
                    🧪 Factures pré-sélectionnées pour la régularisation :
                </p>
                <div style="display:flex; flex-wrap:wrap; gap:8px 16px;">
                    <label style="font-size:13px; display:flex; align-items:center; gap:6px; cursor:pointer;">
                        <input type="checkbox" name="invoice_ids" value="173669" checked /> #173669
                    </label>
                    <label style="font-size:13px; display:flex; align-items:center; gap:6px; cursor:pointer;">
                        <input type="checkbox" name="invoice_ids" value="3073532" /> #3073532
                    </label>
                    <label style="font-size:13px; display:flex; align-items:center; gap:6px; cursor:pointer;">
                        <input type="checkbox" name="invoice_ids" value="3132514" checked /> #3132514
                    </label>
                    <label style="font-size:13px; display:flex; align-items:center; gap:6px; cursor:pointer;">
                        <input type="checkbox" name="invoice_ids" value="3191079" checked /> #3191079
                    </label>
                    <label style="font-size:13px; display:flex; align-items:center; gap:6px; cursor:pointer;">
                        <input type="checkbox" name="invoice_ids" value="3565293" checked /> #3565293
                    </label>
                    <label style="font-size:13px; display:flex; align-items:center; gap:6px; cursor:pointer;">
                        <input type="checkbox" name="invoice_ids" value="3628563" checked /> #3628563
                    </label>
                </div>
                <p style="font-size:11px; color:#6b7280; margin:6px 0 0;">
                    💡 Ces factures seront automatiquement pré-sélectionnées dans le widget
                </p>
            </div>
        </div>

        <!-- ============================================================
        ONGLETS D'INTÉGRATION
        ============================================================ -->
        <div class="tabs-container">
            <div class="tabs-header">
                <button class="tab-btn active" data-tab="html">
                    <span class="tab-icon">🌐</span> HTML / Vanilla JS
                </button>
                <button class="tab-btn" data-tab="react">
                    <span class="tab-icon">⚛️</span> React
                </button>
                <button class="tab-btn" data-tab="vue">
                    <span class="tab-icon">🟢</span> Vue.js
                </button>
                <button class="tab-btn" data-tab="angular">
                    <span class="tab-icon">🅰️</span> Angular
                </button>
                <button class="tab-btn" data-tab="nextjs">
                    <span class="tab-icon">▲</span> Next.js
                </button>
            </div>

            <!-- ============================================================
TAB 1: HTML / Vanilla JS - Version complète avec 3 types
============================================================ -->
            <div class="tab-content active" id="tab-html">
                <p class="code-description">
                    Intégration simple dans une page HTML classique avec les 3 types de paiement.
                    Chargez le script du widget et initialisez-le avec vos paramètres.
                </p>

                <!-- ============================================================
    DOCUMENTATION DÉTAILLÉE HTML / VANILLA JS
    ============================================================ -->
                <div style="background: #f8faf9; border-radius: 8px; padding: 16px 20px; margin-bottom: 16px; border-left: 4px solid #1D603D;">
                    <h4 style="margin: 0 0 8px; font-size: 14px; color: #1D603D;">📖 Documentation d'intégration</h4>

                    <div style="font-size: 13px; color: #4b5563; line-height: 1.7;">
                        <p style="margin: 0 0 8px;"><strong>🎯 Support multi-providers :</strong> Ce widget supporte Wave et Jeko selon le paymentMethod sélectionné.</p>
                        
                        <p style="margin: 0 0 8px;"><strong>1. Inclure le widget JS :</strong></p>
                        <pre style="background: #0c1f15; color: #d7ecdf; padding: 8px 12px; border-radius: 6px; font-size: 12px; margin: 4px 0 12px; overflow-x: auto;">
&lt;script src="{{ url('/api/paiements/jeko-payment-widget.js') }}"&gt;&lt;/script&gt;</pre>

                        <p style="margin: 0 0 8px;"><strong>2. Initialiser le widget :</strong></p>
                        <pre style="background: #0c1f15; color: #d7ecdf; padding: 8px 12px; border-radius: 6px; font-size: 12px; margin: 4px 0 12px; overflow-x: auto;">
const widget = new JekoWidget({
    // Endpoint pour initialiser le paiement (unifié Wave + Jeko)
    backendEndpoint: "{{ url('/api/paiements/init') }}",

    // Endpoint pour vérifier le contrat
    contractCheckEndpoint: "{{ url('/api/paiements/contrat/verifier') }}",

    // Endpoint pour vérifier le statut du paiement (polling automatique)
    statusCheckEndpoint: "{{ url('/api/paiements/statut') }}",

    // Devise par défaut (XOF, XAF, USD, EUR)
    currency: "XOF",

    // Timeout de la requête en millisecondes
    timeout: 30000,

    // Vérification automatique du contrat si contractId fourni
    autoVerifyContract: true,

    // Configuration du polling du statut (optionnel)
    statusPollInterval: 3000,          // Intervalle de polling en ms (défaut: 3000)
    statusPollMaxAttempts: 20,         // Nombre max de tentatives (défaut: 20)

    // Headers personnalisés (CSRF, etc.)
    headers: {
        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content || "",
        "Accept": "application/json",
    },

    // Callbacks pour gérer les événements
    callbacks: {
        onSuccess: (redirectUrl, data) => {
            console.log("✅ Paiement initialisé", { redirectUrl, data });
            // Le widget ouvre automatiquement la page de paiement
            // et effectue le polling du statut
        },
        onError: (message, data) => {
            console.error("❌ Erreur de paiement", { message, data });
            alert("Erreur: " + message);
        },
        onOpen: (data) => console.log("🔄 Widget ouvert", data),
        onClose: () => console.log("❌ Widget fermé"),
    },

    // Personnalisation du thème (optionnel)
    theme: {
        primary: "#1D603D",
        primaryDark: "#0B482F",
        accent: "#E09518",
        radius: "16px",
        fontFamily: "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
        maxWidth: "550px",
    },

    // Traductions personnalisées (optionnel)
    translations: {
        title: "Paiement sécurisé",
        close: "Fermer",
        back: "Retour",
        stepContract: "Contrat",
        stepMethod: "Paiement",
        stepSummary: "Résumé",
        chooseMethod: "Choisissez un moyen de paiement",
        confirmAndPay: "Confirmer et payer",
        processing: "Initialisation du paiement en cours...",
        success: "Paiement initialisé",
        error: "Échec du paiement",
        retry: "Réessayer",
        networkError: "Erreur réseau",
        requiredField: "Ce champ est requis",
        contractIdLabel: "Identifiant du contrat",
        contractIdPlaceholder: "Ex: 345678",
        verify: "Vérifier le contrat",
        verifying: "Vérification en cours...",
        loadingContract: "Chargement des informations du contrat...",
        // Traductions pour le polling du statut
        polling: "Vérification du statut du paiement en cours...",
        pollingPending: "Paiement en attente...",
        pollingSuccess: "Paiement réussi !",
        pollingError: "Le paiement a échoué",
        pollingTimeout: "Délai d'attente dépassé",
        pollingRestart: "Vérifier à nouveau",
    },
});</pre>

                        <p style="margin: 0 0 8px;"><strong>3. Moyens de paiement supportés :</strong></p>
                        <div style="background: #f0f7f3; border-left: 4px solid #1D603D; padding: 12px 16px; border-radius: 8px; margin: 8px 0 12px; font-size: 13px; color: #374151; line-height: 1.6;">
                            <p style="margin: 0 0 8px;"><strong>💳 Wave :</strong> <code>paymentMethod: "wave"</code> → API Wave</p>
                            <p style="margin: 0 0 8px;"><strong>📱 Mobile Money (Jeko) :</strong></p>
                            <ul style="margin: 0 0 8px; padding-left: 20px;">
                                <li><code>paymentMethod: "orange"</code> → Orange Money</li>
                                <li><code>paymentMethod: "moov"</code> → Moov Money</li>
                                <li><code>paymentMethod: "mtn"</code> → MTN Mobile Money</li>
                                <li><code>paymentMethod: "djamo"</code> → Djamo</li>
                            </ul>
                            <p style="margin: 0;"><strong>💳 Cartes (Jeko) :</strong></p>
                            <ul style="margin: 0; padding-left: 20px;">
                                <li><code>paymentMethod: "visa"</code> → Visa</li>
                                <li><code>paymentMethod: "mastercard"</code> → Mastercard</li>
                            </ul>
                        </div>

                        <p style="margin: 0 0 8px;"><strong>3. Polling automatique du statut :</strong></p>
                        <div style="background: #f0f7f3; border-left: 4px solid #1D603D; padding: 12px 16px; border-radius: 8px; margin: 8px 0 12px; font-size: 13px; color: #374151; line-height: 1.6;">
                            <p style="margin: 0 0 8px;"><strong>🔄 Le widget effectue automatiquement le polling du statut du paiement :</strong></p>
                            <ul style="margin: 0 0 8px; padding-left: 20px;">
                                <li>Après l'initialisation, le widget ouvre la page Jeko dans un nouvel onglet</li>
                                <li>Le widget vérifie automatiquement le statut toutes les 3 secondes (configurable)</li>
                                <li>Statuts possibles : <code>pending</code>, <code>success</code>, <code>error</code></li>
                                <li>Si <code>pending</code> : le widget continue le polling avec un spinner</li>
                                <li>Si <code>success</code> : le webhook met à jour le statut en base, le widget affiche "Paiement réussi !"</li>
                                <li>Si <code>error</code> : le widget met à jour le statut en base et affiche une erreur</li>
                                <li>Après 20 tentatives (configurable) : bouton "Vérifier à nouveau" pour relancer le polling</li>
                            </ul>
                        </div>

                        <p style="margin: 0 0 8px;"><strong>4. Vérifier manuellement le statut (optionnel) :</strong></p>
                        <pre style="background: #0c1f15; color: #d7ecdf; padding: 8px 12px; border-radius: 6px; font-size: 12px; margin: 4px 0 12px; overflow-x: auto;">
// Interroger le statut d'un paiement via l'API
// Endpoint: GET /api/paiements/statut/{referenceInterne}
async function checkPaymentStatus(referenceInterne) {
    const response = await fetch(`{{ BASE_URL }}/api/paiements/statut/${referenceInterne}`);
    const data = await response.json();

    if (data.success) {
        console.log("Statut du paiement:", data.data.statut);
        console.log("Montant:", data.data.montant);
        console.log("Détails:", data.data.details);
        return data.data;
    } else {
        console.error("Erreur:", data.message);
        return null;
    }
}

// Exemple d'utilisation
checkPaymentStatus("PAI-20261007095526-1791366926864-b1v5kukn-1782");</pre>

                        <p style="margin: 0 0 8px;"><strong>5. Générer une référence unique :</strong></p>
                        <pre style="background: #0c1f15; color: #d7ecdf; padding: 8px 12px; border-radius: 6px; font-size: 12px; margin: 4px 0 12px; overflow-x: auto;">
function generateReference() {
    const now = new Date();
    const timestamp = now.getTime();
    const random = Math.random().toString(36).substring(2, 10);
    const code = String(Math.floor(Math.random() * 9999) + 1).padStart(4, "0");
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, "0");
    const day = String(now.getDate()).padStart(2, "0");
    const hours = String(now.getHours()).padStart(2, "0");
    const minutes = String(now.getMinutes()).padStart(2, "0");
    const seconds = String(now.getSeconds()).padStart(2, "0");
    // Format: PAI-YYYYMMDDHHMMSS-TIMESTAMP-RANDOM-CODE
    return `PAI-${year}${month}${day}${hours}${minutes}${seconds}-${timestamp}-${random}-${code}`;
}</pre>

                        <p style="margin: 0 0 8px;"><strong>6. Récupérer les factures pré-sélectionnées :</strong></p>
                        <pre style="background: #0c1f15; color: #d7ecdf; padding: 8px 12px; border-radius: 6px; font-size: 12px; margin: 4px 0 12px; overflow-x: auto;">
function getPreselectedInvoices() {
    var ids = [];
    document.querySelectorAll('input[name="invoice_ids"]:checked').forEach(function(el) {
        ids.push(el.value);
    });
    return ids;
}</pre>
                    </div>
                </div>

                <!-- ============================================================
    CODE COMPLET
    ============================================================ -->
                <div class="code-block">
                    <span class="lang-tag">HTML</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copier</button>
                    <pre><code>&lt;!DOCTYPE html&gt;
&lt;html lang="fr"&gt;
&lt;head&gt;
    &lt;meta charset="UTF-8" /&gt;
    &lt;meta name="viewport" content="width=device-width, initial-scale=1" /&gt;
    &lt;meta name="csrf-token" content="{{ csrf_token() }}"&gt;
    &lt;title&gt;Jeko Payment Widget&lt;/title&gt;
&lt;/head&gt;
&lt;body&gt;
    &lt;div class="container"&gt;
        &lt;h1&gt;💳 Paiement sécurisé&lt;/h1&gt;
        &lt;p&gt;Démo des 3 types de paiement — Jeko Widget&lt;/p&gt;

        &lt;!-- ============================================ --&gt;
        &lt;!-- 1) PREMIER PAIEMENT (firstPayment) --&gt;
        &lt;!-- ============================================ --&gt;
        &lt;div class="scenario"&gt;
            &lt;span class="badge"&gt;1️⃣ Souscription&lt;/span&gt;
            &lt;h3&gt;Premier paiement&lt;/h3&gt;
            &lt;p&gt;Après validation d'un nouveau contrat. La vérification est automatique.&lt;/p&gt;
            &lt;input type="hidden" id="contractIdFirstPayment" value="1093" /&gt;
            &lt;button class="btn-pay" id="btnFirstPayment"&gt;Payer la première prime&lt;/button&gt;
        &lt;/div&gt;

        &lt;!-- ============================================ --&gt;
        &lt;!-- 2) PAIEMENT ANTICIPÉ (earlyPayment) --&gt;
        &lt;!-- ============================================ --&gt;
        &lt;div class="scenario"&gt;
            &lt;span class="badge"&gt;2️⃣ Avance&lt;/span&gt;
            &lt;h3&gt;Paiement anticipé&lt;/h3&gt;
            &lt;p&gt;Le client renseigne son identifiant de contrat. La vérification est automatique.&lt;/p&gt;
            &lt;div class="field"&gt;
                &lt;label for="contractIdEarly"&gt;Identifiant de contrat&lt;/label&gt;
                &lt;input type="text" id="contractIdEarly" placeholder="Ex: 12452" value="12452" /&gt;
            &lt;/div&gt;
            &lt;button class="btn-pay" id="btnEarlyPayment"&gt;Payer en avance&lt;/button&gt;
        &lt;/div&gt;

        &lt;!-- ============================================ --&gt;
        &lt;!-- 3) RÉGULARISATION (recoveryPrime) --&gt;
        &lt;!-- ============================================ --&gt;
        &lt;div class="scenario"&gt;
            &lt;span class="badge"&gt;3️⃣ Régularisation&lt;/span&gt;
            &lt;h3&gt;Récupération de primes impayées&lt;/h3&gt;
            &lt;p&gt;Le client sélectionne les factures en attente. Pré-sélection possible.&lt;/p&gt;
            &lt;div class="field"&gt;
                &lt;label for="contractIdRecovery"&gt;Identifiant de contrat&lt;/label&gt;
                &lt;input type="text" id="contractIdRecovery" placeholder="Ex: 12452" value="12452" /&gt;
            &lt;/div&gt;
            &lt;div style="margin: 12px 0; padding: 12px; background: #f8faf9; border-radius: 8px;"&gt;
                &lt;p style="margin: 0 0 8px; font-size: 13px; font-weight: 600;"&gt;🧪 Pré-sélection de factures :&lt;/p&gt;
                &lt;label style="display: inline-block; margin-right: 16px;"&gt;
                    &lt;input type="checkbox" name="invoice_ids" value="173669" checked /&gt; Facture #173669
                &lt;/label&gt;
                &lt;label style="display: inline-block; margin-right: 16px;"&gt;
                    &lt;input type="checkbox" name="invoice_ids" value="3073532" /&gt; Facture #3073532
                &lt;/label&gt;
                &lt;label style="display: inline-block;"&gt;
                    &lt;input type="checkbox" name="invoice_ids" value="3132514" checked /&gt; Facture #3132514
                &lt;/label&gt;
            &lt;/div&gt;
            &lt;button class="btn-pay" id="btnRecoveryPrime"&gt;Régulariser mes impayés&lt;/button&gt;
        &lt;/div&gt;
    &lt;/div&gt;

    &lt;!-- ============================================ --&gt;
    &lt;!-- CHARGEMENT DU WIDGET --&gt;
    &lt;!-- ============================================ --&gt;
    &lt;script src="{{ url('/api/paiements/jeko-payment-widget.js') }}"&gt;&lt;/script&gt;

    &lt;script&gt;
        (function() {
            "use strict";

            // ============================================================
            // VÉRIFICATION DU CHARGEMENT
            // ============================================================
            if (typeof JekoWidget === "undefined") {
                console.error("❌ JekoWidget non chargé");
                document.querySelectorAll(".btn-pay").forEach(function(btn) {
                    btn.disabled = true;
                    btn.textContent = "⚠️ Service indisponible";
                });
                return;
            }

            console.log("✅ JekoWidget chargé avec succès");

            // ============================================================
            // INITIALISATION DU WIDGET
            // ============================================================
            var widget = new JekoWidget({
                // --- Configuration des endpoints ---
                backendEndpoint: "{{ BASE_URL }}/api/paiements/init",
                contractCheckEndpoint: "{{ BASE_URL }}/api/paiements/contrat/verifier",
                statusCheckEndpoint: "{{ BASE_URL }}/api/paiements/statut",

                // --- Configuration générale ---
                currency: "XOF",
                timeout: 30000,
                autoVerifyContract: true,

                // --- Configuration du polling du statut ---
                statusPollInterval: 3000,          // Intervalle de polling en ms (défaut: 3000)
                statusPollMaxAttempts: 20,         // Nombre max de tentatives (défaut: 20)

                // --- Headers ---
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content || "",
                    "Accept": "application/json",
                },

                // --- Callbacks ---
                callbacks: {
                    onSuccess: function(redirectUrl, data) {
                        console.log("✅ Paiement initialisé", { redirectUrl, data });
                        // Le widget ouvre automatiquement la page de paiement
                        // et effectue le polling du statut
                    },
                    onError: function(message, data) {
                        console.error("❌ Erreur de paiement", { message, data });
                        alert("Erreur: " + message);
                    },
                    onOpen: function(data) {
                        console.log("🔄 Widget ouvert", data);
                    },
                    onClose: function() {
                        console.log("❌ Widget fermé");
                    },
                },

                // --- Thème personnalisé ---
                theme: {
                    primary: "#1D603D",
                    primaryDark: "#0B482F",
                    accent: "#E09518",
                    radius: "16px",
                    fontFamily: "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
                    maxWidth: "550px",
                },

                // --- Traductions ---
                translations: {
                    title: "Paiement sécurisé",
                    close: "Fermer",
                    back: "Retour",
                    stepContract: "Contrat",
                    stepMethod: "Paiement",
                    stepSummary: "Résumé",
                    chooseMethod: "Choisissez un moyen de paiement",
                    confirmAndPay: "Confirmer et payer",
                    processing: "Initialisation du paiement en cours...",
                    success: "Paiement initialisé",
                    error: "Échec du paiement",
                    retry: "Réessayer",
                    networkError: "Erreur réseau",
                    requiredField: "Ce champ est requis",
                    contractIdLabel: "Identifiant du contrat",
                    contractIdPlaceholder: "Ex: 345678",
                    verify: "Vérifier le contrat",
                    verifying: "Vérification en cours...",
                    loadingContract: "Chargement des informations du contrat...",
                    // Traductions pour le polling du statut
                    polling: "Vérification du statut du paiement en cours...",
                    pollingPending: "Paiement en attente...",
                    pollingSuccess: "Paiement réussi !",
                    pollingError: "Le paiement a échoué",
                    pollingTimeout: "Délai d'attente dépassé",
                    pollingRestart: "Vérifier à nouveau",
                },
            });

            // ============================================================
            // FONCTIONS UTILITAIRES
            // ============================================================

            /**
             * Génère une référence unique pour le paiement
             * Format: PAI-YYYYMMDDHHMMSS-TIMESTAMP-RANDOM-CODE
             * @returns {string} Référence unique
             */
            function generateReference() {
                var now = new Date();
                var timestamp = now.getTime();
                var random = Math.random().toString(36).substring(2, 10);
                var code = String(Math.floor(Math.random() * 9999) + 1).padStart(4, "0");
                var year = now.getFullYear();
                var month = String(now.getMonth() + 1).padStart(2, "0");
                var day = String(now.getDate()).padStart(2, "0");
                var hours = String(now.getHours()).padStart(2, "0");
                var minutes = String(now.getMinutes()).padStart(2, "0");
                var seconds = String(now.getSeconds()).padStart(2, "0");
                return "PAI-" + year + month + day + hours + minutes + seconds + "-" + timestamp + "-" + random + "-" + code;
            }

            /**
             * Récupère les factures pré-sélectionnées par l'utilisateur
             * @returns {string[]} Tableau des IDs de factures
             */
            function getPreselectedInvoices() {
                var ids = [];
                document.querySelectorAll('input[name="invoice_ids"]:checked').forEach(function(el) {
                    ids.push(el.value);
                });
                return ids;
            }

            /**
             * Met à jour le message d'état d'un scénario
             * @param {string} elementId - ID de l'élément de statut
             * @param {string} message - Message à afficher
             * @param {string} type - Type de message (info, success, error)
             */
            function updateStatus(elementId, message, type) {
                var el = document.getElementById(elementId);
                if (!el) return;
                el.textContent = message;
                el.className = "status-msg visible";
                if (type === "error") {
                    el.style.background = "#fef2f2";
                    el.style.color = "#991b1b";
                } else if (type === "success") {
                    el.style.background = "#f0fdf4";
                    el.style.color = "#166534";
                } else {
                    el.style.background = "#f3f4f6";
                    el.style.color = "#6b7280";
                }
            }

            // ============================================================
            // 1) PREMIER PAIEMENT (firstPayment)
            // ============================================================
            /**
             * Scénario: Premier paiement d'un contrat
             * - Utilisé lors de la souscription d'un nouveau contrat
             * - Le contractId est optionnel (peut être récupéré automatiquement)
             * - La prime principale et les frais d'adhésion sont calculés côté serveur
             */
            document.getElementById("btnFirstPayment").addEventListener("click", function() {
                var contractId = document.getElementById("contractIdFirstPayment").value;
                console.log("🔹 Premier paiement initié", { contractId: contractId });

                widget.open({
                    // Référence unique du paiement
                    reference: generateReference(),
                    
                    // Type de paiement: firstPayment, earlyPayment, recoveryPrime
                    paymentType: "firstPayment",
                    
                    // Identifiant du contrat (optionnel pour firstPayment)
                    contractId: contractId || undefined,
                    
                    // Description du paiement (affichée dans le widget)
                    description: "Souscription — première prime",
                    
                    // Email du client (pour les notifications)
                    customerEmail: "client@example.com",
                    
                    // Nom du client
                    customerName: "Jean Dupont",
                    
                    // URL de redirection en cas de succès
                    successUrl: window.location.origin + "/paiement/recu",
                    
                    // URL de redirection en cas d'erreur
                    errorUrl: window.location.origin + "/paiement/error",
                    
                    // Métadonnées additionnelles (pour le suivi)
                    metadata: {
                        source: "web_demo",
                        scenario: "firstPayment",
                        timestamp: new Date().toISOString(),
                    },
                });
            });

            // ============================================================
            // 2) PAIEMENT ANTICIPÉ (earlyPayment)
            // ============================================================
            /**
             * Scénario: Paiement anticipé des primes
             * - L'utilisateur renseigne l'identifiant du contrat
             * - Le widget vérifie automatiquement le contrat
             * - Vérifie qu'il n'y a pas d'impayés avant de permettre le paiement anticipé
             */
            document.getElementById("btnEarlyPayment").addEventListener("click", function() {
                var contractId = document.getElementById("contractIdEarly").value.trim();
                if (!contractId) {
                    alert("⚠️ Veuillez saisir un identifiant de contrat.");
                    return;
                }
                console.log("🔹 Paiement anticipé initié", { contractId: contractId });
                updateStatus("earlyStatus", "⏳ Vérification automatique du contrat en cours...", "info");

                widget.open({
                    reference: generateReference(),
                    paymentType: "earlyPayment",
                    contractId: contractId,
                    description: "Paiement anticipé de primes",
                    customerEmail: "client@example.com",
                    customerName: "Jean Dupont",
                    successUrl: window.location.origin + "/paiement/recu",
                    errorUrl: window.location.origin + "/paiement/error",
                    metadata: {
                        source: "web_demo",
                        scenario: "earlyPayment",
                        contractId: contractId,
                    },
                });
            });

            // ============================================================
            // 3) RÉGULARISATION (recoveryPrime)
            // ============================================================
            /**
             * Scénario: Régularisation de primes impayées
             * - L'utilisateur renseigne l'identifiant du contrat
             * - Le widget liste les factures en attente
             * - L'utilisateur sélectionne les factures à régulariser
             * - Possibilité de pré-sélectionner des factures
             */
            document.getElementById("btnRecoveryPrime").addEventListener("click", function() {
                var contractId = document.getElementById("contractIdRecovery").value.trim();
                var preselectedIds = getPreselectedInvoices();

                if (!contractId) {
                    alert("⚠️ Veuillez saisir un identifiant de contrat.");
                    return;
                }

                console.log("🔹 Régularisation initiée", {
                    contractId: contractId,
                    preselectedCount: preselectedIds.length,
                    preselectedIds: preselectedIds,
                });

                updateStatus(
                    "recoveryStatus",
                    "⏳ Vérification automatique du contrat avec " + preselectedIds.length + " facture(s) pré-sélectionnée(s)...",
                    "info"
                );

                widget.open({
                    reference: generateReference(),
                    paymentType: "recoveryPrime",
                    contractId: contractId,
                    preselectedInvoiceIds: preselectedIds.length > 0 ? preselectedIds : undefined,
                    description: "Régularisation de primes impayées",
                    customerEmail: "client@example.com",
                    customerName: "Jean Dupont",
                    successUrl: window.location.origin + "/paiement/recu",
                    errorUrl: window.location.origin + "/paiement/error",
                    metadata: {
                        source: "web_demo",
                        scenario: "recoveryPrime",
                        contractId: contractId,
                        preselectedCount: preselectedIds.length,
                    },
                });
            });

            console.log("✅ Widget Jeko initialisé avec succès");

        })();
    &lt;/script&gt;

&lt;/body&gt;
&lt;/html&gt;</code></pre>
                </div>

                <!-- ============================================================
    RÉCAPITULATIF DES TYPES
    ============================================================ -->
                <div class="info-box">
                    <strong>📌 Récapitulatif des 3 types :</strong>
                    <div class="separator">
                        <code>firstPayment</code> : Premier paiement d'un contrat (souscription) — <span class="highlight">contractId optionnel</span>
                    </div>
                    <div class="separator">
                        <code>earlyPayment</code> : Paiement anticipé des primes — <span class="highlight">contractId obligatoire</span>
                    </div>
                    <div class="separator">
                        <code>recoveryPrime</code> : Régularisation de primes impayées — <span class="highlight">contractId obligatoire + preselectedInvoiceIds optionnel</span>
                    </div>
                </div>

                <!-- ============================================================
    PARAMÈTRES DÉTAILLÉS
    ============================================================ -->
                <div class="info-box" style="margin-top: 12px; background: #fef9e7; border-left-color: #F7A400;">
                    <strong>🔧 Paramètres de configuration du widget :</strong>
                    <div class="separator" style="margin-top: 6px;">
                        <code>backendEndpoint</code> : URL pour initialiser le paiement <span style="color: #6b7280; font-size: 12px;">(obligatoire)</span>
                    </div>
                    <div class="separator">
                        <code>contractCheckEndpoint</code> : URL pour vérifier le contrat <span style="color: #6b7280; font-size: 12px;">(obligatoire)</span>
                    </div>
                    <div class="separator">
                        <code>currency</code> : Devise (XOF, XAF, USD, EUR) <span style="color: #6b7280; font-size: 12px;">(défaut: XOF)</span>
                    </div>
                    <div class="separator">
                        <code>timeout</code> : Timeout de la requête en ms <span style="color: #6b7280; font-size: 12px;">(défaut: 30000)</span>
                    </div>
                    <div class="separator">
                        <code>autoVerifyContract</code> : Vérification automatique du contrat <span style="color: #6b7280; font-size: 12px;">(défaut: true)</span>
                    </div>
                    <div class="separator">
                        <code>headers</code> : Headers personnalisés (CSRF, etc.) <span style="color: #6b7280; font-size: 12px;">(optionnel)</span>
                    </div>
                    <div class="separator">
                        <code>callbacks</code> : Callbacks (onSuccess, onError, onOpen, onClose) <span style="color: #6b7280; font-size: 12px;">(optionnel)</span>
                    </div>
                    <div class="separator">
                        <code>theme</code> : Personnalisation du thème <span style="color: #6b7280; font-size: 12px;">(optionnel)</span>
                    </div>
                    <div class="separator">
                        <code>translations</code> : Traductions personnalisées <span style="color: #6b7280; font-size: 12px;">(optionnel)</span>
                    </div>
                </div>
            </div>

            <!-- ============================================================
TAB 2: React - Version complète avec 3 types et documentation
============================================================ -->
            <div class="tab-content" id="tab-react">
                <p class="code-description">
                    Composant React réutilisable avec les 3 types de paiement.
                    Gestion d'état, chargement dynamique et callbacks personnalisés.
                </p>

                <!-- Documentation détaillée React -->
                <div style="background: #f8faf9; border-radius: 8px; padding: 16px 20px; margin-bottom: 16px; border-left: 4px solid #61dafb;">
                    <h4 style="margin: 0 0 8px; font-size: 14px; color: #1D603D;">⚛️ Documentation d'intégration React</h4>

                    <div style="font-size: 13px; color: #4b5563; line-height: 1.7;">
                        <p style="margin: 0 0 8px;"><strong>🎯 Support multi-providers :</strong> Le widget route automatiquement vers Wave ou Jeko selon le paymentMethod.</p>
                        
                        <p style="margin: 0 0 8px;"><strong>1. Installation du composant :</strong></p>
                        <pre style="background: #0c1f15; color: #d7ecdf; padding: 8px 12px; border-radius: 6px; font-size: 12px; margin: 4px 0 12px; overflow-x: auto;">
// Copier le composant JekoPaymentButton dans votre projet
// Fichier: components/JekoPaymentButton.tsx</pre>

                        <p style="margin: 0 0 8px;"><strong>2. Variables d'environnement (.env.local) :</strong></p>
                        <pre style="background: #0c1f15; color: #d7ecdf; padding: 8px 12px; border-radius: 6px; font-size: 12px; margin: 4px 0 12px; overflow-x: auto;">
REACT_APP_PAYMENT_API_URL={{ BASE_URL }}/api/paiements/init
REACT_APP_CONTRACT_CHECK_URL={{ BASE_URL }}/api/paiements/contrat/verifier
REACT_APP_STATUS_CHECK_URL={{ BASE_URL }}/api/paiements/statut</pre>

                        <p style="margin: 0 0 8px;"><strong>3. Utilisation du composant :</strong></p>
                        <pre style="background: #0c1f15; color: #d7ecdf; padding: 8px 12px; border-radius: 6px; font-size: 12px; margin: 4px 0 12px; overflow-x: auto;">
// 1) Premier paiement
&lt;JekoPaymentButton contractId="1093" paymentType="firstPayment" /&gt;

// 2) Paiement anticipé
&lt;JekoPaymentButton contractId="12452" paymentType="earlyPayment" /&gt;

// 3) Régularisation avec pré-sélection
&lt;JekoPaymentButton 
    contractId="12452" 
    paymentType="recoveryPrime" 
    preselectedInvoiceIds={['173669', '3132514']} 
/&gt;

// 4) Paiement Wave (spécifier paymentMethod)
&lt;JekoPaymentButton 
    contractId="12452" 
    paymentType="earlyPayment" 
    paymentMethod="wave"
/&gt;</pre>
                    </div>
                </div>

                <!-- Code React -->
                <div class="code-block">
                    <span class="lang-tag">React</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copier</button>
                    <pre><code>import React, { useState, useEffect, useRef } from "react";

// ============================================================
// TYPES
// ============================================================
type PaymentType = "firstPayment" | "earlyPayment" | "recoveryPrime";

interface JekoPaymentButtonProps {
    // Identifiant du contrat (obligatoire pour earlyPayment et recoveryPrime)
    contractId: string;
    // Type de paiement: firstPayment | earlyPayment | recoveryPrime
    paymentType?: PaymentType;
    // Email du client
    customerEmail?: string;
    // Nom du client
    customerName?: string;
    // Factures pré-sélectionnées (uniquement pour recoveryPrime)
    preselectedInvoiceIds?: string[];
    // Callback en cas de succès
    onSuccess?: (redirectUrl: string, data: any) => void;
    // Callback en cas d'erreur
    onError?: (message: string, data?: any) => void;
    // Classe CSS personnalisée
    className?: string;
    // Contenu du bouton
    children?: React.ReactNode;
}

// ============================================================
// CHARGEMENT DYNAMIQUE DU WIDGET
// ============================================================
const loadJekoWidget = (): Promise&lt;any&gt; => {
    return new Promise((resolve) => {
        if (window.JekoWidget) {
            resolve(window.JekoWidget);
            return;
        }
        const script = document.createElement("script");
        script.src = "{{ BASE_URL }}/api/paiements-payment-widget.js";
        script.onload = () => resolve(window.JekoWidget);
        script.onerror = () => resolve(null);
        document.head.appendChild(script);
    });
};

// ============================================================
// COMPOSANT PRINCIPAL
// ============================================================
const JekoPaymentButton: React.FC&lt;JekoPaymentButtonProps&gt; = ({
    contractId,
    paymentType = "firstPayment",
    customerEmail = "",
    customerName = "",
    preselectedInvoiceIds = [],
    onSuccess,
    onError,
    className = "",
    children,
}) => {
    const [isLoading, setIsLoading] = useState(false);
    const [isReady, setIsReady] = useState(false);
    const widgetRef = useRef&lt;any&gt;(null);

    useEffect(() => {
        const initWidget = async () => {
            try {
                const JekoWidget = await loadJekoWidget();
                if (!JekoWidget) {
                    console.error("❌ JekoWidget non chargé");
                    return;
                }

                widgetRef.current = new JekoWidget({
                    // Endpoint pour initialiser le paiement
                    backendEndpoint: process.env.REACT_APP_JEKO_API_URL || "{{ BASE_URL }}/api/paiements/init",
                    // Endpoint pour vérifier le contrat
                    contractCheckEndpoint: process.env.REACT_APP_JEKO_CONTRACT_CHECK_URL || "{{ BASE_URL }}/api/paiements/contrat/verifier",
                    // Devise par défaut
                    currency: "XOF",
                    // Timeout de la requête (ms)
                    timeout: 30000,
                    // Vérification automatique du contrat si contractId fourni
                    autoVerifyContract: true,
                    // Headers personnalisés
                    headers: {
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content || "",
                    },
                    // Callbacks
                    callbacks: {
                        onSuccess: (redirectUrl: string, data: any) => {
                            setIsLoading(false);
                            onSuccess?.(redirectUrl, data);
                            window.open(redirectUrl, "_blank");
                        },
                        onError: (message: string, data: any) => {
                            setIsLoading(false);
                            onError?.(message, data);
                        },
                        onOpen: () => setIsLoading(true),
                        onClose: () => setIsLoading(false),
                    },
                    // Personnalisation du thème (optionnel)
                    theme: {
                        primary: "#1D603D",
                        primaryDark: "#0B482F",
                        accent: "#E09518",
                        radius: "16px",
                    },
                    // Traductions personnalisées (optionnel)
                    translations: {
                        title: "Paiement sécurisé",
                        close: "Fermer",
                        back: "Retour",
                        stepContract: "Contrat",
                        stepMethod: "Paiement",
                        stepSummary: "Résumé",
                        chooseMethod: "Choisissez un moyen de paiement",
                        confirmAndPay: "Confirmer et payer",
                        processing: "Initialisation du paiement en cours...",
                    },
                });
                setIsReady(true);
            } catch (error) {
                console.error("❌ Erreur chargement widget Jeko:", error);
                onError?.(error instanceof Error ? error.message : "Erreur inconnue");
            }
        };
        initWidget();
    }, [onSuccess, onError]);

    /**
     * Génère une référence unique pour le paiement
     * Format: PAI-YYYYMMDDHHMMSS-TIMESTAMP-RANDOM-CODE
     */
    const generateReference = (): string => {
        const now = new Date();
        const timestamp = now.getTime();
        const random = Math.random().toString(36).substring(2, 10);
        const code = String(Math.floor(Math.random() * 9999) + 1).padStart(4, "0");
        const date = now.getFullYear() +
            String(now.getMonth() + 1).padStart(2, "0") +
            String(now.getDate()).padStart(2, "0") +
            String(now.getHours()).padStart(2, "0") +
            String(now.getMinutes()).padStart(2, "0") +
            String(now.getSeconds()).padStart(2, "0");
        return `PAI-${date}-${timestamp}-${random}-${code}`;
    };

    const handlePayment = () => {
        if (!widgetRef.current || !isReady) return;

        const reference = generateReference();

        // Construction des données selon le type de paiement
        const paymentData: any = {
            reference,
            paymentType,
            contractId,
            description: `Paiement via Jeko - ${paymentType}`,
            customerEmail,
            customerName,
            successUrl: window.location.origin + "/paiements/jeko/success",
            errorUrl: window.location.origin + "/paiements/jeko/error",
            metadata: {
                source: "react_app",
                timestamp: new Date().toISOString(),
            },
        };

        // Ajouter les factures pré-sélectionnées pour recoveryPrime
        if (paymentType === "recoveryPrime" && preselectedInvoiceIds.length > 0) {
            paymentData.preselectedInvoiceIds = preselectedInvoiceIds;
        }

        widgetRef.current.open(paymentData);
    };

    return (
        &lt;button
            onClick={handlePayment}
            disabled={!isReady || isLoading}
            className={`btn-pay ${className}`}
            style={{
                background: "#1D603D",
                color: "#fff",
                border: "none",
                padding: "13px 24px",
                borderRadius: "10px",
                fontWeight: 600,
                fontSize: "15px",
                cursor: "pointer",
                width: "100%",
                transition: "all 0.2s",
                opacity: (!isReady || isLoading) ? 0.5 : 1,
            }}
        &gt;
            {isLoading ? "Chargement..." : (children || "Payer avec Jeko")}
        &lt;/button&gt;
    );
};

export default JekoPaymentButton;

// ============================================================
// EXEMPLES D'UTILISATION DES 3 TYPES
// ============================================================

/*
// 1) PREMIER PAIEMENT (firstPayment)
&lt;JekoPaymentButton
    contractId="1093"
    paymentType="firstPayment"
    customerEmail="client@example.com"
    customerName="Jean Dupont"
    onSuccess={(url) => console.log("Succès", url)}
    onError={(msg) => console.error("Erreur", msg)}
&gt;
    Payer la première prime
&lt;/JekoPaymentButton&gt;

// 2) PAIEMENT ANTICIPÉ (earlyPayment)
&lt;JekoPaymentButton
    contractId="12452"
    paymentType="earlyPayment"
    customerEmail="client@example.com"
    customerName="Jean Dupont"
    onSuccess={(url) => console.log("Succès", url)}
    onError={(msg) => console.error("Erreur", msg)}
&gt;
    Payer en avance
&lt;/JekoPaymentButton&gt;

// 3) RÉGULARISATION (recoveryPrime) avec pré-sélection
&lt;JekoPaymentButton
    contractId="12452"
    paymentType="recoveryPrime"
    customerEmail="client@example.com"
    customerName="Jean Dupont"
    preselectedInvoiceIds={['173669', '3132514']}
    onSuccess={(url) => console.log("Succès", url)}
    onError={(msg) => console.error("Erreur", msg)}
&gt;
    Régulariser mes impayés
&lt;/JekoPaymentButton&gt;
*/</code></pre>
                </div>

                <div class="info-box">
                    <strong>⚛️ Exemples d'utilisation des 3 types :</strong>
                    <div class="separator">
                        <code>firstPayment</code> : <span class="highlight">&lt;JekoPaymentButton contractId="1093" paymentType="firstPayment" /&gt;</span>
                    </div>
                    <div class="separator">
                        <code>earlyPayment</code> : <span class="highlight">&lt;JekoPaymentButton contractId="12452" paymentType="earlyPayment" /&gt;</span>
                    </div>
                    <div class="separator">
                        <code>recoveryPrime</code> : <span class="highlight">&lt;JekoPaymentButton contractId="12452" paymentType="recoveryPrime" preselectedInvoiceIds={['173669', '3132514']} /&gt;</span>
                    </div>
                </div>

                <!-- Paramètres React -->
                <div class="info-box" style="margin-top: 12px; background: #fef9e7; border-left-color: #F7A400;">
                    <strong>🔧 Props du composant React :</strong>
                    <div class="separator" style="margin-top: 6px;">
                        <code>contractId</code> : Identifiant du contrat <span style="color: #6b7280; font-size: 12px;">(obligatoire)</span>
                    </div>
                    <div class="separator">
                        <code>paymentType</code> : Type de paiement (firstPayment, earlyPayment, recoveryPrime) <span style="color: #6b7280; font-size: 12px;">(défaut: firstPayment)</span>
                    </div>
                    <div class="separator">
                        <code>customerEmail</code> : Email du client <span style="color: #6b7280; font-size: 12px;">(optionnel)</span>
                    </div>
                    <div class="separator">
                        <code>customerName</code> : Nom du client <span style="color: #6b7280; font-size: 12px;">(optionnel)</span>
                    </div>
                    <div class="separator">
                        <code>preselectedInvoiceIds</code> : Factures pré-sélectionnées <span style="color: #6b7280; font-size: 12px;">(optionnel, uniquement pour recoveryPrime)</span>
                    </div>
                    <div class="separator">
                        <code>onSuccess</code> : Callback en cas de succès <span style="color: #6b7280; font-size: 12px;">(optionnel)</span>
                    </div>
                    <div class="separator">
                        <code>onError</code> : Callback en cas d'erreur <span style="color: #6b7280; font-size: 12px;">(optionnel)</span>
                    </div>
                    <div class="separator">
                        <code>className</code> : Classe CSS personnalisée <span style="color: #6b7280; font-size: 12px;">(optionnel)</span>
                    </div>
                    <div class="separator">
                        <code>children</code> : Contenu du bouton <span style="color: #6b7280; font-size: 12px;">(optionnel)</span>
                    </div>
                </div>
            </div>


            <!-- ============================================================
TAB 3: Vue.js - Version complète avec 3 types et documentation
============================================================ -->
            <div class="tab-content" id="tab-vue">
                <p class="code-description">
                    Composant Vue.js avec les 3 types de paiement.
                    Gestion d'état réactive, événements personnalisés et props typées.
                </p>

                <!-- Documentation détaillée Vue.js -->
                <div style="background: #f8faf9; border-radius: 8px; padding: 16px 20px; margin-bottom: 16px; border-left: 4px solid #42b883;">
                    <h4 style="margin: 0 0 8px; font-size: 14px; color: #1D603D;">🟢 Documentation d'intégration Vue.js</h4>

                    <div style="font-size: 13px; color: #4b5563; line-height: 1.7;">
                        <p style="margin: 0 0 8px;"><strong>🎯 Support multi-providers :</strong> Le widget route automatiquement vers Wave ou Jeko selon le paymentMethod.</p>
                        
                        <p style="margin: 0 0 8px;"><strong>1. Créer le composant :</strong></p>
                        <pre style="background: #0c1f15; color: #d7ecdf; padding: 8px 12px; border-radius: 6px; font-size: 12px; margin: 4px 0 12px; overflow-x: auto;">
// Fichier: components/JekoPaymentButton.vue
// Copier le code du composant ci-dessous</pre>

                        <p style="margin: 0 0 8px;"><strong>2. Variables d'environnement (.env) :</strong></p>
                        <pre style="background: #0c1f15; color: #d7ecdf; padding: 8px 12px; border-radius: 6px; font-size: 12px; margin: 4px 0 12px; overflow-x: auto;">
VUE_APP_PAYMENT_API_URL={{ BASE_URL }}/api/paiements/init
VUE_APP_CONTRACT_CHECK_URL={{ BASE_URL }}/api/paiements/contrat/verifier
VUE_APP_STATUS_CHECK_URL={{ BASE_URL }}/api/paiements/statut</pre>

                        <p style="margin: 0 0 8px;"><strong>3. Utilisation du composant :</strong></p>
                        <pre style="background: #0c1f15; color: #d7ecdf; padding: 8px 12px; border-radius: 6px; font-size: 12px; margin: 4px 0 12px; overflow-x: auto;">
// 1) Premier paiement
&lt;JekoPaymentButton contract-id="1093" payment-type="firstPayment" /&gt;

// 2) Paiement anticipé
&lt;JekoPaymentButton contract-id="12452" payment-type="earlyPayment" /&gt;

// 3) Régularisation avec pré-sélection
&lt;JekoPaymentButton 
    contract-id="12452" 
    payment-type="recoveryPrime" 
    :preselected-invoice-ids="['173669', '3132514']" 
/&gt;

// 4) Paiement Wave (spécifier paymentMethod)
&lt;JekoPaymentButton 
    contract-id="12452" 
    payment-type="earlyPayment" 
    payment-method="wave"
/&gt;</pre>

// 3) Régularisation avec pré-sélection
&lt;JekoPaymentButton 
    contract-id="12452" 
    payment-type="recoveryPrime" 
    :preselected-invoice-ids="['173669', '3132514']" 
/&gt;</pre>
                    </div>
                </div>

                <!-- Code Vue.js -->
                <div class="code-block">
                    <span class="lang-tag">Vue.js</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copier</button>
                    <pre><code>&lt;template&gt;
    &lt;div&gt;
        &lt;!-- 1) PREMIER PAIEMENT --&gt;
        &lt;JekoPaymentButton
            contract-id="1093"
            payment-type="firstPayment"
            customer-email="client@example.com"
            customer-name="Jean Dupont"
            label="Payer la première prime"
            @success="handleSuccess"
            @error="handleError"
        /&gt;

        &lt;!-- 2) PAIEMENT ANTICIPÉ --&gt;
        &lt;JekoPaymentButton
            :contract-id="earlyContractId"
            payment-type="earlyPayment"
            customer-email="client@example.com"
            customer-name="Jean Dupont"
            label="Payer en avance"
            @success="handleSuccess"
            @error="handleError"
        /&gt;

        &lt;!-- 3) RÉGULARISATION avec pré-sélection --&gt;
        &lt;JekoPaymentButton
            :contract-id="recoveryContractId"
            payment-type="recoveryPrime"
            :preselected-invoice-ids="['173669', '3132514']"
            customer-email="client@example.com"
            customer-name="Jean Dupont"
            label="Régulariser mes impayés"
            @success="handleSuccess"
            @error="handleError"
        /&gt;
    &lt;/div&gt;
&lt;/template&gt;

&lt;script&gt;
import JekoPaymentButton from "./components/JekoPaymentButton.vue";

export default {
    name: "PaymentPage",
    components: { JekoPaymentButton },
    data() {
        return {
            earlyContractId: "12452",
            recoveryContractId: "12452",
        };
    },
    methods: {
        handleSuccess(redirectUrl, data) {
            console.log("✅ Paiement réussi", { redirectUrl, data });
        },
        handleError(message, data) {
            console.error("❌ Erreur de paiement", { message, data });
        },
    },
};
&lt;/script&gt;

&lt;!-- ============================================================ --&gt;
&lt;!-- COMPOSANT JekoPaymentButton.vue --&gt;
&lt;!-- ============================================================ --&gt;

&lt;template&gt;
    &lt;button
        @click="handlePayment"
        :disabled="!isReady || isLoading"
        class="btn-pay"
    &gt;
        {{ isLoading ? "Chargement..." : label }}
    &lt;/button&gt;
&lt;/template&gt;

&lt;script&gt;
export default {
    name: "JekoPaymentButton",
    props: {
        // Identifiant du contrat (obligatoire)
        contractId: {
            type: String,
            required: true,
        },
        // Type de paiement: firstPayment | earlyPayment | recoveryPrime
        paymentType: {
            type: String,
            default: "firstPayment",
            validator: function(value) {
                return ["firstPayment", "earlyPayment", "recoveryPrime"].includes(value);
            },
        },
        // Email du client
        customerEmail: {
            type: String,
            default: "",
        },
        // Nom du client
        customerName: {
            type: String,
            default: "",
        },
        // Factures pré-sélectionnées (uniquement pour recoveryPrime)
        preselectedInvoiceIds: {
            type: Array,
            default: () => [],
        },
        // Libellé du bouton
        label: {
            type: String,
            default: "Payer avec Jeko",
        },
    },
    data() {
        return {
            widget: null,
            isReady: false,
            isLoading: false,
        };
    },
    mounted() {
        this.loadWidget();
    },
    methods: {
        /**
         * Charge le widget Jeko dynamiquement
         */
        loadWidget() {
            if (window.JekoWidget) {
                this.initWidget();
                return;
            }
            const script = document.createElement("script");
            script.src = process.env.VUE_APP_JEKO_WIDGET_URL || "{{ BASE_URL }}/api/paiements-payment-widget.js";
            script.onload = this.initWidget;
            script.onerror = () => {
                console.error("❌ Erreur chargement du widget Jeko");
                this.$emit("error", "Impossible de charger le widget");
            };
            document.head.appendChild(script);
        },

        /**
         * Initialise le widget avec la configuration
         */
        initWidget() {
            this.widget = new JekoWidget({
                // Endpoint pour initialiser le paiement
                backendEndpoint: process.env.VUE_APP_JEKO_API_URL || "{{ BASE_URL }}/api/paiements/init",
                // Endpoint pour vérifier le contrat
                contractCheckEndpoint: process.env.VUE_APP_JEKO_CONTRACT_CHECK_URL || "{{ BASE_URL }}/api/paiements/contrat/verifier",
                // Devise par défaut
                currency: "XOF",
                // Timeout de la requête (ms)
                timeout: 30000,
                // Vérification automatique du contrat si contractId fourni
                autoVerifyContract: true,
                // Headers personnalisés
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content || "",
                },
                // Callbacks
                callbacks: {
                    onSuccess: (redirectUrl, data) => {
                        this.isLoading = false;
                        this.$emit("success", redirectUrl, data);
                        window.open(redirectUrl, "_blank");
                    },
                    onError: (message, data) => {
                        this.isLoading = false;
                        this.$emit("error", message, data);
                    },
                    onOpen: () => {
                        this.isLoading = true;
                    },
                    onClose: () => {
                        this.isLoading = false;
                    },
                },
                // Personnalisation du thème (optionnel)
                theme: {
                    primary: "#1D603D",
                    primaryDark: "#0B482F",
                    accent: "#E09518",
                    radius: "16px",
                    fontFamily: "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
                    maxWidth: "550px",
                },
                // Traductions personnalisées (optionnel)
                translations: {
                    title: "Paiement sécurisé",
                    close: "Fermer",
                    back: "Retour",
                    stepContract: "Contrat",
                    stepMethod: "Paiement",
                    stepSummary: "Résumé",
                    chooseMethod: "Choisissez un moyen de paiement",
                    confirmAndPay: "Confirmer et payer",
                    processing: "Initialisation du paiement en cours...",
                    success: "Paiement initialisé",
                    error: "Échec du paiement",
                    retry: "Réessayer",
                    networkError: "Erreur réseau",
                    requiredField: "Ce champ est requis",
                    contractIdLabel: "Identifiant du contrat",
                    contractIdPlaceholder: "Ex: 345678",
                    verify: "Vérifier le contrat",
                    verifying: "Vérification en cours...",
                    loadingContract: "Chargement des informations du contrat...",
                },
            });
            this.isReady = true;
        },

        /**
         * Génère une référence unique pour le paiement
         * Format: PAI-YYYYMMDDHHMMSS-TIMESTAMP-RANDOM-CODE
         */
        generateReference() {
            const now = new Date();
            const timestamp = now.getTime();
            const random = Math.random().toString(36).substring(2, 10);
            const code = String(Math.floor(Math.random() * 9999) + 1).padStart(4, "0");
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, "0");
            const day = String(now.getDate()).padStart(2, "0");
            const hours = String(now.getHours()).padStart(2, "0");
            const minutes = String(now.getMinutes()).padStart(2, "0");
            const seconds = String(now.getSeconds()).padStart(2, "0");
            return `PAI-${year}${month}${day}${hours}${minutes}${seconds}-${timestamp}-${random}-${code}`;
        },

        /**
         * Gère le clic sur le bouton de paiement
         */
        handlePayment() {
            if (!this.widget || !this.isReady) return;

            const reference = this.generateReference();

            // Construction des données selon le type de paiement
            const paymentData = {
                reference,
                paymentType: this.paymentType,
                contractId: this.contractId,
                description: `Paiement via Jeko - ${this.paymentType}`,
                customerEmail: this.customerEmail,
                customerName: this.customerName,
                successUrl: window.location.origin + "/paiements/jeko/success",
                errorUrl: window.location.origin + "/paiements/jeko/error",
                metadata: {
                    source: "vue_app",
                    timestamp: new Date().toISOString(),
                },
            };

            // Ajouter les factures pré-sélectionnées pour recoveryPrime
            if (this.paymentType === "recoveryPrime" && this.preselectedInvoiceIds.length > 0) {
                paymentData.preselectedInvoiceIds = this.preselectedInvoiceIds;
            }

            this.widget.open(paymentData);
        },
    },
};
&lt;/script&gt;

&lt;style scoped&gt;
.btn-pay {
    background: #1D603D;
    color: #fff;
    border: none;
    padding: 13px 24px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 15px;
    cursor: pointer;
    width: 100%;
    transition: all 0.2s;
}
.btn-pay:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(29, 96, 61, 0.3);
}
.btn-pay:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
&lt;/style&gt;</code></pre>
                </div>

                <div class="info-box">
                    <strong>🟢 Exemples d'utilisation des 3 types :</strong>
                    <div class="separator"><span class="highlight">&lt;JekoPaymentButton contract-id="1093" payment-type="firstPayment" /&gt;</span></div>
                    <div class="separator"><span class="highlight">&lt;JekoPaymentButton contract-id="12452" payment-type="earlyPayment" /&gt;</span></div>
                    <div class="separator"><span class="highlight">&lt;JekoPaymentButton contract-id="12452" payment-type="recoveryPrime" :preselected-invoice-ids="['173669', '3132514']" /&gt;</span></div>
                </div>

                <!-- Paramètres Vue.js -->
                <div class="info-box" style="margin-top: 12px; background: #fef9e7; border-left-color: #F7A400;">
                    <strong>🔧 Props du composant Vue.js :</strong>
                    <div class="separator" style="margin-top: 6px;">
                        <code>contract-id</code> : Identifiant du contrat <span style="color: #6b7280; font-size: 12px;">(obligatoire)</span>
                    </div>
                    <div class="separator">
                        <code>payment-type</code> : Type de paiement (firstPayment, earlyPayment, recoveryPrime) <span style="color: #6b7280; font-size: 12px;">(défaut: firstPayment)</span>
                    </div>
                    <div class="separator">
                        <code>customer-email</code> : Email du client <span style="color: #6b7280; font-size: 12px;">(optionnel)</span>
                    </div>
                    <div class="separator">
                        <code>customer-name</code> : Nom du client <span style="color: #6b7280; font-size: 12px;">(optionnel)</span>
                    </div>
                    <div class="separator">
                        <code>preselected-invoice-ids</code> : Factures pré-sélectionnées <span style="color: #6b7280; font-size: 12px;">(optionnel, uniquement pour recoveryPrime)</span>
                    </div>
                    <div class="separator">
                        <code>label</code> : Libellé du bouton <span style="color: #6b7280; font-size: 12px;">(défaut: "Payer avec Jeko")</span>
                    </div>
                    <div class="separator">
                        <code>@success</code> : Événement en cas de succès <span style="color: #6b7280; font-size: 12px;">(optionnel)</span>
                    </div>
                    <div class="separator">
                        <code>@error</code> : Événement en cas d'erreur <span style="color: #6b7280; font-size: 12px;">(optionnel)</span>
                    </div>
                </div>
            </div>

            <!-- ============================================================
TAB 4: Angular - Version complète avec 3 types et documentation
============================================================ -->
            <div class="tab-content" id="tab-angular">
                <p class="code-description">
                    Service et composant Angular pour une intégration propre avec les 3 types de paiement.
                </p>

                <!-- Documentation détaillée Angular -->
                <div style="background: #f8faf9; border-radius: 8px; padding: 16px 20px; margin-bottom: 16px; border-left: 4px solid #c3002f;">
                    <h4 style="margin: 0 0 8px; font-size: 14px; color: #1D603D;">🅰️ Documentation d'intégration Angular</h4>

                    <div style="font-size: 13px; color: #4b5563; line-height: 1.7;">
                        <p style="margin: 0 0 8px;"><strong>🎯 Support multi-providers :</strong> Le widget route automatiquement vers Wave ou Jeko selon le paymentMethod.</p>
                        
                        <p style="margin: 0 0 8px;"><strong>1. Créer le service et le composant :</strong></p>
                        <pre style="background: #0c1f15; color: #d7ecdf; padding: 8px 12px; border-radius: 6px; font-size: 12px; margin: 4px 0 12px; overflow-x: auto;">
// Fichiers:
// - jeko-widget.service.ts
// - jeko-payment.component.ts
// Copier les codes ci-dessous</pre>

                        <p style="margin: 0 0 8px;"><strong>2. Importer le module :</strong></p>
                        <pre style="background: #0c1f15; color: #d7ecdf; padding: 8px 12px; border-radius: 6px; font-size: 12px; margin: 4px 0 12px; overflow-x: auto;">
// app.module.ts
import { JekoPaymentComponent } from './components/jeko-payment.component';

@NgModule({
    declarations: [JekoPaymentComponent],
    // ...
})</pre>

                        <p style="margin: 0 0 8px;"><strong>3. Utilisation du composant :</strong></p>
                        <pre style="background: #0c1f15; color: #d7ecdf; padding: 8px 12px; border-radius: 6px; font-size: 12px; margin: 4px 0 12px; overflow-x: auto;">
// 1) Premier paiement
&lt;jeko-payment contractId="1093" paymentType="firstPayment"&gt;&lt;/jeko-payment&gt;

// 2) Paiement anticipé
&lt;jeko-payment contractId="12452" paymentType="earlyPayment"&gt;&lt;/jeko-payment&gt;

// 3) Régularisation avec pré-sélection
&lt;jeko-payment 
    contractId="12452" 
    paymentType="recoveryPrime" 
    [preselectedInvoiceIds]="['173669', '3132514']" 
&gt;&lt;/jeko-payment&gt;

// 4) Paiement Wave (spécifier paymentMethod)
&lt;jeko-payment 
    contractId="12452" 
    paymentType="earlyPayment" 
    paymentMethod="wave"
&gt;&lt;/jeko-payment&gt;</pre>
                        <pre style="background: #0c1f15; color: #d7ecdf; padding: 8px 12px; border-radius: 6px; font-size: 12px; margin: 4px 0 12px; overflow-x: auto;">
// 1) Premier paiement
&lt;app-jeko-payment
    contractId="1093"
    paymentType="firstPayment"
    customerEmail="client@example.com"
    customerName="Jean Dupont"
    label="Payer la première prime"
    (success)="onPaymentSuccess($event)"
    (error)="onPaymentError($event)"
&gt;
&lt;/app-jeko-payment&gt;</pre>
                    </div>
                </div>

                <!-- Code Angular -->
                <div class="code-block">
                    <span class="lang-tag">Angular</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copier</button>
                    <pre>
<code>// ============================================================
// jeko-widget.service.ts
// ============================================================
import { Injectable, Inject, DOCUMENT } from "@angular/core";
import { Subject } from "rxjs";

export type PaymentType = "firstPayment" | "earlyPayment" | "recoveryPrime";

export interface JekoWidgetConfig {
    // Endpoint pour initialiser le paiement
    backendEndpoint: string;
    // Endpoint pour vérifier le contrat
    contractCheckEndpoint: string;
    // Devise par défaut
    currency: string;
    // Headers personnalisés
    headers?: Record&lt;string, string&gt;;
    // Timeout de la requête (ms)
    timeout?: number;
    // Vérification automatique du contrat
    autoVerifyContract?: boolean;
    // Personnalisation du thème
    theme?: {
        primary?: string;
        primaryDark?: string;
        accent?: string;
        radius?: string;
        fontFamily?: string;
        maxWidth?: string;
    };
    // Traductions personnalisées
    translations?: Record&lt;string, string&gt;;
}

export interface JekoPaymentData {
    // Référence unique du paiement
    reference: string;
    // Type de paiement: firstPayment | earlyPayment | recoveryPrime
    paymentType: PaymentType;
    // Identifiant du contrat
    contractId: string;
    // Description du paiement
    description?: string;
    // Email du client
    customerEmail?: string;
    // Nom du client
    customerName?: string;
    // Factures pré-sélectionnées (uniquement pour recoveryPrime)
    preselectedInvoiceIds?: string[];
    // URL de redirection en cas de succès
    successUrl?: string;
    // URL de redirection en cas d'erreur
    errorUrl?: string;
    // Métadonnées additionnelles
    metadata?: any;
}

@Injectable({ providedIn: "root" })
export class JekoWidgetService {
    private widget: any = null;
    private isReady = false;
    private successSubject = new Subject&lt;{ redirectUrl: string; data: any }&gt;();
    private errorSubject = new Subject&lt;{ message: string; data?: any }&gt;();

    constructor(@Inject(DOCUMENT) private document: Document) {}

    /**
     * Charge le widget Jeko dynamiquement
     */
    loadWidget(config: JekoWidgetConfig): Promise&lt;void&gt; {
        return new Promise((resolve) => {
            if (window["JekoWidget"]) {
                this.initWidget(config);
                resolve();
                return;
            }
            const script = this.document.createElement("script");
            script.src = "{{ BASE_URL }}/api/paiements-payment-widget.js";
            script.onload = () => {
                this.initWidget(config);
                resolve();
            };
            script.onerror = () => {
                console.error("❌ Erreur chargement du widget Jeko");
                resolve();
            };
            this.document.head.appendChild(script);
        });
    }

    /**
     * Initialise le widget avec la configuration
     */
    private initWidget(config: JekoWidgetConfig): void {
        if (!window["JekoWidget"]) return;

        this.widget = new window["JekoWidget"]({
            backendEndpoint: config.backendEndpoint,
            contractCheckEndpoint: config.contractCheckEndpoint,
            currency: config.currency || "XOF",
            timeout: config.timeout || 30000,
            autoVerifyContract: config.autoVerifyContract !== undefined ? config.autoVerifyContract : true,
            headers: config.headers || {},
            theme: config.theme || {
                primary: "#1D603D",
                primaryDark: "#0B482F",
                accent: "#E09518",
                radius: "16px",
            },
            translations: config.translations || {},
            callbacks: {
                onSuccess: (url: string, data: any) => {
                    this.successSubject.next({ redirectUrl: url, data });
                    window.open(url, "_blank");
                },
                onError: (msg: string, data: any) => {
                    this.errorSubject.next({ message: msg, data });
                },
            },
        });
        this.isReady = true;
    }

    /**
     * Ouvre le widget avec les données de paiement
     */
    openPayment(data: JekoPaymentData): void {
        if (!this.widget || !this.isReady) {
            console.error("❌ Widget Jeko non prêt");
            return;
        }
        this.widget.open(data);
    }

    /**
     * Observable des événements de succès
     */
    get onSuccess() {
        return this.successSubject.asObservable();
    }

    /**
     * Observable des événements d'erreur
     */
    get onError() {
        return this.errorSubject.asObservable();
    }

    /**
     * Ferme le widget
     */
    close(): void {
        if (this.widget) {
            this.widget.close();
        }
    }

    /**
     * Génère une référence unique pour le paiement
     * Format: PAI-YYYYMMDDHHMMSS-TIMESTAMP-RANDOM-CODE
     */
    generateReference(): string {
        const now = new Date();
        const timestamp = now.getTime();
        const random = Math.random().toString(36).substring(2, 10);
        const code = String(Math.floor(Math.random() * 9999) + 1).padStart(4, "0");
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, "0");
        const day = String(now.getDate()).padStart(2, "0");
        const hours = String(now.getHours()).padStart(2, "0");
        const minutes = String(now.getMinutes()).padStart(2, "0");
        const seconds = String(now.getSeconds()).padStart(2, "0");
        return `PAI-${year}${month}${day}${hours}${minutes}${seconds}-${timestamp}-${random}-${code}`;
    }
}

// ============================================================
// jeko-payment.component.ts
// ============================================================
import { Component, Input, OnInit, Output, EventEmitter } from "@angular/core";
import { JekoWidgetService, PaymentType } from "./jeko-widget.service";

@Component({
    selector: "app-jeko-payment",
    template: `
        &lt;button
            (click)="handlePayment()"
            [disabled]="!isReady || isLoading"
            class="btn-pay"
        &gt;
            {{ isLoading ? "Chargement..." : label }}
        &lt;/button&gt;
    `,
    styles: [`
        .btn-pay {
            background: #1D603D;
            color: #fff;
            border: none;
            padding: 13px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            width: 100%;
            transition: all 0.2s;
        }
        .btn-pay:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(29, 96, 61, 0.3);
        }
        .btn-pay:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    `]
})
export class JekoPaymentComponent implements OnInit {
    // Identifiant du contrat (obligatoire)
    @Input() contractId!: string;
    // Type de paiement: firstPayment | earlyPayment | recoveryPrime
    @Input() paymentType: PaymentType = "firstPayment";
    // Email du client
    @Input() customerEmail: string = "";
    // Nom du client
    @Input() customerName: string = "";
    // Factures pré-sélectionnées (uniquement pour recoveryPrime)
    @Input() preselectedInvoiceIds: string[] = [];
    // Libellé du bouton
    @Input() label: string = "Payer avec Jeko";

    @Output() success = new EventEmitter&lt;{ redirectUrl: string; data: any }&gt;();
    @Output() error = new EventEmitter&lt;{ message: string; data?: any }&gt;();

    isReady = false;
    isLoading = false;

    constructor(private widgetService: JekoWidgetService) {}

    ngOnInit(): void {
        // Configuration du widget
        const config: JekoWidgetConfig = {
            backendEndpoint: "{{ BASE_URL }}/api/paiements/init",
            contractCheckEndpoint: "{{ BASE_URL }}/api/paiements/contrat/verifier",
            currency: "XOF",
            timeout: 30000,
            autoVerifyContract: true,
            theme: {
                primary: "#1D603D",
                primaryDark: "#0B482F",
                accent: "#E09518",
                radius: "16px",
            },
            translations: {
                title: "Paiement sécurisé",
                close: "Fermer",
                back: "Retour",
                stepContract: "Contrat",
                stepMethod: "Paiement",
                stepSummary: "Résumé",
                chooseMethod: "Choisissez un moyen de paiement",
                confirmAndPay: "Confirmer et payer",
                processing: "Initialisation du paiement en cours...",
            },
        };

        this.widgetService.loadWidget(config).then(() => {
            this.isReady = true;
        });

        this.widgetService.onSuccess.subscribe((result) => {
            this.isLoading = false;
            this.success.emit(result);
        });

        this.widgetService.onError.subscribe((result) => {
            this.isLoading = false;
            this.error.emit(result);
        });
    }

    /**
     * Gère le clic sur le bouton de paiement
     */
    handlePayment(): void {
        if (!this.isReady) return;

        this.isLoading = true;
        const reference = this.widgetService.generateReference();

        // Construction des données selon le type de paiement
        const paymentData: any = {
            reference,
            paymentType: this.paymentType,
            contractId: this.contractId,
            description: `Paiement via Jeko - ${this.paymentType}`,
            customerEmail: this.customerEmail,
            customerName: this.customerName,
            successUrl: window.location.origin + "/paiements/jeko/success",
            errorUrl: window.location.origin + "/paiements/jeko/error",
            metadata: {
                source: "angular_app",
                timestamp: new Date().toISOString(),
            },
        };

        // Ajouter les factures pré-sélectionnées pour recoveryPrime
        if (this.paymentType === "recoveryPrime" && this.preselectedInvoiceIds.length > 0) {
            paymentData.preselectedInvoiceIds = this.preselectedInvoiceIds;
        }

        this.widgetService.openPayment(paymentData);
    }
}

// ============================================================
// EXEMPLES D'UTILISATION DES 3 TYPES
// ============================================================

/*
// 1) PREMIER PAIEMENT (firstPayment)
&lt;app-jeko-payment
    contractId="1093"
    paymentType="firstPayment"
    customerEmail="client@example.com"
    customerName="Jean Dupont"
    label="Payer la première prime"
    (success)="onPaymentSuccess($event)"
    (error)="onPaymentError($event)"
&gt;
&lt;/app-jeko-payment&gt;

// 2) PAIEMENT ANTICIPÉ (earlyPayment)
&lt;app-jeko-payment
    contractId="12452"
    paymentType="earlyPayment"
    customerEmail="client@example.com"
    customerName="Jean Dupont"
    label="Payer en avance"
    (success)="onPaymentSuccess($event)"
    (error)="onPaymentError($event)"
&gt;
&lt;/app-jeko-payment&gt;

// 3) RÉGULARISATION (recoveryPrime) avec pré-sélection
&lt;app-jeko-payment
    contractId="12452"
    paymentType="recoveryPrime"
    [preselectedInvoiceIds]="['173669', '3132514']"
    customerEmail="client@example.com"
    customerName="Jean Dupont"
    label="Régulariser mes impayés"
    (success)="onPaymentSuccess($event)"
    (error)="onPaymentError($event)"
&gt;
&lt;/app-jeko-payment&gt;
*/</code>
</pre>
                </div>

                <!-- Paramètres Angular -->
                <div class="info-box" style="margin-top: 12px; background: #fef9e7; border-left-color: #F7A400;">
                    <strong>🔧 Inputs du composant Angular :</strong>
                    <div class="separator" style="margin-top: 6px;">
                        <code>contractId</code> : Identifiant du contrat <span style="color: #6b7280; font-size: 12px;">(obligatoire)</span>
                    </div>
                    <div class="separator">
                        <code>paymentType</code> : Type de paiement (firstPayment, earlyPayment, recoveryPrime) <span style="color: #6b7280; font-size: 12px;">(défaut: firstPayment)</span>
                    </div>
                    <div class="separator">
                        <code>customerEmail</code> : Email du client <span style="color: #6b7280; font-size: 12px;">(optionnel)</span>
                    </div>
                    <div class="separator">
                        <code>customerName</code> : Nom du client <span style="color: #6b7280; font-size: 12px;">(optionnel)</span>
                    </div>
                    <div class="separator">
                        <code>preselectedInvoiceIds</code> : Factures pré-sélectionnées <span style="color: #6b7280; font-size: 12px;">(optionnel, uniquement pour recoveryPrime)</span>
                    </div>
                    <div class="separator">
                        <code>label</code> : Libellé du bouton <span style="color: #6b7280; font-size: 12px;">(défaut: "Payer avec Jeko")</span>
                    </div>
                    <div class="separator">
                        <code>success</code> : Événement en cas de succès <span style="color: #6b7280; font-size: 12px;">(optionnel)</span>
                    </div>
                    <div class="separator">
                        <code>error</code> : Événement en cas d'erreur <span style="color: #6b7280; font-size: 12px;">(optionnel)</span>
                    </div>
                </div>
            </div>

            <!-- ============================================================
TAB 5: Next.js - Version complète avec 3 types et documentation
============================================================ -->
            <div class="tab-content" id="tab-nextjs">
                <p class="code-description">
                    Composant Next.js avec App Router et "use client" pour les 3 types de paiement.
                </p>

                <!-- Documentation détaillée Next.js -->
                <div style="background: #f8faf9; border-radius: 8px; padding: 16px 20px; margin-bottom: 16px; border-left: 4px solid #000;">
                    <h4 style="margin: 0 0 8px; font-size: 14px; color: #1D603D;">▲ Documentation d'intégration Next.js</h4>

                    <div style="font-size: 13px; color: #4b5563; line-height: 1.7;">
                        <p style="margin: 0 0 8px;"><strong>🎯 Support multi-providers :</strong> Le widget route automatiquement vers Wave ou Jeko selon le paymentMethod.</p>
                        
                        <p style="margin: 0 0 8px;"><strong>1. Créer le composant :</strong></p>
                        <pre style="background: #0c1f15; color: #d7ecdf; padding: 8px 12px; border-radius: 6px; font-size: 12px; margin: 4px 0 12px; overflow-x: auto;">
// Fichier: components/JekoPaymentButton.tsx
// Copier le code du composant ci-dessous</pre>

                        <p style="margin: 0 0 8px;"><strong>2. Variables d'environnement (.env.local) :</strong></p>
                        <pre style="background: #0c1f15; color: #d7ecdf; padding: 8px 12px; border-radius: 6px; font-size: 12px; margin: 4px 0 12px; overflow-x: auto;">
NEXT_PUBLIC_PAYMENT_API_URL={{ BASE_URL }}/api/paiements/init
NEXT_PUBLIC_CONTRACT_CHECK_URL={{ BASE_URL }}/api/paiements/contrat/verifier
NEXT_PUBLIC_STATUS_CHECK_URL={{ BASE_URL }}/api/paiements/statut</pre>

                        <p style="margin: 0 0 8px;"><strong>3. Utilisation du composant :</strong></p>
                        <pre style="background: #0c1f15; color: #d7ecdf; padding: 8px 12px; border-radius: 6px; font-size: 12px; margin: 4px 0 12px; overflow-x: auto;">
// 1) Premier paiement
&lt;JekoPaymentButton contractId="1093" paymentType="firstPayment" /&gt;

// 2) Paiement anticipé
&lt;JekoPaymentButton contractId="12452" paymentType="earlyPayment" /&gt;

// 3) Régularisation avec pré-sélection
&lt;JekoPaymentButton 
    contractId="12452" 
    paymentType="recoveryPrime" 
    preselectedInvoiceIds={['173669', '3132514']} 
/&gt;

// 4) Paiement Wave (spécifier paymentMethod)
&lt;JekoPaymentButton 
    contractId="12452" 
    paymentType="earlyPayment" 
    paymentMethod="wave"
/&gt;</pre>

// 3) Régularisation avec pré-sélection
&lt;JekoPaymentButton 
    contractId="12452" 
    paymentType="recoveryPrime" 
    preselectedInvoiceIds={['173669', '3132514']} 
/&gt;</pre>
                    </div>
                </div>

                <!-- Code Next.js -->
                <div class="code-block">
                    <span class="lang-tag">Next.js</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copier</button>
                    <pre><code>'use client';

import React, { useState, useEffect, useRef } from "react";

// ============================================================
// TYPES
// ============================================================
type PaymentType = "firstPayment" | "earlyPayment" | "recoveryPrime";

interface JekoPaymentButtonProps {
    // Identifiant du contrat (obligatoire)
    contractId: string;
    // Type de paiement: firstPayment | earlyPayment | recoveryPrime
    paymentType?: PaymentType;
    // Email du client
    customerEmail?: string;
    // Nom du client
    customerName?: string;
    // Factures pré-sélectionnées (uniquement pour recoveryPrime)
    preselectedInvoiceIds?: string[];
    // Callback en cas de succès
    onSuccess?: (redirectUrl: string, data: any) => void;
    // Callback en cas d'erreur
    onError?: (message: string, data?: any) => void;
    // Classe CSS personnalisée
    className?: string;
    // Contenu du bouton
    children?: React.ReactNode;
}

declare global {
    interface Window {
        JekoWidget: any;
    }
}

// ============================================================
// CHARGEMENT DYNAMIQUE DU WIDGET
// ============================================================
const loadJekoWidget = (): Promise&lt;any&gt; => {
    return new Promise((resolve) => {
        if (typeof window === "undefined") {
            resolve(null);
            return;
        }
        if (window.JekoWidget) {
            resolve(window.JekoWidget);
            return;
        }
        const script = document.createElement("script");
        script.src = process.env.NEXT_PUBLIC_JEKO_WIDGET_URL || "{{ BASE_URL }}/api/paiements-payment-widget.js";
        script.onload = () => resolve(window.JekoWidget);
        script.onerror = () => resolve(null);
        document.head.appendChild(script);
    });
};

// ============================================================
// COMPOSANT PRINCIPAL
// ============================================================
export const JekoPaymentButton: React.FC&lt;JekoPaymentButtonProps&gt; = ({
    contractId,
    paymentType = "firstPayment",
    customerEmail = "",
    customerName = "",
    preselectedInvoiceIds = [],
    onSuccess,
    onError,
    className = "",
    children,
}) => {
    const [isLoading, setIsLoading] = useState(false);
    const [isReady, setIsReady] = useState(false);
    const widgetRef = useRef&lt;any&gt;(null);

    useEffect(() => {
        const initWidget = async () => {
            try {
                const JekoWidget = await loadJekoWidget();
                if (!JekoWidget) {
                    console.error("❌ JekoWidget non chargé");
                    return;
                }

                widgetRef.current = new JekoWidget({
                    // Endpoint pour initialiser le paiement
                    backendEndpoint: process.env.NEXT_PUBLIC_JEKO_API_URL || "{{ BASE_URL }}/api/paiements/init",
                    // Endpoint pour vérifier le contrat
                    contractCheckEndpoint: process.env.NEXT_PUBLIC_JEKO_CONTRACT_CHECK_URL || "{{ BASE_URL }}/api/paiements/contrat/verifier",
                    // Devise par défaut
                    currency: "XOF",
                    // Timeout de la requête (ms)
                    timeout: 30000,
                    // Vérification automatique du contrat si contractId fourni
                    autoVerifyContract: true,
                    // Headers personnalisés
                    headers: {
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "",
                    },
                    // Callbacks
                    callbacks: {
                        onSuccess: (redirectUrl: string, data: any) => {
                            setIsLoading(false);
                            onSuccess?.(redirectUrl, data);
                            window.open(redirectUrl, "_blank");
                        },
                        onError: (message: string, data: any) => {
                            setIsLoading(false);
                            onError?.(message, data);
                        },
                        onOpen: () => setIsLoading(true),
                        onClose: () => setIsLoading(false),
                    },
                    // Personnalisation du thème (optionnel)
                    theme: {
                        primary: "#1D603D",
                        primaryDark: "#0B482F",
                        accent: "#E09518",
                        radius: "16px",
                        fontFamily: "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
                        maxWidth: "550px",
                    },
                    // Traductions personnalisées (optionnel)
                    translations: {
                        title: "Paiement sécurisé",
                        close: "Fermer",
                        back: "Retour",
                        stepContract: "Contrat",
                        stepMethod: "Paiement",
                        stepSummary: "Résumé",
                        chooseMethod: "Choisissez un moyen de paiement",
                        confirmAndPay: "Confirmer et payer",
                        processing: "Initialisation du paiement en cours...",
                        success: "Paiement initialisé",
                        error: "Échec du paiement",
                        retry: "Réessayer",
                        networkError: "Erreur réseau",
                        requiredField: "Ce champ est requis",
                        contractIdLabel: "Identifiant du contrat",
                        contractIdPlaceholder: "Ex: 345678",
                        verify: "Vérifier le contrat",
                        verifying: "Vérification en cours...",
                        loadingContract: "Chargement des informations du contrat...",
                    },
                });
                setIsReady(true);
            } catch (error) {
                console.error("❌ Erreur chargement widget Jeko:", error);
                onError?.(error instanceof Error ? error.message : "Erreur inconnue");
            }
        };
        initWidget();
    }, [onSuccess, onError]);

    /**
     * Génère une référence unique pour le paiement
     * Format: PAI-YYYYMMDDHHMMSS-TIMESTAMP-RANDOM-CODE
     */
    const generateReference = (): string => {
        const now = new Date();
        const timestamp = now.getTime();
        const random = Math.random().toString(36).substring(2, 10);
        const code = String(Math.floor(Math.random() * 9999) + 1).padStart(4, "0");
        const date = now.getFullYear() +
            String(now.getMonth() + 1).padStart(2, "0") +
            String(now.getDate()).padStart(2, "0") +
            String(now.getHours()).padStart(2, "0") +
            String(now.getMinutes()).padStart(2, "0") +
            String(now.getSeconds()).padStart(2, "0");
        return `PAI-${date}-${timestamp}-${random}-${code}`;
    };

    const handlePayment = () => {
        if (!widgetRef.current || !isReady) return;

        const reference = generateReference();

        // Construction des données selon le type de paiement
        const paymentData: any = {
            reference,
            paymentType,
            contractId,
            description: `Paiement via Jeko - ${paymentType}`,
            customerEmail,
            customerName,
            successUrl: window.location.origin + "/paiements/jeko/success",
            errorUrl: window.location.origin + "/paiements/jeko/error",
            metadata: {
                source: "nextjs_app",
                timestamp: new Date().toISOString(),
            },
        };

        // Ajouter les factures pré-sélectionnées pour recoveryPrime
        if (paymentType === "recoveryPrime" && preselectedInvoiceIds.length > 0) {
            paymentData.preselectedInvoiceIds = preselectedInvoiceIds;
        }

        widgetRef.current.open(paymentData);
    };

    return (
        &lt;button
            onClick={handlePayment}
            disabled={!isReady || isLoading}
            className={`btn-pay ${className}`}
            style={{
                background: "#1D603D",
                color: "#fff",
                border: "none",
                padding: "13px 24px",
                borderRadius: "10px",
                fontWeight: 600,
                fontSize: "15px",
                cursor: "pointer",
                width: "100%",
                transition: "all 0.2s",
                opacity: (!isReady || isLoading) ? 0.5 : 1,
            }}
        &gt;
            {isLoading ? "Chargement..." : (children || "Payer avec Jeko")}
        &lt;/button&gt;
    );
};

export default JekoPaymentButton;

// ============================================================
// PAGE D'EXEMPLE (app/page.tsx)
// ============================================================

import { JekoPaymentButton } from "@/components/JekoPaymentButton";

export default function PaymentPage() {
    const handleSuccess = (redirectUrl: string, data: any) => {
        console.log("✅ Paiement réussi", { redirectUrl, data });
    };

    const handleError = (message: string, data?: any) => {
        console.error("❌ Erreur de paiement", { message, data });
    };

    return (
        &lt;div style={{ maxWidth: 500, margin: "40px auto", padding: "0 20px" }}&gt;
            &lt;h1&gt;💳 Paiement sécurisé&lt;/h1&gt;

            &lt;h3&gt;1️⃣ Premier paiement (firstPayment)&lt;/h3&gt;
            &lt;p style={{ color: "#6b7280", fontSize: "14px" }}&gt;
                Après validation d'un nouveau contrat. La vérification est automatique.
            &lt;/p&gt;
            &lt;JekoPaymentButton
                contractId="1093"
                paymentType="firstPayment"
                customerEmail="client@example.com"
                customerName="Jean Dupont"
                onSuccess={handleSuccess}
                onError={handleError}
            &gt;
                Payer la première prime
            &lt;/JekoPaymentButton&gt;

            &lt;h3 style={{ marginTop: "24px" }}&gt;2️⃣ Paiement anticipé (earlyPayment)&lt;/h3&gt;
            &lt;p style={{ color: "#6b7280", fontSize: "14px" }}&gt;
                Le client renseigne son identifiant de contrat. La vérification est automatique.
            &lt;/p&gt;
            &lt;JekoPaymentButton
                contractId="12452"
                paymentType="earlyPayment"
                customerEmail="client@example.com"
                customerName="Jean Dupont"
                onSuccess={handleSuccess}
                onError={handleError}
            &gt;
                Payer en avance
            &lt;/JekoPaymentButton&gt;

            &lt;h3 style={{ marginTop: "24px" }}&gt;3️⃣ Régularisation (recoveryPrime)&lt;/h3&gt;
            &lt;p style={{ color: "#6b7280", fontSize: "14px" }}&gt;
                Le client sélectionne les factures en attente. Pré-sélection possible.
            &lt;/p&gt;
            &lt;JekoPaymentButton
                contractId="12452"
                paymentType="recoveryPrime"
                preselectedInvoiceIds={['173669', '3132514']}
                customerEmail="client@example.com"
                customerName="Jean Dupont"
                onSuccess={handleSuccess}
                onError={handleError}
            &gt;
                Régulariser mes impayés
            &lt;/JekoPaymentButton&gt;
        &lt;/div&gt;
    );
}</code></pre>
                </div>

                <div class="info-box">
                    <strong>▲ Configuration Next.js :</strong>
                    <div class="separator">
                        Ajoutez ces variables dans votre <code>.env.local</code> :
                    </div>
                    <div class="separator">
                        <code>NEXT_PUBLIC_JEKO_API_URL={{ BASE_URL }}/api/paiements/init</code>
                    </div>
                    <div class="separator">
                        <code>NEXT_PUBLIC_JEKO_CONTRACT_CHECK_URL={{ BASE_URL }}/api/paiements/contrat/verifier</code>
                    </div>
                </div>

                <!-- Paramètres Next.js -->
                <div class="info-box" style="margin-top: 12px; background: #fef9e7; border-left-color: #F7A400;">
                    <strong>🔧 Props du composant Next.js :</strong>
                    <div class="separator" style="margin-top: 6px;">
                        <code>contractId</code> : Identifiant du contrat <span style="color: #6b7280; font-size: 12px;">(obligatoire)</span>
                    </div>
                    <div class="separator">
                        <code>paymentType</code> : Type de paiement (firstPayment, earlyPayment, recoveryPrime) <span style="color: #6b7280; font-size: 12px;">(défaut: firstPayment)</span>
                    </div>
                    <div class="separator">
                        <code>customerEmail</code> : Email du client <span style="color: #6b7280; font-size: 12px;">(optionnel)</span>
                    </div>
                    <div class="separator">
                        <code>customerName</code> : Nom du client <span style="color: #6b7280; font-size: 12px;">(optionnel)</span>
                    </div>
                    <div class="separator">
                        <code>preselectedInvoiceIds</code> : Factures pré-sélectionnées <span style="color: #6b7280; font-size: 12px;">(optionnel, uniquement pour recoveryPrime)</span>
                    </div>
                    <div class="separator">
                        <code>onSuccess</code> : Callback en cas de succès <span style="color: #6b7280; font-size: 12px;">(optionnel)</span>
                    </div>
                    <div class="separator">
                        <code>onError</code> : Callback en cas d'erreur <span style="color: #6b7280; font-size: 12px;">(optionnel)</span>
                    </div>
                    <div class="separator">
                        <code>className</code> : Classe CSS personnalisée <span style="color: #6b7280; font-size: 12px;">(optionnel)</span>
                    </div>
                    <div class="separator">
                        <code>children</code> : Contenu du bouton <span style="color: #6b7280; font-size: 12px;">(optionnel)</span>
                    </div>
                </div>

                <!-- Récapitulatif des 3 types pour Next.js -->
                <div class="info-box" style="margin-top: 12px;">
                    <strong>📌 Récapitulatif des 3 types de paiement :</strong>
                    <div class="separator">
                        <code>firstPayment</code> : <span class="highlight">&lt;JekoPaymentButton contractId="1093" paymentType="firstPayment" /&gt;</span>
                        <span style="color: #6b7280; font-size: 12px; display: block; margin-left: 16px;">— Premier paiement d'un contrat (souscription)</span>
                    </div>
                    <div class="separator">
                        <code>earlyPayment</code> : <span class="highlight">&lt;JekoPaymentButton contractId="12452" paymentType="earlyPayment" /&gt;</span>
                        <span style="color: #6b7280; font-size: 12px; display: block; margin-left: 16px;">— Paiement anticipé des primes</span>
                    </div>
                    <div class="separator">
                        <code>recoveryPrime</code> : <span class="highlight">&lt;JekoPaymentButton contractId="12452" paymentType="recoveryPrime" preselectedInvoiceIds={['173669', '3132514']} /&gt;</span>
                        <span style="color: #6b7280; font-size: 12px; display: block; margin-left: 16px;">— Régularisation de primes impayées avec pré-sélection</span>
                    </div>
                </div>
            </div>


            <!-- ============================================================
            NOTE DE BAS
            ============================================================ -->
            <div class="info-box" style="margin-top:24px;">
                <strong>🔒 Sécurité :</strong>
                <div class="separator">• Le widget n'appelle <strong>JAMAIS</strong> l'API Jeko directement</div>
                <div class="separator">• Toutes les clés secrètes restent côté serveur</div>
                <div class="separator">• Le montant est <strong>recalculé et vérifié côté serveur</strong> pour éviter toute fraude</div>
                <div class="separator">• API accessible via <code>{{ BASE_URL }}/api/paiements/...</code></div>
            </div>
        </div>
        @endverbatim

        <!-- ============================================================
    CHARGEMENT DU WIDGET
    ============================================================ -->
        <script src="{{ asset('payment-widget/jeko-payment-widget.js') }}"></script>

        <!-- ============================================================
    CODE PRINCIPAL
    ============================================================ -->
        <script src="{{ asset('assets/js/demo-jeko-widget.js') }}"></script>

</body>

</html>
