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
            \Log::error('❌ Invalid Moyasar webhook token');
            return response()->json(['success' => false, 'message' => 'Invalid webhook token'], 403);
        }

        $type = $request->input('type');
        $data = $request->input('data', []);

        if (!is_array($data)) {
            \Log::error('❌ Invalid webhook payload - data is not array');
            return response()->json(['success' => false, 'message' => 'Invalid payload'], 400);
        }

        $gatewayPaymentId = $data['id'] ?? null;
        $gatewayInvoiceId = $data['invoice_id'] ?? null;

        \Log::info('🔔 Moyasar Webhook Received', [
            'type' => $type,
            'gateway_payment_id' => $gatewayPaymentId,
            'gateway_invoice_id' => $gatewayInvoiceId,
            'amount' => $data['amount'] ?? null,
            'currency' => $data['currency'] ?? null,
            'status' => $data['status'] ?? null,
            'source_type' => $data['source']['type'] ?? null,
            'metadata' => $data['metadata'] ?? [],
        ]);

        // استخراج البيانات من metadata
        $localPaymentId = $data['metadata']['local_payment_id'] ?? null;
        $localInvoiceId = $data['metadata']['invoice_id'] ?? null;
        $bookingId = $data['metadata']['booking_id'] ?? null;

        // 🔥 جديد: دعم wallet_topup من الموبايل
        $purpose = $data['metadata']['purpose'] ?? $data['metadata']['type'] ?? null;
        $topupAmount = isset($data['metadata']['amount']) ? (float) $data['metadata']['amount'] : null;

        \Log::info('📋 Extracted Metadata', [
            'local_payment_id' => $localPaymentId,
            'local_invoice_id' => $localInvoiceId,
            'booking_id' => $bookingId,
            'purpose' => $purpose,
            'topup_amount' => $topupAmount,
        ]);

        /** @var Payment|null $payment */
        $payment = null;

        // محاولة 1: البحث بـ local_payment_id
        if ($localPaymentId) {
            \Log::info('🔍 Searching payment by local_payment_id', ['id' => $localPaymentId]);
            $payment = Payment::query()
                ->where('id', (int) $localPaymentId)
                ->where('gateway', 'moyasar')
                ->first();

            if ($payment) {
                \Log::info('✅ Payment found by local_payment_id', ['payment_id' => $payment->id]);
            } else {
                \Log::info('⚠️ Payment NOT found by local_payment_id');
            }
        }

        // محاولة 2: البحث بـ gateway_invoice_id
        if (!$payment && $gatewayInvoiceId) {
            \Log::info('🔍 Searching payment by gateway_invoice_id', ['gateway_invoice_id' => $gatewayInvoiceId]);
            $payment = Payment::query()
                ->where('gateway', 'moyasar')
                ->where('gateway_invoice_id', $gatewayInvoiceId)
                ->first();

            if ($payment) {
                \Log::info('✅ Payment found by gateway_invoice_id', ['payment_id' => $payment->id]);
            } else {
                \Log::info('⚠️ Payment NOT found by gateway_invoice_id');
            }
        }

        // محاولة 3: البحث بـ gateway_payment_id
        if (!$payment && $gatewayPaymentId) {
            \Log::info('🔍 Searching payment by gateway_payment_id', ['gateway_payment_id' => $gatewayPaymentId]);
            $payment = Payment::query()
                ->where('gateway', 'moyasar')
                ->where('gateway_payment_id', $gatewayPaymentId)
                ->first();

            if ($payment) {
                \Log::info('✅ Payment found by gateway_payment_id', ['payment_id' => $payment->id]);
            } else {
                \Log::info('⚠️ Payment NOT found by gateway_payment_id');
            }
        }

        // محاولة 4: البحث بـ pending payment بنفس المبلغ (للويب)
        if (!$payment && $type === 'payment_paid' && $gatewayInvoiceId) {
            $amountPaid = ((int) ($data['amount'] ?? 0)) / 100;

            \Log::info('🔍 Searching pending payment by amount and null gateway_invoice_id', [
                'amount' => $amountPaid,
                'gateway_invoice_id' => $gatewayInvoiceId,
            ]);

            $payment = Payment::query()
                ->where('gateway', 'moyasar')
                ->where('status', 'pending')
                ->where('amount', $amountPaid)
                ->whereNull('gateway_invoice_id')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($payment) {
                \Log::info('✅ Payment found by amount match (updating gateway IDs)', [
                    'payment_id' => $payment->id,
                    'updating_gateway_invoice_id' => $gatewayInvoiceId,
                    'updating_gateway_payment_id' => $gatewayPaymentId,
                ]);

                $payment->update([
                    'gateway_invoice_id' => $gatewayInvoiceId,
                    'gateway_payment_id' => $gatewayPaymentId,
                ]);
            } else {
                \Log::info('⚠️ Payment NOT found by amount match');
            }
        }

        // 🔥 محاولة 5: إنشاء payment جديد
        if (!$payment && $type === 'payment_paid') {

            $sourceType = strtolower($data['source']['type'] ?? '');
            $amountPaid = ((int) ($data['amount'] ?? 0)) / 100;

            // 🔥 حالة 1: Apple/Google Pay لحجز (invoice_id موجود)
            if (in_array($sourceType, ['applepay', 'googlepay']) && $localInvoiceId) {

                \Log::info('🆕 Creating payment for booking (Apple/Google Pay)');

                $invoice = Invoice::find((int) $localInvoiceId);

                if (!$invoice) {
                    \Log::error('❌ Invoice not found', ['invoice_id' => $localInvoiceId]);
                    return response()->json(['success' => true, 'message' => 'Invoice not found'], 200);
                }

                \Log::info('✅ Invoice found', [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'invoice_status' => $invoice->status,
                    'invoice_total' => $invoice->total,
                    'user_id' => $invoice->user_id,
                    'purpose' => $invoice->meta['purpose'] ?? null,
                ]);

                $paymentMethod = $sourceType === 'applepay' ? 'apple_pay' : 'google_pay';

                try {
                    $payment = Payment::create([
                        'user_id' => $invoice->user_id,
                        'invoice_id' => $invoice->id,
                        'payable_type' => $invoice->meta['purpose'] ?? 'invoice_payment',
                        'payable_id' => $bookingId ?? $invoice->id,
                        'amount' => $amountPaid,
                        'currency' => $data['currency'] ?? 'SAR',
                        'method' => $paymentMethod,
                        'status' => 'pending',
                        'gateway' => 'moyasar',
                        'gateway_payment_id' => $gatewayPaymentId,
                        'gateway_invoice_id' => $gatewayInvoiceId,
                        'gateway_status' => $data['status'] ?? 'paid',
                        'gateway_raw' => $data,
                        'meta' => [
                            'auto_created_from_webhook' => true,
                            'source_type' => $sourceType,
                            'webhook_received_at' => now()->toIso8601String(),
                        ],
                        'created_by' => $invoice->user_id,
                        'updated_by' => $invoice->user_id,
                    ]);

                    \Log::info('✅ Payment created successfully (booking)', [
                        'payment_id' => $payment->id,
                        'payment_status' => $payment->status,
                        'payment_amount' => $payment->amount,
                    ]);

                } catch (\Exception $e) {
                    \Log::error('❌ Failed to create payment', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    return response()->json(['success' => false, 'message' => 'Payment creation failed'], 500);
                }
            }

            // 🔥 حالة 2: Apple/Google Pay لشحن محفظة (purpose = wallet_topup)
            elseif (in_array($sourceType, ['applepay', 'googlepay']) && $purpose === 'wallet_topup') {

                \Log::info('🆕 Creating payment for wallet topup (Apple/Google Pay)');

                // نحتاج user_id من metadata
                $userId = $data['metadata']['user_id'] ?? null;

                if (!$userId) {
                    \Log::error('❌ Cannot create wallet topup payment: user_id missing');
                    return response()->json(['success' => true, 'message' => 'User ID missing'], 200);
                }

                $paymentMethod = $sourceType === 'applepay' ? 'apple_pay' : 'google_pay';

                try {
                    $payment = Payment::create([
                        'user_id' => (int) $userId,
                        'invoice_id' => null,
                        'payable_type' => 'wallet_topup',
                        'payable_id' => null,
                        'amount' => $amountPaid,
                        'currency' => $data['currency'] ?? 'SAR',
                        'method' => $paymentMethod,
                        'status' => 'pending',
                        'gateway' => 'moyasar',
                        'gateway_payment_id' => $gatewayPaymentId,
                        'gateway_invoice_id' => $gatewayInvoiceId,
                        'gateway_status' => $data['status'] ?? 'paid',
                        'gateway_raw' => $data,
                        'meta' => [
                            'auto_created_from_webhook' => true,
                            'source_type' => $sourceType,
                            'purpose' => 'wallet_topup',
                            'webhook_received_at' => now()->toIso8601String(),
                        ],
                        'created_by' => (int) $userId,
                        'updated_by' => (int) $userId,
                    ]);

                    \Log::info('✅ Payment created successfully (wallet topup)', [
                        'payment_id' => $payment->id,
                        'payment_status' => $payment->status,
                        'payment_amount' => $payment->amount,
                        'user_id' => $userId,
                    ]);

                } catch (\Exception $e) {
                    \Log::error('❌ Failed to create wallet topup payment', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    return response()->json(['success' => false, 'message' => 'Payment creation failed'], 500);
                }
            }
        }

        if (!$payment) {
            \Log::warning('⚠️ No payment found or created - ignoring webhook', [
                'gateway_payment_id' => $gatewayPaymentId,
                'gateway_invoice_id' => $gatewayInvoiceId,
                'purpose' => $purpose,
            ]);
            return response()->json(['success' => true, 'message' => 'Ignored'], 200);
        }

        \Log::info('📌 Payment to process', [
            'payment_id' => $payment->id,
            'current_status' => $payment->status,
            'amount' => $payment->amount,
            'invoice_id' => $payment->invoice_id,
            'payable_type' => $payment->payable_type,
        ]);

        // منع التكرار
        if (in_array($payment->status, ['paid', 'refunded', 'cancelled'], true)) {
            \Log::info('⏭️ Payment already processed - skipping', [
                'payment_id' => $payment->id,
                'status' => $payment->status,
            ]);
            return response()->json(['success' => true, 'message' => 'Already processed'], 200);
        }

        if ($type === 'payment_paid') {
            \Log::info('💰 Processing payment_paid event');

            try {
                DB::beginTransaction();
                \Log::info('🔄 Transaction started');

                $amountPaid = ((int) ($data['amount'] ?? 0)) / 100;

                \Log::info('💵 Amount comparison', [
                    'amount_from_webhook' => $amountPaid,
                    'amount_in_payment' => $payment->amount,
                    'match' => $amountPaid == $payment->amount,
                ]);

                if ($amountPaid == $payment->amount) {

                    \Log::info('✅ Amount matches - updating payment to paid');

                    $payment->update([
                        'status' => 'paid',
                        'gateway_payment_id' => $gatewayPaymentId,
                        'gateway_status' => $data['status'] ?? 'paid',
                        'paid_at' => now(),
                        'gateway_raw' => $data,
                    ]);

                    \Log::info('✅ Payment updated to paid', ['payment_id' => $payment->id]);

                    if ($payment->invoice_id) {

                        \Log::info('📄 Processing invoice', ['invoice_id' => $payment->invoice_id]);

                        $invoice = Invoice::query()
                            ->where('id', $payment->invoice_id)
                            ->lockForUpdate()
                            ->first();

                        if (!$invoice) {
                            \Log::error('❌ Invoice not found for update', ['invoice_id' => $payment->invoice_id]);
                            DB::rollBack();
                            \Log::error('🔙 Transaction rolled back - invoice not found');
                            return response()->json(['success' => false, 'message' => 'Invoice not found'], 500);
                        }

                        \Log::info('📄 Invoice loaded', [
                            'invoice_id' => $invoice->id,
                            'invoice_status' => $invoice->status,
                            'invoice_total' => $invoice->total,
                        ]);

                        if ($invoice->status !== 'paid') {

                            $paidAmount = (float) Payment::query()
                                ->where('invoice_id', $invoice->id)
                                ->where('status', 'paid')
                                ->sum('amount');

                            $remaining = max(0, (float) $invoice->total - $paidAmount);

                            \Log::info('💰 Invoice payment calculation', [
                                'invoice_total' => $invoice->total,
                                'paid_amount' => $paidAmount,
                                'remaining' => $remaining,
                            ]);

                            if ($remaining <= 0.0) {
                                \Log::info('✅ Invoice fully paid - updating status');

                                $invoice->update([
                                    'status' => 'paid',
                                    'paid_at' => now(),
                                    'is_locked' => true,
                                    'updated_by' => $payment->user_id,
                                ]);

                                \Log::info('✅ Invoice updated to paid');

                                $purpose = data_get($invoice->meta, 'purpose');
                                \Log::info('🎯 Invoice purpose', ['purpose' => $purpose]);

                                if ($purpose === 'package_purchase') {
                                    \Log::info('📦 Fulfilling package purchase');
                                    try {
                                        $svc->fulfillPaidInvoice($invoice->fresh(), $payment->user_id);
                                        \Log::info('✅ Package purchase fulfilled');
                                    } catch (\Exception $e) {
                                        \Log::error('❌ Package fulfillment failed', [
                                            'error' => $e->getMessage(),
                                            'trace' => $e->getTraceAsString(),
                                        ]);
                                        DB::rollBack();
                                        \Log::error('🔙 Transaction rolled back - package fulfillment failed');
                                        return response()->json(['success' => false, 'message' => 'Fulfillment failed'], 500);
                                    }
                                }

                                if ($purpose === 'booking_invoice') {
                                    \Log::info('📅 Fulfilling booking');
                                    try {
                                        app(\App\Services\BookingFulfillmentService::class)
                                            ->fulfillPaidInvoice($invoice->fresh(), $payment->user_id);
                                        \Log::info('✅ Booking fulfilled');
                                    } catch (\Exception $e) {
                                        \Log::error('❌ Booking fulfillment failed', [
                                            'error' => $e->getMessage(),
                                            'trace' => $e->getTraceAsString(),
                                        ]);
                                        DB::rollBack();
                                        \Log::error('🔙 Transaction rolled back - booking fulfillment failed');
                                        return response()->json(['success' => false, 'message' => 'Fulfillment failed'], 500);
                                    }
                                }
                            } else {
                                \Log::info('⚠️ Invoice not fully paid yet', ['remaining' => $remaining]);
                            }
                        } else {
                            \Log::info('⏭️ Invoice already paid - skipping');
                        }
                    } else {
                        \Log::info('ℹ️ Payment has no invoice_id');
                    }

                    // 🔥 شحن محفظة
                    if ($payment->payable_type === 'wallet_topup') {
                        \Log::info('💳 Processing wallet topup');

                        $wallet = Wallet::query()->where('user_id', $payment->user_id)->lockForUpdate()->first();
                        if (!$wallet) {
                            \Log::info('🆕 Creating new wallet for user', ['user_id' => $payment->user_id]);
                            $wallet = Wallet::create([
                                'user_id' => $payment->user_id,
                                'balance' => 0,
                            ]);
                            $wallet->refresh();
                        }

                        $before = (float) $wallet->balance;
                        $after = $before + (float) $amountPaid;

                        \Log::info('💰 Updating wallet balance', [
                            'user_id' => $payment->user_id,
                            'before' => $before,
                            'amount' => $amountPaid,
                            'after' => $after,
                        ]);

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
                                'source_type' => $data['source']['type'] ?? null,
                            ],
                            'payment_id' => $payment->id,
                            'created_by' => $payment->user_id,
                            'updated_by' => $payment->user_id,
                        ]);

                        \Log::info('✅ Wallet transaction created', [
                            'wallet_id' => $wallet->id,
                            'new_balance' => $after,
                        ]);
                    }

                } else {
                    \Log::warning('⚠️ Amount mismatch - marking payment as failed', [
                        'expected' => $payment->amount,
                        'received' => $amountPaid,
                    ]);

                    $payment->update([
                        'status' => 'failed',
                        'gateway_payment_id' => $gatewayPaymentId,
                        'gateway_status' => $data['status'] ?? 'failed',
                        'gateway_raw' => $data,
                    ]);
                }

                DB::commit();
                \Log::info('✅ Transaction committed successfully');

                $payment->refresh();
                $invoice = $payment->invoice_id ? Invoice::find($payment->invoice_id) : null;

                \Log::info('🔍 POST-COMMIT VERIFICATION', [
                    'payment_id' => $payment->id,
                    'payment_status' => $payment->status,
                    'payment_paid_at' => $payment->paid_at,
                    'invoice_id' => $invoice?->id,
                    'invoice_status' => $invoice?->status,
                    'invoice_paid_at' => $invoice?->paid_at,
                    'payable_type' => $payment->payable_type,
                ]);

                if ($payment->status !== 'paid') {
                    \Log::critical('🚨 CRITICAL: Payment status not updated after commit!', [
                        'payment_id' => $payment->id,
                        'expected' => 'paid',
                        'actual' => $payment->status,
                    ]);
                }

                if ($invoice && $invoice->status !== 'paid') {
                    \Log::critical('🚨 CRITICAL: Invoice status not updated after commit!', [
                        'invoice_id' => $invoice->id,
                        'expected' => 'paid',
                        'actual' => $invoice->status,
                    ]);
                }

                return response()->json(['success' => true], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('❌ Transaction rolled back due to exception', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return response()->json(['success' => false, 'message' => 'Processing failed'], 500);
            }
        }

        if (in_array($type, ['payment_failed', 'payment_voided', 'payment_canceled'], true)) {
            \Log::info('❌ Processing failed/voided/canceled payment');

            $payment->update([
                'status' => 'failed',
                'gateway_payment_id' => $gatewayPaymentId,
                'gateway_status' => $data['status'] ?? 'failed',
                'gateway_raw' => $data,
            ]);

            \Log::info('✅ Payment marked as failed');
            return response()->json(['success' => true], 200);
        }

        \Log::info('⚠️ Unhandled event type', ['type' => $type]);
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