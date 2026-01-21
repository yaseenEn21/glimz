@extends('base.layout.app')

@push('custom-style')
    
@endpush

@section('content')

@section('top-btns')
    @can('services.create')
        <a href="{{ route('dashboard.services.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus fs-5 me-2"></i>
            {{ __('services.create_new') }}
        </a>
    @endcan
@endsection

{{-- 🔍 بوكس البحث الجديد - Compact Style --}}
<div class="card mb-5 shadow-sm">
    <div class="card-body p-6">
        <div class="d-flex flex-wrap align-items-center gap-4">
            {{-- بحث بالاسم --}}
            <div class="flex-grow-1" style="min-width: 250px;">
                <div class="position-relative">
                    <i
                        class="fa-solid fa-magnifying-glass position-absolute top-50 translate-middle-y ms-4 text-gray-500"></i>
                    <input type="text" id="search_name" class="form-control form-control-solid input-with-icon"
                        placeholder="{{ __('services.search_by_name_placeholder') }}" />
                </div>
            </div>

            {{-- تصنيف الخدمة --}}
            <div style="min-width: 200px;">
                <select id="filter_category_id" class="form-select form-select-solid" data-control="select2"
                    data-placeholder="{{ __('services.all_categories') }}" data-allow-clear="true">
                    <option value="">{{ __('services.all_categories') }}</option>
                    @php $locale = app()->getLocale(); @endphp
                    @foreach ($categories as $category)
                        @php
                            $catName = $category->name;
                            if (is_array($catName)) {
                                $catName = $catName[$locale] ?? (reset($catName) ?? '');
                            }
                        @endphp
                        <option value="{{ $category->id }}">{{ $catName }}</option>
                    @endforeach
                </select>
            </div>

            {{-- حالة الخدمة --}}
            <div style="min-width: 180px;">
                <select id="filter_status" class="form-select form-select-solid" data-control="select2"
                    data-placeholder="{{ __('services.all_statuses') }}" data-allow-clear="true">
                    <option value="">{{ __('services.all_statuses') }}</option>
                    <option value="active">{{ __('services.active') }}</option>
                    <option value="inactive">{{ __('services.inactive') }}</option>
                </select>
            </div>

            {{-- زر إعادة تعيين --}}
            <div>
                <button type="button" id="reset_filters" class="btn btn-light-primary action-button">
                    <i class="fa-solid fa-rotate-right p-0"></i>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- جدول الخدمات --}}
<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table id="kt_services_table" class="table table-row-bordered table-hover gy-5">
                <thead>
                    <tr class="fw-semibold fs-6 text-muted"></tr>
                </thead>
            </table>
        </div>
    </div>
</div>

{{-- Toast Container --}}
<div class="toast-container"></div>

@endsection

