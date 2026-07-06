// /**
//  * Jeko Payment Widget
//  * ---------------------------------------------------------------
//  * Widget JS autonome, embarquable dans n'importe quelle appli web
//  * (site statique, Laravel, React, etc.), pour collecter les
//  * informations de paiement et déclencher l'initialisation d'un
//  * paiement Jeko.
//  *
//  * ⚠️ SÉCURITÉ : ce widget n'appelle JAMAIS directement l'API Jeko.
//  * Il envoie les données à VOTRE backend (backendEndpoint), qui lui
//  * seul connaît les clés secrètes (PARTNER_API_KEY / PARTNER_API_KEY_ID)
//  * et se charge d'appeler https://api.jeko.africa côté serveur.
//  * Ne jamais mettre ces clés dans ce fichier ou dans le HTML.
//  *
//  * Utilisation minimale :
//  * <script src="jeko-widget.js"></script>
//  * <script>
//  *   const widget = new JekoWidget({
//  *     backendEndpoint: '/api/paiements/jeko/init',
//  *     currency: 'XOF',
//  *   });
//  *
//  *   document.getElementById('payBtn').addEventListener('click', () => {
//  *     widget.open({
//  *       amountCents: 50000,
//  *       reference: 'CONTRAT-2026-001',
//  *       // paymentMethod et payerPhone sont optionnels : si absents,
//  *       // le widget les demande lui-même à l'utilisateur.
//  *     });
//  *   });
//  * </script>
//  */

/**
 * Jeko Payment Widget - Version améliorée avec icônes
 * Avec meilleure gestion d'erreurs, accessibilité et UX
 */
