/**
 * Wave Payment Widget — v2 (modulable)
 * ------------------------------------------------------------------
 * Parcours par défaut :
 *   1. Type de paiement   (masqué si le type est imposé)
 *   2. Contrat / Factures (vérification du contrat, puis sélection)
 *   3. Récapitulatif      (masquable avec skipSummary)
 *
 * Nouveautés v2 :
 *   - Ouverture directe sur n'importe quelle étape (startStep).
 *   - Données du contrat et factures passées en paramètre (contract, invoices)
 *     → aucun appel de vérification, ouverture instantanée sur la sélection.
 *   - Présélection des factures (preselect), règlement chronologique (sequentialInvoices).
 *   - Verrouillage du type et du contrat (lockPaymentType, lockContract).
 *   - Affichage en fenêtre modale OU intégré dans un élément de la page (mount).
 *   - Ouverture depuis des boutons HTML via data-attributes (WaveWidget.attach).
 *   - Callbacks supplémentaires (onStepChange, onContractLoaded, onSelectionChange, onBeforeSubmit).
 *   - Thème par instance (plusieurs widgets avec des thèmes différents sur une même page).
 *
 * Rétrocompatible avec la v1 : defaultPaymentType, defaultContractId,
 * autoVerifyContract, contractId, description, customerName… restent valables.
 *
 * ⚠️ SÉCURITÉ : le widget n'appelle JAMAIS l'API Wave directement. Les montants
 * affichés sont indicatifs : le backend Laravel recalcule toujours le montant réel.
 * ------------------------------------------------------------------
 */
