<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAppTranslationsToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = (string) $request->header('X-Dev-Token', '');
        $expected = (string) env('APP_TRANSLATIONS_TOKEN', '');

        if ($expected === '') {
            return response()->json(['success' => false, 'message' => 'Server token not configured'], 500);
        }

        if ($token === '') {
            return response()->json(['success' => false, 'message' => 'Missing X-Dev-Token header'], 401);
        }

        if (!hash_equals($expected, $token)) {
            return response()->json(['success' => false, 'message' => 'Invalid token'], 403);
        }

        return $next($request);
    }
}