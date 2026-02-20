<div class="dropdown">
    <button class="btn btn-sm btn-light btn-active-light-primary action-button" type="button" data-bs-toggle="dropdown"
        aria-expanded="false">
        <i class="fa-solid fa-ellipsis-vertical"></i>
    </button>

    <ul class="dropdown-menu dropdown-menu-end">

        @can('service_categories.view')
            <li>
                <a class="dropdown-item" href="{{ route('dashboard.service-categories.show', $category->id) }}">
                    <i class="fa-solid fa-circle-info text-info me-2"></i>
                    {{ __('messages.actions-btn.view') }}
                </a>
            </li>
        @endcan
        
        @can('service_categories.edit')
            <li>
                <a class="dropdown-item" href="{{ route('dashboard.service-categories.edit', $category->id) }}">
                    <i class="fa-solid fa-pen text-warning me-2"></i>
                    {{ __('messages.actions-btn.edit') }}
                </a>
            </li>
        @endcan

        @can('service_categories.delete')
            <li>
                <button type="button" class="dropdown-item js-delete-category" data-id="{{ $category->id }}">
                    <i class="fa-regular fa-trash-can text-danger me-2"></i>
                    {{ __('messages.actions-btn.delete') }}
                </button>
            </li>
        @endcan

    </ul>
</div>
