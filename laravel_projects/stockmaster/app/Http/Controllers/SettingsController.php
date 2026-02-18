<?php

namespace App\Http\Controllers;

use App\Models\UserSetting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();

        $settings = $user->settings;

        // ha nincs még sor, csinálunk egy üreset
        if (!$settings) {
            $settings = UserSetting::create([
                'user_id' => $user->id,
                'timezone' => null,
                'data' => [],
            ]);
        }

        return view('settings.edit', [
            'settings' => $settings,
            'data' => $settings->data ?? [],
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'AutoLogin' => ['nullable', 'boolean'],
            'ReceiveNotifications' => ['nullable', 'boolean'],
            'PreferredChartTheme' => ['nullable', 'in:dark,light'],
            'PreferredChartInterval' => ['nullable', 'string', 'max:10'],
            'NewsLimit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'NewsPerSymbolLimit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'NewsPortfolioTotalLimit' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'CalendarLimit' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $settings = $user->settings ?: UserSetting::create([
            'user_id' => $user->id,
            'timezone' => null,
            'data' => [],
        ]);

        $data = $settings->data ?? [];

        // checkbox-oknál, ha nincs bepipálva, nem jön a requestben
        $data['AutoLogin'] = (int) $request->boolean('AutoLogin');
        $data['ReceiveNotifications'] = (int) $request->boolean('ReceiveNotifications');

        // többi mező
        foreach ([
            'PreferredChartTheme',
            'PreferredChartInterval',
            'NewsLimit',
            'NewsPerSymbolLimit',
            'NewsPortfolioTotalLimit',
            'CalendarLimit',
        ] as $key) {
            if (array_key_exists($key, $validated)) {
                $data[$key] = $validated[$key];
            }
        }

        $settings->update([
            'data' => $data,
        ]);

        return redirect()
            ->route('settings.edit')
            ->with('status', 'Settings saved!');
    }
}
