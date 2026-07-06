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
      max-width: 480px;
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
    h1 { 
      font-size: 24px; 
      margin: 0 0 8px;
      color: #111827;
    }
    .subtitle {
      color: #6b7280;
      margin: 0 0 24px;
      font-size: 14px;
    }
    .product {
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      padding: 20px;
      margin: 20px 0;
    }
    .product h3 {
      margin: 0 0 4px;
      font-size: 16px;
    }
    .product .price {
      font-size: 24px;
      font-weight: 700;
      color: #1D603D;
      margin: 8px 0;
    }
    .product .price small {
      font-size: 14px;
      font-weight: 400;
      color: #6b7280;
    }
    .product .badge {
      display: inline-block;
      background: #f0f7f3;
      color: #1D603D;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
    }
    .btn-pay {
      background: #1D603D;
      color: #fff;
      border: none;
      padding: 14px 28px;
      border-radius: 10px;
      font-weight: 600;
      font-size: 16px;
      cursor: pointer;
      width: 100%;
      transition: all 0.2s;
      font-family: inherit;
    }
    .btn-pay:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(29, 96, 61, 0.3);
    }
    .btn-pay:active {
      transform: translateY(0);
    }
    .btn-pay .icon {
      margin-right: 8px;
    }
    .features {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
      margin: 20px 0;
    }
    .feature {
      background: #f9fafb;
      padding: 12px;
      border-radius: 8px;
      text-align: center;
      font-size: 13px;
      color: #374151;
    }
    .feature .emoji {
      display: block;
      font-size: 20px;
      margin-bottom: 4px;
    }
    .info {
      background: #f0f7f3;
      border-left: 4px solid #1D603D;
      padding: 12px 16px;
      border-radius: 6px;
      font-size: 13px;
      color: #374151;
      margin: 16px 0 0;
    }
    .info code {
      background: #e5e7eb;
      padding: 2px 6px;
      border-radius: 4px;
      font-size: 12px;
    }
    @media (max-width: 480px) {
      .features { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>💳 Paiement sécurisé</h1>
    <p class="subtitle">Intégration du widget Jeko</p>

    <div class="product">
      <span class="badge">📦 Abonnement</span>
      <h3>Premium Pro</h3>
      <p style="color:#6b7280;font-size:14px;margin:4px 0 8px;">
        Accès illimité à toutes les fonctionnalités
      </p>
      <div class="price">
        5 000 <small>XOF</small>
      </div>
    </div>

    <div class="features">
      <div class="feature">
        <span class="emoji">🔒</span>
        Paiement sécurisé
      </div>
      <div class="feature">
        <span class="emoji">📱</span>
        Mobile money
      </div>
      <div class="feature">
        <span class="emoji">💳</span>
        Cartes bancaires
      </div>
      <div class="feature">
        <span class="emoji">⚡</span>
        Instantané
      </div>
    </div>

    <button class="btn-pay" id="payBtn">
      <span class="icon">🔐</span> Payer maintenant
    </button>

    <div class="info">
      💡 Utilisez <code>wave</code>, <code>orange</code>, <code>mtn</code>, 
      <code>visa</code> ou <code>mastercard</code>.
    </div>
  </div>

  <!-- Inclure le widget -->
  <script src="{{ asset('payment-widget/jeko-payment-widget.js') }}"></script>
  
  <script>
    (function() {
      // Initialisation du widget
      const widget = new JekoWidget({
        backendEndpoint: '/api/paiements/jeko/init',
        currency: 'XOF',
        timeout: 30000,
        
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
        
        translations: {
          title: 'Paiement sécurisé Jeko',
          submit: 'Continuer vers le paiement',
          success: '✅ Paiement initialisé',
          successMessage: 'Redirection vers la page de paiement...',
          error: '❌ Échec du paiement',
        },
        
        callbacks: {
          onSuccess: (redirectUrl, data) => {
            console.log('✅ Paiement initialisé avec succès', { redirectUrl, data });
          },
          onError: (message, data) => {
            console.error('❌ Erreur de paiement', { message, data });
          },
          onOpen: (data) => {
            console.log('🔄 Widget ouvert', data);
          },
          onClose: () => {
            console.log('❌ Widget fermé');
          },
        },
      });

      // Événement de clic
      document.getElementById('payBtn').addEventListener('click', () => {
        const reference = 'ABO-' + Date.now() + '-' + Math.random().toString(36).substring(2, 6).toUpperCase();
        
        widget.open({
          amountCents: 500000,
          reference: reference,
          successUrl: window.location.origin + '/paiements/jeko/success',
          errorUrl: window.location.origin + '/paiements/jeko/error', 
          description: 'Abonnement Premium Pro',
          customerEmail: 'client@example.com',
          customerName: 'Jean Dupont',
          metadata: {
            plan: 'premium',
            period: 'monthly',
            source: 'web_demo'
          }
        });
      });
    })();
  </script>
</body>
</html>