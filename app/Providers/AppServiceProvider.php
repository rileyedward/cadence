<?php

namespace App\Providers;

use App\Services\Calendar\CalendarSync;
use App\Services\Calendar\GoogleCalendarDriver;
use App\Services\Calendar\NullCalendarDriver;
use Carbon\CarbonImmutable;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Resolve the calendar driver from config; the null driver keeps the app
        // fully functional with no Google credentials (doc 15).
        $this->app->singleton(CalendarSync::class, function ($app) {
            $driver = config('cadence.calendar.driver', 'null');
            $google = $driver === 'google' ? $app->make(GoogleCalendarDriver::class) : null;

            return $google && $google->isConfigured()
                ? $google
                : $app->make(NullCalendarDriver::class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        // Inertia props mirror our TS DTOs as plain arrays — no "data" wrapper.
        JsonResource::withoutWrapping();

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
