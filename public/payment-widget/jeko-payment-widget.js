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


// (function (window, document) {
//     "use strict";

//     const PAYMENT_METHODS = [
//         {
//             id: "wave",
//             label: "Wave",
//             iconUrl:
//                 "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSZaeFi3xAkC86Ui29AojMASpYfFMPLDzf-1hTcDVS-0Q&s=10",
//             hint: "Paiement mobile",
//             disabled: false,
//         },
//         {
//             id: "orange",
//             label: "Orange Money",
//             iconUrl:
//                 "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRiNkcP-3jO9hJmuSHaXVo8yEzdoy-lOy8NcQgBHvbqCw&s=10",
//             hint: "Paiement mobile",
//             disabled: false,
//         },
//         {
//             id: "moov",
//             label: "Moov Money",
//             iconUrl:
//                 "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQyu5BMOD9klyZBXQ4Pq7A1e1twlAte3KLXAVNyy9fla4pOika5S9BccZc&s=10",
//             hint: "Paiement mobile",
//             disabled: false,
//         },
//         {
//             id: "mtn",
//             label: "MTN MoMo",
//             iconUrl:
//                 "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSA_m-zjaO2_OO6Ho9UOVaNUESFGH1oOg33NxQsQIN0KOqnvRzuXML9ppt6&s=10",
//             hint: "Paiement mobile",
//             disabled: false,
//         },
//         {
//             id: "djamo",
//             label: "Djamo",
//             iconUrl:
//                 "https://play-lh.googleusercontent.com/COFlFnBiED3WHi-J8CRd6ehKOzBjvgKGySJasSaOm1OrMZbsn0NVzk3uL4PpzGo7mF91EBaOvbsqRL9ImD_-7A",
//             hint: "Carte virtuelle ou mobile",
//             disabled: false,
//         },
//         {
//             id: "visa",
//             label: "Visa",
//             iconUrl:
//                 "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSyKhbs0dVowkgkGdydEfQkqwZd2XrVFSPBz2fDbgU4_g&s=10",
//             hint: "Carte bancaire",
//             disabled: true,
//         },
//         {
//             id: "mastercard",
//             label: "Mastercard",
//             iconUrl:
//                 "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRtTrzAD2ayUIWu8KkdlZZQ64sRrLLRHZbS4mrq3do4Ug&s=10",
//             hint: "Carte bancaire",
//             disabled: true,
//         },
//     ];

//     // Types de paiement pris en charge par le widget
//     const PAYMENT_TYPES = {
//         firstPayment: "firstPayment",
//         earlyPayment: "earlyPayment",
//         recoveryPrime: "recoveryPrime",
//     };

//     const DEFAULT_OPTIONS = {
//         backendEndpoint: "/api/paiements/jeko/init",
//         contractCheckEndpoint: "/api/paiements/jeko/contrat/verifier",
//         currency: "XOF",
//         successUrl: null,
//         errorUrl: null,
//         timeout: 30000,
//         headers: {},
//         theme: {
//             primary: "#1D603D",
//             primaryDark: "#0B482F",
//             accent: "#E09518",
//             radius: "16px",
//             fontFamily:
//                 "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
//             maxWidth: "550px",
//         },
//         translations: {
//             title: "Paiement sécurisé",
//             close: "Fermer",
//             back: "Retour",
//             stepContract: "Contrat",
//             stepMethod: "Paiement",
//             stepSummary: "Résumé",
//             chooseMethod: "Choisissez un moyen de paiement",
//             submit: "Continuer",
//             continueToSummary: "Continuer",
//             confirmAndPay: "Confirmer et payer",
//             processing: "Initialisation du paiement en cours...",
//             success: "Paiement initialisé",
//             successMessage:
//                 "Vous allez être redirigé vers la page de paiement...",
//             error: "Échec du paiement",
//             retry: "Réessayer",
//             networkError: "Erreur réseau",
//             requiredField: "Ce champ est requis",
//             contractIdLabel: "Identifiant du contrat",
//             contractIdPlaceholder: "Ex: 345678",
//             verify: "Vérifier le contrat",
//             verifying: "Vérification en cours...",
//             firstPaymentOptionA: "Payer la première prime",
//             firstPaymentOptionB: "Payer en avance",
//             numberOfPrimesLabel: "Nombre de primes à régler",
//             blockedEarlyPayment:
//                 "Ce contrat a des primes impayées. Le paiement anticipé n'est pas disponible tant qu'elles ne sont pas régularisées.",
//             switchToRecovery: "Régulariser mes impayés",
//             noUnpaidInvoices: "Aucune facture impayée sur ce contrat.",
//             switchToEarly: "Payer en avance à la place",
//             selectInvoicesLabel: "Sélectionnez les primes à régulariser",
//             totalLabel: "Total à régler",
//             firstPaymentBase: "Première prime",
//             firstPaymentFees: "Frais d'adhésion",
//             firstPaymentTotal: "Total de la première prime",
//             additionalPrimesLabel: "Souhaitez-vous également payer d'autres primes en avance ?",
//             additionalPrimesYes: "Oui",
//             additionalPrimesNo: "Non",
//             additionalPrimesCount: "Nombre de primes en avance",
//             additionalPrimesTotal: "Total des primes en avance",
//             summaryTitle: "Vérifiez votre paiement",
//             summaryContract: "Contrat",
//             summaryMethod: "Moyen de paiement",
//             summaryAmount: "Montant à payer",
//             changeMethod: "Modifier",
//         },
//         callbacks: {
//             onSuccess: null,
//             onError: null,
//             onClose: null,
//             onOpen: null,
//         },
//     };

//     function injectStyles(theme) {
//         const existing = document.getElementById("jeko-widget-styles");
//         if (existing) existing.remove();
//         const style = document.createElement("style");
//         style.id = "jeko-widget-styles";
//         style.textContent = `
//       .jeko-overlay {
//         --jeko-primary:${theme.primary};
//         --jeko-primary-dark:${theme.primaryDark};
//         --jeko-accent:${theme.accent};
//         --jeko-radius:${theme.radius};
//         --jeko-max-width:${theme.maxWidth};
//         font-family:${theme.fontFamily};
//         position:fixed; inset:0;
//         background:rgba(8,12,10,.65);
//         display:flex; align-items:center; justify-content:center;
//         z-index:999999; padding:20px;
//         backdrop-filter:blur(8px);
//         animation: jeko-fade-in .2s ease;
//       }
//       @keyframes jeko-fade-in { from { opacity:0 } to { opacity:1 } }
//       @keyframes jeko-slide-up { from { opacity:0; transform:translateY(12px) } to { opacity:1; transform:translateY(0) } }
//       @keyframes jeko-pop { 0% { transform:scale(.85); opacity:0 } 60% { transform:scale(1.05) } 100% { transform:scale(1); opacity:1 } }
//       @keyframes jeko-check-pop { 0% { transform:scale(0) rotate(-20deg); opacity:0 } 100% { transform:scale(1) rotate(0); opacity:1 } }

//       * { box-sizing:border-box; }

//       .jeko-modal {
//         background:#fff; width:100%;
//         max-width:var(--jeko-max-width);
//         border-radius:var(--jeko-radius);
//         box-shadow:0 32px 80px rgba(0,0,0,.4), 0 0 0 1px rgba(255,255,255,.04);
//         overflow:hidden; max-height:92vh;
//         display:flex; flex-direction:column;
//         animation: jeko-slide-up .28s cubic-bezier(.16,1,.3,1);
//       }

//       /* ============ HEADER ============ */
//       .jeko-header {
//         background:linear-gradient(160deg,var(--jeko-primary) 0%,var(--jeko-primary-dark) 100%);
//         color:#fff; padding:24px 26px 22px;
//         position:relative;
//         overflow:hidden;
//       }
//       .jeko-header::before {
//         content:"";
//         position:absolute; top:-40%; right:-15%;
//         width:220px; height:220px; border-radius:50%;
//         background:radial-gradient(circle, rgba(255,255,255,.08) 0%, transparent 70%);
//         pointer-events:none;
//       }
//       .jeko-header-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; position:relative; z-index:1; }
//       .jeko-header h3 { margin:0; font-size:17px; font-weight:700; letter-spacing:.2px; display:flex; align-items:center; gap:8px; }
//       .jeko-header h3::before {
//         content:"🔒"; font-size:14px; opacity:.9;
//       }
//       .jeko-close {
//         background:rgba(255,255,255,.12); border:none; color:#fff;
//         width:30px; height:30px; border-radius:50%; font-size:16px; cursor:pointer;
//         display:flex; align-items:center; justify-content:center;
//         transition:background .15s, transform .15s;
//       }
//       .jeko-close:hover { background:rgba(255,255,255,.25); transform:rotate(90deg); }

//       /* ============ STEPPER ============ */
//       .jeko-steps {
//         display:flex; align-items:flex-start; gap:0;
//         position:relative; z-index:1;
//       }
//       .jeko-step {
//         display:flex; flex-direction:column; align-items:center;
//         flex:1; position:relative;
//       }
//       .jeko-step:first-child { align-items:flex-start; }
//       .jeko-step:last-child { align-items:flex-end; flex:0 0 auto; }

//       .jeko-step-track {
//         display:flex; align-items:center; width:100%;
//       }
//       .jeko-step-circle-wrap {
//         position:relative; flex-shrink:0;
//         display:flex; align-items:center; justify-content:center;
//       }
//       .jeko-step-circle {
//         width:30px; height:30px; border-radius:50%;
//         display:flex; align-items:center; justify-content:center;
//         font-size:12.5px; font-weight:800;
//         background:rgba(255,255,255,.12); color:rgba(255,255,255,.6);
//         border:2px solid rgba(255,255,255,.28);
//         flex-shrink:0; position:relative; z-index:2;
//         transition:all .25s cubic-bezier(.4,0,.2,1);
//       }
//       .jeko-step-line {
//         flex:1; height:2px; background:rgba(255,255,255,.2);
//         margin:0 4px; border-radius:2px; position:relative; overflow:hidden;
//         top:0;
//       }
//       .jeko-step-line::after {
//         content:""; position:absolute; inset:0; background:var(--jeko-accent);
//         transform:scaleX(0); transform-origin:left; transition:transform .35s ease;
//       }
//       .jeko-step-line.done::after { transform:scaleX(1); }

//       .jeko-step-label {
//         font-size:11px; margin-top:7px; color:rgba(255,255,255,.6);
//         white-space:nowrap; font-weight:600; letter-spacing:.2px;
//         transition:color .2s ease;
//       }

//       .jeko-step.active .jeko-step-circle {
//         background:#fff; color:var(--jeko-primary-dark); border-color:#fff;
//         box-shadow:0 0 0 4px rgba(255,255,255,.18);
//         animation: jeko-pop .3s ease;
//       }
//       .jeko-step.active .jeko-step-label { color:#fff; font-weight:700; }

//       .jeko-step.done .jeko-step-circle {
//         background:var(--jeko-accent); color:#1f2937; border-color:var(--jeko-accent);
//       }
//       .jeko-step.done .jeko-step-label { color:rgba(255,255,255,.85); }
//       .jeko-step.done .jeko-step-circle .checkmark { animation: jeko-check-pop .3s ease; display:inline-block; }

//       /* ============ BODY ============ */
//       .jeko-body { padding:26px; overflow-y:auto; flex:1; animation: jeko-slide-up .22s ease; }
//       .jeko-body::-webkit-scrollbar { width:6px; }
//       .jeko-body::-webkit-scrollbar-thumb { background:#d1d5db; border-radius:6px; }

//       .jeko-amount { text-align:center; padding:4px 0 22px; }
//       .jeko-amount .amount { font-size:32px; font-weight:800; color:var(--jeko-primary-dark); display:block; letter-spacing:-.6px; }
//       .jeko-amount .description { font-size:13px; color:#6b7280; margin-top:5px; display:block; }

