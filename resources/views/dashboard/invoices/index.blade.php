@extends('base.layout.app')

@section('content')

@section('top-btns')
    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#exportInvoicesModal">
        <i class="fa-solid fa-file-excel me-1"></i>
        {{ __('invoices.export_excel') }}
    </button>
@endsection

<div class="card">
    <div class="card-body">

        <div class="row g-4 mb-6">
            <div class="col-md-3">
                <input type="text" id="search_custom" class="form-control"
                    placeholder="{{ __('invoices.filters.search_placeholder') }}">
            </div>

            <div class="col-md-2">
                <select id="status" class="form-select">
                    <option value="">{{ __('invoices.filters.status_placeholder') }}</option>
                    <option value="unpaid">{{ __('invoices.status.unpaid') }}</option>
                    <option value="paid">{{ __('invoices.status.paid') }}</option>
                    <option value="cancelled">{{ __('invoices.status.cancelled') }}</option>
                    <option value="refunded">{{ __('invoices.status.refunded') }}</option>
                </select>
            </div>

            <div class="col-md-3">
                <input type="date" id="from" class="form-control" title="{{ __('invoices.filters.from') }}">
            </div>
            <div class="col-md-3">
                <input type="date" id="to" class="form-control" title="{{ __('invoices.filters.to') }}">
            </div>

            <div class="col-md-1">
                <button type="button" id="reset_filters" class="btn btn-light">
                    <i class="ki-duotone ki-arrows-circle fs-2">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table id="invoices_table" class="table table-row-bordered gy-5">
                <thead>
                    <tr class="fw-semibold fs-6 text-muted">
                        <th>#</th>
                        <th>{{ __('invoices.fields.number') }}</th>
                        <th>{{ __('invoices.fields.user') }}</th>
                        <th>{{ __('invoices.fields.invoiceable') }}</th>
                        <th>{{ __('invoices.fields.status') }}</th>
                        <th>{{ __('invoices.fields.subtotal') }}</th>
                        <th>{{ __('invoices.fields.discount') }}</th>
                        <th>{{ __('invoices.fields.total') }}</th>
                        <th>{{ __('invoices.fields.issued_at') }}</th>
                        <th>{{ __('invoices.fields.paid_at') }}</th>
                        <th class="text-end">{{ __('invoices.actions_title') }}</th>
                    </tr>
                </thead>
            </table>
        </div>

    </div>
</div>

{{-- ─── Modal ─────────────────────────────────────────────────────── --}}
<div class="modal fade" id="exportInvoicesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa-solid fa-file-excel text-success me-2"></i>
                    تصدير الفواتير - Excel
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <p class="text-muted fs-7 mb-4">الفلترة حسب تاريخ الإصدار</p>
                <div class="row g-4">
                    <div class="col-6">
                        <label class="form-label fw-bold">من تاريخ</label>
                        <input type="date" id="invExportFrom" class="form-control"
                            value="{{ now()->startOfMonth()->format('Y-m-d') }}">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-bold">إلى تاريخ</label>
                        <input type="date" id="invExportTo" class="form-control"
                            value="{{ now()->format('Y-m-d') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">الحالة <span
                                class="text-muted fw-normal">(اختياري)</span></label>
                        <select id="invExportStatus" class="form-select">
                            <option value="">الكل</option>
                            <option value="unpaid">غير مدفوعة</option>
                            <option value="paid">مدفوعة</option>
                            <option value="cancelled">ملغاة</option>
                            <option value="refunded">مستردة</option>
                        </select>
                    </div>
                </div>
                <div id="invExportError" class="alert alert-danger mt-3 d-none"></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-success" id="doExportInvoicesBtn">
                    <i class="fa-solid fa-download me-1"></i>
                    تصدير
                </button>
            </div>

        </div>
    </div>
</div>

@endsection

@push('custom-script')
<script>
    (function() {
        const table = $('#invoices_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('dashboard.invoices.datatable') }}",
                data: function(d) {
                    d.search_custom = $('#search_custom').val();
                    d.status = $('#status').val();
                    d.type = $('#type').val();
                    d.locked = $('#locked').val();
                    d.from = $('#from').val();
                    d.to = $('#to').val();
                }
            },
            order: [
                [0, 'desc']
            ],
            columns: [{
                    data: 'id',
                    name: 'id'
                },
                {
                    data: 'number',
                    name: 'number'
                },
                {
                    data: 'user_label',
                    name: 'user_id',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'invoiceable_label',
                    name: 'invoiceable_id',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'status_badge',
                    name: 'status',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'subtotal',
                    name: 'subtotal',
                    searchable: false
                },
                {
                    data: 'discount',
                    name: 'discount',
                    searchable: false
                },
                {
                    data: 'total',
                    name: 'total',
                    searchable: false
                },
                {
                    data: 'issued_at_label',
                    name: 'issued_at',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'paid_at_label',
                    name: 'paid_at',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false,
                    className: 'text-end'
                },
            ],
            drawCallback: function() {
                if (typeof KTMenu !== 'undefined') KTMenu.createInstances();
                $('[data-bs-toggle="tooltip"]').tooltip();
            }
        });

        $('#search_custom, #status, #type, #locked, #from, #to').on('keyup change', function() {
            table.ajax.reload();
        });

        $('#reset_filters').on('click', function() {
            $('#search_custom').val('');
            $('#status').val('');
            $('#type').val('');
            $('#locked').val('');
            $('#from').val('');
            $('#to').val('');
            table.ajax.reload();
        });

        document.getElementById('doExportInvoicesBtn').addEventListener('click', function() {
            const from = document.getElementById('invExportFrom').value;
            const to = document.getElementById('invExportTo').value;
            const status = document.getElementById('invExportStatus').value;
            const err = document.getElementById('invExportError');

            err.classList.add('d-none');

            if (!from || !to) {
                err.textContent = 'الرجاء تحديد التاريخين';
                err.classList.remove('d-none');
                return;
            }
            if (from > to) {
                err.textContent = 'تاريخ البداية يجب أن يكون قبل تاريخ النهاية';
                err.classList.remove('d-none');
                return;
            }

            let url = "{{ route('dashboard.invoices.export') }}" + `?from=${from}&to=${to}`;
            if (status) url += `&status=${status}`;

            window.location.href = url;
            setTimeout(() => bootstrap.Modal.getInstance(document.getElementById('exportInvoicesModal'))
                ?.hide(), 800);
        });

    })();
</script>
@endpush
