<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Resource;
use Laravel\Nova\Http\Requests\NovaRequest;
use Illuminate\Support\Facades\Redis;

class Job extends Resource
{
    public static $model = \App\Models\Job::class;
    public static $title = 'id';
    public static $search = ['id'];
    public static function label() { return 'Queue Jobs'; }

    public function fields(NovaRequest $request)
    {
        return [
            ID::make()->sortable(),
            Text::make('Queue'),
            Text::make('Status'),
            DateTime::make('Created At'),
            Code::make('Payload')->json(),
        ];
    }

    public static function indexQuery(NovaRequest $request, $query)
    {
        $jobs = collect();
        $queues = ['default', 'high', 'low'];

        foreach ($queues as $queue) {
            $pendingJobs = Redis::lrange("queues:{$queue}", 0, -1);
            foreach ($pendingJobs as $job) {
                $payload = json_decode($job);
                $jobs->push(new \App\Models\Job([
                    'id' => $payload->uuid ?? uniqid(),
                    'queue' => $queue,
                    'status' => 'pending',
                    'created_at' => $payload->time ?? now(),
                    'payload' => $job
                ]));
            }
        }

        return $jobs;
    }

    public static function detailQuery(NovaRequest $request, $query)
    {
        return static::indexQuery($request, $query);
    }
} 