//       .jeko-section-label { font-size:13px; font-weight:700; color:#374151; margin:0 0 12px; display:block; text-align:left; letter-spacing:.1px; }

//       /* Payment methods grid */
//       .jeko-methods { display:grid; grid-template-columns:repeat(auto-fill,minmax(130px,1fr)); gap:10px; }
//       .jeko-method-btn {
//         border:2px solid #eceeed; background:#fff; border-radius:13px; padding:15px 10px;
//         text-align:center; cursor:pointer; width:100%;
//         display:flex; flex-direction:column; align-items:center; gap:7px;
//         transition:all .18s cubic-bezier(.4,0,.2,1);
//         position:relative;
//       }
//       .jeko-method-btn:hover:not(.cards-disabled) { border-color:#b8d2c1; background:#fafdfb; transform:translateY(-2px); box-shadow:0 6px 16px rgba(29,96,61,.08); }
//       .jeko-method-btn[aria-pressed="true"] {
//         border-color:var(--jeko-primary); background:#eef7f1;
//         box-shadow:0 0 0 3px rgba(29,96,61,.12);
//       }
//       .jeko-method-btn[aria-pressed="true"]::after {
//         content:"✓"; position:absolute; top:6px; right:8px;
//         width:16px; height:16px; border-radius:50%; background:var(--jeko-primary);
//         color:#fff; font-size:10px; font-weight:800; display:flex; align-items:center; justify-content:center;
//         animation: jeko-check-pop .25s ease;
//       }
//       .jeko-method-btn.cards-disabled { background:#f7f7f7; cursor:not-allowed; opacity:.5; }
//       .jeko-method-btn .icon-wrapper {
//         width:44px; height:44px; border-radius:50%; overflow:hidden;
//         display:flex; align-items:center; justify-content:center; background:#f3f4f6;
//       }
//       .jeko-method-btn .icon-wrapper img { width:28px; height:28px; object-fit:contain; border-radius:20%; }
//       .jeko-method-btn .name { font-weight:700; font-size:12.5px; color:#111827; }
//       .jeko-method-btn .hint { font-size:10.5px; color:#9ca3af; }

//       /* Contract lookup */
//       .jeko-lookup-icon {
//         width:60px; height:60px; border-radius:18px;
//         background:linear-gradient(135deg,#eef7f1,#e3f1e8);
//         display:flex; align-items:center; justify-content:center;
//         margin:0 auto 18px; font-size:26px;
//         box-shadow:inset 0 0 0 1px rgba(29,96,61,.08);
//       }
//       .jeko-field { margin-top:14px; }
//       .jeko-field label { display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:7px; }
//       .jeko-field input {
//         width:100%; box-sizing:border-box; padding:13px 14px; border-radius:11px;
//         border:2px solid #e5e7eb; font-size:15px; font-family:inherit; transition:border-color .15s, box-shadow .15s;
//       }
//       .jeko-field input:focus { outline:none; border-color:var(--jeko-primary); box-shadow:0 0 0 3px rgba(29,96,61,.1); }
//       .jeko-field input.error { border-color:#dc2626; }
//       .jeko-field .error-message { color:#dc2626; font-size:12px; margin-top:6px; display:none; }
//       .jeko-field .error-message.visible { display:block; }
//       .disabled-input { background-color:#f3f4f6 !important; color:#9ca3af !important; cursor:not-allowed; pointer-events:none; }

//       /* Stepper control (qty) */
//       .jeko-stepper { display:flex; align-items:center; gap:16px; }
//       .jeko-stepper button {
//         width:38px; height:38px; border-radius:11px; border:2px solid #e5e7eb; background:#fff;
//         font-size:18px; cursor:pointer; color:var(--jeko-primary-dark); font-weight:700;
//         transition:all .15s;
//       }
//       .jeko-stepper button:hover { border-color:var(--jeko-primary); background:#f3faf5; }
//       .jeko-stepper span { font-size:18px; font-weight:800; min-width:28px; text-align:center; color:#111827; }

//       /* Invoice list */
//       .jeko-invoice-list { display:flex; flex-direction:column; gap:9px; max-height:220px; overflow-y:auto; padding:2px; }
//       .jeko-invoice-item {
//         display:flex; align-items:center; gap:10px; border:2px solid #eceeed; border-radius:11px;
//         padding:12px 13px; cursor:pointer; transition:border-color .15s, background .15s;
//       }
//       .jeko-invoice-item:hover { border-color:#b8d2c1; background:#fafdfb; }
//       .jeko-invoice-item:has(input:checked) { border-color:var(--jeko-primary); background:#eef7f1; }
//       .jeko-invoice-item .meta { flex:1; font-size:12.5px; color:#374151; }
//       .jeko-invoice-item .amount { font-weight:700; font-size:13px; color:#111827; }
//       .jeko-invoice-item input[type="checkbox"] { width:18px; height:18px; accent-color:var(--jeko-primary); cursor:pointer; }

//       .jeko-total-bar {
//         display:flex; justify-content:space-between; align-items:center; margin-top:16px;
//         padding:13px 16px; background:linear-gradient(135deg,#f0f7f3,#e8f3ec); border-radius:11px;
//         font-weight:700; font-size:14px; border:1px solid #dcebe1;
//       }

//       /* Notices */
//       .jeko-notice { font-size:12.5px; padding:12px 15px; border-radius:11px; margin-bottom:15px; line-height:1.45; }
//       .jeko-notice.warn { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
//       .jeko-notice.info { background:#f5f8f6; color:#1f4732; border:1px solid #dce8e0; font-weight:600; }
//       .jeko-notice.success { background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; }
//       .jeko-link-btn {
//         background:none; border:none; color:var(--jeko-primary-dark); font-weight:700;
//         text-decoration:underline; cursor:pointer; padding:0; margin-top:2px; font-family:inherit; font-size:12.5px;
//       }

//       /* Toggle */
//       .jeko-toggle-group { display:flex; gap:10px; margin-top:8px; }
//       .jeko-toggle-btn {
//         flex:1; border:2px solid #e5e7eb; background:#fff; border-radius:11px; padding:12px 14px;
//         text-align:center; cursor:pointer; font-family:inherit; font-weight:700; font-size:13.5px;
//         transition:all .15s;
//       }
//       .jeko-toggle-btn:hover { border-color:#b8d2c1; }
//       .jeko-toggle-btn[aria-pressed="true"] { border-color:var(--jeko-primary); background:#eef7f1; color:var(--jeko-primary-dark); }

//       /* Breakdown */
//       .jeko-breakdown { background:#f9fafb; border-radius:13px; padding:15px 17px; margin-top:15px; border:1px solid #f0f0f0; }
//       .jeko-breakdown-row { display:flex; justify-content:space-between; padding:5px 0; font-size:13px; color:#4b5563; }
//       .jeko-breakdown-row.total { font-weight:800; color:#111827; border-top:2px solid #e5e7eb; padding-top:10px; margin-top:6px; font-size:14px; }

//       /* Summary step (step 3) */
//       .jeko-summary-card { border:2px solid #eef1ef; border-radius:15px; overflow:hidden; }
//       .jeko-summary-row {
//         display:flex; align-items:center; justify-content:space-between;
//         padding:15px 17px; border-bottom:1px solid #f0f0f0;
//       }
//       .jeko-summary-row:last-child { border-bottom:none; }
//       .jeko-summary-row .label { font-size:12.5px; color:#6b7280; font-weight:600; }
//       .jeko-summary-row .value { font-size:13.5px; color:#111827; font-weight:700; text-align:right; }
//       .jeko-summary-row .value.method { display:flex; align-items:center; gap:8px; }
//       .jeko-summary-row .value.method img { width:22px; height:22px; border-radius:5px; object-fit:contain; }
//       .jeko-summary-total {
//         background:linear-gradient(160deg,var(--jeko-primary),var(--jeko-primary-dark));
//         color:#fff; padding:20px 17px; text-align:center; position:relative; overflow:hidden;
//       }
//       .jeko-summary-total::before {
//         content:""; position:absolute; top:-50%; left:-10%;
//         width:180px; height:180px; border-radius:50%;
//         background:radial-gradient(circle, rgba(255,255,255,.08), transparent 70%);
//       }
//       .jeko-summary-total .label { font-size:12px; opacity:.85; font-weight:600; letter-spacing:.4px; text-transform:uppercase; position:relative; }
//       .jeko-summary-total .amount { font-size:29px; font-weight:800; margin-top:5px; letter-spacing:-.5px; position:relative; }

//       /* ============ FOOTER ============ */
//       .jeko-footer { padding:18px 26px 24px; border-top:1px solid #f0f0f0; display:flex; gap:10px; }
//       .jeko-btn-back {
//         border:2px solid #e5e7eb; background:#fff; color:#374151; font-weight:700; font-size:14px;
//         padding:14px 18px; border-radius:12px; cursor:pointer; font-family:inherit; transition:all .15s;
//       }
//       .jeko-btn-back:hover { border-color:#c9d8ce; background:#fafafa; }
//       .jeko-submit {
//         flex:1; border:none; background:var(--jeko-accent); color:#1f2937; font-weight:800; font-size:15px;
//         padding:14px; border-radius:12px; cursor:pointer; font-family:inherit; transition:filter .15s, transform .1s, box-shadow .15s;
//         box-shadow:0 4px 14px rgba(224,149,24,.28);
//       }
//       .jeko-submit:hover:not(:disabled) { filter:brightness(1.05); box-shadow:0 6px 18px rgba(224,149,24,.35); }
//       .jeko-submit:active:not(:disabled) { transform:scale(.99); }
//       .jeko-submit:disabled { opacity:.45; cursor:not-allowed; box-shadow:none; }
//       .jeko-submit.outline {
//         background:#fff; color:var(--jeko-primary-dark); border:2px solid var(--jeko-primary); box-shadow:none;
//       }

//       /* State screens */
//       .jeko-state { padding:46px 26px; text-align:center; min-height:230px; display:flex; flex-direction:column; align-items:center; justify-content:center; }
//       .jeko-state h4 { margin:6px 0 6px; font-size:17px; color:#111827; }
//       .jeko-state p { margin:0; font-size:13.5px; color:#6b7280; max-width:280px; }
//       .jeko-state .icon { font-size:54px; margin-bottom:10px; animation: jeko-pop .35s ease; }
//       .jeko-spinner {
//         width:44px; height:44px; border:3px solid #e5e7eb; border-top-color:var(--jeko-primary);
//         border-radius:50%; margin:0 auto 18px; animation:jeko-spin .8s linear infinite;
//       }
//       @keyframes jeko-spin { to { transform:rotate(360deg); } }
//       .jeko-retry {
//         margin-top:18px; border:2px solid var(--jeko-primary); background:#fff; color:var(--jeko-primary-dark);
//         font-weight:700; padding:11px 26px; border-radius:11px; cursor:pointer; font-family:inherit; transition:background .15s;
//       }
//       .jeko-retry:hover { background:#f3faf5; }
//     `;
//         document.head.appendChild(style);
//     }

//     function formatAmount(units, currency) {
//         try {
//             return new Intl.NumberFormat("fr-FR", {
//                 style: "currency",
//                 currency: currency || "XOF",
//                 minimumFractionDigits: 0,
//                 maximumFractionDigits: 0,
//             }).format(units || 0);
//         } catch {
//             return `${units || 0} ${currency || ""}`;
//         }
//     }

//     function methodMeta(id) {
//         return PAYMENT_METHODS.find((m) => m.id === id);
//     }

