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
    | Authentication
    |--------------------------------------------------------------------------
    |
    | Accept either a Bhutanese mobile number OR an email address as the
    | login identifier. Mobile takes priority. Suspended accounts are
    | rejected silently to avoid leaking account state.
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

            // Normalise Bhutanese mobile format: 17111101 → +97517111101
            $mobileCandidate = $identifier;
            if (preg_match('/^\d{8}$/', $identifier)) {
                $mobileCandidate = '+975'.$identifier;
            } elseif (preg_match('/^975\d{8}$/', $identifier)) {
                $mobileCandidate = '+'.$identifier;
            }

            $user = User::query()
                ->where('mobile', $mobileCandidate)
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
    | Post-login redirects
    |--------------------------------------------------------------------------
    |
    | Staff go to the admin dashboard. Customers go to their wallet.
    | (Wallet page is built in Phase 10.3 — for now it's a placeholder.)
    |
    */

    private function configureRedirects(): void
    {
        // Login redirect is handled by App\Http\Responses\LoginResponse,
        // bound in register() above. Nothing to do here.
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

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('passkeys', function (Request $request) {
            $credentialId = $request->input('credential.id');

            return Limit::perMinute(10)->by(
                ($credentialId ?: $request->session()->getId()).'|'.$request->ip(),
            );
        });
    }
}