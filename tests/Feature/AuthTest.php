<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_user_and_logs_in(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'phone' => '912345678',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('users', ['email' => 'ana@example.com']);
        $this->assertAuthenticated();
    }

    public function test_register_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'ana@example.com']);

        $this->postJson('/api/register', [
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_login_with_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertOk();

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_with_wrong_password_fails(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'errada',
        ])->assertUnprocessable();

        $this->assertGuest();
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_logout_ends_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/logout')->assertNoContent();

        $this->assertGuest();
    }
}
