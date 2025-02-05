<?php

namespace App\Http\Controllers;

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
    public function generateCareerReport($accountId, $careerTitle): BinaryFileResponse|JsonResponse
    {
        $exitCode = Artisan::call('app:generate-career-report', [
            'accountId' => $accountId,
            'careerTitle' => $careerTitle
        ]);

        if ($exitCode !== 0) {
            return response()->json(['error' => 'Failed to generate career report'], 500);
        }

        $filePath = "public/reports/career_report_{$accountId}.pdf";

        if (!Storage::exists($filePath)) {
            return response()->json(['error' => 'Report not found'], 404);
        }

        return Response::download(storage_path("app/{$filePath}"));
    }

    /**
     * Get a JSON file with the specified name.
     *
     * @param string $name
     * @return JsonResponse
     */
    public function getJson(string $name): JsonResponse
    {
        $fileName = "{$name}.json";
        $filePath = "json/{$fileName}";

        if (!Storage::exists($filePath)) {
            return response()->json([
                'error' => 'Template not found'
            ], 404);
        }

        $content = Storage::get($filePath);
        $data = json_decode($content, true);

        return response()->json([
            'name' => $name,
            'data' => $data
        ]);
    }
}
