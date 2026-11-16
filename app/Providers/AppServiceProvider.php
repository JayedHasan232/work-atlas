<?php

namespace App\Providers;

use App\Modules\Knowledge\Events\CurrentValueChanged;
use App\Modules\Knowledge\Events\FieldStatusChanged;
use App\Modules\Publishing\Actions\QueueIndexGateCheck;
use App\Modules\Taxonomy\Actions\SyncOccupationNames;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
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
    }

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
