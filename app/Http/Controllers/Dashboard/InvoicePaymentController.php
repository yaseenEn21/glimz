<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ManualPaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ExternalPaymentMethod;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\PackagePurchaseService;
use App\Services\BookingFulfillmentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InvoicePaymentController extends Controller
{

    public function __construct()
    {
        $this->middleware('can:invoices.pay_manually')->only(['showManualPaymentForm', 'processManualPayment']);
    }

    /**
     * عرض نموذج الدفع اليدوي
     */
    public function showManualPaymentForm(Invoice $invoice)
    {
        // التحقق من أن الفاتورة غير مدفوعة
        if ($invoice->status !== 'unpaid') {
            return response()->json([
                'success' => false,
                'message' => __('invoices.manual_payment.already_paid'),
            ], 400);
        }

        // جلب وسائل الدفع النشطة
        $paymentMethods = ExternalPaymentMethod::active()->get();

        return response()->json([
            'success' => true,
            'data' => [
                'invoice' => [
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                    'total' => $invoice->total,
                    'currency' => $invoice->currency,
                ],
                'payment_methods' => $paymentMethods->map(function ($method) {
                    return [
                        'id' => $method->id,
                        'name' => $method->getLocalizedName(),
                        'description' => $method->getLocalizedDescription(),
                        'code' => $method->code,
                        'icon' => $method->icon,
                        'requires_reference' => $method->requires_reference,
                        'requires_attachment' => $method->requires_attachment,
                        'bank_details' => $method->bank_details,
                    ];
                }),
            ],
        ]);
    }

    /**
     * معالجة الدفع اليدوي
     */
    public function processManualPayment(
        ManualPaymentRequest $request,
        Invoice $invoice,
        PackagePurchaseService $packageService,
        BookingFulfillmentService $bookingService
    ) {
        // التحقق من أن الفاتورة غير مدفوعة
        if ($invoice->status !== 'unpaid') {
            return response()->json([
                'success' => false,
                'message' => __('invoices.manual_payment.already_paid'),
            ], 400);
        }

        try {
            DB::beginTransaction();

            \Log::info('🔄 Manual payment started', [
                'invoice_id' => $invoice->id,
                'user_id' => auth()->id(),
                'amount' => $invoice->total,
            ]);

            // جلب وسيلة الدفع
            $paymentMethod = ExternalPaymentMethod::findOrFail($request->external_payment_method_id);

            // معالجة المرفق إن وجد
            $attachmentPath = null;
            if ($request->hasFile('payment_attachment')) {
                $attachmentPath = $request->file('payment_attachment')->store(
                    'invoices/manual-payments',
                    'public'
                );
            }

            // إنشاء سجل الدفع
            $payment = Payment::create([
                'user_id' => $invoice->user_id,
                'invoice_id' => $invoice->id,
                'payable_type' => $invoice->meta['purpose'] ?? 'invoice_payment',
                'payable_id' => $invoice->invoiceable_id ?? $invoice->id,
                'amount' => $invoice->total,
                'currency' => $invoice->currency,
                'method' => $paymentMethod->code, // bank_transfer, cash, etc.
                'status' => 'paid', // نعتبره مدفوع مباشرة
                'gateway' => 'manual', // للتمييز عن المدفوعات الإلكترونية
                'gateway_payment_id' => null,
                'gateway_invoice_id' => null,
                'gateway_status' => 'manual_payment',
                'gateway_raw' => null,
                'paid_at' => now(),
                'meta' => [
                    'payment_method_id' => $paymentMethod->id,
                    'payment_method_name' => $paymentMethod->getLocalizedName(),
                    'payment_reference' => $request->payment_reference,
                    'payment_attachment' => $attachmentPath,
                    'notes' => $request->notes,
                    'processed_by' => auth()->id(),
                    'processed_at' => now()->toIso8601String(),
                    'manual_payment' => true,
                ],
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            \Log::info('✅ Payment record created', [
                'payment_id' => $payment->id,
                'method' => $paymentMethod->code,
            ]);

            // تحديث حالة الفاتورة
            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
                'is_locked' => true,
                'updated_by' => auth()->id(),
            ]);

            \Log::info('✅ Invoice marked as paid', ['invoice_id' => $invoice->id]);

            // تنفيذ الإجراءات حسب نوع الفاتورة
            $purpose = data_get($invoice->meta, 'purpose');
            \Log::info('🎯 Invoice purpose', ['purpose' => $purpose]);

            if ($purpose === 'package_purchase') {
                \Log::info('📦 Fulfilling package purchase');
                try {
                    $packageService->fulfillPaidInvoice($invoice->fresh(), $invoice->user_id);
                    \Log::info('✅ Package purchase fulfilled');
                } catch (\Exception $e) {
                    \Log::error('❌ Package fulfillment failed', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => __('invoices.manual_payment.fulfillment_failed'),
                    ], 500);
                }
            }

            if ($purpose === 'booking_invoice') {
                \Log::info('📅 Fulfilling booking');
                try {
                    $bookingService->fulfillPaidInvoice($invoice->fresh(), $invoice->user_id);
                    \Log::info('✅ Booking fulfilled');
                } catch (\Exception $e) {
                    \Log::error('❌ Booking fulfillment failed', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => __('invoices.manual_payment.fulfillment_failed'),
                    ], 500);
                }
            }

            // إذا كان شحن محفظة
            if ($purpose === 'wallet_topup') {
                \Log::info('💳 Processing wallet topup');

                $wallet = Wallet::query()
                    ->where('user_id', $invoice->user_id)
                    ->lockForUpdate()
                    ->first();

                if (!$wallet) {
                    \Log::info('🆕 Creating new wallet for user', ['user_id' => $invoice->user_id]);
                    $wallet = Wallet::create([
                        'user_id' => $invoice->user_id,
                        'balance' => 0,
                    ]);
                    $wallet->refresh();
                }

                $before = (float) $wallet->balance;
                $after = $before + (float) $invoice->total;

                \Log::info('💰 Updating wallet balance', [
                    'user_id' => $invoice->user_id,
                    'before' => $before,
                    'amount' => $invoice->total,
                    'after' => $after,
                ]);

                $wallet->update(['balance' => $after]);

                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'user_id' => $invoice->user_id,
                    'direction' => 'credit',
                    'type' => 'topup',
                    'amount' => $invoice->total,
                    'balance_before' => $before,
                    'balance_after' => $after,
                    'description' => [
                        'ar' => 'شحن محفظة (دفع يدوي)',
                        'en' => 'Wallet topup (manual payment)',
                    ],
                    'meta' => [
                        'gateway' => 'manual',
                        'payment_method' => $paymentMethod->code,
                        'payment_method_name' => $paymentMethod->getLocalizedName(),
                    ],
                    'payment_id' => $payment->id,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);

                \Log::info('✅ Wallet transaction created', [
                    'wallet_id' => $wallet->id,
                    'new_balance' => $after,
                ]);
            }

            DB::commit();
            \Log::info('✅ Manual payment transaction committed successfully');

            return response()->json([
                'success' => true,
                'message' => __('invoices.manual_payment.success'),
                'data' => [
                    'payment_id' => $payment->id,
                    'invoice_id' => $invoice->id,
                    'invoice_status' => $invoice->status,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('❌ Manual payment failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('invoices.manual_payment.error'),
            ], 500);
        }
    }
}