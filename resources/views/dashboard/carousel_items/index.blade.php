@extends('base.layout.app')

@section('title', __('carousel.title'))

@section('content')

@section('top-btns')
    @can('carousel_items.create')
        <a href="{{ route('dashboard.carousel-items.create') }}" class="btn btn-primary">
            <i class="ki-duotone ki-plus fs-3 me-1"><span class="path1"></span><span class="path2"></span></i>
            {{ __('carousel.create') }}
        </a>
    @endcan
@endsection

<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-toolbar">
            <div class="d-flex justify-content-end gap-3">
                <select id="filter_active" class="form-select form-select-solid w-200px" data-control="select2" data-placeholder="{{ __('carousel.filter_status') }}">
                    <option value="">{{ __('carousel.all') }}</option>
                    <option value="1">{{ __('carousel.active') }}</option>
                    <option value="0">{{ __('carousel.inactive') }}</option>
                </select>
            </div>
        </div>
    </div>

    <div class="card-body pt-0">
        <table class="table align-middle table-row-dashed fs-6 gy-5" id="carousel_table">
            <thead>
            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                <th style="width:70px">{{ __('carousel.table.image') }}</th>
                <th>{{ __('carousel.table.title') }}</th>
                <th style="width:120px">{{ __('carousel.table.status') }}</th>
                <th style="width:110px">{{ __('carousel.table.sort_order') }}</th>
                <th style="width:160px" class="text-end">{{ __('carousel.table.actions') }}</th>
            </tr>
            </thead>
        </table>
    </div>
</div>
@endsection

@push('custom-script')
<script>
(function () {
    const isAr = document.documentElement.lang === 'ar';

    $('#filter_active').select2({ minimumResultsForSearch: Infinity });

    const table = $('#carousel_table').DataTable({
        processing: true,
        serverSide: true,
        searchDelay: 350,
        ajax: {
            url: "{{ route('dashboard.carousel-items.datatable') }}",
            data: function (d) {
                d.is_active = $('#filter_active').val();
            }
        },
        columns: [
            { data: 'image', name: 'image', orderable: false, searchable: false },
            { data: 'title_text', name: 'title' },
            { data: 'status_badge', name: 'is_active', orderable: true, searchable: false },
            { data: 'sort_order', name: 'sort_order' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end' },
        ],
        order: [[3,'asc'], [0,'desc']],
        language: isAr ? { url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/ar.json" } : undefined,
    });

    $('#dt_search').on('keyup', function () { table.search(this.value).draw(); });
    $('#filter_active').on('change', function () { table.ajax.reload(); });

    // delete ajax
    $(document).on('click', '[data-kt-delete]', function () {
        const url = $(this).data('url');

        Swal.fire({
            icon: 'warning',
            title: "{{ __('carousel.delete_confirm_title') }}",
            text: "{{ __('carousel.delete_confirm_text') }}",
            showCancelButton: true,
            confirmButtonText: "{{ __('carousel.delete_confirm_yes') }}",
            cancelButtonText: "{{ __('carousel.delete_confirm_no') }}",
        }).then((r) => {
            if (!r.isConfirmed) return;

            $.ajax({
                url,
                type: 'DELETE',
                data: { _token: "{{ csrf_token() }}" },
                success: function (res) {
                    toastr?.success(res.message || 'Deleted');
                    table.ajax.reload();
                },
                error: function (xhr) {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Error', 'error');
                }
            });
        });
    });

})();
</script>
@endpush