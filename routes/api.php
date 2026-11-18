<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CarteApiController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Routes REST officielles CIMIS pour SIADOC et Partenaires
Route::get('/cartes/statistiques', [CarteApiController::class, 'statistiques']);
Route::get('/cartes', [CarteApiController::class, 'index']);
Route::get('/cartes/{matricule}', [CarteApiController::class, 'show'])->where('matricule', '.*');
Route::get('/biometrie/{matricule}', [CarteApiController::class, 'show'])->where('matricule', '.*');
