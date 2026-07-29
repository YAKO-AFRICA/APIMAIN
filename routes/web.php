<?php

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\JekoPaymentController;
use App\Http\Controllers\Api\ReceiptController;
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

Route::get('/',[JekoPaymentController::class, 'demoJekoWidget'])->name('demo-jeko-widget');
Route::get('/paiement/recu/{referenceInterne}', [ReceiptController::class, 'show'])->name('paiement.recu');
Route::get('/recu/{referenceInterne}/pdf', [ReceiptController::class, 'downloadPDF'])->name('receipt.download.pdf');

// Route::get('/', function () {
//     return view('welcome');
// });

