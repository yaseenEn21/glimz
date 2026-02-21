@extends('base.layout.app')

@push('custom-style')
    <style>
        /* ── بعد ── */
        #booking_calendar {
            min-height: 750px;
            overflow: visible;
            /* ✅ */
            border: 1px solid #E4E6EF;
            min-width: max-content;
            /* ✅ يتمدد حسب عدد الموظفين */
        }

        /* ✅ أضف هذا — يمنع الـ FullCalendar من قطع المحتوى داخلياً */
        #booking_calendar .fc-view-harness,
        #booking_calendar .fc-scrollgrid,
        #booking_calendar .fc-scrollgrid-section-body>td {
            overflow: visible !important;
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

        .swal2-container .swal2-html-container {
            max-height: 500px !important;
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

        /* ── Horizontal Scroll Wrapper ── */
        .calendar-scroll-wrapper {
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
        }

        .calendar-scroll-wrapper::-webkit-scrollbar {
            height: 6px;
        }

        .calendar-scroll-wrapper::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .calendar-scroll-wrapper::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        .calendar-scroll-wrapper::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* ── عرض ثابت لكل عمود موظف ── */
        #booking_calendar .fc-resource-timeline-lane,
        #booking_calendar .fc-col-header-cell.fc-resource,
        #booking_calendar .fc-timegrid-col[data-resource-id] {
            min-width: 140px !important;
            width: 140px !important;
            max-width: 140px !important;
        }

        /* عرض التقويم الكلي = عرض الـ wrapper (يتمدد مع الأعمدة) */
        #booking_calendar .fc-view-harness {
            min-width: max-content;
        }

        #booking_calendar {
            min-height: 750px;
            overflow: hidden;
            border: 1px solid #E4E6EF;
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

        {{-- بعد --}}
        <div class="card-body p-0">
            <div class="calendar-scroll-wrapper">
                <div id="booking_calendar"></div>
            </div>
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
                slotMinTime: "12:00:00",
                slotMaxTime: "27:00:00",
                scrollTime: "12:00:00",
                resourceAreaWidth: '120px', // ✅ عرض عمود أسماء الموظفين
                contentHeight: 750, // ✅ ارتفاع ثابت (السكرول العمودي داخلي)

                // ✅ عرض ثابت لكل resource
                resourceLabelDidMount: function(info) {
                    info.el.style.minWidth = '140px';
                    info.el.style.maxWidth = '140px';
                },


                events: function(info, successCallback, failureCallback) {
                    // نمدد النطاق يوم إضافي عشان نجيب حجوزات بعد منتصف الليل
                    const extraEnd = new Date(info.end);
                    extraEnd.setDate(extraEnd.getDate() + 1);
                    const extraEndStr = extraEnd.toISOString().slice(0, 10);

                    $.ajax({
                        url: "{{ route('dashboard.bookings.calendar.events') }}",
                        method: "GET",
                        data: {
                            start: info.startStr,
                            end: extraEndStr,
                            employee_id: $('#filter_employee').val() || ''
                        },
                        success: function(events) {
                            const transformed = events.map(function(ev) {
                                if (!ev.start || typeof ev.start !== 'string')
                                    return ev;

                                var match = ev.start.match(
                                    /^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})/);
                                if (!match) return ev;

                                var hour = parseInt(match[4]);
                                if (hour >= 5) return ev; // حجز عادي — ما نلمسه

                                // ✅ حجز بعد منتصف الليل — نرجعه لليوم السابق بساعة 24+
                                var y = parseInt(match[1]);

                                var m = parseInt(match[2]) - 1;
                                var d = parseInt(match[3]);
                                var prev = new Date(y, m, d);
                                prev.setDate(prev.getDate() - 1);
                                var prevStr = prev.getFullYear() + '-' +
                                    String(prev.getMonth() + 1).padStart(2, '0') + '-' +
                                    String(prev.getDate()).padStart(2, '0');

                                ev.start = prevStr + 'T' + String(hour + 24).padStart(2,
                                    '0') + ':' + match[5] + ':00';

                                if (ev.end && typeof ev.end === 'string') {
                                    var endMatch = ev.end.match(
                                        /^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})/
                                    );
                                    if (endMatch) {
                                        var endHour = parseInt(endMatch[4]);
                                        if (endHour < 5) endHour += 24;
                                        ev.end = prevStr + 'T' + String(endHour)
                                            .padStart(2, '0') + ':' + endMatch[5] +
                                            ':00';
                                    }
                                }

                                return ev;
                            });

                            successCallback(transformed);
                        },
                        error: failureCallback
                    });
                },
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

                // events: {
                //     url: "{{ route('dashboard.bookings.calendar.events') }}",
                //     method: "GET",
                //     extraParams: function() {
                //         return {
                //             employee_id: document.getElementById('filter_employee').value || null
                //         };
                //     }
                // },

                eventClick: function(info) {
                    // ✅ لو ضغط على حدث حجب
                    if (info.event.extendedProps?.type === 'time_block') {
                        info.jsEvent.preventDefault();

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
                                        showConfirmButton: false
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

                    // ✅ الحجوزات العادية — popup بخيارين
                    // ✅ الحجوزات العادية — popup
                    info.jsEvent.preventDefault();

                    const ev = info.event;
                    const props = ev.extendedProps || {};
                    const bookingId = ev.id;
                    const status = props.status || 'pending';
                    const customerName = props.customer_name || '—';
                    const serviceName = props.service_name || '—';
                    const startTime = ev.start ? ev.start.toTimeString().slice(0, 5) : '';
                    const endTime = ev.end ? ev.end.toTimeString().slice(0, 5) : '';
                    const bookingDate = ev.start ? ev.start.toISOString().slice(0, 10) : '';

                    const statusLabels = {
                        pending: "{{ __('bookings.status.pending') }}",
                        confirmed: "{{ __('bookings.status.confirmed') }}",
                        moving: "{{ __('bookings.status.moving') }}",
                        arrived: "{{ __('bookings.status.arrived') }}",
                        completed: "{{ __('bookings.status.completed') }}",
                        cancelled: "{{ __('bookings.status.cancelled') }}",
                    };

                    const statusColors = {
                        pending: {
                            bg: '#FFF8DD',
                            color: '#7E6700',
                            border: '#FFA800'
                        },
                        confirmed: {
                            bg: '#E1F0FF',
                            color: '#005A8F',
                            border: '#009EF7'
                        },
                        moving: {
                            bg: '#F1E6FF',
                            color: '#4A1FA0',
                            border: '#7239EA'
                        },
                        arrived: {
                            bg: '#D6F5F5',
                            color: '#006060',
                            border: '#00A3A1'
                        },
                        completed: {
                            bg: '#E8FFF3',
                            color: '#1B6B3E',
                            border: '#50CD89'
                        },
                        cancelled: {
                            bg: '#FFE2E5',
                            color: '#A0203C',
                            border: '#F1416C'
                        },
                    };

                    const locked = ['completed', 'cancelled'].includes(status);
                    const sc = statusColors[status] || statusColors.pending;

                    const statuses = ['pending', 'confirmed', 'moving', 'arrived', 'completed',
                        'cancelled'
                    ];

                    let statusSelectHtml = '';
                    if (!locked) {
                        const options = statuses.map(s =>
                            `<option value="${s}" ${s === status ? 'selected' : ''}>${statusLabels[s]}</option>`
                        ).join('');
                        statusSelectHtml = `
        <div class="mt-5 pt-5" style="border-top: 1px dashed #E4E6EF;">
            <label class="form-label fw-bold text-gray-700 mb-2">{{ __('bookings.calendar.change_status') }}</label>
            <select id="swal_status_select" class="form-select form-select-solid">${options}</select>
            <div id="swal_cancel_reason_wrap"></div>
        </div>`;
                    }

                    Swal.fire({
                        html: `
    <div style="text-align: start;">
        <!-- Header -->
        <div class="d-flex align-items-center justify-content-between mb-4 p-4">
            <div>
                <div class="fs-3 fw-bolder text-gray-900">{{ __('bookings.calendar.booking') }} #${bookingId}</div>
                <div class="text-gray-500 fs-7">${bookingDate}</div>
            </div>
            <span style="
                background: ${sc.bg};
                color: ${sc.color};
                border: 1px solid ${sc.border};
                padding: 5px 14px;
                border-radius: 6px;
                font-size: 0.8rem;
                font-weight: 600;
            ">${statusLabels[status]}</span>
        </div>

        <!-- Info Cards — 3 columns -->
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 16px;">
            <div style="background: #F9F9F9; border-radius: 8px; padding: 12px;">
                <div class="text-gray-500 fs-8 fw-semibold mb-1">{{ __('bookings.calendar.popup_customer') }}</div>
                <div class="fw-bold text-gray-800 fs-7">${customerName}</div>
            </div>
            <div style="background: #F9F9F9; border-radius: 8px; padding: 12px;">
                <div class="text-gray-500 fs-8 fw-semibold mb-1">{{ __('bookings.calendar.popup_service') }}</div>
                <div class="fw-bold text-gray-800 fs-7">${serviceName}</div>
            </div>
            <div style="background: #F9F9F9; border-radius: 8px; padding: 12px;">
                <div class="text-gray-500 fs-8 fw-semibold mb-1">{{ __('bookings.calendar.popup_time') }}</div>
                <div class="fw-bold text-gray-800 fs-7">${startTime} — ${endTime}</div>
            </div>
        </div>

        ${statusSelectHtml}
    </div>
`,
                        showCancelButton: false,
                        showDenyButton: !locked,
                        confirmButtonText: "{{ __('bookings.calendar.view_details') }}",
                        denyButtonText: "{{ __('bookings.calendar.save_status') }}",
                        // cancelButtonText: "{{ __('bookings.calendar.close') }}",
                        confirmButtonColor: '#009EF7',
                        denyButtonColor: '#50CD89',
                        width: '600px',
                        height: 'auto',
                        padding: '1rem',
                        showCloseButton: true,
                        customClass: {
                            popup: 'rounded-3',
                            confirmButton: 'btn btn-primary px-6',
                            denyButton: 'btn btn-success px-6',
                            cancelButton: 'btn btn-light px-6',
                        },
                        buttonsStyling: false,
                        didOpen: () => {
                            const sel = document.getElementById('swal_status_select');
                            if (sel) {
                                sel.addEventListener('change', function() {
                                    const wrap = document.getElementById(
                                        'swal_cancel_reason_wrap');
                                    if (this.value === 'cancelled') {
                                        wrap.innerHTML = `
                        <div class="mt-4">
                            <label class="form-label fw-bold text-gray-700 mb-2">{{ __('bookings.cancel_reason_title') }}</label>
                            <textarea id="swal_cancel_reason" class="form-control form-control-solid" rows="2"
                                placeholder="{{ __('bookings.cancel_reason_placeholder') }}"></textarea>
                        </div>`;
                                    } else {
                                        wrap.innerHTML = '';
                                    }
                                });
                            }
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = ev.url || ("{{ url('/dashboard/bookings') }}/" +
                                bookingId);
                            return;
                        }

                        if (result.isDenied) {
                            const newStatus = document.getElementById('swal_status_select')?.value;
                            if (!newStatus || newStatus === status) return;

                            let payload = {
                                _token: "{{ csrf_token() }}",
                                status: newStatus,
                            };

                            if (newStatus === 'cancelled') {
                                const reason = document.getElementById('swal_cancel_reason')?.value
                                    ?.trim();
                                if (reason) payload.cancel_reason = reason;
                            }

                            $.ajax({
                                url: "{{ url('/dashboard/bookings') }}/" + bookingId +
                                    "/status",
                                method: "PATCH",
                                data: payload,
                                success: function(res) {
                                    if (res.ok) {
                                        toastr.success(res.message ||
                                            "{{ __('bookings.status_updated') }}");
                                        calendar.refetchEvents();
                                    } else {
                                        toastr.error(res.message || 'Error');
                                    }
                                },
                                error: function(xhr) {
                                    toastr.error(xhr.responseJSON?.message ||
                                        "{{ __('bookings.status_update_failed') }}"
                                    );
                                }
                            });
                        }
                    });
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

            // ✅ ضبط عرض التقويم ديناميكياً
            function adjustCalendarWidth(resourceCount) {
                const resourceAreaWidth = 120; // عرض عمود الأسماء
                const columnWidth = 160; // عرض كل موظف
                const minColumns = 5; // أقل عدد أعمدة

                const totalWidth = resourceAreaWidth + (Math.max(resourceCount, minColumns) * columnWidth);
                document.getElementById('booking_calendar').style.minWidth = totalWidth + 'px';
            }

            // تحميل الموظفين + ضبط العرض
            $.get("{{ route('dashboard.bookings.calendar.resources') }}", function(res) {
                allResources = res;

                // ✅ ضبط العرض فور تحميل الموظفين
                adjustCalendarWidth(res.length);

                const $sel = $('#tb_employees');
                const $filterSel = $('#filter_employee');
                res.forEach(r => {
                    $sel.append(new Option(r.title, r.id, false, false));
                    $filterSel.append(`<option value="${r.id}">${r.title}</option>`);
                });
                if ($.fn.select2) $sel.trigger('change');
            });

            // ✅ إعادة الضبط عند تغيير فلتر الموظف
            $('#filter_employee').on('change', function() {
                const selectedId = $(this).val();
                const count = selectedId ? 1 : allResources.length;
                adjustCalendarWidth(count);

                calendar.refetchResources();
                calendar.refetchEvents();
            });

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