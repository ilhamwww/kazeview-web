<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiKey
{
    /**
     * Hardcoded API key untuk endpoint mutasi (store / edit / delete).
     * Pastikan key ini disimpan/dibagikan ke client melalui kanal yang aman.
     */
    private const API_KEY = 'kzv_8f3a91d2c4b745e6a0d12f8b6c9e3a17';

    /**
     * Nama header / query yang diterima.
     */
    private const HEADER_NAMES = ['X-API-KEY', 'X-Api-Key', 'Api-Key', 'X-API-Key'];
    private const QUERY_NAME   = 'api_key';

    public function handle(Request $request, Closure $next): Response
    {
        $provided = null;

        foreach (self::HEADER_NAMES as $header) {
            if ($request->headers->has($header)) {
                $provided = $request->header($header);
                break;
            }
        }

        if (! $provided) {
            $provided = $request->query(self::QUERY_NAME);
        }

        if (! is_string($provided) || ! hash_equals(self::API_KEY, $provided)) {
            return response()->json([
                'success' => false,
                'message' => 'API key tidak valid atau tidak dikirim.',
            ], 401);
        }

        return $next($request);
    }
}
