// assets/js/demo-jeko-widget.js

(function () {
    "use strict";

    // ============================================================
    // 0) BASE URL - Détection automatique du domaine
    // ============================================================
    // Cette variable s'adapte automatiquement au domaine d'hébergement
    // (localhost:8000, apimain.test, mondomaine.com, etc.)
    // Si la page est accessible via une route spécifique, on utilise window.location.origin
    var BASE_URL = window.location.origin;
    
    // URLs des endpoints API
    var API_URLS = {
        widgetJs: BASE_URL + '/api/paiements/jeko/jeko-payment-widget.js',
        init: BASE_URL + '/api/paiements/jeko/init',
        contractCheck: BASE_URL + '/api/paiements/jeko/contrat/verifier',
        checkStatus: BASE_URL + '/api/paiements/jeko/statut',
    };

    console.log('📍 Base URL détectée:', BASE_URL);

    // ============================================================
    // 1.5) FONCTION POUR VÉRIFIER LE STATUT D'UN PAIEMENT
    // ============================================================
    /**
     * Interroge le statut d'un paiement via l'API
     * @param {string} referenceInterne - Référence interne du paiement
     * @returns {Promise<Object|null>} Données du statut ou null en cas d'erreur
     */
    async function checkPaymentStatus(referenceInterne) {
        try {
            const response = await fetch(API_URLS.checkStatus + '/' + referenceInterne);
            const data = await response.json();

            if (data.success) {
                console.log('✅ Statut du paiement:', data.data.statut);
                return data.data;
            } else {
                console.error('❌ Erreur lors de la vérification:', data.message);
                return null;
            }
        } catch (error) {
            console.error('❌ Erreur réseau lors de la vérification:', error);
            return null;
        }
    }

    // Exposer la fonction globalement pour les tests dans la console
    window.checkPaymentStatus = checkPaymentStatus;

    // ============================================================
    // 1) GESTION DES ONGLETS
    // ============================================================
    var tabButtons = document.querySelectorAll(".tab-btn");
    var tabContents = document.querySelectorAll(".tab-content");

    tabButtons.forEach(function (btn) {
        btn.addEventListener("click", function () {
            tabButtons.forEach(function (b) {
                b.classList.remove("active");
            });
            tabContents.forEach(function (c) {
                c.classList.remove("active");
            });

            btn.classList.add("active");
            var tabId = btn.dataset.tab;
            var content = document.getElementById("tab-" + tabId);
            if (content) content.classList.add("active");
        });
    });

    // ============================================================
    // 2) COPIE DE CODE - Version améliorée
    // ============================================================
    window.copyCode = function (btn) {
        var pre = btn.parentElement.querySelector("pre");
        if (!pre) return;
        
        // Récupérer le code et remplacer les variables dynamiques
        var code = pre.textContent;
        
        // Remplacer les URLs dynamiques dans le code copié
        code = code.replace(/\{\{ BASE_URL \}\}/g, BASE_URL);
        code = code.replace(/\{\{ WIDGET_JS_URL \}\}/g, API_URLS.widgetJs);
        code = code.replace(/\{\{ INIT_URL \}\}/g, API_URLS.init);
        code = code.replace(/\{\{ CONTRACT_CHECK_URL \}\}/g, API_URLS.contractCheck);

        navigator.clipboard.writeText(code).then(function () {
            btn.textContent = "Copié ✓";
            setTimeout(function () {
                btn.textContent = "Copier";
            }, 2000);
        }).catch(function () {
            // Fallback pour les navigateurs sans Clipboard API
            var textArea = document.createElement("textarea");
            textArea.value = code;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand("copy");
            document.body.removeChild(textArea);
            btn.textContent = "Copié ✓";
            setTimeout(function () {
                btn.textContent = "Copier";
            }, 2000);
        });
    };

    // ============================================================
    // 3) VÉRIFICATION DU WIDGET
    // ============================================================
    if (typeof JekoWidget === "undefined") {
        console.error("❌ JekoWidget non chargé.");
        document.querySelectorAll(".btn-pay-sm, .btn-pay").forEach(function (btn) {
            btn.disabled = true;
            btn.textContent = "⚠️ Indisponible";
        });
        return;
    }

    console.log("✅ JekoWidget chargé avec succès");

    // ============================================================
    // 4) INITIALISATION DU WIDGET
    // ============================================================
    var widget = new JekoWidget({
        // --- Configuration des endpoints ---
        /** URL pour initialiser le paiement (obligatoire) */
        backendEndpoint: API_URLS.init,
        /** URL pour vérifier le contrat (obligatoire) */
        contractCheckEndpoint: API_URLS.contractCheck,
        /** URL pour vérifier le statut du paiement (polling automatique) */
        statusCheckEndpoint: API_URLS.checkStatus,

        // --- Configuration générale ---
        /** Devise par défaut : XOF, XAF, USD, EUR */
        currency: "XOF",
        /** Timeout de la requête en millisecondes */
        timeout: 30000,
        /** Vérification automatique du contrat si contractId fourni */
        autoVerifyContract: true,

        // --- Configuration du polling du statut ---
        /** Intervalle de polling en millisecondes (défaut: 3000) */
        statusPollInterval: 3000,
        /** Nombre maximum de tentatives de polling (défaut: 20) */
        statusPollMaxAttempts: 20,

        // --- Headers personnalisés ---
        headers: {
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content || "",
            "Accept": "application/json",
        },

        // --- Callbacks ---
        callbacks: {
            /** Appelé lorsque le paiement est initialisé avec succès */
            onSuccess: function (redirectUrl, data) {
                console.log("✅ Paiement initialisé");
                // Le widget ouvre automatiquement la page de paiement
                // et effectue le polling du statut
            },
            /** Appelé en cas d'erreur lors de l'initialisation */
            onError: function (message, data) {
                console.error("❌ Erreur de paiement", { message, data });
                alert("Erreur: " + message);
            },
            /** Appelé lorsque le widget est ouvert */
            onOpen: function (data) {
                console.log("🔄 Widget ouvert");
            },
            /** Appelé lorsque le widget est fermé */
            onClose: function () {
                console.log("❌ Widget fermé");
            },
        },

        // --- Personnalisation du thème ---
        theme: {
            primary: "#1D603D",          // Couleur principale
            primaryDark: "#0B482F",       // Couleur principale foncée
            accent: "#E09518",            // Couleur d'accentuation
            radius: "16px",               // Rayon des bordures
            fontFamily: "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
            maxWidth: "550px",            // Largeur maximale du widget
        },

        // --- Traductions personnalisées ---
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
            successMessage: "Vous allez être redirigé vers la page de paiement...",
            error: "Échec du paiement",
            retry: "Réessayer",
            networkError: "Erreur réseau",
            requiredField: "Ce champ est requis",
            contractIdLabel: "Identifiant du contrat",
            contractIdPlaceholder: "Ex: 345678",
            verify: "Vérifier le contrat",
            verifying: "Vérification en cours...",
            loadingContract: "Chargement des informations du contrat...",
            // Traductions pour le polling du statut
            polling: "Vérification du statut du paiement en cours...",
            pollingPending: "Paiement en attente...",
            pollingSuccess: "Paiement réussi !",
            pollingError: "Le paiement a échoué",
            pollingTimeout: "Délai d'attente dépassé",
            pollingRestart: "Vérifier à nouveau",
        },
    });

    console.log("✅ Widget Jeko initialisé avec succès");

    // ============================================================
    // 5) FONCTIONS UTILITAIRES
    // ============================================================

    /**
     * Génère une référence unique pour le paiement
     * Format: PAI-YYYYMMDDHHMMSS-TIMESTAMP-RANDOM-CODE
     * @returns {string} Référence unique
     */
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

    /**
     * Récupère les factures pré-sélectionnées par l'utilisateur
     * @returns {string[]} Tableau des IDs de factures
     */
    function getPreselectedInvoices() {
        var ids = [];
        document.querySelectorAll('input[name="invoice_ids"]:checked').forEach(function (el) {
            ids.push(el.value);
        });
        return ids;
    }

    /**
     * Met à jour le message d'état d'un scénario
     * @param {string} elementId - ID de l'élément de statut
     * @param {string} message - Message à afficher
     * @param {string} type - Type de message (info, success, error)
     */
    function updateStatus(elementId, message, type) {
        var el = document.getElementById(elementId);
        if (!el) return;
        
        el.textContent = message;
        el.className = "status-msg visible";
        
        if (type === "error") {
            el.style.background = "#fef2f2";
            el.style.color = "#991b1b";
        } else if (type === "success") {
            el.style.background = "#f0fdf4";
            el.style.color = "#166534";
        } else {
            el.style.background = "#f3f4f6";
            el.style.color = "#6b7280";
        }
    }

    const referenceInterne = generateReference();
    const successUrl = BASE_URL + "/paiement/recu/" + referenceInterne;
    const errorUrl = BASE_URL + "/paiement/error";
    // ============================================================
    // 6) BOUTONS DE PAIEMENT - LES 3 TYPES
    // ============================================================

    // ---- 6.1) PREMIER PAIEMENT (firstPayment) ----
    /**
     * Scénario: Premier paiement d'un contrat (souscription)
     * - Utilisé lors de la souscription d'un nouveau contrat
     * - Le contractId est optionnel (peut être récupéré automatiquement)
     * - La prime principale et les frais d'adhésion sont calculés côté serveur
     */
    document.getElementById("btnFirstPayment").addEventListener("click", function () {
        var contractId = document.getElementById("contractIdFirstPayment")?.value || "1093";
        var ref = generateReference(); // Générer une nouvelle référence pour chaque paiement

        console.log("🔹 Premier paiement initié");

        widget.open({
            // Référence unique du paiement
            reference: ref,

            // Type de paiement: firstPayment | earlyPayment | recoveryPrime
            paymentType: "firstPayment",

            // Identifiant du contrat (optionnel pour firstPayment)
            contractId: contractId,

            // Description du paiement (affichée dans le widget)
            description: "Souscription — première prime",

            // Email du client (pour les notifications)
            customerEmail: "client@example.com",

            // Nom du client
            customerName: "Jean Dupont",

            // URL de redirection en cas de succès
            successUrl: BASE_URL + "/paiement/recu/" + ref,

            // URL de redirection en cas d'erreur
            errorUrl: BASE_URL + "/paiement/error",

            // Métadonnées additionnelles (pour le suivi)
            metadata: {
                source: "web_demo",
                scenario: "firstPayment",
                timestamp: new Date().toISOString(),
            },
        });
    });

    // ---- 6.2) PAIEMENT ANTICIPÉ (earlyPayment) ----
    /**
     * Scénario: Paiement anticipé des primes
     * - L'utilisateur renseigne l'identifiant du contrat
     * - Le widget vérifie automatiquement le contrat
     * - Vérifie qu'il n'y a pas d'impayés avant de permettre le paiement anticipé
     */
    document.getElementById("btnEarlyPayment").addEventListener("click", function () {
        var contractId = document.getElementById("contractIdEarly").value.trim();
        var ref = generateReference(); // Générer une nouvelle référence pour chaque paiement

        if (!contractId) {
            alert("⚠️ Veuillez saisir un identifiant de contrat.");
            return;
        }

        console.log("🔹 Paiement anticipé initié");

        updateStatus("earlyStatus", "⏳ Vérification automatique du contrat en cours...", "info");



        widget.open({
            reference: ref,
            paymentType: "earlyPayment",
            contractId: contractId,
            description: "Paiement anticipé de primes",
            customerEmail: "client@example.com",
            customerName: "Jean Dupont",
            successUrl: BASE_URL + "/paiement/recu/" + ref,
            errorUrl: BASE_URL + "/paiement/error",
            metadata: {
                source: "web_demo",
                scenario: "earlyPayment",
                contractId: contractId,
                timestamp: new Date().toISOString(),
            },
        });
    });

    // ---- 6.3) RÉGULARISATION (recoveryPrime) ----
    /**
     * Scénario: Régularisation de primes impayées
     * - L'utilisateur renseigne l'identifiant du contrat
     * - Le widget liste les factures en attente
     * - L'utilisateur sélectionne les factures à régulariser
     * - Possibilité de pré-sélectionner des factures via preselectedInvoiceIds
     */
    document.getElementById("btnRecoveryPrime").addEventListener("click", function () {
        var contractId = document.getElementById("contractIdRecovery").value.trim();
        var preselectedIds = getPreselectedInvoices();
        var ref = generateReference(); // Générer une nouvelle référence pour chaque paiement

        if (!contractId) {
            alert("⚠️ Veuillez saisir un identifiant de contrat.");
            return;
        }

        console.log("🔹 Régularisation initiée");

        updateStatus(
            "recoveryStatus",
            "⏳ Vérification automatique du contrat avec " + preselectedIds.length + " facture(s) pré-sélectionnée(s)...",
            "info"
        );

        widget.open({
            reference: ref,
            paymentType: "recoveryPrime",
            contractId: contractId,
            preselectedInvoiceIds: preselectedIds.length > 0 ? preselectedIds : undefined,
            description: "Régularisation de primes impayées",
            customerEmail: "client@example.com",
            customerName: "Jean Dupont",
            successUrl: BASE_URL + "/paiement/recu/" + ref,
            errorUrl: BASE_URL + "/paiement/error",
            metadata: {
                source: "web_demo",
                scenario: "recoveryPrime",
                contractId: contractId,
                preselectedCount: preselectedIds.length,
                timestamp: new Date().toISOString(),
            },
        });
    });

    // ============================================================
    // 7) GESTION DES ERREURS ET DES ÉTATS
    // ============================================================

    /**
     * Gestionnaire d'erreurs global pour le widget
     * Affiche les erreurs de manière conviviale
     */
    window.addEventListener('error', function (e) {
        // Ignorer les erreurs de cookies
        if (e.message && e.message.includes('Cookie')) {
            return;
        }
        console.error('⚠️ Erreur globale:', e.message);
    });

    /**
     * Gestionnaire d'erreurs pour les promesses non capturées
     */
    window.addEventListener('unhandledrejection', function (e) {
        console.error('⚠️ Promesse non capturée:', e.reason);
    });

    console.log("📌 Types de paiement disponibles: firstPayment, earlyPayment, recoveryPrime");
    console.log("🔄 Polling automatique du statut activé");

})();