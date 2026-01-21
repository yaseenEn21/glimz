@extends('base.layout.app')

@section('content')

@section('top-btns')
    <div class="d-flex gap-2">
        @can('products.edit')
            <a href="{{ route('dashboard.products.edit', $product->id) }}" class="btn btn-primary">
                <i class="fa-solid fa-pen fs-5 me-2"></i>
                {{ __('products.edit') }}
            </a>
        @endcan
    </div>
@endsection

@php
    $locale = app()->getLocale();
    $img = $product->getImageUrlForLocale($locale) ?: asset('assets/media/svg/files/blank-image.svg');

    $catName = '—';
    if ($product->category) {
        $n = $product->category->name ?? [];
        $catName = $n[$locale] ?? (reset($n) ?: '—');
    }
@endphp

<div class="row g-7">
    {{-- العمود الرئيسي --}}
    <div class="col-xl-12">

        {{-- معلومات المنتج الأساسية --}}
        <div class="card shadow-sm mb-7">
            <div class="card-body p-10">
                <div class="d-flex gap-8 align-items-start">
                    {{-- الصورة --}}
                    <div class="flex-shrink-0">
                        <img src="{{ $img }}" class="rounded"
                            style="width: 200px; height: 200px; object-fit: cover;" alt="">
                    </div>

                    {{-- التفاصيل --}}
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center justify-content-between mb-5">
                            <h2 class="fw-bold text-gray-800 mb-0">{{ $product->getLocalizedName() }}</h2>
                            @if ($product->is_active)
                                <span class="badge badge-light-success fs-6">{{ __('products.active') }}</span>
                            @else
                                <span class="badge badge-light-danger fs-6">{{ __('products.inactive') }}</span>
                            @endif
                        </div>

                        <div class="mb-5">
                            <span class="text-muted">{{ __('products.fields.category') }}: </span>
                            <span class="fw-semibold text-gray-700">{{ $catName }}</span>
                        </div>

                        @if ($product->getLocalizedDescription())
                            <div class="text-gray-600 mb-5">
                                {{ $product->getLocalizedDescription() }}
                            </div>
                        @endif

                        <div class="d-flex gap-8 flex-wrap">
                            <div>
                                <div class="text-muted fs-7 mb-1">{{ __('products.fields.sort_order') }}</div>
                                <div class="fw-semibold">{{ $product->sort_order ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="text-muted fs-7 mb-1">{{ __('products.created_at') }}</div>
                                <div class="fw-semibold">{{ $product->created_at?->format('Y-m-d') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- العمود الجانبي --}}
    <div class="row">

        {{-- التسعير --}}
        <div class="col-md-6">
            <div class="card shadow-sm mb-7">
                <div class="card-body p-8">
                    <h3 class="fw-bold text-gray-800 mb-6">{{ __('products.pricing') }}</h3>

                    <div class="mb-6">
                        <div class="text-muted fs-7 mb-2">{{ __('products.fields.price') }}</div>
                        <div class="fw-bold fs-2 text-gray-800">{{ number_format((float) $product->price, 2) }} SAR
                        </div>
                    </div>

                    @if ($product->discounted_price)
                        <div class="separator my-6"></div>
                        <div class="mb-6">
                            <div class="text-muted fs-7 mb-2">{{ __('products.fields.discounted_price') }}</div>
                            <div class="fw-bold fs-3 text-success">
                                {{ number_format((float) $product->discounted_price, 2) }} SAR
                            </div>
                        </div>
                    @endif

                    @if ($product->max_qty_per_booking)
                        <div class="separator my-6"></div>
                        <div>
                            <div class="text-muted fs-7 mb-2">{{ __('products.fields.max_qty_per_booking') }}</div>
                            <div class="fw-bold fs-4 text-gray-800">{{ $product->max_qty_per_booking }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- الإحصائيات --}}
        <div class="col-md-6">
            <div class="card shadow-sm">
            <div class="card-body p-8">
                <h3 class="fw-bold text-gray-800 mb-6">{{ __('products.sales_stats') }}</h3>

                <div class="mb-6">
                    <div class="text-muted fs-7 mb-2">{{ __('products.total_sales_count') }}</div>
                    <div class="fw-bold fs-1 text-gray-800" id="js_total_qty">{{ $totalQty }}</div>
                </div>

                <div class="separator my-6"></div>

                <div class="mb-6">
                    <div class="text-muted fs-7 mb-2">{{ __('products.total_sales_amount') }}</div>
                    <div class="fw-bold fs-2 text-success" id="js_total_sales">
                        {{ number_format((float) $totalSales, 2) }} SAR
                    </div>
                </div>

                <div class="separator my-6"></div>

                <div>
                    <div class="text-muted fs-7 mb-2">{{ __('products.total_profit') }}</div>
                    <div class="fw-bold fs-2" id="js_total_profit">
                        @if ($profit === null)
                            <span class="text-muted">—</span>
                        @else
                            <span class="text-primary">{{ number_format((float) $profit, 2) }} SAR</span>
                        @endif
                    </div>
                    <div class="text-muted fs-8 mt-2 {{ $profit === null ? '' : 'd-none' }}" id="js_profit_note">
                        {{ __('products.profit_note_missing_cost') }}
                    </div>
                </div>
            </div>
        </div>
        </div>

        <div class="col-12">
            

        {{-- سجل المبيعات --}}
        <div class="card shadow-sm">
            <div class="card-header border-0 pt-6">
                <h3 class="card-title fw-bold">{{ __('products.sales_lines') }}</h3>
            </div>

            <div class="card-body pt-0">
                {{-- الفلاتر - نفس ستايل فلتر المنتجات --}}
                <div class="mb-5">
                    <div class="p-6">
                        <form id="sales_filter_form" class="d-flex flex-wrap align-items-center gap-4">
                            <div class="flex-grow-1" style="min-width: 200px;">
                                <input type="date" name="from" class="form-control form-control-solid"
                                    value="{{ $from }}" placeholder="{{ __('products.filters.from') }}">
                            </div>

                            <div class="flex-grow-1" style="min-width: 200px;">
                                <input type="date" name="to" class="form-control form-control-solid"
                                    value="{{ $to }}" placeholder="{{ __('products.filters.to') }}">
                            </div>

                            <div>
                                <button class="btn btn-primary" type="submit">
                                    {{ __('products.filters.apply') }}
                                </button>
                            </div>

                            <div>
                                <button type="button" id="reset_filters" class="btn btn-light-primary action-button">
                                    <i class="fa-solid fa-rotate-right p-0"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- الجدول --}}
                <div class="table-responsive">
                    <table id="sales_lines_table" class="table table-row-bordered table-hover gy-5">
                        <thead>
                            <tr class="fw-semibold fs-6 text-muted">
                                <th>#</th>
                                <th>{{ __('products.booking_id') }}</th>
                                <th>{{ __('products.qty') }}</th>
                                <th>{{ __('products.unit_price') }}</th>
                                <th>{{ __('products.line_total') }}</th>
                                <th>{{ __('products.created_at') }}</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 fw-semibold"></tbody>
                    </table>
                </div>
            </div>
        </div>
        </div>
        
    </div>

