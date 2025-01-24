<?php

namespace App\Nova\Filters;

use Laravel\Nova\Filters\BooleanFilter;
use Laravel\Nova\Http\Requests\NovaRequest;

class AssessmentStatus extends BooleanFilter
{
    /**
     * Apply the filter to the given query.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(NovaRequest $request, $query, $value)
    {
        /** @var array{assessment_complete: bool, shortscale_complete: bool} $value */
        return $query->where(column: function ($query) use ($value) {
            if($value['any']) {
                return $query;
            } else {
                $value['shortscale_complete'] = $value['assessment_complete'] ? true : $value['shortscale_complete'];
                return $query->where('assessment_complete', $value['assessment_complete'])
                    ->where('shortscale_complete', $value['shortscale_complete']);
            }
        });
    }

    /**
     * Get the filter's available options.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function options(NovaRequest $request)
    {
        return [
            'Any' => 'any',
            'Assessment complete' => 'assessment_complete',
            'Shortscale complete' => 'shortscale_complete',
        ];
    }

    public function default()
    {
        return [
            'any' => true,
            'assessment_complete' => false,
            'shortscale_complete' => false,
        ];
    }
}
