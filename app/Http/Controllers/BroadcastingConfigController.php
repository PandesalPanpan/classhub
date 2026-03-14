<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class BroadcastingConfigController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $key = config('broadcasting.client.key');
        $host = config('broadcasting.client.host');
        $port = config('broadcasting.client.port');
        $scheme = config('broadcasting.client.scheme');

        if (empty($key) || empty($host)) {
            return response()->json(['enabled' => false]);
        }

        return response()->json([
            'enabled' => true,
            'key' => $key,
            'wsHost' => $host,
            'wsPort' => $scheme === 'https' ? 80 : $port,
            'wssPort' => $scheme === 'https' ? $port : 443,
            'forceTLS' => $scheme === 'https',
        ]);
    }
}
