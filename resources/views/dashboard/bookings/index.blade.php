@extends('base.layout.app')

@section('title', __('bookings.title'))

@section('content')

@section('top-btns')
    {{-- لاحقاً: زر create --}}
    <a href="{{ route('dashboard.bookings.create') }}" class="btn btn-primary"> {{ __('bookings.create.title') }} </a>
@endsection

<div class="card mb-5">
    <div class="card-body">
        {{-- Filters --}}
        <div class="row g-4 align-items-center">
            <div class="col-lg-4">
                <input type="text" id="search_custom" class="form-control"
                    placeholder="{{ __('bookings.filters.search_placeholder') }}">
            </div>

            <div class="col-lg-2">
                <select id="status" class="form-select">
                    <option value="">{{ __('bookings.filters.status_placeholder') }}</option>
                    <option value="pending">{{ __('bookings.status.pending') }}</option>
                    <option value="confirmed">{{ __('bookings.status.confirmed') }}</option>
                    <option value="moving">{{ __('bookings.status.moving') }}</option>
                    <option value="arrived">{{ __('bookings.status.arrived') }}</option>
                    <option value="completed">{{ __('bookings.status.completed') }}</option>
                    <option value="cancelled">{{ __('bookings.status.cancelled') }}</option>
                </select>
            </div>

            {{-- <div class="col-lg-2">
                <select id="time_period" class="form-select">
                    <option value="">{{ __('bookings.filters.time_period_placeholder') }}</option>
                    <option value="morning">{{ __('bookings.time_period.morning') }}</option>
                    <option value="evening">{{ __('bookings.time_period.evening') }}</option>
                    <option value="all">{{ __('bookings.time_period.all') }}</option>
                </select>
            </div> --}}

            <div class="col-lg-2">
                <input type="date" id="from" class="form-control" placeholder="From">
            </div>

            <div class="col-lg-2">
                <input type="date" id="to" class="form-control" placeholder="To">
            </div>

            {{-- <div class="col-lg-3">
                <select id="service_id" class="form-select">
                    <option value="">{{ __('bookings.filters.service_placeholder') }}</option>
                    @foreach ($services as $s)
                        @php
                            $name = is_array($s->name) ? ($s->name[app()->getLocale()] ?? collect($s->name)->first()) : $s->name;
                        @endphp
                        <option value="{{ $s->id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-lg-3">
                <input type="number" id="employee_id" class="form-control"
                       placeholder="{{ __('bookings.filters.employee_placeholder') }}">
            </div>

            <div class="col-lg-3">
                <input type="number" id="zone_id" class="form-control"
                       placeholder="{{ __('bookings.filters.zone_placeholder') }}">
            </div> --}}

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
            <table id="bookings_table" class="table align-middle table-row-dashed fs-6 gy-5">
                <thead>
                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                        <th>#</th>
                        <th>{{ __('bookings.columns.customer') }}</th>
                        <th>{{ __('bookings.columns.service') }}</th>
                        <th>{{ __('bookings.columns.schedule') }}</th>
                        <th>{{ __('bookings.columns.employee') }}</th>
                        <th>{{ __('bookings.columns.source') }}</th>
                        <th>{{ __('bookings.columns.total') }}</th>
                        <th>{{ __('bookings.columns.status_control') }}</th>
                        <th class="text-end">{{ __('bookings.columns.actions') }}</th>
                    </tr>
                </thead>
            </table>
        </div>

    </div>
</div>

