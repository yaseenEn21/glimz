@php
    $notificationsTitle = __('notifications.title');
@endphp

<div class="menu menu-sub menu-sub-dropdown menu-column w-350px" data-kt-menu="true" id="kt_menu_notifications">
    <div class="d-flex align-items-center justify-content-between px-6 py-4 border-bottom">
        <h3 class="mb-0 fs-5 fw-semibold">{{ $notificationsTitle }}</h3>
        <a href="{{ route('dashboard.notifications.index') }}" class="btn btn-sm btn-light-primary">
            {{ __('notifications.view_all') }}
        </a>
    </div>

    <div class="scroll-y mh-325px">
        @if ($webNotificationsLatest->isEmpty())
            <div class="px-6 py-8 text-center text-muted">
                {{ __('notifications.empty_list') }}
            </div>
        @else
            @foreach ($webNotificationsLatest as $n)
                <a href="{{ $n->data['url'] ?? '#' }}" data-id="{{ $n->id }}"
                    data-open-url="{{ $n->data['url'] ?? '' }}"
                    class="notification-link px-6 py-4 d-flex flex-column border-bottom text-reset text-decoration-none {{ $n->is_read ? '' : 'bg-light-primary' }}">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-semibold fs-6 text-dark">
                            {{ $n->title }}
                        </span>
                        <span class="text-muted fs-8">
                            {{ $n->created_at?->diffForHumans() }}
                        </span>
                    </div>
                    <div class="text-muted fs-7">
                        {{ \Illuminate\Support\Str::limit($n->body, 100) }}
                    </div>
                </a>
            @endforeach
        @endif
    </div>

</div>