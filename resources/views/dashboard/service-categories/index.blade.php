@extends('base.layout.app')

@section('content')

@section('top-btns')
    @can('service_categories.create')
        <a href="{{ route('dashboard.service-categories.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus fs-5 me-2"></i>
            {{ __('service_categories.create_new') }}
        </a>
    @endcan
@endsection

{{-- فلاتر --}}
<div class="card mb-5 shadow-sm">
    <div class="card-body p-6">
        <div class="d-flex flex-wrap align-items-center gap-4">
            <div class="flex-grow-1" style="min-width: 250px;">
                <div class="position-relative">
                    <i class="fa-solid fa-magnifying-glass position-absolute top-50 translate-middle-y ms-4 text-gray-500"></i>
                    <input type="text" id="search_custom" class="form-control form-control-solid input-with-icon"
                        placeholder="{{ __('service_categories.search_placeholder') }}" />
                </div>
            </div>

            <div style="min-width: 180px;">
                <select id="filter_status" class="form-select form-select-solid" data-control="select2"
                    data-placeholder="{{ __('service_categories.all_statuses') }}" data-allow-clear="true">
                    <option value="">{{ __('service_categories.all_statuses') }}</option>
                    <option value="active">{{ __('service_categories.active') }}</option>
                    <option value="inactive">{{ __('service_categories.inactive') }}</option>
                </select>
            </div>

            <div>
                <button type="button" id="reset_filters" class="btn btn-light-primary action-button">
                    <i class="fa-solid fa-rotate-right p-0"></i>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- الجدول --}}
<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table id="service_categories_table" class="table table-row-bordered table-hover gy-5">
                <thead>
                    <tr class="fw-semibold fs-6 text-muted"></tr>
                </thead>
            </table>
        </div>
    </div>
</div>

@endsection

@push('custom-script')
<script>
(function () {

    const table = window.KH.initAjaxDatatable({
        tableId: 'service_categories_table',
        ajaxUrl: '{{ route('dashboard.service-categories.index') }}',
        languageUrl: dtLangUrl,
        searchInputId: 'search_custom',
        columns: [
            { data: 'id',             name: 'id',             title: "{{ t('datatable.lbl_id') }}" },
            { data: 'name',           name: 'name',           title: "{{ __('service_categories.name') }}" },
            { data: 'services_count', name: 'services_count', title: "{{ __('service_categories.services_count') }}", orderable: false, searchable: false },
            { data: 'sort_order',     name: 'sort_order',     title: "{{ __('service_categories.sort_order') }}" },
            { data: 'is_active_badge',name: 'is_active',      title: "{{ __('service_categories.status') }}", orderable: true, searchable: false },
            { data: 'created_at',     name: 'created_at',     title: "{{ t('datatable.lbl_created_at') }}" },
            { data: 'actions',        name: 'actions',        title: "{{ t('datatable.lbl_actions') }}", orderable: false, searchable: false, className: 'text-end' },
        ],
        extraData: function (d) {
            d.status = $('#filter_status').val();
        }
    });

    $('#filter_status').on('change', function () {
        table.ajax.reload();
    });

    $('#reset_filters').on('click', function () {
        $('#search_custom').val('');
        $('#filter_status').val('').trigger('change');
        table.ajax.reload();
    });

    // حذف
    $(document).on('click', '.js-delete-category', function () {
        const id  = $(this).data('id');
        const url = '{{ route('dashboard.service-categories.destroy', ':id') }}'.replace(':id', id);

        Swal.fire({
            icon: 'warning',
            title: "{{ __('messages.confirm_delete_title') }}",
            text:  "{{ __('messages.confirm_delete_text') }}",
            showCancelButton: true,
            confirmButtonText: "{{ __('messages.confirm_delete_confirm_button') }}",
            cancelButtonText:  "{{ __('messages.confirm_delete_cancel_button') }}",
            confirmButtonColor: '#d33',
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url,
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function (res) {
                    if (window.toastr) toastr.success(res.message);
                    table.ajax.reload(null, false);
                },
                error: function (xhr) {
                    const msg = xhr.responseJSON?.message || 'حدث خطأ';
                    Swal.fire('خطأ', msg, 'error');
                }
            });
        });
    });

})();
</script>
@endpush