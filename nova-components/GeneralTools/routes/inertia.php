<?php

use Illuminate\Support\Facades\Route;
use Laravel\Nova\Http\Requests\NovaRequest;
use Illuminate\Support\Facades\Redis;

/*
|--------------------------------------------------------------------------
| Tool Routes
|--------------------------------------------------------------------------
|
| Here is where you may register Inertia routes for your tool. These are
| loaded by the ServiceProvider of the tool. The routes are protected
| by your tool's "Authorize" middleware by default. Now - go build!
|
*/

Route::get('/', function (NovaRequest $request) {
    return inertia('GeneralTools');
});

Route::get('/jobs', function (NovaRequest $request) {
    $jobs = collect();
    $queues = ['default', 'high', 'low'];

    foreach ($queues as $queue) {
        $pendingJobs = Redis::lrange("queues:{$queue}", 0, -1);
        foreach ($pendingJobs as $job) {
            $payload = json_decode($job);
            $jobs->push([
                'id' => $payload->uuid ?? uniqid(),
                'queue' => $queue,
                'status' => 'pending',
                'created_at' => $payload->time ?? now(),
                'payload' => $job
            ]);
        }
    }

    return inertia('QueueJobs', [
        'jobs' => $jobs
    ]);
});
