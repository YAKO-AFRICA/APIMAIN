<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement de prime via Wave</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1DC8FF 0%, #4A90E2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            padding: 40px;
            max-width: 520px;
            width: 100%;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header {
            text-align: center;
            margin-bottom: 32px;
        }

        .home-icon {
            width: 90px;
            height: 90px;
            margin: 0 auto 20px;
            display: block;
            object-fit: contain;
            animation: wave-bounce 2s ease-in-out infinite;
        }

        @keyframes wave-bounce {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-8px);
            }
        }

        .title {
            font-size: 26px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .title-logo {
            width: 32px;
            height: 32px;
            object-fit: contain;
            vertical-align: middle;
        }

        .subtitle {
            font-size: 15px;
            color: #6b7280;
            line-height: 1.5;
        }

        .subtitle strong {
            color: #1DC8FF;
        }

        .options-title {
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 14px;
            text-align: center;
        }

        .options-grid {
            display: grid;
            gap: 12px;
            margin-bottom: 24px;
        }

        .btn {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            background: #ffffff;
            cursor: pointer;
            font-family: inherit;
            font-size: 15px;
            font-weight: 600;
            color: #1f2937;
            transition: all 0.25s ease;
            text-align: left;
            width: 100%;
        }

        .btn:hover {
            border-color: #1DC8FF;
            background: #F0F9FF;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(29, 200, 255, 0.2);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
            background: #F0F9FF;
        }

        .btn-content {
            flex: 1;
        }

        .btn-title {
            display: block;
            font-size: 15px;
            font-weight: 600;
            color: inherit;
        }

        .btn-desc {
            display: block;
            font-size: 12px;
            font-weight: 400;
            color: #6b7280;
            margin-top: 2px;
        }

        .footer {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            color: #9ca3af;
        }

        .secure-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            color: #16a34a;
            font-weight: 600;
        }

        @media (max-width: 480px) {
            .container {
                padding: 28px 20px;
            }

            .title {
                font-size: 22px;
            }

            .home-icon {
                width: 70px;
                height: 70px;
            }

            .title-logo {
                width: 26px;
                height: 26px;
            }
        }
    </style>
</head>

<body>

    <div class="container">
        <!-- Header avec logos -->
        <div class="header">
            <img src="{{ asset('assets/images/ynov-logo.jpg') }}" alt="Logo Yako" class="home-icon">

            <h1 class="title">
                Paiement de prime via
                <img src="{{ asset('assets/images/logo-wave.png') }}" alt="Wave" class="title-logo">
                Wave
            </h1>

            <p class="subtitle">
                Payez vos primes d'assurance en toute sécurité avec <strong>Wave</strong>
            </p>
        </div>

        <!-- Options de paiement -->
        <div class="options-title">Choisissez le type de paiement</div>
        <div class="options-grid">

            <button class="btn" onclick="openWave('firstPayment')">
                <span class="btn-icon">🆕</span>
                <span class="btn-content">
                    <span class="btn-title">Payer la 1ère prime</span>
                    <span class="btn-desc">Démarrage du contrat</span>
                </span>
            </button>

            <button class="btn" onclick="openWave('earlyPayment')">
                <span class="btn-icon">⚡</span>
                <span class="btn-content">
                    <span class="btn-title">Payer en avance</span>
                    <span class="btn-desc">Anticiper votre échéance</span>
                </span>
            </button>

            <button class="btn" onclick="openWave('recoveryPrime')">
                <span class="btn-icon">🔄</span>
                <span class="btn-content">
                    <span class="btn-title">Régulariser mes impayés</span>
                    <span class="btn-desc">Mettre à jour votre situation</span>
                </span>
            </button>
        </div>

        <!-- Footer -->
        <div class="footer">
            <span class="secure-badge">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" />
                    <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                </svg>
                Paiement sécurisé
            </span>
            <span>•</span>
            <span>Propulsé par YAKO AFRICA ASSURANCES VIE</span>
        </div>
    </div>

    <!-- Widget Wave -->
    <script src="{{ url('/api/wave/wave-payment-widget.js') }}"></script>
    <script>
        (function () {
            "use strict";

            const wave = new WaveWidget({
                backendEndpoint:       "/api/wave/paiements/init",
                contractCheckEndpoint: "/api/wave/paiements/contrat/verifier",
                csrfToken:             document.querySelector('meta[name="csrf-token"]').content,

                leftLogo:  "{{ asset('assets/images/ynov-logo.jpg') }}",
                rightLogo: "{{ asset('assets/images/logo-wave.png') }}",

                currency: "XOF",
            });

            const CONTEXT_TITLES = {
                firstPayment:  "Payer ma 1ère prime",
                earlyPayment:  "Payer en avance",
                recoveryPrime: "Régulariser mes impayés",
            };

            // Fonction globale utilisée par les 3 boutons
            window.openWave = function (type) {
                wave.open({
                    defaultPaymentType: type,
                    contextTitle:       CONTEXT_TITLES[type] || null,
                    customerName:       "{{ auth()->user()->name ?? '' }}",
                    customerEmail:      "{{ auth()->user()->email ?? '' }}",
                    description:        "Paiement prime assurance YAKO AFRICA ASSURANCES VIE",
                });
            };
        })();
    </script>

</body>

</html>
