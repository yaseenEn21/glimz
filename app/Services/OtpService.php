<?php

namespace App\Services;

use App\Models\User;
use App\Models\OtpCode;
use Carbon\Carbon;

class OtpService
{
    protected SMsService $smsService;

    public function __construct(SMsService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function sendLoginOtp(User $user): OtpCode
    {
        if (app()->environment('development')) {
            $code = '1111';
        }else{
            $code = random_int(1000, 9999);
        }

        OtpCode::where('user_id', $user->id)
            ->where('type', 'login')
            ->delete();

        $otp = OtpCode::create([
            'user_id'    => $user->id,
            'mobile'     => $user->mobile,
            'code'       => (string) $code,
            'type'       => 'login',
            'expires_at' => Carbon::now()->addMinutes(1),
        ]);

        $message = "رمز التحقق للدخول إلى Glimz هو: {$code}";

        // Turn off SMS

        // $this->smsService->send(
        //     to: $user->mobile,
        //     message: $message,
        //     sender: 'GLIMZ'
        // );

        return $otp;
    }

    public function verifyLoginOtp(User $user, string $code): bool
    {
        $otp = OtpCode::where('user_id', $user->id)
            ->where('type', 'login')
            ->latest()
            ->first();

        if (! $otp) {
            return false;
        }

        $otp->increment('attempts');

        if ($otp->is_used || $otp->expires_at->isPast()) {
            return false;
        }

        if ($otp->code !== $code) {
            return false;
        }

        $otp->update(['is_used' => true]);

        return true;
    }
}