<?php

/**
 * Ficheiro PHP: web.php
 * PHP file: web.php
 */
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
Route::inertia('/messages', 'Messages')->middleware('auth')->name('messages');
// Foto de perfil de um utilizador como imagem (usada pelo chat, que precisa de um URL e não de base64)
Route::get('/users/{user}/avatar', \App\Http\Controllers\UserAvatarController::class)
    ->middleware('auth')
    ->whereNumber('user')
    ->name('users.avatar');
Route::get('/petsitters/{petsitter}', [PetsitterProfileController::class, 'show'])
    ->middleware('auth')
    ->whereNumber('petsitter')
    ->name('petsitters.show');
Route::get('/profile-owner-view', fn () => Inertia::render('Petsitter/ProfileOwnerView'))
    ->middleware('auth')
    ->name('profile.owner-view');

Route::get('/profile', function () {
    $user = auth()->user();
    $petsitter = \App\Models\Petsitter::where('user_id', $user->id)->first();

    // só vai para o perfil de petsitter depois de o admin aprovar
    if ($petsitter && $petsitter->approval_status === 'approved') {
        return redirect()->route('petsitters.profile');
    }

    return Inertia::render('Owner/Profile', [
        // null (sem candidatura), 'pending' ou 'rejected' -> o perfil mostra o botão ou o estado
        'petsitterApplicationStatus' => $petsitter?->approval_status,
    ]);
})->middleware('auth')->name('owner.profile');

// Página de pedir reserva (dono). O petsitter vem no URL: /booking?petsitter=ID
// Os dados são carregados pela página via API, que já valida tudo ao criar a reserva.
Route::inertia('/booking', 'Owner/BookingPage')
    ->middleware('auth')
    ->name('bookings.create');

// Página de reserva confirmada (dono). O id vem no URL: /booking-confirmed?booking=ID
// Os dados são carregados pela página via /api/bookings/{id}, que já verifica permissões.
Route::inertia('/booking-confirmed', 'Owner/BookingConfirmed')
    ->middleware('auth')
    ->name('bookings.confirmed');

// Pedidos de reserva recebidos (petsitter): aceitar / recusar.
// Os dados são carregados pela página via API, que já verifica permissões.
Route::inertia('/orders', 'Petsitter/ReservationNotification')
    ->middleware('auth')
    ->name('bookings.orders');

// Reservas feitas pelo utilizador (dono): estado, cancelar.
Route::inertia('/reservations', 'Owner/ReservationNotificationToOwner')
    ->middleware('auth')
    ->name('bookings.reservations');

// Páginas de administração: só para admins (403 para os outros, login para quem não tem sessão).
// Os dados de cada página são carregados pela API (/api/admin/*), que também exige admin.
Route::middleware(['auth', 'can:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::inertia('/petsitters/pending', 'Admin/VerificationsView')->name('petsitters.pending');
    Route::inertia('/users', 'Admin/UsersView')->name('users');
    Route::get('/users/{user}', fn (string $user) => Inertia::render('Admin/UserDetailView', ['userId' => (int) $user]))
        ->whereNumber('user')
        ->name('users.show');
    Route::inertia('/bookings', 'Admin/BookingsView')->name('bookings');
    Route::get('/bookings/{booking}', fn (string $booking) => Inertia::render('Admin/BookingDetailView', ['bookingId' => (int) $booking]))
        ->whereNumber('booking')
        ->name('bookings.show');
    Route::inertia('/reviews', 'Admin/ReviewsView')->name('reviews');
    Route::inertia('/reports', 'Admin/ReportsView')->name('reports');
    Route::inertia('/support', 'Admin/SupportView')->name('support');
    Route::inertia('/settings', 'Admin/SettingsView')->name('settings');
});

// Pagamento confirmado (dono)
Route::inertia('/payment-confirmed', 'Payment/PaymentConfirmed')
    ->middleware('auth')
    ->name('payment.confirmed');

Route::inertia('/dev/modals', 'Modals/ModalTests')->name('dev.modals');
require __DIR__ . '/settings.php';
