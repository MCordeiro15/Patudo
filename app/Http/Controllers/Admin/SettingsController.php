<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('Admin/SettingsView', [
            'settings' => [
                'weeklySummaryEnabled' => (bool) $request->user()->weekly_summary_enabled,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'weekly_summary_enabled' => ['required', 'boolean'],
        ]);

        $request->user()->update([
            'weekly_summary_enabled' => (bool) $validated['weekly_summary_enabled'],
        ]);

        return to_route('admin.settings');
    }
}
