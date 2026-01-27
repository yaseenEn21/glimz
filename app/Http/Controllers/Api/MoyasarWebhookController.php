<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\PackagePurchaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MoyasarWebhookController extends Controller
{
    public function handle(Request $request, PackagePurchaseService $svc)
    {
        $secret = config('services.moyasar.webhook_secret');

        if ($secret && $request->input('secret_token') !== $secret) {
            \Log::error('Invalid Moyasar webhook token');
            return response()->json(['success' => false, 'message' => 'Invalid webhook token'], 403);
        }

        $type = $request->input('type');
        $data = $request->input('data', []);

        if (!is_array($data)) {
            return response()->json(['success' => false, 'message' => 'Invalid payload'], 400);
        }

        $gatewayPaymentId = $data['id'] ?? null;
        $gatewayInvoiceId = $data['invoice_id'] ?? null;

        \Log::info('Moyasar webhook', [
            'type' => $type,
            'data' => $data,
            'gateway_payment_id' => $gatewayPaymentId,
            'gateway_invoice_id' => $gatewayInvoiceId,
        ]);

        // محاولة إيجاد Payment موجود
        $localPaymentId = $data['metadata']['local_payment_id'] ?? null;
        $localInvoiceId = $data['metadata']['invoice_id'] ?? null;
        $bookingId = $data['metadata']['booking_id'] ?? null;

        /** @var Payment|null $payment */
        $payment = null;

        if ($localPaymentId) {
            $payment = Payment::query()->where('id', (int) $localPaymentId)->where('gateway', 'moyasar')->first();
        }

        if (!$payment && $gatewayInvoiceId) {
            $payment = Payment::query()
                ->where('gateway', 'moyasar')
                ->where('gateway_invoice_id', $gatewayInvoiceId)
                ->first();
        }

        // 🔥 جديد: إذا ما لقينا payment ونوع الدفع paid، ننشئ واحد جديد
        if (!$payment && $type === 'payment_paid' && $localInvoiceId) {

            $invoice = Invoice::find((int) $localInvoiceId);

            if ($invoice) {
                // تحديد نوع الدفع من source
                $paymentMethod = 'credit_card'; // default
                $sourceType = strtolower($data['source']['type'] ?? '');

                if ($sourceType === 'applepay') {
                    $paymentMethod = 'apple_pay';
                } elseif ($sourceType === 'googlepay') {
                    $paymentMethod = 'google_pay';
                } elseif (in_array($sourceType, ['creditcard', 'credit_card'])) {
                    $paymentMethod = 'credit_card';
                }

                $amountPaid = ((int) ($data['amount'] ?? 0)) / 100;

                // إنشاء Payment جديد
                $payment = Payment::create([
                    'user_id' => $invoice->user_id,
                    'invoice_id' => $invoice->id,
                    'payable_type' => $invoice->meta['purpose'] ?? 'invoice_payment',
                    'payable_id' => $bookingId ?? $invoice->id,
                    'amount' => $amountPaid,
                    'currency' => $data['currency'] ?? 'SAR',
                    'method' => $paymentMethod,
                    'status' => 'pending', // سنحدثه لاحقاً
                    'gateway' => 'moyasar',
                    'gateway_payment_id' => $gatewayPaymentId,
                    'gateway_invoice_id' => $gatewayInvoiceId,
                    'gateway_status' => $data['status'] ?? 'paid',
                    'gateway_raw' => $data,
                    'meta' => [
                        'auto_created_from_webhook' => true,
                        'source_type' => $sourceType,
                    ],
                    'created_by' => $invoice->user_id,
                    'updated_by' => $invoice->user_id,
                ]);

                \Log::info('Payment auto-created from webhook', [
                    'payment_id' => $payment->id,
                    'invoice_id' => $invoice->id,
                    'gateway_payment_id' => $gatewayPaymentId,
                ]);
            } else {
                \Log::warning('Invoice not found for webhook', ['invoice_id' => $localInvoiceId]);
                return response()->json(['success' => true, 'message' => 'Invoice not found'], 200);
            }
        }

        if (!$payment) {
            // مش خطأ قاتل: ممكن webhook لحاجة مش من نظامنا
            return response()->json(['success' => true, 'message' => 'Ignored'], 200);
        }

        // منع التكرار
        if (in_array($payment->status, ['paid', 'refunded', 'cancelled'], true)) {
            return response()->json(['success' => true, 'message' => 'Already processed'], 200);
        }

        if ($type === 'payment_paid') {
            DB::transaction(function () use ($payment, $data, $gatewayPaymentId, $svc) {

                $amountPaid = ((int) ($data['amount'] ?? 0)) / 100;

                if ($amountPaid == $payment->amount) {

                    $payment->update([
                        'status' => 'paid',
                        'gateway_payment_id' => $gatewayPaymentId,
                        'gateway_status' => $data['status'] ?? 'paid',
                        'paid_at' => now(),
                        'gateway_raw' => $data,
                    ]);

                    if ($payment->invoice_id) {

                        $invoice = Invoice::query()
                            ->where('id', $payment->invoice_id)
                            ->lockForUpdate()
                            ->first();

                        if ($invoice && $invoice->status !== 'paid') {

                            $paidAmount = (float) Payment::query()
                                ->where('invoice_id', $invoice->id)
                                ->where('status', 'paid')
                                ->sum('amount');

                            $remaining = max(0, (float) $invoice->total - $paidAmount);

                            if ($remaining <= 0.0) {
                                $invoice->update([
                                    'status' => 'paid',
                                    'paid_at' => now(),
                                    'is_locked' => true,
                                    'updated_by' => $payment->user_id,
                                ]);

                                // تنفيذ الإجراءات بناءً على نوع الفاتورة
                                if (data_get($invoice->meta, 'purpose') === 'package_purchase') {
                                    $svc->fulfillPaidInvoice($invoice->fresh(), $payment->user_id);
                                }

                                if (data_get($invoice->meta, 'purpose') === 'booking_invoice') {
                                    app(\App\Services\BookingFulfillmentService::class)
                                        ->fulfillPaidInvoice($invoice->fresh(), $payment->user_id);
                                }
                            }
                        }
                    }

                    // شحن محفظة
                    if ($payment->payable_type === 'wallet_topup') {

                        $wallet = Wallet::query()->where('user_id', $payment->user_id)->lockForUpdate()->first();
                        if (!$wallet) {
                            $wallet = Wallet::create([
                                'user_id' => $payment->user_id,
                                'balance' => 0,
                            ]);
                            $wallet->refresh();
                        }

                        $before = (float) $wallet->balance;
                        $after = $before + (float) $amountPaid;

                        $wallet->update(['balance' => $after]);

                        WalletTransaction::create([
                            'wallet_id' => $wallet->id,
                            'user_id' => $payment->user_id,
                            'direction' => 'credit',
                            'type' => 'topup',
                            'amount' => $amountPaid,
                            'balance_before' => $before,
                            'balance_after' => $after,
                            'description' => [
                                'ar' => 'شحن محفظة',
                                'en' => 'Wallet topup',
                            ],
                            'meta' => [
                                'gateway' => 'moyasar',
                                'gateway_payment_id' => $gatewayPaymentId,
                                'gateway_invoice_id' => $payment->gateway_invoice_id,
                            ],
                            'payment_id' => $payment->id,
                            'created_by' => $payment->user_id,
                            'updated_by' => $payment->user_id,
                        ]);
                    }

                } else {
                    $payment->update([
                        'status' => 'failed',
                        'gateway_payment_id' => $gatewayPaymentId,
                        'gateway_status' => $data['status'] ?? 'failed',
                        'gateway_raw' => $data,
                    ]);
                }

            });

            return response()->json(['success' => true], 200);
        }

        if (in_array($type, ['payment_failed', 'payment_voided', 'payment_canceled'], true)) {
            $payment->update([
                'status' => 'failed',
                'gateway_payment_id' => $gatewayPaymentId,
                'gateway_status' => $data['status'] ?? 'failed',
                'gateway_raw' => $data,
            ]);

            return response()->json(['success' => true], 200);
        }

        return response()->json(['success' => true, 'message' => 'Unhandled event'], 200);
    }

    public function callback(Request $request)
    {
        return view('payment.callback');
    }

    public function success(Request $request)
    {
        return view('payment.success');
    }
}