@php
    $user = auth()->user();
@endphp

<div class="dropdown">
    <button class="btn btn-sm btn-light btn-active-light-primary action-button" type="button" data-bs-toggle="dropdown"
        aria-expanded="false">
        <i class="fa-solid fa-ellipsis-vertical"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        @can('employees.view')
            <li>
                <a class="dropdown-item" href="{{ route('dashboard.employees.show', $employee->id) }}">
                    <i class="fa-solid fa-circle-info text-info me-2"></i>
                    <span>{{ __('messages.actions-btn.view') }}</span>
                </a>
            </li>
        @endcan

        @can('employees.edit')
            <li>
                <a class="dropdown-item" href="{{ route('dashboard.employees.edit', $employee->id) }}">
                    <i class="fa-solid fa-pen text-warning me-2"></i>
                    <span>{{ __('messages.actions-btn.edit') }}</span>
                </a>
            </li>
        @endcan

        @can('employees.delete')
            <li>
                <button type="button" class="dropdown-item js-delete-employee" data-id="{{ $employee->id }}">
                    <i class="fa-regular fa-trash-can text-danger me-2"></i>
                    <span>{{ __('messages.actions-btn.delete') }}</span>
                </button>
            </li>
        @endcan
    </ul>
</div>
