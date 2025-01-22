<?php

use App\Http\Controllers\PersonalityTestController;
use App\Http\Controllers\PrinciplesInfoController;
use App\Http\Controllers\DinosaurController;
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

Route::get('/tools/personality-test/{studentUid}/{showQuestions?}', [PersonalityTestController::class, 'index'])
    ->name('personality-test.index');

Route::post('/tools/personality-test/{studentUid}', [PersonalityTestController::class, 'store'])
    ->name('personality-test.store');

Route::get('/tools/personality-test/{studentUid}/pdf/download', [PersonalityTestController::class, 'showPdf'])
    ->name('personality-test.show-pdf');

Route::get('/test', function() {
    return 'Hello World';
});

Route::get('/principles-info', [PrinciplesInfoController::class, 'showInfo']);

Route::get('/dinosaur', [DinosaurController::class, 'show']);
