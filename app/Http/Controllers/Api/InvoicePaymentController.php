<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\InvoicePaymentRequest;
use App\Http\Resources\Api\PaymentResource;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\MoyasarService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;

class InvoicePaymentController extends Controller
{
    /**
     * POST /api/v1/invoices/{invoice}/payments
     * body: { "method": "wallet|credit_card|apple_pay|google_pay" }
     */
    public function store(InvoicePaymentRequest $request, $invoiceId, WalletService $walletService)
    {
        $user = $request->user();
        if (!$user)
            return api_error('Unauthenticated', 401);

        $method = $request->input('method');

        $payment = DB::transaction(function () use ($user, $method, $invoiceId, $walletService) {

            $invoice = Invoice::query()
                ->where('id', $invoiceId)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (!$invoice) {
                return api_error('Invoice not found', 404);
            }

            if (!in_array($invoice->status, ['unpaid'], true)) {
                return api_error('Invoice is not payable', 409);
            }

            // احسب المتبقي
            $paidAmount = (float) Payment::query()
                ->where('invoice_id', $invoice->id)
                ->where('status', 'paid')
                ->sum('amount');

            $remaining = max(0, (float) $invoice->total - $paidAmount);

            if ($remaining <= 0.0) {
                return api_error('Invoice already paid', 409);
            }

            // ✅ إنشاء Payment
            $p = Payment::create([
                'user_id' => $user->id,
                'invoice_id' => $invoice->id,

                'amount' => $remaining,
                'currency' => $invoice->currency,

                'method' => $method,
                'status' => $method === 'wallet' ? 'paid' : 'pending',
                'paid_at' => $method === 'wallet' ? now() : null,

                'payable_type' => $invoice->resolvePayableType(),
                'payable_id' => $invoice->resolvePayableId(),

                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            \Log::info('💳 Payment created', [
                'payment_id' => $p->id,
                'invoice_id' => $invoice->id,
                'amount' => $remaining,
                'method' => $method,
            ]);

            // ✅ لو Wallet → خصم + إقفال الفاتورة
            if ($method === 'wallet') {
                $walletService->debit(
                    $user,
                    $remaining,
                    'booking_charge',
                    ['ar' => 'سداد فاتورة', 'en' => 'Invoice payment'],
                    $invoice->invoiceable,
                    $p->id,
                    $user->id,
                    ['invoice_id' => $invoice->id, 'invoice_number' => $invoice->number]
                );

                $invoice->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'is_locked' => true,
                    'updated_by' => $user->id,
                ]);

                if (data_get($invoice->meta, 'purpose') === 'package_purchase') {
                    app(\App\Services\PackagePurchaseService::class)
                        ->fulfillPaidInvoice($invoice->fresh(), $user->id);
                }

                if (data_get($invoice->meta, 'purpose') === 'booking_invoice') {
                    app(\App\Services\BookingFulfillmentService::class)
                        ->fulfillPaidInvoice($invoice->fresh(), $user->id);
                }

            } else {
                // بوابة الدفع (Moyasar)
                $p->update([
                    'gateway' => 'moyasar',
                    'meta' => array_merge($p->meta ?? [], [
                        'purpose' => data_get($invoice->meta, 'purpose'),
                        'invoice_number' => $invoice->number,
                    ]),
                ]);

                \Log::info('🔗 Creating Moyasar invoice', [
                    'payment_id' => $p->id,
                    'amount' => $remaining,
                ]);

                try {
                    $amountHalala = (int) round($remaining * 100);

                    $moyasarInvoice = app(MoyasarService::class)->createInvoice([
                        'amount' => $amountHalala,
                        'currency' => $invoice->currency,
                        'description' => "Invoice {$invoice->number}",
                        'callback_url' => config('services.moyasar.callback_url'),
                        'success_url' => config('services.moyasar.success_url'),
                        'back_url' => config('services.moyasar.back_url'),
                        'metadata' => [
                            'local_payment_id' => (string) $p->id,
                            'invoice_id' => (string) $invoice->id,
                            'user_id' => (string) $user->id,
                            'purpose' => data_get($invoice->meta, 'purpose'),
                        ],
                    ]);

                    \Log::info('✅ Moyasar invoice created', [
                        'payment_id' => $p->id,
                        'moyasar_invoice_id' => $moyasarInvoice['id'] ?? 'N/A',
                        'moyasar_url' => $moyasarInvoice['url'] ?? 'N/A',
                        'moyasar_status' => $moyasarInvoice['status'] ?? 'N/A',
                        'full_response' => $moyasarInvoice,
                    ]);

                    $updateData = [
                        'gateway_invoice_id' => $moyasarInvoice['id'] ?? null,
                        'gateway_transaction_url' => $moyasarInvoice['url'] ?? null,
                        'gateway_status' => $moyasarInvoice['status'] ?? null,
                        'gateway_raw' => $moyasarInvoice,
                    ];

                    \Log::info('📝 Updating payment with Moyasar data', [
                        'payment_id' => $p->id,
                        'update_data' => $updateData,
                    ]);

                    $p->update($updateData);

                    // تحقق من التحديث
                    $p->refresh();
                    \Log::info('🔍 Payment after update', [
                        'payment_id' => $p->id,
                        'gateway_invoice_id' => $p->gateway_invoice_id,
                        'gateway_transaction_url' => $p->gateway_transaction_url,
                    ]);

                    if (!$p->gateway_invoice_id) {
                        \Log::error('⚠️ WARNING: gateway_invoice_id is still null after update!', [
                            'payment_id' => $p->id,
                            'moyasar_response' => $moyasarInvoice,
                        ]);
                    }

                } catch (\Exception $e) {
                    \Log::error('❌ Moyasar invoice creation failed', [
                        'payment_id' => $p->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    throw $e;
                }
            }

            return $p->load(['invoice:id,number,type,status,total']);
        });

        if ($payment instanceof \Illuminate\Http\JsonResponse) {
            return $payment;
        }

        return api_success(new PaymentResource($payment), 'Payment created', 201);
    }
}
