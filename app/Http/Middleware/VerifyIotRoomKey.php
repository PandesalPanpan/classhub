<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyIotRoomKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key') ?? $request->query('api_key');
        $validApiKey = config('services.iot_secret_key');

        if (empty($validApiKey)) {
            return response()->json([
                'message' => 'API key not configured on server',
            ], 500);
        }

        if ($apiKey !== $validApiKey) {
            return response()->json([
                'message' => 'Invalid API key or missing API key',
            ], 401);
        }

        return $next($request);
    }
}
