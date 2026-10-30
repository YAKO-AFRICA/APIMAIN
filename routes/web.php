<?php
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ReceiptController;
use App\Http\Controllers\Api\WaveController;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('storage/documents/{file}', function ($file) {
    $path = base_path(env('UPLOADS_PATH') . $file);

    if (!file_exists($path)) {
        abort(404);
    }

    $fileContents = file_get_contents($path);
    $mimeType = mime_content_type($path);

    return Response::make($fileContents, 200, ['Content-Type' => $mimeType]);
})->where('file', '.*');

// Route::get('/test-pdf-simple', function() {
//     try {
//         $pdf = Barryvdh\DomPDF\Facade\Pdf::loadHTML('<h1 style="color: green;">Test PDF</h1><p>Ceci est un test simple.</p>');
//         return $pdf->download('test.pdf');
//     } catch (\Exception $e) {
//         return 'Erreur: ' . $e->getMessage();
//     }
// });

Route::get('/', [PaymentController::class, 'demoJekoWidget'])->name('demo-jeko-widget');
Route::get('/paiement/recu/{referenceInterne}', [ReceiptController::class, 'show'])->name('paiement.recu');
Route::get('/recu/{referenceInterne}/pdf', [ReceiptController::class, 'downloadPDF'])->name('receipt.download.pdf');
Route::get('/paiement/regularisation-primes', function () {
    return view('paiement.regularisation-primes');
})->name('paiement.regularisation-primes');
Route::get('/paiement/error/{referenceInterne}', function () {
    return view('paiement.jeko-error', ['referenceInterne' => request()->route('referenceInterne')]);
})->name('paiement.error');


Route::get('/wave-demo', [WaveController::class, 'demoWaveWidget'])->name('demo-wave-widget');

Route::prefix('wave')->name('wave.')->group(function () {
    // Retours après paiement
    Route::get('/paiements/retour/success', [WaveController::class, 'retourSuccess'])->name('retour.success');

    Route::get('/paiements/retour/error', [WaveController::class, 'retourError'])->name('retour.error');

    Route::get('/factures/{identifiant}', [WaveController::class, 'facturesPage'])
        ->where('identifiant', '[A-Za-z0-9\-_]+')
        ->name('factures.page');

    // routes/web.php
    Route::get('/payer/{identifiant}', [WaveController::class, 'payerPage'])
        ->where('identifiant', '[A-Za-z0-9\-_]+')
        ->name('payer.page');
});

// Route::get('/', function () {
//     return view('welcome');
// });
