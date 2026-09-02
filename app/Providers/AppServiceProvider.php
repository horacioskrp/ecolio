<?php

namespace App\Providers;

use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Observers\SchoolObserver;
use App\Observers\StudentObserver;
use App\Observers\UserObserver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\URL;

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
        $this->registerObservers();

        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    }

    /**
     * Register model observers.
     */
    protected function registerObservers(): void
    {
        User::observe(UserObserver::class);
        Student::observe(StudentObserver::class);
        School::observe(SchoolObserver::class);
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

        // SQLite (tests) : désactiver les FK pour permettre les dropColumn
        if (config('database.connections.' . config('database.default') . '.driver') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        }

        Password::defaults(function (): ?Password {
            // Les tests utilisent des mots de passe simples : on garde le défaut souple.
            if (app()->runningUnitTests()) {
                return null;
            }

            $rule = Password::min(12)
                ->mixedCase()   // au moins une majuscule et une minuscule
                ->letters()
                ->numbers()
                ->symbols();

            // Vérification contre les fuites connues (HaveIBeenPwned) en production.
            return app()->isProduction() ? $rule->uncompromised() : $rule;
        });
    }
}
