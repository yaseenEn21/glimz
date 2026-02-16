@extends('base.layout.app')

@push('custom-style')
    <style>
        #booking_calendar {
            min-height: 750px;
            /* border-radius: 0.75rem; */
            overflow: hidden;
            border: 1px solid #E4E6EF;
        }

        .fc .fc-toolbar-title {
            font-size: 1.15rem;
            font-weight: 700;
        }

        /* إلغاء الخلفية الخضراء */
        .fc .fc-timegrid-slot,
        .fc .fc-timegrid-col.fc-day,
        .fc .fc-timegrid-bg-harness,
        .fc .fc-bg-event,
        .fc .fc-highlight {
            background: transparent !important;
        }

        .fc .fc-timegrid-col-frame {
            background: #fff !important;
        }

        .fc .fc-non-business {
            background: #fafafa !important;
        }

        /* ── Event Base ── */
        .fc-event {
            /* border-radius: 6px !important; */
            padding: 2px 5px !important;
            font-size: 0.75rem !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
            transition: box-shadow 0.15s;
            border: none !important;
            overflow: hidden;
        }

        .fc-event:hover {
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.2);
            z-index: 15 !important;
        }

        .fc-event .fc-event-title {
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .fc-event .fc-event-time {
            font-size: 0.7rem;
            opacity: 0.85;
        }

        /* ── Status Colors ── */

        .fc-v-event .fc-event-main {
            color: unset !important;
        }

        /* ── Status Colors ── */
        .fc-event.status-pending {
            background: #FFF8DD !important;
            border-left: 3px solid #FFA800 !important;
            color: #7E6700 !important;
        }

        .fc-event.status-confirmed {
            background: #E1F0FF !important;
            border-left: 3px solid #009EF7 !important;
            color: #005A8F !important;
        }

        .fc-event.status-moving {
            background: #F1E6FF !important;
            border-left: 3px solid #7239EA !important;
            color: #4A1FA0 !important;
        }

        .fc-event.status-arrived {
            background: #D6F5F5 !important;
            border-left: 3px solid #00A3A1 !important;
            color: #006060 !important;
        }

        .fc-event.status-completed {
            background: #E8FFF3 !important;
            border-left: 3px solid #50CD89 !important;
            color: #1B6B3E !important;
        }

        .fc-event.status-cancelled {
            background: #FFE2E5 !important;
            border-left: 3px solid #F1416C !important;
            color: #A0203C !important;
            opacity: 0.6;
        }

        /* ── Time Block ── */
        .fc-event.time-block-event {
            background: repeating-linear-gradient(45deg, #f1416c22, #f1416c22 4px, #fff 4px, #fff 8px) !important;
            border-left: 3px solid #F1416C !important;
            color: #F1416C !important;
            font-weight: 600;
        }

        /* ── Now Indicator ── */
        .fc .fc-timegrid-now-indicator-line {
            border-color: #F1416C !important;
            border-width: 2px !important;
        }

        .fc .fc-timegrid-now-indicator-arrow {
            border-color: #F1416C !important;
            border-top-color: transparent !important;
            border-bottom-color: transparent !important;
        }

        /* ── RTL Fixes ── */
        html[dir="rtl"] .card-toolbar .btn-group {
            flex-direction: row-reverse;
        }

        html[dir="rtl"] .fc-event {
            border-left: none !important;
            border-right: 3px solid !important;
        }

        html[dir="rtl"] .fc-event.status-pending {
            border-right-color: #FFA800 !important;
        }

        html[dir="rtl"] .fc-event.status-confirmed {
            border-right-color: #009EF7 !important;
        }

        html[dir="rtl"] .fc-event.status-moving {
            border-right-color: #7239EA !important;
        }

        html[dir="rtl"] .fc-event.status-arrived {
            border-right-color: #00A3A1 !important;
        }

        html[dir="rtl"] .fc-event.status-completed {
            border-right-color: #50CD89 !important;
        }

        html[dir="rtl"] .fc-event.status-cancelled {
            border-right-color: #F1416C !important;
        }

        html[dir="rtl"] .fc-event.time-block-event {
            border-right-color: #F1416C !important;
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

                <select id="filter_employee" class="form-select form-select-sm" style="width:220px">
                    <option value="">{{ __('bookings.calendar.all_employees') }}</option>
                </select>

                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-light"
                        id="btn_today">{{ __('bookings.calendar.today') }}</button>
                    <button type="button" class="btn btn-sm btn-light mx-2" id="btn_prev">
                        <i class="ki-duotone ki-arrow-left fs-3 p-0"><span class="path1"></span><span
                                class="path2"></span></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-light ms-2" id="btn_next">
                        <i class="ki-duotone ki-arrow-right fs-3 p-0"><span class="path1"></span><span
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
    {{-- Modal حجب الموعد --}}
    <div class="modal fade" id="timeBlockModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">{{ __('bookings.calendar.time_block_title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light-info d-flex align-items-center p-3 mb-5">
                        <i class="ki-duotone ki-calendar fs-2 me-3 text-info"><span class="path1"></span><span
                                class="path2"></span></i>
                        <div>
                            <span id="tb_summary_date" class="fw-bold"></span>
                            <span class="text-muted mx-1">|</span>
                            <span id="tb_summary_time" class="fw-semibold text-gray-700"></span>
                        </div>
                    </div>
                    <div class="mb-5">
                        <label class="form-label required">{{ __('bookings.calendar.action') }}</label>
                        <select id="tb_action" class="form-select">
                            <option value="block_time">{{ __('bookings.calendar.block_time') }}</option>
                        </select>
                    </div>
                    <div class="mb-5">
                        <label class="form-label required">{{ __('bookings.calendar.target_employees') }}</label>
                        <select id="tb_employees" class="form-select" multiple="multiple"
                            data-placeholder="{{ __('bookings.calendar.select_employees') }}"></select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('bookings.calendar.reason') }}</label>
                        <textarea id="tb_reason" class="form-control" rows="3"
                            placeholder="{{ __('bookings.calendar.reason_placeholder') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light"
                        data-bs-dismiss="modal">{{ __('bookings.calendar.cancel') }}</button>
                    <button type="button" class="btn btn-danger" id="tb_submit">
                        <span class="indicator-label">{{ __('bookings.calendar.confirm_block') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('custom-script')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar-scheduler@6.1.20/index.global.min.js"></script>

    <script>
        (function() {
            const calendarEl = document.getElementById('booking_calendar');

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
                schedulerLicenseKey: "YOUR_SCHEDULER_KEY",
                timeZone: 'local',
                initialView: 'resourceTimeGridDay',
                height: 780,
                nowIndicator: true,
                editable: false,
                selectable: true,
                selectMirror: true,
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
                slotDuration: "00:10:00",
                resourceAreaHeaderContent: "{{ __('bookings.calendar.employees') }}",
                resources: {
                    url: "{{ route('dashboard.bookings.calendar.resources') }}",
                    method: "GET",
                    extraParams: function() {
                        return {
                            employee_id: $('#filter_employee').val() || ''
                        };
                    }
                },

                events: {
                    url: "{{ route('dashboard.bookings.calendar.events') }}",
                    method: "GET",
                    extraParams: function() {
                        return {
                            employee_id: document.getElementById('filter_employee').value || null
                        };
                    }
                },

                eventClick: function(info) {
                    // ✅ لو ضغط على حدث حجب
                    if (info.event.extendedProps?.type === 'time_block') {
                        info.jsEvent.preventDefault();

                        // استخرج ID الحقيقي (بدون tb_)
                        const blockId = info.event.id.replace('tb_', '');
                        const resourceTitle = info.event.getResources()?.[0]?.title || '';

                        Swal.fire({
                            icon: 'warning',
                            title: "{{ __('bookings.calendar.remove_block_title') }}",
                            html: `{{ __('bookings.calendar.remove_block_text') }}<br><strong>${resourceTitle}</strong>`,
                            showCancelButton: true,
                            confirmButtonColor: '#f1416c',
                            confirmButtonText: "{{ __('bookings.calendar.confirm_remove') }}",
                            cancelButtonText: "{{ __('bookings.calendar.cancel') }}",
                        }).then((result) => {
                            if (!result.isConfirmed) return;

                            $.ajax({
                                url: "{{ url('/dashboard/bookings/calendar/block-slots') }}/" +
                                    blockId,
                                method: "DELETE",
                                data: {
                                    _token: "{{ csrf_token() }}"
                                },
                                success: function(res) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: "{{ __('bookings.calendar.done') }}",
                                        text: res.message,
                                        timer: 2000,
                                        showConfirmButton: false,
                                    });
                                    calendar.refetchEvents();
                                },
                                error: function(xhr) {
                                    Swal.fire('Error', xhr.responseJSON?.message ||
                                        'Failed', 'error');
                                }
                            });
                        });
                        return;
                    }

                    // الحجوزات العادية
                    if (info.event.url) {
                        info.jsEvent.preventDefault();
                        window.open(info.event.url, '_self');
                    }
                },

                select: function(info) {
                    openTimeBlockModal(info);
                    calendar.unselect();
                },

                eventDidMount: function(info) {
                    const props = info.event.extendedProps || {};
                    const status = props.status || '';

                    info.el.setAttribute('title', `#${info.event.id} - ${status}`);

                    if (props.type === 'time_block') {
                        info.el.classList.add('time-block-event');
                    } else if (status) {
                        info.el.classList.add('status-' + status);
                    }
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

            // ══════════════════════════════
            //  Time Block Modal Logic
            // ══════════════════════════════
            let selectedInfo = {};
            let allResources = [];

            // Select2
            if ($.fn.select2) {
                $('#tb_employees').select2({
                    dropdownParent: $('#timeBlockModal'),
                    width: '100%',
                    allowClear: true,
                });
            }

            // تحميل الموظفين للمودال
            $.get("{{ route('dashboard.bookings.calendar.resources') }}", function(res) {
                allResources = res;
                const $sel = $('#tb_employees');
                res.forEach(r => $sel.append(new Option(r.title, r.id, false, false)));
                if ($.fn.select2) $sel.trigger('change');
            });

            function openTimeBlockModal(info) {
                selectedInfo = {
                    date: info.start.toISOString().slice(0, 10),
                    start_time: info.start.toTimeString().slice(0, 5),
                    end_time: info.end.toTimeString().slice(0, 5),
                };

                $('#tb_summary_date').text(selectedInfo.date);
                $('#tb_summary_time').text(selectedInfo.start_time + ' → ' + selectedInfo.end_time);

                // تحديد الموظف اللي سحبت عليه
                const resourceId = info.resource?.id || null;
                if (resourceId) {
                    $('#tb_employees').val([resourceId]);
                    if ($.fn.select2) $('#tb_employees').trigger('change');
                }

                $('#tb_action').val('block_time');
                $('#tb_reason').val('');

                bootstrap.Modal.getOrCreateInstance(document.getElementById('timeBlockModal')).show();
            }

            $('#tb_submit').on('click', function() {
                const $btn = $(this);
                const employeeIds = $('#tb_employees').val();

                if (!employeeIds || employeeIds.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: "{{ __('bookings.calendar.validation_error') }}",
                        text: "{{ __('bookings.calendar.select_at_least_one_employee') }}",
                    });
                    return;
                }

                $btn.prop('disabled', true);

                $.ajax({
                    url: "{{ route('dashboard.bookings.calendar.block-slots') }}",
                    method: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        date: selectedInfo.date,
                        start_time: selectedInfo.start_time,
                        end_time: selectedInfo.end_time,
                        employee_ids: employeeIds,
                        reason: $('#tb_reason').val(),
                    },
                    success: function(res) {
                        bootstrap.Modal.getInstance(document.getElementById('timeBlockModal'))
                            ?.hide();
                        Swal.fire({
                            icon: 'success',
                            title: "{{ __('bookings.calendar.done') }}",
                            text: res.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        calendar.refetchEvents();
                    },
                    error: function(xhr) {
                        const data = xhr.responseJSON || {};
                        let msg = data.message || 'حدث خطأ';
                        if (data.details) msg += '<br><br>' + data.details;
                        Swal.fire({
                            icon: 'error',
                            title: "{{ __('bookings.calendar.error') }}",
                            html: msg
                        });
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                    }
                });
            });

            // ✅ Load employees into filter select (اختياري + لطيف)
            $.get("{{ route('dashboard.bookings.calendar.resources') }}", function(res) {
                const $sel = $('#filter_employee');
                res.forEach(r => $sel.append(`<option value="${r.id}">${r.title}</option>`));
            });

            // toolbar actions
            $('#btn_today').on('click', () => calendar.today());
            $('#btn_prev').on('click', () => calendar.prev());
            $('#btn_next').on('click', () => calendar.next());

            $('#filter_employee').on('change', function() {
                calendar.refetchResources();
                calendar.refetchEvents();
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
