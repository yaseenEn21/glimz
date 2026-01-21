@php
    $user = auth()->user();
@endphp

<div class="dropdown">
    <button class="btn btn-sm btn-light btn-active-light-primary action-button" type="button" data-bs-toggle="dropdown"
        aria-expanded="false">
        <i class="fa-solid fa-ellipsis-vertical"></i>
    </button>

    <ul class="dropdown-menu dropdown-menu-end">

        @can('products.view')
            <li>
                <a class="dropdown-item" href="{{ route('dashboard.products.show', $product->id) }}">
                    <i class="fa-solid fa-circle-info text-info me-2"></i>
                    <span>{{ __('messages.actions-btn.view') }}</span>
                </a>
            </li>
        @endcan

        @can('products.edit')
            <li>
                <a class="dropdown-item" href="{{ route('dashboard.products.edit', $product->id) }}">
                    <i class="fa-solid fa-pen text-warning me-2"></i>
                    <span>{{ __('messages.actions-btn.edit') }}</span>
                </a>
            </li>
        @endcan

        @can('products.delete')
            <li>
                <button type="button" class="dropdown-item js-delete-product" data-id="{{ $product->id }}">
                    <i class="fa-regular fa-trash-can text-danger me-2"></i>
                    <span>{{ __('messages.actions-btn.delete') }}</span>
                </button>
            </li>
        @endcan

    </ul>
</div>
