<div class="d-flex justify-content-end gap-2">

    @can('bookings.view')
        <a href="{{ route('dashboard.bookings.show', $booking->id) }}" class="btn btn-icon btn-light-info btn-sm">
            <i class="ki-duotone ki-eye fs-2">
                <span class="path1"></span>
                <span class="path2"></span>
                <span class="path3"></span>
            </i>
        </a>
    @endcan

    @can('bookings.edit')
        @if ($booking->status !== 'completed' && $booking->status !== 'cancelled')
            <a href="{{ route('dashboard.bookings.edit', $booking->id) }}" class="btn btn-icon btn-light-warning btn-sm"
                data-bs-toggle="tooltip" title="{{ __('bookings.edit') }}">
                <i class="ki-duotone ki-pencil fs-2">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </a>
        @endif
    @endcan

    @can('bookings.delete')
        @if ($booking->status !== 'completed')
            <button type="button" class="btn btn-icon btn-light-danger btn-sm js-delete-booking"
                data-id="{{ $booking->id }}">
                <i class="ki-duotone ki-trash fs-2">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </button>
        @endif
    @endcan


</div>
