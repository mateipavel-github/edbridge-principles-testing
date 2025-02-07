<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class RedisJobsController extends Controller
{
    public function index()
    {
        $redis = Redis::connection();
        
        // Get jobs from various Redis queues
        $queues = ['default', 'high', 'low']; // Add your queue names here
        $jobs = [];
        
        foreach ($queues as $queue) {
            $length = $redis->llen("queues:{$queue}");
            $jobs[$queue] = [
                'count' => $length,
                'jobs' => []
            ];
            
            // Get the actual jobs (up to 100 for performance)
            $limit = min($length, 100);
            for ($i = 0; $i < $limit; $i++) {
                $job = $redis->lindex("queues:{$queue}", $i);
                if ($job) {
                    $jobs[$queue]['jobs'][] = json_decode($job, true);
                }
            }
        }
        
        return response()->json($jobs);
    }
} 