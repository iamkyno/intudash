<?php

namespace App\Http\Middleware;

use App\Models\Client;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiClient
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractToken($request);

        if (!$token) {
            return response()->json(['error' => 'Missing API token. Provide a Bearer token or X-Api-Key header.'], 401);
        }

        $client = Client::findByApiToken($token);

        if (!$client || $client->status !== 'active') {
            return response()->json(['error' => 'Invalid or inactive API token.'], 401);
        }

        // Make the authenticated client available to the controller.
        $request->attributes->set('api_client', $client);

        return $next($request);
    }

    private function extractToken(Request $request): ?string
    {
        if ($request->bearerToken()) {
            return $request->bearerToken();
        }

        $header = $request->header('X-Api-Key');
        return $header ?: null;
    }
}
