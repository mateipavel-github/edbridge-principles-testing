<?php

namespace App\Http\Controllers;

use App\Helpers\Onet;
use App\Jobs\GenerateCareerReportJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
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
            'name' => 'required|string',
            'data' => 'required|array'
        ]);

        $fileName = $request->input('name') . '.json';
        $filePath = "json/{$fileName}";

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
        // Dispatch the Artisan command as a queued job
        dispatch(new GenerateCareerReportJob($accountId, $careerTitle));

        $socCode = Onet::getOnetSocCode($careerTitle);

        return response()->json([
            'message' => 'Career report generation has been queued.',
            'download_url' => url("/storage/reports/career_report_{$accountId}_{$socCode}.pdf")
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
}
