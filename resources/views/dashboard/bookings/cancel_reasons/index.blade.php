@extends('base.layout.app')

@section('title', __('bookings.cancel_reasons.title'))

@section('content')

@section('top-btns')
    @can('cancel_reasons.create')
        <a href="{{ route('dashboard.bookings.cancel-reasons.create') }}" class="btn btn-primary">
            {{ __('bookings.cancel_reasons.create') }}
        </a>
    @endcan
@endsection

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="cancel_reasons_table" class="table align-middle table-row-dashed">
                <thead>
                <tr class="text-muted fw-bold text-uppercase fs-7">
                    <th>{{ __('bookings.cancel_reasons.fields.sort') }}</th>
                    <th>{{ __('bookings.cancel_reasons.fields.code') }}</th>
                    <th>{{ __('bookings.cancel_reasons.fields.name_ar') }}</th>
                    <th>{{ __('bookings.cancel_reasons.fields.name_en') }}</th>
                    <th>{{ __('bookings.cancel_reasons.fields.is_active') }}</th>
                    <th class="text-end">{{ __('bookings.cancel_reasons.actions') }}</th>
                </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection

@push('custom-script')
<script>
(function () {
    const isAr = document.documentElement.lang === 'ar';

    const table = $('#cancel_reasons_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('dashboard.bookings.cancel-reasons.datatable') }}",
        order: [[0, 'asc']],
        columns: [
            {data: 'sort', name: 'sort'},
            {data: 'code', name: 'code'},
            {data: 'name_ar', name: 'name_ar'},
            {data: 'name_en', name: 'name_en'},
            {
                data: 'is_active',
                name: 'is_active',
                render: function (val) {
                    return val
                        ? `<span class="badge badge-light-success">{{ __('bookings.cancel_reasons.active') }}</span>`
                        : `<span class="badge badge-light-danger">{{ __('bookings.cancel_reasons.inactive') }}</span>`;
                }
            },
            {data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end'},
        ]
    });

    // delete
    $(document).on('click', '.js-delete-cancel-reason', function () {
        const id = $(this).data('id');

        Swal.fire({
            icon: 'warning',
            title: "{{ __('bookings.cancel_reasons.delete_confirm_title') }}",
            text: "{{ __('bookings.cancel_reasons.delete_confirm_text') }}",
            showCancelButton: true,
            confirmButtonText: "{{ __('bookings.cancel_reasons.delete') }}",
            cancelButtonText: "{{ __('bookings.cancel_reasons.cancel') }}"
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: "{{ url('/dashboard/bookings/cancel-reasons') }}/" + id,
                method: 'POST',
                data: {_method:'DELETE', _token:"{{ csrf_token() }}"},
                success: function (res) {
                    Swal.fire({icon:'success', title:"{{ __('bookings.cancel_reasons.done') }}", text: res.message});
                    table.ajax.reload();
                },
                error: function (xhr) {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Something went wrong', 'error');
                }
            });
        });
    });

})();
</script>
@endpush