</div>

@endsection

@push('custom-script')
<script>
    (function() {
        const $from = $('input[name="from"]');
        const $to = $('input[name="to"]');

        const locale = '{{ app()->getLocale() }}';
        const dtLangUrl = locale === 'ar' ?
            'https://cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json' :
            'https://cdn.datatables.net/plug-ins/1.13.6/i18n/en-GB.json';

        // Datatable
        const dt = $('#sales_lines_table').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            lengthChange: true,
            pageLength: 10,
            order: [
                [0, 'desc']
            ],
            language: {
                url: dtLangUrl
            },
            ajax: {
                url: "{{ route('dashboard.products.salesLines', $product->id) }}",
                data: function(d) {
                    d.from = $from.val();
                    d.to = $to.val();
                }
            },
            columns: [{
                    data: 'id',
                    name: 'id'
                },
                {
                    data: 'booking_id',
                    name: 'booking_id'
                },
                {
                    data: 'qty',
                    name: 'qty'
                },
                {
                    data: 'unit_price_snapshot',
                    name: 'unit_price_snapshot'
                },
                {
                    data: 'line_total',
                    name: 'line_total'
                },
                {
                    data: 'created_at',
                    name: 'created_at'
                },
            ],
        });

        function formatMoney(v) {
            if (v === null || v === undefined) return '—';
            const n = Number(v);
            if (Number.isNaN(n)) return '—';
            return n.toFixed(2) + ' SAR';
        }

        function loadStats() {
            $.get("{{ route('dashboard.products.salesStats', $product->id) }}", {
                from: $from.val(),
                to: $to.val()
            }).done(function(res) {
                $('#js_total_qty').text(res.total_qty ?? 0);
                $('#js_total_sales').html('<span class="text-success">' + formatMoney(res.total_sales) +
                    '</span>');

                if (res.profit === null || res.profit === undefined) {
                    $('#js_total_profit').html('<span class="text-muted">—</span>');
                    $('#js_profit_note').removeClass('d-none');
                } else {
                    $('#js_total_profit').html('<span class="text-primary">' + formatMoney(res.profit) +
                        '</span>');
                    $('#js_profit_note').addClass('d-none');
                }
            });
        }

        // فلتر
        $('#sales_filter_form').on('submit', function(e) {
            e.preventDefault();
            dt.ajax.reload();
            loadStats();
        });

        // إعادة تعيين
        $('#reset_filters').on('click', function() {
            $from.val('');
            $to.val('');
            dt.ajax.reload();
            loadStats();
        });

        // تحميل الإحصائيات أول مرة
        loadStats();
    })();
</script>
@endpush
