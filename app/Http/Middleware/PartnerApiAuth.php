<?php

namespace App\Http\Middleware;

use App\Models\Partner;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PartnerApiAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // 1. استخراج Token من Header
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized - Token required',
                'error_code' => 'TOKEN_MISSING',
            ], 401);
        }

        // 2. البحث عن الشريك
        $partner = Partner::query()
            ->where('api_token', $token)
            ->first();

        if (!$partner) {
            Log::warning('Partner API: Invalid token', [
                'token' => substr($token, 0, 16) . '...',
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Unauthorized - Invalid token',
                'error_code' => 'INVALID_TOKEN',
            ], 401);
        }

        // 3. التحقق من تفعيل الشريك
        if (!$partner->is_active) {
            Log::warning('Partner API: Inactive partner attempted access', [
                'partner_id' => $partner->id,
                'partner_name' => $partner->name,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Partner account is inactive',
                'error_code' => 'PARTNER_INACTIVE',
            ], 403);
        }

        // 4. إرفاق الشريك في الـ Request
        $request->merge(['partner' => $partner]);
        $request->setUserResolver(fn() => $partner);

        Log::info('Partner API: Authenticated', [
            'partner_id' => $partner->id,
            'partner_name' => $partner->name,
            'endpoint' => $request->path(),
        ]);

        return $next($request);
    }
}