@php
    $user = auth()->user();
@endphp

<div class="dropdown">
    <button class="btn btn-sm btn-light btn-active-light-primary action-button" type="button" data-bs-toggle="dropdown"
        aria-expanded="false">
        <i class="fa-solid fa-ellipsis-vertical"></i>
    </button>

    <ul class="dropdown-menu dropdown-menu-end">

        @can('bookings.view')
            <li>
                <a class="dropdown-item" href="{{ route('dashboard.bookings.show', $booking->id) }}">
                    <i class="fa-solid fa-circle-info text-info me-2"></i>
                    <span>{{ __('reviews.actions.view_booking') }}</span>
                </a>
            </li>
        @endcan

        @can('reviews.delete')
            <li>
                <button type="button" class="dropdown-item js-delete-review" data-id="{{ $booking->id }}">
                    <i class="fa-regular fa-trash-can text-danger me-2"></i>
                    <span>{{ __('messages.actions-btn.delete') }}</span>
                </button>
            </li>
        @endcan

    </ul>
</div>