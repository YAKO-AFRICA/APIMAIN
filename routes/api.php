<?php

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\CaisseController;
use App\Http\Controllers\Api\CaisseEtatController;
use App\Http\Controllers\Api\CaisseMouvementController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\JekoPaymentController;
use App\Http\Controllers\Api\OperateurController;
use App\Http\Controllers\Api\PaiementController;
use App\Http\Controllers\Api\RapportCaisseController;
use App\Http\Controllers\Api\RapportController;
use App\Http\Controllers\Api\SmsController;
use App\Http\Controllers\Api\SouscriptionController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\TypeOperationController;
use App\Http\Controllers\Api\YvonController;
use App\Http\Controllers\Api\YvonWidgetController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Bni\BniController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\Pret\SimulateurPrimeController;
use App\Http\Controllers\Suggestion\CategoryController;
use App\Http\Controllers\Suggestion\ESuggestionController;
use App\Http\Controllers\Suggestion\QrCodeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/log/request', [LogController::class, 'getRequestLogs'])->name('logRequest');



// Route pour avoir la liste de tout les utilisateur Assurfin
Route::post('/get/user/by/partner', [ApiController::class,'getUserByPartner' ])->name('getUserByPartner');
Route::get('/get/user/assurfin', [ApiController::class,'getAllUserAssurFin' ])->name('getAllUserAssurFin');
Route::post('/check/user', [ApiController::class,'userCheck' ])->name('userCheck');

// Route pour les types d'opérations et les opérations
Route::get('/get/type/operations', [TypeOperationController::class,'index' ]);
Route::post('/store/type/operations', [TypeOperationController::class,'store' ]);
Route::post('/update/type/operations/{uuid}', [TypeOperationController::class,'update' ]);
Route::post('/destroy/type/operations/{uuid}', [TypeOperationController::class,'destroy' ]);
 Route::post('/activate/type/operations/{uuid}', [TypeOperationController::class, 'activate']);

 // routes/api.php
Route::prefix('rapports')->group(function () {
    Route::get('/', [RapportController::class, 'index']);
    Route::post('/store', [RapportController::class, 'store']);
    Route::get('/{uuid}', [RapportController::class, 'show']);
    Route::put('/{uuid}', [RapportController::class, 'update']);
    Route::delete('/{uuid}', [RapportController::class, 'destroy']);
    Route::post('/{uuid}/restore', [RapportController::class, 'restore']);
    Route::get('/periode/rapports', [RapportController::class, 'getByDateRange']);

    Route::post('/synthese/data', [RapportController::class, 'rapportSynthese']);
});


Route::prefix('dashboard')->group(function () {
    Route::get('/stats', [DashboardController::class, 'getStats']);
    Route::get('/transactions-by-period', [DashboardController::class, 'getTransactionsByPeriod']);
    Route::get('/top-operators', [DashboardController::class, 'getTopOperators']);
    Route::get('/transactions-by-type', [DashboardController::class, 'getTransactionsByType']);
});

Route::prefix('caisse')->group(function () {
    Route::get('/get', [CaisseController::class, 'index']);
    Route::post('/store', [CaisseController::class, 'store']);
    Route::post('/update/{uuid}', [CaisseController::class, 'update']);
    Route::post('/destroy/{uuid}', [CaisseController::class, 'destroy']);
    Route::post('/restore/{uuid}', [CaisseController::class, 'restore']);
});
Route::prefix('operateur')->group(function () {
    Route::get('/get', [OperateurController::class, 'index']);
    Route::post('/store', [OperateurController::class, 'store']);
    Route::post('/update/{uuid}', [OperateurController::class, 'update']);
    Route::post('/destroy/{uuid}', [OperateurController::class, 'destroy']);
    Route::post('/restore/{uuid}', [OperateurController::class, 'restore']);
});

Route::prefix('transactions')->group(function () {
    Route::get('/get', [TransactionController::class, 'index']);
    Route::get('/statistiques', [TransactionController::class, 'statistiques']);
    Route::post('/depot-mobile-money', [TransactionController::class, 'depotMobileMoney']);
    Route::post('/retrait-mobile-money', [TransactionController::class, 'retraitMobileMoney']);
    Route::post('/operation-mto', [TransactionController::class, 'operationMTO']);
    Route::post('/annuler/{uuid}', [TransactionController::class, 'annuler']);
    Route::get('/transactions-by-period', [DashboardController::class, 'getTransactionsByPeriod']);
});

