@extends('base.layout.app')

@section('title', __('promotional_notifications.show'))

@section('content')

@section('top-btns')
    {{-- @if ($notification->canBeEdited())
        <a href="{{ route('dashboard.promotional-notifications.edit', $notification->id) }}" class="btn btn-primary me-2">
            <i class="ki-duotone ki-pencil fs-2"></i>
            {{ __('promotional_notifications.actions.edit') }}
        </a>
    @endif --}}

    <div>

        @if ($notification->canBeSent())
            <button type="button" class="btn btn-success" id="send_now_btn">
                <i class="ki-duotone ki-send fs-2"></i>
                {{ __('promotional_notifications.actions.send_now') }}
            </button>
        @endif

        @if ($notification->canBeCancelled())
            <button type="button" class="btn btn-warning" id="cancel_btn">
                <i class="ki-duotone ki-cross-circle fs-2"></i>
                {{ __('promotional_notifications.actions.cancel') }}
            </button>
        @endif
    </div>
@endsection

<div class="row g-6">

    {{-- LEFT COLUMN --}}
    <div class="col-lg-8">

        {{-- Status Banner --}}
        @php
            $statusBadges = [
                'draft' => [
                    'class' => 'bg-light-secondary',
                    'icon' => 'document',
                    'text' => __('promotional_notifications.statuses.draft'),
                ],
                'scheduled' => [
                    'class' => 'bg-light-info',
                    'icon' => 'time',
                    'text' => __('promotional_notifications.statuses.scheduled'),
                ],
                'sending' => [
                    'class' => 'bg-light-warning',
                    'icon' => 'send',
                    'text' => __('promotional_notifications.statuses.sending'),
                ],
                'sent' => [
                    'class' => 'bg-light-success',
                    'icon' => 'check-circle',
                    'text' => __('promotional_notifications.statuses.sent'),
                ],
                'failed' => [
                    'class' => 'bg-light-danger',
                    'icon' => 'cross-circle',
                    'text' => __('promotional_notifications.statuses.failed'),
                ],
                'cancelled' => [
                    'class' => 'bg-light-dark',
                    'icon' => 'cross',
                    'text' => __('promotional_notifications.statuses.cancelled'),
                ],
            ];
            $statusInfo = $statusBadges[$notification->status] ?? $statusBadges['draft'];
        @endphp

        <div class="card {{ $statusInfo['class'] }} mb-6">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <i class="ki-duotone ki-{{ $statusInfo['icon'] }} fs-3x me-5">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <div class="flex-grow-1">
                        <div class="fs-3 fw-bold">{{ $statusInfo['text'] }}</div>
                        <div class="text-muted fw-semibold mt-1">
                            @if ($notification->status === 'scheduled' && $notification->scheduled_at)
                                {{ __('promotional_notifications.scheduled_for') }}:
                                {{ $notification->scheduled_at->format('Y-m-d H:i') }}
                                <span class="mx-2">•</span>
                                {{ $notification->scheduled_at->diffForHumans() }}
                            @elseif($notification->status === 'sent' && $notification->sent_at)
                                {{ __('promotional_notifications.sent_at_date') }}:
                                {{ $notification->sent_at->format('Y-m-d H:i') }}
                            @endif
                        </div>
                    </div>
                    <div class="fs-1 fw-bolder">#{{ $notification->id }}</div>
                </div>
            </div>
        </div>

        {{-- Content Card --}}
        <div class="card card-flush mb-6">
            <div class="card-header pt-6">
                <h3 class="card-title fw-bold">{{ __('promotional_notifications.content') }}</h3>
            </div>
            <div class="card-body pt-0">

                {{-- Tabs --}}
                <ul class="nav nav-tabs nav-line-tabs mb-6">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#tab_ar">
                            {{ __('promotional_notifications.lang.ar') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#tab_en">
                            {{ __('promotional_notifications.lang.en') }}
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    {{-- Arabic Tab --}}
                    <div class="tab-pane fade show active" id="tab_ar">
                        <div class="mb-6">
                            <div class="text-muted fw-semibold mb-2">{{ __('promotional_notifications.fields.title') }}
                            </div>
                            <div class="fs-3 fw-bold">{{ $notification->title['ar'] ?? '—' }}</div>
                        </div>

                        <div>
                            <div class="text-muted fw-semibold mb-2">{{ __('promotional_notifications.fields.body') }}
                            </div>
                            <div class="fs-5 fw-semibold bg-light p-5 rounded">{{ $notification->body['ar'] ?? '—' }}
                            </div>
                        </div>
                    </div>

                    {{-- English Tab --}}
                    <div class="tab-pane fade" id="tab_en">
                        <div class="mb-6">
                            <div class="text-muted fw-semibold mb-2">{{ __('promotional_notifications.fields.title') }}
                            </div>
                            <div class="fs-3 fw-bold">{{ $notification->title['en'] ?? '—' }}</div>
                        </div>

                        <div>
                            <div class="text-muted fw-semibold mb-2">{{ __('promotional_notifications.fields.body') }}
                            </div>
                            <div class="fs-5 fw-semibold bg-light p-5 rounded">{{ $notification->body['en'] ?? '—' }}
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- Link Target Card --}}
        @if ($notification->linkable_type && $notification->linkable_id)
            <div class="card card-flush mb-6">
                <div class="card-header pt-6">
                    <h3 class="card-title fw-bold">{{ __('promotional_notifications.fields.linkable') }}</h3>
                </div>
                <div class="card-body pt-0">
                    <div class="d-flex align-items-center gap-4">
                        <div class="symbol symbol-50px">
                            <div class="symbol-label bg-light-primary">
                                <i class="ki-duotone ki-abstract-26 fs-2x text-primary">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="text-muted fw-semibold">{{ __('promotional_notifications.link_type') }}</div>
                            <div class="fw-bold fs-5">{{ class_basename($notification->linkable_type) }}</div>
                            <div class="text-muted">ID: {{ $notification->linkable_id }}</div>
                            @if ($notification->linkable)
                                @php
                                    $linkableName = '';
                                    if (isset($notification->linkable->name)) {
                                        $linkableName = is_array($notification->linkable->name)
                                            ? $notification->linkable->name[app()->getLocale()] ??
                                                ($notification->linkable->name['ar'] ??
                                                    ($notification->linkable->name['en'] ?? ''))
                                            : $notification->linkable->name;
                                    }
                                @endphp
                                @if ($linkableName)
                                    <div class="badge badge-light-primary mt-2">{{ $linkableName }}</div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Internal Notes --}}
        @if ($notification->internal_notes)
            <div class="card card-flush">
                <div class="card-header pt-6">
                    <h3 class="card-title fw-bold">{{ __('promotional_notifications.fields.internal_notes') }}</h3>
                </div>
                <div class="card-body pt-0">
                    <div class="bg-light p-5 rounded">
                        {{ $notification->internal_notes }}
                    </div>
                </div>
            </div>
        @endif

    </div>

    {{-- RIGHT COLUMN --}}
    <div class="col-lg-4">

        {{-- Statistics Card --}}
        @if ($notification->status === 'sent')
            <div class="card card-flush mb-6">
                <div class="card-header pt-6">
                    <h3 class="card-title fw-bold">{{ __('promotional_notifications.statistics') }}</h3>
                </div>
                <div class="card-body pt-0">

                    {{-- Total Recipients --}}
                    <div class="mb-6">
                        <div class="d-flex align-items-center mb-2">
                            <i class="ki-duotone ki-user fs-2 text-gray-600 me-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <div class="text-muted fw-semibold">
                                {{ __('promotional_notifications.fields.total_recipients') }}</div>
                        </div>
                        <div class="fs-2hx fw-bold">{{ number_format($notification->total_recipients) }}</div>
                    </div>

                    <div class="separator my-4"></div>

                    {{-- Successful Sends --}}
                    <div class="mb-6">
                        <div class="d-flex align-items-center mb-2">
                            <i class="ki-duotone ki-check-circle fs-2 text-success me-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <div class="text-muted fw-semibold">
                                {{ __('promotional_notifications.fields.successful_sends') }}</div>
                        </div>
                        <div class="fs-2hx fw-bold text-success">{{ number_format($notification->successful_sends) }}
                        </div>
                    </div>

                    {{-- Failed Sends --}}
                    @if ($notification->failed_sends > 0)
                        <div class="mb-6">
                            <div class="d-flex align-items-center mb-2">
                                <i class="ki-duotone ki-cross-circle fs-2 text-danger me-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <div class="text-muted fw-semibold">
                                    {{ __('promotional_notifications.fields.failed_sends') }}</div>
                            </div>
                            <div class="fs-2hx fw-bold text-danger">{{ number_format($notification->failed_sends) }}
                            </div>
                        </div>
                    @endif

                    <div class="separator my-4"></div>

                    {{-- Success Rate --}}
                    <div>
                        <div class="d-flex align-items-center mb-2">
                            <i class="ki-duotone ki-chart-simple fs-2 text-primary me-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                            </i>
                            <div class="text-muted fw-semibold">
                                {{ __('promotional_notifications.fields.success_rate') }}</div>
                        </div>
                        <div class="d-flex align-items-baseline">
                            <div class="fs-2hx fw-bold text-primary me-2">
                                {{ number_format($notification->success_rate, 2) }}%</div>
                        </div>
                        <div class="progress h-8px bg-light-primary mt-3">
                            <div class="progress-bar bg-primary" role="progressbar"
                                style="width: {{ $notification->success_rate }}%">
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        @endif

        {{-- Target Audience Card --}}
        <div class="card card-flush mb-6">
            <div class="card-header pt-6">
                <h3 class="card-title fw-bold">{{ __('promotional_notifications.target_audience') }}</h3>
            </div>
            <div class="card-body pt-0">

                @php
                    $targetBadges = [
                        'all_users' => 'badge-light-success',
                        'specific_users' => 'badge-light-primary',
                    ];
                    $targetBadge = $targetBadges[$notification->target_type] ?? 'badge-light-secondary';
                @endphp

                <div class="mb-4">
                    <div class="text-muted fw-semibold mb-2">{{ __('promotional_notifications.fields.target_type') }}
                    </div>
                    <span class="badge {{ $targetBadge }} fs-5">
                        {{ __('promotional_notifications.target_types.' . $notification->target_type) }}
                    </span>
                </div>

                @if ($notification->target_type === 'specific_users' && $notification->target_user_ids)
                    <div class="separator my-4"></div>
                    <div>
                        <div class="text-muted fw-semibold mb-2">
                            {{ __('promotional_notifications.selected_users_count') }}</div>
                        <div class="fs-2hx fw-bold">{{ count($notification->target_user_ids) }}</div>
                    </div>
                @endif

            </div>
        </div>

        {{-- Timeline Card --}}
        <div class="card card-flush mb-6">
            <div class="card-header pt-6">
                <h3 class="card-title fw-bold">{{ __('promotional_notifications.timeline') }}</h3>
            </div>
            <div class="card-body pt-0">

                <div class="timeline">
                    {{-- Created --}}
                    <div class="timeline-item mb-6">
                        <div class="timeline-line w-40px"></div>
                        <div class="timeline-icon symbol symbol-circle symbol-40px">
                            <div class="symbol-label bg-light-primary">
                                <i class="ki-duotone ki-plus fs-2 text-primary">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>
                        <div class="timeline-content mb-10 mt-n1">
                            <div class="fw-semibold text-gray-800">{{ __('promotional_notifications.created') }}</div>
                            <div class="text-muted">
                                {{ $notification->created_at->format('Y-m-d H:i') }}
                                <span class="mx-1">•</span>
                                {{ $notification->created_at->diffForHumans() }}
                            </div>
                            @if ($notification->creator)
                                <div class="text-muted fs-7 mt-1">
                                    {{ __('promotional_notifications.by') }}: {{ $notification->creator->name }}
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Scheduled --}}
                    @if ($notification->scheduled_at)
                        <div class="timeline-item mb-6">
                            <div class="timeline-line w-40px"></div>
                            <div class="timeline-icon symbol symbol-circle symbol-40px">
                                <div class="symbol-label bg-light-info">
                                    <i class="ki-duotone ki-time fs-2 text-info">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                            </div>
                            <div class="timeline-content mb-10 mt-n1">
                                <div class="fw-semibold text-gray-800">{{ __('promotional_notifications.scheduled') }}
                                </div>
                                <div class="text-muted">
                                    {{ $notification->scheduled_at->format('Y-m-d H:i') }}
                                    <span class="mx-1">•</span>
                                    {{ $notification->scheduled_at->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Sent --}}
                    @if ($notification->sent_at)
                        <div class="timeline-item">
                            <div class="timeline-line w-40px"></div>
                            <div class="timeline-icon symbol symbol-circle symbol-40px">
                                <div class="symbol-label bg-light-success">
                                    <i class="ki-duotone ki-check fs-2 text-success">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                            </div>
                            <div class="timeline-content mt-n1">
                                <div class="fw-semibold text-gray-800">{{ __('promotional_notifications.sent') }}
                                </div>
                                <div class="text-muted">
                                    {{ $notification->sent_at->format('Y-m-d H:i') }}
                                    <span class="mx-1">•</span>
                                    {{ $notification->sent_at->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

            </div>
        </div>

        {{-- Metadata Card --}}
        <div class="card card-flush">
            <div class="card-header pt-6">
                <h3 class="card-title fw-bold">{{ __('promotional_notifications.metadata') }}</h3>
            </div>
            <div class="card-body pt-0">

                <div class="mb-4">
                    <div class="text-muted fw-semibold mb-1">{{ __('promotional_notifications.id') }}</div>
                    <div class="fw-bold">#{{ $notification->id }}</div>
                </div>

                <div class="mb-4">
                    <div class="text-muted fw-semibold mb-1">{{ __('promotional_notifications.fields.created_at') }}
                    </div>
                    <div class="fw-bold">{{ $notification->created_at->format('Y-m-d H:i') }}</div>
                </div>

                <div>
                    <div class="text-muted fw-semibold mb-1">{{ __('promotional_notifications.updated_at') }}</div>
                    <div class="fw-bold">{{ $notification->updated_at->format('Y-m-d H:i') }}</div>
                </div>

            </div>
        </div>

    </div>

</div>

@endsection

@push('custom-script')
<script>
    (function() {
        const isAr = document.documentElement.lang === 'ar';
        const notificationId = {{ $notification->id }};

        // Send Now
        $('#send_now_btn').on('click', function() {
            Swal.fire({
                title: isAr ? 'تأكيد الإرسال' : 'Confirm Send',
                text: "{{ __('promotional_notifications.messages.confirm_send') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: isAr ? 'نعم، أرسل الآن' : 'Yes, Send Now',
                cancelButtonText: isAr ? 'إلغاء' : 'Cancel',
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-success',
                    cancelButton: 'btn btn-secondary'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post(`/dashboard/promotional-notifications/${notificationId}/send`, {
                            _token: '{{ csrf_token() }}'
                        })
                        .done(function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: isAr ? 'تم الإرسال' : 'Sent',
                                text: response.message ||
                                    "{{ __('promotional_notifications.messages.sent_successfully') }}",
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => window.location.reload());
                        })
                        .fail(function(xhr) {
                            Swal.fire('Error', xhr.responseJSON?.message || 'Failed to send',
                                'error');
                        });
                }
            });
        });

        // Cancel
        $('#cancel_btn').on('click', function() {
            Swal.fire({
                title: isAr ? 'تأكيد الإلغاء' : 'Confirm Cancel',
                text: "{{ __('promotional_notifications.messages.confirm_cancel') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: isAr ? 'نعم، ألغ' : 'Yes, Cancel',
                cancelButtonText: isAr ? 'رجوع' : 'Back',
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-warning',
                    cancelButton: 'btn btn-secondary'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post(`/dashboard/promotional-notifications/${notificationId}/cancel`, {
                            _token: '{{ csrf_token() }}'
                        })
                        .done(function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: isAr ? 'تم الإلغاء' : 'Cancelled',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => window.location.reload());
                        })
                        .fail(function(xhr) {
                            Swal.fire('Error', xhr.responseJSON?.message || 'Failed to cancel',
                                'error');
                        });
                }
            });
        });
    })();
</script>
@endpush
