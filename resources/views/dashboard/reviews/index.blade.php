@extends('base.layout.app')

@section('title', __('reviews.title'))

@section('content')

<div class="card mb-5">
    <div class="card-body">
        {{-- Filters --}}
        <div class="row g-4 align-items-center">
            <div class="col-lg-4">
                <input type="text" id="search_custom" class="form-control"
                    placeholder="{{ __('reviews.filters.search_placeholder') }}">
            </div>

            <div class="col-lg-2">
                <select id="rating" class="form-select">
                    <option value="">{{ __('reviews.filters.rating_placeholder') }}</option>
                    <option value="5">⭐⭐⭐⭐⭐ (5)</option>
                    <option value="4">⭐⭐⭐⭐ (4)</option>
                    <option value="3">⭐⭐⭐ (3)</option>
                    <option value="2">⭐⭐ (2)</option>
                    <option value="1">⭐ (1)</option>
                </select>
            </div>

            <div class="col-lg-2">
                <input type="date" id="from" class="form-control" placeholder="From">
            </div>

            <div class="col-lg-2">
                <input type="date" id="to" class="form-control" placeholder="To">
            </div>

            <div class="col-lg-1">
                <button type="button" id="reset_filters" class="btn btn-light-primary action-button">
                    <i class="fa-solid fa-rotate-right p-0"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">

        <div class="table-responsive">
            <table id="reviews_table" class="table align-middle table-row-dashed fs-6 gy-5">
                <thead>
                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                        <th>#</th>
                        <th>{{ __('reviews.columns.customer') }}</th>
                        <th>{{ __('reviews.columns.service') }}</th>
                        <th>{{ __('reviews.columns.employee') }}</th>
                        <th>{{ __('reviews.columns.rating') }}</th>
                        <th>{{ __('reviews.columns.comment') }}</th>
                        <th>{{ __('reviews.columns.rated_at') }}</th>
                        <th class="text-end">{{ __('reviews.columns.actions') }}</th>
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
            tableId: 'reviews_table',
            ajaxUrl: '{{ route('dashboard.reviews.datatable') }}',
            languageUrl: dtLangUrl,
            searchInputId: 'search_custom',
            columns: [
                {
                    data: 'id',
                    name: 'id',
                    title: "{{ t('datatable.lbl_id') }}"
                },
                {
                    data: 'customer',
                    name: 'user_id',
                    title: "{{ __('reviews.columns.customer') }}",
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'service_name',
                    name: 'service_id',
                    title: "{{ __('reviews.columns.service') }}",
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'employee_label',
                    name: 'employee_id',
                    title: "{{ __('reviews.columns.employee') }}",
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'rating_stars',
                    name: 'rating',
                    title: "{{ __('reviews.columns.rating') }}",
                    orderable: true,
                    searchable: false
                },
                {
                    data: 'rating_comment',
                    name: 'rating_comment',
                    title: "{{ __('reviews.columns.comment') }}",
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'rated_at_formatted',
                    name: 'rated_at',
                    title: "{{ __('reviews.columns.rated_at') }}",
                    orderable: true,
                    searchable: false
                },
                {
                    data: 'actions',
                    name: 'actions',
                    className: 'text-end',
                    title: '{{ t('datatable.lbl_actions') }}',
                    orderable: false,
                    searchable: false
                }
            ],
            extraData: function(d) {
                d.rating = $('#rating').val();
                d.from = $('#from').val();
                d.to = $('#to').val();
            }
        });

        $('#search_custom').on('keyup', function() {
            table.ajax.reload();
        });

        $('#rating, #from, #to').on('change', function() {
            table.ajax.reload();
        });

        $('#reset_filters').on('click', function() {
            $('#search_custom').val('');
            $('#rating').val('');
            $('#from').val('');
            $('#to').val('');
            table.ajax.reload();
        });

        // ✅ حذف تقييم
        $(document).on('click', '.js-delete-review', function() {
            const id = $(this).data('id');

            Swal.fire({
                icon: 'warning',
                title: "{{ __('reviews.delete_confirm_title') }}",
                text: "{{ __('reviews.delete_confirm_text') }}",
                showCancelButton: true,
                confirmButtonText: "{{ __('reviews.delete') }}",
                cancelButtonText: "{{ __('reviews.cancel') }}"
            }).then((result) => {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: "{{ url('/dashboard/reviews') }}/" + id,
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