<?php

use App\Http\Controllers\API\AnimalController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\PetsitterAvailabilityController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/petsitters/{id}/availability', [PetsitterAvailabilityController::class, 'availability']);

    Route::middleware('auth:sanctum')->group(function () {
        extracted();

        // Animals
        Route::apiResource('animals', AnimalController::class);
    });
});

Route::inertia('/', 'Homepage')->name('home');
Route::inertia('/login', 'auth/Login')->name('login');
Route::inertia('/register', 'auth/Register')->name('register');
Route::inertia('/petsitters', 'Petsitter/SearchPetsitter')->middleware('auth')->name('petsitters.search');
Route::inertia('/find-petsitter', 'Petsitter/PublicSearchPetsitter')->name('petsitters.public');
require __DIR__.'/settings.php';