//     class JekoWidget {
//         constructor(options = {}) {
//             this.options = {
//                 ...DEFAULT_OPTIONS,
//                 ...options,
//                 theme: { ...DEFAULT_OPTIONS.theme, ...(options.theme || {}) },
//                 headers: {
//                     ...DEFAULT_OPTIONS.headers,
//                     ...(options.headers || {}),
//                 },
//                 translations: {
//                     ...DEFAULT_OPTIONS.translations,
//                     ...(options.translations || {}),
//                 },
//                 callbacks: {
//                     ...DEFAULT_OPTIONS.callbacks,
//                     ...(options.callbacks || {}),
//                 },
//             };
//             injectStyles(this.options.theme);
//             this._overlay = null;
//             this._modal = null;
//             this._isOpen = false;
//             this._isSubmitting = false;
//             this._escHandler = this._escHandler.bind(this);
//             this._isVerifyingContract = false;
//             this._additionalPrimes = false;
//             this._additionalPrimesCount = 0;
//             // step: 1 = contrat, 2 = moyen de paiement, 3 = résumé (uniquement pour earlyPayment/recoveryPrime)
//             this._step = 1;
//             // Indique si le résumé doit être affiché (false pour firstPayment)
//             this._hasSummaryStep = true;
//         }

//         /**
//          * paymentData attendu :
//          * {
//          *   reference,                 // requis, référence métier unique
//          *   paymentType,               // requis: 'firstPayment' | 'earlyPayment' | 'recoveryPrime'
//          *   contractId,                // requis pour earlyPayment / recoveryPrime, optionnel pour firstPayment
//          *   primeUnitaire,             // XOF, requis pour firstPayment si contractId absent
//          *   fraisAdhesion,             // XOF, requis pour firstPayment si contractId absent
//          *   currency, description, customerEmail, customerName, metadata
//          * }
//          */
//         open(paymentData) {
//             if (!paymentData?.reference)
//                 throw new Error("JekoWidget.open() nécessite 'reference'.");
//             if (!PAYMENT_TYPES[paymentData.paymentType])
//                 throw new Error(
//                     "JekoWidget.open() nécessite un 'paymentType' valide.",
//                 );

//             if (this._isOpen) this.close();

//             this._paymentData = {
//                 currency: this.options.currency,
//                 ...paymentData,
//             };
//             this._selectedMethod = null;
//             this._numberOfPrimes = 1;
//             this._contractInfo = null;
//             this._selectedInvoiceIds = new Set();
//             this._blocked = false;
//             this._additionalPrimes = false;
//             this._additionalPrimesCount = 0;
//             this._step = 1;
            
//             // Pour firstPayment : pas d'étape résumé
//             this._hasSummaryStep = this._paymentData.paymentType !== "firstPayment";

//             this._render();
//             this._isOpen = true;
//             document.addEventListener("keydown", this._escHandler);
//             if (this.options.callbacks.onOpen)
//                 this.options.callbacks.onOpen(this._paymentData);
//         }

//         close() {
//             if (this._overlay) {
//                 this._overlay.remove();
//                 this._overlay = null;
//                 this._modal = null;
//             }
//             this._isOpen = false;
//             document.removeEventListener("keydown", this._escHandler);
//             if (this.options.callbacks.onClose)
//                 this.options.callbacks.onClose();
//         }

//         _escHandler(e) {
//             if (e.key === "Escape") this.close();
//         }

//         _render() {
//             if (this._overlay) this._overlay.remove();
//             const overlay = document.createElement("div");
//             overlay.className = "jeko-overlay";
//             overlay.setAttribute("role", "dialog");
//             overlay.setAttribute("aria-modal", "true");
//             overlay.addEventListener("click", (e) => {
//                 if (e.target === overlay) this.close();
//             });

//             const modal = document.createElement("div");
//             modal.className = "jeko-modal";
//             modal.innerHTML = this._shellTemplate();
//             overlay.appendChild(modal);
//             document.body.appendChild(overlay);
//             this._overlay = overlay;
//             this._modal = modal;

//             this._bindEvents();

//             // Pour firstPayment : vérification automatique du contrat si contractId fourni
//             if (this._paymentData.paymentType === "firstPayment" && this._paymentData.contractId) {
//                 // Petite attente pour que le DOM soit prêt
//                 setTimeout(() => {
//                     this._autoVerifyContract();
//                 }, 100);
//             }
//         }

//         // ---------- Helpers de calcul ----------

//         _computeAmount() {
//             const t = this._paymentData.paymentType;
//             const prime = Number(this._contractInfo?.primePrincipale || 0);
//             const frais = Number(this._contractInfo?.fraisAdhesion || 0);

//             if (t === "firstPayment") {
//                 const firstPrimeTotal = frais + prime;
//                 const additionalTotal = this._additionalPrimes ? prime * this._additionalPrimesCount : 0;
//                 return firstPrimeTotal + additionalTotal;
//             }
//             if (t === "earlyPayment") {
//                 return prime * this._numberOfPrimes;
//             }
//             if (t === "recoveryPrime") {
//                 const list = this._contractInfo?.facturesImpayees || [];
//                 return list
//                     .filter((f) =>
//                         this._selectedInvoiceIds.has(f.IdPresentation),
//                     )
//                     .reduce((sum, f) => sum + Number(f.MontantNet || 0), 0);
//             }
//             return 0;
//         }

//         _canProceedFromStep1() {
//             const type = this._paymentData.paymentType;
//             if (!this._contractInfo) return false;
//             if (type === "earlyPayment" && this._blocked) return false;
//             if (
//                 type === "recoveryPrime" &&
//                 (this._contractInfo.facturesImpayees || []).length === 0
//             )
//                 return false;
//             return true;
//         }

//         _canProceedFromStep2() {
//             if (!this._selectedMethod) return false;
//             if (
//                 this._paymentData.paymentType === "recoveryPrime" &&
//                 this._selectedInvoiceIds.size === 0
//             )
//                 return false;
//             return true;
//         }

//         _shouldShowSummaryStep() {
//             return this._hasSummaryStep;
//         }

//         // ---------- Shell + Stepper ----------

//         _shellTemplate() {
//             const t = this.options.translations;
//             return `
//         <div class="jeko-header">
//           <div class="jeko-header-top">
//             <h3>${t.title}</h3>
//             <button type="button" class="jeko-close" aria-label="${t.close}">&times;</button>
//           </div>
//           ${this._stepperHeaderTemplate()}
//         </div>
//         <div class="jeko-body" data-role="body">${this._stepBodyTemplate()}</div>
//         <div class="jeko-footer" data-role="footer">${this._footerTemplate()}</div>
//       `;
//         }

//         _stepperHeaderTemplate() {
//             const t = this.options.translations;
            
//             // Pour firstPayment : seulement 2 étapes
//             const showSummary = this._shouldShowSummaryStep();
//             const steps = showSummary
//                 ? [
//                     { n: 1, label: t.stepContract },
//                     { n: 2, label: t.stepMethod },
//                     { n: 3, label: t.stepSummary },
//                   ]
//                 : [
//                     { n: 1, label: t.stepContract },
//                     { n: 2, label: t.stepMethod },
//                   ];

//             return `
//         <div class="jeko-steps">
//           ${steps
//               .map((s, i) => {
//                   const state =
//                       this._step === s.n
//                           ? "active"
//                           : this._step > s.n
//                             ? "done"
//                             : "";
//                   const circleContent =
//                       this._step > s.n
//                           ? `<span class="checkmark">✓</span>`
//                           : String(s.n);
//                   const isLast = i === steps.length - 1;
//                   return `
//               <div class="jeko-step ${state}">
//                 <div class="jeko-step-track">
//                   <div class="jeko-step-circle-wrap">
//                     <div class="jeko-step-circle">${circleContent}</div>
//                   </div>
//                   ${!isLast ? `<div class="jeko-step-line ${this._step > s.n ? "done" : ""}"></div>` : ""}
//                 </div>
//                 <span class="jeko-step-label">${s.label}</span>
//               </div>
//             `;
//               })
//               .join("")}
//         </div>
//       `;
//         }

//         _stepBodyTemplate() {
//             if (this._step === 1) return this._step1Template();
//             if (this._step === 2) return this._step2Template();
//             return this._step3Template();
//         }

//         _footerTemplate() {
//             const t = this.options.translations;
//             const showSummary = this._shouldShowSummaryStep();
//             const isFirstPayment = this._paymentData.paymentType === "firstPayment";

//             if (this._step === 1) {
//                 if (!this._canProceedFromStep1()) return "";
//                 // Pour firstPayment : on passe directement à l'étape 2
//                 const label = isFirstPayment ? t.submit : t.continueToSummary;
//                 return `<button type="button" class="jeko-submit" data-action="go-step-2">${label}</button>`;
//             }
            
//             if (this._step === 2) {
//                 // Pour firstPayment : le bouton "Confirmer et payer" est affiché directement
//                 if (isFirstPayment) {
//                     return `
//                 <button type="button" class="jeko-btn-back" data-action="go-step-1">${t.back}</button>
//                 <button type="button" class="jeko-submit" data-action="submit-payment" ${this._canProceedFromStep2() ? "" : "disabled"}>${t.confirmAndPay}</button>
//               `;
//                 }
                
//                 // Pour earlyPayment/recoveryPrime : afficher le résumé
//                 if (showSummary) {
//                     return `
//                 <button type="button" class="jeko-btn-back" data-action="go-step-1">${t.back}</button>
//                 <button type="button" class="jeko-submit" data-action="go-step-3" ${this._canProceedFromStep2() ? "" : "disabled"}>${t.continueToSummary}</button>
//               `;
//                 }
//             }
            
//             if (this._step === 3 && showSummary) {
//                 return `
//           <button type="button" class="jeko-btn-back" data-action="go-step-2">${t.back}</button>
//           <button type="button" class="jeko-submit" data-action="submit-payment">${t.confirmAndPay}</button>
//         `;
//             }
//             return "";
//         }

//         _refreshBody() {
//             const body = this._modal.querySelector('[data-role="body"]');
//             const footer = this._modal.querySelector('[data-role="footer"]');
//             const header = this._modal.querySelector(".jeko-header");
//             body.innerHTML = this._stepBodyTemplate();
//             footer.innerHTML = this._footerTemplate();
//             header.innerHTML = `
//         <div class="jeko-header-top">
//           <h3>${this.options.translations.title}</h3>
//           <button type="button" class="jeko-close" aria-label="${this.options.translations.close}">&times;</button>
//         </div>
//         ${this._stepperHeaderTemplate()}
//       `;
//             this._bindEvents();
//         }

//         // ---------- Vérification automatique pour firstPayment ----------

//         async _autoVerifyContract() {
//             const idContrat = this._paymentData.contractId;
//             if (!idContrat) return;
            
//             // Vérifier que le contrat n'est pas déjà vérifié
//             if (this._contractInfo) return;
            
//             const t = this.options.translations;
            
//             try {
//                 const res = await fetch(this.options.contractCheckEndpoint, {
//                     method: "POST",
//                     headers: {
//                         "Content-Type": "application/json",
//                         Accept: "application/json",
//                         ...this.options.headers,
//                     },
//                     body: JSON.stringify({
//                         idContrat,
//                         paymentType: this._paymentData.paymentType,
//                     }),
//                 });
//                 const data = await res.json();

//                 if (!res.ok || !data.success) {
//                     this._showGeneralError(data.message || t.error);
//                     return;
//                 }

//                 this._contractInfo = data.data;
//                 this._blocked = false;
//                 this._refreshBody();
//             } catch (e) {
//                 console.error(e);
//                 this._showGeneralError(t.networkError);
//             }
//         }

//         // ---------- Step 1 : contrat ----------

//         _step1Template() {
//             const t = this.options.translations;
//             const type = this._paymentData.paymentType;

//             if (!this._contractInfo) {
//                 return this._contractLookupTemplate();
//             }

//             if (type === "firstPayment") {
//                 return this._firstPaymentTemplate();
//             }

