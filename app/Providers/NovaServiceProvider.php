<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Nova\Nova;
use Laravel\Nova\NovaApplicationServiceProvider;
use Laravel\Nova\Menu\MenuSection;
use Laravel\Nova\Menu\MenuItem;
use Principles\GeneralTools\GeneralTools;

class NovaServiceProvider extends NovaApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }

    /**
     * Register the Nova routes.
     *
     * @return void
     */
    protected function routes()
    {
        Nova::routes()
                ->withAuthenticationRoutes()
                ->withPasswordResetRoutes()
                ->register();
    }

    /**
     * Register the Nova gate.
     *
     * This gate determines who can access Nova in non-local environments.
     *
     * @return void
     */
    protected function gate()
    {
        Gate::define('viewNova', function ($user) {
            return in_array($user->email, [
                'ionel@provoker.io',
                'matei@edbridge.academy',
                'mateipavel@gmail.com',
                'catalin.bica89@gmail.com',
                'daria.cupareanu@gmail.com'
            ]);
        });
    }

    /**
     * Get the dashboards that should be listed in the Nova sidebar.
     *
     * @return array
     */
    protected function dashboards()
    {
        return [
            new \App\Nova\Dashboards\Main,
        ];
    }

    /**
     * Get the tools that should be listed in the Nova sidebar.
     *
     * @return array
     */
    public function tools()
    {
        return [
            new GeneralTools,
        ];
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    public function resources()
    {
        Nova::resources([
            \App\Nova\User::class,
            \App\Nova\Student::class,
            // ... other resources
        ]);
    }

    protected function menu()
    {
        return [
            MenuSection::make('Resources', [
                MenuItem::resource(\App\Nova\Student::class),
                MenuItem::resource(\App\Nova\User::class),
            ])->icon('users')->collapsable(),

            // Keep existing menu items
            MenuSection::make('Tools', [
                MenuItem::make('General Tools'),
            ])->icon('tools'),
        ];
    }
}
