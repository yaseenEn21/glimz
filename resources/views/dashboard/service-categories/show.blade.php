@extends('base.layout.app')

@section('content')

@section('top-btns')
    @can('service_categories.edit')
        <a href="{{ route('dashboard.service-categories.edit', $serviceCategory->id) }}"
            class="btn btn-light-warning me-2">
            {{ __('messages.actions-btn.edit') }}
        </a>
    @endcan
@endsection

@php
    $locale  = app()->getLocale();
    $nameArr = is_array($serviceCategory->name) ? $serviceCategory->name : [];
    $name    = $nameArr[$locale] ?? reset($nameArr) ?? '—';
@endphp

<div class="row g-6">

    {{-- ─── العمود الرئيسي ─────────────────────────────────────── --}}
    <div class="col-xl-8">

        {{-- جدول الخدمات --}}
        <div class="card card-flush">
            <div class="card-header pt-5">
                <h3 class="card-title fw-bold">
                    {{ __('service_categories.services_in_category') }}
                </h3>
            </div>

            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table id="category_services_table"
                        class="table align-middle table-row-dashed fs-6 gy-5">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                <th>#</th>
                                <th>{{ __('services.singular_title') }}</th>
                                <th>{{ __('services.duration') }}</th>
                                <th>{{ __('services.price') }}</th>
                                <th>{{ __('services.discount_price') }}</th>
                                <th>{{ __('services.status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 fw-semibold"></tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    {{-- ─── الشريط الجانبي ──────────────────────────────────────── --}}
    <div class="col-xl-4">

        {{-- تفاصيل التصنيف --}}
        <div class="card card-flush mb-6">
            <div class="card-header pt-5">
                <h3 class="card-title fw-bold">{{ __('service_categories.category_details') }}</h3>
            </div>

            <div class="card-body pt-0">

                {{-- الأيقونة والاسم --}}
                <div class="d-flex align-items-center gap-5 mb-7">
                    <div class="symbol symbol-70px symbol-circle bg-light-primary">
                        <span class="symbol-label">
                            <i class="fa-solid fa-layer-group fs-2 text-primary"></i>
                        </span>
                    </div>
                    <div>
                        <div class="fw-bold fs-3 text-gray-800">{{ $name }}</div>
                        <div class="text-muted fs-7 mt-1">
                            {{ __('service_categories.sort_order') }}:
                            <span class="fw-semibold text-gray-700">
                                {{ $serviceCategory->sort_order ?? '—' }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- الاسمين --}}
                <div class="separator separator-dashed mb-5"></div>

                <div class="mb-4">
                    <div class="text-muted fs-8 mb-1">{{ __('service_categories.name_ar') }}</div>
                    <div class="fw-semibold text-gray-800">
                        {{ $serviceCategory->name['ar'] ?? '—' }}
                    </div>
                </div>

                <div class="mb-4">
                    <div class="text-muted fs-8 mb-1">{{ __('service_categories.name_en') }}</div>
                    <div class="fw-semibold text-gray-800">
                        {{ $serviceCategory->name['en'] ?? '—' }}
                    </div>
                </div>

                {{-- الحالة --}}
                <div class="mb-4">
                    <div class="text-muted fs-8 mb-1">{{ __('service_categories.status') }}</div>
                    @if ($serviceCategory->is_active)
                        <span class="badge badge-light-success">{{ __('service_categories.active') }}</span>
                    @else
                        <span class="badge badge-light-danger">{{ __('service_categories.inactive') }}</span>
                    @endif
                </div>

                {{-- تاريخ الإنشاء --}}
                <div>
                    <div class="text-muted fs-8 mb-1">{{ __('messages.created_at') }}</div>
                    <div class="fw-semibold text-gray-800">
                        {{ optional($serviceCategory->created_at)->format('Y-m-d') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- إحصائيات --}}
        <div class="card card-flush">
            <div class="card-header pt-5">
                <h3 class="card-title fw-bold">{{ __('service_categories.stats') }}</h3>
            </div>

            <div class="card-body pt-0">

                <div class="d-flex align-items-center justify-content-between mb-5 p-4 bg-light-primary rounded">
                    <div>
                        <div class="text-muted fs-8">{{ __('service_categories.total_services') }}</div>
                        <div class="fw-bold fs-2 text-primary">{{ $servicesCount }}</div>
                    </div>
                    <div class="symbol symbol-50px">
                        <span class="symbol-label bg-primary bg-opacity-10">
                            <i class="fa-solid fa-tools fs-2 text-primary"></i>
                        </span>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-between p-4 bg-light-success rounded">
                    <div>
                        <div class="text-muted fs-8">{{ __('service_categories.active_services') }}</div>
                        <div class="fw-bold fs-2 text-success">{{ $activeCount }}</div>
                    </div>
                    <div class="symbol symbol-50px">
                        <span class="symbol-label bg-success bg-opacity-10">
                            <i class="fa-solid fa-circle-check fs-2 text-success"></i>
                        </span>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

@endsection

@push('custom-script')
<script>
(function () {

    $('#category_services_table').DataTable({
        processing: true,
        serverSide: true,
        searching:  false,
        pageLength: 10,
        order: [[0, 'asc']],
        language: { url: dtLangUrl },
        ajax: {
            url: "{{ route('dashboard.service-categories.services.datatable', $serviceCategory->id) }}"
        },
        columns: [
            { data: 'DT_RowIndex',     name: 'DT_RowIndex',      orderable: false, searchable: false },
            { data: 'name',            name: 'name' },
            { data: 'duration_minutes',name: 'duration_minutes' },
            { data: 'price',           name: 'price' },
            { data: 'discounted_price',name: 'discounted_price' },
            { data: 'is_active_badge', name: 'is_active',         orderable: false, searchable: false },
        ],
    });

})();
</script>
@endpush