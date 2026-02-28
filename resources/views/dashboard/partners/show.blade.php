@extends('base.layout.app')

@section('title', __('partners.show'))

@section('content')

    <div class="row g-6">
        {{-- Left Column - Partner Details --}}
        <div class="col-lg-8">
            {{-- Basic Info --}}
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="card-title">{{ __('partners.show') }}</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-6">
                        @if ($partner->is_active)
                            <span class="badge badge-light-success fs-5">{{ __('partners.active') }}</span>
                        @else
                            <span class="badge badge-light-danger fs-5">{{ __('partners.inactive') }}</span>
                        @endif
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="fw-semibold text-muted mb-1">{{ __('partners.fields.name') }}</div>
                            <div class="fw-bold fs-5">{{ $partner->name }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="fw-semibold text-muted mb-1">{{ __('partners.fields.username') }}</div>
                            <div class="fw-bold fs-5">
                                <span class="badge badge-light-info fs-6">{{ $partner->username }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="separator my-4"></div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="fw-semibold text-muted mb-1">
                                <i class="ki-duotone ki-sms fs-4 me-1">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                {{ __('partners.fields.email') }}
                            </div>
                            <div class="fw-bold">{{ $partner->email }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="fw-semibold text-muted mb-1">
                                <i class="ki-duotone ki-phone fs-4 me-1">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                {{ __('partners.fields.mobile') }}
                            </div>
                            <div class="fw-bold">{{ $partner->mobile ?? '—' }}</div>
                        </div>
                    </div>

                    <div class="separator my-4"></div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="fw-semibold text-muted mb-1">
                                <i class="ki-duotone ki-calendar fs-4 me-1">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                {{ __('partners.fields.daily_booking_limit') }}
                            </div>
                            <div class="fw-bold">
                                <span class="badge badge-light-primary fs-4">
                                    {{ number_format($partner->daily_booking_limit) }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="fw-semibold text-muted mb-1">
                                <i class="ki-duotone ki-globe fs-4 me-1">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                {{ __('partners.fields.webhook_url') }}
                            </div>
                            <div class="fw-bold">
                                @if ($partner->webhook_url)
                                    <a href="{{ $partner->webhook_url }}" target="_blank" class="text-primary">
                                        {{ Str::limit($partner->webhook_url, 40) }}
                                    </a>
                                @else
                                    —
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="separator my-4"></div>

                    {{-- Slot Matching Settings --}}
                    <div class="row mb-4">
                        <div class="col-12 mb-3">
                            <div class="fw-semibold text-muted mb-1">
                                <i class="ki-duotone ki-time fs-4 me-1">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                {{ app()->getLocale() === 'ar' ? 'مطابقة المواعيد' : 'Slot Matching' }}
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="fw-semibold text-muted mb-1 fs-7">
                                {{ app()->getLocale() === 'ar' ? 'مواعيد غير مطابقة' : 'Non-exact matching' }}
                            </div>
                            <div class="fw-bold">
                                @if ($partner->allow_slot_fallback)
                                    <span class="badge badge-light-success">
                                        {{ app()->getLocale() === 'ar' ? 'مسموح' : 'Allowed' }}
                                    </span>
                                @else
                                    <span class="badge badge-light-danger">
                                        {{ app()->getLocale() === 'ar' ? 'مطابقة دقيقة فقط' : 'Exact only' }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        @if ($partner->allow_slot_fallback)
                            <div class="col-md-4">
                                <div class="fw-semibold text-muted mb-1 fs-7">
                                    {{ app()->getLocale() === 'ar' ? 'الفرق المسموح' : 'Allowed difference' }}
                                </div>
                                <div class="fw-bold">
                                    <span class="badge badge-light-primary">
                                        {{ $partner->slot_fallback_minutes }}
                                        {{ app()->getLocale() === 'ar' ? 'دقيقة' : 'min' }}
                                    </span>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="fw-semibold text-muted mb-1 fs-7">
                                    {{ app()->getLocale() === 'ar' ? 'اتجاه البحث' : 'Direction' }}
                                </div>
                                <div class="fw-bold">
                                    @php
                                        $dirLabels = [
                                            'both' => app()->getLocale() === 'ar' ? 'قبل وبعد' : 'Before & After',
                                            'after' => app()->getLocale() === 'ar' ? 'بعد فقط' : 'After only',
                                            'before' => app()->getLocale() === 'ar' ? 'قبل فقط' : 'Before only',
                                        ];
                                    @endphp
                                    <span class="badge badge-light-info">
                                        {{ $dirLabels[$partner->slot_fallback_direction] ?? $partner->slot_fallback_direction }}
                                    </span>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="fw-semibold text-muted mb-1">{{ __('partners.fields.created_at') }}</div>
                    <div class="fw-bold">{{ $partner->created_at->format('Y-m-d H:i') }}</div>
                </div>
            </div>

            {{-- Assigned Services --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('partners.assigned_services') }}</h3>
                    <div class="card-toolbar">
                        @can('partners.assign_services')
                            <a href="{{ route('dashboard.partners.assign-services', $partner) }}"
                                class="btn btn-sm btn-light-primary">
                                <i class="ki-duotone ki-pencil fs-4"></i>
                                {{ __('partners.edit_services') }}
                            </a>
                        @endcan
                    </div>
                </div>
                <div class="card-body pt-0">
                    @forelse($servicesWithEmployees as $item)
                        <div class="border border-gray-300 border-dashed rounded p-4 mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="symbol symbol-50px me-3">
                                    <div class="symbol-label bg-light-success">
                                        <i class="ki-duotone ki-wrench fs-2x text-success">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold fs-5">
                                        {{ is_array($item['service']->name)
                                            ? $item['service']->name[app()->getLocale()] ?? $item['service']->name['ar']
                                            : $item['service']->name }}
                                    </div>
                                    <div class="text-muted fs-7">
                                        {{ $item['employees']->count() }} {{ __('partners.employees') }}
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-2">
                                @foreach ($item['employees'] as $employee)
                                    <span class="badge badge-light-primary">
                                        <i class="ki-duotone ki-user fs-5 me-1">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        {{ $employee->user->name ?? 'N/A' }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-10">
                            <div class="text-gray-600 fs-5 mb-3">{{ __('partners.no_services_assigned') }}</div>
                            @can('partners.assign_services')
                                <a href="{{ route('dashboard.partners.assign-services', $partner) }}" class="btn btn-primary">
                                    <i class="ki-duotone ki-plus fs-2"></i>
                                    {{ __('partners.actions.assign_services') }}
                                </a>
                            @endcan
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Right Column - API Token --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('partners.fields.api_token') }}</h3>
                </div>
                <div class="card-body">
                    {{-- ✅ رابط API Documentation --}}
                    <div class="alert alert-primary d-flex align-items-center mb-5">
                        <div class="d-flex flex-column flex-grow-1">
                            <h5 class="mb-1">{{ __('partners.api_documentation') }}</h5>
                            <span class="text-gray-700 fw-semibold fs-6">
                                {{ __('partners.api_documentation_desc') }}
                            </span>
                            <a href="https://documenter.getpostman.com/view/26698513/2sBXc7KPVq" target="_blank"
                                class="btn btn-sm btn-light-primary mt-3">
                                <i class="ki-duotone ki-exit-right-corner fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                {{ __('partners.view_documentation') }}
                            </a>
                        </div>
                    </div>

                    <div class="separator my-5"></div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Token</label>
                        <div class="input-group">
                            <input type="password" id="apiToken" class="form-control form-control-solid"
                                value="{{ $partner->api_token }}" readonly>
                            <button type="button" class="btn btn-light-primary d-none" id="toggleToken"
                                title="{{ __('partners.actions.show_token') }}">
                                <i class="ki-duotone ki-eye fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                </i>
                            </button>
                            <button type="button" class="btn btn-primary ms-2" id="copyToken"
                                title="{{ __('partners.actions.copy_token') }}">
                                <i class="ki-duotone ki-copy fs-2 pe-0">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                    <span class="path5"></span>
                                </i>
                            </button>
                        </div>
                        <div class="form-text mt-2" id="copyMessage"></div>
                    </div>

                    @can('partners.regenerate_token')
                        <button type="button" class="btn btn-danger w-100" id="regenerateToken">
                            <i class="ki-duotone ki-arrows-circle fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            {{ __('partners.actions.regenerate_token') }}
                        </button>
                    @endcan
                </div>
            </div>
        </div>

        {{-- Partner Bookings Section --}}
        <div class="col-12 mt-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('partners.bookings_title') }}</h3>
                </div>
                <div class="card-body">

                    {{-- ✅ إحصائيات --}}
                    <div class="row g-4 mb-6">
                        <div class="col-md-6 col-xl-3">
                            <div class="card card-flush h-100">
                                <div class="card-body d-flex flex-column justify-content-between">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="fs-2hx fw-bold text-gray-800 me-2" id="stat-total">
                                            {{ number_format($bookingsStats['total']) }}
                                        </span>
                                    </div>
                                    <span
                                        class="fs-6 fw-semibold text-gray-400">{{ __('partners.stats.total_bookings') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-3">
                            <div class="card card-flush h-100" style="background-color: #FFF4DE;">
                                <div class="card-body d-flex flex-column justify-content-between">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="fs-2hx fw-bold text-warning me-2" id="stat-pending">
                                            {{ number_format($bookingsStats['pending']) }}
                                        </span>
                                    </div>
                                    <span
                                        class="fs-6 fw-semibold text-gray-600">{{ __('bookings.status.pending') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-3">
                            <div class="card card-flush h-100" style="background-color: #E8F5E9;">
                                <div class="card-body d-flex flex-column justify-content-between">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="fs-2hx fw-bold text-success me-2" id="stat-completed">
                                            {{ number_format($bookingsStats['completed']) }}
                                        </span>
                                    </div>
                                    <span
                                        class="fs-6 fw-semibold text-gray-600">{{ __('bookings.status.completed') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-3">
                            <div class="card card-flush h-100" style="background-color: #FFEBEE;">
                                <div class="card-body d-flex flex-column justify-content-between">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="fs-2hx fw-bold text-danger me-2" id="stat-cancelled">
                                            {{ number_format($bookingsStats['cancelled']) }}
                                        </span>
                                    </div>
                                    <span
                                        class="fs-6 fw-semibold text-gray-600">{{ __('bookings.status.cancelled') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ✅ Filters --}}
                    <div class="card mb-5 bg-light">
                        <div class="card-body">
                            <div class="row g-4 align-items-center">
                                <div class="col-lg-4">
                                    <input type="text" id="partner_booking_search" class="form-control"
                                        placeholder="{{ __('bookings.filters.search_placeholder') }}">
                                </div>

                                <div class="col-lg-3">
                                    <select id="partner_booking_status" class="form-select">
                                        <option value="">{{ __('bookings.filters.status_placeholder') }}</option>
                                        <option value="pending">{{ __('bookings.status.pending') }}</option>
                                        <option value="confirmed">{{ __('bookings.status.confirmed') }}</option>
                                        <option value="moving">{{ __('bookings.status.moving') }}</option>
                                        <option value="arrived">{{ __('bookings.status.arrived') }}</option>
                                        <option value="completed">{{ __('bookings.status.completed') }}</option>
                                        <option value="cancelled">{{ __('bookings.status.cancelled') }}</option>
                                    </select>
                                </div>

                                <div class="col-lg-2">
                                    <input type="date" id="partner_booking_from" class="form-control"
                                        placeholder="{{ __('bookings.filters.from') }}">
                                </div>

                                <div class="col-lg-2">
                                    <input type="date" id="partner_booking_to" class="form-control"
                                        placeholder="{{ __('bookings.filters.to') }}">
                                </div>

                                <div class="col-lg-1">
                                    <button type="button" id="partner_booking_reset"
                                        class="btn btn-light-primary w-100">
                                        <i class="fa-solid fa-rotate-right p-0"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ✅ DataTable --}}
                    <div class="table-responsive">
                        <table id="partner_bookings_table" class="table align-middle table-row-dashed fs-6 gy-5">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>#</th>
                                    <th>{{ __('partners.external_id') }}</th>
                                    <th>{{ __('bookings.columns.customer') }}</th>
                                    <th>{{ __('bookings.columns.service') }}</th>
                                    <th>{{ __('bookings.columns.schedule') }}</th>
                                    <th>{{ __('bookings.columns.employee') }}</th>
                                    <th>{{ __('bookings.columns.total') }}</th>
                                    <th>{{ __('bookings.columns.status') }}</th>
                                    <th class="text-end">{{ __('bookings.columns.actions') }}</th>
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
        // Toggle Token Visibility
        const tokenInput = document.getElementById('apiToken');
        const toggleBtn = document.getElementById('toggleToken');

        toggleBtn.addEventListener('click', function() {
            if (tokenInput.type === 'password') {
                tokenInput.type = 'text';
                toggleBtn.innerHTML =
                    '<i class="ki-duotone ki-eye-slash fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>';
            } else {
                tokenInput.type = 'password';
                toggleBtn.innerHTML =
                    '<i class="ki-duotone ki-eye fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>';
            }
        });

        // Copy Token
        document.getElementById('copyToken').addEventListener('click', function() {
            // استخدام Clipboard API الحديث
            navigator.clipboard.writeText(tokenInput.value).then(function() {
                const message = document.getElementById('copyMessage');
                message.textContent = '{{ __('partners.token_copied') }}';
                message.classList.add('text-success', 'fw-bold');

                // تغيير لون الزر مؤقتاً
                const btn = document.getElementById('copyToken');
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-success');

                setTimeout(() => {
                    message.textContent = '';
                    message.classList.remove('text-success', 'fw-bold');
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-primary');
                }, 2000);
            }).catch(function(err) {
                console.error('Failed to copy:', err);
                // Fallback للطريقة القديمة
                tokenInput.select();
                document.execCommand('copy');

                const message = document.getElementById('copyMessage');
                message.textContent = '{{ __('partners.token_copied') }}';
                message.classList.add('text-success', 'fw-bold');

                setTimeout(() => {
                    message.textContent = '';
                    message.classList.remove('text-success', 'fw-bold');
                }, 2000);
            });
        });

        // Regenerate Token
        @can('partners.regenerate_token')
            const regenerateBtn = document.getElementById('regenerateToken');
            if (regenerateBtn) {
                regenerateBtn.addEventListener('click', function() {
                    if (!confirm('{{ __('partners.regenerate_token_confirm') }}')) {
                        return;
                    }

                    const btn = this;
                    const originalHTML = btn.innerHTML;
                    btn.disabled = true;
                    btn.innerHTML =
                        '<span class="spinner-border spinner-border-sm me-2"></span>{{ __('partners.loading') }}...';

                    fetch('{{ route('dashboard.partners.regenerate-token', $partner) }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            }
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                tokenInput.value = data.token;
                                tokenInput.type = 'password'; // إخفاء Token الجديد

                                // رسالة نجاح
                                const message = document.getElementById('copyMessage');
                                message.textContent = data.message;
                                message.classList.add('text-success', 'fw-bold');

                                setTimeout(() => {
                                    message.textContent = '';
                                    message.classList.remove('text-success', 'fw-bold');
                                }, 3000);
                            } else {
                                alert('{{ __('partners.error') }}: ' + (data.message || 'Unknown error'));
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('{{ __('partners.error') }}: ' + error.message);
                        })
                        .finally(() => {
                            btn.disabled = false;
                            btn.innerHTML = originalHTML;
                        });
                });
            }
        @endcan

        // ✅ Partner Bookings DataTable
        (function() {
            const partnerId = {{ $partner->id }};

            const table = window.KH.initAjaxDatatable({
                tableId: 'partner_bookings_table',
                ajaxUrl: '{{ route('dashboard.partners.bookings.datatable', $partner) }}',
                languageUrl: dtLangUrl,
                searchInputId: 'partner_booking_search',
                columns: [{
                        data: 'id',
                        name: 'id',
                        title: "#"
                    },
                    {
                        data: 'external_id_display',
                        name: 'external_id',
                        title: "{{ __('partners.external_id') }}",
                        orderable: false,
                        searchable: true
                    },
                    {
                        data: 'customer',
                        name: 'user_id',
                        title: "{{ __('bookings.columns.customer') }}",
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'service_name',
                        name: 'service_id',
                        title: "{{ __('bookings.columns.service') }}",
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'schedule',
                        name: 'booking_date',
                        title: "{{ __('bookings.columns.schedule') }}",
                        orderable: true,
                        searchable: false
                    },
                    {
                        data: 'employee_label',
                        name: 'employee_id',
                        title: "{{ __('bookings.columns.employee') }}",
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'total',
                        name: 'total_snapshot',
                        title: "{{ __('bookings.columns.total') }}",
                        searchable: false
                    },
                    {
                        data: 'status_badge',
                        name: 'status',
                        title: "{{ __('bookings.columns.status') }}",
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        className: 'text-end',
                        title: '{{ __('datatable.lbl_actions') }}',
                        orderable: false,
                        searchable: false
                    }
                ],
                extraData: function(d) {
                    d.status = $('#partner_booking_status').val();
                    d.from = $('#partner_booking_from').val();
                    d.to = $('#partner_booking_to').val();
                }
            });

            // ✅ تحديث الإحصائيات
            function updateStats() {
                $.ajax({
                    url: '{{ route('dashboard.partners.bookings.stats', $partner) }}',
                    data: {
                        status: $('#partner_booking_status').val(),
                        from: $('#partner_booking_from').val(),
                        to: $('#partner_booking_to').val()
                    },
                    success: function(data) {
                        $('#stat-total').text(data.total.toLocaleString());
                        $('#stat-pending').text(data.pending.toLocaleString());
                        $('#stat-completed').text(data.completed.toLocaleString());
                        $('#stat-cancelled').text(data.cancelled.toLocaleString());
                    }
                });
            }

            // ✅ Event Listeners
            $('#partner_booking_search').on('keyup', function() {
                table.ajax.reload();
            });

            $('#partner_booking_status, #partner_booking_from, #partner_booking_to').on('change', function() {
                table.ajax.reload();
                updateStats();
            });

            $('#partner_booking_reset').on('click', function() {
                $('#partner_booking_search').val('');
                $('#partner_booking_status').val('');
                $('#partner_booking_from').val('');
                $('#partner_booking_to').val('');
                table.ajax.reload();
                updateStats();
            });
        })();
    </script>
@endpush
