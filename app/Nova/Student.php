<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Exceptions\HelperNotSupported;
use Laravel\Nova\Fields\Email;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Http\Requests\NovaRequest;
use App\Nova\Filters\AssessmentStatus;
class Student extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Student>
     */
    public static $model = \App\Models\Student::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'email','first_name','last_name','uid','principles_account_uid','principles_person_uid'
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param \Laravel\Nova\Http\Requests\NovaRequest $request
     * @return array
     * @throws HelperNotSupported
     */
    public function fields(NovaRequest $request)
    {
        return [
            ID::make()
                ->sortable(),

            Boolean::make('Completed', attribute: 'assessment_complete'),

            Boolean::make('Shortscale Completed', attribute: 'shortscale_complete'),
            
            Email::make('Email', 'email')
                ->sortable()
                ->rules('required', 'email')
                ->copyable(),

            Text::make('First Name', 'first_name')
                ->sortable()
                ->rules('required'),

            Text::make('Last Name', 'last_name')
                ->sortable()
                ->rules('required'),

            Text::make('UID', 'uid')
                ->sortable()
                ->hideWhenCreating()
                ->hideWhenUpdating()
                ->copyable(),

            Text::make('Principles Account UID', 'principles_account_uid')
                ->sortable()
                ->hideWhenCreating()
                ->hideWhenUpdating()
                ->copyable(),

            Text::make('Principles Person UID', 'principles_person_uid')
                ->sortable()
                ->hideFromIndex()
                ->hideWhenCreating()
                ->hideWhenUpdating()
                ->copyable(),

            Text::make('Student URL', function () {
                return route('personality-test.index', ['studentUid' => $this->uid]);
            })->copyable(),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function cards(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function filters(NovaRequest $request)
    {
        return [
            new AssessmentStatus,
        ];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function lenses(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function actions(NovaRequest $request)
    {
        return [];
    }

    /**
     * Determine if the current user can update the given resource.
     *
     * @param  Request  $request
     * @return bool
     */
    public function authorizedToUpdate(Request $request)
    {
        return false;
    }

    /**
     * Determine if the current user can delete the given resource.
     *
     * @param  Request  $request
     * @return bool
     */
    public function authorizedToDelete(Request $request)
    {
        return false;
    }
}
