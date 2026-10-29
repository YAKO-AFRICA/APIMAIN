<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#1DC8FF">
    <title>Factures impayées — Contrat {{ $identifiant }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --wave: #1DC8FF;
            --wave-press: #12B4E8;
            --wave-deep: #0A7FA8;
            --wave-tint: #E8F8FE;
            --wave-line: #BFEBFA;
            --ink: #0F1F2B;
            --ink-soft: #5B6B76;
            --line: #E3EAEE;
            --card: #FFFFFF;
            --danger: #B42318;
            --danger-tint: #FEF0EE;
            --ok: #067647;
            --font: 'Plus Jakarta Sans', 'Segoe UI', system-ui, -apple-system, Roboto, Arial, sans-serif;
        }

        *,
        *::before,
        *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            -webkit-text-size-adjust: 100%;
        }

        body {
            font-family: var(--font);
            background: var(--wave);
            color: var(--ink);
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 14px;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        .num {
            font-variant-numeric: tabular-nums;
        }

        :focus-visible {
            outline: 3px solid var(--ink);
            outline-offset: 2px;
        }

        /* ---------- Widget ---------- */
        .widget {
            width: 100%;
            max-width: 420px;
            background: var(--card);
            border-radius: 26px;
            box-shadow: 0 24px 60px rgba(6, 70, 95, .35);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .w-head {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 20px 22px 16px;
        }

        .w-badge {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: var(--wave);
            color: #fff;
            display: grid;
            place-items: center;
            flex-shrink: 0;
        }

        .w-head h1 {
            font-size: 17px;
            font-weight: 800;
            letter-spacing: -.01em;
            line-height: 1.2;
        }

        .w-head p {
            font-size: 12.5px;
            color: var(--ink-soft);
        }

        .w-body {
            padding: 0 22px;
        }

        /* Résumé du contrat */
        .contract {
            background: var(--wave-tint);
            border: 1px solid var(--wave-line);
            border-radius: 14px;
            padding: 6px 14px;
            margin-bottom: 16px;
        }

        .contract .row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 7px 0;
            font-size: 13px;
        }

        .contract .row+.row {
            border-top: 1px dashed var(--wave-line);
        }

        .contract .key {
            color: var(--wave-deep);
            font-weight: 600;
        }

        .contract .value {
            font-weight: 700;
            text-align: right;
            overflow-wrap: anywhere;
        }

        /* En-tête de liste */
        .list-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 8px;
        }

        .list-head h2 {
            font-size: 14px;
            font-weight: 700;
        }

        .list-head .hint {
            font-size: 12px;
            color: var(--ink-soft);
        }

        .btn-link {
            background: none;
            border: 0;
            font: inherit;
            font-size: 13px;
            font-weight: 700;
            color: var(--wave-deep);
            padding: 6px 8px;
            margin-right: -8px;
            border-radius: 8px;
            cursor: pointer;
            white-space: nowrap;
        }

        .btn-link:hover:not(:disabled) {
            background: var(--wave-tint);
        }

        /* Alerte */
        .alert {
            display: none;
            align-items: flex-start;
            gap: 8px;
            background: var(--danger-tint);
            color: var(--danger);
            border: 1px solid #F6C6C0;
            border-radius: 12px;
            padding: 10px 12px;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 10px;
        }

        .alert.visible {
            display: flex;
        }

        .alert svg {
            flex-shrink: 0;
            margin-top: 2px;
        }

        /* Liste scrollable */
        .invoice-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-height: min(36vh, 300px);
            overflow-y: auto;
            padding: 3px 3px 6px;
            margin: 0 -3px;
            scrollbar-width: thin;
            scrollbar-color: var(--wave-line) transparent;
        }

        .invoice {
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1.5px solid var(--line);
            border-radius: 14px;
            padding: 12px 14px;
            min-height: 60px;
            cursor: pointer;
            transition: border-color .15s, background-color .15s;
        }

        .invoice:hover {
            border-color: var(--wave-line);
        }

        .invoice:has(input:checked) {
            border-color: var(--wave);
            background: var(--wave-tint);
        }

        .invoice:has(input:focus-visible) {
            outline: 3px solid var(--ink);
            outline-offset: 2px;
        }

        .invoice input {
            appearance: none;
            -webkit-appearance: none;
            width: 22px;
            height: 22px;
            border: 2px solid #A7B5BE;
            border-radius: 50%;
            background: #fff;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            cursor: pointer;
            transition: background-color .15s, border-color .15s;
        }

        .invoice input::after {
            content: "";
            width: 10px;
            height: 6px;
            border-left: 2.5px solid #fff;
            border-bottom: 2.5px solid #fff;
            transform: rotate(-45deg) scale(0);
            margin-top: -2px;
            transition: transform .15s ease;
        }

        .invoice input:checked {
            background: var(--wave);
            border-color: var(--wave);
        }

        .invoice input:checked::after {
            transform: rotate(-45deg) scale(1);
        }

        .invoice .meta {
            flex: 1;
            min-width: 0;
        }

        .invoice .code {
            display: block;
            font-weight: 700;
            font-size: 13.5px;
            overflow-wrap: anywhere;
        }

        .invoice .date {
            display: block;
            font-size: 12px;
            color: var(--ink-soft);
        }

        .invoice .amount {
            font-weight: 800;
            font-size: 14.5px;
            white-space: nowrap;
            text-align: right;
        }

        .invoice .amount small {
            display: block;
            font-size: 11px;
            font-weight: 600;
            color: var(--ink-soft);
        }

        /* Pied : total + paiement */
        .w-foot {
            padding: 16px 22px calc(20px + env(safe-area-inset-bottom, 0px));
            border-top: 1px solid var(--line);
            margin-top: 12px;
            background: #fff;
        }

        .total {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

        .total .label {
            font-size: 13px;
            color: var(--ink-soft);
            font-weight: 500;
        }

        .total .amount {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -.01em;
        }

        .btn-pay {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            min-height: 54px;
            border: 0;
            border-radius: 16px;
            background: var(--wave);
            color: var(--ink);
            font: inherit;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
            transition: background-color .15s, transform .1s;
        }

        .btn-pay:hover:not(:disabled) {
            background: var(--wave-press);
        }

        .btn-pay:active:not(:disabled) {
            transform: scale(.985);
        }

        .btn-pay:disabled {
            background: #E4EAEE;
            color: #7A8B96;
            cursor: not-allowed;
        }

        .btn-pay.loading {
            cursor: progress;
        }

        .spinner {
            width: 18px;
            height: 18px;
            border: 3px solid rgba(15, 31, 43, .25);
            border-top-color: var(--ink);
            border-radius: 50%;
            animation: spin .7s linear infinite;
        }

        .spinner[hidden] {
            display: none;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .secure {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 10px;
            font-size: 12px;
            color: var(--ink-soft);
        }

        /* État vide */
        .empty {
            text-align: center;
            padding: 20px 22px 36px;
        }

        .empty .icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #E7F6EE;
            color: var(--ok);
            display: grid;
            place-items: center;
            margin: 0 auto 14px;
        }

        .empty h2 {
            font-size: 17px;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .empty p {
            color: var(--ink-soft);
            font-size: 13.5px;
        }

        @media (max-width: 380px) {

            .w-head,
            .w-body,
            .w-foot {
                padding-left: 16px;
                padding-right: 16px;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {
                animation-duration: .01ms !important;
                transition-duration: .01ms !important;
            }
        }
    </style>
</head>

<body>

    @php
        $devise = $contrat['devise'] ?? 'XOF';
    @endphp

    <main class="widget" aria-labelledby="w-title">

        <header class="w-head">
            <div class="w-badge" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2 9c2.5-3 5-3 7.5 0s5 3 7.5 0 3-2.5 5-2" />
                    <path d="M2 16c2.5-3 5-3 7.5 0s5 3 7.5 0 3-2.5 5-2" />
                </svg>
            </div>
            <div>
                <h1 id="w-title">Factures impayées</h1>
                <p>Réglez vos primes avec Wave</p>
            </div>
        </header>

        @if (count($facturesImpayees) === 0)
            <section class="empty">
                <div class="icon" aria-hidden="true">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 6 9 17l-5-5" />
                    </svg>
                </div>
                <h2>Tout est à jour</h2>
                <p>Aucune facture impayée sur le contrat {{ $identifiant }}.</p>
            </section>
        @else
            <div class="w-body">
                <section class="contract" aria-label="Contrat">
                    <div class="row">
                        <span class="key">Souscripteur</span>
                        <span class="value">{{ $contrat['souscripteur'] ?? '—' }}</span>
                    </div>
                    <div class="row">
                        <span class="key">Contrat</span>
                        <span class="value num">{{ $identifiant }}</span>
                    </div>
                    <div class="row">
                        <span class="key">Produit</span>
                        <span class="value">{{ $contrat['produit'] ?? '—' }}</span>
                    </div>
                </section>

                <div class="list-head">
                    <div>
                        <h2>Primes à régler</h2>
                        <p class="hint" id="countHint">{{ count($facturesImpayees) }} facture(s) impayée(s)</p>
                    </div>
                    <button type="button" id="btnToggleAll" class="btn-link">Tout cocher</button>
                </div>

                <div class="alert" id="errorMsg" role="alert" aria-live="assertive">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M12 8v5M12 16.5h.01" />
                    </svg>
                    <span id="errorText"></span>
                </div>

                <div class="invoice-list" id="invoiceList" role="group" aria-label="Factures impayées">
                    @foreach ($facturesImpayees as $f)
                        <label class="invoice">
                            <input type="checkbox" class="invoice-checkbox" data-id="{{ $f['IdPresentation'] }}"
                                data-amount="{{ (int) $f['MontantNet'] }}" />
                            <span class="meta">
                                <span class="code">Réf {{ $f['CodePresentation'] ?? $f['IdPresentation'] }}</span>
                                @if (!empty($f['MaDate']))
                                    <span class="date">Date : {{ $f['MaDate'] }}</span>
                                @endif
                            </span>
                            <span class="amount num">
                                {{ number_format($f['MontantNet'], 0, ',', ' ') }}
                                <small>{{ $devise }}</small>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            <footer class="w-foot">
                <div class="total" aria-live="polite">
                    <span class="label" id="totalLabel">Aucune facture sélectionnée</span>
                    <span class="amount num" id="totalAmount">0 {{ $devise }}</span>
                </div>
                <button type="button" id="btnPay" class="btn-pay" disabled>
                    <span class="spinner" id="btnSpinner" hidden aria-hidden="true"></span>
                    <span id="btnPayText">Payer avec Wave</span>
                </button>
                <p class="secure">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="4" y="11" width="16" height="10" rx="2" />
                        <path d="M8 11V7a4 4 0 0 1 8 0v4" />
                    </svg>
                    Paiement sécurisé : vous serez redirigé vers Wave
                </p>
            </footer>
        @endif
    </main>

    {{-- Widget Wave --}}
    <script src="{{ url('/api/wave/wave-payment-widget.js') }}"></script>
    <script>
        (function() {
            "use strict";

            const CONTRAT_ID = @json((string) $identifiant);
            const CONTRAT_INFO = {
                idProposition: @json((string) ($contrat['idProposition'] ?? $identifiant)),
                souscripteur: @json((string) ($contrat['souscripteur'] ?? '')),
                produit: @json((string) ($contrat['produit'] ?? '')),
                devise: @json((string) ($contrat['devise'] ?? 'XOF')),
            };

            const invoiceList = document.getElementById("invoiceList");
            const totalAmount = document.getElementById("totalAmount");
            const totalLabel = document.getElementById("totalLabel");
            const countHint = document.getElementById("countHint");
            const btnPay = document.getElementById("btnPay");
            const btnPayText = document.getElementById("btnPayText");
            const btnSpinner = document.getElementById("btnSpinner");
            const btnToggleAll = document.getElementById("btnToggleAll");
            const errorMsg = document.getElementById("errorMsg");
            const errorText = document.getElementById("errorText");
            const checkboxes = document.querySelectorAll(".invoice-checkbox");

            if (!invoiceList || !btnPay) return; // pas de factures

            const nf = new Intl.NumberFormat("fr-FR", {
                maximumFractionDigits: 0
            });
            const formatMontant = (n) => nf.format(n) + " " + CONTRAT_INFO.devise;

            let isSubmitting = false;

            function showError(message) {
                errorText.textContent = message;
                errorMsg.classList.add("visible");
            }

            function hideError() {
                errorMsg.classList.remove("visible");
                errorText.textContent = "";
            }

            // -------- Total + état du bouton --------
            function updateTotal() {
                let total = 0;
                let count = 0;

                checkboxes.forEach((cb) => {
                    if (cb.checked) {
                        total += Number(cb.dataset.amount || 0);
                        count++;
                    }
                });

                totalAmount.textContent = formatMontant(total);
                totalLabel.textContent =
                    count === 0 ?
                    "Aucune facture sélectionnée" :
                    count + " facture" + (count > 1 ? "s" : "") + " sélectionnée" + (count > 1 ? "s" : "");
                countHint.textContent =
                    count === 0 ?
                    checkboxes.length + " facture(s) impayée(s)" :
                    count + " sur " + checkboxes.length + " sélectionnée(s)";

                if (!isSubmitting) {
                    btnPay.disabled = count === 0;
                    btnPayText.textContent = count === 0 ? "Payer avec Wave" : "Payer " + formatMontant(total);
                }

                const allChecked = checkboxes.length > 0 && Array.from(checkboxes).every((cb) => cb.checked);
                if (btnToggleAll) btnToggleAll.textContent = allChecked ? "Tout décocher" : "Tout cocher";
            }

            function setLoading(loading) {
                isSubmitting = loading;
                btnPay.classList.toggle("loading", loading);
                btnSpinner.hidden = !loading;
                checkboxes.forEach((cb) => (cb.disabled = loading));
                if (btnToggleAll) btnToggleAll.disabled = loading;

                if (loading) {
                    btnPay.disabled = true;
                    btnPay.setAttribute("aria-busy", "true");
                    btnPayText.textContent = "Redirection vers Wave…";
                } else {
                    btnPay.removeAttribute("aria-busy");
                    updateTotal();
                }
            }

            // -------- Listeners --------
            checkboxes.forEach((cb) => {
                cb.addEventListener("change", () => {
                    hideError();
                    updateTotal();
                });
            });

            if (btnToggleAll) {
                btnToggleAll.addEventListener("click", () => {
                    const allChecked = Array.from(checkboxes).every((cb) => cb.checked);
                    checkboxes.forEach((cb) => {
                        cb.checked = !allChecked;
                    });
                    hideError();
                    updateTotal();
                });
            }

            // -------- Init paiement + redirection vers Wave --------
            btnPay.addEventListener("click", async () => {
                if (isSubmitting) return;

                const selected = Array.from(checkboxes)
                    .filter((cb) => cb.checked)
                    .map((cb) => cb.dataset.id);

                if (selected.length === 0) return;

                hideError();
                setLoading(true);

                const reference = "WAVE-" + Date.now() + "-" + Math.floor(Math.random() * 9999);

                // Total côté client (revérifié côté serveur)
                let amountClient = 0;
                checkboxes.forEach((cb) => {
                    if (cb.checked) amountClient += Number(cb.dataset.amount || 0);
                });

                const payload = {
                    reference,
                    currency: CONTRAT_INFO.devise,
                    paymentType: "recoveryPrime",
                    amount: amountClient,
                    contractId: CONTRAT_ID,
                    selectedInvoiceIds: selected,
                    description: `Paiement ${selected.length} facture(s) - Contrat ${CONTRAT_ID}`,
                    customerName: CONTRAT_INFO.souscripteur || null,
                    customerEmail: null,
                    metadata: null,
                };

                try {
                    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

                    const res = await fetch("/api/wave/paiements/init", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "Accept": "application/json",
                            "X-CSRF-TOKEN": csrf || "",
                        },
                        credentials: "same-origin",
                        body: JSON.stringify(payload),
                    });

                    let data;
                    try {
                        data = await res.json();
                    } catch {
                        data = {};
                    }

                    if (!res.ok || !data.success) {
                        throw new Error(data.message || `Erreur serveur (${res.status})`);
                    }

                    const redirectUrl =
                        data.data?.redirectUrl ||
                        data.data?.wave_launch_url ||
                        data.redirect_url;

                    if (!redirectUrl) {
                        throw new Error("Aucune URL Wave reçue.");
                    }

                    window.location.href = redirectUrl;

                } catch (err) {
                    setLoading(false);
                    showError(
                        (err && err.message ? err.message : "Le paiement n'a pas pu démarrer.") +
                        " Vérifiez votre connexion puis réessayez."
                    );
                }
            });

            // Retour arrière depuis Wave (cache du navigateur)
            window.addEventListener("pageshow", (e) => {
                if (e.persisted) setLoading(false);
            });

            updateTotal();
        })();
    </script>
</body>

</html>
