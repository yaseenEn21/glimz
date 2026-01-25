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
                $q->where('status', 'unpaid')
                ->orWhere('status', 'cancelled')
                ->orWhere('status', 'refunded');
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
}