<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>Paiement Wave — Contrat {{ $identifiant }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1DC8FF 0%, #4A90E2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            text-align: center;
        }

        .fallback {
            background: #fff;
            border-radius: 20px;
            padding: 40px;
            max-width: 460px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .2);
        }

        h1 { font-size: 20px; color: #111827; margin-bottom: 10px; }

        p { color: #6b7280; font-size: 14px; line-height: 1.5; margin-bottom: 20px; }

        .actions { display: flex; flex-direction: column; gap: 10px; }

        .btn {
            display: inline-block;
            padding: 14px 28px;
            background: #FFCE00;
            color: #111827;
            text-decoration: none;
            border: none;
            border-radius: 12px;
            font-weight: 800;
            font-size: 15px;
            font-family: inherit;
            cursor: pointer;
        }

        .btn-secondary { background: #fff; border: 2px solid #e5e7eb; }

        [hidden] { display: none !important; }
    </style>
</head>

<body>

    {{-- Affiché si le widget ne s'ouvre pas, ou après sa fermeture --}}
    <div class="fallback" id="fallback">
        <h1>Paiement Wave</h1>
        <p id="fallback-message">Ouverture du paiement en cours...</p>
        <div class="actions">
            <button type="button" class="btn" id="btn-rouvrir" hidden>Choisir mes factures à payer</button>
            <a href="{{ url('/wave/factures/' . $identifiant) }}" class="btn btn-secondary">Voir les factures</a>
        </div>
    </div>

    {{-- Configuration transmise au widget : @json échappe les caractères dangereux --}}
    <script id="wave-config" type="application/json">
        @json($widgetConfig)
    </script>

    {{-- Fichier statique public/payment-widget/wave-payment-widget.js.
         ?v= change à chaque mise à jour du fichier : le navigateur ne garde jamais une ancienne version en cache. --}}
    <script src="{{ asset('payment-widget/wave-payment-widget-2.js') }}?v={{ filemtime(public_path('payment-widget/wave-payment-widget-2.js')) }}"></script>
    <script>
        (function () {
            "use strict";

            var message = document.getElementById("fallback-message");
            var boutonRouvrir = document.getElementById("btn-rouvrir");

            if (typeof window.WaveWidget !== "function") {
                message.textContent = "Le module de paiement n'a pas pu être chargé. Utilisez le lien ci-dessous.";
                return;
            }

            var config = JSON.parse(document.getElementById("wave-config").textContent);

            var wave = new WaveWidget({
                backendEndpoint: "{{ url('/api/wave/paiements/init') }}",
                contractCheckEndpoint: "{{ url('/api/wave/paiements/contrat/verifier') }}",
                csrfToken: document.querySelector('meta[name="csrf-token"]').content,
                leftLogo: "{{ asset('assets/images/ynov-logo.jpg') }}",
                rightLogo: "{{ asset('assets/images/logo-wave.png') }}",
                leftLogoAlt: "Yako",
                currency: config.contract.devise || "XOF",
                callbacks: {
                    onClose: function () {
                        message.textContent = "Vous avez fermé la fenêtre de paiement.";
                        boutonRouvrir.hidden = false;
                    },
                    onError: function (msg) {
                        console.error("Paiement Wave :", msg);
                    }
                }
            });

            function ouvrir() {
                boutonRouvrir.hidden = true;
                message.textContent = "Ouverture du paiement en cours...";
                wave.open({
                    paymentType: "recoveryPrime",
                    startStep: "invoices",            // ouverture directe sur la sélection des factures
                    contract: config.contract,        // données déjà chargées : aucun appel de vérification
                    preselect: config.preselect,      // 'all' | 'first' | 'none' | [ids]
                    sequentialInvoices: config.sequential,
                    skipSummary: config.skipSummary,
                    contextTitle: "Régulariser mes impayés",
                    customerName: config.contract.souscripteur || null,
                    description: "Paiement factures impayées - Contrat " + config.contract.idProposition
                });
            }

            boutonRouvrir.addEventListener("click", ouvrir);
            ouvrir();
        })();
    </script>

</body>

</html>
