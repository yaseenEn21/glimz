<div class="d-flex gap-2 justify-content-end">

    @can('customers.view')
        <a href="{{ route('dashboard.customers.show', $row->id) }}" class="btn btn-icon btn-light-info btn-sm">
            <i class="ki-duotone ki-eye fs-2">
                <span class="path1"></span>
                <span class="path2"></span>
                <span class="path3"></span>
            </i>
        </a>
    @endcan

    @can('customers.edit')
        <a href="{{ route('dashboard.customers.edit', $row->id) }}" class="btn btn-icon btn-light-warning btn-sm">
            <i class="ki-duotone ki-pencil fs-2">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
        </a>
    @endcan

    @can('customers.delete')
        <button type="button" class="btn btn-sm btn-icon btn-light-danger js-delete-customer" data-id="{{ $row->id }}">
            <i class="ki-duotone ki-trash fs-2">
                <span class="path1"></span><span class="path2"></span>
            </i>
        </button>
    @endcan

</div>