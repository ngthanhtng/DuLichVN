<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

use App\Http\Controllers\ChatbotController;
Route::post('/chat', [ChatbotController::class, 'chat']);

use App\Http\Controllers\TourController;

Route::get('/tours', [TourController::class, 'getTours']);
Route::get('/tours/{destination}', [TourController::class, 'getTourByDestination']);

// use App\Http\Controllers\WebhookController;

// Route::post('/taggoai-webhook', [WebhookController::class, 'handleWebhook']);

use App\Http\Controllers\TaggoAIController;

Route::post('/taggoai-webhook', [TaggoAIController::class, 'handleWebhook']);
