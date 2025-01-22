<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class DinosaurController extends Controller
{
    /**
     * Display the text "Cata is a dinosaur".
     *
     * @return Response
     */
    public function show(): Response
    {
        return response('Cata is a dinosaur');
    }
} 