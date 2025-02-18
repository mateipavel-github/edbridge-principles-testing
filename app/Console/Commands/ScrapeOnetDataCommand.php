<?php

namespace App\Console\Commands;

use App\Jobs\ScrapeOnetJob;
use App\Models\OccupationData;
use Illuminate\Console\Command;

class ScrapeOnetDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:scrape-onet-data-command {onetCode?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Iterate through onet__occupation_data and dispatch scraping jobs for each onetsoc_code';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $onetCode = $this->argument('onetCode');

        if ($onetCode) {
            $this->info("Dispatching job for onet code: {$onetCode}");
            ScrapeOnetJob::dispatch($onetCode);
        } else {
            // Use chunking to handle large datasets without memory issues
            OccupationData::chunk(100, function ($occupations) {
                foreach ($occupations as $occupation) {
                    $this->info("Dispatching job for onetsoc_code: {$occupation->onetsoc_code}");
                    ScrapeOnetJob::dispatch($occupation->onetsoc_code);
                }
            });
        }

        $this->info("All scraping jobs have been dispatched.");
    }
}
