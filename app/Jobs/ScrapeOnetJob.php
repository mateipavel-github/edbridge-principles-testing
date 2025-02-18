<?php

namespace App\Jobs;

use App\Models\ExtraData;
use App\Services\OnetScraper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScrapeOnetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $timeout = 120;

    protected string $onetSocCode;

    /**
     * Create a new job instance.
     *
     * @param string $onetSocCode
     */
    public function __construct(string $onetSocCode)
    {
        $this->onetSocCode = $onetSocCode;
    }

    /**
     * Execute the job.
     *
     * @param OnetScraper $scraper
     * @return void
     * @throws \Exception
     */
    public function handle(OnetScraper $scraper): void
    {
        // Scrape data from both pages
        $localData = $scraper->scrapeLocalTrends($this->onetSocCode);
        $summaryData = $scraper->scrapeSummary($this->onetSocCode);

        // Merge the scraped data
        $data = array_merge($localData, $summaryData);

        // Upsert the data into the extra_data table
        ExtraData::updateOrCreate(
            ['onet_soc_code' => $this->onetSocCode],
            $data
        );
    }

    /**
     * Handle a job failure.
     *
     * @param \Exception $exception
     */
    public function failed(\Exception $exception): void
    {
        Log::error("ScrapeOnetJob failed for {$this->onetSocCode}: " . $exception->getMessage());
    }
}
