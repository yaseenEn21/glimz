@extends('base.layout.app')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-5 gap-3">
        <div>
            <h2 class="fw-bold mb-1">{{ __('app-pages.title') }}</h2>
            <div class="text-muted">
                {{ __('app-pages.subtitle') }}
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-row-dashed align-middle mb-0">
                    <thead>
                    <tr class="fw-semibold text-gray-700">
                        <th class="min-w-200px ps-4">{{ __('app-pages.table.page') }}</th>
                        <th class="min-w-300px">{{ __('app-pages.table.description') }}</th>
                        <th class="min-w-150px">{{ __('app-pages.table.updated_at') }}</th>
                        <th class="min-w-120px text-center">{{ __('app-pages.table.actions') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($pages as $page)
                        <tr>
                            <td class="fw-bold ps-4">
                                {{ $page['name'] }}
                                @if($page['has_value'])
                                    <span class="badge badge-light-success ms-2">
                                        {{ __('app-pages.badges.configured') }}
                                    </span>
                                @else
                                    <span class="badge badge-light-warning ms-2">
                                        {{ __('app-pages.badges.not_configured') }}
                                    </span>
                                @endif
                            </td>

                            <td class="text-muted">
                                {{ $page['description'] }}
                            </td>

                            <td>
                                @if($page['updated_at'])
                                    {{ $page['updated_at']->format('Y-m-d H:i') }}
                                @else
                                    <span class="text-muted">{{ __('app-pages.dash') }}</span>
                                @endif
                            </td>

                            <td class="text-center">
                                <a href="{{ route('dashboard.app-pages.edit', $page['key']) }}"
                                   class="btn btn-sm btn-light-primary">
                                    {{ __('app-pages.actions.edit_content') }}
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection