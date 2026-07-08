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
    "use strict";

    const PAYMENT_METHODS = [
        {
            id: "wave",
            label: "Wave",
            iconUrl:
                "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSZaeFi3xAkC86Ui29AojMASpYfFMPLDzf-1hTcDVS-0Q&s=10",
            hint: "Paiement mobile",
            disabled: false,
        },
        {
            id: "orange",
            label: "Orange Money",
            iconUrl:
                "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRiNkcP-3jO9hJmuSHaXVo8yEzdoy-lOy8NcQgBHvbqCw&s=10",
            hint: "Paiement mobile",
            disabled: false,
        },
        {
            id: "moov",
            label: "Moov Money",
            iconUrl:
                "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQyu5BMOD9klyZBXQ4Pq7A1e1twlAte3KLXAVNyy9fla4pOika5S9BccZc&s=10",
            hint: "Paiement mobile",
            disabled: false,
        },
        {
            id: "mtn",
            label: "MTN MoMo",
            iconUrl:
                "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSA_m-zjaO2_OO6Ho9UOVaNUESFGH1oOg33NxQsQIN0KOqnvRzuXML9ppt6&s=10",
            hint: "Paiement mobile",
            disabled: false,
        },
        {
            id: "djamo",
            label: "Djamo",
            iconUrl:
                "https://play-lh.googleusercontent.com/COFlFnBiED3WHi-J8CRd6ehKOzBjvgKGySJasSaOm1OrMZbsn0NVzk3uL4PpzGo7mF91EBaOvbsqRL9ImD_-7A",
            hint: "Carte virtuelle ou mobile",
            disabled: false,
        },
        {
            id: "visa",
            label: "Visa",
            iconUrl:
                "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSyKhbs0dVowkgkGdydEfQkqwZd2XrVFSPBz2fDbgU4_g&s=10",
            hint: "Carte bancaire",
            disabled: true,
        },
        {
            id: "mastercard",
            label: "Mastercard",
            iconUrl:
                "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRtTrzAD2ayUIWu8KkdlZZQ64sRrLLRHZbS4mrq3do4Ug&s=10",
            hint: "Carte bancaire",
            disabled: true,
        },
    ];

    // Types de paiement pris en charge par le widget
    const PAYMENT_TYPES = {
        firstPayment: "firstPayment",
        earlyPayment: "earlyPayment",
        recoveryPrime: "recoveryPrime",
    };

    const DEFAULT_OPTIONS = {
        backendEndpoint: "/api/paiements/jeko/init",
        contractCheckEndpoint: "/api/paiements/jeko/contrat/verifier",
        currency: "XOF",
        successUrl: null,
        errorUrl: null,
        timeout: 30000,
        headers: {},
        theme: {
            primary: "#1D603D",
            primaryDark: "#0B482F",
            accent: "#E09518",
            radius: "12px",
            fontFamily:
                "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
            maxWidth: "630px",
        },
        translations: {
            title: "Paiement sécurisé",
            close: "Fermer",
            chooseMethod: "Choisissez un moyen de paiement",
            submit: "Continuer",
            processing: "Initialisation du paiement en cours...",
            success: "Paiement initialisé",
            successMessage:
                "Vous allez être redirigé vers la page de paiement...",
            error: "Échec du paiement",
            retry: "Réessayer",
            networkError: "Erreur réseau",
            requiredField: "Ce champ est requis",
            contractIdLabel: "Identifiant du contrat",
            contractIdPlaceholder: "Ex: 345678",
            verify: "Vérifier",
            verifying: "Vérification en cours...",
            firstPaymentOptionA: "Payer la première prime",
            firstPaymentOptionB: "Payer en avance",
            numberOfPrimesLabel: "Nombre de primes à régler",
            blockedEarlyPayment:
                "Ce contrat a des primes impayées. Le paiement anticipé n'est pas disponible tant qu'elles ne sont pas régularisées.",
            switchToRecovery: "Régulariser mes impayés",
            noUnpaidInvoices: "Aucune facture impayée sur ce contrat.",
            switchToEarly: "Payer en avance à la place",
            selectInvoicesLabel: "Sélectionnez les primes à régulariser",
            totalLabel: "Total à régler",
            // Nouveaux libellés pour firstPayment
            firstPaymentBase: "Première prime",
            firstPaymentFees: "Frais d'adhésion",
            firstPaymentTotal: "Total de la première prime",
            additionalPrimesLabel: "Souhaitez-vous également payer d'autres primes en avance ?",
            additionalPrimesYes: "Oui",
            additionalPrimesNo: "Non",
            additionalPrimesCount: "Nombre de primes en avance",
            additionalPrimesTotal: "Total des primes en avance",
        },
        callbacks: {
            onSuccess: null,
            onError: null,
            onClose: null,
            onOpen: null,
        },
    };

    function injectStyles(theme) {
        if (document.getElementById("jeko-widget-styles")) return;
        const style = document.createElement("style");
        style.id = "jeko-widget-styles";
        style.textContent = `
      .jeko-overlay { --jeko-primary:${theme.primary}; --jeko-primary-dark:${theme.primaryDark}; --jeko-accent:${theme.accent}; --jeko-radius:${theme.radius}; --jeko-max-width:${theme.maxWidth}; font-family:${theme.fontFamily}; position:fixed; inset:0; background:rgba(11,15,13,.55); display:flex; align-items:center; justify-content:center; z-index:999999; padding:20px; backdrop-filter:blur(4px); }
      .jeko-modal { background:#fff; width:100%; max-width:var(--jeko-max-width); border-radius:var(--jeko-radius); box-shadow:0 25px 60px rgba(0,0,0,.3); overflow:hidden; max-height:90vh; display:flex; flex-direction:column; }
      .jeko-header { background:linear-gradient(135deg,var(--jeko-primary),var(--jeko-primary-dark)); color:#fff; padding:20px 24px; display:flex; align-items:center; justify-content:space-between; }
      .jeko-header h3 { margin:0; font-size:18px; font-weight:600; }
      .jeko-close { background:rgba(255,255,255,.15); border:none; color:#fff; width:32px; height:32px; border-radius:50%; font-size:18px; cursor:pointer; }
      .jeko-body { padding:24px; overflow-y:auto; flex:1; }
      .jeko-amount { text-align:center; padding:8px 0 16px; }
      .jeko-amount .amount { font-size:32px; font-weight:700; color:var(--jeko-primary-dark); display:block; }
      .jeko-amount .description { font-size:14px; color:#6b7280; margin-top:4px; display:block; }
      .jeko-section-label { font-size:14px; font-weight:600; color:#374151; margin:16px 0 8px; display:block; }
      .jeko-methods { display:grid; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:10px; }
      .jeko-method-btn { border:2px solid #e5e7eb; background:#fafafa; border-radius:10px; padding:12px 14px; text-align:center; cursor:pointer; width:100%; display:flex; flex-direction:column; align-items:center; gap:4px; }
      .jeko-method-btn[aria-pressed="true"] { border-color:var(--jeko-primary); background:#e8f5ee; }
      .jeko-method-btn.cards-disabled { background:#f0f0f0; cursor:no-drop; pointer-events:none; }
      .jeko-method-btn .icon-wrapper { width:44px; height:44px; border-radius:50%; overflow:hidden; display:flex; align-items:center; justify-content:center; background:#f3f4f6; }
      .jeko-method-btn .icon-wrapper img { width:30px; height:30px; object-fit:contain; border-radius:20%; }
      .jeko-method-btn .name { font-weight:600; font-size:13px; color:#111827; }
      .jeko-method-btn .hint { font-size:11px; color:#6b7280; }
      .jeko-type-grid { display:grid; grid-template-columns:1fr; gap:8px; }
      .jeko-type-btn { border:2px solid #e5e7eb; background:#fafafa; border-radius:10px; padding:12px 14px; text-align:left; cursor:pointer; font-family:inherit; }
      .jeko-type-btn[aria-pressed="true"] { border-color:var(--jeko-primary); background:#e8f5ee; }
      .jeko-field { margin-top:14px; }
      .jeko-field label { display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px; }
      .jeko-field input { width:100%; box-sizing:border-box; padding:12px 14px; border-radius:8px; border:2px solid #e5e7eb; font-size:15px; font-family:inherit; }
      .jeko-field .error-message { color:#dc2626; font-size:12px; margin-top:4px; display:none; }
      .jeko-field .error-message.visible { display:block; }
      .jeko-stepper { display:flex; align-items:center; gap:12px; }
      .jeko-stepper button { width:36px; height:36px; border-radius:8px; border:2px solid #e5e7eb; background:#fff; font-size:18px; cursor:pointer; }
      .jeko-stepper span { font-size:16px; font-weight:700; min-width:24px; text-align:center; }
      .jeko-invoice-list { display:flex; flex-direction:column; gap:8px; max-height:220px; overflow-y:auto; }
      .jeko-invoice-item { display:flex; align-items:center; gap:10px; border:1px solid #e5e7eb; border-radius:8px; padding:10px 12px; }
      .jeko-invoice-item .meta { flex:1; font-size:13px; color:#374151; }
      .jeko-invoice-item .amount { font-weight:700; font-size:13px; color:#111827; }
      .jeko-total-bar { display:flex; justify-content:space-between; align-items:center; margin-top:12px; padding:10px 14px; background:#f0f7f3; border-radius:8px; font-weight:700; }
      .jeko-notice { font-size:13px; padding:10px 14px; border-radius:8px; margin-top:12px; }
      .jeko-notice.warn { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
      .jeko-notice.info { background:#eff6ff; color:#1e3a8a; border:1px solid #bfdbfe; }
      .jeko-notice.success { background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; }
      .jeko-link-btn { background:none; border:none; color:var(--jeko-primary-dark); font-weight:600; text-decoration:underline; cursor:pointer; padding:0; margin-top:8px; font-family:inherit; font-size:13px; }
      .jeko-footer { padding:16px 24px 24px; border-top:1px solid #f0f0f0; }
      .jeko-submit { width:100%; border:none; background:var(--jeko-accent); color:#1f2937; font-weight:700; font-size:15px; padding:14px; border-radius:10px; cursor:pointer; font-family:inherit; }
      .jeko-submit:disabled { opacity:.6; cursor:not-allowed; }
      .jeko-state { padding:40px 20px; text-align:center; min-height:200px; display:flex; flex-direction:column; align-items:center; justify-content:center; }
      .jeko-state.success .icon { font-size:64px; }
      .jeko-state.error .icon { font-size:64px; }
      .jeko-spinner { width:40px; height:40px; border:3px solid #e5e7eb; border-top-color:var(--jeko-primary); border-radius:50%; margin:0 auto 16px; animation:jeko-spin .8s linear infinite; }
      @keyframes jeko-spin { to { transform:rotate(360deg); } }
      .jeko-state .icon { font-size:48px; margin-bottom:12px; }
      .jeko-retry { margin-top:16px; border:2px solid var(--jeko-primary); background:#fff; color:var(--jeko-primary-dark); font-weight:600; padding:10px 24px; border-radius:8px; cursor:pointer; }
      @keyframes shake { 0%,100%{transform:translateX(0)} 10%,30%,50%,70%,90%{transform:translateX(-6px)} 20%,40%,60%,80%{transform:translateX(6px)} }
      .jeko-toggle-group { display:flex; gap:12px; margin-top:6px; }
      .jeko-toggle-btn { flex:1; border:2px solid #e5e7eb; background:#fafafa; border-radius:10px; padding:10px 14px; text-align:center; cursor:pointer; font-family:inherit; font-weight:600; font-size:14px; }
      .jeko-toggle-btn[aria-pressed="true"] { border-color:var(--jeko-primary); background:#e8f5ee; }
      .jeko-breakdown { background:#f9fafb; border-radius:8px; padding:14px; margin-top:12px; }
      .jeko-breakdown-row { display:flex; justify-content:space-between; padding:4px 0; font-size:13px; color:#374151; }
      .jeko-breakdown-row.total { font-weight:700; color:#111827; border-top:2px solid #e5e7eb; padding-top:8px; margin-top:4px; }
    `;
        document.head.appendChild(style);
    }

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

    class JekoWidget {
        constructor(options = {}) {
            this.options = {
                ...DEFAULT_OPTIONS,
                ...options,
                theme: { ...DEFAULT_OPTIONS.theme, ...(options.theme || {}) },
                headers: {
                    ...DEFAULT_OPTIONS.headers,
                    ...(options.headers || {}),
                },
                translations: {
                    ...DEFAULT_OPTIONS.translations,
                    ...(options.translations || {}),
                },
                callbacks: {
                    ...DEFAULT_OPTIONS.callbacks,
                    ...(options.callbacks || {}),
                },
            };
            injectStyles(this.options.theme);
            this._overlay = null;
            this._modal = null;
            this._isOpen = false;
            this._isSubmitting = false;
            this._escHandler = this._escHandler.bind(this);
            this._autoVerifyDone = false;
            this._isVerifyingContract = false;
            // Nouveaux états pour firstPayment
            this._additionalPrimes = false;
            this._additionalPrimesCount = 0;
        }

        /**
         * paymentData attendu :
         * {
         *   reference,                 // requis, référence métier unique
         *   paymentType,               // requis: 'firstPayment' | 'earlyPayment' | 'recoveryPrime'
         *   contractId,                // requis pour earlyPayment / recoveryPrime, optionnel pour firstPayment
         *   primeUnitaire,             // XOF, requis pour firstPayment si contractId absent
         *   fraisAdhesion,             // XOF, requis pour firstPayment si contractId absent
         *   currency, description, customerEmail, customerName, metadata
         * }
         */
        open(paymentData) {
            if (!paymentData?.reference)
                throw new Error("JekoWidget.open() nécessite 'reference'.");
            if (!PAYMENT_TYPES[paymentData.paymentType])
                throw new Error(
                    "JekoWidget.open() nécessite un 'paymentType' valide.",
                );

            if (this._isOpen) this.close();

            this._paymentData = {
                currency: this.options.currency,
                ...paymentData,
            };
            this._selectedMethod = null;
            this._numberOfPrimes = 1;
            this._firstPaymentOption = "A"; // 'A' = première prime uniquement, 'B' = avance
            this._contractInfo = null; // rempli après vérification contrat
            this._selectedInvoiceIds = new Set();
            this._blocked = false;
            // Réinitialiser les états firstPayment
            this._additionalPrimes = false;
            this._additionalPrimesCount = 0;

            this._render();
            this._isOpen = true;
            document.addEventListener("keydown", this._escHandler);
            if (this.options.callbacks.onOpen)
                this.options.callbacks.onOpen(this._paymentData);
        }

        close() {
            if (this._overlay) {
                this._overlay.remove();
                this._overlay = null;
                this._modal = null;
            }
            this._isOpen = false;
            document.removeEventListener("keydown", this._escHandler);
            if (this.options.callbacks.onClose)
                this.options.callbacks.onClose();
        }

        _escHandler(e) {
            if (e.key === "Escape") this.close();
        }

        _render() {
            if (this._overlay) this._overlay.remove();
            const overlay = document.createElement("div");
            overlay.className = "jeko-overlay";
            overlay.setAttribute("role", "dialog");
            overlay.setAttribute("aria-modal", "true");
            overlay.addEventListener("click", (e) => {
                if (e.target === overlay) this.close();
            });

            const modal = document.createElement("div");
            modal.className = "jeko-modal";
            modal.innerHTML = this._formTemplate();
            overlay.appendChild(modal);
            document.body.appendChild(overlay);
            this._overlay = overlay;
            this._modal = modal;

            this._bindEvents();
        }

        _computeAmount() {
            const t = this._paymentData.paymentType;
            const prime = Number(this._contractInfo?.primePrincipale || 0);
            const frais = Number(this._contractInfo?.fraisAdhesion || 0);

            if (t === "firstPayment") {
                const firstPrimeTotal = frais + prime;
                const additionalTotal = this._additionalPrimes ? prime * this._additionalPrimesCount : 0;
                return firstPrimeTotal + additionalTotal;
            }
            if (t === "earlyPayment") {
                return prime * this._numberOfPrimes;
            }
            if (t === "recoveryPrime") {
                const list = this._contractInfo?.facturesImpayees || [];
                return list
                    .filter((f) =>
                        this._selectedInvoiceIds.has(f.IdPresentation),
                    )
                    .reduce((sum, f) => sum + Number(f.MontantNet || 0), 0);
            }
            return 0;
        }

        _formTemplate() {
            const t = this.options.translations;
            const { description } = this._paymentData;
            const amount = this._computeAmount();

            const methodsHtml = PAYMENT_METHODS.map(
                (m) => `
        <button type="button" class="jeko-method-btn ${m.disabled ? "cards-disabled" : ""}" data-method="${m.id}" aria-pressed="${this._selectedMethod === m.id}" aria-label="${m.label}">
          <span class="icon-wrapper"><img src="${m.iconUrl}" alt="${m.label}" loading="lazy" /></span>
          <span class="name">${m.label}</span>
          <span class="hint">${m.hint}</span>
        </button>`,
            ).join("");

            return `
        <div class="jeko-header">
          <h3>${t.title}</h3>
          <button type="button" class="jeko-close" aria-label="${t.close}">&times;</button>
        </div>
        <div class="jeko-body">
          <div class="jeko-amount">
            <span class="amount">${formatAmount(amount, this._paymentData.currency)}</span>
            ${description ? `<span class="description">${description}</span>` : ""}
          </div>

          ${this._typeSpecificTemplate()}

          <span class="jeko-section-label">${t.chooseMethod}</span>
          <div class="jeko-methods">${methodsHtml}</div>
        </div>
        <div class="jeko-footer">
          <button type="button" class="jeko-submit" ${this._blocked ? "disabled" : ""}>${t.submit}</button>
        </div>
      `;
        }

        _typeSpecificTemplate() {
            const t = this.options.translations;
            const type = this._paymentData.paymentType;

            if (type === "firstPayment") {
                return this._firstPaymentTemplate();
            }

            if (type === "earlyPayment") {
                if (!this._contractInfo) return this._contractLookupTemplate();
                if (this._blocked) {
                    return `
            <div class="jeko-notice info" style="text-align: center">${this._contractSummaryTemplate()}</div>
            <div class="jeko-notice warn">${t.blockedEarlyPayment}</div>
            <button type="button" class="jeko-link-btn" data-switch="recoveryPrime">${t.switchToRecovery}</button>
          `;
                }
                return `<div class="jeko-notice info" style="text-align: center">${this._contractSummaryTemplate()}</div><span class="jeko-section-label" style="text-align: center">${t.numberOfPrimesLabel}</span>${this._stepperTemplate(1)}`;
            }

            if (type === "recoveryPrime") {
                if (!this._contractInfo) return this._contractLookupTemplate();
                const list = this._contractInfo.facturesImpayees || [];
                if (list.length === 0) {
                    return `
            <div class="jeko-notice info" style="text-align: center">${this._contractSummaryTemplate()}</div>
            <div class="jeko-notice info">${t.noUnpaidInvoices}</div>
            <button type="button" class="jeko-link-btn" data-switch="earlyPayment">${t.switchToEarly}</button>
          `;
                }
                const total = this._computeAmount();
                const itemsHtml = list
                    .map(
                        (f) => `
          <label class="jeko-invoice-item">
            <input type="checkbox" data-invoice="${f.IdPresentation}" ${this._selectedInvoiceIds.has(f.IdPresentation) ? "checked" : ""} />
            <span class="meta">${f.MaDate || ""} — réf ${f.IdPresentation || ""} — ${f.CodePresentation}</span>
            <span class="amount">${formatAmount(f.MontantNet, this._paymentData.currency)}</span>
          </label>`,
                    )
                    .join("");
                return `
          <div class="jeko-notice info" style="text-align: center">${this._contractSummaryTemplate()}</div>
          <span class="jeko-section-label">${t.selectInvoicesLabel}</span>
          <div class="jeko-invoice-list">${itemsHtml}</div>
          <div class="jeko-total-bar"><span>${t.totalLabel}</span><span>${formatAmount(total, this._paymentData.currency)}</span></div>
        `;
            }

            return "";
        }

        _firstPaymentTemplate() {
            const t = this.options.translations;
            const prime = Number(this._contractInfo?.primePrincipale || 0);
            const frais = Number(this._contractInfo?.fraisAdhesion || 0);
            const firstPrimeTotal = frais + prime;
            const additionalTotal = this._additionalPrimes ? prime * this._additionalPrimesCount : 0;
            const totalAmount = firstPrimeTotal + additionalTotal;

            // Si pas d'infos contrat, afficher le formulaire de recherche
            if (!this._contractInfo) {
                return this._contractLookupTemplate();
            }

            return `
            <div class="jeko-notice info" style="text-align: center">${this._contractSummaryTemplate()}</div>
            
            <div class="jeko-breakdown">
                <div class="jeko-breakdown-row">
                    <span>${t.firstPaymentBase}</span>
                    <span>${formatAmount(prime, this._paymentData.currency)}</span>
                </div>
                <div class="jeko-breakdown-row">
                    <span>${t.firstPaymentFees}</span>
                    <span>${formatAmount(frais, this._paymentData.currency)}</span>
                </div>
                <div class="jeko-breakdown-row total">
                    <span>${t.firstPaymentTotal}</span>
                    <span>${formatAmount(firstPrimeTotal, this._paymentData.currency)}</span>
                </div>
                ${this._additionalPrimes ? `
                    ${this._additionalPrimesCount > 0 ? `
                    <div class="jeko-breakdown-row">
                        <span>${t.additionalPrimesTotal} (×${this._additionalPrimesCount})</span>
                        <span>${formatAmount(additionalTotal, this._paymentData.currency)}</span>
                    </div>
                    <div class="jeko-breakdown-row total">
                        <span>${t.totalLabel}</span>
                        <span>${formatAmount(totalAmount, this._paymentData.currency)}</span>
                    </div>
                    ` : ''}
                ` : ''}
            </div>

            <span class="jeko-section-label">${t.additionalPrimesLabel}</span>
            <div class="jeko-toggle-group">
                <button type="button" class="jeko-toggle-btn" data-additional="yes" aria-pressed="${this._additionalPrimes ? 'true' : 'false'}">${t.additionalPrimesYes}</button>
                <button type="button" class="jeko-toggle-btn" data-additional="no" aria-pressed="${!this._additionalPrimes ? 'true' : 'false'}">${t.additionalPrimesNo}</button>
            </div>

            ${this._additionalPrimes ? `
                <div class="jeko-field" style="margin: 12px auto; width: fit-content;">
                    <label for="jeko-additional-count">${t.additionalPrimesCount}</label>
                    <div class="jeko-stepper" data-role="additional-stepper">
                        <button type="button" data-additional-step="-1">−</button>
                        <span>${this._additionalPrimesCount}</span>
                        <button type="button" data-additional-step="1">+</button>
                    </div>
                </div>
            ` : ''}
        `;
        }

        _contractLookupTemplate() {
            const t = this.options.translations;
            return `
        <div class="jeko-field" data-role="contract">
          <label for="jeko-contract">${t.contractIdLabel}</label>
          <input id="jeko-contract" type="text" placeholder="${t.contractIdPlaceholder}" value="${this._paymentData.contractId || ""}" />
          <div class="error-message">${t.requiredField}</div>
        </div>
        <button type="button" class="jeko-submit" data-action="verify-contract" style="margin-top:8px;background:#fff;color:var(--jeko-primary-dark);border:2px solid var(--jeko-primary);">${t.verify}</button>
      `;
        }

        _contractSummaryTemplate() {
            const info = this._contractInfo;
            if (!info) return "";
            return `Contrat n° ${info.idProposition || info.contratIdWeb} — ${info.produit || ""}  — ${info.souscripteur || ""}`;
        }

        _stepperTemplate(min) {
            if (this._numberOfPrimes < min) this._numberOfPrimes = min;
            return `
        <div class="jeko-stepper" data-role="stepper" style="margin: auto; width: fit-content;">
          <button type="button" data-step="-1">−</button>
          <span>${this._numberOfPrimes}</span>
          <button type="button" data-step="1">+</button>
        </div>
      `;
        }

        _bindEvents() {
            const modal = this._modal;
            const t = this.options.translations;

            modal
                .querySelector(".jeko-close")
                .addEventListener("click", () => this.close());

            modal.querySelectorAll(".jeko-method-btn").forEach((btn) => {
                btn.addEventListener("click", () => {
                    modal
                        .querySelectorAll(".jeko-method-btn")
                        .forEach((b) =>
                            b.setAttribute("aria-pressed", "false"),
                        );
                    btn.setAttribute("aria-pressed", "true");
                    this._selectedMethod = btn.dataset.method;
                });
            });

            // Gestion des boutons "Oui/Non" pour les primes supplémentaires
            modal.querySelectorAll("[data-additional]").forEach((btn) => {
                btn.addEventListener("click", () => {
                    const value = btn.dataset.additional;
                    this._additionalPrimes = value === "yes";
                    if (!this._additionalPrimes) {
                        this._additionalPrimesCount = 0;
                    } else if (this._additionalPrimesCount === 0) {
                        this._additionalPrimesCount = 1;
                    }
                    this._render();
                });
            });

            // Stepper pour les primes supplémentaires
            modal.querySelectorAll("[data-additional-step]").forEach((btn) => {
                btn.addEventListener("click", () => {
                    const delta = Number(btn.dataset.additionalStep);
                    const newCount = this._additionalPrimesCount + delta;
                    if (newCount >= 1) {
                        this._additionalPrimesCount = newCount;
                        this._render();
                    }
                });
            });

            modal.querySelectorAll("[data-step]").forEach((btn) => {
                btn.addEventListener("click", () => {
                    const delta = Number(btn.dataset.step);
                    const min =
                        this._paymentData.paymentType === "firstPayment"
                            ? 2
                            : 1;
                    this._numberOfPrimes = Math.max(
                        min,
                        this._numberOfPrimes + delta,
                    );
                    this._render();
                });
            });

            modal.querySelectorAll("[data-switch]").forEach((btn) => {
                btn.addEventListener("click", () => {
                    this._paymentData.paymentType = btn.dataset.switch;
                    this._blocked = false;
                    this._render();
                });
            });

            modal.querySelectorAll("[data-invoice]").forEach((cb) => {
                cb.addEventListener("change", () => {
                    const id = cb.dataset.invoice;
                    if (cb.checked) this._selectedInvoiceIds.add(id);
                    else this._selectedInvoiceIds.delete(id);
                    this._render();
                });
            });

            const verifyBtn = modal.querySelector(
                '[data-action="verify-contract"]',
            );
            if (verifyBtn)
                verifyBtn.addEventListener("click", () =>
                    this._verifyContract(),
                );

            const submitBtn = modal.querySelector(
                ".jeko-submit:not([data-action])",
            );
            if (submitBtn)
                submitBtn.addEventListener("click", () => this._handleSubmit());
        }

        async _verifyContract() {
            if (this._isVerifyingContract) return;

            this._isVerifyingContract = true;

            const t = this.options.translations;
            const input = this._modal.querySelector("#jeko-contract");
            const idContrat = (input?.value || "").trim();
            if (!idContrat) {
                if (input) {
                    input.classList.add("error");
                    const errorMsg = input.parentElement.querySelector(".error-message");
                    if (errorMsg) errorMsg.classList.add("visible");
                }
                this._isVerifyingContract = false;
                return;
            }
            
            const btn = this._modal.querySelector('[data-action="verify-contract"]');
            if (btn) {
                btn.disabled = true;
                btn.textContent = t.verifying;
            }

            try {
                const res = await fetch(this.options.contractCheckEndpoint, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        ...this.options.headers,
                    },
                    body: JSON.stringify({
                        idContrat,
                        paymentType: this._paymentData.paymentType,
                    }),
                });
                const data = await res.json();

                if (!res.ok || !data.success) {
                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = t.verify;
                    }
                    this._showGeneralError(data.message || t.error);
                    this._isVerifyingContract = false;
                    return;
                }

                this._paymentData.contractId = idContrat;
                this._contractInfo = data.data;
                this._blocked = this._paymentData.paymentType === "earlyPayment" && this._contractInfo.aDesImpayes === true;
                this._render();
            } catch (e) {
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = t.verify;
                }
                console.error(e);
                this._showGeneralError(t.networkError);
            } finally {
                this._isVerifyingContract = false;
            }
        }

        _showGeneralError(message) {
            let el = this._modal.querySelector(".jeko-general-error");
            if (!el) {
                el = document.createElement("div");
                el.className = "jeko-notice warn jeko-general-error";
                const body = this._modal.querySelector(".jeko-body");
                if (body) body.appendChild(el);
            }
            el.textContent = message;
        }

        _handleSubmit() {
            const t = this.options.translations;
            if (!this._selectedMethod) {
                this._showGeneralError(
                    "Veuillez choisir un moyen de paiement.",
                );
                return;
            }
            if (this._blocked) return;

            const type = this._paymentData.paymentType;
            if (
                (type === "earlyPayment" || type === "recoveryPrime") &&
                !this._contractInfo
            ) {
                this._showGeneralError(t.requiredField);
                return;
            }
            if (
                type === "recoveryPrime" &&
                this._selectedInvoiceIds.size === 0
            ) {
                this._showGeneralError(t.requiredField);
                return;
            }

            this._submitPayment();
        }

        _renderLoadingState() {
            const t = this.options.translations;
            this._modal.innerHTML = `<div class="jeko-state"><div class="jeko-spinner"></div><p>${t.processing}</p></div>`;
        }

        _renderResultState(type, title, message, options = {}) {
            const t = this.options.translations;
            const icon = type === "success" ? "✅" : "❌";
            this._modal.innerHTML = `
        <div class="jeko-state ${type}">
          <span class="icon">${icon}</span>
          <h4>${title}</h4>
          <p>${message}</p>
          ${options.showRetry ? `<button type="button" class="jeko-retry">${t.retry}</button>` : ""}
        </div>`;
            if (options.showRetry) {
                this._modal
                    .querySelector(".jeko-retry")
                    .addEventListener("click", () => this._render());
            }
        }

        async _submitPayment() {
            if (this._isSubmitting) return;
            this._isSubmitting = true;
            this._renderLoadingState();

            const t = this.options.translations;
            const {
                reference,
                paymentType,
                contractId,
                customerEmail,
                customerName,
                description,
                metadata,
                currency,
            } = this._paymentData;

            let numberOfPrimes = undefined;
            if (paymentType === "firstPayment") {
                // Pour firstPayment, on envoie le nombre total de primes = 1 (première) + primes supplémentaires
                numberOfPrimes = 1 + (this._additionalPrimes ? this._additionalPrimesCount : 0);
            } else if (paymentType === "earlyPayment") {
                numberOfPrimes = this._numberOfPrimes;
            }

            const payload = {
                reference,
                currency: currency || this.options.currency,
                paymentMethod: this._selectedMethod,
                paymentType,
                contractId: contractId || undefined,
                numberOfPrimes: numberOfPrimes,
                selectedInvoiceIds:
                    paymentType === "recoveryPrime"
                        ? Array.from(this._selectedInvoiceIds)
                        : undefined,
                successUrl: this.options.successUrl || window.location.href,
                errorUrl: this.options.errorUrl || window.location.href,
                description,
                customerEmail,
                customerName,
                metadata,
            };

            try {
                const controller = new AbortController();
                const timeout = setTimeout(
                    () => controller.abort(),
                    this.options.timeout,
                );
                const response = await fetch(this.options.backendEndpoint, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        ...this.options.headers,
                    },
                    body: JSON.stringify(payload),
                    signal: controller.signal,
                });
                clearTimeout(timeout);
                const data = await response.json();

                if (!response.ok || !data.success) {
                    this._renderResultState(
                        "error",
                        t.error,
                        data.message || t.error,
                        { showRetry: true },
                    );
                    if (this.options.callbacks.onError)
                        this.options.callbacks.onError(data.message, data);
                    return;
                }

                const redirectUrl = data.data?.redirectUrl;
                if (!redirectUrl) {
                    this._renderResultState(
                        "error",
                        t.error,
                        "Aucune URL de paiement reçue.",
                        { showRetry: true },
                    );
                    return;
                }

                this._renderResultState("success", t.success, t.successMessage);
                if (this.options.callbacks.onSuccess)
                    this.options.callbacks.onSuccess(redirectUrl, data);
                setTimeout(() => {
                    window.open(redirectUrl, "_blank");
                }, 1500);
            } catch (error) {
                const msg =
                    error.name === "AbortError"
                        ? "La requête a expiré."
                        : error.message || t.error;
                this._renderResultState("error", t.networkError, msg, {
                    showRetry: true,
                });
                if (this.options.callbacks.onError)
                    this.options.callbacks.onError(msg, null);
            } finally {
                this._isSubmitting = false;
            }
        }

        isOpen() {
            return this._isOpen;
        }
        getSelectedMethod() {
            return this._selectedMethod;
        }
        getPaymentData() {
            return this._paymentData;
        }
    }

    window.JekoWidget = JekoWidget;
})(window, document);