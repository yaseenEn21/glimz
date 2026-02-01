@php
    $user = auth()->user();
@endphp

<div class="dropdown">
    <button class="btn btn-sm btn-light btn-active-light-primary action-button" type="button" data-bs-toggle="dropdown"
        aria-expanded="false">
        <i class="fa-solid fa-ellipsis-vertical"></i>
    </button>

    <ul class="dropdown-menu dropdown-menu-end">

        @can('partners.view')
            <li>
                <a class="dropdown-item" href="{{ route('dashboard.partners.show', $partner->id) }}">
                    <i class="fa-solid fa-circle-info text-info me-2"></i>
                    <span>{{ __('partners.actions.view') }}</span>
                </a>
            </li>
        @endcan

        @can('partners.assign_services')
            <li>
                <a class="dropdown-item" href="{{ route('dashboard.partners.assign-services', $partner->id) }}">
                    <i class="fa-solid fa-gear text-primary me-2"></i>
                    <span>{{ __('partners.actions.assign_services') }}</span>
                </a>
            </li>
        @endcan

        @can('partners.edit')
            <li>
                <a class="dropdown-item" href="{{ route('dashboard.partners.edit', $partner->id) }}">
                    <i class="fa-solid fa-pen text-warning me-2"></i>
                    <span>{{ __('partners.actions.edit') }}</span>
                </a>
            </li>
        @endcan

        {{-- @can('partners.delete')
            <li>
                <button type="button" class="dropdown-item js-delete-partner" 
                        data-id="{{ $partner->id }}">
                    <i class="fa-regular fa-trash-can text-danger me-2"></i>
                    <span>{{ __('partners.actions.delete') }}</span>
                </button>
            </li>
        @endcan --}}

    </ul>
</div>