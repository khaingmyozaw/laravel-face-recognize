<?php

namespace App\Providers;

use App\Auth\ApiTokenGuard;
use App\Enums\Permission;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configurePermissions();
        $this->configureApiTokenAuth();
    }

    /**
     * Authenticate API requests with Bearer personal access tokens.
     */
    protected function configureApiTokenAuth(): void
    {
        Auth::extend('api-token', function ($app): ApiTokenGuard {
            $guard = new ApiTokenGuard(
                function (Request $request): ?User {
                    $plainTextToken = $request->bearerToken();

                    if ($plainTextToken === null || $plainTextToken === '') {
                        return null;
                    }

                    $accessToken = PersonalAccessToken::query()
                        ->where('token', hash('sha256', $plainTextToken))
                        ->first();

                    if ($accessToken === null || $accessToken->isExpired()) {
                        return null;
                    }

                    $user = $accessToken->tokenable;

                    if (! $user instanceof User) {
                        return null;
                    }

                    $accessToken->forceFill(['last_used_at' => now()])->save();

                    return $user->withAccessToken($accessToken);
                },
                $app['request'],
            );

            $app->refresh('request', $guard, 'setRequest');

            return $guard;
        });
    }

    /**
     * Register permission abilities for Gate / can() checks.
     */
    protected function configurePermissions(): void
    {
        foreach (Permission::cases() as $permission) {
            Gate::define(
                $permission->value,
                fn (User $user): bool => $user->hasPermission($permission),
            );
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
