<?php

use App\Http\Controllers\API\AnimalController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\PetsitterAvailabilityController;
use App\Http\Controllers\API\PetsitterController;
use App\Http\Controllers\API\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/petsitters/{id}/calendar', [PetsitterAvailabilityController::class, 'calendar']);

    Route::middleware('auth:sactum')->group(function () {
        Route::get('/me', [AuthController::class, 'myInformation']);
        Route::get('/myInformation', [AuthController::class, 'myInformation']);
        Route::post('/logout', [AuthController::class, 'logout']);

        // Users
        Route::post('/users/{user}/suspend', [UserController::class, 'suspend']);
        Route::apiResource('users', UserController::class);

        // Petsitters
        Route::post('/petsitters/calendar/toggle', [PetsitterAvailabilityController::class, 'toggle']);
        Route::post('/petsitters/{petsitter}/approve', [PetsitterController::class, 'approve']);
        Route::post('/petsitters/{petsitter}/reject', [PetsitterController::class, 'reject']);
        Route::apiResource('petsitters', PetsitterController::class);

        // Animals
        Route::apiResource('animals', AnimalController::class);
    });
});
