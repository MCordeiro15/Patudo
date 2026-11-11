<?php

use App\Http\Controllers\PetsitterProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Sanctum CSRF cookie route for SPA authentication
Route::get('/sanctum/csrf-cookie', function (Request $request) {
    return response()->json(['message' => 'CSRF cookie set']);
})->name('sanctum.csrf-cookie');

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
Route::inertia('/favorites', 'Petsitter/Favorites')->middleware('auth')->name('favorites');
Route::get('/petsitters/profile', [PetsitterProfileController::class, 'showOwn'])
    ->middleware('auth')
    ->name('petsitters.profile');
Route::get('/petsitters/{petsitter}', [PetsitterProfileController::class, 'show'])
    ->middleware('auth')
    ->whereNumber('petsitter')
    ->name('petsitters.show');
Route::inertia('/profile', 'Owner/Profile')->middleware('auth')->name('owner.profile');
Route::inertia('/dev/modals', 'Modals/ModalTests');
require __DIR__ . '/settings.php';