(function (window, document) {
  'use strict';

  const PAYMENT_METHODS = [
    { 
      id: 'wave', 
      label: 'Wave', 
      icon: '',
      iconUrl: 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSZaeFi3xAkC86Ui29AojMASpYfFMPLDzf-1hTcDVS-0Q&s=10',
      hint: 'Paiement mobile',
      color: '#1A73E8',
      disabled: false
    },
    { 
      id: 'orange', 
      label: 'Orange Money', 
      icon: '',
      iconUrl: 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRiNkcP-3jO9hJmuSHaXVo8yEzdoy-lOy8NcQgBHvbqCw&s=10',
      hint: 'Paiement mobile',
      color: '#FF7900',
      disabled: false
    },
    { 
      id: 'moov', 
      label: 'Moov Money', 
      icon: '',
      iconUrl: 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQyu5BMOD9klyZBXQ4Pq7A1e1twlAte3KLXAVNyy9fla4pOika5S9BccZc&s=10',
      hint: 'Paiement mobile',
      color: '#0055A4',
      disabled: false
    },
    { 
      id: 'mtn', 
      label: 'MTN MoMo', 
      icon: '',
      iconUrl: 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSA_m-zjaO2_OO6Ho9UOVaNUESFGH1oOg33NxQsQIN0KOqnvRzuXML9ppt6&s=10',
      hint: 'Paiement mobile',
      color: '#FFCC00',
      disabled: false
    },
    { 
      id: 'djamo', 
      label: 'Djamo', 
      icon: '',
      iconUrl: 'https://play-lh.googleusercontent.com/COFlFnBiED3WHi-J8CRd6ehKOzBjvgKGySJasSaOm1OrMZbsn0NVzk3uL4PpzGo7mF91EBaOvbsqRL9ImD_-7A',
      hint: 'Carte virtuelle ou mobile',
      color: '#FF6B00',
      disabled: false
    },
    { 
      id: 'visa', 
      label: 'Visa', 
      icon: '',
      iconUrl: 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSyKhbs0dVowkgkGdydEfQkqwZd2XrVFSPBz2fDbgU4_g&s=10',
      hint: 'Carte bancaire',
      color: '#1A1F71',
      disabled: true
    },
    { 
      id: 'mastercard', 
      label: 'Mastercard', 
      icon: '',
      iconUrl: 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRtTrzAD2ayUIWu8KkdlZZQ64sRrLLRHZbS4mrq3do4Ug&s=10',
      hint: 'Carte bancaire',
      color: '#EB001B',
      disabled: true
    },
  ];

  const PHONE_REQUIRED_METHODS = new Set(['wave', 'orange', 'moov', 'mtn', 'djamo']);

  const DEFAULT_OPTIONS = {
    backendEndpoint: '/api/paiements/jeko/init',
    currency: 'XOF',
    successUrl: null,
    errorUrl: null,
    timeout: 30000,
    headers: {},
    theme: {
      primary: '#1D603D',
      primaryDark: '#0B482F',
      accent: '#E09518',
      radius: '12px',
      fontFamily: "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
      maxWidth: '620px',
    },
    translations: {
      title: 'Paiement sécurisé',
      close: 'Fermer',
      chooseMethod: 'Choisissez un moyen de paiement',
      phoneLabel: 'Numéro de téléphone (sans indicatif)',
      phonePlaceholder: 'Ex: 07 58 81 72 35',
      submit: 'Continuer',
      processing: 'Initialisation du paiement en cours...',
      success: 'Paiement initialisé',
      successMessage: 'Vous allez être redirigé vers la page de paiement...',
      error: 'Échec du paiement',
      retry: 'Réessayer',
      networkError: 'Erreur réseau',
      requiredField: 'Ce champ est requis',
      invalidPhone: 'Numéro de téléphone invalide',
    },
    callbacks: {
      onSuccess: null,
      onError: null,
      onClose: null,
      onOpen: null,
    },
    // Mode d'affichage des icônes : 'emoji', 'image', 'both'
    iconMode: 'both',
  };

  // Injection des styles (améliorée avec support des icônes)
  function injectStyles(theme) {
    if (document.getElementById('jeko-widget-styles')) return;

    const style = document.createElement('style');
    style.id = 'jeko-widget-styles';
    style.textContent = `
      .jeko-overlay {
        --jeko-primary: ${theme.primary};
        --jeko-primary-dark: ${theme.primaryDark};
        --jeko-accent: ${theme.accent};
        --jeko-radius: ${theme.radius};
        --jeko-max-width: ${theme.maxWidth};
        font-family: ${theme.fontFamily};
        position: fixed;
        inset: 0;
        background: rgba(11, 15, 13, 0.55);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 999999;
        animation: jeko-fade-in 0.2s ease-out;
        padding: 20px;
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
      }
      
      @keyframes jeko-fade-in {
        from { opacity: 0; transform: scale(0.98); }
        to { opacity: 1; transform: scale(1); }
      }
      
      .jeko-modal {
        background: #fff;
        width: 100%;
        max-width: var(--jeko-max-width);
        border-radius: var(--jeko-radius);
        box-shadow: 0 25px 60px rgba(0,0,0,0.3);
        overflow: hidden;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        position: relative;
      }
      
      .jeko-header {
        background: linear-gradient(135deg, var(--jeko-primary), var(--jeko-primary-dark));
        color: #fff;
        padding: 20px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-shrink: 0;
      }
      
      .jeko-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
        letter-spacing: -0.3px;
      }
      
      .jeko-close {
        background: rgba(255,255,255,0.15);
        border: none;
        color: #fff;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        font-size: 18px;
        line-height: 1;
        cursor: pointer;
        transition: background 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
      }
      
      .jeko-close:hover {
        background: rgba(255,255,255,0.25);
      }
      
      .jeko-close:focus-visible {
        outline: 2px solid #fff;
        outline-offset: 2px;
      }
      
      .jeko-body {
        padding: 24px;
        overflow-y: auto;
        flex: 1;
      }
      
      .jeko-amount {
        text-align: center;
        padding: 8px 0 16px;
      }
      
      .jeko-amount .amount {
        font-size: 32px;
        font-weight: 700;
        color: var(--jeko-primary-dark);
        display: block;
      }
      
      .jeko-amount .description {
        font-size: 14px;
        color: #6b7280;
        margin-top: 4px;
        display: block;
      }
      
      .jeko-methods-label {
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        margin: 4px 0 12px;
        display: block;
      }
      
      .jeko-methods {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 10px;
        margin-bottom: 4px;
      }
      
      .jeko-method-btn {
        border: 2px solid #e5e7eb;
        background: #fafafa;
        border-radius: 10px;
        padding: 12px 14px;
        text-align: center;
        cursor: pointer;
        transition: all 0.15s;
        font-family: inherit;
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
      }
      
      .jeko-method-btn:hover {
        border-color: var(--jeko-primary);
        background: #f0f7f3;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      }
      
      .jeko-method-btn:focus-visible {
        outline: 2px solid var(--jeko-primary);
        outline-offset: 2px;
      }
      
      .jeko-method-btn[aria-pressed="true"] {
        border-color: var(--jeko-primary);
        background: #e8f5ee;
        box-shadow: 0 0 0 3px rgba(29, 96, 61, 0.15);
      }
      
      .jeko-method-btn .icon-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: #f3f4f6;
        margin-bottom: 4px;
        transition: background 0.15s;
        overflow: hidden;
      }
      
      .jeko-method-btn[aria-pressed="true"] .icon-wrapper {
        background: #d4e8dd;
      }
      
      .jeko-method-btn .icon-wrapper img {
        width: 32px;
        height: 32px;
        object-fit: contain;
      }
      
      .jeko-method-btn .icon-wrapper .emoji {
        font-size: 24px;
        line-height: 1;
      }
      
      .jeko-method-btn .name {
        display: block;
        font-weight: 600;
        font-size: 13px;
        color: #111827;
      }
      
      .jeko-method-btn .hint {
        display: block;
        font-size: 11px;
        color: #6b7280;
        margin-top: 2px;
      }
      
      .jeko-field {
        margin-top: 16px;
      }
      
      .jeko-field label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 6px;
      }
      
      .jeko-field input {
        width: 100%;
        box-sizing: border-box;
        padding: 12px 14px;
        border-radius: 8px;
        border: 2px solid #e5e7eb;
        font-size: 15px;
        font-family: inherit;
        transition: border-color 0.15s;
      }
      
      .jeko-field input:focus {
        outline: none;
        border-color: var(--jeko-primary);
        box-shadow: 0 0 0 3px rgba(29, 96, 61, 0.1);
      }
      
      .jeko-field input.error {
        border-color: #dc2626;
      }
      
      .jeko-field .error-message {
        color: #dc2626;
        font-size: 12px;
        margin-top: 4px;
        display: none;
      }
      
      .jeko-field .error-message.visible {
        display: block;
      }
      
      .jeko-footer {
        padding: 16px 24px 24px;
        flex-shrink: 0;
        border-top: 1px solid #f0f0f0;
      }
      
      .jeko-submit {
        width: 100%;
        border: none;
        background: var(--jeko-accent);
        color: #1f2937;
        font-weight: 700;
        font-size: 15px;
        padding: 14px;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.15s;
        font-family: inherit;
        position: relative;
      }
      
      .jeko-submit:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(224, 149, 24, 0.3);
      }
      
      .jeko-submit:active:not(:disabled) {
        transform: translateY(0);
      }
      
      .jeko-submit:disabled {
        opacity: 0.6;
        cursor: not-allowed;
      }
      
      .jeko-submit:focus-visible {
        outline: 2px solid var(--jeko-primary);
        outline-offset: 2px;
      }
      
      .jeko-state {
        padding: 40px 20px;
        text-align: center;
        min-height: 200px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
      }
      
      .jeko-spinner {
        width: 40px;
        height: 40px;
        border: 3px solid #e5e7eb;
        border-top-color: var(--jeko-primary);
        border-radius: 50%;
        margin: 0 auto 16px;
        animation: jeko-spin 0.8s linear infinite;
      }
      
      @keyframes jeko-spin {
        to { transform: rotate(360deg); }
      }
      
      .jeko-state .icon {
        font-size: 48px;
        margin-bottom: 12px;
        display: block;
      }
      
      .jeko-state h4 {
        margin: 0 0 8px;
        font-size: 18px;
        color: #111827;
      }
      
      .jeko-state p {
        color: #6b7280;
        font-size: 14px;
        margin: 0;
        max-width: 300px;
      }
      
      .jeko-state.error .icon { color: #dc2626; }
      .jeko-state.success .icon { color: var(--jeko-primary); }
      
      .jeko-retry {
        margin-top: 16px;
        border: 2px solid var(--jeko-primary);
        background: #fff;
        color: var(--jeko-primary-dark);
        font-weight: 600;
        font-size: 13.5px;
        padding: 10px 24px;
        border-radius: 8px;
        cursor: pointer;
        font-family: inherit;
        transition: all 0.15s;
      }
      
      .jeko-retry:hover {
        background: var(--jeko-primary);
        color: #fff;
      }
      
      /* Mode sombre automatique */
      @media (prefers-color-scheme: dark) {
        .jeko-overlay {
          background: rgba(0, 0, 0, 0.75);
        }
        .jeko-modal {
          background: #1f2937;
        }
        .jeko-method-btn {
          background: #374151;
          border-color: #4b5563;
        }
        .jeko-method-btn .name {
          color: #f3f4f6;
        }
        .jeko-method-btn .hint {
          color: #9ca3af;
        }
        .jeko-method-btn:hover {
          background: #4b5563;
        }
        .jeko-method-btn[aria-pressed="true"] {
          background: #1D603D;
          border-color: #1D603D;
        }
        .jeko-method-btn .icon-wrapper {
          background: #4b5563;
        }
        .jeko-method-btn[aria-pressed="true"] .icon-wrapper {
          background: #2d7a4e;
        }
        .jeko-field label {
          color: #f3f4f6;
        }
        .jeko-field input {
          background: #374151;
          border-color: #4b5563;
          color: #f3f4f6;
        }
        .jeko-field input:focus {
          border-color: var(--jeko-primary);
        }
        .jeko-amount .amount {
          color: #f3f4f6;
        }
        .jeko-amount .description {
          color: #9ca3af;
        }
        .jeko-methods-label {
          color: #f3f4f6;
        }
        .jeko-state h4 {
          color: #f3f4f6;
        }
        .jeko-state p {
          color: #9ca3af;
        }
        .jeko-footer {
          border-top-color: #374151;
        }
      }
      
      @media (max-width: 480px) {
        .jeko-body { padding: 16px; }
        .jeko-footer { padding: 12px 16px 16px; }
        .jeko-methods { grid-template-columns: 1fr 1fr; }
        .jeko-amount .amount { font-size: 26px; }
        .jeko-method-btn .icon-wrapper {
          width: 40px;
          height: 40px;
        }
        .jeko-method-btn .icon-wrapper img {
          width: 28px;
          height: 28px;
        }
        .jeko-method-btn .icon-wrapper .emoji {
          font-size: 20px;
        }
      }
      
      @media (max-width: 380px) {
        .jeko-methods { grid-template-columns: 1fr 1fr; }
        .jeko-method-btn { padding: 8px 10px; }
        .jeko-method-btn .name { font-size: 12px; }
        .jeko-method-btn .hint { font-size: 10px; }
      }
      
      @media (prefers-reduced-motion: reduce) {
        .jeko-overlay,
        .jeko-spinner { animation: none !important; }
        .jeko-method-btn:hover { transform: none !important; }
      }

      .cards-disabled {
            background-color: #f0f0f0;
            border: 1px solid #ccc;
            cursor: no-drop;
            pointer-events: none;
        }

        /* Remplacer le curseur par l'emoji 🚫 lors du survol des champs readonly */
        .cards-disabled:hover,
        .cards-disabled:hover,
        .cards-disabled:hover {
            cursor: no-drop;
        }
    `;
    document.head.appendChild(style);
  }

  // Fonctions utilitaires
  function formatAmount(cents, currency) {
    const amount = (cents || 0) / 100;
    try {
      return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: currency || 'XOF',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
      }).format(amount);
    } catch {
      return `${amount} ${currency || ''}`;
    }
  }

  function isValidPhone(phone) {
    const cleaned = phone.replace(/[\s\-\.\(\)]/g, '');
    return /^[\+]?[0-9]{8,15}$/.test(cleaned);
  }

  function getMethodIcon(method, mode) {
    const iconHtml = [];
    
    if (mode === 'emoji' || mode === 'both') {
      iconHtml.push(`<span class="emoji">${method.icon}</span>`);
    }
    
    if (mode === 'image' || mode === 'both') {
      if (method.iconUrl) {
        iconHtml.push(`<img src="${method.iconUrl}" alt="${method.label}" loading="lazy" style="border-radius: 50%;" />`);
      }
    }
    
    return iconHtml.join('');
  }

  class JekoWidget {
    constructor(options = {}) {
      this.options = {
        ...DEFAULT_OPTIONS,
        ...options,
        theme: { ...DEFAULT_OPTIONS.theme, ...(options.theme || {}) },
        headers: { ...DEFAULT_OPTIONS.headers, ...(options.headers || {}) },
        translations: { ...DEFAULT_OPTIONS.translations, ...(options.translations || {}) },
        callbacks: { ...DEFAULT_OPTIONS.callbacks, ...(options.callbacks || {}) },
      };
      
      // Si l'option iconMode est définie dans les options
      if (options.iconMode) {
        this.options.iconMode = options.iconMode;
      }
      
      injectStyles(this.options.theme);
      this._overlay = null;
      this._modal = null;
      this._isOpen = false;
      this._isSubmitting = false;
      this._escHandler = this._escHandler.bind(this);
      this._clickOutsideHandler = this._clickOutsideHandler.bind(this);
    }

    open(paymentData) {
      if (!paymentData?.amountCents || !paymentData?.reference) {
        throw new Error(
          "JekoWidget.open() nécessite 'amountCents' et 'reference'."
        );
      }

      if (this._isOpen) {
        this.close();
      }

      this._paymentData = {
        currency: this.options.currency,
        ...paymentData,
      };
      
      this._selectedMethod = paymentData.paymentMethod || null;
      this._payerPhone = paymentData.payerPhone || '';
      this._errors = {};

      this._render();
      this._isOpen = true;
      document.addEventListener('keydown', this._escHandler);
      
      if (this.options.callbacks.onOpen) {
        this.options.callbacks.onOpen(this._paymentData);
      }
    }

    close() {
      if (this._overlay) {
        this._overlay.remove();
        this._overlay = null;
        this._modal = null;
      }
      this._isOpen = false;
      document.removeEventListener('keydown', this._escHandler);
      
      if (this.options.callbacks.onClose) {
        this.options.callbacks.onClose();
      }
    }

    _escHandler(e) {
      if (e.key === 'Escape') this.close();
    }

    _clickOutsideHandler(e) {
      if (e.target === this._overlay) this.close();
    }

    _render() {
      if (this._overlay) this._overlay.remove();

      const overlay = document.createElement('div');
      overlay.className = 'jeko-overlay';
      overlay.setAttribute('role', 'dialog');
      overlay.setAttribute('aria-modal', 'true');
      overlay.setAttribute('aria-labelledby', 'jeko-title');
      overlay.addEventListener('click', this._clickOutsideHandler);

      const modal = document.createElement('div');
      modal.className = 'jeko-modal';
      modal.innerHTML = this._formTemplate();
      overlay.appendChild(modal);
      document.body.appendChild(overlay);
      this._overlay = overlay;
      this._modal = modal;

      this._bindEvents();
      this._togglePhoneField();
      
      // Focus sur le premier élément interactif
      const firstInput = modal.querySelector('input, button:not(.jeko-close)');
      if (firstInput) {
        setTimeout(() => firstInput.focus(), 100);
      }
    }

    _formTemplate() {
      const { amountCents, currency, description } = this._paymentData;
      const t = this.options.translations;
      const iconMode = this.options.iconMode;
      
      const methodsHtml = PAYMENT_METHODS.map(
        (m) => `
        <button type="button" class="jeko-method-btn ${m.disabled ? 'cards-disabled' : ''}" data-method="${m.id}"
          aria-pressed="${this._selectedMethod === m.id}"
          aria-label="${m.label}">
          <span class="icon-wrapper">
            ${getMethodIcon(m, iconMode)}
          </span>
          <span class="name">${m.label}</span>
          <span class="hint">${m.hint}</span>
        </button>`
      ).join('');

      return `
        <div class="jeko-header">
          <h3 id="jeko-title">${t.title}</h3>
          <button type="button" class="jeko-close" aria-label="${t.close}">&times;</button>
        </div>
        <div class="jeko-body">
          <div class="jeko-amount">
            <span class="amount">${formatAmount(amountCents, currency)}</span>
            ${description ? `<span class="description">${description}</span>` : ''}
          </div>
          <span class="jeko-methods-label">${t.chooseMethod}</span>
          <div class="jeko-methods" role="radiogroup" aria-label="Moyens de paiement">
            ${methodsHtml}
          </div>
          <div class="jeko-field" data-role="phone" style="display:none">
            <label for="jeko-phone">${t.phoneLabel}</label>
            <input id="jeko-phone" type="tel" 
                   inputmode="tel" 
                   placeholder="${t.phonePlaceholder}"
                   value="${this._payerPhone}" />
            <div class="error-message">${t.invalidPhone}</div>
          </div>
        </div>
        <div class="jeko-footer">
          <button type="button" class="jeko-submit">${t.submit}</button>
        </div>
      `;
    }

    _bindEvents() {
      const modal = this._modal;
      const t = this.options.translations;

      // Fermeture
      modal.querySelector('.jeko-close').addEventListener('click', () => this.close());

      // Sélection des méthodes de paiement
      modal.querySelectorAll('.jeko-method-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
          modal.querySelectorAll('.jeko-method-btn').forEach((b) => 
            b.setAttribute('aria-pressed', 'false')
          );
          btn.setAttribute('aria-pressed', 'true');
          this._selectedMethod = btn.dataset.method;
          this._clearError('method');
          this._togglePhoneField();
        });
        
        // Support clavier
        btn.addEventListener('keydown', (e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            btn.click();
          }
        });
      });

      // Champ téléphone
      const phoneInput = modal.querySelector('#jeko-phone');
      if (phoneInput) {
        phoneInput.addEventListener('input', (e) => {
          this._payerPhone = e.target.value;
          this._clearError('phone');
        });
        
        phoneInput.addEventListener('blur', () => {
          if (this._payerPhone && !isValidPhone(this._payerPhone)) {
            this._showError('phone', t.invalidPhone);
          }
        });
        
        // Formatage automatique
        phoneInput.addEventListener('input', (e) => {
          let value = e.target.value;
          // Supprimer tout sauf les chiffres, + et espaces
          value = value.replace(/[^0-9\+]/g, '');
          e.target.value = value;
        });
      }

      // Soumission
      const submitBtn = modal.querySelector('.jeko-submit');
      submitBtn.addEventListener('click', () => this._handleSubmit());
      
      // Soumission avec Entrée
      modal.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.target.closest('.jeko-close')) {
          e.preventDefault();
          this._handleSubmit();
        }
      });
    }

    _togglePhoneField() {
      const field = this._modal?.querySelector('.jeko-field[data-role="phone"]');
      if (!field) return;
      
      const needsPhone = PHONE_REQUIRED_METHODS.has(this._selectedMethod);
      field.style.display = needsPhone ? 'block' : 'none';
      
      if (needsPhone) {
        const input = field.querySelector('#jeko-phone');
        if (input) {
          input.required = true;
          if (this._isOpen) {
            setTimeout(() => input.focus(), 300);
          }
        }
      }
    }

    _showError(field, message) {
      const t = this.options.translations;
      this._errors[field] = message || t.requiredField;
      
      if (field === 'general') {
        let el = this._modal?.querySelector('.jeko-error-text');
        if (!el) {
          // Créer l'élément d'erreur général s'il n'existe pas
          const body = this._modal?.querySelector('.jeko-body');
          if (body) {
            el = document.createElement('div');
            el.className = 'jeko-error-text';
            el.style.cssText = 'color:#dc2626;font-size:12.5px;margin-top:12px;display:none;padding:10px;background:#fef2f2;border-radius:8px;border:1px solid #fecaca;';
            body.appendChild(el);
          }
        }
        if (el) {
          el.textContent = message;
          el.style.display = 'block';
        }
        return;
      }
      
      const input = this._modal?.querySelector(`#jeko-${field}`);
      if (input) {
        input.classList.add('error');
        const errorEl = input.parentElement?.querySelector('.error-message');
        if (errorEl) {
          errorEl.textContent = message || t.requiredField;
          errorEl.classList.add('visible');
        }
      }
    }

    _clearError(field) {
      delete this._errors[field];
      
      if (field === 'general') {
        const el = this._modal?.querySelector('.jeko-error-text');
        if (el) el.style.display = 'none';
        return;
      }
      
      const input = this._modal?.querySelector(`#jeko-${field}`);
      if (input) {
        input.classList.remove('error');
        const errorEl = input.parentElement?.querySelector('.error-message');
        if (errorEl) errorEl.classList.remove('visible');
      }
    }

    _clearAllErrors() {
      this._errors = {};
      const errorEl = this._modal?.querySelector('.jeko-error-text');
      if (errorEl) errorEl.style.display = 'none';
      
      this._modal?.querySelectorAll('.jeko-field input.error').forEach((input) => {
        input.classList.remove('error');
        const errorMsg = input.parentElement?.querySelector('.error-message');
        if (errorMsg) errorMsg.classList.remove('visible');
      });
    }

    _handleSubmit() {
      this._clearAllErrors();
      const t = this.options.translations;

      // Vérification de la méthode
      if (!this._selectedMethod) {
        this._showError('general', 'Veuillez choisir un moyen de paiement.');
        // Animer le premier bouton de méthode
        const firstBtn = this._modal?.querySelector('.jeko-method-btn');
        if (firstBtn) {
          firstBtn.style.animation = 'shake 0.5s';
          setTimeout(() => firstBtn.style.animation = '', 500);
        }
        return;
      }

      // Vérification du téléphone si nécessaire
      if (PHONE_REQUIRED_METHODS.has(this._selectedMethod)) {
        const phone = this._payerPhone.trim();
        if (!phone) {
          this._showError('phone', t.requiredField);
          const phoneInput = this._modal?.querySelector('#jeko-phone');
          if (phoneInput) {
            phoneInput.focus();
            phoneInput.style.animation = 'shake 0.5s';
            setTimeout(() => phoneInput.style.animation = '', 500);
          }
          return;
        }
        if (!isValidPhone(phone)) {
          this._showError('phone', t.invalidPhone);
          const phoneInput = this._modal?.querySelector('#jeko-phone');
          if (phoneInput) {
            phoneInput.focus();
            phoneInput.style.animation = 'shake 0.5s';
            setTimeout(() => phoneInput.style.animation = '', 500);
          }
          return;
        }
        this._payerPhone = '+225' + phone;
      }

      this._submitPayment();
    }

    _renderLoadingState() {
      const t = this.options.translations;
      this._modal.innerHTML = `
        <div class="jeko-state">
          <div class="jeko-spinner"></div>
          <p>${t.processing}</p>
        </div>
      `;
    }

    _renderResultState(type, title, message, options = {}) {
      const t = this.options.translations;
      const icon = type === 'success' ? '✅' : '❌';
      
      this._modal.innerHTML = `
        <div class="jeko-state ${type}">
          <span class="icon">${icon}</span>
          <h4>${title}</h4>
          <p>${message}</p>
          ${options.showRetry ? `<button type="button" class="jeko-retry">${t.retry}</button>` : ''}
        </div>
      `;
      
      if (options.showRetry) {
        this._modal.querySelector('.jeko-retry').addEventListener('click', () => {
          this._render();
        });
      }
    }

    async _submitPayment() {
      if (this._isSubmitting) return;
      this._isSubmitting = true;

      const submitBtn = this._modal?.querySelector('.jeko-submit');
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = this.options.translations.processing;
      }

      this._renderLoadingState();

      const { amountCents, currency, reference } = this._paymentData;
      const t = this.options.translations;

      const payload = {
        amountCents,
        currency: currency || this.options.currency,
        reference,
        paymentMethod: this._selectedMethod,
        payerPhone: this._payerPhone || undefined,
        successUrl: this.options.successUrl || window.location.href,
        errorUrl: this.options.errorUrl || window.location.href,
        description: this._paymentData.description,
        customerEmail: this._paymentData.customerEmail,
        customerName: this._paymentData.customerName,
        metadata: this._paymentData.metadata,
      };

      try {
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), this.options.timeout);

        const response = await fetch(this.options.backendEndpoint, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            ...this.options.headers,
          },
          body: JSON.stringify(payload),
          signal: controller.signal,
        });

        clearTimeout(timeout);
        const data = await response.json();

        if (!response.ok || !data.success) {
          const message = data.message || t.error;
          this._renderResultState('error', t.error, message, { showRetry: true });
          
          if (this.options.callbacks.onError) {
            this.options.callbacks.onError(message, data);
          }
          return;
        }

        const redirectUrl = data.data?.redirectUrl;

        if (!redirectUrl) {
          this._renderResultState('error', t.error, 'Aucune URL de paiement reçue.', { showRetry: true });
          return;
        }

        this._renderResultState('success', t.success, t.successMessage);

        if (this.options.callbacks.onSuccess) {
          this.options.callbacks.onSuccess(redirectUrl, data);
        }

        // Redirection automatique
        setTimeout(() => {
          window.location.href = redirectUrl;
        }, 1500);

      } catch (error) {
        if (error.name === 'AbortError') {
          this._renderResultState('error', t.error, 'La requête a expiré.', { showRetry: true });
        } else {
          this._renderResultState('error', t.networkError, error.message || t.error, { showRetry: true });
        }
        
        if (this.options.callbacks.onError) {
          this.options.callbacks.onError(error.message, null);
        }
      } finally {
        this._isSubmitting = false;
      }
    }

    // Méthodes publiques supplémentaires
    isOpen() {
      return this._isOpen;
    }

    getSelectedMethod() {
      return this._selectedMethod;
    }

    getPaymentData() {
      return this._paymentData;
    }

    // Méthode pour changer le mode d'affichage des icônes dynamiquement
    setIconMode(mode) {
      if (['emoji', 'image', 'both'].includes(mode)) {
        this.options.iconMode = mode;
        if (this._isOpen) {
          this._render();
        }
      }
    }
  }

  // Ajout de l'animation shake pour les erreurs
  const shakeStyle = document.createElement('style');
  shakeStyle.textContent = `
    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      10%, 30%, 50%, 70%, 90% { transform: translateX(-6px); }
      20%, 40%, 60%, 80% { transform: translateX(6px); }
    }
  `;
  document.head.appendChild(shakeStyle);

  // Export
  window.JekoWidget = JekoWidget;
})(window, document);