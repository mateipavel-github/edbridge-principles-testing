<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\CareerReport;

class GenerateCareerReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private CareerReport $report;

    public function __construct(CareerReport $report)
    {
        $this->report = $report;
    }

    public function handle(): void
    {
        $this->report->updateStatus('processing');
        Log::info("JOB {$this->job->getJobId()} started: Account ID: {$this->report->student->principles_account_uid}, Career: {$this->report->onet_soc_code}");

        try {
            Artisan::call('app:generate-career-report', [
                'reportId' => $this->report->id
            ]);

            $this->report->updateStatus('completed');
            Log::info("JOB {$this->job->getJobId()} completed");
        } catch (\Exception $e) {
            $this->report->updateStatus('failed');
            Log::error("JOB {$this->job->getJobId()} failed: {$e->getMessage()}");
            throw $e;
        }
    }

    public function failed(\Throwable $exception)
    {
        $this->report->updateStatus('failed');
        Log::error("JOB {$this->job->getJobId()} failed: {$exception->getMessage()}");
    }
}

