@extends('base.layout.app')

@section('top-btns')
    @can('branches.create')
        <a href="{{ route('dashboard.branches.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus fs-5 me-2"></i>
            {{ __('branches.create') }}
        </a>
    @endcan
@endsection

@section('content')

{{-- 🔍 بوكس البحث --}}
<div class="card mb-5 shadow-sm">
    <div class="card-body p-6">
        <div class="d-flex align-items-center gap-4">
            <div class="flex-grow-1" style="min-width: 250px;">
                <div class="position-relative">
                    <i class="fa-solid fa-magnifying-glass position-absolute top-50 translate-middle-y ms-4 text-gray-500"></i>
                    <input type="text"
                           id="search_name"
                           class="form-control form-control-solid input-with-icon"
                           placeholder="{{ __('branches.search_placeholder') }}" />
                </div>
            </div>

            <div>
                <button type="button" id="reset_filters" class="btn btn-light-primary action-button">
                    <i class="fa-solid fa-rotate-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- جدول الفروع --}}
<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table id="kt_branches_table" class="table table-row-bordered table-hover gy-5">
                <thead>
                    <tr class="fw-semibold fs-6 text-muted"></tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div class="toast-container"></div>

@endsection

@push('custom-script')
<script>
(function () {

    const locale = '{{ app()->getLocale() }}';
    const dtLangUrl = locale === 'ar'
        ? 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json'
        : 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/en-GB.json';

    const table = window.KH.initAjaxDatatable({
        tableId: 'kt_branches_table',
        ajaxUrl: '{{ route('dashboard.branches.index') }}',
        languageUrl: dtLangUrl,
        searchInputId: 'search_name',
        columns: [
            {
                data: 'id',
                name: 'id',
                title: "{{ t('datatable.lbl_id') }}"
            },
            {
                data: 'name',
                name: 'name',
                title: "{{ __('branches.name') }}"
            },
            // {
            //     data: 'created_at',
            //     name: 'created_at',
            //     title: "{{ t('datatable.lbl_created_at') }}"
            // },
            // {
            //     data: 'actions',
            //     name: 'actions',
            //     title: "{{ t('datatable.lbl_actions') }}",
            //     orderable: false,
            //     searchable: false
            // }
        ]
    });

    $('#reset_filters').on('click', function () {
        $('#search_name').val('');
        table.ajax.reload();
    });

})();
</script>
@endpush