//             if (type === "earlyPayment") {
//                 if (this._blocked) {
//                     return `
//             <div class="jeko-notice info">${this._contractSummaryTemplate()}</div>
//             <div class="jeko-notice warn">${t.blockedEarlyPayment}</div>
//             <button type="button" class="jeko-link-btn" data-switch="recoveryPrime">${t.switchToRecovery}</button>
//           `;
//                 }
//                 const amount = this._computeAmount();
//                 return `
//           <div class="jeko-notice info">${this._contractSummaryTemplate()}</div>
//           <span class="jeko-section-label" style="text-align: center">${t.numberOfPrimesLabel}</span>
//           <div style="display:flex;justify-content:center;margin:6px 0 4px;">${this._stepperTemplate(1)}</div>
//           <div class="jeko-total-bar"><span>${t.totalLabel}</span><span>${formatAmount(amount, this._paymentData.currency)}</span></div>
//         `;
//             }

//             if (type === "recoveryPrime") {
//                 const list = this._contractInfo.facturesImpayees || [];
//                 if (list.length === 0) {
//                     return `
//             <div class="jeko-notice info">${this._contractSummaryTemplate()}</div>
//             <div class="jeko-notice info">${t.noUnpaidInvoices}</div>
//             <button type="button" class="jeko-link-btn" data-switch="earlyPayment">${t.switchToEarly}</button>
//           `;
//                 }
//                 const total = this._computeAmount();
//                 const itemsHtml = list
//                     .map(
//                         (f) => `
//           <label class="jeko-invoice-item">
//             <input type="checkbox" data-invoice="${f.IdPresentation}" ${this._selectedInvoiceIds.has(f.IdPresentation) ? "checked" : ""} />
//             <span class="meta">${f.MaDate || ""} — réf ${f.IdPresentation || ""} — ${f.CodePresentation}</span>
//             <span class="amount">${formatAmount(f.MontantNet, this._paymentData.currency)}</span>
//           </label>`,
//                     )
//                     .join("");
//                 return `
//           <div class="jeko-notice info">${this._contractSummaryTemplate()}</div>
//           <span class="jeko-section-label">${t.selectInvoicesLabel}</span>
//           <div class="jeko-invoice-list">${itemsHtml}</div>
//           <div class="jeko-total-bar"><span>${t.totalLabel}</span><span>${formatAmount(total, this._paymentData.currency)}</span></div>
//         `;
//             }

//             return "";
//         }

//         _firstPaymentTemplate() {
//             const t = this.options.translations;
//             const prime = Number(this._contractInfo?.primePrincipale || 0);
//             const frais = Number(this._contractInfo?.fraisAdhesion || 0);
//             const firstPrimeTotal = frais + prime;
//             const additionalTotal = this._additionalPrimes ? prime * this._additionalPrimesCount : 0;
//             const totalAmount = firstPrimeTotal + additionalTotal;

//             return `
//         <div class="jeko-notice info">${this._contractSummaryTemplate()}</div>

//         <div class="jeko-breakdown">
//             <div class="jeko-breakdown-row">
//                 <span>${t.firstPaymentBase}</span>
//                 <span>${formatAmount(prime, this._paymentData.currency)}</span>
//             </div>
//             <div class="jeko-breakdown-row">
//                 <span>${t.firstPaymentFees}</span>
//                 <span>${formatAmount(frais, this._paymentData.currency)}</span>
//             </div>
//             <div class="jeko-breakdown-row total">
//                 <span>${t.firstPaymentTotal}</span>
//                 <span>${formatAmount(firstPrimeTotal, this._paymentData.currency)}</span>
//             </div>
//             ${
//                 this._additionalPrimes && this._additionalPrimesCount > 0
//                     ? `
//             <div class="jeko-breakdown-row">
//                 <span>${t.additionalPrimesTotal} (×${this._additionalPrimesCount})</span>
//                 <span>${formatAmount(additionalTotal, this._paymentData.currency)}</span>
//             </div>
//             <div class="jeko-breakdown-row total">
//                 <span>${t.totalLabel}</span>
//                 <span>${formatAmount(totalAmount, this._paymentData.currency)}</span>
//             </div>`
//                     : ""
//             }
//         </div>

//         <span class="jeko-section-label" style="margin-top:16px;">${t.additionalPrimesLabel}</span>
//         <div class="jeko-toggle-group">
//             <button type="button" class="jeko-toggle-btn" data-additional="yes" aria-pressed="${this._additionalPrimes ? "true" : "false"}">${t.additionalPrimesYes}</button>
//             <button type="button" class="jeko-toggle-btn" data-additional="no" aria-pressed="${!this._additionalPrimes ? "true" : "false"}">${t.additionalPrimesNo}</button>
//         </div>

//         ${
//             this._additionalPrimes
//                 ? `
//             <div class="jeko-field" style="margin:14px auto 0; width:fit-content; text-align:center;">
//                 <label for="jeko-additional-count">${t.additionalPrimesCount}</label>
//                 <div class="jeko-stepper" data-role="additional-stepper">
//                     <button type="button" data-additional-step="-1">−</button>
//                     <span>${this._additionalPrimesCount}</span>
//                     <button type="button" data-additional-step="1">+</button>
//                 </div>
//             </div>`
//                 : ""
//         }
//       `;
//         }

//         _contractLookupTemplate() {
//             const t = this.options.translations;
//             const isFirstPayment = this._paymentData.paymentType === "firstPayment";
//             const inputDisabled = isFirstPayment ? "disabled-input" : "";
//             const inputValue = this._paymentData.contractId || "";
            
//             return `
//         <div class="jeko-lookup-icon">📄</div>
//         ${isFirstPayment ? '<p style="text-align:center;color:#6b7280;font-size:13px;margin:-4px 0 8px;">Vérification automatique du contrat...</p>' : ''}
//         <div class="jeko-field" data-role="contract">
//           <label for="jeko-contract">${t.contractIdLabel}</label>
//           <input id="jeko-contract" class="${inputDisabled}" type="text" placeholder="${t.contractIdPlaceholder}" value="${inputValue}" ${isFirstPayment ? 'disabled' : ''} />
//           <div class="error-message">${t.requiredField}</div>
//         </div>
//         ${!isFirstPayment ? `<button type="button" class="jeko-submit" data-action="verify-contract" style="margin:16px 0;width:100%;background:#fff;color:var(--jeko-primary-dark);border:2px solid var(--jeko-primary);box-shadow:none;">${t.verify}</button>` : ''}
//       `;
//         }

//         _contractSummaryTemplate() {
//             const info = this._contractInfo;
//             if (!info) return "";
//             return `Contrat n° <strong>${info.idProposition || info.contratIdWeb}</strong> — ${info.produit || ""} — ${info.souscripteur || this._paymentData.customerName || ""}`;
//         }

//         _stepperTemplate(min) {
//             if (this._numberOfPrimes < min) this._numberOfPrimes = min;
//             return `
//         <div class="jeko-stepper" data-role="stepper">
//           <button type="button" data-step="-1">−</button>
//           <span>${this._numberOfPrimes}</span>
//           <button type="button" data-step="1">+</button>
//         </div>
//       `;
//         }

//         // ---------- Step 2 : moyen de paiement ----------

//         _step2Template() {
//             const t = this.options.translations;
//             const amount = this._computeAmount();
//             const methodsHtml = PAYMENT_METHODS.map(
//                 (m) => `
//         <button type="button" class="jeko-method-btn ${m.disabled ? "cards-disabled" : ""}" data-method="${m.id}" aria-pressed="${this._selectedMethod === m.id}" aria-label="${m.label}" ${m.disabled ? "disabled" : ""}>
//           <span class="icon-wrapper"><img src="${m.iconUrl}" alt="${m.label}" loading="lazy" /></span>
//           <span class="name">${m.label}</span>
//           <span class="hint">${m.hint}</span>
//         </button>`,
//             ).join("");

//             return `
//         <div class="jeko-amount">
//           <span class="amount">${formatAmount(amount, this._paymentData.currency)}</span>
//           ${this._paymentData.description ? `<span class="description">${this._paymentData.description}</span>` : ""}
//         </div>
//         <span class="jeko-section-label">${t.chooseMethod}</span>
//         <div class="jeko-methods">${methodsHtml}</div>
//       `;
//         }

//         // ---------- Step 3 : résumé (uniquement pour earlyPayment/recoveryPrime) ----------

//         _step3Template() {
//             const t = this.options.translations;
//             const amount = this._computeAmount();
//             const method = methodMeta(this._selectedMethod);
//             const info = this._contractInfo;
//             const contractLabel = info
//                 ? `${info.idProposition || info.contratIdWeb}`
//                 : this._paymentData.contractId || "—";

//             return `
//         <p style="text-align:center;color:#6b7280;font-size:13px;margin:0 0 16px;">${t.summaryTitle}</p>
//         <div class="jeko-summary-card">
//           <div class="jeko-summary-row">
//             <span class="label">${t.summaryContract}</span>
//             <span class="value">${contractLabel}</span>
//           </div>
//           <div class="jeko-summary-row">
//             <span class="label">${t.summaryMethod}</span>
//             <span class="value method">
//               ${method ? `<img src="${method.iconUrl}" alt="${method.label}" />` : ""}
//               ${method ? method.label : "—"}
//             </span>
//           </div>
//           <div class="jeko-summary-total">
//             <div class="label">${t.summaryAmount}</div>
//             <div class="amount">${formatAmount(amount, this._paymentData.currency)}</div>
//           </div>
//         </div>
//       `;
//         }

//         // ---------- Navigation ----------

//         _goToStep(n) {
//             this._step = n;
//             this._refreshBody();
//         }

//         // ---------- Events ----------

//         _bindEvents() {
//             const modal = this._modal;

//             const closeBtn = modal.querySelector(".jeko-close");
//             if (closeBtn) closeBtn.addEventListener("click", () => this.close());

//             modal.querySelectorAll(".jeko-method-btn").forEach((btn) => {
//                 btn.addEventListener("click", () => {
//                     if (btn.disabled) return;
//                     modal
//                         .querySelectorAll(".jeko-method-btn")
//                         .forEach((b) => b.setAttribute("aria-pressed", "false"));
//                     btn.setAttribute("aria-pressed", "true");
//                     this._selectedMethod = btn.dataset.method;
                    
//                     // Mise à jour des boutons d'action
//                     const submitBtn = modal.querySelector('[data-action="submit-payment"]');
//                     if (submitBtn) submitBtn.disabled = !this._canProceedFromStep2();
                    
//                     const contBtn = modal.querySelector('[data-action="go-step-3"]');
//                     if (contBtn) contBtn.disabled = !this._canProceedFromStep2();
//                 });
//             });

//             modal.querySelectorAll("[data-additional]").forEach((btn) => {
//                 btn.addEventListener("click", () => {
//                     const value = btn.dataset.additional;
//                     this._additionalPrimes = value === "yes";
//                     if (!this._additionalPrimes) {
//                         this._additionalPrimesCount = 0;
//                     } else if (this._additionalPrimesCount === 0) {
//                         this._additionalPrimesCount = 1;
//                     }
//                     this._refreshBody();
//                 });
//             });

//             modal.querySelectorAll("[data-additional-step]").forEach((btn) => {
//                 btn.addEventListener("click", () => {
//                     const delta = Number(btn.dataset.additionalStep);
//                     const newCount = this._additionalPrimesCount + delta;
//                     if (newCount >= 1) {
//                         this._additionalPrimesCount = newCount;
//                         this._refreshBody();
//                     }
//                 });
//             });

//             modal.querySelectorAll("[data-step]").forEach((btn) => {
//                 btn.addEventListener("click", () => {
//                     const delta = Number(btn.dataset.step);
//                     const min = 1;
//                     this._numberOfPrimes = Math.max(min, this._numberOfPrimes + delta);
//                     this._refreshBody();
//                 });
//             });

