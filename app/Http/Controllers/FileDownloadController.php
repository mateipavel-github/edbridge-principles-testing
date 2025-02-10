<?php

namespace App\Http\Controllers;

use App\Models\FileDownload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class FileDownloadController extends Controller
{
    public function downloadPdf($filename)
    {
        // Ensure the file exists
        if (!Storage::disk('public')->exists($filename)) {
            abort(404);
        }

        // Ensure it's a PDF file
        if (!str_ends_with(strtolower($filename), '.pdf')) {
            abort(400, 'Only PDF files can be downloaded');
        }

        // Get or create download record
        $downloadRecord = FileDownload::firstOrCreate(
            ['filename' => $filename],
            ['download_count' => 0]
        );

        // Check if this download was already counted in this session
        $sessionKey = 'downloaded_' . md5($filename);
        
        Log::info('Download attempt', [
            'filename' => $filename,
            'sessionKey' => $sessionKey,
            'hasSession' => Session::has($sessionKey),
            'currentCount' => $downloadRecord->download_count
        ]);

        if (!Session::has($sessionKey)) {
            // Increment download count
            $downloadRecord->increment('download_count');
            
            // Mark as downloaded in this session
            Session::put($sessionKey, true);
            Session::save(); // Force save the session

            Log::info('Download counted', [
                'filename' => $filename,
                'newCount' => $downloadRecord->fresh()->download_count
            ]);
        }

        // Get the file path
        $path = Storage::disk('public')->path($filename);

        $response = response()->download($path, $filename, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
            'Pragma' => 'no-cache',
        ]);

        return $response;
    }

    // Optional: Add a method to view download statistics
    public function getStats($filename)
    {
        $stats = FileDownload::where('filename', $filename)->first();
        return response()->json([
            'filename' => $filename,
            'downloads' => $stats ? $stats->download_count : 0
        ]);
    }
} 