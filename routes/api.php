<?php

// te
use App\Http\Controllers\API\AnimalController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\FavouriteController;
use App\Http\Controllers\API\PetsitterAvailabilityController;
use App\Http\Controllers\API\PetsitterController;
use App\Http\Controllers\API\PostController;
use App\Http\Controllers\API\SupportTicketsController;
use App\Http\Controllers\API\UserController;
use Illuminate\Support\Facades\Route;

/**
 * @return void
 */
if (! function_exists('extracted')) {
    function extracted(): void
    {
    Route::get('/me', [AuthController::class, 'myInformation']);
    Route::get('/myInformation', [AuthController::class, 'myInformation']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Users
    Route::post('/users/{user}/suspend', [UserController::class, 'suspend']);
    Route::apiResource('users', UserController::class);

    // Petsitters
    Route::post('/petsitters/availability/toggle', [PetsitterAvailabilityController::class, 'toggle']);
    Route::put('/petsitters/availability/days', [PetsitterController::class, 'updateAvailabilityDays']);
    Route::post('/petsitters/{petsitter}/availability', [PetsitterAvailabilityController::class, 'store']);
    Route::put('/petsitters/{petsitter}/availability/{date}', [PetsitterAvailabilityController::class, 'update']);
    Route::delete('/petsitters/{petsitter}/availability/{date}', [PetsitterAvailabilityController::class, 'destroy']);
    Route::post('/petsitters/{petsitter}/approve', [PetsitterController::class, 'approve']);
    Route::post('/petsitters/{petsitter}/reject', [PetsitterController::class, 'reject']);
    Route::apiResource('petsitters', PetsitterController::class);
    }
}

// SPA authentication routes — need session middleware for Auth::attempt() and session()->regenerate()
Route::middleware([
    \Illuminate\Cookie\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Session\Middleware\StartSession::class,
])->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::get('/petsitters/{id}/availability', [PetsitterAvailabilityController::class, 'availability']);

// Rota pública para listar petsitters aprovados
Route::get('/petsitters/public', [PetsitterController::class, 'publicIndex']);

Route::middleware('auth:sanctum')->group(function () {
    extracted();
    Route::get('/petsitters/{petsitter}/posts', [PostController::class, 'byPetsitter']);
    Route::apiResource('posts', PostController::class);

    // Animals
    Route::apiResource('animals', AnimalController::class);

    // Favourites
    Route::get('/favourites', [FavouriteController::class, 'index']);
    Route::post('/favourites', [FavouriteController::class, 'store']);
    Route::delete('/favourites/{petsitter}', [FavouriteController::class, 'destroy']);

    // Support Tickets
    Route::get('/support-tickets', [SupportTicketsController::class, 'index']);
    Route::post('/support-tickets', [SupportTicketsController::class, 'store']);
    Route::get('/support-tickets/{ticket}', [SupportTicketsController::class, 'show']);
    Route::post('/support-tickets/{ticket}/claim', [SupportTicketsController::class, 'claim']);
    Route::post('/support-tickets/{ticket}/close', [SupportTicketsController::class, 'close']);
});
