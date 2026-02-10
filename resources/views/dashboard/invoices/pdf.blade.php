@php
    // Helper to extract localized string
    $t = function ($value) use ($locale) {
        if (is_array($value)) {
            return $value[$locale] ?? $value['ar'] ?? $value['en'] ?? '—';
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded[$locale] ?? $decoded['ar'] ?? $decoded['en'] ?? '—';
            }
        }
        return (string) ($value ?? '—');
    };
@endphp

<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $isRtl ? 'فاتورة' : 'Invoice' }} #{{ $invoice->number }}</title>
    <style>
        /* ===== Base ===== */
        @php
            $fontFamily = $isRtl ? 'DejaVu Sans, sans-serif' : 'DejaVu Sans, sans-serif';
        @endphp

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: {!! $fontFamily !!};
            font-size: 12px;
            color: #333;
            direction: {{ $isRtl ? 'rtl' : 'ltr' }};
            line-height: 1.6;
            padding: 30px;
        }

        /* ===== Header ===== */
        .header {
            width: 100%;
            margin-bottom: 30px;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
        }

        .header table {
            width: 100%;
        }

        .header td {
            vertical-align: top;
        }

        .company-name {
            font-size: 22px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 5px;
        }

        .company-details {
            font-size: 10px;
            color: #666;
            line-height: 1.8;
        }

        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            color: #2563eb;
            text-align: {{ $isRtl ? 'left' : 'right' }};
            margin-bottom: 5px;
        }

        .invoice-number {
            font-size: 12px;
            color: #666;
            text-align: {{ $isRtl ? 'left' : 'right' }};
        }

        /* ===== Info Boxes ===== */
        .info-section {
            width: 100%;
            margin-bottom: 25px;
        }

        .info-section table {
            width: 100%;
        }

        .info-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 15px;
            vertical-align: top;
            width: 48%;
        }

        .info-box-title {
            font-size: 11px;
            font-weight: bold;
            color: #2563eb;
            text-transform: uppercase;
            margin-bottom: 8px;
            padding-bottom: 5px;
        }

        .info-row {
            margin-bottom: 3px;
        }

        .info-label {
            color: #888;
            font-size: 10px;
        }

        .info-value {
            font-weight: bold;
            font-size: 11px;
        }

        /* ===== Status Badge ===== */
        .status-badge {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
            color: #fff;
        }

        .status-paid { background: #16a34a; }
        .status-unpaid { background: #ea580c; }
        .status-cancelled { background: #dc2626; }
        .status-refunded { background: #7c3aed; }

        /* ===== Items Table ===== */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }

        .items-table thead th {
            background: #2563eb;
            color: #fff;
            padding: 10px 12px;
            font-size: 11px;
            text-align: {{ $isRtl ? 'right' : 'left' }};
            font-weight: bold;
        }

        .items-table thead th:first-child {
            border-radius: {{ $isRtl ? '0 6px 0 0' : '6px 0 0 0' }};
        }

        .items-table thead th:last-child {
            border-radius: {{ $isRtl ? '6px 0 0 0' : '0 6px 0 0' }};
            text-align: {{ $isRtl ? 'left' : 'right' }};
        }

        .items-table tbody td {
            padding: 10px 12px;
            font-size: 11px;
            vertical-align: middle;
        }

        .items-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .items-table tbody tr:last-child td {
        }

        .text-end {
            text-align: {{ $isRtl ? 'left' : 'right' }};
        }

        .text-center {
            text-align: center;
        }

        /* ===== Totals ===== */
        .totals-section {
            width: 100%;
            margin-bottom: 25px;
        }

        .totals-table {
            width: 280px;
            float: {{ $isRtl ? 'left' : 'right' }};
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 6px 12px;
            font-size: 11px;
        }

        .totals-table .label-col {
            color: #666;
            text-align: {{ $isRtl ? 'right' : 'left' }};
        }

        .totals-table .value-col {
            text-align: {{ $isRtl ? 'left' : 'right' }};
            font-weight: bold;
        }

        .totals-table .total-row td {
            font-size: 14px;
            font-weight: bold;
            color: #2563eb;
            border-top: 2px solid #2563eb;
            padding-top: 10px;
        }

        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }

        /* ===== Footer ===== */
        .footer {
            position: fixed;
            bottom: 20px;
            left: 30px;
            right: 30px;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            text-align: center;
            font-size: 9px;
            color: #999;
        }

        /* ===== Notes ===== */
        .notes-section {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 6px;
            padding: 12px 15px;
            margin-bottom: 20px;
            font-size: 10px;
            color: #92400e;
        }

        .notes-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>

    {{-- ===== Header ===== --}}
    <div class="header">
        <table>
            <tr>
                <td style="width: 55%;">
                    @if(file_exists($company['logo']))
                        <img src="{{ $company['logo'] }}" alt="Logo" style="height: 50px; margin-bottom: 8px;">
                        <br>
                    @endif
                    <div class="company-name">{{ $company['name'] }}</div>
                    <div class="company-details">
                        {{ $company['address'] }}<br>
                        {{ $company['phone'] }} | {{ $company['email'] }}
                        @if($company['cr'])
                            <br>{{ $isRtl ? 'سجل تجاري' : 'CR' }}: {{ $company['cr'] }}
                        @endif
                        @if($company['vat'])
                            | {{ $isRtl ? 'الرقم الضريبي' : 'VAT' }}: {{ $company['vat'] }}
                        @endif
                    </div>
                </td>
                <td style="width: 45%;">
                    <div class="invoice-title">
                        @if($invoice->type === 'credit_note')
                            {{ $isRtl ? 'إشعار دائن' : 'Credit Note' }}
                        @elseif($invoice->type === 'adjustment')
                            {{ $isRtl ? 'فاتورة تعديل' : 'Adjustment Invoice' }}
                        @else
                            {{ $isRtl ? 'فاتورة' : 'Invoice' }}
                        @endif
                    </div>
                    <div class="invoice-number">
                        #{{ $invoice->number }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- ===== Invoice & Customer Info ===== --}}
    <div class="info-section">
        <table>
            <tr>
                {{-- Invoice Details --}}
                <td class="info-box">
                    <div class="info-box-title">{{ $isRtl ? 'تفاصيل الفاتورة' : 'Invoice Details' }}</div>

                    <div class="info-row">
                        <span class="info-label">{{ $isRtl ? 'التاريخ' : 'Date' }}:</span>
                        <span class="info-value">{{ $invoice->issued_at?->format('Y-m-d') ?? $invoice->created_at?->format('Y-m-d') }}</span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">{{ $isRtl ? 'الحالة' : 'Status' }}:</span>
                        <span class="status-badge status-{{ $invoice->status }}">
                            @php
                                $statusLabels = [
                                    'paid'      => $isRtl ? 'مدفوعة'  : 'Paid',
                                    'unpaid'    => $isRtl ? 'غير مدفوعة' : 'Unpaid',
                                    'cancelled' => $isRtl ? 'ملغاة'   : 'Cancelled',
                                    'refunded'  => $isRtl ? 'مستردة'  : 'Refunded',
                                ];
                            @endphp
                            {{ $statusLabels[$invoice->status] ?? $invoice->status }}
                        </span>
                    </div>

                    @if($invoice->paid_at)
                        <div class="info-row">
                            <span class="info-label">{{ $isRtl ? 'تاريخ الدفع' : 'Paid At' }}:</span>
                            <span class="info-value">{{ $invoice->paid_at->format('Y-m-d H:i') }}</span>
                        </div>
                    @endif

                    @if($invoice->latestPaidPayment?->method)
                        <div class="info-row">
                            <span class="info-label">{{ $isRtl ? 'طريقة الدفع' : 'Payment Method' }}:</span>
                            <span class="info-value">{{ $invoice->latestPaidPayment->method }}</span>
                        </div>
                    @endif
                </td>

                <td style="width: 4%;"></td>

                {{-- Customer Details --}}
                <td class="info-box">
                    <div class="info-box-title">{{ $isRtl ? 'بيانات العميل' : 'Customer Details' }}</div>

                    <div class="info-row">
                        <span class="info-label">{{ $isRtl ? 'الاسم' : 'Name' }}:</span>
                        <span class="info-value">{{ $t($invoice->user?->name) }}</span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">{{ $isRtl ? 'الجوال' : 'Phone' }}:</span>
                        <span class="info-value">{{ $invoice->user?->phone ?? '—' }}</span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">{{ $isRtl ? 'البريد' : 'Email' }}:</span>
                        <span class="info-value">{{ $invoice->user?->email ?? '—' }}</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- ===== Booking Info (if applicable) ===== --}}
    @if($invoice->invoiceable_type === \App\Models\Booking::class && $invoice->invoiceable)
        @php $booking = $invoice->invoiceable; @endphp
        <div class="info-section">
            <table>
                <tr>
                    <td class="info-box" style="width: 100%;">
                        <div class="info-box-title">{{ $isRtl ? 'تفاصيل الحجز' : 'Booking Details' }}</div>
                        <table style="width: 100%;">
                            <tr>
                                <td style="width: 33%;">
                                    <span class="info-label">{{ $isRtl ? 'رقم الحجز' : 'Booking #' }}:</span>
                                    <span class="info-value">{{ $booking->id }}</span>
                                </td>
                                <td style="width: 33%;">
                                    <span class="info-label">{{ $isRtl ? 'التاريخ' : 'Date' }}:</span>
                                    <span class="info-value">{{ $booking->booking_date?->format('Y-m-d') ?? '—' }}</span>
                                </td>
                                <td style="width: 33%;">
                                    <span class="info-label">{{ $isRtl ? 'الوقت' : 'Time' }}:</span>
                                    <span class="info-value">{{ substr((string)$booking->start_time, 0, 5) }}</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
    @endif

    {{-- ===== Items Table ===== --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 45%;">{{ $isRtl ? 'البند' : 'Item' }}</th>
                <th class="text-center" style="width: 10%;">{{ $isRtl ? 'الكمية' : 'Qty' }}</th>
                <th class="text-end" style="width: 20%;">{{ $isRtl ? 'سعر الوحدة' : 'Unit Price' }}</th>
                <th class="text-end" style="width: 20%;">{{ $isRtl ? 'الإجمالي' : 'Total' }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoice->items->sortBy('sort_order') as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $t($item->title) }}</strong>
                        @if($item->description)
                            <br><span style="font-size: 9px; color: #888;">{{ $t($item->description) }}</span>
                        @endif
                        @if($item->item_type === 'service')
                            <br><span style="font-size: 9px; color: #2563eb;">{{ $isRtl ? 'خدمة' : 'Service' }}</span>
                        @elseif($item->item_type === 'product')
                            <br><span style="font-size: 9px; color: #16a34a;">{{ $isRtl ? 'منتج' : 'Product' }}</span>
                        @endif
                    </td>
                    <td class="text-center">{{ (int) $item->qty }}</td>
                    <td class="text-end">{{ number_format((float) $item->unit_price, 2) }}</td>
                    <td class="text-end">{{ number_format((float) $item->line_total, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center" style="padding: 20px; color: #999;">
                        {{ $isRtl ? 'لا توجد بنود' : 'No items' }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- ===== Totals ===== --}}
    <div class="totals-section clearfix">
        <table class="totals-table">
            <tr>
                <td class="label-col">{{ $isRtl ? 'المجموع الفرعي' : 'Subtotal' }}</td>
                <td class="value-col">{{ number_format((float) $invoice->subtotal, 2) }}</td>
            </tr>

            @if((float) $invoice->discount > 0)
                <tr>
                    <td class="label-col">{{ $isRtl ? 'الخصم' : 'Discount' }}</td>
                    <td class="value-col" style="color: #dc2626;">-{{ number_format((float) $invoice->discount, 2) }}</td>
                </tr>
            @endif

            @if((float) $invoice->tax > 0)
                <tr>
                    <td class="label-col">{{ $isRtl ? 'الضريبة' : 'Tax' }}</td>
                    <td class="value-col">{{ number_format((float) $invoice->tax, 2) }}</td>
                </tr>
            @endif

            <tr class="total-row">
                <td class="label-col">{{ $isRtl ? 'الإجمالي' : 'Total' }}</td>
                <td class="value-col">{{ number_format((float) $invoice->total, 2) }} {{ $invoice->currency }}</td>
            </tr>
        </table>
    </div>

    {{-- ===== Coupon (if exists) ===== --}}
    @php
        $coupon = data_get($invoice->meta, 'coupon');
    @endphp
    @if(is_array($coupon) && !empty($coupon['code'] ?? null))
        <div class="notes-section">
            <div class="notes-title">{{ $isRtl ? 'كوبون مطبّق' : 'Applied Coupon' }}</div>
            {{ $isRtl ? 'الكود' : 'Code' }}: <strong>{{ $coupon['code'] }}</strong>
            @if(!empty($coupon['discount']))
                &nbsp;|&nbsp; {{ $isRtl ? 'الخصم' : 'Discount' }}: {{ $coupon['discount'] }}
            @endif
        </div>
    @endif

    {{-- ===== Footer ===== --}}
    <div class="footer">
        {{ $company['name'] }} — {{ $company['address'] }}
        <br>
        {{ $isRtl ? 'هذه الفاتورة أُنشئت إلكترونيًا ولا تحتاج إلى توقيع' : 'This invoice is electronically generated and does not require a signature' }}
    </div>

</body>
</html>