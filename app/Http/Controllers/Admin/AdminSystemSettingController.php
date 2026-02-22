<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminSystemSettingController extends Controller
{
    public function index(): Response
    {
        $settings = SystemSetting::query()
            ->orderBy('group')
            ->orderBy('key')
            ->get()
            ->groupBy('group');

        return Inertia::render('admin/system-settings/index', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*.key' => ['required', 'string'],
            'settings.*.value' => ['nullable', 'string'],
        ]);

        foreach ($validated['settings'] as $item) {
            SystemSetting::query()
                ->where('key', $item['key'])
                ->update(['value' => $item['value']]);
        }

        return back()->with('success', 'Settings updated successfully.');
    }
}
