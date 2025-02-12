<?php

use App\Http\Controllers\CareerReportController;
use App\Http\Controllers\JcrTemplateController;
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

Route::post('/save-career-report-template', [CareerReportController::class, 'storeJson']);
Route::get('/get-career-report-template', [CareerReportController::class, 'getJson']);
Route::get('/career-report/{accountId}/{careerTitle}/generate', [CareerReportController::class, 'generateCareerReport']);
Route::get('/career-report/{accountId}/{careerTitle}/download', [CareerReportController::class, 'downloadCareerReport']);
Route::get('/career-report/{accountId}/{careerTitle}/prompts', [CareerReportController::class, 'downloadPrompts']);

Route::get('/jcr-templates', [JcrTemplateController::class, 'index']);
Route::get('/jcr-templates/{template}', [JcrTemplateController::class, 'show']);
Route::post('/jcr-templates', [JcrTemplateController::class, 'createTemplate']);
Route::post('/jcr-templates/{template}', [JcrTemplateController::class, 'updateTemplate']);
Route::delete('/jcr-templates/{template}', [JcrTemplateController::class, 'deleteTemplate']);