//             modal.querySelectorAll("[data-switch]").forEach((btn) => {
//                 btn.addEventListener("click", () => {
//                     this._paymentData.paymentType = btn.dataset.switch;
//                     this._blocked = false;
//                     this._hasSummaryStep = this._paymentData.paymentType !== "firstPayment";
//                     this._refreshBody();
//                 });
//             });

//             modal.querySelectorAll("[data-invoice]").forEach((cb) => {
//                 cb.addEventListener("change", () => {
//                     const id = cb.dataset.invoice;
//                     if (cb.checked) this._selectedInvoiceIds.add(id);
//                     else this._selectedInvoiceIds.delete(id);
//                     this._refreshBody();
//                 });
//             });

//             const verifyBtn = modal.querySelector('[data-action="verify-contract"]');
//             if (verifyBtn)
//                 verifyBtn.addEventListener("click", () => this._manualVerifyContract());

//             const goStep2 = modal.querySelector('[data-action="go-step-2"]');
//             if (goStep2)
//                 goStep2.addEventListener("click", () => {
//                     if (this._step === 1 && !this._canProceedFromStep1()) return;
//                     this._goToStep(2);
//                 });

//             const goStep1 = modal.querySelectorAll('[data-action="go-step-1"]');
//             goStep1.forEach((b) =>
//                 b.addEventListener("click", () => this._goToStep(1)),
//             );

//             const goStep3 = modal.querySelector('[data-action="go-step-3"]');
//             if (goStep3)
//                 goStep3.addEventListener("click", () => {
//                     if (!this._canProceedFromStep2()) return;
//                     this._goToStep(3);
//                 });

//             const submitBtn = modal.querySelector('[data-action="submit-payment"]');
//             if (submitBtn)
//                 submitBtn.addEventListener("click", () => this._handleSubmit());
//         }

//         async _manualVerifyContract() {
//             if (this._isVerifyingContract) return;
//             this._isVerifyingContract = true;

//             const t = this.options.translations;
//             const input = this._modal.querySelector("#jeko-contract");
//             const idContrat = (input?.value || "").trim();
//             if (!idContrat) {
//                 if (input) {
//                     input.classList.add("error");
//                     const errorMsg = input.parentElement.querySelector(".error-message");
//                     if (errorMsg) errorMsg.classList.add("visible");
//                 }
//                 this._isVerifyingContract = false;
//                 return;
//             }

//             const btn = this._modal.querySelector('[data-action="verify-contract"]');
//             if (btn) {
//                 btn.disabled = true;
//                 btn.textContent = t.verifying;
//             }

//             try {
//                 const res = await fetch(this.options.contractCheckEndpoint, {
//                     method: "POST",
//                     headers: {
//                         "Content-Type": "application/json",
//                         Accept: "application/json",
//                         ...this.options.headers,
//                     },
//                     body: JSON.stringify({
//                         idContrat,
//                         paymentType: this._paymentData.paymentType,
//                     }),
//                 });
//                 const data = await res.json();

//                 if (!res.ok || !data.success) {
//                     if (btn) {
//                         btn.disabled = false;
//                         btn.textContent = t.verify;
//                     }
//                     this._showGeneralError(data.message || t.error);
//                     this._isVerifyingContract = false;
//                     return;
//                 }

//                 this._paymentData.contractId = idContrat;
//                 this._contractInfo = data.data;
//                 this._blocked =
//                     this._paymentData.paymentType === "earlyPayment" &&
//                     this._contractInfo.aDesImpayes === true;
//                 this._refreshBody();
//             } catch (e) {
//                 if (btn) {
//                     btn.disabled = false;
//                     btn.textContent = t.verify;
//                 }
//                 console.error(e);
//                 this._showGeneralError(t.networkError);
//             } finally {
//                 this._isVerifyingContract = false;
//             }
//         }

//         _showGeneralError(message) {
//             let el = this._modal.querySelector(".jeko-general-error");
//             if (!el) {
//                 el = document.createElement("div");
//                 el.className = "jeko-notice warn jeko-general-error";
//                 const body = this._modal.querySelector('[data-role="body"]');
//                 if (body) body.appendChild(el);
//             }
//             el.textContent = message;
//         }

//         _handleSubmit() {
//             const t = this.options.translations;
//             if (!this._selectedMethod) {
//                 this._showGeneralError("Veuillez choisir un moyen de paiement.");
//                 return;
//             }
//             if (this._blocked) return;

//             const type = this._paymentData.paymentType;
//             if (
//                 (type === "earlyPayment" || type === "recoveryPrime") &&
//                 !this._contractInfo
//             ) {
//                 this._showGeneralError(t.requiredField);
//                 return;
//             }
//             if (type === "recoveryPrime" && this._selectedInvoiceIds.size === 0) {
//                 this._showGeneralError(t.requiredField);
//                 return;
//             }

//             this._submitPayment();
//         }

//         _renderLoadingState() {
//             const t = this.options.translations;
//             this._modal.innerHTML = `<div class="jeko-state"><div class="jeko-spinner"></div><p>${t.processing}</p></div>`;
//         }

//         _renderResultState(type, title, message, options = {}) {
//             const t = this.options.translations;
//             const icon = type === "success" ? "✅" : "❌";
//             this._modal.innerHTML = `
//         <div class="jeko-state ${type}">
//           <span class="icon">${icon}</span>
//           <h4>${title}</h4>
//           <p>${message}</p>
//           ${options.showRetry ? `<button type="button" class="jeko-retry">${t.retry}</button>` : ""}
//         </div>`;
//             if (options.showRetry) {
//                 this._modal
//                     .querySelector(".jeko-retry")
//                     .addEventListener("click", () => {
//                         this._modal.innerHTML = this._shellTemplate();
//                         this._bindEvents();
//                     });
//             }
//         }

//         async _submitPayment() {
//             if (this._isSubmitting) return;
//             this._isSubmitting = true;
//             this._renderLoadingState();

//             const t = this.options.translations;
//             const {
//                 reference,
//                 paymentType,
//                 contractId,
//                 customerEmail,
//                 customerName,
//                 description,
//                 metadata,
//                 currency,
//             } = this._paymentData;

//             let numberOfPrimes = undefined;
//             if (paymentType === "firstPayment") {
//                 numberOfPrimes = 1 + (this._additionalPrimes ? this._additionalPrimesCount : 0);
//             } else if (paymentType === "earlyPayment") {
//                 numberOfPrimes = this._numberOfPrimes;
//             }

//             const payload = {
//                 reference,
//                 currency: currency || this.options.currency,
//                 paymentMethod: this._selectedMethod,
//                 paymentType,
//                 contractId: contractId || undefined,
//                 numberOfPrimes: numberOfPrimes,
//                 selectedInvoiceIds:
//                     paymentType === "recoveryPrime"
//                         ? Array.from(this._selectedInvoiceIds)
//                         : undefined,
//                 successUrl: this.options.successUrl || window.location.href,
//                 errorUrl: this.options.errorUrl || window.location.href,
//                 description,
//                 customerEmail,
//                 customerName,
//                 metadata,
//             };

//             try {
//                 const controller = new AbortController();
//                 const timeout = setTimeout(() => controller.abort(), this.options.timeout);
//                 const response = await fetch(this.options.backendEndpoint, {
//                     method: "POST",
//                     headers: {
//                         "Content-Type": "application/json",
//                         Accept: "application/json",
//                         ...this.options.headers,
//                     },
//                     body: JSON.stringify(payload),
//                     signal: controller.signal,
//                 });
//                 clearTimeout(timeout);
//                 const data = await response.json();

//                 if (!response.ok || !data.success) {
//                     this._renderResultState("error", t.error, data.message || t.error, {
//                         showRetry: true,
//                     });
//                     if (this.options.callbacks.onError)
//                         this.options.callbacks.onError(data.message, data);
//                     return;
//                 }

//                 const redirectUrl = data.data?.redirectUrl;
//                 if (!redirectUrl) {
//                     this._renderResultState(
//                         "error",
//                         t.error,
//                         "Aucune URL de paiement reçue.",
//                         { showRetry: true },
//                     );
//                     return;
//                 }

//                 this._renderResultState("success", t.success, t.successMessage);
//                 if (this.options.callbacks.onSuccess)
//                     this.options.callbacks.onSuccess(redirectUrl, data);
//                 setTimeout(() => {
//                     this.close();
//                     window.open(redirectUrl, "_blank");
//                 }, 1500);
//             } catch (error) {
//                 const msg =
//                     error.name === "AbortError"
//                         ? "La requête a expiré."
//                         : error.message || t.error;
//                 this._renderResultState("error", t.networkError, msg, { showRetry: true });
//                 if (this.options.callbacks.onError)
//                     this.options.callbacks.onError(msg, null);
//             } finally {
//                 this._isSubmitting = false;
//             }
//         }

//         isOpen() {
//             return this._isOpen;
//         }
//         getSelectedMethod() {
//             return this._selectedMethod;
//         }
//         getPaymentData() {
//             return this._paymentData;
//         }
//     }

