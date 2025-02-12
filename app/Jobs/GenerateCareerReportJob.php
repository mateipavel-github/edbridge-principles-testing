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

    public int $careerReportId;

    public function __construct(int $careerReportId)
    {
        $this->careerReportId = $careerReportId;
    }

    public function handle(): void
    {
        $careerReport = CareerReport::find($this->careerReportId);
        $student = $careerReport->student;

        DB::table('jobs')->where('id', $this->job->getJobId())->update(['status' => 'processing']);
        Log::info("JOB {$this->job->getJobId()} started: Account ID: {$student->principles_account_uid}, Career: {$careerReport->onet_soc_code}");

        try {
            Artisan::call('app:generate-career-report', [
                'accountId' => $student->principles_account_uid,
                'careerCode' => $careerReport->onet_soc_code
            ]);

            DB::table('jobs')->where('id', $this->job->getJobId())->update(['status' => 'completed']);
            Log::info("JOB {$this->job->getJobId()} completed");
        } catch (\Exception $e) {
            DB::table('jobs')->where('id', $this->job->getJobId())->update(['status' => 'failed']);
            Log::error("JOB {$this->job->getJobId()} failed: {$e->getMessage()}");
            throw $e;
        }

    }

}