{{-- Cancel Reason Modal --}}
<div class="modal fade" id="cancelBookingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('bookings.cancel_reason_title') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <textarea id="cancelReasonInput" class="form-control" rows="3"
                    placeholder="{{ __('bookings.cancel_reason_placeholder') }}"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    {{ __('bookings.cancel_modal_close') }}
                </button>
                <button type="button" class="btn btn-danger" id="confirmCancelBtn">
                    {{ __('bookings.confirm_cancel') }}
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('custom-script')
<script>
    (function() {

        const table = window.KH.initAjaxDatatable({
            tableId: 'bookings_table',
            ajaxUrl: '{{ route('dashboard.bookings.datatable') }}',
            languageUrl: dtLangUrl,
            searchInputId: 'search_custom',
            columns: [{
                    data: 'id',
                    name: 'id',
                    title: "{{ t('datatable.lbl_id') }}"
                },
                {
                    data: 'customer',
                    name: 'user_id',
                    title: "{{ __('bookings.columns.customer') }}", // ✅ غيّر هون
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'service_name',
                    name: 'service_id',
                    title: "{{ __('bookings.columns.service') }}", // ✅ غيّر هون
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'schedule',
                    name: 'booking_date',
                    title: "{{ __('bookings.columns.schedule') }}", // ✅ غيّر هون
                    orderable: true,
                    searchable: false
                },
                {
                    data: 'employee_label',
                    name: 'employee_id',
                    title: "{{ __('bookings.columns.employee') }}", // ✅ غيّر هون
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'booking_source',
                    name: 'partner_id',
                    title: "{{ __('bookings.columns.source') }}",
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'total',
                    name: 'total_snapshot',
                    title: "{{ __('bookings.columns.total') }}", // ✅ غيّر هون
                    searchable: false
                },
                {
                    data: 'status_control',
                    name: 'status',
                    title: "{{ __('bookings.columns.status_control') }}", // ✅ غيّر هون
                    orderable: false,
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
                d.status = $('#status').val();
                d.time_period = $('#time_period').val();
                d.from = $('#from').val();
                d.to = $('#to').val();
                d.service_id = $('#service_id').val();
                d.employee_id = $('#employee_id').val();
                d.zone_id = $('#zone_id').val();
            }
        });

        $('#search_custom').on('keyup', function() {
            table.ajax.reload();
        });
        $('#status, #time_period, #from, #to, #service_id, #employee_id, #zone_id').on('change keyup', function() {
            table.ajax.reload();
        });

        $('#reset_filters').on('click', function() {
            $('#search_custom').val('');
            $('#status').val('');
            $('#time_period').val('');
            $('#from').val('');
            $('#to').val('');
            $('#service_id').val('');
            $('#employee_id').val('');
            $('#zone_id').val('');
            table.ajax.reload();
        });

        // ✅ change status ajax
        // ✅ change status ajax — مع مودال الإلغاء
        let cancelModal = new bootstrap.Modal(document.getElementById('cancelBookingModal'));
        let pendingCancelSelect = null;
        let previousStatus = null;

        $(document).on('change', '.js-booking-status-select', function() {
            const $select = $(this);
            const newStatus = $select.val();

            // لو إلغاء → افتح المودال
            if (newStatus === 'cancelled') {
                // احفظ الحالة السابقة عشان نرجعها لو ألغى المودال
                previousStatus = $select.find('option').filter(function() {
                    return this.defaultSelected;
                }).val() || 'pending';

                pendingCancelSelect = $select;
                $('#cancelReasonInput').val('');
                cancelModal.show();
                return;
            }

            // باقي الحالات → أرسل مباشرة
            sendStatusUpdate($select, newStatus, null);
        });

        // تأكيد الإلغاء من المودال
        $('#confirmCancelBtn').on('click', function() {
            if (!pendingCancelSelect) return;

            const reason = $('#cancelReasonInput').val().trim();
            sendStatusUpdate(pendingCancelSelect, 'cancelled', reason);
            cancelModal.hide();
            pendingCancelSelect = null;
        });

        // لو أغلق المودال بدون تأكيد → رجّع القيمة القديمة
        $('#cancelBookingModal').on('hidden.bs.modal', function() {
            if (pendingCancelSelect) {
                pendingCancelSelect.val(previousStatus);
                pendingCancelSelect = null;
            }
            previousStatus = null;
        });

        function sendStatusUpdate($select, status, cancelReason) {
            const url = $select.data('url');

            let payload = {
                _token: "{{ csrf_token() }}",
                status: status
            };

            if (cancelReason) {
                payload.cancel_reason = cancelReason;
            }

            $.ajax({
                url: url,
                type: 'PATCH',
                data: payload,
                success: function(res) {
                    table.ajax.reload(null, false);
                    if (res && res.ok) {
                        if (window.toastr) toastr.success(res.message ||
                            "{{ __('bookings.status_updated') }}");
                    } else {
                        if (window.toastr) toastr.error(res.message || 'Error');
                    }
                },
                error: function(xhr) {
                    table.ajax.reload(null, false);
                    const msg = xhr.responseJSON?.message ||
                        "{{ __('bookings.status_update_failed') }}";
                    if (window.toastr) toastr.error(msg);
                }
            });
        }

        $(document).on('click', '.js-delete-booking', function() {
            const id = $(this).data('id');

            Swal.fire({
                icon: 'warning',
                title: "{{ __('bookings.delete_confirm_title') }}",
                text: "{{ __('bookings.delete_confirm_text') }}",
                showCancelButton: true,
                confirmButtonText: "{{ __('bookings.delete') }}",
                cancelButtonText: "{{ __('bookings.cancel') }}"
            }).then((result) => {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: "{{ url('/dashboard/bookings') }}/" + id,
                    method: 'POST',
                    data: {
                        _method: 'DELETE',
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(res) {

                        if (window.toastr) toastr.success(res.message);

                        if (typeof table !== 'undefined' && table?.ajax) {
                            table.ajax.reload(null, false);
                            return;
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message ||
                            'Something went wrong', 'error');
                    }
                });
            });
        });


        // ✅ نسخ معلومات الحجز
        $(document).on('click', '.js-copy-booking-info', function() {
            const btn = $(this);

            const bookingId = btn.data('booking-id');
            const serviceName = btn.data('service-name');
            const bookingDate = btn.data('booking-date');
            const startTime = btn.data('start-time');
            const customerName = btn.data('customer-name');
            const customerMobile = btn.data('customer-mobile');
            const address = btn.data('address');
            const lat = btn.data('lat');
            const lng = btn.data('lng');
            const plate = btn.data('plate');
            const carColor = btn.data('car-color');
            const carMake = btn.data('car-make');
            const carModel = btn.data('car-model');
            const products = btn.data('products');

            // ✅ تنسيق بسيط ومرتب
            let text = `📋 Booking Info ( ${bookingId} )


🧰 Service: ${serviceName}
📅 Date: ${bookingDate}
🕒 Time: ${startTime}

👤 Customer: ${customerName}
📱 Mobile: ${customerMobile}

🚗 Car: ${carMake} ${carModel}
🔢 Plate: ${plate}
🎨 Color: ${carColor}

📍 Address: ${address}`;

            if (lat && lng) {
                text += `\n🗺 Map: https://maps.google.com/?q=${lat},${lng}`;
            }

            if (products && products.trim() !== '') {
                text += `\n\n📦 Products: ${products}`;
            } else {
                text += `\n\n📦 Products: No products`;
            }

            if (lat && lng) {
                text += `\n🗺 الخريطة: https://maps.google.com/?q=${lat},${lng}`;
            }

            if (products && products.trim() !== '') {
                text += `\n\n📦 المنتجات: ${products}`;
            } else {
                text += `\n\n📦 المنتجات: لا توجد منتجات`;
            }

            // ✅ نسخ للحافظة
            navigator.clipboard.writeText(text).then(function() {
                const icon = btn.find('i');
                const originalClass = icon.attr('class');

                icon.removeClass('fa-copy text-primary')
                    .addClass('fa-check text-success');

                if (window.toastr) {
                    toastr.success('تم نسخ المعلومات بنجاح');
                }

                setTimeout(() => {
                    icon.attr('class', originalClass);
                }, 2000);
            }).catch(function(err) {
                console.error('Failed to copy:', err);

                // Fallback
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();

                try {
                    document.execCommand('copy');
                    if (window.toastr) {
                        toastr.success('تم نسخ المعلومات بنجاح');
                    }
                } catch (e) {
                    if (window.toastr) {
                        toastr.error('فشل نسخ المعلومات');
                    }
                }

                document.body.removeChild(textarea);
            });
        });
    })();
</script>
@endpush
