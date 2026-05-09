<?php

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\CaisseController;
use App\Http\Controllers\Api\OperateurController;
use App\Http\Controllers\Api\RapportController;
use App\Http\Controllers\Api\SmsController;
use App\Http\Controllers\Api\SouscriptionController;
use App\Http\Controllers\Api\TypeOperationController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Bni\BniController;
use App\Http\Controllers\Pret\SimulateurPrimeController;
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
Route::prefix('souscription')->group(function () {
    Route::get('/calculHomeData', [SouscriptionController::class, 'calculHomeData']);
});

// api calcule de prime cotation pret
Route::post('/simulateur/prime/pret', [SimulateurPrimeController::class, 'simulatePrime']);

Route::post('/adherent-bni', [BniController::class, 'getAdherent']);

// endpoint envoie sms
Route::post('/send-sms', [SmsController::class, 'sendSms']);































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
