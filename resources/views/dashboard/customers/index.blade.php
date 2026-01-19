@extends('base.layout.app')

@section('title', __('customers.title'))

@section('content')

@section('top-btns')
    @can('customers.create')
        {{-- <a href="{{ route('dashboard.customers.create') }}" class="btn btn-primary">
            {{ __('customers.create') }}
        </a> --}}
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

@endsection

@push('custom-script')
<script>
(function(){
    const table = $('#customers_table').DataTable({
        processing: true,
        serverSide: true,
        searching: false, // ✅ لأن عندنا بحث مخصص
        ajax: {
            url: "{{ route('dashboard.customers.datatable') }}",
            data: function(d){
                d.search_custom = $('#search_custom').val();
                d.is_active = $('#filter_is_active').val() || '';
            }
        },
        order: [[0,'desc']],
        columns: [
            {data:'id', name:'id'},
            {data:'name', name:'name'},
            {data:'mobile', name:'mobile'},
            // {data:'group', name:'customerGroup.name', orderable:false, searchable:false},
            // {data:'cars_count', name:'cars_count', searchable:false},
            // {data:'addresses_count', name:'addresses_count', searchable:false},
            {data:'status_badge', name:'is_active', orderable:false, searchable:false},
            {data:'actions', orderable:false, searchable:false, className:'text-end'},
        ],
        drawCallback: function() {
            $('[data-bs-toggle="tooltip"]').tooltip();
        }
    });

    $('#search_custom').on('keyup', function(){
        table.ajax.reload();
    });

    $('#filter_is_active').on('change', function(){
        table.ajax.reload();
    });

    $('#reset_filters').on('click', function(){
        $('#search_custom').val('');
        $('#filter_is_active').val('');
        table.ajax.reload();
    });

    // delete
    $(document).on('click', '.js-delete-customer', function () {
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
                data: {_method:'DELETE', _token:"{{ csrf_token() }}"},
                success: function (res) {
                    if (window.toastr) toastr.success(res.message);
                    table.ajax.reload(null, false);
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