<div class="d-flex justify-content-end gap-2">
    @can('cancel_reasons.edit')
        <a href="{{ route('dashboard.bookings.cancel-reasons.edit', $row['id']) }}"
           class="btn btn-sm btn-icon btn-light-primary">
            <i class="ki-duotone ki-pencil fs-2"><span class="path1"></span><span class="path2"></span></i>
        </a>
    @endcan

    @can('cancel_reasons.delete')
        <button type="button"
                class="btn btn-sm btn-icon btn-light-danger js-delete-cancel-reason"
                data-id="{{ $row['id'] }}">
            <i class="ki-duotone ki-trash fs-2"><span class="path1"></span><span class="path2"></span></i>
        </button>
    @endcan
</div>