<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;

class ClientIndexController extends Controller
{
    /**
     * Fetch unique client indices.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'dnis' => Client::select('dni')->distinct()->pluck('dni')->all(),
                'emails' => Client::select('email')->distinct()->pluck('email')->all(),
                'phone_numbers' => Client::select('phone_number')->distinct()->pluck('phone_number')->all(),
            ],
        ], 200);
    }
}
