<?php

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\JekoPaymentController;
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


Route::get('/',[JekoPaymentController::class, 'demoJekoWidget'])->name('demo-jeko-widget');

// Route::get('/', function () {
//     return view('welcome');
// });

