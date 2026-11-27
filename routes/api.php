<?php

use App\Http\Controllers\API\Admin\ReportsController as AdminReportsController;
use App\Http\Controllers\API\Admin\UserController as AdminUserController;
use App\Http\Controllers\API\AnimalController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BookingController;
use App\Http\Controllers\API\ChatController;
use App\Http\Controllers\API\FavouriteController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\PetsitterAvailabilityController;
use App\Http\Controllers\API\PetsitterController;
use App\Http\Controllers\API\PetsitterServiceController;
use App\Http\Controllers\API\PostController;
use App\Http\Controllers\API\PostFavouriteController;
use App\Http\Controllers\API\ReviewController;
use App\Http\Controllers\API\SupportTicketsController;
use App\Http\Controllers\API\UserController;
use App\Http\Middleware\EnsureAccountActive;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas públicas — não requerem autenticação
|--------------------------------------------------------------------------
*/

// Rota pública para listar petsitters aprovados
Route::get('/petsitters/public', [PetsitterController::class, 'publicIndex']);

// Disponibilidade de um petsitter (pública)
Route::get('/petsitters/{id}/availability', [PetsitterAvailabilityController::class, 'availability']);

/*
|--------------------------------------------------------------------------
| Rotas de autenticação — precisam de sessão para Auth::attempt()
|--------------------------------------------------------------------------
*/
Route::middleware([
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
])->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Logout precisa de sessão para invalidar
Route::middleware([
    'auth:sanctum',
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});

/*
|--------------------------------------------------------------------------
| Rotas autenticadas — requerem auth:sanctum
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', EnsureAccountActive::class])->group(function () {

    // Auth
    Route::get('/me', [AuthController::class, 'myInformation']);

    // Users
    Route::post('/users/{user}/suspend', [AdminUserController::class, 'suspend'])->middleware('can:admin');
    Route::apiResource('users', UserController::class);

    // Petsitters
    Route::post('/petsitters/availability/toggle', [PetsitterAvailabilityController::class, 'toggle']);
    Route::put('/petsitters/availability/days', [PetsitterController::class, 'updateAvailabilityDays']);
    Route::post('/petsitters/{petsitter}/availability', [PetsitterAvailabilityController::class, 'store']);
    Route::put('/petsitters/{petsitter}/availability/{date}', [PetsitterAvailabilityController::class, 'update']);
    Route::delete('/petsitters/{petsitter}/availability/{date}', [PetsitterAvailabilityController::class, 'destroy']);
    Route::post('/petsitters/{petsitter}/approve', [PetsitterController::class, 'approve']);
    Route::post('/petsitters/{petsitter}/reject', [PetsitterController::class, 'reject']);
    // documento de identificação (disco privado): só o admin o pode abrir
    Route::get('/petsitters/{petsitter}/identification-document', [PetsitterController::class, 'identificationDocument']);
    Route::apiResource('petsitters', PetsitterController::class);

    // Posts
    Route::get('/petsitters/{petsitter}/posts', [PostController::class, 'byPetsitter']);
    Route::apiResource('posts', PostController::class);

    // Post Favourites
    Route::get('/post-favourites', [PostFavouriteController::class, 'index']);
    Route::post('/posts/{post}/favourite', [PostFavouriteController::class, 'store']);
    Route::delete('/posts/{post}/favourite', [PostFavouriteController::class, 'destroy']);

    // Animals
    Route::apiResource('animals', AnimalController::class);

    // Favourites
    Route::get('/favourites', [FavouriteController::class, 'index']);
    Route::post('/favourites', [FavouriteController::class, 'store']);
    Route::delete('/favourites/{petsitter}', [FavouriteController::class, 'destroy']);

    // Bookings
    Route::get('/bookings/availability', [BookingController::class, 'availability']);
    Route::apiResource('bookings', BookingController::class)->only(['index', 'store', 'show']);
    Route::patch('/bookings/{booking}/status', [BookingController::class, 'updateStatus']);

    // Reviews
    Route::post('/bookings/{booking}/review', [ReviewController::class, 'store']);
    Route::get('/petsitters/{petsitter}/reviews', [ReviewController::class, 'byPetsitter']);
    Route::patch('/reviews/{review}/hide', [ReviewController::class, 'hide']);
    Route::apiResource('reviews', ReviewController::class)->only(['show', 'update', 'destroy']);

    // Services
    Route::get('/petsitters/{petsitter}/services', [PetsitterServiceController::class, 'index']);
    Route::post('/petsitters/{petsitter}/services', [PetsitterServiceController::class, 'store']);
    Route::match(['put', 'patch'], '/services/{service}', [PetsitterServiceController::class, 'update']);
    Route::delete('/services/{service}', [PetsitterServiceController::class, 'destroy']);

    // Support Tickets
    Route::get('/support-tickets', [SupportTicketsController::class, 'index']);
    Route::post('/support-tickets', [SupportTicketsController::class, 'store'])
        ->withoutMiddleware(EnsureAccountActive::class);
    Route::get('/support-tickets/{ticket}', [SupportTicketsController::class, 'show']);
    Route::post('/support-tickets/{ticket}/claim', [SupportTicketsController::class, 'claim']);
    Route::post('/support-tickets/{ticket}/close', [SupportTicketsController::class, 'close']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications', [NotificationController::class, 'store']);
    Route::get('/notifications/{notification}', [NotificationController::class, 'show']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('/notifications/{notification}/unread', [NotificationController::class, 'markAsUnread']);
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);

    // Chat (TalkJS)
    Route::get('/chat/session', [ChatController::class, 'session']);
    Route::get('/chat/contacts', [ChatController::class, 'contacts']);
});

/*
|--------------------------------------------------------------------------
| Rotas de administração — /api/admin/*, só para admins
|--------------------------------------------------------------------------
| Sem sessão devolvem 401 (auth:sanctum) e quem não é admin recebe 403 (can:admin).
| As rotas de cada página (utilizadores, reservas, avaliações, denúncias, suporte,
| definições) entram aqui nos tickets seguintes.
*/
Route::middleware(['auth:sanctum', 'can:admin', EnsureAccountActive::class])->prefix('admin')->name('api.admin.')->group(function () {
    Route::get('/reports', [AdminReportsController::class, 'index'])->name('reports.index');
    Route::get('/reports/{report}', [AdminReportsController::class, 'show'])->name('reports.show');
    Route::post('/reports/{report}/archive', [AdminReportsController::class, 'archive'])->name('reports.archive');
    Route::post('/reports/{report}/close', [AdminReportsController::class, 'close'])->name('reports.close');
    Route::post('/reports/{report}/warning', [AdminReportsController::class, 'sendWarning'])->name('reports.warning');
    Route::get('/users/export', [AdminUserController::class, 'export'])->name('users.export');
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}/notes', [AdminUserController::class, 'notes'])->name('users.notes.index');
    Route::post('/users/{user}/notes', [AdminUserController::class, 'storeNote'])->name('users.notes.store');
    Route::post('/users/{user}/suspend', [AdminUserController::class, 'suspend'])->name('users.suspend');
    Route::post('/users/{user}/ban', [AdminUserController::class, 'ban'])->name('users.ban');
    Route::post('/users/{user}/warning', [AdminUserController::class, 'sendWarning'])->name('users.warning');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
});
