{{-- Bookings actions --}}
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
                    <span>{{ __('messages.actions-btn.view') }}</span>
                </a>
            </li>
        @endcan

        {{-- ✅ نسخ معلومات الحجز --}}
        <li>
            <button type="button" class="dropdown-item js-copy-booking-info" data-booking-id="{{ $booking->id }}"
                data-service-name="{{ is_array($booking->service?->name) ? $booking->service->name[app()->getLocale()] ?? ($booking->service->name['ar'] ?? '') : $booking->service?->name }}"
                data-booking-date="{{ $booking->booking_date ? \Carbon\Carbon::parse($booking->booking_date)->format('Y/m/d') : '—' }}"
                data-start-time="{{ $booking->start_time ? \Carbon\Carbon::parse($booking->start_time)->format('h:i A') : '—' }}"
                data-customer-name="{{ $booking->user?->name ?? '—' }}"
                data-customer-mobile="{{ $booking->user?->mobile ?? '—' }}"
                data-address="{{ $booking->address?->address_line ?? '—' }}"
                data-lat="{{ $booking->address?->lat ?? '' }}" data-lng="{{ $booking->address?->lng ?? '' }}"
                data-plate="{{ trim(($booking->car?->plate_number ?? '') . ' ' . ($booking->car?->plate_letters ?? '')) }}"
                data-car-color="{{ $booking->car?->color ?? '—' }}"
                data-car-make="{{ is_array($booking->car?->make?->name) ? $booking->car->make->name[app()->getLocale()] ?? ($booking->car->make->name['ar'] ?? '') : $booking->car?->make?->name ?? '—' }}"
                data-car-model="{{ is_array($booking->car?->model?->name) ? $booking->car->model->name[app()->getLocale()] ?? ($booking->car->model->name['ar'] ?? '') : $booking->car?->model?->name ?? '—' }}"
                data-products="{{ $booking->products->map(function ($bp) {
                        $title = is_array($bp->title) ? $bp->title[app()->getLocale()] ?? ($bp->title['ar'] ?? '') : $bp->title;
                        return $title . ($bp->qty > 1 ? ' (x' . $bp->qty . ')' : '');
                    })->filter()->implode(', ') }}">
                <i class="fa-solid fa-copy text-primary me-2"></i>
                <span>{{ __('bookings.copy_info') }}</span>
            </button>
        </li>

        @can('bookings.edit')
            @if ($booking->status !== 'completed' && $booking->status !== 'cancelled')
                <li>
                    <a class="dropdown-item" href="{{ route('dashboard.bookings.edit', $booking->id) }}">
                        <i class="fa-solid fa-pen text-warning me-2"></i>
                        <span>{{ __('messages.actions-btn.edit') }}</span>
                    </a>
                </li>
            @endif
        @endcan

        @can('bookings.delete')
            @if ($booking->status !== 'completed')
                <li>
                    <button type="button" class="dropdown-item js-delete-booking" data-id="{{ $booking->id }}">
                        <i class="fa-regular fa-trash-can text-danger me-2"></i>
                        <span>{{ __('messages.actions-btn.delete') }}</span>
                    </button>
                </li>
            @endif
        @endcan

    </ul>
</div>
