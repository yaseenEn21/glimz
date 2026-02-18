@extends('base.layout.app')

@section('title', __('customers.title'))

@section('content')

@section('top-btns')
    @can('customers.create')
        {{-- <a href="{{ route('dashboard.customers.create') }}" class="btn btn-primary">
            {{ __('customers.create') }}
        </a> --}}
    @endcan
    @can('customers.view')
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#exportCustomersModal">
            <i class="fa-solid fa-file-excel me-1"></i>
            {{ __('customers.export_excel') }}
        </button>
    @endcan
@endsection

{{-- Filters (مثل bookings) --}}
<div class="card mb-5">
    <div class="card-body">
        <div class="row g-4">

            {{-- Search --}}
            <div class="col-lg-4">
                <input type="text" id="search_custom" class="form-control"
                    placeholder="{{ __('customers.filters.search_placeholder') }}">
            </div>

            {{-- Status --}}
            <div class="col-lg-2">
                <select id="filter_is_active" class="form-select">
                    <option value="">{{ __('customers.filters.all_status') }}</option>
                    <option value="1">{{ __('customers.active') }}</option>
                    <option value="0">{{ __('customers.inactive') }}</option>
                </select>
            </div>

            {{-- Reset --}}
            <div class="col-lg-1">
                <button type="button" id="reset_filters" class="btn btn-light w-100">
                    <i class="ki-duotone ki-arrows-circle fs-2">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                </button>
            </div>

        </div>
    </div>
</div>

{{-- Table --}}
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="customers_table" class="table align-middle table-row-bordered fs-6 gy-5">
                <thead>
                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                        <th>#</th>
                        <th>{{ __('customers.fields.name') }}</th>
                        <th>{{ __('customers.fields.mobile') }}</th>
                        {{-- <th>{{ __('customers.fields.group') }}</th> --}}
                        {{-- <th>{{ __('customers.fields.cars') }}</th> --}}
                        {{-- <th>{{ __('customers.fields.addresses') }}</th> --}}
                        <th>{{ __('customers.fields.status') }}</th>
                        <th class="text-end">{{ __('customers.actions') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

{{-- ─── Modal ─────────────────────────────────────────────────────── --}}
<div class="modal fade" id="exportCustomersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa-solid fa-file-excel text-success me-2"></i>
                    تصدير الزبائن - Excel
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <p class="text-muted fs-7 mb-4">الفلترة حسب تاريخ التسجيل</p>
                <div class="row g-4">
                    <div class="col-6">
                        <label class="form-label fw-bold">من تاريخ</label>
                        <input type="date" id="custExportFrom" class="form-control"
                            value="{{ now()->startOfMonth()->format('Y-m-d') }}">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-bold">إلى تاريخ</label>
                        <input type="date" id="custExportTo" class="form-control"
                            value="{{ now()->format('Y-m-d') }}">
                    </div>
                </div>
                <div id="custExportError" class="alert alert-danger mt-3 d-none"></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-success" id="doExportCustomersBtn">
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
        const table = $('#customers_table').DataTable({
            processing: true,
            serverSide: true,
            searching: false, // ✅ لأن عندنا بحث مخصص
            ajax: {
                url: "{{ route('dashboard.customers.datatable') }}",
                data: function(d) {
                    d.search_custom = $('#search_custom').val();
                    d.is_active = $('#filter_is_active').val() || '';
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
                    data: 'name',
                    name: 'name'
                },
                {
                    data: 'mobile',
                    name: 'mobile'
                },
                // {data:'group', name:'customerGroup.name', orderable:false, searchable:false},
                // {data:'cars_count', name:'cars_count', searchable:false},
                // {data:'addresses_count', name:'addresses_count', searchable:false},
                {
                    data: 'status_badge',
                    name: 'is_active',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'actions',
                    orderable: false,
                    searchable: false,
                    className: 'text-end'
                },
            ],
            drawCallback: function() {
                $('[data-bs-toggle="tooltip"]').tooltip();
            }
        });

        $('#search_custom').on('keyup', function() {
            table.ajax.reload();
        });

        $('#filter_is_active').on('change', function() {
            table.ajax.reload();
        });

        $('#reset_filters').on('click', function() {
            $('#search_custom').val('');
            $('#filter_is_active').val('');
            table.ajax.reload();
        });

        // delete
        $(document).on('click', '.js-delete-customer', function() {
            const id = $(this).data('id');

            Swal.fire({
                icon: 'warning',
                title: "{{ __('customers.delete_confirm_title') }}",
                text: "{{ __('customers.delete_confirm_text') }}",
                showCancelButton: true,
                confirmButtonText: "{{ __('customers.delete') }}",
                cancelButtonText: "{{ __('customers.cancel') }}"
            }).then((result) => {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: "{{ url('/dashboard/customers') }}/" + id,
                    method: 'POST',
                    data: {
                        _method: 'DELETE',
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(res) {
                        if (window.toastr) toastr.success(res.message);
                        table.ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message ||
                            'Something went wrong', 'error');
                    }
                });
            });
        });

        document.getElementById('doExportCustomersBtn').addEventListener('click', function() {
            const from = document.getElementById('custExportFrom').value;
            const to = document.getElementById('custExportTo').value;
            const err = document.getElementById('custExportError');

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

            window.location.href = "{{ route('dashboard.customers.export') }}" + `?from=${from}&to=${to}`;
            setTimeout(() => bootstrap.Modal.getInstance(document.getElementById('exportCustomersModal'))
                ?.hide(), 800);
        });

    })();
</script>
@endpush
