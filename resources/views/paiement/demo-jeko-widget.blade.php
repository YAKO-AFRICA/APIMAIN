<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Jeko Payment Widget - Démonstration</title>
  <style>
    * { box-sizing: border-box; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      max-width: 580px;
      margin: 40px auto;
      padding: 0 20px;
      color: #1f2937;
      background: #f9fafb;
    }
    .container {
      background: #fff;
      border-radius: 16px;
      padding: 32px 24px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    }
    h1 { font-size: 24px; margin: 0 0 8px; color: #111827; }
    .subtitle { color: #6b7280; margin: 0 0 24px; font-size: 14px; }
    .scenario {
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      padding: 20px;
      margin: 16px 0;
    }
    .scenario h3 { margin: 0 0 4px; font-size: 16px; }
    .scenario p { color: #6b7280; font-size: 13.5px; margin: 4px 0 14px; }
    .scenario .badge {
      display: inline-block;
      background: #f0f7f3;
      color: #1D603D;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      margin-bottom: 8px;
    }
    .field { margin-bottom: 10px; }
    .field label { display:block; font-size:12.5px; font-weight:600; color:#374151; margin-bottom:4px; }
    .field input {
      width: 100%; box-sizing: border-box; padding: 10px 12px;
      border-radius: 8px; border: 1.5px solid #e5e7eb; font-size: 14px; font-family: inherit;
    }
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
      font-family: inherit;
      margin-top: 6px;
    }
    .btn-pay:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(29, 96, 61, 0.3); }
    .btn-pay:active { transform: translateY(0); }
    .info {
      background: #f0f7f3;
      border-left: 4px solid #1D603D;
      padding: 12px 16px;
      border-radius: 6px;
      font-size: 13px;
      color: #374151;
      margin: 20px 0 0;
    }
    .info code { background: #e5e7eb; padding: 2px 6px; border-radius: 4px; font-size: 12px; }
  </style>
</head>
<body>
  <div class="container">
    <h1>💳 Paiement sécurisé</h1>
    <p class="subtitle">Démo des 3 types de paiement — Jeko Widget</p>

    <!-- Scénario 1 : Premier paiement -->
    <div class="scenario">
      <span class="badge">1️⃣ Souscription</span>
      <h3>Premier paiement</h3>
      <p>Après validation d'un nouveau contrat. Pas de contrat existant chez Yako Africassur pour cette démo : on fournit prime/frais manuellement.</p>
      <input type="hidden" id="contractIdFirstPayment" value="1093"/>
      <button class="btn-pay" id="btnFirstPayment">Payer la première prime</button>
    </div>

    <!-- Scénario 2 : Paiement anticipé -->
    <div class="scenario">
      <span class="badge">2️⃣ Avance</span>
      <h3>Paiement anticipé</h3>
      <p>Le client renseigne son identifiant de contrat dans le widget ; celui-ci vérifie automatiquement l'absence d'impayés.</p>
      <div class="field">
        <label for="contractIdEarly">Identifiant de contrat (optionnel, pré-rempli)</label>
        <input type="text" id="contractIdEarly" placeholder="Ex: 12452" />
      </div>
      <button class="btn-pay" id="btnEarlyPayment">Payer en avance</button>
    </div>

    <!-- Scénario 3 : Régularisation -->
    <div class="scenario">
      <span class="badge">3️⃣ Régularisation</span>
      <h3>Récupération de primes impayées</h3>
      <p>Le client sélectionne dans le widget les factures en attente qu'il souhaite régler.</p>
      <div class="field">
        <label for="contractIdRecovery">Identifiant de contrat (optionnel, pré-rempli)</label>
        <input type="text" id="contractIdRecovery" placeholder="Ex: 12452" />
      </div>
      <button class="btn-pay" id="btnRecoveryPrime">Régulariser mes impayés</button>
    </div>

    <div class="info">
      💡 Le montant n'est jamais envoyé par le navigateur : il est recalculé côté serveur
      (<code>PrimePaymentOrchestrator</code>) à partir du type de paiement et du contrat.
    </div>
  </div>

  <script src="{{ url('/api/paiements/jeko/jeko-payment-widget.js') }}"></script>

  <script>
    (function () {
      const widget = new JekoWidget({
        backendEndpoint: '/api/paiements/jeko/init',
        contractCheckEndpoint: '/api/paiements/jeko/contrat/verifier',
        currency: 'XOF',
        timeout: 30000,
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
        callbacks: {
          onSuccess: (redirectUrl, data) => console.log('✅ Paiement initialisé', { redirectUrl, data }),
          onError: (message, data) => console.error('❌ Erreur de paiement', { message, data }),
          onOpen: (data) => console.log('🔄 Widget ouvert', data),
          onClose: () => console.log('❌ Widget fermé'),
        },
      });

      function newReference(prefix) {
        return prefix + '-' + Date.now() + '-' + Math.random().toString(36).substring(2, 6).toUpperCase();
      }

      // 1) Premier paiement — le contrat vient d'être validé, on connaît déjà
      //    la prime principale et les frais d'adhésion (ex: renvoyés par l'écran
      //    de souscription juste avant), donc pas besoin de contractId ici.
      document.getElementById('btnFirstPayment').addEventListener('click', () => {
        widget.open({
          reference: newReference('SOUSCRIPTION'),
          paymentType: 'firstPayment',
          contractId: document.getElementById('contractIdFirstPayment').value || undefined,
          description: 'Souscription CADENCE Education — première prime',
          customerEmail: 'client@example.com',
          customerName: 'Jean Dupont',
          successUrl: window.location.origin + '/paiements/jeko/success',
          errorUrl: window.location.origin + '/paiements/jeko/error',
          metadata: { source: 'web_demo', scenario: 'firstPayment' },
        });
      });

      // 2) Paiement anticipé — le widget demandera/vérifiera le contractId
      //    lui-même si on ne le fournit pas ici.
      document.getElementById('btnEarlyPayment').addEventListener('click', () => {
        widget.open({
          reference: newReference('AVANCE'),
          paymentType: 'earlyPayment',
          contractId: document.getElementById('contractIdEarly').value || undefined,
          description: 'Paiement anticipé de primes',
          customerEmail: 'client@example.com',
          customerName: 'Jean Dupont',
          successUrl: window.location.origin + '/paiements/jeko/success',
          errorUrl: window.location.origin + '/paiements/jeko/error',
          metadata: { source: 'web_demo', scenario: 'earlyPayment' },
        });
      });

      // 3) Régularisation d'impayés — le widget liste les factures en attente
      //    une fois le contrat vérifié, et laisse le client les sélectionner.
      document.getElementById('btnRecoveryPrime').addEventListener('click', () => {
        widget.open({
          reference: newReference('REGUL'),
          paymentType: 'recoveryPrime',
          contractId: document.getElementById('contractIdRecovery').value || undefined,
          description: 'Régularisation de primes impayées',
          customerEmail: 'client@example.com',
          customerName: 'Jean Dupont',
          successUrl: window.location.origin + '/paiements/jeko/success',
          errorUrl: window.location.origin + '/paiements/jeko/error',
          metadata: { source: 'web_demo', scenario: 'recoveryPrime' },
        });
      });
    })();
  </script>
</body>
</html>