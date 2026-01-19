@extends('base.layout.app')

@push('custom-style')
    <style>
        #booking_calendar {
            min-height: 750px;
            border-radius: 0.75rem;
            overflow: hidden;
            border: 1px solid #E4E6EF;
        }

        .fc .fc-toolbar-title {
            font-size: 1.15rem;
            font-weight: 700;
        }
    </style>
@endpush

@section('content')

    <div class="card">
        <div class="card-header align-items-center gap-3">
            <div class="card-title">
                <h3 class="fw-bold mb-0">{{ __('bookings.calendar.title') }}</h3>
            </div>

            <div class="card-toolbar d-flex flex-wrap gap-2">

                <input id="calendar_date" class="form-control form-control-sm" style="width:160px"
                    placeholder="{{ __('bookings.calendar.pick_date') }}">

                {{-- <select id="filter_employee" class="form-select form-select-sm" style="width:220px">
                    <option value="">{{ __('bookings.calendar.all_employees') }}</option>
                </select> --}}

                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-light"
                        id="btn_today">{{ __('bookings.calendar.today') }}</button>
                    <button type="button" class="btn btn-sm btn-light" id="btn_prev">
                        <i class="ki-duotone ki-arrow-left fs-3"><span class="path1"></span><span
                                class="path2"></span></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-light" id="btn_next">
                        <i class="ki-duotone ki-arrow-right fs-3"><span class="path1"></span><span
                                class="path2"></span></i>
                    </button>
                </div>

                <div class="btn-group">
                    <button class="btn btn-sm btn-light-primary" id="view_day">{{ __('bookings.calendar.day') }}</button>
                    <button class="btn btn-sm btn-light" id="view_week">{{ __('bookings.calendar.week') }}</button>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div id="booking_calendar"></div>
        </div>
    </div>

@endsection

