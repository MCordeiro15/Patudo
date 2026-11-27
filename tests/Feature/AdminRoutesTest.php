<?php

/**
 * Testes das rotas de administração (/admin/*) e dos contadores do menu (prop partilhada adminCounts).
 * Tests for the admin routes (/admin/*) and the menu counters (adminCounts shared prop).
 */
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Petsitter;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

/** Páginas de admin e o componente Inertia de cada uma. */
dataset('admin pages', [
    ['/admin/petsitters/pending', 'Admin/VerificationsView'],
    ['/admin/users', 'Admin/UsersView'],
    ['/admin/users/1', 'Admin/UserDetailView'],
    ['/admin/bookings', 'Admin/BookingsView'],
    ['/admin/bookings/1', 'Admin/BookingDetailView'],
    ['/admin/reviews', 'Admin/ReviewsView'],
    ['/admin/reports', 'Admin/ReportsView'],
    ['/admin/support', 'Admin/SupportView'],
    ['/admin/settings', 'Admin/SettingsView'],
]);

/** Cria um support ticket do tipo e estado pedidos. */
function makeTicketForCounts(string $type, string $status): SupportTicket
{
    return SupportTicket::create([
        'user_id' => User::factory()->create()->id,
        'subject' => 'Assunto',
        'description' => 'Descrição',
        'type' => $type,
        'status' => $status,
    ]);
}

test('guest is redirected to login on the admin pages', function (string $url) {
    $this->get($url)->assertRedirect(route('login'));
})->with('admin pages');

test('guest gets 401 on the admin pages when asking for json', function (string $url) {
    $this->getJson($url)->assertUnauthorized();
})->with('admin pages');

test('non admin is redirected to their dashboard on the admin pages', function (string $url) {
    $this->actingAs(User::factory()->create())
        ->get($url)
        ->assertRedirect(route('dashboard'));
})->with('admin pages');

test('admin sees the admin pages', function (string $url, string $component) {
    $this->actingAs(User::factory()->admin()->create())
        ->get($url)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component($component));
})->with('admin pages');

test('detail pages receive the id from the url', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/admin/users/7')
        ->assertInertia(fn (Assert $page) => $page->where('userId', 7));

    $this->actingAs($admin)->get('/admin/bookings/12')
        ->assertInertia(fn (Assert $page) => $page->where('bookingId', 12));
});

// Teste geral: qualquer rota em /api/admin/* tem de exigir sessão e admin.
// Hoje o grupo está vazio; apanha rotas de admin criadas mais tarde fora do grupo.
test('every api admin route requires auth:sanctum and can:admin', function () {
    $adminRoutes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => str_starts_with($route->uri(), 'api/admin'));

    foreach ($adminRoutes as $route) {
        expect($route->gatherMiddleware())
            ->toContain('auth:sanctum', 'can:admin');
    }

    expect(true)->toBeTrue(); // evita "risky" enquanto o grupo não tem rotas
});

test('admin receives the menu counters', function () {
    Petsitter::factory()->count(2)->create(); // pending
    Petsitter::factory()->approved()->create();
    Petsitter::factory()->rejected()->create();

    makeTicketForCounts('complaint', 'open');
    makeTicketForCounts('complaint', 'open');
    makeTicketForCounts('complaint', 'open');
    makeTicketForCounts('complaint', 'closed');
    makeTicketForCounts('help', 'open');
    makeTicketForCounts('help', 'in_progress');

    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin/users')
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.isAdmin', true)
            ->where('adminCounts.pending_petsitters', 2)
            ->where('adminCounts.open_reports', 3)
            ->where('adminCounts.open_support', 1));
});

test('non admin receives adminCounts as null', function () {
    Petsitter::factory()->create();
    makeTicketForCounts('help', 'open');

    $this->actingAs(User::factory()->create())
        ->get('/petsitters')
        ->assertInertia(fn (Assert $page) => $page
            ->where('adminCounts', null)
            ->where('auth.isAdmin', false));
});

test('guest receives adminCounts as null', function () {
    $this->get('/')
        ->assertInertia(fn (Assert $page) => $page->where('adminCounts', null));
});

test('partial reload returns only the admin counters', function () {
    Petsitter::factory()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/users', [
            'X-Inertia' => 'true',
            // a versão tem de bater com a do middleware, senão o Inertia responde 409
            'X-Inertia-Version' => (string) app(HandleInertiaRequests::class)->version(request()),
            'X-Inertia-Partial-Component' => 'Admin/UsersView',
            'X-Inertia-Partial-Data' => 'adminCounts',
        ])
        ->assertOk()
        ->assertJsonPath('props.adminCounts.pending_petsitters', 1)
        ->assertJsonMissingPath('props.auth');
});
