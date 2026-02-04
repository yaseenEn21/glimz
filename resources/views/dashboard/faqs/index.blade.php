@extends('base.layout.app')

@section('title', __('faqs.title'))

@section('content')

@section('top-btns')
    @can('faqs.create')
        <a href="{{ route('dashboard.faqs.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i>
            {{ __('faqs.create') }}
        </a>
    @endcan
@endsection

{{-- Filters --}}
<div class="card mb-5">
    <div class="card-body">
        <div class="row g-4 align-items-center">
            <div class="col-lg-6">
                <input type="text" id="search_custom" class="form-control"
                    placeholder="{{ __('faqs.search_placeholder') }}">
            </div>

            <div class="col-lg-3">
                <select id="status" class="form-select">
                    <option value="">{{ __('faqs.all_status') }}</option>
                    <option value="active">{{ __('faqs.active') }}</option>
                    <option value="inactive">{{ __('faqs.inactive') }}</option>
                </select>
            </div>

            <div class="col-lg-1">
                <button type="button" id="reset_filters" class="btn btn-light-primary w-100">
                    <i class="fa-solid fa-rotate-right p-0"></i>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- DataTable --}}
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="faqs_table" class="table align-middle table-row-dashed fs-6 gy-5">
                <thead>
                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                        <th>#</th>
                        <th>{{ __('faqs.question') }}</th>
                        <th>{{ __('faqs.sort_order') }}</th>
                        <th>{{ __('faqs.status') }}</th>
                        <th class="text-end">{{ __('faqs.actions') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

@endsection

@push('custom-script')
<script>
(function() {
    const table = window.KH.initAjaxDatatable({
        tableId: 'faqs_table',
        ajaxUrl: '{{ route('dashboard.faqs.datatable') }}',
        languageUrl: dtLangUrl,
        searchInputId: 'search_custom',
        columns: [
            {
                data: 'id',
                name: 'id',
                title: "#"
            },
            {
                data: 'question_label',
                name: 'question',
                title: "{{ __('faqs.question') }}",
                orderable: false,
                searchable: false
            },
            {
                data: 'sort_order',
                name: 'sort_order',
                title: "{{ __('faqs.sort_order') }}"
            },
            {
                data: 'status_badge',
                name: 'is_active',
                title: "{{ __('faqs.status') }}",
                orderable: false,
                searchable: false
            },
            {
                data: 'actions',
                name: 'actions',
                className: 'text-end',
                title: '{{ __('faqs.actions') }}',
                orderable: false,
                searchable: false
            }
        ],
        extraData: function(d) {
            d.status = $('#status').val();
        }
    });

    $('#search_custom, #status').on('keyup change', function() {
        table.ajax.reload();
    });

    $('#reset_filters').on('click', function() {
        $('#search_custom').val('');
        $('#status').val('');
        table.ajax.reload();
    });

    // Delete FAQ
    $(document).on('click', '.js-delete-faq', function() {
        const id = $(this).data('id');

        Swal.fire({
            icon: 'warning',
            title: "{{ __('faqs.delete_confirm_title') }}",
            text: "{{ __('faqs.delete_confirm_text') }}",
            showCancelButton: true,
            confirmButtonText: "{{ __('faqs.delete') }}",
            cancelButtonText: "{{ __('faqs.cancel') }}"
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: "{{ url('/dashboard/faqs') }}/" + id,
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
                    Swal.fire('Error', xhr.responseJSON?.message || 'Something went wrong', 'error');
                }
            });
        });
    });
})();
</script>
@endpush