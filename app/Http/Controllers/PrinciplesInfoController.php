<?php

namespace App\Http\Controllers;

use App\Services\PrinciplesService;
use App\Exceptions\PrinciplesApiException;
use Illuminate\Http\JsonResponse;

class PrinciplesInfoController extends Controller
{
    /**
     * Display the user information from the Principles API.
     *
     * @return JsonResponse
     */
    public function showInfo(): JsonResponse
    {
        $service = new PrinciplesService();

        try {
            $info = $service->info();
            return response()->json($info);
        } catch (PrinciplesApiException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
} 