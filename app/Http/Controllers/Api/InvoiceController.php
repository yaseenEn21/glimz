<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\InvoicesIndexRequest;
use App\Http\Resources\Api\InvoiceResource;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\Service;
use App\Models\Product;
use App\Models\Package;
use App\Models\Invoice;

class InvoiceController extends Controller
{
    /**
     * GET /api/v1/invoices
     */
    public function index(InvoicesIndexRequest $request)
    {
        $user = $request->user();
        if (!$user)
            return api_error('Unauthenticated', 401);

        $q = Invoice::query()
            ->with([
                'payments',
                'latestPaidPayment',
                'latestPayment',
                'items' => fn($q) => $q->orderBy('sort_order'),
                'items.itemable' => function (MorphTo $morphTo) {
                    $morphTo->morphWith([
                        Service::class => ['media'],
                        Product::class => ['media'],
                        Package::class => ['media'],
                    ]);
                },
            ])
            ->where('user_id', $user->id)
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->withCount('items')
            ->withSum([
                'payments as paid_amount' => function ($pq) {
                    $pq->where('status', 'paid');
                }
            ], 'amount');

        // فلتر الفترة الزمنية (كل التواريخ / آخر 30 يوم)
        if ($request->filled('period')) {
            $period = $request->input('period');

            if ($period === 'last_30_days') {
                $q->where('created_at', '>=', now()->subDays(30));
            }
            // 'all_dates' لا يحتاج فلتر
        }

        if ($request->filled('status')) {
            $status = $request->input('status');

            if ($status === 'completed') {
                $q->where('status', 'paid');
            } elseif ($status === 'not_completed') {
                $q->whereIn('status', ['unpaid', 'cancelled', 'refunded']);
            }
        }

        // فلتر النوع (إذا كان موجود)
        if ($request->filled('type')) {
            $q->where('type', $request->input('type'));
        }

        // البحث برقم الفاتورة
        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));
            $q->where('number', 'like', "%{$search}%");
        }

        $paginator = $q->paginate(50);

        $paginator->setCollection(
            $paginator->getCollection()->map(fn($inv) => new InvoiceResource($inv))
        );

        return api_paginated($paginator);
    }

    /**
     * GET /api/v1/invoices/{invoice}
     */
    public function show($invoiceId)
    {
        $user = request()->user();
        if (!$user)
            return api_error('Unauthenticated', 401);

        $invoice = Invoice::query()
            ->where('id', $invoiceId)
            ->where('user_id', $user->id)
            ->with([
                'items.itemable.media',
                'payments' => fn($q) => $q->orderByDesc('id'),
                'latestPaidPayment',
                'latestPayment',
            ])
            ->first();

        if (!$invoice) {
            return api_error('Not found', 404);
        }

        return api_success(new InvoiceResource($invoice));
    }

    /**
     * GET /api/v1/invoices/{invoice}/download
     */
    public function download($invoiceId)
    {
        $user = request()->user();
        if (!$user)
            return api_error('Unauthenticated', 401);

        $invoice = Invoice::query()
            ->where('id', $invoiceId)
            ->where('user_id', $user->id)
            ->with([
                'user',
                'items' => fn($q) => $q->orderBy('sort_order'),
                'items.itemable',
                'latestPaidPayment',
                'invoiceable', // Booking, etc.
            ])
            ->first();

        if (!$invoice) {
            return api_error('Not found', 404);
        }

        // اللغة من الهيدر Accept-Language أو fallback
        $locale = request()->header('Accept-Language', 'ar');
        $locale = in_array($locale, ['ar', 'en']) ? $locale : 'ar';

        $isRtl = $locale === 'ar';

        // بيانات الشركة (عدّلها حسب إعداداتك)
        $company = [
            'name' => $locale === 'ar' ? config('app.company_name_ar', 'جلمز') : config('app.company_name_en', 'Glimz'),
            'address' => $locale === 'ar' ? config('app.company_address_ar', 'الرياض، المملكة العربية السعودية') : config('app.company_address_en', 'Riyadh, Saudi Arabia'),
            'phone' => config('app.company_phone', '+966 XX XXX XXXX'),
            'email' => config('app.company_email', 'info@glimz.com'),
            'cr' => config('app.company_cr', ''),       // السجل التجاري
            'vat' => config('app.company_vat_number', ''), // الرقم الضريبي
            'logo' => public_path('images/logo.png'),
        ];

        // اكتب هذا:
        $pdf = \PDF::loadView('dashboard.invoices.pdf', [
            'invoice' => $invoice,
            'company' => $company,
            'locale' => $locale,
            'isRtl' => $isRtl,
        ], [], [
            'format' => 'A4',
            'orientation' => 'P',
            'autoLangToFont' => true,   // يدعم العربية تلقائيًا
            'autoScriptToLang' => true,
        ]);

        $filename = "invoice-{$invoice->number}.pdf";

        return $pdf->download($filename);
    }
}