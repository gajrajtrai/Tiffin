<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            \Laravel\Fortify\Contracts\LoginResponse::class,
            \App\Http\Responses\LoginResponse::class,
        );

        $this->app->singleton(
            \Laravel\Fortify\Contracts\RegisterResponse::class,
            \App\Http\Responses\RegisterResponse::class,
        );
    }

    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureAuthentication();
        $this->configureRedirects();
        $this->configureRateLimiting();
    }

    /*
    |--------------------------------------------------------------------------
    | Fortify actions
    |--------------------------------------------------------------------------
    */

    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Fortify views
    |--------------------------------------------------------------------------
    */

    private function configureViews(): void
    {
        Fortify::loginView(fn () => view('pages::auth.login'));
        Fortify::verifyEmailView(fn () => view('pages::auth.verify-email'));
        Fortify::twoFactorChallengeView(fn () => view('pages::auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => view('pages::auth.confirm-password'));
        Fortify::registerView(fn () => view('pages::auth.register'));
        Fortify::resetPasswordView(fn () => view('pages::auth.reset-password'));
        Fortify::requestPasswordResetLinkView(fn () => view('pages::auth.forgot-password'));
    }

    /*
    |--------------------------------------------------------------------------
    | Authentication — mobile OR email
    |--------------------------------------------------------------------------
    |
    | Accept either an 8-digit Bhutanese mobile number OR an email address.
    | Mobile is normalised internally so "17111101", "97517111101", and
    | "+97517111101" all match the same user. Suspended accounts are
    | rejected silently.
    |
    */

    private function configureAuthentication(): void
    {
        Fortify::authenticateUsing(function (Request $request) {
            $identifier = trim((string) $request->input('email'));
            $password   = (string) $request->input('password');

            if ($identifier === '' || $password === '') {
                return null;
            }

            // Normalise any common Bhutanese mobile format to 8-digit local
            $mobileCandidate = preg_replace('/^(\+?975)/', '', $identifier);

            $user = User::query()
                ->where('mobile', $mobileCandidate)
                ->orWhere('mobile', $identifier)
                ->orWhere('email', $identifier)
                ->first();

            if (! $user) {
                return null;
            }

            if (! Hash::check($password, $user->password)) {
                return null;
            }

            if ($user->status !== 'active') {
                return null;
            }

            return $user;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Post-login / post-register redirects
    |--------------------------------------------------------------------------
    |
    | Staff go to the admin dashboard. Customers go to the public menu.
    | The login response is handled by App\Http\Responses\LoginResponse
    | bound in register() above.
    |
    */

    private function configureRedirects(): void
    {
        // Login and Register redirects are handled by the response contracts
        // (LoginResponse, RegisterResponse) bound in register() above.

        // Email verification (not currently required) — send to menu or admin.
        Fortify::redirects('email-verification', function () {
            $user = auth()->user();

            if ($user?->isStaff()) {
                return route('admin.dashboard');
            }

            return route('menu.index');
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Rate limiting
    |--------------------------------------------------------------------------
    */

    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(
                Str::lower($request->input(Fortify::username())).'|'.$request->ip()
            );

            return Limit::perMinute(5)
                ->by($throttleKey)
                ->response(function () {
                    return back()->withErrors([
                        'email' => 'Too many login attempts. Please wait a minute and try again.',
                    ]);
                });
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perHour(5)
                ->by($request->ip())
                ->response(function () {
                    return back()->withErrors([
                        'mobile' => 'Too many registration attempts. Please try again later.',
                    ]);
                });
        });

        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perHour(3)
                ->by($request->ip())
                ->response(function () {
                    return back()->withErrors([
                        'email' => 'Too many password reset requests. Please try again later.',
                    ]);
                });
        });

        RateLimiter::for('passkeys', function (Request $request) {
            $credentialId = $request->input('credential.id');

            return Limit::perMinute(10)->by(
                ($credentialId ?: $request->session()->getId()).'|'.$request->ip(),
            );
        });
    }
}