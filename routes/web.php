<?php

use App\Http\Controllers\API\AnimalController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\PetsitterAvailabilityController;
use App\Http\Controllers\PetsitterProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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

Route::get('/', action: function () {
    if (auth()->check()) {
        return redirect()->route('petsitters.search');
    }

    return Inertia::render('Homepage');
})->name('home');
Route::inertia('/login', 'auth/Login')->name('login');
Route::inertia('/register', 'auth/Register')->name('register');
Route::inertia('/petsitters', 'Petsitter/SearchPetsitter')->middleware('auth')->name('petsitters.search');
Route::inertia('/find-petsitter', 'Petsitter/PublicSearchPetsitter')->name('petsitters.public');
Route::get('/petsitters/profile', [PetsitterProfileController::class, 'show'])
    ->middleware('auth')
    ->name('petsitters.profile');
require __DIR__ . '/settings.php';
