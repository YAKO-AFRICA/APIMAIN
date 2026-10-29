/**
 * Wave Payment Widget
 * ------------------------------------------------------------------
 * Parcours :
 *   1. Choix du type de paiement (sauf si defaultPaymentType fourni)
 *   2. Saisie du contrat + vérification (adaptée au type)
 *      - Si contractId + autoVerifyContract → vérification automatique
 *   3. Récapitulatif + redirection vers Wave
 *
 * ⚠️ SÉCURITÉ : le widget n'appelle JAMAIS l'API Wave directement.
 * Tout passe par le backend Laravel (/api/wave/paiements/...).
 *
 * CORRECTIF : cocher/décocher une facture ne reconstruit plus tout le
 * corps du widget (ce qui remettait le scroll en haut). Seuls le total,
 * le libellé « Tout cocher » et le bouton « Continuer » sont mis à jour.
 * ------------------------------------------------------------------
 */
(function (window, document) {
    "use strict";

    const PAYMENT_TYPES = {
        firstPayment:  "firstPayment",
        earlyPayment:  "earlyPayment",
        recoveryPrime: "recoveryPrime",
    };

    const DEFAULT_OPTIONS = {
        backendEndpoint:       "/api/wave/paiements/init",
        contractCheckEndpoint: "/api/wave/paiements/contrat/verifier",
        currency:              "XOF",
        timeout:               30000,
        csrfToken:             null,
        defaultPaymentType:    null,   // 'firstPayment' | 'earlyPayment' | 'recoveryPrime' | null
        defaultContractId:     null,   // Pré-remplit l'identifiant du contrat
        autoVerifyContract:    false,  // Lance la vérification dès l'ouverture

        // ---- En-tête ----
        leftLogo:     null,   // URL du logo gauche (Yako)
        rightLogo:    null,   // URL du logo droite (Wave)
        contextTitle: null,   // Titre contextuel affiché après "Paiement Wave -"

        headers: {},
        theme: {
            primary:     "#033f1f",
            primaryDark: "#033f1f",
            accent:      "#FFCE00",
            radius:      "16px",
            fontFamily:  "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
            maxWidth:    "550px",
        },
        translations: {
            title:                 "Paiement Wave",
            close:                 "Fermer",
            stepType:              "Type",
            stepContract:          "Contrat",
            stepSummary:           "Résumé",
            chooseType:            "Que voulez-vous payer ?",
            typeFirstPayment:      "Payer ma 1ère prime",
            typeEarlyPayment:      "Payer en avance",
            typeRecoveryPrime:     "Régulariser mes impayés",
            contractIdLabel:       "Identifiant du contrat",
            contractIdPlaceholder: "Ex: 345678",
            verify:                "Vérifier le contrat",
            verifying:             "Vérification en cours...",
            requiredField:         "Ce champ est requis",
            contractNotFound:      "Contrat introuvable.",
            contractHasUnpaid:     "Ce contrat a des primes impayées.",
            contractNoUnpaid:      "Ce contrat n'a aucune prime impayée.",
            switchToRecovery:      "Régulariser mes impayés",
            backToType:            "Choisir un autre type",
            manualAmountLabel:     "Montant à payer (FCFA)",
            manualAmountHint:      "Saisissez le montant de la 1ère prime",
            numberOfPrimesLabel:   "Nombre de primes à régler",
            selectInvoicesLabel:   "Sélectionnez les primes à régulariser",
            selectAll:             "Tout cocher",
            unselectAll:           "Tout décocher",
            totalLabel:            "Total à régler",
            contractSummary:       "Contrat n° {id}",
            subscriber:            "Souscripteur",
            product:               "Produit",
            primePrincipale:       "Prime principale",
            fraisAdhesion:         "Frais d'adhésion",
            unpaidInvoices:        "{count} facture(s) impayée(s)",
            continue:              "Continuer",
            back:                  "Retour",
            confirmAndPay:         "Confirmer et payer",
            summaryTitle:          "Vérifiez votre paiement",
            summaryContract:       "Contrat",
            summaryType:           "Type de paiement",
            summaryAmount:         "Montant à payer",
            processing:            "Initialisation du paiement...",
            success:               "Paiement initialisé",
            successMessage:        "Vous allez être redirigé vers l'application Wave...",
            error:                 "Échec du paiement",
            networkError:          "Erreur réseau",
            retry:                 "Réessayer",
            cancel:                "Annuler",
        },
        callbacks: {
            onOpen:    null,
            onClose:   null,
            onSuccess: null,
            onError:   null,
        },
    };

    // ------------------------------------------------------------------
    // Styles
    // ------------------------------------------------------------------
    function injectStyles(theme) {
        const existing = document.getElementById("wave-widget-styles");
        if (existing) existing.remove();

        const style = document.createElement("style");
        style.id = "wave-widget-styles";
        style.textContent = `
      .wave-overlay {
        --wave-primary:${theme.primary};
        --wave-primary-dark:${theme.primaryDark};
        --wave-accent:${theme.accent};
        --wave-radius:${theme.radius};
        --wave-max-width:${theme.maxWidth};
        font-family:${theme.fontFamily};
        position:fixed; inset:0;
        background:rgba(8,12,20,.65);
        display:flex; align-items:center; justify-content:center;
        z-index:999999; padding:20px;
        backdrop-filter:blur(8px);
        animation: wave-fade-in .2s ease;
      }
      @keyframes wave-fade-in { from { opacity:0 } to { opacity:1 } }
      @keyframes wave-slide-up { from { opacity:0; transform:translateY(12px) } to { opacity:1; transform:translateY(0) } }
      @keyframes wave-pop { 0% { transform:scale(.85); opacity:0 } 60% { transform:scale(1.05) } 100% { transform:scale(1); opacity:1 } }
      @keyframes wave-spin { to { transform:rotate(360deg); } }
      @keyframes wave-check-pop { 0% { transform:scale(0) rotate(-20deg); opacity:0 } 100% { transform:scale(1) rotate(0); opacity:1 } }

      * { box-sizing:border-box; }

      .wave-modal {
        background:#fff; width:100%;
        max-width:var(--wave-max-width);
        border-radius:var(--wave-radius);
        box-shadow:0 32px 80px rgba(0,0,0,.4);
        overflow:hidden; max-height:92vh;
        display:flex; flex-direction:column;
        animation: wave-slide-up .28s cubic-bezier(.16,1,.3,1);
      }

      .wave-header {
        background:linear-gradient(160deg,var(--wave-primary) 0%,var(--wave-primary-dark) 100%);
        color:#fff; padding:22px 24px 18px;
        position:relative; overflow:hidden;
      }
      .wave-header::before {
        content:"";
        position:absolute; top:-40%; right:-15%;
        width:200px; height:200px; border-radius:50%;
        background:radial-gradient(circle, rgba(255,255,255,.12) 0%, transparent 70%);
        pointer-events:none;
      }
      .wave-header-top {
        display:flex; align-items:center; justify-content:space-between;
        margin-bottom:18px; position:relative; z-index:1;
      }
      .wave-header h3 {
        margin:0; font-size:17px; font-weight:700;
        display:flex; align-items:center; gap:8px;
      }
      .wave-header h3::before { content:"🔒"; font-size:14px; opacity:.9; }
      .wave-close {
        background:rgba(255,255,255,.15); border:none; color:#fff;
        width:30px; height:30px; border-radius:50%; font-size:16px;
        cursor:pointer; display:flex; align-items:center; justify-content:center;
        transition:background .15s, transform .15s;
      }
      .wave-close:hover { background:rgba(255,255,255,.28); transform:rotate(90deg); }

      .wave-steps { display:flex; align-items:flex-start; position:relative; z-index:1; }
      .wave-step { display:flex; flex-direction:column; align-items:center; flex:1; }
      .wave-step:first-child { align-items:flex-start; }
      .wave-step:last-child { align-items:flex-end; flex:0 0 auto; }
      .wave-step-track { display:flex; align-items:center; width:100%; }
      .wave-step-circle {
        width:28px; height:28px; border-radius:50%;
        display:flex; align-items:center; justify-content:center;
        font-size:12px; font-weight:800;
        background:rgba(255,255,255,.12); color:rgba(255,255,255,.6);
        border:2px solid rgba(255,255,255,.28);
        flex-shrink:0; transition:all .25s;
      }
      .wave-step-line {
        flex:1; height:2px; background:rgba(255,255,255,.2);
        margin:0 4px; border-radius:2px; position:relative; overflow:hidden;
      }
      .wave-step-line::after {
        content:""; position:absolute; inset:0; background:var(--wave-accent);
        transform:scaleX(0); transform-origin:left; transition:transform .35s ease;
      }
      .wave-step-line.done::after { transform:scaleX(1); }
      .wave-step-label {
        font-size:10.5px; margin-top:6px; color:rgba(255,255,255,.6);
        white-space:nowrap; font-weight:600;
      }
      .wave-step.active .wave-step-circle {
        background:#fff; color:var(--wave-primary-dark); border-color:#fff;
        box-shadow:0 0 0 4px rgba(255,255,255,.18);
        animation: wave-pop .3s ease;
      }
      .wave-step.active .wave-step-label { color:#fff; font-weight:700; }
      .wave-step.done .wave-step-circle {
        background:var(--wave-accent); color:#1f2937; border-color:var(--wave-accent);
      }
      .wave-step.done .wave-step-circle .checkmark { animation: wave-check-pop .3s ease; display:inline-block; }
      .wave-step.done .wave-step-label { color:rgba(255,255,255,.85); }

      .wave-body { padding:24px; overflow-y:auto; flex:1; }
      .wave-body::-webkit-scrollbar { width:6px; }
      .wave-body::-webkit-scrollbar-thumb { background:#d1d5db; border-radius:6px; }

      .wave-type-options { display:flex; flex-direction:column; gap:10px; }
      .wave-type-btn {
        border:2px solid #e5e7eb; background:#fff; border-radius:12px;
        padding:16px; text-align:left; cursor:pointer;
        font-family:inherit; font-weight:700; font-size:14px;
        color:#111827; display:flex; align-items:center; gap:12px;
        transition:all .15s;
      }
      .wave-type-btn:hover:not(:disabled) {
        border-color:#a8e3f5; background:#f8fdff;
        transform:translateY(-2px); box-shadow:0 6px 16px rgba(29,195,243,.08);
      }
      .wave-type-btn[aria-pressed="true"] {
        border-color:var(--wave-primary); background:#e6f7fd;
        box-shadow:0 0 0 3px rgba(29,195,243,.12);
      }
      .wave-type-btn .icon { font-size:22px; }

      .wave-lookup-icon {
        width:60px; height:60px; border-radius:18px;
        background:linear-gradient(135deg,#e6f7fd,#d0effa);
        display:flex; align-items:center; justify-content:center;
        margin:0 auto 18px; font-size:26px;
      }

      .wave-field { margin-top:14px; }
      .wave-field label {
        display:block; font-size:13px; font-weight:600;
        color:#374151; margin-bottom:7px;
      }
      .wave-field input {
        width:100%; padding:13px 14px; border-radius:11px;
        border:2px solid #e5e7eb; font-size:15px; font-family:inherit;
        transition:border-color .15s, box-shadow .15s;
      }
      .wave-field input:focus {
        outline:none; border-color:var(--wave-primary);
        box-shadow:0 0 0 3px rgba(29,195,243,.15);
      }
      .wave-field input.error { border-color:#dc2626; }
      .wave-field .error-message {
        color:#dc2626; font-size:12px; margin-top:6px; display:none;
      }
      .wave-field .error-message.visible { display:block; }
      .wave-field .hint {
        font-size:11.5px; color:#9ca3af; margin-top:5px; display:block;
      }

      .wave-notice {
        font-size:12.5px; padding:12px 15px; border-radius:11px;
        margin-bottom:15px; line-height:1.5;
      }
      .wave-notice.warn  { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
      .wave-notice.info  { background:#f0fbff; color:#0B5A75; border:1px solid #d6f1fb; font-weight:600; }
      .wave-notice.success { background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; }

      .wave-link-btn {
        background:none; border:none; color:var(--wave-primary-dark);
        font-weight:700; text-decoration:underline; cursor:pointer;
        padding:0; margin-top:6px; font-family:inherit; font-size:12.5px;
      }

      .wave-contract-info {
        background:#f9fafb; border-radius:13px; padding:14px 16px;
        margin-bottom:16px; border:1px solid #f0f0f0;
      }
      .wave-contract-info .row {
        display:flex; justify-content:space-between;
        padding:5px 0; font-size:13px;
      }
      .wave-contract-info .row .key { color:#6b7280; font-weight:600; }
      .wave-contract-info .row .value { color:#111827; font-weight:700; text-align:right; }

      .wave-stepper { display:flex; align-items:center; justify-content:center; gap:16px; }
      .wave-stepper button {
        width:38px; height:38px; border-radius:11px;
        border:2px solid #e5e7eb; background:#fff;
        font-size:18px; cursor:pointer; color:var(--wave-primary-dark);
        font-weight:700; transition:all .15s;
      }
      .wave-stepper button:hover { border-color:var(--wave-primary); background:#f0fbff; }
      .wave-stepper span {
        font-size:18px; font-weight:800; min-width:28px;
        text-align:center; color:#111827;
      }

      .wave-invoice-header {
        display:flex; justify-content:space-between; align-items:center;
        margin-bottom:10px;
      }
      .wave-invoice-list {
        display:flex; flex-direction:column; gap:9px;
        max-height:220px; overflow-y:auto; padding:2px;
      }
      .wave-invoice-item {
        display:flex; align-items:center; gap:10px;
        border:2px solid #eceeed; border-radius:11px;
        padding:12px 13px; cursor:pointer; transition:all .15s;
      }
      .wave-invoice-item:hover { border-color:#a8e3f5; background:#f8fdff; }
      .wave-invoice-item:has(input:checked) {
        border-color:var(--wave-primary); background:#e6f7fd;
      }
      .wave-invoice-item .meta { flex:1; font-size:12.5px; color:#374151; }
      .wave-invoice-item .amount { font-weight:700; font-size:13px; color:#111827; }
      .wave-invoice-item input[type="checkbox"] {
        width:18px; height:18px; accent-color:var(--wave-primary); cursor:pointer;
      }

      .wave-total-bar {
        display:flex; justify-content:space-between; align-items:center;
        margin-top:16px; padding:13px 16px;
        background:linear-gradient(135deg,#f0fbff,#e6f7fd);
        border-radius:11px; font-weight:700; font-size:14px;
        border:1px solid #d6f1fb; color:#0B5A75;
      }

      .wave-breakdown {
        background:#f9fafb; border-radius:13px; padding:15px 17px;
        margin-top:15px; border:1px solid #f0f0f0;
      }
      .wave-breakdown-row {
        display:flex; justify-content:space-between;
        padding:5px 0; font-size:13px; color:#4b5563;
      }
      .wave-breakdown-row.total {
        font-weight:800; color:#111827; border-top:2px solid #e5e7eb;
        padding-top:10px; margin-top:6px; font-size:14px;
      }

      .wave-summary-card {
        border:2px solid #eef1ef; border-radius:15px; overflow:hidden;
      }
      .wave-summary-row {
        display:flex; align-items:center; justify-content:space-between;
        padding:15px 17px; border-bottom:1px solid #f0f0f0;
      }
      .wave-summary-row:last-child { border-bottom:none; }
      .wave-summary-row .label { font-size:12.5px; color:#6b7280; font-weight:600; }
      .wave-summary-row .value { font-size:13.5px; color:#111827; font-weight:700; text-align:right; }
      .wave-summary-total {
        background:linear-gradient(160deg,var(--wave-primary),var(--wave-primary-dark));
        color:#fff; padding:20px 17px; text-align:center;
      }
      .wave-summary-total .label {
        font-size:12px; opacity:.85; font-weight:600;
        letter-spacing:.4px; text-transform:uppercase;
      }
      .wave-summary-total .amount {
        font-size:29px; font-weight:800; margin-top:5px; letter-spacing:-.5px;
      }

      .wave-footer {
        padding:18px 24px 22px; border-top:1px solid #f0f0f0;
        display:flex; gap:10px;
      }
      .wave-btn-back {
        border:2px solid #e5e7eb; background:#fff; color:#374151;
        font-weight:700; font-size:14px; padding:14px 18px;
        border-radius:12px; cursor:pointer; font-family:inherit;
        transition:all .15s;
      }
      .wave-btn-back:hover { border-color:#c9d8ce; background:#fafafa; }
      .wave-submit {
        flex:1; border:none; background:var(--wave-accent);
        color:#111827; font-weight:800; font-size:15px; padding:14px;
        border-radius:12px; cursor:pointer; font-family:inherit;
        transition:filter .15s, transform .1s, box-shadow .15s;
        box-shadow:0 4px 14px rgba(255,206,0,.35);
      }
      .wave-submit:hover:not(:disabled) {
        filter:brightness(1.05); box-shadow:0 6px 18px rgba(255,206,0,.45);
      }
      .wave-submit:active:not(:disabled) { transform:scale(.99); }
      .wave-submit:disabled { opacity:.5; cursor:not-allowed; box-shadow:none; }

      .wave-state {
        padding:44px 24px; text-align:center; min-height:220px;
        display:flex; flex-direction:column; align-items:center; justify-content:center;
      }
      .wave-state h4 { margin:8px 0; font-size:17px; color:#111827; }
      .wave-state p { margin:0; font-size:13.5px; color:#6b7280; max-width:300px; line-height:1.5; }
      .wave-state .icon { font-size:52px; margin-bottom:8px; animation: wave-pop .35s ease; }
      .wave-spinner {
        width:44px; height:44px; border:3px solid #e5e7eb;
        border-top-color:var(--wave-primary); border-radius:50%;
        margin:0 auto 16px; animation: wave-spin .8s linear infinite;
      }
      .wave-retry {
        margin-top:18px; border:2px solid var(--wave-primary);
        background:#fff; color:var(--wave-primary-dark); font-weight:700;
        padding:11px 26px; border-radius:11px; cursor:pointer;
        font-family:inherit; transition:background .15s;
      }
      .wave-retry:hover { background:#f0fbff; }

      /* ---- En-tête avec logos + titre ---- */
      .wave-header-top {
        display:flex; align-items:center; justify-content:space-between;
        gap:12px; margin-bottom:14px; position:relative; z-index:1;
      }
      .wave-header-logos {
        display:flex; align-items:center; justify-content:space-between;
        width:100%; gap:12px;
      }
      .wave-header-logos img {
        height:36px; width:auto; object-fit:contain;
        background:#fff; border-radius:8px; padding:4px 8px;
        box-shadow:0 2px 6px rgba(0,0,0,.08);
      }
      .wave-header-logos .logo-left  { max-width:120px; }
      .wave-header-logos .logo-right { max-width:120px; }
      .wave-header-title {
        text-align:center; font-size:15px; font-weight:700;
        color:#fff; margin:8px 0 14px;
        letter-spacing:.2px; position:relative; z-index:1;
      }
      .wave-header-title .sep { opacity:.6; margin:0 6px; }
      .wave-header-title .ctx { color:#fff; opacity:.95; }
    `;
        document.head.appendChild(style);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------
    function formatAmount(units, currency) {
        try {
            return new Intl.NumberFormat("fr-FR", {
                style: "currency",
                currency: currency || "XOF",
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            }).format(units || 0);
        } catch {
            return `${units || 0} ${currency || ""}`;
        }
    }

    function escapeHtml(str) {
        const div = document.createElement("div");
        div.textContent = str == null ? "" : String(str);
        return div.innerHTML;
    }

    // ------------------------------------------------------------------
    // Widget
    // ------------------------------------------------------------------
    class WaveWidget {
        constructor(options = {}) {
            this.options = {
                ...DEFAULT_OPTIONS,
                ...options,
                theme: { ...DEFAULT_OPTIONS.theme, ...(options.theme || {}) },
                headers: { ...DEFAULT_OPTIONS.headers, ...(options.headers || {}) },
                translations: { ...DEFAULT_OPTIONS.translations, ...(options.translations || {}) },
                callbacks: { ...DEFAULT_OPTIONS.callbacks, ...(options.callbacks || {}) },
            };

            if (!PAYMENT_TYPES[this.options.defaultPaymentType]) {
                this.options.defaultPaymentType = null;
            }

            injectStyles(this.options.theme);

            this._overlay = null;
            this._modal = null;
            this._isOpen = false;
            this._escHandler = this._escHandler.bind(this);
        }

        /**
         * Ouvre le widget.
         * @param {Object} [baseData]
         * @param {string} [baseData.defaultPaymentType]  Force le type ('firstPayment' | 'earlyPayment' | 'recoveryPrime')
         * @param {string} [baseData.contractId]          Pré-remplit l'identifiant du contrat
         * @param {boolean}[baseData.autoVerifyContract]  Lance la vérification dès l'ouverture
         * @param {string} [baseData.description]
         * @param {string} [baseData.customerName]
         * @param {string} [baseData.customerEmail]
         * @param {object} [baseData.metadata]
         */
        open(baseData = {}) {
            if (this._isOpen) this.close();

            const forced     = baseData.defaultPaymentType || this.options.defaultPaymentType;
            const contract   = baseData.contractId || this.options.defaultContractId;
            const autoVerify = baseData.autoVerifyContract ?? this.options.autoVerifyContract;

            this._baseData = baseData;
            this._paymentType = PAYMENT_TYPES[forced] ? forced : null;
            this._step = this._paymentType ? 2 : 1;

            this._contractId         = contract || null;
            this._contractInfo       = null;
            this._contractStatus     = null;
            this._hasUnpaid          = false;
            this._numberOfPrimes     = 1;
            this._selectedInvoiceIds = new Set();
            this._manualAmount       = null;
            this._isVerifyingContract = false;
            this._isSubmitting       = false;
            this._blocked            = false;
            this._blockedReason      = null;

            this._render();
            this._isOpen = true;
            document.addEventListener("keydown", this._escHandler);

            if (typeof this.options.callbacks.onOpen === "function") {
                this.options.callbacks.onOpen(baseData);
            }

            // Auto-vérification si type + contrat fournis
            if (this._paymentType && this._contractId && autoVerify) {
                setTimeout(() => this._verifyContract(), 50);
            }
        }

        close() {
            if (this._overlay) {
                this._overlay.remove();
                this._overlay = null;
                this._modal = null;
            }
            this._isOpen = false;
            document.removeEventListener("keydown", this._escHandler);
            if (typeof this.options.callbacks.onClose === "function") {
                this.options.callbacks.onClose();
            }
        }

        _escHandler(e) {
            if (e.key === "Escape") this.close();
        }

        // ---------------- RENDU ----------------

        _render() {
            if (this._overlay) this._overlay.remove();

            const overlay = document.createElement("div");
            overlay.className = "wave-overlay";
            overlay.setAttribute("role", "dialog");
            overlay.setAttribute("aria-modal", "true");
            overlay.addEventListener("click", (e) => {
                if (e.target === overlay) this.close();
            });

            const modal = document.createElement("div");
            modal.className = "wave-modal";
            modal.innerHTML = this._shellTemplate();
            overlay.appendChild(modal);
            document.body.appendChild(overlay);

            this._overlay = overlay;
            this._modal = modal;

            this._bindEvents();
        }

        _shellTemplate() {
            const t = this.options.translations;
            return `
                <div class="wave-header">
                <div class="wave-header-top">
                    <div class="wave-header-logos">
                    ${this._leftLogoHtml()}
                    ${this._rightLogoHtml()}
                    </div>
                    <button type="button" class="wave-close" aria-label="${t.close}">&times;</button>
                </div>
                <div class="wave-header-title">
                    ${escapeHtml(t.title)}${this._contextTitleHtml()}
                </div>
                ${this._stepperTemplate()}
                </div>
                <div class="wave-body" data-role="body">${this._stepBodyTemplate()}</div>
                <div class="wave-footer" data-role="footer">${this._footerTemplate()}</div>
            `;
        }

        _leftLogoHtml() {
            const url = this._baseData?.leftLogo || this.options.leftLogo;
            if (!url) return "";
            return `<img src="${escapeHtml(url)}" alt="Yako" class="logo-left" />`;
        }

        _rightLogoHtml() {
            const url = this._baseData?.rightLogo || this.options.rightLogo;
            if (!url) return "";
            return `<img src="${escapeHtml(url)}" alt="Wave" class="logo-right" />`;
        }

        _contextTitleHtml() {
            const ctx = this._baseData?.contextTitle || this.options.contextTitle;
            if (!ctx) return "";
            return `<span class="sep">-</span><span class="ctx">${escapeHtml(ctx)}</span>`;
        }

        _stepperTemplate() {
            const t = this.options.translations;
            const steps = [
                { n: 1, label: t.stepType },
                { n: 2, label: t.stepContract },
                { n: 3, label: t.stepSummary },
            ];

            return `
        <div class="wave-steps">
          ${steps.map((s, i) => {
                const state = this._step === s.n ? "active" : this._step > s.n ? "done" : "";
                const content = this._step > s.n ? `<span class="checkmark">✓</span>` : String(s.n);
                return `
              <div class="wave-step ${state}">
                <div class="wave-step-track">
                  <div class="wave-step-circle">${content}</div>
                  ${i < steps.length - 1 ? `<div class="wave-step-line ${this._step > s.n ? "done" : ""}"></div>` : ""}
                </div>
                <span class="wave-step-label">${s.label}</span>
              </div>`;
            }).join("")}
        </div>
      `;
        }

        _stepBodyTemplate() {
            if (this._step === 1) return this._step1TypeTemplate();
            if (this._step === 2) return this._step2ContractTemplate();
            return this._step3SummaryTemplate();
        }

        _footerTemplate() {
            const t = this.options.translations;

            if (this._step === 1) {
                return `<button type="button" class="wave-btn-back" data-action="cancel">${t.cancel}</button>`;
            }

            if (this._step === 2) {
                if (this._blocked) {
                    const label = this._blockedReason === "has_unpaid" && this._paymentType === "earlyPayment"
                        ? t.switchToRecovery
                        : t.backToType;
                    return `<button type="button" class="wave-submit" data-action="unblock">${label}</button>`;
                }

                if (!this._contractInfo) {
                    return `<button type="button" class="wave-btn-back" data-action="go-type">${t.back}</button>`;
                }

                const canContinue = this._canProceedFromStep2();
                return `
                    <button type="button" class="wave-btn-back" data-action="go-type">${t.back}</button>
                    <button type="button" class="wave-submit" data-action="go-3" ${canContinue ? "" : "disabled"}>${t.continue}</button>`;
            }

            return `
                <button type="button" class="wave-btn-back" data-action="go-2">${t.back}</button>
                <button type="button" class="wave-submit" data-action="submit">${t.confirmAndPay}</button>`;
        }

        /**
         * Re-rend le corps, le pied et l'en-tête.
         * Conserve la position de scroll du corps et de la liste des factures
         * pour éviter tout « retour au début » visuel.
         */
        _refreshBody() {
            const body = this._modal.querySelector('[data-role="body"]');
            const footer = this._modal.querySelector('[data-role="footer"]');
            const header = this._modal.querySelector(".wave-header");

            const bodyScroll = body.scrollTop;
            const listScroll = body.querySelector(".wave-invoice-list")?.scrollTop || 0;

            body.innerHTML = this._stepBodyTemplate();
            footer.innerHTML = this._footerTemplate();
            header.innerHTML = `
                <div class="wave-header-top">
                <div class="wave-header-logos">
                    ${this._leftLogoHtml()}
                    ${this._rightLogoHtml()}
                </div>
                <button type="button" class="wave-close" aria-label="${this.options.translations.close}">&times;</button>
                </div>
                <div class="wave-header-title">
                ${escapeHtml(this.options.translations.title)}${this._contextTitleHtml()}
                </div>
                ${this._stepperTemplate()}
            `;

            body.scrollTop = bodyScroll;
            const newList = body.querySelector(".wave-invoice-list");
            if (newList) newList.scrollTop = listScroll;

            this._bindEvents();
        }

        /**
         * Active/désactive le bouton « Continuer » sans re-rendre le pied.
         */
        _syncContinueButton() {
            const go3 = this._modal?.querySelector('[data-action="go-3"]');
            if (go3) go3.disabled = !this._canProceedFromStep2();
        }

        /**
         * Met à jour uniquement ce qui dépend de la sélection des factures :
         * total, libellé « Tout cocher / décocher » et bouton « Continuer ».
         * Aucun re-rendu → la liste ne bouge pas.
         */
        _updateRecoverySelection() {
            const t = this.options.translations;
            const list = this._contractInfo?.facturesImpayees || [];
            const allChecked = list.length > 0 && list.every((f) => this._selectedInvoiceIds.has(f.IdPresentation));

            const totalEl = this._modal.querySelector('[data-role="recovery-total"]');
            if (totalEl) totalEl.textContent = formatAmount(this._computeRecoveryTotal(), this.options.currency);

            const toggle = this._modal.querySelector('[data-action="toggle-all"]');
            if (toggle) toggle.textContent = allChecked ? t.unselectAll : t.selectAll;

            this._syncContinueButton();
        }

        // ---------------- ÉTAPE 1 : TYPE DE PAIEMENT ----------------

        _step1TypeTemplate() {
            const t = this.options.translations;
            return `
                <div style="font-size:14px;font-weight:700;color:#374151;margin:0 0 16px;text-align:center;">
                    ${t.chooseType}
                </div>
                <div class="wave-type-options">
                    <button type="button" class="wave-type-btn" data-type="firstPayment">
                        <span class="icon">1️⃣</span>
                        ${t.typeFirstPayment}
                    </button>
                    <button type="button" class="wave-type-btn" data-type="earlyPayment">
                        <span class="icon">📅</span>
                        ${t.typeEarlyPayment}
                    </button>
                    <button type="button" class="wave-type-btn" data-type="recoveryPrime">
                        <span class="icon">⚠️</span>
                        ${t.typeRecoveryPrime}
                    </button>
                </div>
            `;
        }

        // ---------------- ÉTAPE 2 : CONTRAT ----------------

        _step2ContractTemplate() {
            const t = this.options.translations;

            if (this._blocked) {
                return `
                    <div class="wave-state" style="min-height:180px;">
                        <span class="icon">⛔</span>
                        <h4>${this._blockedTitle()}</h4>
                        <p>${escapeHtml(this._blockedReason || "")}</p>
                    </div>`;
            }

            if (this._isVerifyingContract) {
                return `
                    <div class="wave-state" style="min-height:180px;padding:30px 20px;">
                        <div class="wave-spinner"></div>
                        <p>${t.verifying}</p>
                    </div>`;
            }

            if (this._contractInfo) {
                return this._step2VerifiedTemplate();
            }

            return `
                <div class="wave-lookup-icon">📄</div>
                <div class="wave-field">
                    <label for="wave-contract">${t.contractIdLabel}</label>
                    <input id="wave-contract" type="text"
                           placeholder="${t.contractIdPlaceholder}"
                           value="${escapeHtml(this._contractId || "")}" />
                    <div class="error-message">${t.requiredField}</div>
                </div>
                <button type="button" class="wave-submit"
                        data-action="verify"
                        style="margin:18px 0 0;width:100%;background:#fff;color:var(--wave-primary-dark);border:2px solid var(--wave-primary);box-shadow:none;">
                    ${t.verify}
                </button>
            `;
        }

        _blockedTitle() {
            const t = this.options.translations;
            if (this._blockedReason === "has_unpaid" && this._paymentType === "earlyPayment") {
                return t.contractHasUnpaid;
            }
            if (this._blockedReason === "no_unpaid" && this._paymentType === "recoveryPrime") {
                return t.contractNoUnpaid;
            }
            if (this._blockedReason === "not_found") {
                return t.contractNotFound;
            }
            return t.error;
        }

        _step2VerifiedTemplate() {
            const t = this.options.translations;
            const info = this._contractInfo || {};
            const prime = Number(info.primePrincipale || 0);
            const frais = Number(info.fraisAdhesion || 0);

            let body = `<div class="wave-notice info">${this._contractSummaryHtml()}</div>`;

            if (this._paymentType === "firstPayment" && this._contractStatus === "found") {
                body += `
                    <div class="wave-breakdown">
                        <div class="wave-breakdown-row">
                            <span>${t.primePrincipale}</span>
                            <span>${formatAmount(prime, this.options.currency)}</span>
                        </div>
                        <div class="wave-breakdown-row">
                            <span>${t.fraisAdhesion}</span>
                            <span>${formatAmount(frais, this.options.currency)}</span>
                        </div>
                        <div class="wave-breakdown-row total">
                            <span>${t.totalLabel}</span>
                            <span>${formatAmount(prime + frais, this.options.currency)}</span>
                        </div>
                    </div>`;
                return body;
            }

            if (this._paymentType === "firstPayment" && this._contractStatus === "not_found") {
                body += `
                    <div class="wave-field">
                        <label for="wave-manual-amount">${t.manualAmountLabel}</label>
                        <input id="wave-manual-amount" type="number" min="1" step="1"
                               placeholder="Ex: 15000"
                               value="${this._manualAmount || ""}" />
                        <span class="hint">${t.manualAmountHint}</span>
                        <div class="error-message">${t.requiredField}</div>
                    </div>`;
                return body;
            }

            if (this._paymentType === "earlyPayment" && this._contractStatus === "found") {
                const total = prime * this._numberOfPrimes;
                body += `
                    <div style="text-align:center;font-size:13px;font-weight:700;color:#374151;margin:14px 0 10px;">
                        ${t.numberOfPrimesLabel}
                    </div>
                    <div class="wave-stepper">
                        <button type="button" data-prime-step="-1">−</button>
                        <span>${this._numberOfPrimes}</span>
                        <button type="button" data-prime-step="1">+</button>
                    </div>
                    <div class="wave-total-bar">
                        <span>${t.totalLabel}</span>
                        <span>${formatAmount(total, this.options.currency)}</span>
                    </div>`;
                return body;
            }

            if (this._paymentType === "recoveryPrime" && this._contractStatus === "found") {
                const list = info.facturesImpayees || [];
                const total = this._computeRecoveryTotal();
                const allChecked = list.length > 0 && list.every(f => this._selectedInvoiceIds.has(f.IdPresentation));

                const items = list.map((f) => {
                    const id = f.IdPresentation;
                    const checked = this._selectedInvoiceIds.has(id);
                    return `
                        <label class="wave-invoice-item">
                            <input type="checkbox" data-invoice="${escapeHtml(id)}" ${checked ? "checked" : ""} />
                            <span class="meta">
                                ${escapeHtml(f.MaDate || "")} — réf ${escapeHtml(f.CodePresentation || id)}
                            </span>
                            <span class="amount">${formatAmount(f.MontantNet || 0, this.options.currency)}</span>
                        </label>`;
                }).join("");

                body += `
                    <div class="wave-invoice-header">
                        <div style="font-size:13px;font-weight:700;color:#374151;">
                            ${t.selectInvoicesLabel}
                        </div>
                        <button type="button" class="wave-link-btn" data-action="toggle-all">
                            ${allChecked ? t.unselectAll : t.selectAll}
                        </button>
                    </div>
                    <div class="wave-invoice-list">${items}</div>
                    <div class="wave-total-bar">
                        <span>${t.totalLabel}</span>
                        <span data-role="recovery-total">${formatAmount(total, this.options.currency)}</span>
                    </div>`;
                return body;
            }

            return body;
        }

        _contractSummaryHtml() {
            const info = this._contractInfo || {};
            const t = this.options.translations;

            const id = info.idProposition || info.contratIdWeb || this._contractId || "—";
            const souscripteur = info.souscripteur || "";
            const produit = info.produit || "";
            const nbImpayes = (info.facturesImpayees || []).length;

            let html = `<strong>${t.contractSummary.replace("{id}", escapeHtml(id))}</strong>`;
            if (souscripteur) html += `<br>${t.subscriber} : ${escapeHtml(souscripteur)}`;
            if (produit) html += `<br>${t.product} : ${escapeHtml(produit)}`;
            if (nbImpayes > 0) {
                html += `<br>⚠️ <strong>${t.unpaidInvoices.replace("{count}", nbImpayes)}</strong>`;
            }
            return html;
        }

        // ---------------- ÉTAPE 3 : RÉCAP ----------------

        _step3SummaryTemplate() {
            const t = this.options.translations;
            const info = this._contractInfo || {};
            const amount = this._computeAmount();
            const contractLabel = info.idProposition || info.contratIdWeb || this._contractId || "—";

            const typeLabels = {
                firstPayment: t.typeFirstPayment,
                earlyPayment: t.typeEarlyPayment,
                recoveryPrime: t.typeRecoveryPrime,
            };

            return `
                <p style="text-align:center;color:#6b7280;font-size:13px;margin:0 0 16px;">
                    ${t.summaryTitle}
                </p>
                <div class="wave-summary-card">
                    <div class="wave-summary-row">
                        <span class="label">${t.summaryContract}</span>
                        <span class="value">${escapeHtml(contractLabel)}</span>
                    </div>
                    <div class="wave-summary-row">
                        <span class="label">${t.summaryType}</span>
                        <span class="value">${typeLabels[this._paymentType] || "—"}</span>
                    </div>
                    <div class="wave-summary-total">
                        <div class="label">${t.summaryAmount}</div>
                        <div class="amount">${formatAmount(amount, this.options.currency)}</div>
                    </div>
                </div>
            `;
        }

        // ---------------- CALCULS ----------------

        _computeRecoveryTotal() {
            const info = this._contractInfo || {};
            const list = info.facturesImpayees || [];
            return list
                .filter((f) => this._selectedInvoiceIds.has(f.IdPresentation))
                .reduce((s, f) => s + Number(f.MontantNet || 0), 0);
        }

        _computeAmount() {
            const info = this._contractInfo || {};
            const prime = Number(info.primePrincipale || 0);
            const frais = Number(info.fraisAdhesion || 0);

            if (this._paymentType === "firstPayment") {
                if (this._contractStatus === "not_found") {
                    return Number(this._manualAmount || 0);
                }
                return prime + frais;
            }
            if (this._paymentType === "earlyPayment") {
                return prime * this._numberOfPrimes;
            }
            if (this._paymentType === "recoveryPrime") {
                return this._computeRecoveryTotal();
            }
            return 0;
        }

        _canProceedFromStep2() {
            if (this._blocked) return false;

            if (this._paymentType === "firstPayment") {
                if (this._contractStatus === "not_found") {
                    return Number(this._manualAmount || 0) > 0;
                }
                return this._computeAmount() > 0;
            }

            if (this._paymentType === "earlyPayment") {
                return this._contractStatus === "found" && this._computeAmount() > 0;
            }

            if (this._paymentType === "recoveryPrime") {
                return this._selectedInvoiceIds.size > 0 && this._computeAmount() > 0;
            }

            return false;
        }

        // ---------------- ÉVÉNEMENTS ----------------

        _bindEvents() {
            const modal = this._modal;

            const closeBtn = modal.querySelector(".wave-close");
            if (closeBtn) closeBtn.addEventListener("click", () => this.close());

            const cancelBtn = modal.querySelector('[data-action="cancel"]');
            if (cancelBtn) cancelBtn.addEventListener("click", () => this.close());

            modal.querySelectorAll(".wave-type-btn").forEach((btn) => {
                btn.addEventListener("click", () => {
                    const type = btn.dataset.type;
                    if (!PAYMENT_TYPES[type]) return;
                    this._paymentType = type;
                    this._resetContractState();
                    this._step = 2;
                    this._refreshBody();
                });
            });

            const verifyBtn = modal.querySelector('[data-action="verify"]');
            if (verifyBtn) verifyBtn.addEventListener("click", () => this._verifyContract());

            const input = modal.querySelector("#wave-contract");
            if (input) {
                input.addEventListener("keydown", (e) => {
                    if (e.key === "Enter") {
                        e.preventDefault();
                        this._verifyContract();
                    }
                });
            }

            // Montant manuel : mise à jour du bouton « Continuer » sans re-rendu
            const manualInput = modal.querySelector("#wave-manual-amount");
            if (manualInput) {
                manualInput.addEventListener("input", () => {
                    this._manualAmount = manualInput.value;
                    this._syncContinueButton();
                });
            }

            modal.querySelectorAll("[data-prime-step]").forEach((btn) => {
                btn.addEventListener("click", () => {
                    const delta = Number(btn.dataset.primeStep);
                    const next = this._numberOfPrimes + delta;
                    if (next >= 1 && next <= 60) {
                        this._numberOfPrimes = next;
                        this._refreshBody();
                    }
                });
            });

            // Cocher / décocher une facture : mise à jour ciblée, pas de re-rendu
            modal.querySelectorAll("[data-invoice]").forEach((cb) => {
                cb.addEventListener("change", () => {
                    const id = cb.dataset.invoice;
                    if (cb.checked) this._selectedInvoiceIds.add(id);
                    else this._selectedInvoiceIds.delete(id);
                    this._updateRecoverySelection();
                });
            });

            // Tout cocher / décocher : on modifie les cases existantes sur place
            const toggleAll = modal.querySelector('[data-action="toggle-all"]');
            if (toggleAll) {
                toggleAll.addEventListener("click", () => {
                    const list = this._contractInfo?.facturesImpayees || [];
                    const allChecked = list.length > 0 && list.every(f => this._selectedInvoiceIds.has(f.IdPresentation));

                    if (allChecked) {
                        this._selectedInvoiceIds.clear();
                    } else {
                        list.forEach(f => this._selectedInvoiceIds.add(f.IdPresentation));
                    }

                    modal.querySelectorAll("[data-invoice]").forEach((cb) => {
                        cb.checked = this._selectedInvoiceIds.has(cb.dataset.invoice);
                    });

                    this._updateRecoverySelection();
                });
            }

            const unblock = modal.querySelector('[data-action="unblock"]');
            if (unblock) {
                unblock.addEventListener("click", () => {
                    if (this._blockedReason === "has_unpaid" && this._paymentType === "earlyPayment") {
                        this._paymentType = "recoveryPrime";
                        this._resetContractState(false);
                        this._blocked = false;
                        this._blockedReason = null;
                        this._contractId = this._contractId || null;
                        this._step = 2;
                        this._refreshBody();
                    } else {
                        this._step = 1;
                        this._resetContractState();
                        this._refreshBody();
                    }
                });
            }

            const goType = modal.querySelectorAll('[data-action="go-type"]');
            goType.forEach(b => b.addEventListener("click", () => {
                this._step = 1;
                this._resetContractState();
                this._refreshBody();
            }));

            const go3 = modal.querySelector('[data-action="go-3"]');
            if (go3) go3.addEventListener("click", () => {
                if (!this._canProceedFromStep2()) return;
                this._step = 3;
                this._refreshBody();
            });

            const go2 = modal.querySelector('[data-action="go-2"]');
            if (go2) go2.addEventListener("click", () => {
                this._step = 2;
                this._refreshBody();
            });

            const submitBtn = modal.querySelector('[data-action="submit"]');
            if (submitBtn) submitBtn.addEventListener("click", () => this._handleSubmit());
        }

        _resetContractState(resetContractId = true) {
            this._contractInfo = null;
            this._contractStatus = null;
            this._hasUnpaid = false;
            this._selectedInvoiceIds = new Set();
            this._numberOfPrimes = 1;
            this._manualAmount = null;
            this._blocked = false;
            this._blockedReason = null;
            if (resetContractId) this._contractId = null;
        }

        // ---------------- VÉRIFICATION CONTRAT ----------------

        async _verifyContract() {
            if (this._isVerifyingContract) return;

            const input = this._modal.querySelector("#wave-contract");
            const idContrat = (input?.value || this._contractId || "").trim();

            if (!idContrat) {
                if (input) {
                    input.classList.add("error");
                    input.parentElement.querySelector(".error-message")?.classList.add("visible");
                }
                return;
            }

            this._contractId = idContrat;
            this._isVerifyingContract = true;
            this._refreshBody();

            const t = this.options.translations;
            const headers = {
                "Content-Type": "application/json",
                Accept: "application/json",
                ...this.options.headers,
            };
            if (this.options.csrfToken) headers["X-CSRF-TOKEN"] = this.options.csrfToken;

            try {
                const res = await fetch(this.options.contractCheckEndpoint, {
                    method: "POST",
                    headers,
                    credentials: "same-origin",
                    body: JSON.stringify({
                        idContrat,
                        paymentType: this._paymentType,
                    }),
                });

                let data;
                try { data = await res.json(); } catch { data = {}; }

                if (!res.ok || !data.success) {
                    this._isVerifyingContract = false;
                    this._refreshBody();
                    this._showError(data.message || t.contractNotFound);
                    return;
                }

                const status = data.status || "contract_found";
                this._contractStatus = status === "contract_not_found" ? "not_found" : "found";
                this._contractInfo = data.data || {};
                this._hasUnpaid = data.has_unpaid === true || (this._contractInfo.facturesImpayees || []).length > 0;

                if (this._paymentType === "earlyPayment") {
                    if (this._contractStatus === "not_found") {
                        this._blocked = true;
                        this._blockedReason = "not_found";
                    } else if (this._hasUnpaid) {
                        this._blocked = true;
                        this._blockedReason = "has_unpaid";
                    }
                } else if (this._paymentType === "recoveryPrime") {
                    if (this._contractStatus === "not_found") {
                        this._blocked = true;
                        this._blockedReason = "not_found";
                    } else if (!this._hasUnpaid) {
                        this._blocked = true;
                        this._blockedReason = "no_unpaid";
                    }
                }

                this._isVerifyingContract = false;
                this._refreshBody();
            } catch (e) {
                this._isVerifyingContract = false;
                this._refreshBody();
                this._showError(e.message || t.networkError);
            }
        }

        _showError(message) {
            let el = this._modal.querySelector(".wave-general-error");
            if (!el) {
                el = document.createElement("div");
                el.className = "wave-notice warn wave-general-error";
                const body = this._modal.querySelector('[data-role="body"]');
                if (body) body.appendChild(el);
            }
            el.textContent = message;
        }

        // ---------------- SOUMISSION ----------------

        _renderLoadingState() {
            const t = this.options.translations;
            this._modal.innerHTML = `
                <div class="wave-state">
                    <div class="wave-spinner"></div>
                    <p>${t.processing}</p>
                </div>`;
        }

        _renderRedirectingState() {
            const t = this.options.translations;
            this._modal.innerHTML = `
                <div class="wave-state">
                    <span class="icon">✅</span>
                    <h4>${t.success}</h4>
                    <p>${t.successMessage}</p>
                </div>`;
        }

        _renderErrorState(message) {
            const t = this.options.translations;
            this._modal.innerHTML = `
                <div class="wave-state">
                    <span class="icon">❌</span>
                    <h4>${t.error}</h4>
                    <p>${escapeHtml(message || t.error)}</p>
                    <button type="button" class="wave-retry">${t.retry}</button>
                </div>`;
            this._modal.querySelector(".wave-retry").addEventListener("click", () => {
                this._step = 3;
                this._render();
            });
        }

        async _handleSubmit() {
            if (this._isSubmitting) return;
            this._isSubmitting = true;
            this._renderLoadingState();

            const t = this.options.translations;
            const headers = {
                "Content-Type": "application/json",
                Accept: "application/json",
                ...this.options.headers,
            };
            if (this.options.csrfToken) headers["X-CSRF-TOKEN"] = this.options.csrfToken;

            const reference = "WAVE-" + Date.now() + "-" + Math.floor(Math.random() * 9999);

            const payload = {
                reference,
                currency: this.options.currency,
                paymentType: this._paymentType,
                amount: this._computeAmount(),
                contractId: this._contractId || undefined,
                numberOfPrimes: this._paymentType === "earlyPayment" ? this._numberOfPrimes : undefined,
                selectedInvoiceIds: this._paymentType === "recoveryPrime"
                    ? Array.from(this._selectedInvoiceIds)
                    : undefined,
                manualAmount: (this._paymentType === "firstPayment" && this._contractStatus === "not_found")
                    ? Number(this._manualAmount)
                    : undefined,
                description: this._baseData.description || null,
                customerName: this._baseData.customerName || null,
                customerEmail: this._baseData.customerEmail || null,
                metadata: this._baseData.metadata || null,
            };

            try {
                const controller = new AbortController();
                const timeout = setTimeout(() => controller.abort(), this.options.timeout);

                const response = await fetch(this.options.backendEndpoint, {
                    method: "POST",
                    headers,
                    body: JSON.stringify(payload),
                    signal: controller.signal,
                    credentials: "same-origin",
                });
                clearTimeout(timeout);

                let data;
                try { data = await response.json(); } catch { data = {}; }

                if (!response.ok || !data.success) {
                    const msg = data.message || `Erreur serveur (${response.status})`;
                    this._renderErrorState(msg);
                    if (typeof this.options.callbacks.onError === "function") {
                        this.options.callbacks.onError(msg, data);
                    }
                    this._isSubmitting = false;
                    return;
                }

                const redirectUrl = data.data?.redirectUrl || data.data?.wave_launch_url || data.redirect_url;
                if (!redirectUrl) {
                    this._renderErrorState("Aucune URL Wave reçue du serveur.");
                    this._isSubmitting = false;
                    return;
                }

                this._renderRedirectingState();

                if (typeof this.options.callbacks.onSuccess === "function") {
                    this.options.callbacks.onSuccess(redirectUrl, data);
                }

                setTimeout(() => {
                    this.close();
                    window.location.href = redirectUrl;
                }, 1200);
            } catch (error) {
                const msg = error.name === "AbortError"
                    ? "La requête a expiré."
                    : error.message || t.networkError;
                this._renderErrorState(msg);
                if (typeof this.options.callbacks.onError === "function") {
                    this.options.callbacks.onError(msg, null);
                }
                this._isSubmitting = false;
            }
        }

        isOpen() { return this._isOpen; }
        getPaymentData() {
            return {
                paymentType: this._paymentType,
                contractId: this._contractId,
                manualAmount: this._manualAmount,
                numberOfPrimes: this._numberOfPrimes,
                selectedInvoiceIds: Array.from(this._selectedInvoiceIds),
            };
        }
    }

    window.WaveWidget = WaveWidget;
})(window, document);
