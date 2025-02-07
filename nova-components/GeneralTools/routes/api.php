<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudentAssessmentController;
use App\Http\Controllers\RedisJobsController;

/*
|--------------------------------------------------------------------------
| Tool API Routes
|--------------------------------------------------------------------------
|
| Here is where you may register API routes for your tool. These routes
| are loaded by the ServiceProvider of your tool. They are protected
| by your tool's "Authorize" middleware by default. Now, go build!
|
*/

// Route::get('/', function (Request $request) {
//     //
// });

Route::post('/update-assessments', [StudentAssessmentController::class, 'updateAssessments']);
Route::get('/redis-jobs', [RedisJobsController::class, 'index']);