Route::prefix('caisse-mouvements')->group(function () {
    Route::get('/get', [CaisseMouvementController::class, 'index']);
    Route::post('/approvisionnement', [CaisseMouvementController::class, 'approvisionnement']);
    Route::post('/rapatriement', [CaisseMouvementController::class, 'rapatriement']);
    Route::post('/valider-envoi/{uuid}', [CaisseMouvementController::class, 'validerEnvoi']);
    Route::post('/confirmer-reception/{uuid}', [CaisseMouvementController::class, 'confirmerReception']);
    Route::post('/annuler/{uuid}', [CaisseMouvementController::class, 'annuler']);

    Route::get('/stats', [CaisseMouvementController::class, 'getStats']);
    Route::get('/mouvements-by-period', [CaisseMouvementController::class, 'getMouvementsByPeriod']);
    Route::get('/top-caisses', [CaisseMouvementController::class, 'getTopCaisses']);
    Route::get('/mouvements-by-statut', [CaisseMouvementController::class, 'getMouvementsByStatut']);
});

Route::prefix('caisse-etat')->group(function () {
    Route::get('/check', [CaisseEtatController::class, 'checkOuverture']);
    Route::post('/ouvrir', [CaisseEtatController::class, 'ouvrir']);
    Route::post('/rapprochement', [CaisseEtatController::class, 'rapprochement']);
    Route::post('/fermer', [CaisseEtatController::class, 'fermer']);
    Route::post('/verrouiller/{uuid}', [CaisseEtatController::class, 'verrouiller']);
    Route::post('/deverrouiller/{uuid}', [CaisseEtatController::class, 'deverrouiller']);
    Route::get('/historique', [CaisseEtatController::class, 'historique']);
});


Route::prefix('caisse-rapports')->group(function () {
    Route::get('/journalier', [RapportCaisseController::class, 'rapportJournalier']);
    Route::post('/export-pdf', [RapportCaisseController::class, 'exportPdf']);
    Route::post('/export-excel', [RapportCaisseController::class, 'exportExcel']);
});


// api calcule de prime cotation pret
Route::post('/simulateur/prime/pret', [SimulateurPrimeController::class, 'simulatePrime']);

Route::post('/adherent-bni', [BniController::class, 'getAdherent']);

// endpoint envoie sms
Route::post('/send-sms', [SmsController::class, 'sendSms']);

// endpoint module de souscription
Route::prefix('souscription')->group(function () {
    Route::get('/calculHomeData/{idmembre}', [SouscriptionController::class, 'calculHomeData']);
});

// route api general || paramettre de souscription
Route::prefix('param')->group(function () {
    Route::post('/getProduitsByReseau', [ApiController::class, 'getProduitsByReseau']);
});


Route::prefix('paiement')->group(function () {
    Route::post('/save-paiement-om-callback', [PaiementController::class, 'savePaiementOM']);
});


Route::prefix('/paiements/jeko')->group(function () {
    // Initialisation du paiement
    Route::post('/init', [JekoPaymentController::class, 'initierPaiement'])
        ->name('api.paiements.jeko.init');
    
    // Vérification du statut
    Route::get('/statut/{referenceInterne}', [JekoPaymentController::class, 'verifierStatut'])
        ->name('api.paiements.jeko.statut');
    
    // Vérification du contrat
    Route::post('/contrat/verifier', [JekoPaymentController::class, 'verifierContrat']);
    
    // Widget
    Route::get('/jeko-payment-widget.js', [JekoPaymentController::class, 'jekoPaymentWidget']);
    
    // Webhook pour les notifications
    Route::post('/webhook', [JekoPaymentController::class, 'webhook'])
        ->name('api.paiements.jeko.webhook');
});




// ============================================================
//  routes/api.php — Routes YVON complètes
//  Serveur EX2 : apimain.yakoafricassur.com
//
//  DEUX MODES D'ACCÈS :
//  1. /api/yvon/*        → Protégé Sanctum (espace client connecté)
//  2. /widget/yvon/*     → Public avec rate-limit (widget externe)
//  3. /static/*          → Fichiers widget.js et yvon.png
// ============================================================


