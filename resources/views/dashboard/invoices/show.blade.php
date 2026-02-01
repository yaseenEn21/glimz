@extends('base.layout.app')

@section('content')

    @php
        $locale = app()->getLocale();

        $statusMap = [
            'unpaid' => 'danger',
            'paid' => 'success',
            'cancelled' => 'secondary',
            'refunded' => 'warning',
        ];
        $statusCls = $statusMap[$invoice->status] ?? 'secondary';

        $typeMap = [
            'invoice' => 'primary',
            'adjustment' => 'warning',
            'credit_note' => 'info',
        ];
        $typeCls = $typeMap[$invoice->type] ?? 'secondary';

        $currency = $invoice->currency ?: 'SAR';

        $gross = (float) $invoice->subtotal + (float) $invoice->tax;
        $discount = (float) $invoice->discount;
        $total = (float) $invoice->total;

        $meta = is_array($invoice->meta) ? $invoice->meta : (json_decode($invoice->meta ?? '[]', true) ?: []);
        $couponMeta = $meta['coupon'] ?? null;

        $invoiceableLabel = '—';
        if ($invoice->invoiceable_type && $invoice->invoiceable_id) {
            $invoiceableLabel = class_basename($invoice->invoiceable_type) . ' #' . (int) $invoice->invoiceable_id;
        }

        // helper: safe first value from array without reset() reference issue
        $firstOf = function ($arr) {
            return is_array($arr) ? collect($arr)->first() ?? null : null;
        };
    @endphp

    @if ($invoice->status === 'unpaid')
        {{-- @can('invoices.store') --}}
        <button type="button" class="btn btn-sm btn-primary" id="btn_manual_payment" data-invoice-id="{{ $invoice->id }}">
            <i class="ki-duotone ki-dollar fs-3 me-1">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
            {{ __('invoices.manual_payment.pay_button') }}
        </button>
        {{-- @endcan --}}
    @endif

    <div class="row g-6">

        {{-- LEFT --}}
        <div class="col-xl-8">

            {{-- HERO / HEADER --}}
            <div class="card card-flush mb-6">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-4">

                        <div class="d-flex align-items-center gap-4">
                            <div class="symbol symbol-50px">
                                <div class="symbol-label bg-light-primary">
                                    <i class="ki-duotone ki-bill fs-2 text-primary">
                                        <span class="path1"></span><span class="path2"></span>
                                    </i>
                                </div>
                            </div>

                            <div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <div class="fw-bold fs-2">{{ __('invoices.invoice') }}</div>
                                    <span
                                        class="badge badge-light-{{ $statusCls }}">{{ __('invoices.status.' . $invoice->status) }}</span>
                                </div>

                                <div class="d-flex align-items-center gap-2 mt-2">
                                    <span class="fw-bold fs-3 text-gray-800"
                                        id="invoice_number_text">{{ $invoice->number }}</span>
                                </div>

                                <div class="text-muted mt-2">
                                    {{ __('invoices.fields.id') }}: <span class="fw-semibold">#{{ $invoice->id }}</span>
                                    <span class="mx-2">•</span>
                                    {{ __('invoices.fields.version') }}: <span
                                        class="fw-semibold">{{ $invoice->version }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="text-end">
                            <div class="text-muted">{{ __('invoices.fields.issued_at') }}</div>
                            <div class="fw-semibold fs-6">{{ $invoice->issued_at?->format('Y-m-d H:i') ?? '—' }}</div>

                            <div class="text-muted mt-3">{{ __('invoices.fields.paid_at') }}</div>
                            <div class="fw-semibold fs-6">{{ $invoice->paid_at?->format('Y-m-d H:i') ?? '—' }}</div>
                        </div>
                    </div>

                    <div class="separator my-6"></div>

                    {{-- USER + LINKED ENTITY --}}
                    <div class="row g-6">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center gap-3">
                                <div class="symbol symbol-40px">
                                    <div class="symbol-label bg-light-info">
                                        <i class="ki-duotone ki-user fs-2 text-info">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="text-muted">{{ __('invoices.fields.user') }}</div>
                                    <div class="fw-bold">
                                        {{ $invoice->user?->name ?? '—' }}
                                        @if ($invoice->user?->mobile)
                                            <span class="text-muted fw-semibold">- {{ $invoice->user->mobile }}</span>
                                        @endif
                                    </div>
                                    @if ($invoice->user?->email)
                                        <div class="text-muted">{{ $invoice->user->email }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="d-flex align-items-center gap-3">
                                <div class="symbol symbol-40px">
                                    <div class="symbol-label bg-light-warning">
                                        <i class="ki-duotone ki-link fs-2 text-warning">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="text-muted">{{ __('invoices.fields.invoiceable') }}</div>
                                    <div class="fw-bold">{{ $invoiceableLabels }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ITEMS --}}
            <div class="card card-flush mb-6">
                <div class="card-header pt-5">
                    <h3 class="card-title fw-bold">{{ __('invoices.items_title') }}</h3>
                    <div class="card-toolbar text-muted">
                        {{ __('invoices.items_count') }}: <span class="fw-semibold">{{ $invoice->items->count() }}</span>
                    </div>
                </div>

                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>#</th>
                                    <th>{{ __('invoices.item.fields.type') }}</th>
                                    <th>{{ __('invoices.item.fields.title') }}</th>
                                    <th>{{ __('invoices.item.fields.qty') }}</th>
                                    <th>{{ __('invoices.item.fields.unit_price') }}</th>
                                    <th>{{ __('invoices.item.fields.line_tax') }}</th>
                                    <th class="text-end">{{ __('invoices.item.fields.line_total') }}</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-700 fw-semibold">
                                @forelse($invoice->items as $item)
                                    @php
                                        $title = $item->title[$locale] ?? ($firstOf($item->title) ?? null);

                                        // fallback from itemable
                                        if (!$title && $item->itemable) {
                                            if ($item->itemable_type === \App\Models\Service::class) {
                                                $title =
                                                    $item->itemable->name[$locale] ??
                                                    ($firstOf($item->itemable->name) ?? null);
                                            }
                                            if ($item->itemable_type === \App\Models\Product::class) {
                                                $title = method_exists($item->itemable, 'getLocalizedName')
                                                    ? $item->itemable->getLocalizedName()
                                                    : $item->itemable->name[$locale] ??
                                                        ($firstOf($item->itemable->name) ?? null);
                                            }
                                        }

                                        $desc = $item->description[$locale] ?? ($firstOf($item->description) ?? null);

                                        $typeBadgeMap = [
                                            'service' => 'info',
                                            'product' => 'primary',
                                            'fee' => 'warning',
                                            'custom' => 'secondary',
                                        ];
                                        $tb = $typeBadgeMap[$item->item_type] ?? 'secondary';
                                    @endphp

                                    <tr>
                                        <td>{{ $item->id }}</td>
                                        <td>
                                            <span class="badge badge-light-{{ $tb }}">
                                                {{ __('invoices.item.type.' . $item->item_type) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-bold">{{ $title ?: '—' }}</div>
                                            {{-- @if ($desc)
                                                <div class="text-muted fw-semibold">{{ $desc }}</div>
                                            @endif --}}
                                        </td>
                                        <td>{{ number_format((float) $item->qty, 2) }}</td>
                                        <td>{{ number_format((float) $item->unit_price, 2) }} {{ $currency }}</td>
                                        <td>{{ number_format((float) $item->line_tax, 2) }} {{ $currency }}</td>
                                        <td class="text-end fw-bold">{{ number_format((float) $item->line_total, 2) }}
                                            {{ $currency }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-6">—</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- PAYMENTS (قراءة فقط - لو موجودة) --}}
            <div class="card card-flush">
                <div class="card-header pt-5">
                    <h3 class="card-title fw-bold">{{ __('invoices.payments_title') }}</h3>
                </div>

                <div class="card-body pt-0">
                    @if ($invoice->relationLoaded('payments') && $invoice->payments && $invoice->payments->count())
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th>#</th>
                                        <th>{{ __('invoices.payment.fields.amount') }}</th>
                                        <th>{{ __('invoices.payment.fields.method') }}</th>
                                        <th>{{ __('invoices.payment.fields.status') }}</th>
                                        <th>{{ __('invoices.payment.fields.paid_at') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-700 fw-semibold">
                                    @foreach ($invoice->payments as $p)
                                        <tr>
                                            <td>{{ $p->id }}</td>
                                            <td class="fw-bold">{{ number_format((float) $p->amount, 2) }}
                                                {{ $currency }}</td>
                                            <td>{{ __('payment_methods.' . $p->method ?? '-') }}</td>
                                            <td>{{ __('payments.status.' . $p->status ?? '-') }}</td>
                                            <td>{{ $p->paid_at?->format('Y-m-d H:i') ?? ($p->created_at?->format('Y-m-d H:i') ?? '—') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-muted">—</div>
                    @endif
                </div>
            </div>

        </div>

        {{-- RIGHT --}}
        <div class="col-xl-4">

            {{-- TOTALS --}}
            <div class="card card-flush mb-6">
                <div class="card-header pt-5">
                    <h3 class="card-title fw-bold">{{ __('invoices.totals_title') }}</h3>
                </div>
                <div class="card-body pt-0">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="text-muted">{{ __('invoices.fields.subtotal') }}</div>
                        <div class="fw-bold">{{ number_format((float) $invoice->subtotal, 2) }} {{ $currency }}</div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="text-muted">{{ __('invoices.fields.tax') }}</div>
                        <div class="fw-bold">{{ number_format((float) $invoice->tax, 2) }} {{ $currency }}</div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="text-muted">{{ __('invoices.fields.gross_total') }}</div>
                        <div class="fw-bold">{{ number_format((float) $gross, 2) }} {{ $currency }}</div>
                    </div>

                    <div class="separator my-4"></div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="text-muted">{{ __('invoices.fields.discount') }}</div>
                        <div class="fw-bold text-danger">- {{ number_format((float) $discount, 2) }} {{ $currency }}
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-gray-800 fw-bold fs-5">{{ __('invoices.fields.total') }}</div>
                        <div class="fw-bolder fs-2">{{ number_format((float) $total, 2) }} {{ $currency }}</div>
                    </div>

                    @if ($invoice->status === 'unpaid')
                        <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-4 mt-6">
                            <i class="ki-duotone ki-information-5 fs-2 text-warning me-3">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                            <div class="d-flex flex-stack flex-grow-1">
                                <div class="fw-semibold">
                                    <div class="text-gray-800">{{ __('invoices.notice_unpaid_title') }}</div>
                                    <div class="text-muted fs-7">{{ __('invoices.notice_unpaid_text') }}</div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- COUPON INFO (من meta) --}}
            <div class="card card-flush mb-6">
                <div class="card-header pt-5">
                    <h3 class="card-title fw-bold">{{ __('invoices.coupon_title') }}</h3>
                </div>
                <div class="card-body pt-0">
                    @if (is_array($couponMeta))
                        <div class="mb-3">
                            <div class="text-muted fs-8">{{ __('invoices.coupon.code') }}</div>
                            <div class="fw-bold fs-5">{{ $couponMeta['code'] ?? '—' }}</div>
                        </div>

                        <div class="mb-3">
                            <div class="text-muted fs-8">{{ __('invoices.coupon.discount') }}</div>
                            <div class="fw-bold text-danger">-
                                {{ number_format((float) ($couponMeta['discount'] ?? 0), 2) }} {{ $currency }}</div>
                        </div>

                        <div class="mb-3">
                            <div class="text-muted fs-8">{{ __('invoices.coupon.eligible_base') }}</div>
                            <div class="fw-semibold">{{ number_format((float) ($couponMeta['eligible_base'] ?? 0), 2) }}
                                {{ $currency }}</div>
                        </div>

                        <div class="text-muted fs-7">
                            {{ __('invoices.coupon.applied_at') }}:
                            <span class="fw-semibold">{{ $couponMeta['applied_at'] ?? '—' }}</span>
                        </div>
                    @else
                        <div class="text-muted">—</div>
                    @endif
                </div>
            </div>

            {{-- RELATIONS --}}
            <div class="card card-flush mb-6">
                <div class="card-header pt-5">
                    <h3 class="card-title fw-bold">{{ __('invoices.relations_title') }}</h3>
                </div>
                <div class="card-body pt-0">

                    <div class="mb-4">
                        <div class="text-muted fs-8">{{ __('invoices.fields.parent_invoice') }}</div>
                        @if ($invoice->parent)
                            <a class="fw-bold" href="{{ route('dashboard.invoices.show', $invoice->parent->id) }}">
                                {{ $invoice->parent->number }} (#{{ $invoice->parent->id }})
                            </a>
                        @else
                            <div class="text-muted">—</div>
                        @endif
                    </div>

                    <div class="mb-2">
                        <div class="text-muted fs-8">{{ __('invoices.fields.child_invoices') }}</div>
                        @if ($invoice->children && $invoice->children->count())
                            <div class="d-flex flex-column gap-2 mt-2">
                                @foreach ($invoice->children as $child)
                                    <a class="d-flex align-items-center justify-content-between border rounded p-3"
                                        href="{{ route('dashboard.invoices.show', $child->id) }}">
                                        <span class="fw-bold">{{ $child->number }}</span>
                                        <span class="text-muted">#{{ $child->id }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <div class="text-muted">—</div>
                        @endif
                    </div>

                </div>
            </div>

            {{-- RAW META --}}
            <div class="card card-flush d-none">
                <div class="card-header pt-5">
                    <h3 class="card-title fw-bold">{{ __('invoices.meta_title') }}</h3>
                </div>
                <div class="card-body pt-0">
                    @if (!empty($meta))
                        <pre class="bg-light rounded p-4 mb-0" style="max-height: 260px; overflow:auto;">{{ json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    @else
                        <div class="text-muted">—</div>
                    @endif
                </div>
            </div>

        </div>
    </div>

    {{-- Manual Payment Modal --}}
    <div class="modal fade" id="manual_payment_modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">{{ __('invoices.manual_payment.modal_title') }}</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </div>
                </div>

                <form id="manual_payment_form" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body py-10 px-lg-17">

                        {{-- معلومات الفاتورة --}}
                        <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-6 mb-8">
                            <i class="ki-duotone ki-information fs-2tx text-primary me-4">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                            </i>
                            <div class="d-flex flex-stack flex-grow-1">
                                <div class="fw-semibold">
                                    <div class="fs-6 text-gray-700">
                                        {{ __('invoices.manual_payment.invoice_info') }}
                                    </div>
                                    <div class="fs-3 fw-bold text-gray-900 mt-2">
                                        <span id="modal_invoice_number">{{ $invoice->number }}</span>
                                        <span class="mx-2">-</span>
                                        <span
                                            id="modal_invoice_amount">{{ number_format((float) $invoice->total, 2) }}</span>
                                        <span id="modal_invoice_currency">{{ $invoice->currency }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- وسيلة الدفع --}}
                        <div class="fv-row mb-8">
                            <label class="required fs-6 fw-semibold mb-2">
                                {{ __('invoices.manual_payment.payment_method') }}
                            </label>
                            <select name="external_payment_method_id" id="external_payment_method_id" class="form-select"
                                data-control="select2"
                                data-placeholder="{{ __('invoices.manual_payment.select_method') }}"
                                data-dropdown-parent="#manual_payment_modal">
                                <option></option>
                            </select>
                            <div class="invalid-feedback"></div>

                            {{-- عرض تفاصيل البنك إن وجدت --}}
                            <div id="bank_details_container" class="d-none mt-4">
                                <div class="alert alert-info d-flex align-items-center p-5">
                                    <i class="ki-duotone ki-information-5 fs-2hx text-info me-4">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                    </i>
                                    <div class="d-flex flex-column">
                                        <h5 class="mb-1">{{ __('invoices.manual_payment.bank_details_title') }}</h5>
                                        <div id="bank_details_content" class="fs-7"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- رقم المرجع --}}
                        <div class="fv-row mb-8" id="reference_field" style="display: none;">
                            <label class="fs-6 fw-semibold mb-2">
                                {{ __('invoices.manual_payment.reference_number') }}
                            </label>
                            <input type="text" class="form-control" name="payment_reference"
                                placeholder="{{ __('invoices.manual_payment.reference_placeholder') }}">
                            <div class="form-text">{{ __('invoices.manual_payment.reference_hint') }}</div>
                            <div class="invalid-feedback"></div>
                        </div>

                        {{-- المرفق --}}
                        <div class="fv-row mb-8" id="attachment_field" style="display: none;">
                            <label class="fs-6 fw-semibold mb-2">
                                {{ __('invoices.manual_payment.attachment') }}
                            </label>
                            <input type="file" class="form-control" name="payment_attachment"
                                accept="image/*,application/pdf">
                            <div class="form-text">{{ __('invoices.manual_payment.attachment_hint') }}</div>
                            <div class="invalid-feedback"></div>
                        </div>

                        {{-- ملاحظات --}}
                        <div class="fv-row">
                            <label class="fs-6 fw-semibold mb-2">
                                {{ __('invoices.manual_payment.notes') }}
                            </label>
                            <textarea class="form-control" name="notes" rows="3"
                                placeholder="{{ __('invoices.manual_payment.notes_placeholder') }}"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>

                    </div>

                    <div class="modal-footer flex-center">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">
                            {{ __('invoices.manual_payment.cancel') }}
                        </button>
                        <button type="submit" class="btn btn-primary" id="submit_payment_btn">
                            <span class="indicator-label">
                                <i class="ki-duotone ki-check fs-2 me-1">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                {{ __('invoices.manual_payment.confirm') }}
                            </span>
                            <span class="indicator-progress">
                                {{ __('invoices.manual_payment.processing') }}
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('custom-script')
    <script>
        (function() {
            const isAr = document.documentElement.lang === 'ar';
            const $modal = $('#manual_payment_modal');
            const $form = $('#manual_payment_form');
            const $submitBtn = $('#submit_payment_btn');
            const $methodSelect = $('#external_payment_method_id');
            const $referenceField = $('#reference_field');
            const $attachmentField = $('#attachment_field');
            const $bankDetailsContainer = $('#bank_details_container');
            const $bankDetailsContent = $('#bank_details_content');

            let paymentMethods = [];
            let invoiceId = null;

            // Initialize Select2
            $methodSelect.select2({
                dropdownParent: $modal,
            });

            // زر فتح المودل
            $('#btn_manual_payment').on('click', function() {
                invoiceId = $(this).data('invoice-id');
                loadPaymentMethods(invoiceId);
            });

            // تحميل وسائل الدفع
            function loadPaymentMethods(invId) {
                $.ajax({
                    url: `/dashboard/invoices/${invId}/manual-payment`,
                    method: 'GET',
                    success: function(response) {
                        if (response.success) {
                            paymentMethods = response.data.payment_methods;

                            // تعبئة القائمة المنسدلة
                            $methodSelect.empty().append('<option></option>');
                            paymentMethods.forEach(method => {
                                const icon = method.icon ? `<i class="${method.icon} me-2"></i>` :
                                    '';
                                $methodSelect.append(
                                    `<option value="${method.id}" 
                                     data-requires-reference="${method.requires_reference}"
                                     data-requires-attachment="${method.requires_attachment}"
                                     data-bank-details='${JSON.stringify(method.bank_details || {})}'>
                                ${method.name}
                            </option>`
                                );
                            });

                            $modal.modal('show');
                        } else {
                            Swal.fire('Error', response.message || 'Failed to load payment methods',
                                'error');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Failed to load payment methods',
                            'error');
                    }
                });
            }

            // عند تغيير وسيلة الدفع
            $methodSelect.on('change', function() {
                const $selected = $(this).find('option:selected');
                const requiresReference = $selected.data('requires-reference');
                const requiresAttachment = $selected.data('requires-attachment');
                const bankDetails = $selected.data('bank-details');

                // إظهار/إخفاء الحقول المطلوبة
                if (requiresReference) {
                    $referenceField.show();
                } else {
                    $referenceField.hide();
                }

                if (requiresAttachment) {
                    $attachmentField.show();
                } else {
                    $attachmentField.hide();
                }

                // عرض تفاصيل البنك
                if (bankDetails && Object.keys(bankDetails).length > 0) {
                    let detailsHtml = '<div class="d-flex flex-column gap-2">';
                    for (const [key, value] of Object.entries(bankDetails)) {
                        if (value) {
                            detailsHtml += `<div><strong>${key}:</strong> ${value}</div>`;
                        }
                    }
                    detailsHtml += '</div>';
                    $bankDetailsContent.html(detailsHtml);
                    $bankDetailsContainer.removeClass('d-none');
                } else {
                    $bankDetailsContainer.addClass('d-none');
                }
            });

            // إرسال النموذج
            $form.on('submit', function(e) {
                e.preventDefault();

                if (!invoiceId) return;

                const formData = new FormData($form[0]);

                // تعطيل الزر
                $submitBtn.attr('disabled', true);
                $submitBtn.find('.indicator-label').hide();
                $submitBtn.find('.indicator-progress').show();

                // مسح الأخطاء السابقة
                $form.find('.is-invalid').removeClass('is-invalid');
                $form.find('.invalid-feedback').text('');

                $.ajax({
                    url: `/dashboard/invoices/${invoiceId}/manual-payment`,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: isAr ? 'تم الدفع بنجاح' : 'Payment Successful',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                // إعادة تحميل الصفحة
                                window.location.reload();
                            });
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422 && xhr.responseJSON?.errors) {
                            // عرض أخطاء التحقق
                            const errors = xhr.responseJSON.errors;
                            for (const field in errors) {
                                const $field = $form.find(`[name="${field}"]`);
                                $field.addClass('is-invalid');
                                $field.siblings('.invalid-feedback').text(errors[field][0]);
                            }
                        } else {
                            Swal.fire('Error', xhr.responseJSON?.message || 'Payment failed',
                                'error');
                        }
                    },
                    complete: function() {
                        // إعادة تفعيل الزر
                        $submitBtn.attr('disabled', false);
                        $submitBtn.find('.indicator-label').show();
                        $submitBtn.find('.indicator-progress').hide();
                    }
                });
            });

            // تنظيف النموذج عند إغلاق المودل
            $modal.on('hidden.bs.modal', function() {
                $form[0].reset();
                $methodSelect.val(null).trigger('change');
                $referenceField.hide();
                $attachmentField.hide();
                $bankDetailsContainer.addClass('d-none');
                $form.find('.is-invalid').removeClass('is-invalid');
                $form.find('.invalid-feedback').text('');
            });

            // Copy invoice number (الكود الموجود سابقاً)
            $('[data-bs-toggle="tooltip"]').tooltip();

            const btn = document.getElementById('copy_invoice_number');
            const txt = document.getElementById('invoice_number_text');

            if (btn && txt) {
                btn.addEventListener('click', async function() {
                    try {
                        await navigator.clipboard.writeText(txt.textContent.trim());
                        Swal.fire({
                            icon: 'success',
                            title: "{{ __('invoices.copied') }}",
                            timer: 1200,
                            showConfirmButton: false
                        });
                    } catch (e) {
                        const range = document.createRange();
                        range.selectNodeContents(txt);
                        const sel = window.getSelection();
                        sel.removeAllRanges();
                        sel.addRange(range);
                        document.execCommand('copy');
                        sel.removeAllRanges();
                    }
                });
            }
        })();
    </script>
@endpush
