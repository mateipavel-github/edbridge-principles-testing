<?php

namespace Principles\GeneralTools;

use Illuminate\Http\Request;
use Laravel\Nova\Menu\MenuSection;
use Laravel\Nova\Menu\MenuItem;
use Laravel\Nova\Nova;
use Laravel\Nova\Tool;

class GeneralTools extends Tool
{
    /**
     * Perform any tasks that need to happen when the tool is booted.
     *
     * @return void
     */
    public function boot()
    {
        Nova::script('GeneralTools', __DIR__.'/../dist/js/tool.js');
        Nova::style('GeneralTools', __DIR__.'/../dist/css/tool.css');
    }

    /**
     * Build the menu that renders the navigation links for the tool.
     *
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function menu(Request $request)
    {
        return MenuSection::make('General Tools', [
            MenuItem::make('Update assessments', '/GeneralTools'),
            MenuItem::make('Queue Jobs', '/GeneralTools/jobs'),
        ])
            ->icon('server');
    }
}
