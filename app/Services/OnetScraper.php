<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class OnetScraper
{
    /**
     * Scrape the local trends page.
     *
     * @param string $onetSocCode
     * @return array
     * @throws \Exception
     */
    public function scrapeLocalTrends(string $onetSocCode): array
    {
        $url = "https://www.onetonline.org/link/localtrends/{$onetSocCode}";
        $response = Http::withHeaders([
            'User-Agent' => 'MyLaravelScraper/1.0'
        ])->timeout(30)->get($url);

        if (!$response->ok()) {
            throw new \Exception("Unable to fetch local trends page for {$onetSocCode}");
        }

        $crawler = new Crawler($response->body());

        // We expect four <dd> elements inside the <dl class="row"> containing the data.
        $dds = $crawler->filter('dl.row dd');
        if ($dds->count() < 4) {
            throw new \Exception("Insufficient data in local trends page for {$onetSocCode}");
        }

        // Extract texts from the <dd> elements.
        $employmentText = $dds->eq(0)->text();               // e.g. "313,900 employees"
        $projectedEmploymentText = $dds->eq(1)->text();        // e.g. "331,100 employees"
        $projectedGrowthText = $dds->eq(2)->text();            // e.g. contains "6%"
        $projectedAnnualOpeningsText = $dds->eq(3)->text();    // e.g. "23,000"

        // Clean up the texts and convert them into numbers.
        $employment = intval(str_replace(',', '', preg_replace('/[^0-9]/', '', $employmentText)));
        $projectedEmployment = intval(str_replace(',', '', preg_replace('/[^0-9]/', '', $projectedEmploymentText)));

        // Use regex to capture the percentage number from the projected growth text.
        preg_match('/(\d+)%/', $projectedGrowthText, $matches);
        $projectedGrowth = isset($matches[1]) ? floatval($matches[1]) : 0;

        $projectedAnnualOpenings = intval(str_replace(',', '', preg_replace('/[^0-9]/', '', $projectedAnnualOpeningsText)));

        return [
            'employment' => $employment,
            'projected_employment' => $projectedEmployment,
            'projected_growth' => $projectedGrowth,
            'projected_annual_openings' => $projectedAnnualOpenings,
        ];
    }

    /**
     * Scrape the summary page.
     *
     * @param string $onetSocCode
     * @return array
     * @throws \Exception
     */
    public function scrapeSummary(string $onetSocCode): array
    {
        $url = "https://www.onetonline.org/link/summary/{$onetSocCode}";
        $response = Http::withHeaders([
            'User-Agent' => 'MyLaravelScraper/1.0'
        ])->timeout(30)->get($url);

        if (!$response->ok()) {
            throw new \Exception("Unable to fetch summary page for {$onetSocCode}");
        }

        $crawler = new Crawler($response->body());

        // Locate the <dt> that contains "Median wages" and get its first following <dd>
        $wageText = $crawler->filterXPath('//dt[contains(., "Median wages")]/following-sibling::dd[1]')->text();
        // Example wageText: "$99.37 hourly, $206,680 annual"

        // Use regex to extract the numbers
        preg_match('/\$(\d+(?:\.\d+)?)\s*hourly/', $wageText, $matchHourly);
        preg_match('/\$(\d[\d,]*)\s*annual/', $wageText, $matchAnnual);

        $median_hourly_wage = isset($matchHourly[1]) ? floatval($matchHourly[1]) : null;
        $median_annual_wage = isset($matchAnnual[1]) ? floatval(str_replace(',', '', $matchAnnual[1])) : null;

        return [
            'median_hourly_wage' => $median_hourly_wage,
            'median_annual_wage' => $median_annual_wage,
        ];
    }
}
