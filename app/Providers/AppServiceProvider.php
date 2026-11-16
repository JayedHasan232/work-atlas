<?php

namespace App\Providers;

use App\Models\User;
use App\Modules\Knowledge\Events\CurrentValueChanged;
use App\Modules\Knowledge\Events\FieldStatusChanged;
use App\Modules\Publishing\Actions\QueueIndexGateCheck;
use App\Modules\Taxonomy\Actions\SyncOccupationNames;
use App\Support\Monitoring\CheckHealth;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->listenAcrossModules();
        $this->configureRateLimits();
        $this->configureGates();
    }

    /**
     * Contribution limits (PLAN M16, A15). Error reports are counted per IP address, suggestions and
     * micro-task answers (M17) per person; the counters live only in the cache, never in the database.
     */
    /**
     * The moderation pages (PLAN M18, D66) are for reviewers and above who are not blocked.
     */
    protected function configureGates(): void
    {
        Gate::define('moderate', fn (User $user): bool => $user->canModerate());
    }

    protected function configureRateLimits(): void
    {
        RateLimiter::for('error-reports', fn (Request $request): Limit => Limit::perHour(Config::integer('atlas.community.error_reports_per_hour'))
            ->by((string) $request->ip()));

        RateLimiter::for('suggestions', fn (Request $request): Limit => Limit::perMinute(Config::integer('atlas.community.suggestions_per_minute'))
            ->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));

        RateLimiter::for('public-api', fn (Request $request): Limit => Limit::perMinute(Config::integer('atlas.api.per_minute'))
            ->by((string) $request->ip()));

        // Fortify's register and forgot-password forms have no limiter of their own: without one,
        // anyone could create accounts in bulk or send reset mail to many addresses (D81).
        RateLimiter::for('account-forms', fn (Request $request): Limit => $request->isMethod('POST')
            && in_array($request->route()?->getName(), ['register.store', 'password.email'], true)
            ? Limit::perHour(Config::integer('atlas.security.account_forms_per_hour'))->by($request->ip() ?? '')
            : Limit::none());

        RateLimiter::for('answers', fn (Request $request): Limit => Limit::perMinute(Config::integer('atlas.community.answers_per_minute'))
            ->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    /**
     * Modules react to each other's events here, because a module has no service provider of its own
     * (Arch/ModuleLayoutTest). The listener calls the other module's action.
     */
    protected function listenAcrossModules(): void
    {
        // Taxonomy's occupation_names is a copy of the current `names` values (D44).
        Event::listen(function (CurrentValueChanged $event): void {
            if ($event->fieldKey === SyncOccupationNames::FIELD_KEY && $event->locale !== null && $event->region === null) {
                app(SyncOccupationNames::class)->handle($event->occupationId, $event->locale, $event->value);
            }
        });

        // The index gate (PLAN M14): a new value or status may change whether a page is indexable.
        // Nothing to do while the gate is off; `atlas:index-gate:recompute` catches up when it is
        // turned on.
        Event::listen(function (CurrentValueChanged|FieldStatusChanged $event): void {
            app(QueueIndexGateCheck::class)->handle($event->occupationId);
        });

        // `/up` checks the database and Redis (PLAN M22).
        Event::listen(DiagnosingHealth::class, CheckHealth::class);
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        // The session cookie is Secure whenever the app is served over HTTPS, unless
        // SESSION_SECURE_COOKIE says otherwise (D81): production cannot send it over plain HTTP by accident.
        if (Config::get('session.secure') === null && str_starts_with(Config::string('app.url'), 'https://')) {
            Config::set('session.secure', true);
        }

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
