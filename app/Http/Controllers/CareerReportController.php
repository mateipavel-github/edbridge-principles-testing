<?php

namespace App\Http\Controllers;

use App\Helpers\Onet;
use App\Jobs\GenerateCareerReportJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CareerReportController extends Controller
{
    /**
     * Store a JSON file with the provided prompts.
     */
    public function storeJson(Request $request): JsonResponse
    {
        $request->validate([
            'data' => 'required|array'
        ]);

        $filePath = "json/career_report_template.json";

        Storage::put($filePath, json_encode($request->input('data'), JSON_PRETTY_PRINT));

        return response()->json([
            'message' => 'File saved successfully',
            'path' => $filePath
        ]);
    }

    /**
     * Generate a career report PDF and provide it for download.
     */
    public function generateCareerReport($accountId, $careerTitle): JsonResponse
    {
        $socCode = Onet::getOnetSocCode($careerTitle);
        $filePath = storage_path("app/public/reports/career_report_{$accountId}_{$socCode}.pdf");

        // Remove the file if it exists
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Dispatch the Artisan command as a queued job
        $job = new GenerateCareerReportJob($accountId, $careerTitle);
        $jobId = Bus::dispatch($job);


        $careerTitleForURL = str_replace(' ', '_', $careerTitle);

        return response()->json([
            'message' => 'Career report generation has been queued.',
            'download_url' => url("/api/career-report/{$accountId}/{$careerTitleForURL}/download"),
            'job_id' => $jobId,
        ]);
    }

    /**
     * Allow users to download the generated PDF.
     */
    public function downloadCareerReport($accountId, $careerTitle): BinaryFileResponse|JsonResponse
    {
        $careerTitle = str_replace('_', ' ', $careerTitle);
        $socCode = Onet::getOnetSocCode($careerTitle);

        $filePath = "/public/reports/career_report_{$accountId}_{$socCode}.pdf";

        if (!Storage::exists($filePath)) {
            return response()->json(['error' => 'Report not ready yet. Try again later.'], 404);
        }

        return Response::download(storage_path("app/{$filePath}"));
    }

    /**
     * Get a JSON file with the specified name.
     *
     * @return JsonResponse
     */
    public function getJson(): JsonResponse
    {
        $filePath = "json/career_report_template.json";

        if (!Storage::exists($filePath)) {
            return response()->json([
                'error' => 'Template not found'
            ], 404);
        }

        $content = Storage::get($filePath);
        $data = json_decode($content, true);

        return response()->json([
            'data' => $data
        ]);
    }
}
