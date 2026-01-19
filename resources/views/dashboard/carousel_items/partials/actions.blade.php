<div class="d-flex justify-content-end gap-2">
    @can('carousel_items.view')
        <a href="{{ route('dashboard.carousel-items.show', $item->id) }}" class="btn btn-icon btn-light-info btn-sm">
            <i class="ki-duotone ki-eye fs-2">
                <span class="path1"></span>
                <span class="path2"></span>
                <span class="path3"></span>
            </i>
        </a>
    @endcan

    @can('carousel_items.edit')
        <a href="{{ route('dashboard.carousel-items.edit', $item->id) }}" class="btn btn-icon btn-light-warning btn-sm">
            <i class="ki-duotone ki-pencil fs-2">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
        </a>
    @endcan

    @can('carousel_items.delete')
        <button type="button" class="btn btn-icon btn-light-danger btn-sm" data-kt-delete data-url="{{ route('dashboard.carousel-items.destroy', $item->id) }}">
            <i class="ki-duotone ki-trash fs-2">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
        </button>
    @endcan
</div>