//     window.JekoWidget = JekoWidget;
// })(window, document);

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
            radius: "16px",
            fontFamily:
                "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
            maxWidth: "550px",
        },
        translations: {
            title: "Paiement sécurisé",
            close: "Fermer",
            back: "Retour",
            stepContract: "Contrat",
            stepMethod: "Paiement",
            stepSummary: "Résumé",
            chooseMethod: "Choisissez un moyen de paiement",
            submit: "Continuer",
            continueToSummary: "Continuer",
            confirmAndPay: "Confirmer et payer",
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
            verify: "Vérifier le contrat",
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
            firstPaymentBase: "Première prime",
            firstPaymentFees: "Frais d'adhésion",
            firstPaymentTotal: "Total de la première prime",
            additionalPrimesLabel: "Souhaitez-vous également payer d'autres primes en avance ?",
            additionalPrimesYes: "Oui",
            additionalPrimesNo: "Non",
            additionalPrimesCount: "Nombre de primes en avance",
            additionalPrimesTotal: "Total des primes en avance",
            summaryTitle: "Vérifiez votre paiement",
            summaryContract: "Contrat",
            summaryMethod: "Moyen de paiement",
            summaryAmount: "Montant à payer",
            changeMethod: "Modifier",
            loadingContract: "Chargement des informations du contrat...",
        },
        callbacks: {
            onSuccess: null,
            onError: null,
            onClose: null,
            onOpen: null,
        },
    };

    function injectStyles(theme) {
        const existing = document.getElementById("jeko-widget-styles");
        if (existing) existing.remove();
        const style = document.createElement("style");
        style.id = "jeko-widget-styles";
        style.textContent = `
      .jeko-overlay {
        --jeko-primary:${theme.primary};
        --jeko-primary-dark:${theme.primaryDark};
        --jeko-accent:${theme.accent};
        --jeko-radius:${theme.radius};
        --jeko-max-width:${theme.maxWidth};
        font-family:${theme.fontFamily};
        position:fixed; inset:0;
        background:rgba(8,12,10,.65);
        display:flex; align-items:center; justify-content:center;
        z-index:999999; padding:20px;
        backdrop-filter:blur(8px);
        animation: jeko-fade-in .2s ease;
      }
      @keyframes jeko-fade-in { from { opacity:0 } to { opacity:1 } }
      @keyframes jeko-slide-up { from { opacity:0; transform:translateY(12px) } to { opacity:1; transform:translateY(0) } }
      @keyframes jeko-pop { 0% { transform:scale(.85); opacity:0 } 60% { transform:scale(1.05) } 100% { transform:scale(1); opacity:1 } }
      @keyframes jeko-check-pop { 0% { transform:scale(0) rotate(-20deg); opacity:0 } 100% { transform:scale(1) rotate(0); opacity:1 } }

      * { box-sizing:border-box; }

      .jeko-modal {
        background:#fff; width:100%;
        max-width:var(--jeko-max-width);
        border-radius:var(--jeko-radius);
        box-shadow:0 32px 80px rgba(0,0,0,.4), 0 0 0 1px rgba(255,255,255,.04);
        overflow:hidden; max-height:92vh;
        display:flex; flex-direction:column;
        animation: jeko-slide-up .28s cubic-bezier(.16,1,.3,1);
      }

      /* ============ HEADER ============ */
      .jeko-header {
        background:linear-gradient(160deg,var(--jeko-primary) 0%,var(--jeko-primary-dark) 100%);
        color:#fff; padding:24px 26px 22px;
        position:relative;
        overflow:hidden;
      }
      .jeko-header::before {
        content:"";
        position:absolute; top:-40%; right:-15%;
        width:220px; height:220px; border-radius:50%;
        background:radial-gradient(circle, rgba(255,255,255,.08) 0%, transparent 70%);
        pointer-events:none;
      }
      .jeko-header-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; position:relative; z-index:1; }
      .jeko-header h3 { margin:0; font-size:17px; font-weight:700; letter-spacing:.2px; display:flex; align-items:center; gap:8px; }
      .jeko-header h3::before {
        content:"🔒"; font-size:14px; opacity:.9;
      }
      .jeko-close {
        background:rgba(255,255,255,.12); border:none; color:#fff;
        width:30px; height:30px; border-radius:50%; font-size:16px; cursor:pointer;
        display:flex; align-items:center; justify-content:center;
        transition:background .15s, transform .15s;
      }
      .jeko-close:hover { background:rgba(255,255,255,.25); transform:rotate(90deg); }

      /* ============ STEPPER (redesigned) ============ */
      .jeko-steps {
        display:flex; align-items:flex-start; gap:0;
        position:relative; z-index:1;
      }
      .jeko-step {
        display:flex; flex-direction:column; align-items:center;
        flex:1; position:relative;
      }
      .jeko-step:first-child { align-items:flex-start; }
      .jeko-step:last-child { align-items:flex-end; flex:0 0 auto; }

      .jeko-step-track {
        display:flex; align-items:center; width:100%;
      }
      .jeko-step-circle-wrap {
        position:relative; flex-shrink:0;
        display:flex; align-items:center; justify-content:center;
      }
      .jeko-step-circle {
        width:30px; height:30px; border-radius:50%;
        display:flex; align-items:center; justify-content:center;
        font-size:12.5px; font-weight:800;
        background:rgba(255,255,255,.12); color:rgba(255,255,255,.6);
        border:2px solid rgba(255,255,255,.28);
        flex-shrink:0; position:relative; z-index:2;
        transition:all .25s cubic-bezier(.4,0,.2,1);
      }
      .jeko-step-line {
        flex:1; height:2px; background:rgba(255,255,255,.2);
        margin:0 4px; border-radius:2px; position:relative; overflow:hidden;
        top:0;
      }
      .jeko-step-line::after {
        content:""; position:absolute; inset:0; background:var(--jeko-accent);
        transform:scaleX(0); transform-origin:left; transition:transform .35s ease;
      }
      .jeko-step-line.done::after { transform:scaleX(1); }

      .jeko-step-label {
        font-size:11px; margin-top:7px; color:rgba(255,255,255,.6);
        white-space:nowrap; font-weight:600; letter-spacing:.2px;
        transition:color .2s ease;
      }

      .jeko-step.active .jeko-step-circle {
        background:#fff; color:var(--jeko-primary-dark); border-color:#fff;
        box-shadow:0 0 0 4px rgba(255,255,255,.18);
        animation: jeko-pop .3s ease;
      }
      .jeko-step.active .jeko-step-label { color:#fff; font-weight:700; }

      .jeko-step.done .jeko-step-circle {
        background:var(--jeko-accent); color:#1f2937; border-color:var(--jeko-accent);
      }
      .jeko-step.done .jeko-step-label { color:rgba(255,255,255,.85); }
      .jeko-step.done .jeko-step-circle .checkmark { animation: jeko-check-pop .3s ease; display:inline-block; }

      /* ============ BODY ============ */
      .jeko-body { padding:26px; overflow-y:auto; flex:1; animation: jeko-slide-up .22s ease; }
      .jeko-body::-webkit-scrollbar { width:6px; }
      .jeko-body::-webkit-scrollbar-thumb { background:#d1d5db; border-radius:6px; }

      .jeko-amount { text-align:center; padding:4px 0 22px; }
      .jeko-amount .amount { font-size:32px; font-weight:800; color:var(--jeko-primary-dark); display:block; letter-spacing:-.6px; }
      .jeko-amount .description { font-size:13px; color:#6b7280; margin-top:5px; display:block; }

      .jeko-section-label { font-size:13px; font-weight:700; color:#374151; margin:0 0 12px; display:block; text-align:left; letter-spacing:.1px; }

      /* Payment methods grid */
      .jeko-methods { display:grid; grid-template-columns:repeat(auto-fill,minmax(130px,1fr)); gap:10px; }
      .jeko-method-btn {
        border:2px solid #eceeed; background:#fff; border-radius:13px; padding:15px 10px;
        text-align:center; cursor:pointer; width:100%;
        display:flex; flex-direction:column; align-items:center; gap:7px;
        transition:all .18s cubic-bezier(.4,0,.2,1);
        position:relative;
      }
      .jeko-method-btn:hover:not(.cards-disabled) { border-color:#b8d2c1; background:#fafdfb; transform:translateY(-2px); box-shadow:0 6px 16px rgba(29,96,61,.08); }
      .jeko-method-btn[aria-pressed="true"] {
        border-color:var(--jeko-primary); background:#eef7f1;
        box-shadow:0 0 0 3px rgba(29,96,61,.12);
      }
      .jeko-method-btn[aria-pressed="true"]::after {
        content:"✓"; position:absolute; top:6px; right:8px;
        width:16px; height:16px; border-radius:50%; background:var(--jeko-primary);
        color:#fff; font-size:10px; font-weight:800; display:flex; align-items:center; justify-content:center;
        animation: jeko-check-pop .25s ease;
      }
      .jeko-method-btn.cards-disabled { background:#f7f7f7; cursor:not-allowed; opacity:.5; }
      .jeko-method-btn .icon-wrapper {
        width:44px; height:44px; border-radius:50%; overflow:hidden;
        display:flex; align-items:center; justify-content:center; background:#f3f4f6;
      }
      .jeko-method-btn .icon-wrapper img { width:28px; height:28px; object-fit:contain; border-radius:20%; }
      .jeko-method-btn .name { font-weight:700; font-size:12.5px; color:#111827; }
      .jeko-method-btn .hint { font-size:10.5px; color:#9ca3af; }

      /* Contract lookup */
      .jeko-lookup-icon {
        width:60px; height:60px; border-radius:18px;
        background:linear-gradient(135deg,#eef7f1,#e3f1e8);
        display:flex; align-items:center; justify-content:center;
        margin:0 auto 18px; font-size:26px;
        box-shadow:inset 0 0 0 1px rgba(29,96,61,.08);
      }
      .jeko-field { margin-top:14px; }
      .jeko-field label { display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:7px; }
      .jeko-field input {
        width:100%; box-sizing:border-box; padding:13px 14px; border-radius:11px;
        border:2px solid #e5e7eb; font-size:15px; font-family:inherit; transition:border-color .15s, box-shadow .15s;
      }
      .jeko-field input:focus { outline:none; border-color:var(--jeko-primary); box-shadow:0 0 0 3px rgba(29,96,61,.1); }
      .jeko-field input.error { border-color:#dc2626; }
      .jeko-field .error-message { color:#dc2626; font-size:12px; margin-top:6px; display:none; }
      .jeko-field .error-message.visible { display:block; }
      .disabled-input { background-color:#f3f4f6 !important; color:#9ca3af !important; cursor:not-allowed; pointer-events:none; }

      /* Stepper control (qty) */
      .jeko-stepper { display:flex; align-items:center; gap:16px; }
      .jeko-stepper button {
        width:38px; height:38px; border-radius:11px; border:2px solid #e5e7eb; background:#fff;
        font-size:18px; cursor:pointer; color:var(--jeko-primary-dark); font-weight:700;
        transition:all .15s;
      }
      .jeko-stepper button:hover { border-color:var(--jeko-primary); background:#f3faf5; }
      .jeko-stepper span { font-size:18px; font-weight:800; min-width:28px; text-align:center; color:#111827; }

      /* Invoice list */
      .jeko-invoice-list { display:flex; flex-direction:column; gap:9px; max-height:220px; overflow-y:auto; padding:2px; }
      .jeko-invoice-item {
        display:flex; align-items:center; gap:10px; border:2px solid #eceeed; border-radius:11px;
        padding:12px 13px; cursor:pointer; transition:border-color .15s, background .15s;
      }
      .jeko-invoice-item:hover { border-color:#b8d2c1; background:#fafdfb; }
      .jeko-invoice-item:has(input:checked) { border-color:var(--jeko-primary); background:#eef7f1; }
      .jeko-invoice-item .meta { flex:1; font-size:12.5px; color:#374151; }
      .jeko-invoice-item .amount { font-weight:700; font-size:13px; color:#111827; }
      .jeko-invoice-item input[type="checkbox"] { width:18px; height:18px; accent-color:var(--jeko-primary); cursor:pointer; }

      .jeko-total-bar {
        display:flex; justify-content:space-between; align-items:center; margin-top:16px;
        padding:13px 16px; background:linear-gradient(135deg,#f0f7f3,#e8f3ec); border-radius:11px;
        font-weight:700; font-size:14px; border:1px solid #dcebe1;
      }

      /* Notices */
      .jeko-notice { font-size:12.5px; padding:12px 15px; border-radius:11px; margin-bottom:15px; line-height:1.45; }
      .jeko-notice.warn { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
      .jeko-notice.info { background:#f5f8f6; color:#1f4732; border:1px solid #dce8e0; font-weight:600; }
      .jeko-notice.success { background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; }
      .jeko-link-btn {
        background:none; border:none; color:var(--jeko-primary-dark); font-weight:700;
        text-decoration:underline; cursor:pointer; padding:0; margin-top:2px; font-family:inherit; font-size:12.5px;
      }

      /* Toggle */
      .jeko-toggle-group { display:flex; gap:10px; margin-top:8px; }
      .jeko-toggle-btn {
        flex:1; border:2px solid #e5e7eb; background:#fff; border-radius:11px; padding:12px 14px;
        text-align:center; cursor:pointer; font-family:inherit; font-weight:700; font-size:13.5px;
        transition:all .15s;
      }
      .jeko-toggle-btn:hover { border-color:#b8d2c1; }
      .jeko-toggle-btn[aria-pressed="true"] { border-color:var(--jeko-primary); background:#eef7f1; color:var(--jeko-primary-dark); }

      /* Breakdown */
      .jeko-breakdown { background:#f9fafb; border-radius:13px; padding:15px 17px; margin-top:15px; border:1px solid #f0f0f0; }
      .jeko-breakdown-row { display:flex; justify-content:space-between; padding:5px 0; font-size:13px; color:#4b5563; }
      .jeko-breakdown-row.total { font-weight:800; color:#111827; border-top:2px solid #e5e7eb; padding-top:10px; margin-top:6px; font-size:14px; }

      /* Summary step (step 3) */
      .jeko-summary-card { border:2px solid #eef1ef; border-radius:15px; overflow:hidden; }
      .jeko-summary-row {
        display:flex; align-items:center; justify-content:space-between;
        padding:15px 17px; border-bottom:1px solid #f0f0f0;
      }
      .jeko-summary-row:last-child { border-bottom:none; }
      .jeko-summary-row .label { font-size:12.5px; color:#6b7280; font-weight:600; }
      .jeko-summary-row .value { font-size:13.5px; color:#111827; font-weight:700; text-align:right; }
      .jeko-summary-row .value.method { display:flex; align-items:center; gap:8px; }
      .jeko-summary-row .value.method img { width:22px; height:22px; border-radius:5px; object-fit:contain; }
      .jeko-summary-total {
        background:linear-gradient(160deg,var(--jeko-primary),var(--jeko-primary-dark));
        color:#fff; padding:20px 17px; text-align:center; position:relative; overflow:hidden;
      }
      .jeko-summary-total::before {
        content:""; position:absolute; top:-50%; left:-10%;
        width:180px; height:180px; border-radius:50%;
        background:radial-gradient(circle, rgba(255,255,255,.08), transparent 70%);
      }
      .jeko-summary-total .label { font-size:12px; opacity:.85; font-weight:600; letter-spacing:.4px; text-transform:uppercase; position:relative; }
      .jeko-summary-total .amount { font-size:29px; font-weight:800; margin-top:5px; letter-spacing:-.5px; position:relative; }

      /* ============ FOOTER ============ */
      .jeko-footer { padding:18px 26px 24px; border-top:1px solid #f0f0f0; display:flex; gap:10px; }
      .jeko-btn-back {
        border:2px solid #e5e7eb; background:#fff; color:#374151; font-weight:700; font-size:14px;
        padding:14px 18px; border-radius:12px; cursor:pointer; font-family:inherit; transition:all .15s;
      }
      .jeko-btn-back:hover { border-color:#c9d8ce; background:#fafafa; }
      .jeko-submit {
        flex:1; border:none; background:var(--jeko-accent); color:#1f2937; font-weight:800; font-size:15px;
        padding:14px; border-radius:12px; cursor:pointer; font-family:inherit; transition:filter .15s, transform .1s, box-shadow .15s;
        box-shadow:0 4px 14px rgba(224,149,24,.28);
      }
      .jeko-submit:hover:not(:disabled) { filter:brightness(1.05); box-shadow:0 6px 18px rgba(224,149,24,.35); }
      .jeko-submit:active:not(:disabled) { transform:scale(.99); }
      .jeko-submit:disabled { opacity:.45; cursor:not-allowed; box-shadow:none; }
      .jeko-submit.outline {
        background:#fff; color:var(--jeko-primary-dark); border:2px solid var(--jeko-primary); box-shadow:none;
      }

      /* State screens */
      .jeko-state { padding:46px 26px; text-align:center; min-height:230px; display:flex; flex-direction:column; align-items:center; justify-content:center; }
      .jeko-state h4 { margin:6px 0 6px; font-size:17px; color:#111827; }
      .jeko-state p { margin:0; font-size:13.5px; color:#6b7280; max-width:280px; }
      .jeko-state .icon { font-size:54px; margin-bottom:10px; animation: jeko-pop .35s ease; }
      .jeko-spinner {
        width:44px; height:44px; border:3px solid #e5e7eb; border-top-color:var(--jeko-primary);
        border-radius:50%; margin:0 auto 18px; animation:jeko-spin .8s linear infinite;
      }
      @keyframes jeko-spin { to { transform:rotate(360deg); } }
      .jeko-retry {
        margin-top:18px; border:2px solid var(--jeko-primary); background:#fff; color:var(--jeko-primary-dark);
        font-weight:700; padding:11px 26px; border-radius:11px; cursor:pointer; font-family:inherit; transition:background .15s;
      }
      .jeko-retry:hover { background:#f3faf5; }
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

    function methodMeta(id) {
        return PAYMENT_METHODS.find((m) => m.id === id);
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
            this._isVerifyingContract = false;
            this._additionalPrimes = false;
            this._additionalPrimesCount = 0;
            // step: 1 = contrat, 2 = moyen de paiement, 3 = résumé
            this._step = 1;
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
            this._contractInfo = null;
            this._selectedInvoiceIds = new Set();
            this._blocked = false;
            this._additionalPrimes = false;
            this._additionalPrimesCount = 0;
            this._step = 1;

            this._render();
            this._isOpen = true;
            document.addEventListener("keydown", this._escHandler);
            if (this.options.callbacks.onOpen)
                this.options.callbacks.onOpen(this._paymentData);

            // Pour firstPayment : la vérification du contrat est automatique,
            // l'utilisateur n'a pas à saisir/soumettre un identifiant.
            if (this._paymentData.paymentType === "firstPayment") {
                this._autoVerifyFirstPayment();
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
            modal.innerHTML = this._shellTemplate();
            overlay.appendChild(modal);
            document.body.appendChild(overlay);
            this._overlay = overlay;
            this._modal = modal;

            this._bindEvents();
        }

        // ---------- Helpers de calcul ----------

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

        _isFirstPayment() {
            return this._paymentData.paymentType === "firstPayment";
        }

        _canProceedFromStep1() {
            const type = this._paymentData.paymentType;
            if (!this._contractInfo) return false;
            if (type === "earlyPayment" && this._blocked) return false;
            if (
                type === "recoveryPrime" &&
                (this._contractInfo.facturesImpayees || []).length === 0
            )
                return false;
            return true;
        }

        _canProceedFromStep2() {
            if (!this._selectedMethod) return false;
            if (
                this._paymentData.paymentType === "recoveryPrime" &&
                this._selectedInvoiceIds.size === 0
            )
                return false;
            return true;
        }

        // ---------- Shell + Stepper ----------

        _shellTemplate() {
            const t = this.options.translations;
            return `
        <div class="jeko-header">
          <div class="jeko-header-top">
            <h3>${t.title}</h3>
            <button type="button" class="jeko-close" aria-label="${t.close}">&times;</button>
          </div>
          ${this._stepperHeaderTemplate()}
        </div>
        <div class="jeko-body" data-role="body">${this._stepBodyTemplate()}</div>
        <div class="jeko-footer" data-role="footer">${this._footerTemplate()}</div>
      `;
        }

        // Pour firstPayment, le parcours n'a que 2 étapes utiles (Contrat -> Paiement),
        // il n'y a pas d'étape "Résumé" séparée.
        _visibleSteps() {
            const t = this.options.translations;
            if (this._isFirstPayment()) {
                return [
                    { n: 1, label: t.stepContract },
                    { n: 2, label: t.stepMethod },
                ];
            }
            return [
                { n: 1, label: t.stepContract },
                { n: 2, label: t.stepMethod },
                { n: 3, label: t.stepSummary },
            ];
        }

        _stepperHeaderTemplate() {
            const steps = this._visibleSteps();
            return `
        <div class="jeko-steps">
          ${steps
              .map((s, i) => {
                  const state =
                      this._step === s.n
                          ? "active"
                          : this._step > s.n
                            ? "done"
                            : "";
                  const circleContent =
                      this._step > s.n
                          ? `<span class="checkmark">✓</span>`
                          : String(s.n);
                  return `
              <div class="jeko-step ${state}">
                <div class="jeko-step-track">
                  <div class="jeko-step-circle-wrap">
                    <div class="jeko-step-circle">${circleContent}</div>
                  </div>
                  ${i < steps.length - 1 ? `<div class="jeko-step-line ${this._step > s.n ? "done" : ""}"></div>` : ""}
                </div>
                <span class="jeko-step-label">${s.label}</span>
              </div>
            `;
              })
              .join("")}
        </div>
      `;
        }

        _stepBodyTemplate() {
            if (this._step === 1) return this._step1Template();
            if (this._step === 2) return this._step2Template();
            return this._step3Template();
        }

        _footerTemplate() {
            const t = this.options.translations;
            if (this._step === 1) {
                // Pour firstPayment, la vérification est automatique : tant qu'elle est
                // en cours ou pas encore faite, on n'affiche pas de bouton "Continuer".
                if (this._isFirstPayment()) {
                    if (!this._contractInfo) return "";
                    return `<button type="button" class="jeko-submit" data-action="go-step-2">${t.continueToSummary}</button>`;
                }
                if (!this._canProceedFromStep1()) return "";
                return `<button type="button" class="jeko-submit" data-action="go-step-2">${t.continueToSummary}</button>`;
            }
            if (this._step === 2) {
                const confirmLabel = this._isFirstPayment()
                    ? t.confirmAndPay
                    : t.continueToSummary;
                const confirmAction = this._isFirstPayment()
                    ? "submit-payment"
                    : "go-step-3";
                return `
          <button type="button" class="jeko-btn-back" data-action="go-step-1">${t.back}</button>
          <button type="button" class="jeko-submit" data-action="${confirmAction}" ${this._canProceedFromStep2() ? "" : "disabled"}>${confirmLabel}</button>
        `;
            }
            if (this._step === 3) {
                return `
          <button type="button" class="jeko-btn-back" data-action="go-step-2">${t.back}</button>
          <button type="button" class="jeko-submit" data-action="submit-payment">${t.confirmAndPay}</button>
        `;
            }
            return "";
        }

        _refreshBody() {
            const body = this._modal.querySelector('[data-role="body"]');
            const footer = this._modal.querySelector('[data-role="footer"]');
            const header = this._modal.querySelector(".jeko-header");
            body.innerHTML = this._stepBodyTemplate();
            footer.innerHTML = this._footerTemplate();
            header.innerHTML = `
        <div class="jeko-header-top">
          <h3>${this.options.translations.title}</h3>
          <button type="button" class="jeko-close" aria-label="${this.options.translations.close}">&times;</button>
        </div>
        ${this._stepperHeaderTemplate()}
      `;
            this._bindEvents();
        }

        // ---------- Vérification automatique (firstPayment) ----------

        async _autoVerifyFirstPayment() {
            this._isVerifyingContract = true;
            this._refreshBody();

            const t = this.options.translations;
            try {
                const res = await fetch(this.options.contractCheckEndpoint, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        ...this.options.headers,
                    },
                    body: JSON.stringify({
                        idContrat: this._paymentData.contractId || null,
                        paymentType: this._paymentData.paymentType,
                        reference: this._paymentData.reference,
                    }),
                });
                const data = await res.json();

                if (!res.ok || !data.success) {
                    this._isVerifyingContract = false;
                    this._refreshBody();
                    this._showGeneralError(data.message || t.error);
                    return;
                }

                this._contractInfo = data.data;
                if (data.data?.idProposition || data.data?.contratIdWeb) {
                    this._paymentData.contractId =
                        data.data.idProposition || data.data.contratIdWeb;
                }
                this._isVerifyingContract = false;
                this._refreshBody();
            } catch (e) {
                console.error(e);
                this._isVerifyingContract = false;
                this._refreshBody();
                this._showGeneralError(t.networkError);
            }
        }

        // ---------- Step 1 : contrat ----------

        _step1Template() {
            const t = this.options.translations;
            const type = this._paymentData.paymentType;

            if (type === "firstPayment") {
                if (this._isVerifyingContract || !this._contractInfo) {
                    return this._contractLoadingTemplate();
                }
                return this._firstPaymentTemplate();
            }

            if (!this._contractInfo) {
                return this._contractLookupTemplate();
            }

            if (type === "earlyPayment") {
                if (this._blocked) {
                    return `
            <div class="jeko-notice info">${this._contractSummaryTemplate()}</div>
            <div class="jeko-notice warn">${t.blockedEarlyPayment}</div>
            <button type="button" class="jeko-link-btn" data-switch="recoveryPrime">${t.switchToRecovery}</button>
          `;
                }
                const amount = this._computeAmount();
                return `
          <div class="jeko-notice info">${this._contractSummaryTemplate()}</div>
          <span class="jeko-section-label" style="text-align: center">${t.numberOfPrimesLabel}</span>
          <div style="display:flex;justify-content:center;margin:6px 0 4px;">${this._stepperTemplate(1)}</div>
          <div class="jeko-total-bar"><span>${t.totalLabel}</span><span>${formatAmount(amount, this._paymentData.currency)}</span></div>
        `;
            }

            if (type === "recoveryPrime") {
                const list = this._contractInfo.facturesImpayees || [];
                if (list.length === 0) {
                    return `
            <div class="jeko-notice info">${this._contractSummaryTemplate()}</div>
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
          <div class="jeko-notice info">${this._contractSummaryTemplate()}</div>
          <span class="jeko-section-label">${t.selectInvoicesLabel}</span>
          <div class="jeko-invoice-list">${itemsHtml}</div>
          <div class="jeko-total-bar"><span>${t.totalLabel}</span><span>${formatAmount(total, this._paymentData.currency)}</span></div>
        `;
            }

            return "";
        }

        _contractLoadingTemplate() {
            const t = this.options.translations;
            return `
        <div class="jeko-state" style="min-height:180px;padding:30px 20px;">
          <div class="jeko-spinner"></div>
          <p>${t.loadingContract}</p>
        </div>
      `;
        }

        _firstPaymentTemplate() {
            const t = this.options.translations;
            const prime = Number(this._contractInfo?.primePrincipale || 0);
            const frais = Number(this._contractInfo?.fraisAdhesion || 0);
            const firstPrimeTotal = frais + prime;
            const additionalTotal = this._additionalPrimes ? prime * this._additionalPrimesCount : 0;
            const totalAmount = firstPrimeTotal + additionalTotal;

            return `
        <div class="jeko-notice info">${this._contractSummaryTemplate()}</div>

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
            ${
                this._additionalPrimes && this._additionalPrimesCount > 0
                    ? `
            <div class="jeko-breakdown-row">
                <span>${t.additionalPrimesTotal} (×${this._additionalPrimesCount})</span>
                <span>${formatAmount(additionalTotal, this._paymentData.currency)}</span>
            </div>
            <div class="jeko-breakdown-row total">
                <span>${t.totalLabel}</span>
                <span>${formatAmount(totalAmount, this._paymentData.currency)}</span>
            </div>`
                    : ""
            }
        </div>

        <span class="jeko-section-label" style="margin-top:16px; display:none;">${t.additionalPrimesLabel}</span>
        <div class="jeko-toggle-group" style="margin-top:16px; display:none;">
            <button type="button" class="jeko-toggle-btn" data-additional="yes" aria-pressed="${this._additionalPrimes ? "true" : "false"}">${t.additionalPrimesYes}</button>
            <button type="button" class="jeko-toggle-btn" data-additional="no" aria-pressed="${!this._additionalPrimes ? "true" : "false"}">${t.additionalPrimesNo}</button>
        </div>

        ${
            this._additionalPrimes
                ? `
            <div class="jeko-field" style="margin:14px auto 0; width:fit-content; text-align:center;">
                <label for="jeko-additional-count">${t.additionalPrimesCount}</label>
                <div class="jeko-stepper" data-role="additional-stepper">
                    <button type="button" data-additional-step="-1">−</button>
                    <span>${this._additionalPrimesCount}</span>
                    <button type="button" data-additional-step="1">+</button>
                </div>
            </div>`
                : ""
        }
      `;
        }

        _contractLookupTemplate() {
            const t = this.options.translations;
            return `
        <div class="jeko-lookup-icon">📄</div>
        <div class="jeko-field" data-role="contract">
          <label for="jeko-contract">${t.contractIdLabel}</label>
          <input id="jeko-contract" type="text" placeholder="${t.contractIdPlaceholder}" value="${this._paymentData.contractId || ""}" />
          <div class="error-message">${t.requiredField}</div>
        </div>
        <button type="button" class="jeko-submit" data-action="verify-contract" style="margin:16px 0;width:100%;background:#fff;color:var(--jeko-primary-dark);border:2px solid var(--jeko-primary);box-shadow:none;">${t.verify}</button>
      `;
        }

        _contractSummaryTemplate() {
            const info = this._contractInfo;
            if (!info) return "";
            return `Contrat n° <strong>${info.idProposition || info.contratIdWeb}</strong> — ${info.produit || ""} — ${info.souscripteur || this._paymentData.customerName || ""}`;
        }

        _stepperTemplate(min) {
            if (this._numberOfPrimes < min) this._numberOfPrimes = min;
            return `
        <div class="jeko-stepper" data-role="stepper">
          <button type="button" data-step="-1">−</button>
          <span>${this._numberOfPrimes}</span>
          <button type="button" data-step="1">+</button>
        </div>
      `;
        }

        // ---------- Step 2 : moyen de paiement ----------

        _step2Template() {
            const t = this.options.translations;
            const amount = this._computeAmount();
            const methodsHtml = PAYMENT_METHODS.map(
                (m) => `
        <button type="button" class="jeko-method-btn ${m.disabled ? "cards-disabled" : ""}" data-method="${m.id}" aria-pressed="${this._selectedMethod === m.id}" aria-label="${m.label}" ${m.disabled ? "disabled" : ""}>
          <span class="icon-wrapper"><img src="${m.iconUrl}" alt="${m.label}" loading="lazy" /></span>
          <span class="name">${m.label}</span>
          <span class="hint">${m.hint}</span>
        </button>`,
            ).join("");

            return `
        <div class="jeko-amount">
          <span class="amount">${formatAmount(amount, this._paymentData.currency)}</span>
          ${this._paymentData.description ? `<span class="description">${this._paymentData.description}</span>` : ""}
        </div>
        <span class="jeko-section-label">${t.chooseMethod}</span>
        <div class="jeko-methods">${methodsHtml}</div>
      `;
        }

        // ---------- Step 3 : résumé (non utilisé pour firstPayment) ----------

        _step3Template() {
            const t = this.options.translations;
            const amount = this._computeAmount();
            const method = methodMeta(this._selectedMethod);
            const info = this._contractInfo;
            const contractLabel = info
                ? `${info.idProposition || info.contratIdWeb}`
                : this._paymentData.contractId || "—";

            return `
        <p style="text-align:center;color:#6b7280;font-size:13px;margin:0 0 16px;">${t.summaryTitle}</p>
        <div class="jeko-summary-card">
          <div class="jeko-summary-row">
            <span class="label">${t.summaryContract}</span>
            <span class="value">${contractLabel}</span>
          </div>
          <div class="jeko-summary-row">
            <span class="label">${t.summaryMethod}</span>
            <span class="value method">
              ${method ? `<img src="${method.iconUrl}" alt="${method.label}" />` : ""}
              ${method ? method.label : "—"}
            </span>
          </div>
          <div class="jeko-summary-total">
            <div class="label">${t.summaryAmount}</div>
            <div class="amount">${formatAmount(amount, this._paymentData.currency)}</div>
          </div>
        </div>
      `;
        }

        // ---------- Navigation ----------

        _goToStep(n) {
            this._step = n;
            this._refreshBody();
        }

        // ---------- Events ----------

        _bindEvents() {
            const modal = this._modal;

            const closeBtn = modal.querySelector(".jeko-close");
            if (closeBtn) closeBtn.addEventListener("click", () => this.close());

            modal.querySelectorAll(".jeko-method-btn").forEach((btn) => {
                btn.addEventListener("click", () => {
                    if (btn.disabled) return;
                    modal
                        .querySelectorAll(".jeko-method-btn")
                        .forEach((b) => b.setAttribute("aria-pressed", "false"));
                    btn.setAttribute("aria-pressed", "true");
                    this._selectedMethod = btn.dataset.method;
                    const cont = modal.querySelector(
                        '[data-action="go-step-3"], [data-action="submit-payment"]',
                    );
                    if (cont) cont.disabled = !this._canProceedFromStep2();
                });
            });

            modal.querySelectorAll("[data-additional]").forEach((btn) => {
                btn.addEventListener("click", () => {
                    const value = btn.dataset.additional;
                    this._additionalPrimes = value === "yes";
                    if (!this._additionalPrimes) {
                        this._additionalPrimesCount = 0;
                    } else if (this._additionalPrimesCount === 0) {
                        this._additionalPrimesCount = 1;
                    }
                    this._refreshBody();
                });
            });

            modal.querySelectorAll("[data-additional-step]").forEach((btn) => {
                btn.addEventListener("click", () => {
                    const delta = Number(btn.dataset.additionalStep);
                    const newCount = this._additionalPrimesCount + delta;
                    if (newCount >= 1) {
                        this._additionalPrimesCount = newCount;
                        this._refreshBody();
                    }
                });
            });

            modal.querySelectorAll("[data-step]").forEach((btn) => {
                btn.addEventListener("click", () => {
                    const delta = Number(btn.dataset.step);
                    const min = 1;
                    this._numberOfPrimes = Math.max(min, this._numberOfPrimes + delta);
                    this._refreshBody();
                });
            });

            modal.querySelectorAll("[data-switch]").forEach((btn) => {
                btn.addEventListener("click", () => {
                    this._paymentData.paymentType = btn.dataset.switch;
                    this._blocked = false;
                    this._refreshBody();
                });
            });

            modal.querySelectorAll("[data-invoice]").forEach((cb) => {
                cb.addEventListener("change", () => {
                    const id = cb.dataset.invoice;
                    if (cb.checked) this._selectedInvoiceIds.add(id);
                    else this._selectedInvoiceIds.delete(id);
                    this._refreshBody();
                });
            });

            const verifyBtn = modal.querySelector('[data-action="verify-contract"]');
            if (verifyBtn)
                verifyBtn.addEventListener("click", () => this._verifyContract());

            const goStep2 = modal.querySelector('[data-action="go-step-2"]');
            if (goStep2)
                goStep2.addEventListener("click", () => {
                    if (this._step === 1 && !this._isFirstPayment() && !this._canProceedFromStep1())
                        return;
                    if (this._step === 1 && this._isFirstPayment() && !this._contractInfo)
                        return;
                    this._goToStep(2);
                });

            const goStep1 = modal.querySelectorAll('[data-action="go-step-1"]');
            goStep1.forEach((b) =>
                b.addEventListener("click", () => this._goToStep(1)),
            );

            const goStep3 = modal.querySelector('[data-action="go-step-3"]');
            if (goStep3)
                goStep3.addEventListener("click", () => {
                    if (!this._canProceedFromStep2()) return;
                    this._goToStep(3);
                });

            const submitBtn = modal.querySelector('[data-action="submit-payment"]');
            if (submitBtn)
                submitBtn.addEventListener("click", () => {
                    if (submitBtn.disabled) return;
                    this._handleSubmit();
                });
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
                this._blocked =
                    this._paymentData.paymentType === "earlyPayment" &&
                    this._contractInfo.aDesImpayes === true;
                this._refreshBody();
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
                const body = this._modal.querySelector('[data-role="body"]');
                if (body) body.appendChild(el);
            }
            el.textContent = message;
        }

        _handleSubmit() {
            const t = this.options.translations;
            if (!this._selectedMethod) {
                this._showGeneralError("Veuillez choisir un moyen de paiement.");
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
            if (type === "recoveryPrime" && this._selectedInvoiceIds.size === 0) {
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
                    .addEventListener("click", () => {
                        this._modal.innerHTML = this._shellTemplate();
                        this._bindEvents();
                    });
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
                const timeout = setTimeout(() => controller.abort(), this.options.timeout);
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
                    this._renderResultState("error", t.error, data.message || t.error, {
                        showRetry: true,
                    });
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
                    // Fermer le modal
                    this.close();
                    window.open(redirectUrl, "_blank");
                }, 1500);
            } catch (error) {
                const msg =
                    error.name === "AbortError"
                        ? "La requête a expiré."
                        : error.message || t.error;
                this._renderResultState("error", t.networkError, msg, { showRetry: true });
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