@push('custom-script')
<script>
    (function() {
        const locale = '{{ app()->getLocale() }}';
        const dtLangUrl = locale === 'ar' ?
            'https://cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json' :
            'https://cdn.datatables.net/plug-ins/1.13.6/i18n/en-GB.json';

        // ✨ دالة عرض Toast
        function showToast(type, message, icon = null) {
            const toastId = 'toast_' + Date.now();
            const iconHtml = icon ? `<i class="${icon} fs-2"></i>` : '';
            const toastClass = type === 'success' ? 'toast-success' : 'toast-error';

            const toastHtml = `
                <div id="${toastId}" class="toast custom-toast ${toastClass} show" role="alert">
                    <div class="toast-body">
                        ${iconHtml}
                        <span class="fw-semibold">${message}</span>
                    </div>
                </div>
            `;

            $('.toast-container').append(toastHtml);

            // إخفاء تلقائي بعد 4 ثواني
            setTimeout(() => {
                $(`#${toastId}`).fadeOut(300, function() {
                    $(this).remove();
                });
            }, 4000);
        }

        const table = window.KH.initAjaxDatatable({
            tableId: 'kt_services_table',
            ajaxUrl: '{{ route('dashboard.services.index') }}',
            languageUrl: dtLangUrl,
            searchInputId: 'search_name',
            columns: [{
                    data: 'id',
                    name: 'id',
                    title: "{{ t('datatable.lbl_id') }}"
                },
                {
                    data: 'name',
                    name: 'name',
                    title: "{{ t('datatable.lbl_name') }}"
                },
                {
                    data: 'category_name',
                    name: 'category.name',
                    title: "{{ __('services.category') }}"
                },
                {
                    data: 'duration_minutes',
                    name: 'duration_minutes',
                    title: "{{ __('services.duration') }}"
                },
                {
                    data: 'price',
                    name: 'price',
                    title: "{{ __('services.price') }}"
                },
                {
                    data: 'discounted_price',
                    name: 'discounted_price',
                    title: "{{ __('services.discount_price') }}"
                },
                {
                    data: 'is_active_badge',
                    name: 'is_active',
                    title: "{{ __('services.status') }}",
                    orderable: true,
                    searchable: false
                },
                {
                    data: 'created_at',
                    name: 'created_at',
                    title: "{{ t('datatable.lbl_created_at') }}"
                },
                {
                    data: 'actions',
                    name: 'actions',
                    className: 'text-start',
                    title: '{{ t('datatable.lbl_actions') }}',
                    orderable: false,
                    searchable: false
                }
            ],
            extraData: function(d) {
                d.category_id = $('#filter_category_id').val();
                d.status = $('#filter_status').val();
            }
        });

        // 🗑️ Custom Delete Handler
        $(document).on('click', '.js-delete-service', function(e) {
            e.preventDefault();

            const $btn = $(this);
            const serviceId = $btn.data('id');
            const deleteUrl = '{{ route('dashboard.services.destroy', ':id') }}'.replace(':id', serviceId);

            // 🎨 Custom Confirmation Modal (بدلاً من SweetAlert)
            const confirmHtml = `
                <div class="modal fade" id="deleteConfirmModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header border-0 pb-0">
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-center px-10 pb-10">
                                <div class="symbol symbol-100px symbol-circle bg-light-danger mb-7">
                                    <i class="fa-regular fa-trash-can fs-2x text-danger"></i>
                                </div>
                                <h2 class="fw-bold mb-4">{{ __('messages.confirm_delete_title') }}</h2>
                                <p class="text-gray-600 fs-5 mb-8">{{ __('messages.confirm_delete_text') }}</p>
                                <div class="d-flex gap-3 justify-content-center">
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                                        {{ __('messages.confirm_delete_cancel_button') }}
                                    </button>
                                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                                        <i class="fa-regular fa-trash-can me-2"></i>
                                        {{ __('messages.confirm_delete_confirm_button') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // إضافة المودال وعرضه
            $('body').append(confirmHtml);
            const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
            modal.show();

            // عند التأكيد
            $('#confirmDeleteBtn').on('click', function() {
                const $confirmBtn = $(this);
                $confirmBtn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-2"></span>{{ __('messages.deleting') }}...'
                    );

                $.ajax({
                    url: deleteUrl,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        modal.hide();
                        $('#deleteConfirmModal').remove();
                        $('.modal-backdrop').remove();

                        // ✅ عرض Toast للنجاح
                        showToast('success',
                            '{{ __('messages.delete_success_text') }}',
                            'fa-solid fa-circle-check');

                        // إعادة تحميل الجدول
                        if (table) {
                            table.ajax.reload(null, false);
                        }
                    },
                    error: function(xhr) {
                        modal.hide();
                        $('#deleteConfirmModal').remove();
                        $('.modal-backdrop').remove();

                        let errorMsg = '{{ __('messages.delete_error_text') }}';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }

                        // ❌ عرض Toast للخطأ
                        showToast('error', errorMsg, 'fa-solid fa-circle-xmark');
                    }
                });
            });

            // عند إغلاق المودال
            $('#deleteConfirmModal').on('hidden.bs.modal', function() {
                $(this).remove();
            });
        });

        // إعادة تحميل الجدول عند تغيير الفلاتر
        $('#filter_category_id, #filter_status').on('change', function() {
            if (table) {
                table.ajax.reload();
            }
        });

        // إعادة تعيين الفلاتر
        $('#reset_filters').on('click', function() {
            $('#search_name').val('');
            $('#filter_category_id').val('').trigger('change');
            $('#filter_status').val('').trigger('change');
            if (table) {
                table.ajax.reload();
            }
        });

    })();
</script>
@endpush