// ── 1. Routes protégées Sanctum (utilisateurs connectés) ─────
Route::prefix('yvon')->middleware('auth:sanctum')->group(function () {
    Route::post('/chat',           [YvonController::class, 'chat']);
    Route::post('/voice',          [YvonController::class, 'voiceChat']);
    Route::delete('/session/{id}', [YvonController::class, 'deleteSession']);
});
 
// ── 2. Routes widget public (rate-limit 60 req/min/IP) ───────
//    Utilisées par widget.js intégré sur sites externes
//    et par les applications mobiles
Route::prefix('widget/yvon')->middleware('throttle:60,1')->group(function () {
    Route::post('/auth',           [YvonWidgetController::class, 'getPublicToken']);
    Route::post('/chat',           [YvonWidgetController::class, 'chat']);
    Route::post('/voice',          [YvonWidgetController::class, 'voiceChat']);
    Route::delete('/session/{id}', [YvonWidgetController::class, 'deleteSession']);
});
 
// ── 3. Routes publiques sans auth ────────────────────────────
Route::get('/yvon/health',    [YvonController::class, 'health']);
Route::get('/yvon/languages', [YvonController::class, 'languages']);
 
// ── 4. Servir le widget.js et l'icône depuis Laravel ─────────
//    Permet : <script src="https://apimain.yakoafricassur.com/static/widget.js">
Route::get('/static/widget.js', [YvonWidgetController::class, 'serveWidget']);
Route::get('/static/yvon.png',  [YvonWidgetController::class, 'serveIcon']);


// e-suggestion routes
Route::prefix('suggestion')->group(function () {
    Route::post('/generate-qr-code', [QrCodeController::class, 'store']); // methode post avec le code agence "agency_code" dans le body de la requete
    Route::get('/get/qr-codes', [QrCodeController::class, 'index']); // methode get param de filtre
    Route::put('/qr-codes/{uuid}', [QrCodeController::class, 'changeEtat']); // methode put pour changer l'etat du qr code
    Route::post('/qr-codes/{uuid}/scan', [QrCodeController::class, 'countScan']); // methode post pour incrémenter le compteur de scans
    Route::delete('/qr-codes/{uuid}', [QrCodeController::class, 'destroy']); // methode delete pour supprimer le qr code

    // routes pour la gestion des catégories
    Route::post('/cat/create', [CategoryController::class, 'store']); // methode post pour créer une catégorie
    Route::get('/cat/get', [CategoryController::class, 'index']); // methode get pour obtenir les catégories
    Route::put('/cat/update/{uuid}', [CategoryController::class, 'update']); // methode put pour mettre à jour une catégorie
    Route::put('/cat/change/etat/{uuid}', [CategoryController::class, 'changeEtat']); // methode put pour changer l'etat de la catégorie
    Route::delete('/cat/destroy/{uuid}', [CategoryController::class, 'destroy']); // methode delete pour supprimer la catégorie

    // creation des suggestions
    Route::post('/create', [ESuggestionController::class, 'store']); // methode post pour créer une suggestion
    Route::get('/get', [ESuggestionController::class, 'index']); // methode get pour obtenir les suggestions
    Route::get('/show/{uuid}', [ESuggestionController::class, 'show']); // methode get pour obtenir les détails d'une suggestion
    Route::put('/change/etat/{uuid}', [ESuggestionController::class, 'changeEtat']); // methode put pour changer l'etat de la suggestion
    Route::delete('/destroy/{uuid}', [ESuggestionController::class, 'destroy']); // methode delete pour supprimer la suggestion

    // gestion des treatment de suggestion
    Route::post('/treatment/create', [ESuggestionController::class, 'treatmentSuggestion']); // methode post pour créer un traitement de suggestion
    Route::get('/treatment/get-by-param', [ESuggestionController::class, 'getTreatmentsByParam']); // methode get pour obtenir les traitements en fonction des param
});































// Auth Controller routes can be added here in the future

// Route pour obtenir le cookie CSRF
Route::get('/sanctum/csrf-cookie', [AuthController::class, 'getCsrfCookie']);

// Route de Connexion avec Throttling: 3 tentatives max en 2 minutes
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:3,2'); // 3 tentatives en 2 minutes

// Route de Déconnexion
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Route de Réinitialisation de Mot de Passe
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