(function (window, document) {
    "use strict";

    const VERSION = "2.0.0";

    const PAYMENT_TYPES = {
        firstPayment: "firstPayment",
        earlyPayment: "earlyPayment",
        recoveryPrime: "recoveryPrime",
    };

    /** Étapes internes et alias acceptés pour startStep / goTo(). */
    const STEP_ORDER = ["type", "contract", "summary"];
    const STEP_ALIASES = {
        type: "type",
        contract: "contract",
        contrat: "contract",
        invoices: "contract",
        factures: "contract",
        primes: "contract",
        amount: "contract",
        montant: "contract",
        summary: "summary",
        recap: "summary",
        resume: "summary",
    };

    const DEFAULT_OPTIONS = {
        // ---- Backend ----
        backendEndpoint: "/api/wave/paiements/init",
        contractCheckEndpoint: "/api/wave/paiements/contrat/verifier",
        currency: "XOF",
        timeout: 30000,
        csrfToken: null,
        headers: {},

        // ---- Parcours ----
        paymentType: null,          // 'firstPayment' | 'earlyPayment' | 'recoveryPrime'
        contractId: null,           // identifiant du contrat
        startStep: null,            // 'type' | 'contract' | 'invoices' | 'summary'
        autoVerifyContract: null,   // null = automatique (oui si startStep est fourni)
        contract: null,             // données du contrat déjà chargées (même format que l'API de vérification)
        contractStatus: "found",    // 'found' | 'not_found' (avec contract)
        invoices: null,             // factures impayées (remplace contract.facturesImpayees)
        preselect: "none",          // 'none' | 'all' | 'first' | [ids]
        sequentialInvoices: false,  // oblige à régler de la plus ancienne à la plus récente
        lockPaymentType: null,      // null = automatique
        lockContract: null,         // null = automatique (oui si contract est fourni)
        skipSummary: false,         // paie directement depuis l'étape contrat/factures
        maxPrimes: 60,

        // ---- Affichage ----
        mount: null,                // null = modale ; Element ou sélecteur CSS = intégré dans la page
        showStepper: true,
        closable: true,
        closeOnOverlayClick: true,
        closeOnEscape: true,
        lockBodyScroll: true,
        leftLogo: null,
        rightLogo: null,
        leftLogoAlt: "Logo",
        rightLogoAlt: "Wave",
        contextTitle: null,

        // ---- Soumission ----
        autoRedirect: true,
        redirectDelay: 1200,
        generateReference: null,    // function () => string (lettres, chiffres, - et _)
        extraPayload: null,         // champs additionnels envoyés au backend
        description: null,
        customerName: null,
        customerEmail: null,
        metadata: null,

        theme: {
            primary: "#033f1f",
            primaryDark: "#033f1f",
            accent: "#FFCE00",
            radius: "16px",
            fontFamily: "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
            maxWidth: "550px",
        },
        translations: {
            title: "Paiement Wave",
            close: "Fermer",
            stepType: "Type",
            stepContract: "Contrat",
            stepInvoices: "Factures",
            stepPrimes: "Primes",
            stepAmount: "Montant",
            stepSummary: "Résumé",
            chooseType: "Que voulez-vous payer ?",
            typeFirstPayment: "Payer ma 1ère prime",
            typeEarlyPayment: "Payer en avance",
            typeRecoveryPrime: "Régulariser mes impayés",
            contractIdLabel: "Identifiant du contrat",
            contractIdPlaceholder: "Ex: 345678",
            verify: "Vérifier le contrat",
            verifying: "Vérification en cours...",
            requiredField: "Ce champ est requis",
            contractNotFound: "Contrat introuvable.",
            contractHasUnpaid: "Ce contrat a des primes impayées.",
            contractNoUnpaid: "Ce contrat n'a aucune prime impayée.",
            switchToRecovery: "Régulariser mes impayés",
            backToType: "Choisir un autre type",
            changeContract: "Changer de contrat",
            manualAmountLabel: "Montant à payer (FCFA)",
            manualAmountHint: "Saisissez le montant de la 1ère prime",
            numberOfPrimesLabel: "Nombre de primes à régler",
            selectInvoicesLabel: "Sélectionnez les primes à régulariser",
            sequentialHint: "Les primes se règlent de la plus ancienne à la plus récente.",
            selectAll: "Tout cocher",
            unselectAll: "Tout décocher",
            selectedCount: "{count} sélectionnée(s)",
            totalLabel: "Total à régler",
            contractSummary: "Contrat n° {id}",
            subscriber: "Souscripteur",
            product: "Produit",
            primePrincipale: "Prime principale",
            fraisAdhesion: "Frais d'adhésion",
            unpaidInvoices: "{count} facture(s) impayée(s)",
            invoiceRef: "réf",
            continue: "Continuer",
            back: "Retour",
            confirmAndPay: "Confirmer et payer",
            payNow: "Payer maintenant",
            summaryTitle: "Vérifiez votre paiement",
            summaryContract: "Contrat",
            summaryType: "Type de paiement",
            summaryInvoices: "Factures",
            summaryPrimes: "Nombre de primes",
            summaryAmount: "Montant à payer",
            processing: "Initialisation du paiement...",
            success: "Paiement initialisé",
            successMessage: "Vous allez être redirigé vers l'application Wave...",
            error: "Échec du paiement",
            networkError: "Erreur réseau",
            timeoutError: "La requête a expiré.",
            noRedirectUrl: "Aucune URL Wave reçue du serveur.",
            retry: "Réessayer",
            cancel: "Annuler",
        },
        callbacks: {
            onOpen: null,             // (config)
            onClose: null,            // ()
            onStepChange: null,       // (step, paymentData)
            onContractLoaded: null,   // (contractInfo, status)
            onSelectionChange: null,  // (selectedIds, total)
            onBeforeSubmit: null,     // (payload) → false pour annuler, ou payload modifié (peut être async)
            onSuccess: null,          // (redirectUrl, response)
            onError: null,            // (message, response)
        },
    };

    // ------------------------------------------------------------------
    // Styles (injectés une seule fois ; les couleurs sont propres à chaque instance)
    // ------------------------------------------------------------------
    const STYLES = `
      .wave-root {
        font-family:var(--wave-font);
        color:#111827; line-height:1.4; text-align:left;
      }
      .wave-root *, .wave-root *::before, .wave-root *::after { box-sizing:border-box; }
      .wave-overlay {
        position:fixed; inset:0;
        background:rgba(8,12,20,.65);
        display:flex; align-items:center; justify-content:center;
        z-index:999999; padding:20px;
        backdrop-filter:blur(8px);
        animation: wave-fade-in .2s ease;
      }
      .wave-inline { position:relative; width:100%; display:flex; justify-content:center; }
      .wave-inline .wave-modal { max-height:none; box-shadow:0 10px 40px rgba(0,0,0,.08); animation:none; }
      @keyframes wave-fade-in { from { opacity:0 } to { opacity:1 } }
      @keyframes wave-slide-up { from { opacity:0; transform:translateY(12px) } to { opacity:1; transform:translateY(0) } }
      @keyframes wave-pop { 0% { transform:scale(.85); opacity:0 } 60% { transform:scale(1.05) } 100% { transform:scale(1); opacity:1 } }
      @keyframes wave-spin { to { transform:rotate(360deg); } }
      @keyframes wave-check-pop { 0% { transform:scale(0) rotate(-20deg); opacity:0 } 100% { transform:scale(1) rotate(0); opacity:1 } }


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

      /* ---- Ajouts v2 ---- */
      .wave-type-title { font-size:14px; font-weight:700; color:#374151; margin:0 0 16px; text-align:center; }
      .wave-section-title { font-size:13px; font-weight:700; color:#374151; }
      .wave-hint-line { font-size:12px; color:#6b7280; margin:0 0 10px; }
      .wave-invoice-item.is-locked { opacity:.55; }
      .wave-invoice-item .date { font-weight:700; color:#111827; }
      .wave-selected-count { font-size:12px; font-weight:600; opacity:.8; margin-left:6px; }
      .wave-verify-btn {
        margin:18px 0 0; width:100%; background:#fff; color:var(--wave-primary-dark);
        border:2px solid var(--wave-primary); box-shadow:none;
      }
      .wave-state.compact { min-height:180px; padding:30px 20px; }
      .wave-modal:focus { outline:none; }
      @media (max-width:480px) {
        .wave-overlay { padding:0; align-items:flex-end; }
        .wave-overlay .wave-modal { border-radius:var(--wave-radius) var(--wave-radius) 0 0; max-height:96vh; }
        .wave-body { padding:20px 18px; }
        .wave-footer { padding:14px 18px 18px; }
      }
    `;

    function injectStyles() {
        if (document.getElementById("wave-widget-styles")) return;
        const style = document.createElement("style");
        style.id = "wave-widget-styles";
        style.textContent = STYLES;
        document.head.appendChild(style);
    }

    // ------------------------------------------------------------------
    // Utilitaires
    // ------------------------------------------------------------------
    let instanceCounter = 0;

    function formatAmount(units, currency) {
        try {
            return new Intl.NumberFormat("fr-FR", {
                style: "currency",
                currency: currency || "XOF",
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            }).format(Number(units) || 0);
        } catch {
            return `${Number(units) || 0} ${currency || ""}`;
        }
    }

    function escapeHtml(str) {
        return String(str == null ? "" : str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#39;");
    }

    function tpl(text, values) {
        return String(text || "").replace(/\{(\w+)\}/g, (_, k) => (values[k] == null ? "" : values[k]));
    }

    function normalizeStep(step) {
        if (!step) return null;
        return STEP_ALIASES[String(step).toLowerCase()] || null;
    }

    /** Supprime les clés undefined pour que les valeurs par défaut s'appliquent. */
    function defined(obj) {
        const out = {};
        Object.keys(obj || {}).forEach((k) => {
            if (obj[k] !== undefined) out[k] = obj[k];
        });
        return out;
    }

    function resolveElement(target) {
        if (!target) return null;
        if (typeof target === "string") return document.querySelector(target);
        return target instanceof Element ? target : null;
    }

    function randomToken(length) {
        const chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
        const out = [];
        const bytes = new Uint8Array(length);
        if (window.crypto && window.crypto.getRandomValues) window.crypto.getRandomValues(bytes);
        else for (let i = 0; i < length; i += 1) bytes[i] = Math.floor(Math.random() * 256);
        for (let i = 0; i < length; i += 1) out.push(chars[bytes[i] % chars.length]);
        return out.join("");
    }

    /**
     * Accepte le format de l'API (IdPresentation, CodePresentation, MaDate, MontantNet)
     * ou un format simplifié (id, code, date, amount).
     */
    function normalizeInvoices(list) {
        if (!Array.isArray(list)) return [];
        return list
            .map((f) => {
                if (!f || typeof f !== "object") return null;
                const id = f.IdPresentation ?? f.id ?? f.idPresentation;
                if (id === undefined || id === null || id === "") return null;
                return {
                    ...f,
                    IdPresentation: String(id),
                    CodePresentation: String(f.CodePresentation ?? f.code ?? f.codePresentation ?? id),
                    MaDate: f.MaDate ?? f.date ?? f.maDate ?? "",
                    MontantNet: Number(f.MontantNet ?? f.amount ?? f.montantNet ?? 0) || 0,
                };
            })
            .filter(Boolean);
    }

    function parseJsonAttr(value) {
        if (!value) return undefined;
        try {
            return JSON.parse(value);
        } catch {
            console.warn("[WaveWidget] data-attribute JSON invalide :", value);
            return undefined;
        }
    }

    function parseBoolAttr(value) {
        if (value === undefined) return undefined;
        return value === "" || value === "true" || value === "1";
    }

    // ------------------------------------------------------------------
    // Widget
    // ------------------------------------------------------------------
    class WaveWidget {
        constructor(options = {}) {
            this.options = WaveWidget._merge(DEFAULT_OPTIONS, options);
            this._uid = `ww${(instanceCounter += 1)}`;
            this._root = null;
            this._modal = null;
            this._isOpen = false;
            this._cfg = null;
            this._previousFocus = null;
            this._previousBodyOverflow = null;

            this._onDocKeydown = this._onDocKeydown.bind(this);
            this._onClick = this._onClick.bind(this);
            this._onChange = this._onChange.bind(this);
            this._onInput = this._onInput.bind(this);
            this._onKeydown = this._onKeydown.bind(this);

            injectStyles();
        }

        static get version() {
            return VERSION;
        }

        /** Fusion profonde des sous-objets theme / translations / callbacks / headers. */
        static _merge(base, extra) {
            const e = defined(extra || {});
            const merged = {
                ...base,
                ...e,
                theme: { ...base.theme, ...(e.theme || {}) },
                headers: { ...base.headers, ...(e.headers || {}) },
                translations: { ...base.translations, ...(e.translations || {}) },
                callbacks: { ...base.callbacks, ...(e.callbacks || {}) },
            };
            // Alias v1
            if (e.defaultPaymentType !== undefined && e.paymentType === undefined) merged.paymentType = e.defaultPaymentType;
            if (e.defaultContractId !== undefined && e.contractId === undefined) merged.contractId = e.defaultContractId;
            return merged;
        }

        // ==============================================================
        // API PUBLIQUE
        // ==============================================================

        /**
         * Ouvre le widget. Toutes les options du constructeur peuvent être surchargées ici.
         *
         * Exemple — ouverture directe sur la sélection des factures :
         *   widget.open({
         *     paymentType: "recoveryPrime",
         *     startStep: "invoices",
         *     contract: { idProposition: "123", souscripteur: "…", facturesImpayees: [...] },
         *     preselect: "all",
         *   });
         */
        open(config = {}) {
            if (this._isOpen) this._teardown(false);

            const c = (this._cfg = WaveWidget._merge(this.options, config));

            this._paymentType = PAYMENT_TYPES[c.paymentType] ? c.paymentType : null;
            this._contractId = c.contractId != null && String(c.contractId).trim() !== ""
                ? String(c.contractId).trim()
                : null;
            this._resetContractState(false);
            this._isVerifyingContract = false;
            this._isSubmitting = false;
            this._generalError = null;

            const preloaded = c.contract && typeof c.contract === "object";
            const start = normalizeStep(c.startStep);

            this._lockType = c.lockPaymentType != null
                ? Boolean(c.lockPaymentType) && Boolean(this._paymentType)
                : Boolean(this._paymentType && (preloaded || (start && start !== "type")));
            this._lockContract = c.lockContract != null ? Boolean(c.lockContract) : Boolean(preloaded);

            if (preloaded) {
                this._applyContract(c.contract, {
                    status: c.contractStatus === "not_found" ? "not_found" : "found",
                    invoices: c.invoices,
                    preselect: c.preselect,
                });
            }

            let step = start || (this._paymentType ? "contract" : "type");
            if (!this._paymentType) step = "type";
            if (step === "summary" && (c.skipSummary || !this._canProceedFromContract())) step = "contract";
            this._step = step;

            this._render();
            this._isOpen = true;

            if (typeof c.callbacks.onOpen === "function") c.callbacks.onOpen(c);
            this._emitStepChange();

            const autoVerify = c.autoVerifyContract != null ? Boolean(c.autoVerifyContract) : Boolean(start);
            if (!preloaded && this._paymentType && this._contractId && autoVerify && this._step === "contract") {
                setTimeout(() => this._verifyContract(), 30);
            }
            return this;
        }

        close() {
            this._teardown(true);
            return this;
        }

        /** Ferme et libère toutes les ressources de l'instance. */
        destroy() {
            this._teardown(false);
            this._cfg = null;
        }

        /** Modifie les options par défaut de l'instance (prises en compte au prochain open()). */
        update(options = {}) {
            this.options = WaveWidget._merge(this.options, options);
            return this;
        }

        /** Va à une étape : 'type' | 'contract' | 'invoices' | 'summary'. */
        goTo(step) {
            const s = normalizeStep(step);
            if (!s || !this._isOpen) return false;
            if (s === "type" && this._lockType) return false;
            if (s !== "type" && !this._paymentType) return false;
            if (s === "summary" && (this._cfg.skipSummary || !this._canProceedFromContract())) return false;
            this._setStep(s);
            return true;
        }

        /** Injecte des données de contrat (évite l'appel de vérification). */
        setContract(data, opts = {}) {
            if (!this._isOpen) return this;
            this._resetContractState(false);
            this._applyContract(data || {}, {
                status: opts.status === "not_found" ? "not_found" : "found",
                invoices: opts.invoices,
                preselect: opts.preselect ?? this._cfg.preselect,
            });
            if (this._step === "type" && this._paymentType) this._step = "contract";
            this._refresh();
            return this;
        }

        /** Remplace la liste des factures affichées. */
        setInvoices(list, preselect) {
            if (!this._isOpen) return this;
            this._contractInfo = { ...(this._contractInfo || {}), facturesImpayees: normalizeInvoices(list) };
            this._contractStatus = this._contractStatus || "found";
            this._selectedInvoiceIds = new Set();
            this._applyPreselect(preselect ?? this._cfg.preselect);
            this._evaluateBlocking();
            this._refresh();
            return this;
        }

        /** Sélectionne des factures : tableau d'identifiants, 'all', 'first' ou 'none'. */
        selectInvoices(selection) {
            if (!this._isOpen) return this;
            this._selectedInvoiceIds = new Set();
            this._applyPreselect(selection);
            this._syncInvoiceCheckboxes();
            this._updateRecoverySelection();
            return this;
        }

        isOpen() {
            return this._isOpen;
        }

        getSelectedTotal() {
            return this._computeAmount();
        }

        getPaymentData() {
            return {
                step: this._step,
                paymentType: this._paymentType,
                contractId: this._contractId,
                contractStatus: this._contractStatus,
                manualAmount: this._manualAmount,
                numberOfPrimes: this._numberOfPrimes,
                selectedInvoiceIds: Array.from(this._selectedInvoiceIds || []),
                amount: this._computeAmount(),
            };
        }

        /**
         * Ouvre le widget au clic sur des éléments HTML, configurés par data-attributes :
         *   data-wave-payment-type, data-wave-contract-id, data-wave-start-step,
         *   data-wave-preselect ("all" | "first" | "none" | "id1,id2"),
         *   data-wave-contract (JSON), data-wave-invoices (JSON),
         *   data-wave-skip-summary, data-wave-sequential, data-wave-auto-verify,
         *   data-wave-customer-name, data-wave-customer-email, data-wave-description,
         *   data-wave-context-title
         *
         * @param {string|Element|NodeList|Element[]} target  Sélecteur (délégation, éléments dynamiques inclus) ou éléments
         * @param {object|WaveWidget} [widgetOrOptions]         Instance existante ou options du constructeur
         * @param {object} [openDefaults]                       Options passées à open() pour tous les boutons
         * @returns {{ widget: WaveWidget, detach: Function }}
         */
        static attach(target, widgetOrOptions = {}, openDefaults = {}) {
            const widget = widgetOrOptions instanceof WaveWidget ? widgetOrOptions : new WaveWidget(widgetOrOptions);

            const ouvrir = (el, evt) => {
                if (evt) evt.preventDefault();
                widget.open({ ...openDefaults, ...WaveWidget.readDataset(el) });
            };

            if (typeof target === "string") {
                const handler = (evt) => {
                    const el = evt.target.closest(target);
                    if (el) ouvrir(el, evt);
                };
                document.addEventListener("click", handler);
                return { widget, detach: () => document.removeEventListener("click", handler) };
            }

            const elements = target instanceof Element ? [target] : Array.from(target || []);
            const handlers = elements.map((el) => {
                const h = (evt) => ouvrir(el, evt);
                el.addEventListener("click", h);
                return [el, h];
            });
            return { widget, detach: () => handlers.forEach(([el, h]) => el.removeEventListener("click", h)) };
        }

        /** Lit la configuration d'ouverture depuis les data-attributes d'un élément. */
        static readDataset(el) {
            const d = (el && el.dataset) || {};
            let preselect = d.wavePreselect;
            if (preselect && !["all", "none", "first"].includes(preselect)) {
                preselect = preselect.split(",").map((s) => s.trim()).filter(Boolean);
            }
            return defined({
                paymentType: d.wavePaymentType,
                contractId: d.waveContractId,
                startStep: d.waveStartStep,
                preselect,
                contract: parseJsonAttr(d.waveContract),
                invoices: parseJsonAttr(d.waveInvoices),
                skipSummary: parseBoolAttr(d.waveSkipSummary),
                sequentialInvoices: parseBoolAttr(d.waveSequential),
                autoVerifyContract: parseBoolAttr(d.waveAutoVerify),
                customerName: d.waveCustomerName,
                customerEmail: d.waveCustomerEmail,
                description: d.waveDescription,
                contextTitle: d.waveContextTitle,
            });
        }

        // ==============================================================
        // CYCLE DE VIE
        // ==============================================================

        _isInline() {
            return Boolean(resolveElement(this._cfg && this._cfg.mount));
        }

        _render() {
            const c = this._cfg;
            const container = resolveElement(c.mount);
            if (c.mount && !container) {
                console.warn("[WaveWidget] Élément mount introuvable, affichage en fenêtre modale.", c.mount);
            }

            this._previousFocus = document.activeElement;

            const root = document.createElement("div");
            root.className = `wave-root ${container ? "wave-inline" : "wave-overlay"}`;
            const th = c.theme;
            root.style.setProperty("--wave-primary", th.primary);
            root.style.setProperty("--wave-primary-dark", th.primaryDark);
            root.style.setProperty("--wave-accent", th.accent);
            root.style.setProperty("--wave-radius", th.radius);
            root.style.setProperty("--wave-max-width", th.maxWidth);
            root.style.setProperty("--wave-font", th.fontFamily);

            const modal = document.createElement("div");
            modal.className = "wave-modal";
            modal.tabIndex = -1;
            modal.setAttribute("role", container ? "region" : "dialog");
            modal.setAttribute("aria-labelledby", `${this._uid}-title`);
            if (!container) modal.setAttribute("aria-modal", "true");
            modal.innerHTML = this._shellTemplate();
            root.appendChild(modal);

            root.addEventListener("click", this._onClick);
            root.addEventListener("change", this._onChange);
            root.addEventListener("input", this._onInput);
            root.addEventListener("keydown", this._onKeydown);

            if (container) {
                container.innerHTML = "";
                container.appendChild(root);
            } else {
                document.body.appendChild(root);
                document.addEventListener("keydown", this._onDocKeydown);
                if (c.lockBodyScroll) {
                    this._previousBodyOverflow = document.body.style.overflow;
                    document.body.style.overflow = "hidden";
                }
                setTimeout(() => modal.focus({ preventScroll: true }), 0);
            }

            this._root = root;
            this._modal = modal;
        }

        _teardown(fireCallback) {
            const wasOpen = this._isOpen;
            if (this._root) {
                this._root.removeEventListener("click", this._onClick);
                this._root.removeEventListener("change", this._onChange);
                this._root.removeEventListener("input", this._onInput);
                this._root.removeEventListener("keydown", this._onKeydown);
                this._root.remove();
            }
            document.removeEventListener("keydown", this._onDocKeydown);
            if (this._previousBodyOverflow !== null) {
                document.body.style.overflow = this._previousBodyOverflow;
                this._previousBodyOverflow = null;
            }
            if (this._previousFocus && typeof this._previousFocus.focus === "function" && document.contains(this._previousFocus)) {
                this._previousFocus.focus({ preventScroll: true });
            }
            this._root = null;
            this._modal = null;
            this._isOpen = false;
            if (fireCallback && wasOpen && this._cfg && typeof this._cfg.callbacks.onClose === "function") {
                this._cfg.callbacks.onClose();
            }
        }

        // ==============================================================
        // GABARITS
        // ==============================================================

        _t() {
            return this._cfg.translations;
        }

        _visibleSteps() {
            return STEP_ORDER.filter((s) => {
                if (s === "type" && this._lockType) return false;
                if (s === "summary" && this._cfg.skipSummary) return false;
                return true;
            });
        }

        _stepLabel(step) {
            const t = this._t();
            if (step === "type") return t.stepType;
            if (step === "summary") return t.stepSummary;
            if (this._contractInfo && this._contractStatus === "found") {
                if (this._paymentType === "recoveryPrime") return t.stepInvoices;
                if (this._paymentType === "earlyPayment") return t.stepPrimes;
            }
            if (this._paymentType === "firstPayment" && this._contractStatus === "not_found") return t.stepAmount;
            return t.stepContract;
        }

        _shellTemplate() {
            return `
                <div class="wave-header" data-role="header">${this._headerTemplate()}</div>
                <div class="wave-body" data-role="body">${this._bodyTemplate()}</div>
                <div class="wave-footer" data-role="footer">${this._footerTemplate()}</div>`;
        }

        _headerTemplate() {
            const c = this._cfg;
            const t = this._t();
            const logo = (url, alt, cls) => (url ? `<img src="${escapeHtml(url)}" alt="${escapeHtml(alt)}" class="${cls}" />` : "<span></span>");
            const showClose = c.closable && !this._isInline();
            const ctx = c.contextTitle
                ? `<span class="sep">-</span><span class="ctx">${escapeHtml(c.contextTitle)}</span>`
                : "";
            const hasLogos = c.leftLogo || c.rightLogo;
            return `
                <div class="wave-header-top">
                    ${hasLogos ? `<div class="wave-header-logos">${logo(c.leftLogo, c.leftLogoAlt, "logo-left")}${logo(c.rightLogo, c.rightLogoAlt, "logo-right")}</div>` : "<span></span>"}
                    ${showClose ? `<button type="button" class="wave-close" data-action="close" aria-label="${escapeHtml(t.close)}">&times;</button>` : ""}
                </div>
                <div class="wave-header-title" id="${this._uid}-title">${escapeHtml(t.title)}${ctx}</div>
                ${this._stepperTemplate()}`;
        }

        _stepperTemplate() {
            const steps = this._visibleSteps();
            if (!this._cfg.showStepper || steps.length < 2) return "";
            const current = Math.max(0, steps.indexOf(this._step));
            return `
                <div class="wave-steps" aria-hidden="true">
                    ${steps.map((s, i) => {
                        const state = i === current ? "active" : i < current ? "done" : "";
                        const content = i < current ? `<span class="checkmark">✓</span>` : String(i + 1);
                        return `
                        <div class="wave-step ${state}">
                            <div class="wave-step-track">
                                <div class="wave-step-circle">${content}</div>
                                ${i < steps.length - 1 ? `<div class="wave-step-line ${i < current ? "done" : ""}"></div>` : ""}
                            </div>
                            <span class="wave-step-label">${escapeHtml(this._stepLabel(s))}</span>
                        </div>`;
                    }).join("")}
                </div>`;
        }

        _bodyTemplate() {
            let html;
            if (this._step === "type") html = this._typeTemplate();
            else if (this._step === "contract") html = this._contractTemplate();
            else html = this._summaryTemplate();
            if (this._generalError) {
                html += `<div class="wave-notice warn wave-general-error" role="alert">${escapeHtml(this._generalError)}</div>`;
            }
            return html;
        }

        _footerTemplate() {
            const t = this._t();
            const c = this._cfg;
            const cancel = c.closable && !this._isInline()
                ? `<button type="button" class="wave-btn-back" data-action="close">${escapeHtml(t.cancel)}</button>`
                : "";

            if (this._step === "type") return cancel;

            if (this._step === "contract") {
                if (this._isVerifyingContract) return "";

                if (this._blocked) {
                    if (this._blockedReason === "has_unpaid" && this._paymentType === "earlyPayment") {
                        return `<button type="button" class="wave-submit" data-action="switch-recovery">${escapeHtml(t.switchToRecovery)}</button>`;
                    }
                    if (!this._lockType) return `<button type="button" class="wave-submit" data-action="go-type">${escapeHtml(t.backToType)}</button>`;
                    if (!this._lockContract) return `<button type="button" class="wave-submit" data-action="change-contract">${escapeHtml(t.changeContract)}</button>`;
                    return cancel;
                }

                const back = !this._lockType
                    ? `<button type="button" class="wave-btn-back" data-action="go-type">${escapeHtml(t.back)}</button>`
                    : cancel;

                if (!this._contractInfo) return back;

                const ok = this._canProceedFromContract();
                const next = c.skipSummary
                    ? `<button type="button" class="wave-submit" data-action="submit" ${ok ? "" : "disabled"}>${escapeHtml(t.payNow)}</button>`
                    : `<button type="button" class="wave-submit" data-action="go-summary" ${ok ? "" : "disabled"}>${escapeHtml(t.continue)}</button>`;
                return back + next;
            }

            return `
                <button type="button" class="wave-btn-back" data-action="go-contract">${escapeHtml(t.back)}</button>
                <button type="button" class="wave-submit" data-action="submit">${escapeHtml(t.confirmAndPay)}</button>`;
        }

        // ---------------- Étape « type » ----------------

        _typeTemplate() {
            const t = this._t();
            const btn = (type, icon, label) => `
                <button type="button" class="wave-type-btn" data-type="${type}" aria-pressed="${this._paymentType === type}">
                    <span class="icon">${icon}</span>${escapeHtml(label)}
                </button>`;
            return `
                <div class="wave-type-title">${escapeHtml(t.chooseType)}</div>
                <div class="wave-type-options">
                    ${btn("firstPayment", "1️⃣", t.typeFirstPayment)}
                    ${btn("earlyPayment", "📅", t.typeEarlyPayment)}
                    ${btn("recoveryPrime", "⚠️", t.typeRecoveryPrime)}
                </div>`;
        }

        // ---------------- Étape « contrat / factures » ----------------

        _contractTemplate() {
            const t = this._t();

            if (this._blocked) {
                return `
                    <div class="wave-state compact">
                        <span class="icon">⛔</span>
                        <h4>${escapeHtml(this._blockedTitle())}</h4>
                        <p>${escapeHtml(this._blockedMessage || "")}</p>
                    </div>`;
            }

            if (this._isVerifyingContract) {
                return `
                    <div class="wave-state compact" role="status">
                        <div class="wave-spinner"></div>
                        <p>${escapeHtml(t.verifying)}</p>
                    </div>`;
            }

            if (this._contractInfo) return this._contractVerifiedTemplate();

            const inputId = `${this._uid}-contract`;
            return `
                <div class="wave-lookup-icon">📄</div>
                <div class="wave-field">
                    <label for="${inputId}">${escapeHtml(t.contractIdLabel)}</label>
                    <input id="${inputId}" data-role="contract-input" type="text" autocomplete="off"
                           placeholder="${escapeHtml(t.contractIdPlaceholder)}"
                           value="${escapeHtml(this._contractId || "")}" />
                    <div class="error-message">${escapeHtml(t.requiredField)}</div>
                </div>
                <button type="button" class="wave-submit wave-verify-btn" data-action="verify">${escapeHtml(t.verify)}</button>`;
        }

        _blockedTitle() {
            const t = this._t();
            if (this._blockedReason === "has_unpaid") return t.contractHasUnpaid;
            if (this._blockedReason === "no_unpaid") return t.contractNoUnpaid;
            if (this._blockedReason === "not_found") return t.contractNotFound;
            return t.error;
        }

        _contractVerifiedTemplate() {
            const t = this._t();
            const cur = this._cfg.currency;
            const info = this._contractInfo || {};
            const prime = Number(info.primePrincipale || 0);
            const frais = Number(info.fraisAdhesion || 0);

            let body = `<div class="wave-notice info">${this._contractSummaryHtml()}`;
            if (!this._lockContract) {
                body += `<br><button type="button" class="wave-link-btn" data-action="change-contract">${escapeHtml(t.changeContract)}</button>`;
            }
            body += "</div>";

            if (this._paymentType === "firstPayment" && this._contractStatus === "found") {
                return `${body}
                    <div class="wave-breakdown">
                        <div class="wave-breakdown-row"><span>${escapeHtml(t.primePrincipale)}</span><span>${formatAmount(prime, cur)}</span></div>
                        <div class="wave-breakdown-row"><span>${escapeHtml(t.fraisAdhesion)}</span><span>${formatAmount(frais, cur)}</span></div>
                        <div class="wave-breakdown-row total"><span>${escapeHtml(t.totalLabel)}</span><span>${formatAmount(prime + frais, cur)}</span></div>
                    </div>`;
            }

            if (this._paymentType === "firstPayment" && this._contractStatus === "not_found") {
                const id = `${this._uid}-manual`;
                return `${body}
                    <div class="wave-field">
                        <label for="${id}">${escapeHtml(t.manualAmountLabel)}</label>
                        <input id="${id}" data-role="manual-amount" type="number" min="1" step="1" inputmode="numeric"
                               placeholder="Ex: 15000" value="${escapeHtml(this._manualAmount || "")}" />
                        <span class="hint">${escapeHtml(t.manualAmountHint)}</span>
                    </div>`;
            }

            if (this._paymentType === "earlyPayment" && this._contractStatus === "found") {
                return `${body}
                    <div class="wave-section-title" style="text-align:center;margin:14px 0 10px;">${escapeHtml(t.numberOfPrimesLabel)}</div>
                    <div class="wave-stepper">
                        <button type="button" data-prime-step="-1" aria-label="Moins">−</button>
                        <span data-role="primes-count" aria-live="polite">${this._numberOfPrimes}</span>
                        <button type="button" data-prime-step="1" aria-label="Plus">+</button>
                    </div>
                    <div class="wave-total-bar">
                        <span>${escapeHtml(t.totalLabel)}</span>
                        <span data-role="primes-total">${formatAmount(prime * this._numberOfPrimes, cur)}</span>
                    </div>`;
            }

            if (this._paymentType === "recoveryPrime" && this._contractStatus === "found") {
                return body + this._invoicesTemplate();
            }

            return body;
        }

        _invoicesTemplate() {
            const t = this._t();
            const cur = this._cfg.currency;
            const list = this._invoices();
            const allChecked = list.length > 0 && list.every((f) => this._selectedInvoiceIds.has(f.IdPresentation));

            const items = list.map((f, i) => {
                const id = f.IdPresentation;
                const checked = this._selectedInvoiceIds.has(id);
                const cbId = `${this._uid}-inv-${i}`;
                return `
                    <label class="wave-invoice-item" for="${cbId}">
                        <input id="${cbId}" type="checkbox" data-invoice="${escapeHtml(id)}" ${checked ? "checked" : ""} />
                        <span class="meta">
                            <span class="date">${escapeHtml(f.MaDate || "")}</span>
                            — ${escapeHtml(t.invoiceRef)} ${escapeHtml(f.CodePresentation || id)}
                        </span>
                        <span class="amount">${formatAmount(f.MontantNet, cur)}</span>
                    </label>`;
            }).join("");

            return `
                <div class="wave-invoice-header">
                    <div class="wave-section-title">${escapeHtml(t.selectInvoicesLabel)}</div>
                    <button type="button" class="wave-link-btn" data-action="toggle-all">${escapeHtml(allChecked ? t.unselectAll : t.selectAll)}</button>
                </div>
                ${this._cfg.sequentialInvoices ? `<p class="wave-hint-line">${escapeHtml(t.sequentialHint)}</p>` : ""}
                <div class="wave-invoice-list" role="group" aria-label="${escapeHtml(t.selectInvoicesLabel)}">${items}</div>
                <div class="wave-total-bar">
                    <span>${escapeHtml(t.totalLabel)}<span class="wave-selected-count" data-role="recovery-count">${escapeHtml(tpl(t.selectedCount, { count: this._selectedInvoiceIds.size }))}</span></span>
                    <span data-role="recovery-total" aria-live="polite">${formatAmount(this._computeRecoveryTotal(), cur)}</span>
                </div>`;
        }

        _contractSummaryHtml() {
            const info = this._contractInfo || {};
            const t = this._t();
            const id = info.idProposition || info.contratIdWeb || this._contractId || "—";
            const nbImpayes = this._invoices().length;

            let html = `<strong>${escapeHtml(tpl(t.contractSummary, { id }))}</strong>`;
            if (info.souscripteur) html += `<br>${escapeHtml(t.subscriber)} : ${escapeHtml(info.souscripteur)}`;
            if (info.produit) html += `<br>${escapeHtml(t.product)} : ${escapeHtml(info.produit)}`;
            if (nbImpayes > 0) html += `<br>⚠️ <strong>${escapeHtml(tpl(t.unpaidInvoices, { count: nbImpayes }))}</strong>`;
            return html;
        }

        // ---------------- Étape « récapitulatif » ----------------

        _summaryTemplate() {
            const t = this._t();
            const info = this._contractInfo || {};
            const contractLabel = info.idProposition || info.contratIdWeb || this._contractId || "—";
            const typeLabels = {
                firstPayment: t.typeFirstPayment,
                earlyPayment: t.typeEarlyPayment,
                recoveryPrime: t.typeRecoveryPrime,
            };

            let detail = "";
            if (this._paymentType === "recoveryPrime") {
                detail = `
                    <div class="wave-summary-row">
                        <span class="label">${escapeHtml(t.summaryInvoices)}</span>
                        <span class="value">${this._selectedInvoiceIds.size}</span>
                    </div>`;
            } else if (this._paymentType === "earlyPayment") {
                detail = `
                    <div class="wave-summary-row">
                        <span class="label">${escapeHtml(t.summaryPrimes)}</span>
                        <span class="value">${this._numberOfPrimes}</span>
                    </div>`;
            }

            return `
                <p style="text-align:center;color:#6b7280;font-size:13px;margin:0 0 16px;">${escapeHtml(t.summaryTitle)}</p>
                <div class="wave-summary-card">
                    <div class="wave-summary-row">
                        <span class="label">${escapeHtml(t.summaryContract)}</span>
                        <span class="value">${escapeHtml(contractLabel)}</span>
                    </div>
                    <div class="wave-summary-row">
                        <span class="label">${escapeHtml(t.summaryType)}</span>
                        <span class="value">${escapeHtml(typeLabels[this._paymentType] || "—")}</span>
                    </div>
                    ${detail}
                    <div class="wave-summary-total">
                        <div class="label">${escapeHtml(t.summaryAmount)}</div>
                        <div class="amount">${formatAmount(this._computeAmount(), this._cfg.currency)}</div>
                    </div>
                </div>`;
        }

        // ==============================================================
        // RAFRAÎCHISSEMENT
        // ==============================================================

        _refresh() {
            if (!this._modal) return;
            const body = this._modal.querySelector('[data-role="body"]');
            const footer = this._modal.querySelector('[data-role="footer"]');
            const header = this._modal.querySelector('[data-role="header"]');
            if (!body || !footer || !header) {
                this._modal.innerHTML = this._shellTemplate();
                return;
            }
            const bodyScroll = body.scrollTop;
            const listScroll = body.querySelector(".wave-invoice-list")?.scrollTop || 0;

            header.innerHTML = this._headerTemplate();
            body.innerHTML = this._bodyTemplate();
            footer.innerHTML = this._footerTemplate();

            body.scrollTop = bodyScroll;
            const list = body.querySelector(".wave-invoice-list");
            if (list) list.scrollTop = listScroll;
        }

        _setStep(step) {
            this._step = step;
            this._generalError = null;
            this._refresh();
            const body = this._modal && this._modal.querySelector('[data-role="body"]');
            if (body) body.scrollTop = 0;
            this._emitStepChange();
        }

        _emitStepChange() {
            const cb = this._cfg && this._cfg.callbacks.onStepChange;
            if (typeof cb === "function") cb(this._step, this.getPaymentData());
        }

        _syncContinueButton() {
            const ok = this._canProceedFromContract();
            this._modal?.querySelectorAll('[data-action="go-summary"], [data-role="footer"] [data-action="submit"]').forEach((b) => {
                if (this._step === "contract") b.disabled = !ok;
            });
        }

        _syncInvoiceCheckboxes() {
            this._modal?.querySelectorAll("[data-invoice]").forEach((cb) => {
                cb.checked = this._selectedInvoiceIds.has(cb.dataset.invoice);
            });
        }

        /** Mise à jour ciblée (sans re-rendu) après un changement de sélection. */
        _updateRecoverySelection() {
            if (!this._modal) return;
            const t = this._t();
            const list = this._invoices();
            const allChecked = list.length > 0 && list.every((f) => this._selectedInvoiceIds.has(f.IdPresentation));
            const total = this._computeRecoveryTotal();

            const totalEl = this._modal.querySelector('[data-role="recovery-total"]');
            if (totalEl) totalEl.textContent = formatAmount(total, this._cfg.currency);
            const countEl = this._modal.querySelector('[data-role="recovery-count"]');
            if (countEl) countEl.textContent = tpl(t.selectedCount, { count: this._selectedInvoiceIds.size });
            const toggle = this._modal.querySelector('[data-action="toggle-all"]');
            if (toggle) toggle.textContent = allChecked ? t.unselectAll : t.selectAll;

            this._syncContinueButton();

            const cb = this._cfg.callbacks.onSelectionChange;
            if (typeof cb === "function") cb(Array.from(this._selectedInvoiceIds), total);
        }

        // ==============================================================
        // ÉTAT DU CONTRAT
        // ==============================================================

        _invoices() {
            return (this._contractInfo && this._contractInfo.facturesImpayees) || [];
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
            this._blockedMessage = null;
            if (resetContractId) this._contractId = null;
        }

        /** Applique des données de contrat (issues de l'API ou passées en paramètre). */
        _applyContract(data, { status = "found", invoices, preselect, message } = {}) {
            const info = { ...(data || {}) };
            info.facturesImpayees = normalizeInvoices(invoices !== undefined && invoices !== null ? invoices : info.facturesImpayees);

            this._contractInfo = info;
            this._contractStatus = status;
            if (!this._contractId) {
                const id = info.idProposition || info.contratIdWeb;
                if (id) this._contractId = String(id);
            }
            this._blockedMessage = message || null;
            this._evaluateBlocking(data);
            if (!this._blocked) this._applyPreselect(preselect);

            const cb = this._cfg.callbacks.onContractLoaded;
            if (typeof cb === "function") cb(this._contractInfo, this._contractStatus);
        }

        _evaluateBlocking(rawData) {
            const info = this._contractInfo || {};
            this._hasUnpaid = (rawData && rawData.aDesImpayes === true) || this._invoices().length > 0;
            this._blocked = false;
            this._blockedReason = null;

            if (this._paymentType === "earlyPayment") {
                if (this._contractStatus === "not_found") this._block("not_found");
                else if (this._hasUnpaid) this._block("has_unpaid");
            } else if (this._paymentType === "recoveryPrime") {
                if (this._contractStatus === "not_found") this._block("not_found");
                else if (this._invoices().length === 0) this._block("no_unpaid");
            }
            return info;
        }

        _block(reason) {
            this._blocked = true;
            this._blockedReason = reason;
        }

        _applyPreselect(preselect) {
            const list = this._invoices();
            if (!list.length || !preselect || preselect === "none") return;
            if (preselect === "all") list.forEach((f) => this._selectedInvoiceIds.add(f.IdPresentation));
            else if (preselect === "first") this._selectedInvoiceIds.add(list[0].IdPresentation);
            else if (Array.isArray(preselect)) {
                const voulus = new Set(preselect.map(String));
                list.forEach((f) => { if (voulus.has(f.IdPresentation)) this._selectedInvoiceIds.add(f.IdPresentation); });
            }
            if (this._cfg.sequentialInvoices) this._enforceSequence();
        }

        /** Mode chronologique : la sélection doit être un bloc continu depuis la première facture. */
        _enforceSequence(changedId, checked) {
            const ids = this._invoices().map((f) => f.IdPresentation);
            let limit;
            if (changedId !== undefined) {
                const idx = ids.indexOf(changedId);
                limit = checked ? idx : idx - 1;
            } else {
                limit = -1;
                for (let i = 0; i < ids.length; i += 1) {
                    if (this._selectedInvoiceIds.has(ids[i])) limit = i;
                }
            }
            this._selectedInvoiceIds = new Set(ids.slice(0, limit + 1));
        }

        // ==============================================================
        // CALCULS
        // ==============================================================

        _computeRecoveryTotal() {
            return this._invoices()
                .filter((f) => this._selectedInvoiceIds.has(f.IdPresentation))
                .reduce((s, f) => s + Number(f.MontantNet || 0), 0);
        }

        _computeAmount() {
            const info = this._contractInfo || {};
            const prime = Number(info.primePrincipale || 0);
            const frais = Number(info.fraisAdhesion || 0);
            if (this._paymentType === "firstPayment") {
                return this._contractStatus === "not_found" ? Number(this._manualAmount || 0) : prime + frais;
            }
            if (this._paymentType === "earlyPayment") return prime * this._numberOfPrimes;
            if (this._paymentType === "recoveryPrime") return this._computeRecoveryTotal();
            return 0;
        }

        _canProceedFromContract() {
            if (this._blocked || !this._contractInfo) return false;
            if (this._paymentType === "firstPayment") return this._computeAmount() > 0;
            if (this._paymentType === "earlyPayment") return this._contractStatus === "found" && this._computeAmount() > 0;
            if (this._paymentType === "recoveryPrime") return this._selectedInvoiceIds.size > 0 && this._computeAmount() > 0;
            return false;
        }

        // ==============================================================
        // ÉVÉNEMENTS (délégation sur la racine : aucun ré-abonnement)
        // ==============================================================

        _onDocKeydown(e) {
            if (e.key === "Escape" && this._cfg.closable && this._cfg.closeOnEscape && !this._isSubmitting) this.close();
        }

        _onKeydown(e) {
            if (e.key === "Enter" && e.target.matches('[data-role="contract-input"]')) {
                e.preventDefault();
                this._verifyContract();
            }
        }

        _onInput(e) {
            if (e.target.matches('[data-role="manual-amount"]')) {
                this._manualAmount = e.target.value;
                this._syncContinueButton();
            }
            if (e.target.matches('[data-role="contract-input"]')) {
                e.target.classList.remove("error");
                e.target.parentElement.querySelector(".error-message")?.classList.remove("visible");
            }
        }

        _onChange(e) {
            const cb = e.target.closest("[data-invoice]");
            if (!cb) return;
            const id = cb.dataset.invoice;
            if (cb.checked) this._selectedInvoiceIds.add(id);
            else this._selectedInvoiceIds.delete(id);
            if (this._cfg.sequentialInvoices) {
                this._enforceSequence(id, cb.checked);
                this._syncInvoiceCheckboxes();
            }
            this._updateRecoverySelection();
        }

        _onClick(e) {
            const c = this._cfg;

            if (e.target === this._root && !this._isInline()) {
                if (c.closable && c.closeOnOverlayClick && !this._isSubmitting) this.close();
                return;
            }

            const typeBtn = e.target.closest("[data-type]");
            if (typeBtn) {
                const type = typeBtn.dataset.type;
                if (!PAYMENT_TYPES[type]) return;
                this._paymentType = type;
                this._resetContractState(false);
                this._setStep("contract");
                if (this._contractId && c.autoVerifyContract !== false) this._verifyContract();
                return;
            }

            const primeBtn = e.target.closest("[data-prime-step]");
            if (primeBtn) {
                const next = this._numberOfPrimes + Number(primeBtn.dataset.primeStep);
                if (next >= 1 && next <= c.maxPrimes) {
                    this._numberOfPrimes = next;
                    const prime = Number((this._contractInfo || {}).primePrincipale || 0);
                    const count = this._modal.querySelector('[data-role="primes-count"]');
                    const total = this._modal.querySelector('[data-role="primes-total"]');
                    if (count) count.textContent = String(next);
                    if (total) total.textContent = formatAmount(prime * next, c.currency);
                    this._syncContinueButton();
                }
                return;
            }

            const actionEl = e.target.closest("[data-action]");
            if (!actionEl || actionEl.disabled) return;

            switch (actionEl.dataset.action) {
                case "close":
                    this.close();
                    break;
                case "verify":
                    this._verifyContract();
                    break;
                case "toggle-all": {
                    const list = this._invoices();
                    const allChecked = list.length > 0 && list.every((f) => this._selectedInvoiceIds.has(f.IdPresentation));
                    this._selectedInvoiceIds = allChecked ? new Set() : new Set(list.map((f) => f.IdPresentation));
                    this._syncInvoiceCheckboxes();
                    this._updateRecoverySelection();
                    break;
                }
                case "go-type":
                    if (this._lockType) break;
                    this._resetContractState(false);
                    this._setStep("type");
                    break;
                case "change-contract":
                    this._resetContractState(true);
                    this._lockContract = false;
                    this._setStep("contract");
                    setTimeout(() => this._modal?.querySelector('[data-role="contract-input"]')?.focus(), 0);
                    break;
                case "switch-recovery":
                    this._paymentType = "recoveryPrime";
                    this._resetContractState(false);
                    this._setStep("contract");
                    this._verifyContract();
                    break;
                case "go-summary":
                    if (this._canProceedFromContract()) this._setStep("summary");
                    break;
                case "go-contract":
                    this._setStep("contract");
                    break;
                case "submit":
                    this._handleSubmit();
                    break;
                default:
                    break;
            }
        }

        // ==============================================================
        // VÉRIFICATION DU CONTRAT
        // ==============================================================

        _requestHeaders() {
            const headers = { "Content-Type": "application/json", Accept: "application/json", ...this._cfg.headers };
            if (this._cfg.csrfToken) headers["X-CSRF-TOKEN"] = this._cfg.csrfToken;
            return headers;
        }

        async _verifyContract() {
            if (this._isVerifyingContract || !this._modal) return;
            const t = this._t();
            const input = this._modal.querySelector('[data-role="contract-input"]');
            const idContrat = String((input && input.value) || this._contractId || "").trim();

            if (!idContrat) {
                if (input) {
                    input.classList.add("error");
                    input.parentElement.querySelector(".error-message")?.classList.add("visible");
                    input.focus();
                }
                return;
            }

            this._contractId = idContrat;
            this._isVerifyingContract = true;
            this._generalError = null;
            this._refresh();

            const controller = new AbortController();
            const timer = setTimeout(() => controller.abort(), this._cfg.timeout);

            try {
                const res = await fetch(this._cfg.contractCheckEndpoint, {
                    method: "POST",
                    headers: this._requestHeaders(),
                    credentials: "same-origin",
                    signal: controller.signal,
                    body: JSON.stringify({ idContrat, paymentType: this._paymentType }),
                });
                let data;
                try { data = await res.json(); } catch { data = {}; }

                this._isVerifyingContract = false;
                if (!this._isOpen) return;

                if (!res.ok || !data.success) {
                    this._generalError = data.message || t.contractNotFound;
                    this._refresh();
                    return;
                }

                this._applyContract(data.data || {}, {
                    status: data.status === "contract_not_found" ? "not_found" : "found",
                    preselect: this._cfg.preselect,
                    message: data.status === "contract_not_found" ? data.message : null,
                });
                this._refresh();
            } catch (err) {
                this._isVerifyingContract = false;
                if (!this._isOpen) return;
                this._generalError = err.name === "AbortError" ? t.timeoutError : (err.message || t.networkError);
                this._refresh();
            } finally {
                clearTimeout(timer);
            }
        }

        // ==============================================================
        // SOUMISSION
        // ==============================================================

        _renderState(icon, title, message, withRetry) {
            const t = this._t();
            this._modal.innerHTML = `
                <div class="wave-state" role="status">
                    ${icon === "spinner" ? '<div class="wave-spinner"></div>' : `<span class="icon">${icon}</span>`}
                    ${title ? `<h4>${escapeHtml(title)}</h4>` : ""}
                    <p>${escapeHtml(message || "")}</p>
                    ${withRetry ? `<button type="button" class="wave-retry" data-action="retry">${escapeHtml(t.retry)}</button>` : ""}
                </div>`;
            if (withRetry) {
                this._modal.querySelector('[data-action="retry"]').addEventListener("click", () => {
                    this._modal.innerHTML = this._shellTemplate();
                    this._setStep(this._cfg.skipSummary ? "contract" : "summary");
                });
            }
        }

        _buildPayload() {
            const c = this._cfg;
            const reference = typeof c.generateReference === "function"
                ? String(c.generateReference())
                : `WAVE-${Date.now()}-${randomToken(6)}`;

            const coeur = {
                reference,
                currency: c.currency,
                paymentType: this._paymentType,
                amount: this._computeAmount(),
                contractId: this._contractId || undefined,
                numberOfPrimes: this._paymentType === "earlyPayment" ? this._numberOfPrimes : undefined,
                selectedInvoiceIds: this._paymentType === "recoveryPrime" ? Array.from(this._selectedInvoiceIds) : undefined,
                manualAmount: this._paymentType === "firstPayment" && this._contractStatus === "not_found"
                    ? Number(this._manualAmount)
                    : undefined,
                description: c.description || null,
                customerName: c.customerName || null,
                customerEmail: c.customerEmail || null,
                metadata: c.metadata || null,
            };
            // Les champs additionnels ne peuvent pas écraser les champs du paiement
            return { ...(c.extraPayload || {}), ...coeur };
        }

        async _handleSubmit() {
            if (this._isSubmitting || !this._canProceedFromContract()) return;
            const c = this._cfg;
            const t = this._t();
            this._isSubmitting = true;

            let payload = this._buildPayload();

            if (typeof c.callbacks.onBeforeSubmit === "function") {
                try {
                    const r = await c.callbacks.onBeforeSubmit(payload, this.getPaymentData());
                    if (r === false) {
                        this._isSubmitting = false;
                        return;
                    }
                    if (r && typeof r === "object") payload = r;
                } catch (err) {
                    this._isSubmitting = false;
                    this._generalError = err.message || t.error;
                    this._refresh();
                    return;
                }
            }

            this._renderState("spinner", "", t.processing, false);

            const controller = new AbortController();
            const timer = setTimeout(() => controller.abort(), c.timeout);
            const echec = (msg, data) => {
                this._renderState("❌", t.error, msg, true);
                this._isSubmitting = false;
                if (typeof c.callbacks.onError === "function") c.callbacks.onError(msg, data);
            };

            try {
                const response = await fetch(c.backendEndpoint, {
                    method: "POST",
                    headers: this._requestHeaders(),
                    body: JSON.stringify(payload),
                    signal: controller.signal,
                    credentials: "same-origin",
                });
                let data;
                try { data = await response.json(); } catch { data = {}; }

                if (!response.ok || !data.success) {
                    echec(data.message || `Erreur serveur (${response.status})`, data);
                    return;
                }

                const redirectUrl = data.data?.redirectUrl || data.data?.wave_launch_url || data.redirect_url;
                if (!redirectUrl) {
                    echec(t.noRedirectUrl, data);
                    return;
                }

                this._renderState("✅", t.success, t.successMessage, false);
                if (typeof c.callbacks.onSuccess === "function") c.callbacks.onSuccess(redirectUrl, data);

                if (c.autoRedirect) {
                    setTimeout(() => {
                        this._teardown(false);
                        window.location.href = redirectUrl;
                    }, c.redirectDelay);
                }
            } catch (err) {
                echec(err.name === "AbortError" ? t.timeoutError : (err.message || t.networkError), null);
            } finally {
                clearTimeout(timer);
            }
        }
    }

    window.WaveWidget = WaveWidget;
})(window, document);
