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

class GenerateCareerReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $accountId;
    public string $careerTitle;

    public function __construct(string $accountId, string $careerTitle)
    {
        $this->accountId = $accountId;
        $this->careerTitle = $careerTitle;
    }

    public function handle(): void
    {
        Log::info("Starting report generation for Account ID: {$this->accountId}, Career: {$this->careerTitle}");

        Artisan::call('app:generate-career-report', [
            'accountId' => $this->accountId,
            'careerTitle' => $this->careerTitle
        ]);


        Log::info("Report generation completed for Account ID: {$this->accountId}");
    }

}

