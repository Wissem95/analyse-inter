<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;

class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        $response = $next($request);

        if ($response instanceof JsonResponse) {
            return $response;
        }

        if ($response->headers->get('Content-Type') === 'application/json') {
            return $response;
        }

        $content = $response->getContent();

        if (empty($content)) {
            return response()->json(null);
        }

        $json = json_decode($content);
        if (json_last_error() === JSON_ERROR_NONE) {
            return response()->json($json);
        }

        return response()->json(['data' => $content]);
    }
}
