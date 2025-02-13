<?php

use App\Http\Controllers\PersonalityTestController;
use App\Http\Controllers\PrinciplesInfoController;
use App\Http\Controllers\DinosaurController;
use App\Http\Controllers\FileDownloadController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CareerReportController;
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

Route::get('download/pdf/{filename}', [FileDownloadController::class, 'downloadPdf'])
    ->where('filename', '.*') // Allow slashes in filename for nested paths
    ->name('download.pdf');

Route::get('download/stats/{filename}', [FileDownloadController::class, 'getStats'])
    ->where('filename', '.*')
    ->name('download.stats');


Route::get('/career-reports/{careerReport}', [CareerReportController::class, 'displayCareerReport']);
