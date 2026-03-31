<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255', 'unique:users,Username'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,Email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'Username' => $validated['username'],
                'Email' => $validated['email'],
                'PasswordHash' => Hash::make($validated['password']),
                'RegistrationDate' => now(),
                'IsLoggedIn' => 1,
                'PreferredTheme' => 'dark',
                'NotificationsEnabled' => 1,
                'DemoBalance' => 10000.00,
                'RealBalance' => 0.00,
                'PreferredCurrency' => 'USD',
            ]);

            UserSetting::create([
                'user_id' => $user->ID,
                'timezone' => 'Europe/Budapest',
                'chart_interval' => '1m',
                'chart_theme' => 'dark',
                'chart_limit_initial' => 1500,
                'chart_backfill_chunk' => 1500,
                'news_limit' => 8,
                'news_per_symbol_limit' => 3,
                'news_portfolio_total_limit' => 20,
                'calendar_limit' => 8,
                'auto_login' => 0,
                'receive_notifications' => 1,
                'data' => null,
            ]);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}