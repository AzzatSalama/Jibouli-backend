<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // $this->registerPolicies();
        Sanctum::authenticateAccessTokensUsing(function ($accessToken, $isValid) {
            return Cache::remember("token:{$accessToken->id}", 120, function () use ($accessToken, $isValid) {
                return $isValid && !$accessToken->revoked;
            });
        });
        //
    }
}
