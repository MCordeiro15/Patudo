<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('admin can view their saved weekly summary preference', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->fresh()->weekly_summary_enabled)->toBeTrue();

    $this->actingAs($admin)
        ->get('/admin/settings')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/SettingsView')
            ->where('settings.weeklySummaryEnabled', true));
});

test('admin can save a weekly summary preference without changing another admin', function () {
    $admin = User::factory()->admin()->create();
    $anotherAdmin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch('/admin/settings', [
            'weekly_summary_enabled' => false,
            'notifications_enabled' => false,
        ])
        ->assertRedirect('/admin/settings');

    expect($admin->fresh()->weekly_summary_enabled)->toBeFalse()
        ->and($anotherAdmin->fresh()->weekly_summary_enabled)->toBeTrue();

    $this->actingAs($admin)
        ->get('/admin/settings')
        ->assertInertia(fn (Assert $page) => $page
            ->where('settings.weeklySummaryEnabled', false));
});

test('admin settings require a weekly summary choice', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch('/admin/settings')
        ->assertSessionHasErrors('weekly_summary_enabled');

    expect($admin->fresh()->weekly_summary_enabled)->toBeTrue();
});

test('non-admin cannot update admin settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch('/admin/settings', ['weekly_summary_enabled' => false])
        ->assertRedirect('/dashboard');

    expect($user->fresh()->weekly_summary_enabled)->toBeTrue();
});
