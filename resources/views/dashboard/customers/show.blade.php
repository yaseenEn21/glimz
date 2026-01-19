@extends('base.layout.app')

@section('title', __('customers.profile_title'))

@section('content')

@section('top-btns')

    @can('customers.edit')
        <a href="{{ route('dashboard.customers.edit', $customer->id) }}" class="btn btn-primary">
            {{ __('customers.edit') }}
        </a>
    @endcan

@endsection

@php
    $img = $customer->getFirstMediaUrl('profile_image') ?: asset('assets/media/avatars/blank.png');
    $walletBalance = (float)($customer->wallet?->balance ?? 0);
    $walletCurrency = $customer->wallet?->currency ?? 'SAR';

    $points = (int)($customer->pointWallet?->balance_points ?? 0);
    $earned = (int)($customer->pointWallet?->total_earned_points ?? 0);
    $spent  = (int)($customer->pointWallet?->total_spent_points ?? 0);
@endphp

<div class="card mb-6">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-6">

            <div class="d-flex align-items-center gap-4">
                <div class="symbol symbol-90px symbol-circle">
                    <img src="{{ $img }}" alt="avatar">
                </div>

                <div>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <h2 class="fw-bold mb-0">{{ $customer->name }}</h2>

                        {!! $customer->is_active
                            ? '<span class="badge badge-light-success">'.e(__('customers.active')).'</span>'
                            : '<span class="badge badge-light-danger">'.e(__('customers.inactive')).'</span>' !!}

                        <span class="badge badge-light">
                            {{ __('customers.fields.mobile') }}: {{ $customer->mobile }}
                        </span>
                        @if($customer->email)
                            <span class="badge badge-light">
                                {{ __('customers.fields.email') }}: {{ $customer->email }}
                            </span>
                        @endif
                    </div>

                    <div class="text-muted mt-2">
                        {{ __('customers.fields.customer_group') }}:
                        <span class="fw-semibold">{{ $customer->customerGroup?->name ?? '—' }}</span>
                        <span class="mx-2">•</span>
                        {{ __('customers.fields.gender') }}:
                        <span class="fw-semibold">{{ __('customers.genders.'.$customer->gender) }}</span>
                        <span class="mx-2">•</span>
                        {{ __('customers.fields.birth_date') }}:
                        <span class="fw-semibold">{{ $customer->birth_date?->format('Y-m-d') ?? '—' }}</span>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-3 flex-wrap">
                <div class="border rounded p-4">
                    <div class="text-muted">{{ __('customers.stats.wallet') }}</div>
                    <div class="fw-bold fs-3">{{ number_format($walletBalance,2) }} <span class="fs-7 text-muted">{{ $walletCurrency }}</span></div>
                </div>

                <div class="border rounded p-4">
                    <div class="text-muted">{{ __('customers.stats.points') }}</div>
                    <div class="fw-bold fs-3">{{ $points }}</div>
                    {{-- <div class="text-muted fs-8">{{ __('customers.stats.earned') }}: {{ $earned }} • {{ __('customers.stats.spent') }}: {{ $spent }}</div> --}}
                </div>

                <div class="border rounded p-4">
                    <div class="text-muted">{{ __('customers.stats.bookings_total') }}</div>
                    <div class="fw-bold fs-3">{{ (int)($bookingStats->total ?? 0) }}</div>
                    {{-- <div class="text-muted fs-8">
                        {{ __('customers.stats.active') }}: {{ (int)($bookingStats->active ?? 0) }} •
                        {{ __('customers.stats.completed') }}: {{ (int)($bookingStats->completed ?? 0) }} •
                        {{ __('customers.stats.cancelled') }}: {{ (int)($bookingStats->cancelled ?? 0) }}
                    </div> --}}
                </div>

                {{-- <div class="border rounded p-4">
                    <div class="text-muted">{{ __('customers.stats.assets') }}</div>
                    <div class="text-muted fs-8">
                        {{ __('customers.fields.cars') }}: <span class="fw-bold">{{ (int)$customer->cars_count }}</span>
                        <span class="mx-2">•</span>
                        {{ __('customers.fields.addresses') }}: <span class="fw-bold">{{ (int)$customer->addresses_count }}</span>
                    </div>
                    <div class="text-muted fs-8 mt-1">
                        {{ __('customers.stats.subscriptions') }}: <span class="fw-bold">{{ (int)$customer->package_subscriptions_count }}</span>
                    </div>
                </div> --}}
            </div>

        </div>

        @if($activeSub)
            <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-4 mt-6">
                <i class="ki-duotone ki-information-5 fs-2tx text-primary me-4">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i>
                <div class="fw-semibold">
                    <div class="fs-6 text-gray-700">
                        {{ __('customers.active_subscription') }}:
                        <span class="fw-bold">{{ function_exists('i18n') ? i18n($activeSub->package?->name) : (data_get($activeSub->package?->name,'ar') ?? '') }}</span>
                        — {{ __('customers.remaining_washes') }}:
                        <span class="fw-bold">{{ (int)$activeSub->remaining_washes }}</span>
                        — {{ __('customers.ends_at') }}:
                        <span class="fw-bold">{{ $activeSub->ends_at?->format('Y-m-d') }}</span>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-body">

        <ul class="nav nav-tabs nav-line-tabs mb-6">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab_bookings">{{ __('customers.tabs.bookings') }}</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab_payments">{{ __('customers.tabs.payments') }}</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab_invoices">{{ __('customers.tabs.invoices') }}</a></li>

            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab_wallet">{{ __('customers.tabs.wallet') }}</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab_points">{{ __('customers.tabs.points') }}</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab_subs">{{ __('customers.tabs.subscriptions') }}</a></li>

            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab_cars">{{ __('customers.tabs.cars') }}</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab_addresses">{{ __('customers.tabs.addresses') }}</a></li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="tab_bookings">
                <div class="table-responsive">
                    <table id="dt_bookings" class="table align-middle table-row-dashed">
                        <thead>
                            <tr class="text-muted fw-bold text-uppercase fs-7">
                                <th>#</th>
                                <th>{{ __('customers.cols.date') }}</th>
                                <th>{{ __('customers.cols.time') }}</th>
                                <th>{{ __('customers.cols.service') }}</th>
                                <th>{{ __('customers.cols.employee') }}</th>
                                <th>{{ __('customers.cols.status') }}</th>
                                <th>{{ __('customers.cols.total') }}</th>
                                <th class="text-end">{{ __('customers.actions') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="tab_payments">
                <div class="table-responsive">
                    <table id="dt_payments" class="table align-middle table-row-dashed">
                        <thead>
                            <tr class="text-muted fw-bold text-uppercase fs-7">
                                <th>#</th>
                                <th>{{ __('customers.cols.amount') }}</th>
                                <th>{{ __('customers.cols.status') }}</th>
                                <th>{{ __('customers.cols.created_at') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="tab_invoices">
                <div class="table-responsive">
                    <table id="dt_invoices" class="table align-middle table-row-dashed">
                        <thead>
                            <tr class="text-muted fw-bold text-uppercase fs-7">
                                <th>#</th>
                                <th>{{ __('customers.cols.amount') }}</th>
                                <th>{{ __('customers.cols.status') }}</th>
                                <th>{{ __('customers.cols.created_at') }}</th>
                                <th class="text-end">{{ __('customers.actions') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="tab_wallet">
                <div class="table-responsive">
                    <table id="dt_wallet" class="table align-middle table-row-dashed">
                        <thead>
                            <tr class="text-muted fw-bold text-uppercase fs-7">
                                <th>#</th>
                                <th>{{ __('customers.cols.type') }}</th>
                                <th>{{ __('customers.cols.amount') }}</th>
                                <th>{{ __('customers.cols.created_at') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="tab_points">
                <div class="table-responsive">
                    <table id="dt_points" class="table align-middle table-row-dashed">
                        <thead>
                            <tr class="text-muted fw-bold text-uppercase fs-7">
                                <th>#</th>
                                <th>{{ __('customers.cols.type') }}</th>
                                <th>{{ __('customers.cols.points') }}</th>
                                <th>{{ __('customers.cols.created_at') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="tab_subs">
                <div class="table-responsive">
                    <table id="dt_subs" class="table align-middle table-row-dashed">
                        <thead>
                            <tr class="text-muted fw-bold text-uppercase fs-7">
                                <th>#</th>
                                <th>{{ __('customers.cols.package') }}</th>
                                <th>{{ __('customers.cols.remaining_washes') }}</th>
                                <th>{{ __('customers.cols.ends_at') }}</th>
                                <th>{{ __('customers.cols.status') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="tab_cars">
                <div class="table-responsive">
                    <table id="dt_cars" class="table align-middle table-row-dashed">
                        <thead>
                            <tr class="text-muted fw-bold text-uppercase fs-7">
                                <th>#</th>
                                <th>{{ __('customers.cols.car') }}</th>
                                <th>{{ __('customers.cols.created_at') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="tab_addresses">
                <div class="table-responsive">
                    <table id="dt_addresses" class="table align-middle table-row-dashed">
                        <thead>
                            <tr class="text-muted fw-bold text-uppercase fs-7">
                                <th>#</th>
                                <th>{{ __('customers.cols.address') }}</th>
                                <th>{{ __('customers.cols.created_at') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

        </div>

    </div>
</div>

@endsection

@push('custom-script')
<script>
(function(){
    // Delete on show page
    $('#btn_delete_customer').on('click', function () {
        Swal.fire({
            icon: 'warning',
            title: "{{ __('customers.delete_confirm_title') }}",
            text: "{{ __('customers.delete_confirm_text') }}",
            showCancelButton: true,
            confirmButtonText: "{{ __('customers.delete') }}",
            cancelButtonText: "{{ __('customers.cancel') }}"
        }).then((result) => {
            if (!result.isConfirmed) return;

            const $f = $('#delete_customer_form');
            $.ajax({
                url: $f.attr('action'),
                method: 'POST',
                data: {_method:'DELETE', _token:"{{ csrf_token() }}"},
                success: function(res){
                    Swal.fire({icon:'success', title:"{{ __('customers.done') }}", text: res.message});
                    if(res.redirect) window.location.href = res.redirect;
                },
                error: function(xhr){
                    Swal.fire('Error', xhr.responseJSON?.message || 'Something went wrong', 'error');
                }
            });
        });
    });

    let loaded = {};

    function initBookings(){
        if(loaded.bookings) return; loaded.bookings = true;
        $('#dt_bookings').DataTable({
            processing:true, serverSide:true,
            ajax: "{{ route('dashboard.customers.datatable.bookings', $customer->id) }}",
            order:[[0,'desc']],
            columns:[
                {data:'id', name:'id'},
                {data:'booking_date', name:'booking_date'},
                {data:'start_time', name:'start_time'},
                {data:'service', orderable:false, searchable:false},
                {data:'employee', orderable:false, searchable:false},
                {data:'status_badge', orderable:false, searchable:false},
                {data:'total', orderable:false, searchable:false},
                {data:'actions', orderable:false, searchable:false, className:'text-end'},
            ],
            raw: true
        });
    }

    function initPayments(){
        if(loaded.payments) return; loaded.payments = true;
        $('#dt_payments').DataTable({
            processing:true, serverSide:true,
            ajax: "{{ route('dashboard.customers.datatable.payments', $customer->id) }}",
            order:[[0,'desc']],
            columns:[
                {data:'id', name:'id'},
                {data:'amount', orderable:false, searchable:false},
                {data:'status_badge', orderable:false, searchable:false},
                {data:'created_at', name:'created_at'},
            ],
            raw:true
        });
    }

    function initInvoices(){
        if(loaded.invoices) return; loaded.invoices = true;
        $('#dt_invoices').DataTable({
            processing:true, serverSide:true,
            ajax: "{{ route('dashboard.customers.datatable.invoices', $customer->id) }}",
            order:[[0,'desc']],
            columns:[
                {data:'id', name:'id'},
                {data:'amount', orderable:false, searchable:false},
                {data:'status_badge', orderable:false, searchable:false},
                {data:'created_at', name:'created_at'},
                {data:'actions', orderable:false, searchable:false, className:'text-end'},
            ],
            raw:true
        });
    }

    function initWallet(){
        if(loaded.wallet) return; loaded.wallet = true;
        $('#dt_wallet').DataTable({
            processing:true, serverSide:true,
            ajax: "{{ route('dashboard.customers.datatable.wallet_transactions', $customer->id) }}",
            order:[[0,'desc']],
            columns:[
                {data:'id', name:'id'},
                {data:'type', name:'type'},
                {data:'amount', name:'amount'},
                {data:'created_at', name:'created_at'},
            ],
        });
    }

    function initPoints(){
        if(loaded.points) return; loaded.points = true;
        $('#dt_points').DataTable({
            processing:true, serverSide:true,
            ajax: "{{ route('dashboard.customers.datatable.point_transactions', $customer->id) }}",
            order:[[0,'desc']],
            columns:[
                {data:'id', name:'id'},
                {data:'type', name:'type'},
                {data:'points', name:'points'},
                {data:'created_at', name:'created_at'},
            ],
        });
    }

    function initSubs(){
        if(loaded.subs) return; loaded.subs = true;
        $('#dt_subs').DataTable({
            processing:true, serverSide:true,
            ajax: "{{ route('dashboard.customers.datatable.package_subscriptions', $customer->id) }}",
            order:[[0,'desc']],
            columns:[
                {data:'id', name:'id'},
                {data:'package', orderable:false, searchable:false},
                {data:'remaining_washes', name:'remaining_washes'},
                {data:'ends_at', name:'ends_at'},
                {data:'status', name:'status'},
            ],
        });
    }

    function initCars(){
        if(loaded.cars) return; loaded.cars = true;
        $('#dt_cars').DataTable({
            processing:true, serverSide:true,
            ajax: "{{ route('dashboard.customers.datatable.cars', $customer->id) }}",
            order:[[0,'desc']],
            columns:[
                {data:'id', name:'id'},
                {data:'plate_number', name:'plate_number'},
                {data:'created_at', name:'created_at'},
            ],
        });
    }

    function initAddresses(){
        if(loaded.addresses) return; loaded.addresses = true;
        $('#dt_addresses').DataTable({
            processing:true, serverSide:true,
            ajax: "{{ route('dashboard.customers.datatable.addresses', $customer->id) }}",
            order:[[0,'desc']],
            columns:[
                {data:'id', name:'id'},
                {data:'address_line', name:'address_line'},
                {data:'created_at', name:'created_at'},
            ],
        });
    }

    // init first tab
    initBookings();

    // init on tab shown
    document.querySelectorAll('a[data-bs-toggle="tab"]').forEach(a=>{
        a.addEventListener('shown.bs.tab', function(e){
            const target = e.target.getAttribute('href');

            if(target === '#tab_bookings') initBookings();
            if(target === '#tab_payments') initPayments();
            if(target === '#tab_invoices') initInvoices();
            if(target === '#tab_wallet') initWallet();
            if(target === '#tab_points') initPoints();
            if(target === '#tab_subs') initSubs();
            if(target === '#tab_cars') initCars();
            if(target === '#tab_addresses') initAddresses();
        });
    });

})();
</script>
@endpush