@push('custom-script')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar-scheduler@6.1.20/index.global.min.js"></script>

    <script>
        (function() {
            const calendarEl = document.getElementById('booking_calendar');

            // ✅ flatpickr (Metronic غالباً موجود)
            if (window.flatpickr) {
                flatpickr("#calendar_date", {
                    dateFormat: "Y-m-d",
                    defaultDate: new Date(),
                    onChange: function(selectedDates) {
                        if (!selectedDates?.length) return;
                        calendar.gotoDate(selectedDates[0]);
                    }
                });
            }

            const calendar = new FullCalendar.Calendar(calendarEl, {
                schedulerLicenseKey: "YOUR_SCHEDULER_KEY", // <-- مهم (Premium)
                timeZone: 'local',
                initialView: 'resourceTimeGridDay',
                height: 780,
                nowIndicator: true,
                editable: false, // ✅ للسحب والإفلات (إذا بدك read-only خليها false)
                selectable: false,
                slotMinTime: "06:00:00",
                slotMaxTime: "23:59:00",
                expandRows: true,
                stickyHeaderDates: true,
                eventStartEditable: false, // يسمح بتحريك البداية (drag)
                eventDurationEditable: false, // يمنع تغيير المدة (resize) ✅
                eventResizableFromStart: false, // احتياط
                // eventMinHeight: 150,
                // eventShortHeight: 150,
                headerToolbar: false, // إحنا عاملين toolbar خاص فينا
                slotDuration: "00:05:00",
                resourceAreaHeaderContent: "{{ __('bookings.calendar.employees') }}",
                resources: {
                    url: "{{ route('dashboard.bookings.calendar.resources') }}",
                    method: "GET",
                    // extraParams: function() {
                    //     return {
                    //         employee_id: $('#filter_employee').val() || ''
                    //     };
                    // }
                },

                events: {
                    url: "{{ route('dashboard.bookings.calendar.events') }}",
                    method: "GET",
                    // extraParams: function() {
                    //     return {
                    //         employee_id: document.getElementById('filter_employee').value || null
                    //     };
                    // }
                },

                eventClick: function(info) {
                    // افتح صفحة الحجز
                    if (info.event.url) {
                        info.jsEvent.preventDefault();
                        window.open(info.event.url, '_self');
                    }
                },

                eventDidMount: function(info) {
                    // Tooltip بسيط
                    const st = info.event.extendedProps?.status || '';
                    info.el.setAttribute('title', `#${info.event.id} - ${st}`);
                },

                // ✅ Drag & Drop move
                eventDrop: function(info) {
                    const ev = info.event;
                    const newDate = FullCalendar.formatDate(ev.start, {
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit'
                    }).replaceAll('/', '-');
                    const startTime = FullCalendar.formatDate(ev.start, {
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: false
                    });
                    const resourceId = ev.getResources()?.[0]?.id || ev.getResourceId?.() || ev._def
                        ?.resourceIds?.[0];

                    Swal.fire({
                        icon: 'question',
                        title: "{{ __('bookings.calendar.confirm_move_title') }}",
                        text: "{{ __('bookings.calendar.confirm_move_text') }}",
                        showCancelButton: true,
                        confirmButtonText: "{{ __('bookings.calendar.confirm') }}",
                        cancelButtonText: "{{ __('bookings.calendar.cancel') }}",
                    }).then((r) => {
                        if (!r.isConfirmed) {
                            info.revert();
                            return;
                        }

                        $.ajax({
                            url: "{{ url('/dashboard/bookings') }}/" + ev.id +
                                "/calendar-move",
                            method: "POST",
                            data: {
                                _token: "{{ csrf_token() }}",
                                booking_date: newDate,
                                start_time: startTime,
                                employee_id: resourceId
                            },
                            success: function(res) {
                                Swal.fire({
                                    icon: 'success',
                                    title: "{{ __('bookings.calendar.done') }}",
                                    text: res.message
                                });
                                calendar.refetchEvents();
                            },
                            error: function(xhr) {
                                info.revert();
                                Swal.fire('Error', xhr.responseJSON?.message ||
                                    'Move failed', 'error');
                            }
                        });
                    });
                }
            });

            async function reloadResources() {
                const employeeId = $('#filter_employee').val() || '';

                const res = await $.ajax({
                    url: "{{ route('dashboard.bookings.calendar.resources') }}",
                    method: "GET",
                    data: {
                        employee_id: employeeId
                    }
                });

                calendar.batchRendering(() => {
                    // امسح كل الأعمدة الحالية
                    calendar.getResources().forEach(r => r.remove());

                    // أضف الأعمدة الجديدة فقط (موظف واحد)
                    res.forEach(r => calendar.addResource(r));
                });
            }

            calendar.render();

            // ✅ Load employees into filter select (اختياري + لطيف)
            $.get("{{ route('dashboard.bookings.calendar.resources') }}", function(res) {
                const $sel = $('#filter_employee');
                res.forEach(r => $sel.append(`<option value="${r.id}">${r.title}</option>`));
            });

            // toolbar actions
            $('#btn_today').on('click', () => calendar.today());
            $('#btn_prev').on('click', () => calendar.prev());
            $('#btn_next').on('click', () => calendar.next());

$('#filter_employee').on('change', async () => {
                  await reloadResources();
                calendar.refetchEvents(); // ✅ يحدث الأحداث
            });


            $('#view_day').on('click', function() {
                $('#view_day').addClass('btn-light-primary').removeClass('btn-light');
                $('#view_week').addClass('btn-light').removeClass('btn-light-primary');
                calendar.changeView('resourceTimeGridDay');
            });

            $('#view_week').on('click', function() {
                $('#view_week').addClass('btn-light-primary').removeClass('btn-light');
                $('#view_day').addClass('btn-light').removeClass('btn-light-primary');
                calendar.changeView('resourceTimeGridWeek');
            });

        })();
    </script>
@endpush
