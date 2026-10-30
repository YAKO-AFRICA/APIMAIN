<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Régularisation de primes</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1D603D 0%, #0B482F 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            padding: 30px 20px;
            max-width: 600px;
            width: 100%;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        .invoices-container {
            max-height: 400px;
            overflow-y: auto;
            padding-right: 5px;
        }

        .logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        h1 {
            color: #1D603D;
            font-size: 24px;
            margin-bottom: 10px;
        }

        p {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .info-box {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: left;
        }

        .info-label {
            font-size: 12px;
            color: #999;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }

        .contract-info {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: left;
            font-size: 15px;
            color: #333;
        }

        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            text-align: left;
        }

        .select-all-btn {
            background: #f8f9fa;
            border: 2px solid #1D603D;
            color: #1D603D;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .select-all-btn:hover {
            background: #1D603D;
            color: white;
        }

        .btn-pay {
            background: #1D603D;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
        }

        .btn-pay:hover {
            background: #0B482F;
            transform: translateY(-2px);
        }

        .btn-pay:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .loading {
            display: none;
            margin-top: 20px;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #1D603D;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .error {
            background: #fee;
            color: #c33;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
        }

        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #999;
        }

        .invoice-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 15px;
            margin-bottom: 10px;
            background: #e8f5e9;
            border: 2px solid #1D603D;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .invoice-item:not(:has(input:checked)) {
            background: white;
            border: 2px solid #e0e0e0;
        }

        .invoice-item-left {
            display: flex;
            align-items: center;
            flex: 1;
        }

        .invoice-item input[type="checkbox"] {
            width: 24px;
            height: 24px;
            margin-right: 12px;
            cursor: pointer;
            accent-color: #1D603D;
        }

        .invoice-info {
            flex: 1;
            text-align: left;
        }

        .invoice-ref {
            font-size: 14px;
            color: #333;
            margin-bottom: 2px;
        }

        .invoice-details {
            font-size: 13px;
            color: #666;
        }

        .invoice-amount {
            font-size: 15px;
            font-weight: 600;
            color: #1D603D;
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="{{ asset('assets/images/ynov-logo.jpg') }}" alt="Logo Ynov" />
        </div>
        <h1>Régularisation de primes</h1>
        <p>Veuillez régulariser vos primes impayées pour poursuivre votre couverture.</p>

        <div class="contract-info" id="contractInfo" style="display: none;">
            <div id="contractDisplay">Chargement...</div>
        </div>

        <div class="loading" id="loadingContract" style="display: block;">
            <div class="spinner"></div>
            <p style="margin-top: 10px;">Chargement des informations du contrat...</p>
        </div>

        <div class="section-title" id="sectionTitle" style="display: none;">Sélectionnez les primes à régulariser</div>
        <button class="select-all-btn" id="selectAllBtn" onclick="toggleSelectAll()" style="display: none;">Tout cocher</button>

        <div class="invoices-container" id="invoicesContainer" style="display: none;">
            <div id="invoicesList"></div>
        </div>

        <div class="info-box" id="amountBox" style="display: none;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div style="font-weight: 600; color: #333;">Total à régler</div>
                <div class="info-value" id="amountDisplay">0 FCFA</div>
            </div>
        </div>

        <div class="error" id="errorBox"></div>

        <button class="btn-pay" id="btnPay" onclick="initiatePayment()" style="display: none;">
            Payer maintenant
        </button>

        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p style="margin-top: 10px;">Chargement...</p>
        </div>

        <div class="footer">
            Paiement sécurisé via notre partenaire de paiement. Vos informations sont protégées.
        </div>
    </div>

    <script>
        // Récupérer les paramètres de l'URL
        const urlParams = new URLSearchParams(window.location.search);
        const contractId = urlParams.get('contract');

        const BASE_URL = window.location.origin;
        const API_URLS = {
            widgetJs: BASE_URL + '/api/paiements/jeko-payment-widget.js',
            init: BASE_URL + '/api/paiements/init',
            contractCheck: BASE_URL + '/api/paiements/contrat/verifier',
        };

        // Stocker les factures et les montants
        let availableInvoices = [];
        let selectedInvoiceIds = [];

        // Afficher l'ID du contrat
        document.getElementById('contractDisplay').textContent = contractId || 'Non spécifié';

        // Charger les factures du contrat
        async function loadContractInvoices() {
            if (!contractId) {
                showError('ID de contrat manquant');
                document.getElementById('loadingContract').style.display = 'none';
                return false;
            }

            try {
                const response = await fetch(API_URLS.contractCheck, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        idContrat: contractId,
                        paymentType: 'recoveryPrime',
                    }),
                });

                const data = await response.json();

                // Cacher le spinner de chargement
                document.getElementById('loadingContract').style.display = 'none';

                if (data.success && data.data && data.data.facturesImpayees) {
                    // Afficher les informations du contrat
                    const contractInfo = document.getElementById('contractInfo');
                    const contractDisplay = document.getElementById('contractDisplay');
                    contractDisplay.innerHTML = `Contrat n° <strong>${contractId}</strong> — ${data.data.produit || ''} — ${data.data.souscripteur || ''}`;
                    contractInfo.style.display = 'block';

                    availableInvoices = data.data.facturesImpayees.map(invoice => ({
                        id: invoice.IdPresentation,
                        numero: invoice.CodePresentation,
                        montant: invoice.MontantNet,
                        date: invoice.MaDate,
                        type: invoice.TypePresentation,
                    }));
                    displayInvoices(availableInvoices);
                    return true; // Succès
                } else {
                    showError(data.message || 'Impossible de charger les factures du contrat');
                    return false; // Échec
                }
            } catch (error) {
                console.error('Erreur:', error);
                document.getElementById('loadingContract').style.display = 'none';
                showError('Erreur lors du chargement des factures');
                return false; // Échec
            }
        }

        // Afficher les factures en checkboxes
        function displayInvoices(invoices) {
            const invoicesList = document.getElementById('invoicesList');
            const contractInfo = document.getElementById('contractInfo');
            const selectAllBtn = document.getElementById('selectAllBtn');
            const btnPay = document.getElementById('btnPay');
            const sectionTitle = document.getElementById('sectionTitle');
            const invoicesContainer = document.getElementById('invoicesContainer');

            // Afficher la section titre et le conteneur des factures
            sectionTitle.style.display = 'block';
            invoicesContainer.style.display = 'block';

            if (!invoices || invoices.length === 0) {
                invoicesList.innerHTML = '<p style="font-size: 14px; color: #999; text-align: center; padding: 20px;">Aucune facture impayée trouvée</p>';
                selectAllBtn.style.display = 'none';
                btnPay.style.display = 'none';
                return;
            }

            invoicesList.innerHTML = '';

            invoices.forEach(invoice => {
                const invoiceItem = document.createElement('div');
                invoiceItem.className = 'invoice-item';

                const leftPart = document.createElement('div');
                leftPart.className = 'invoice-item-left';

                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.id = invoice.id;
                checkbox.value = invoice.id;
                checkbox.checked = true; // Cocher par défaut
                checkbox.addEventListener('change', updateSelectedInvoices);

                const invoiceInfo = document.createElement('div');
                invoiceInfo.className = 'invoice-info';

                const invoiceRef = document.createElement('div');
                invoiceRef.className = 'invoice-ref';
                invoiceRef.textContent = `${invoice.date} — réf ${invoice.id} — ${invoice.numero}`;

                leftPart.appendChild(checkbox);
                leftPart.appendChild(invoiceInfo);
                invoiceInfo.appendChild(invoiceRef);

                const invoiceAmount = document.createElement('div');
                invoiceAmount.className = 'invoice-amount';
                invoiceAmount.textContent = formatAmount(invoice.montant);

                invoiceItem.appendChild(leftPart);
                invoiceItem.appendChild(invoiceAmount);

                // Clic sur tout le bloc pour cocher/décocher
                invoiceItem.addEventListener('click', (e) => {
                    if (e.target !== checkbox) {
                        checkbox.checked = !checkbox.checked;
                        updateSelectedInvoices();
                    }
                });

                invoicesList.appendChild(invoiceItem);
            });

            // Afficher les boutons quand il y a des factures
            selectAllBtn.style.display = 'inline-block';
            btnPay.style.display = 'block';

            // Mettre à jour les factures sélectionnées initialement
            updateSelectedInvoices();
        }

        // Mettre à jour les factures sélectionnées et le montant total
        function updateSelectedInvoices() {
            const checkboxes = document.querySelectorAll('#invoicesList input[type="checkbox"]:checked');
            selectedInvoiceIds = Array.from(checkboxes).map(cb => cb.value);

            const amountBox = document.getElementById('amountBox');
            const amountDisplay = document.getElementById('amountDisplay');

            if (selectedInvoiceIds.length > 0) {
                const totalAmount = availableInvoices
                    .filter(inv => selectedInvoiceIds.includes(inv.id))
                    .reduce((sum, inv) => sum + (parseFloat(inv.montant) || 0), 0);

                amountDisplay.textContent = formatAmount(totalAmount);
                amountBox.style.display = 'block';
            } else {
                amountBox.style.display = 'none';
            }
        }

        // Formater le montant
        function formatAmount(amount) {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'XOF',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            }).format(amount);
        }

        // Générer une référence unique
        function generateReference() {
            var now = new Date();
            var timestamp = now.getTime();
            var random = Math.random().toString(36).substring(2, 10);
            var code = String(Math.floor(Math.random() * 9999) + 1).padStart(4, "0");
            var year = now.getFullYear();
            var month = String(now.getMonth() + 1).padStart(2, "0");
            var day = String(now.getDate()).padStart(2, "0");
            var hours = String(now.getHours()).padStart(2, "0");
            var minutes = String(now.getMinutes()).padStart(2, "0");
            var seconds = String(now.getSeconds()).padStart(2, "0");
            return "PAI-" + year + month + day + hours + minutes + seconds + "-" + timestamp + "-" + random + "-" + code;
        }

        // Charger le widget
        let widget = null;

        function loadWidget() {
            return new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = API_URLS.widgetJs;
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            });
        }

        // Initialiser le paiement
        async function initiatePayment() {
            if (!contractId) {
                showError('ID de contrat manquant');
                return;
            }

            // if (selectedInvoiceIds.length === 0) {
            //     showError('Veuillez sélectionner au moins une facture');
            //     return;
            // }

            document.getElementById('btnPay').disabled = true;
            document.getElementById('loading').style.display = 'block';

            try {
                if (!widget) {
                    await loadWidget();
                    
                    widget = new JekoWidget({
                        backendEndpoint: API_URLS.init,
                        contractCheckEndpoint: API_URLS.contractCheck,
                        statusCheckEndpoint: BASE_URL + '/api/paiements/statut',

                        currency: "XOF",
                        timeout: 30000,
                        autoVerifyContract: true,

                        // statusPollInterval: 3000,
                        // statusPollMaxAttempts: 20,

                        headers: {
                            "Accept": "application/json",
                        },

                        callbacks: {
                            onSuccess: function(redirectUrl, data) {
                                console.log("Paiement initialisé");
                            },
                            onError: function(message, data) {
                                console.error("Erreur de paiement:", message);
                                showError(message || "Erreur lors du paiement");
                                document.getElementById('btnPay').disabled = false;
                                document.getElementById('loading').style.display = 'none';
                            },
                            onOpen: function(data) {
                                console.log("Widget ouvert");
                            },
                            onClose: function() {
                                console.log("Widget fermé");
                                document.getElementById('btnPay').disabled = false;
                                document.getElementById('loading').style.display = 'none';
                            },
                        },
                    });
                }

                const referenceInterne = generateReference();
                const successUrl = "{{ url('/paiement/recu') }}" + "/" + referenceInterne;
                // console.log('successUrl :', successUrl)

                widget.open({
                    reference: referenceInterne,
                    paymentType: "recoveryPrime",
                    contractId: contractId,
                    preselectedInvoiceIds: selectedInvoiceIds.length > 0 ? selectedInvoiceIds : undefined,
                    description: "Régularisation de primes impayées",
                    customerEmail: "",
                    customerName: "Client",
                    successUrl: successUrl,
                    // successUrl: window.location.origin + "/paiements/jeko/success",
                    errorUrl: "{{ url('/paiement/error') }}",
                    metadata: {
                        source: "sms_link",
                        scenario: "recoveryPrime",
                        contractId: contractId,
                    },
                });

            } catch (error) {
                console.error("Erreur:", error);
                showError("Erreur lors du chargement du paiement");
                document.getElementById('btnPay').disabled = false;
                document.getElementById('loading').style.display = 'none';
            }
        }

        function showError(message) {
            const errorBox = document.getElementById('errorBox');
            errorBox.textContent = message;
            errorBox.style.display = 'block';
        }

        // Fonction pour tout cocher / tout décocher
        function toggleSelectAll() {
            const checkboxes = document.querySelectorAll('#invoicesList input[type="checkbox"]');
            const selectAllBtn = document.getElementById('selectAllBtn');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);

            checkboxes.forEach(cb => {
                cb.checked = !allChecked;
            });

            selectAllBtn.textContent = allChecked ? 'Tout cocher' : 'Tout décocher';
            updateSelectedInvoices();
        }

        // Charger automatiquement les factures si le contrat est spécifié
        if (contractId) {
            loadContractInvoices().then((success) => {
                // Lancer automatiquement le widget après chargement des factures SEULEMENT si succès
                if (success) {
                    setTimeout(() => {
                        initiatePayment();
                    }, 500);
                }
            });
        }
    </script>
</body>
</html>
