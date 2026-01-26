<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;


class RekazWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // تسجيل البيانات للمتابعة
        Log::info('Rekaz Webhook Received', $request->all());

        // $event = $request->input('event'); // نوع الحدث
        // $data  = $request->input('data');  // بيانات الحجز

        // // مثال: حجز جديد
        // if ($event === 'booking.created') {
        //     // خزّن الحجز عندك
        // }

        // // مثال: تعديل حجز
        // if ($event === 'booking.updated') {
        //     // حدّث الحجز
        // }

        // // مثال: إلغاء حجز
        // if ($event === 'booking.cancelled') {
        //     // ألغِ الحجز
        // }

        return response()->json(['status' => 'ok']);
    }
}