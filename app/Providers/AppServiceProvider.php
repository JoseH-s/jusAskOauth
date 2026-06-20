<?php

namespace App\Providers;

use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantManager::class);
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme(config('app.url') ? parse_url(config('app.url'), PHP_URL_SCHEME) : 'https');
        }
        Gate::define('super-admin', fn (User $user) => $user->isSuperAdmin());

        Event::listen(Login::class, function () {
            session(['login_at' => now()->toIso8601String()]);
        });
    }
}
