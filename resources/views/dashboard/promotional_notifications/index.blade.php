@extends('base.layout.app')

@section('title', __('promotional_notifications.list'))

@section('content')

@section('top-btns')
    @can('promotional_notifications.send')
        <a href="{{ route('dashboard.promotional-notifications.create') }}" class="btn btn-primary">
            <i class="ki-duotone ki-plus fs-2"></i>
            {{ __('promotional_notifications.create') }}
        </a>
    @endcan
@endsection

{{-- 🔍 بوكس البحث --}}
<div class="card mb-5 shadow-sm">
    <div class="card-body p-6">
        <div class="d-flex flex-wrap align-items-center gap-4">
            {{-- بحث بالعنوان --}}
            <div class="flex-grow-1" style="min-width: 250px;">
                <div class="position-relative">
                    <i class="ki-duotone ki-magnifier position-absolute top-50 translate-middle-y ms-4 fs-3 text-gray-500">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <input type="text" id="search_title" class="form-control form-control-solid input-with-icon"
                        placeholder="{{ __('promotional_notifications.search_placeholder') }}" />
                </div>
            </div>

            {{-- الحالة --}}
            <div style="min-width: 180px;">
                <select id="filter_status" class="form-select form-select-solid" data-control="select2"
                    data-placeholder="{{ __('promotional_notifications.all_statuses') }}" data-allow-clear="true">
                    <option value="">{{ __('promotional_notifications.all_statuses') }}</option>
                    <option value="draft">{{ __('promotional_notifications.statuses.draft') }}</option>
                    <option value="scheduled">{{ __('promotional_notifications.statuses.scheduled') }}</option>
                    <option value="sending">{{ __('promotional_notifications.statuses.sending') }}</option>
                    <option value="sent">{{ __('promotional_notifications.statuses.sent') }}</option>
                    <option value="failed">{{ __('promotional_notifications.statuses.failed') }}</option>
                    <option value="cancelled">{{ __('promotional_notifications.statuses.cancelled') }}</option>
                </select>
            </div>

            {{-- نوع الجمهور --}}
            <div style="min-width: 180px;">
                <select id="filter_target_type" class="form-select form-select-solid" data-control="select2"
                    data-placeholder="{{ __('promotional_notifications.all_targets') }}" data-allow-clear="true">
                    <option value="">{{ __('promotional_notifications.all_targets') }}</option>
                    <option value="all_users">{{ __('promotional_notifications.target_types.all_users') }}</option>
                    <option value="specific_users">{{ __('promotional_notifications.target_types.specific_users') }}</option>
                </select>
            </div>

            {{-- زر إعادة تعيين --}}
             {{-- زر إعادة تعيين --}}
            <div>
                <button type="button" id="reset_filters" class="btn btn-light-primary action-button">
                    <i class="fa-solid fa-rotate-right p-0"></i>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- جدول الإشعارات --}}
<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table id="kt_notifications_table" class="table table-row-bordered table-hover gy-5">
                <thead>
                    <tr class="fw-semibold fs-6 text-muted"></tr>
                </thead>
            </table>
        </div>
    </div>
</div>

{{-- Toast Container --}}
<div class="toast-container position-fixed top-0 end-0 p-3"></div>

@endsection

@push('custom-script')
<script>
(function() {
    const locale = '{{ app()->getLocale() }}';
    const isAr = locale === 'ar';
    const dtLangUrl = isAr ?
        'https://cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json' :
        'https://cdn.datatables.net/plug-ins/1.13.6/i18n/en-GB.json';

    // ✨ دالة عرض Toast
    function showToast(type, message, icon = null) {
        const toastId = 'toast_' + Date.now();
        const iconHtml = icon ? `<i class="${icon} fs-2 me-2"></i>` : '';
        const toastClass = type === 'success' ? 'bg-success' : 'bg-danger';

        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-white ${toastClass} border-0 show" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        ${iconHtml}
                        <span class="fw-semibold">${message}</span>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;

        $('.toast-container').append(toastHtml);

        setTimeout(() => {
            $(`#${toastId}`).fadeOut(300, function() {
                $(this).remove();
            });
        }, 4000);
    }

    const table = window.KH.initAjaxDatatable({
        tableId: 'kt_notifications_table',
        ajaxUrl: '{{ route('dashboard.promotional-notifications.index') }}',
        languageUrl: dtLangUrl,
        searchInputId: 'search_title',
        searchDelay: 500,
        columns: [
            {
                data: 'id',
                name: 'id',
                title: '#',
                width: '50px'
            },
            {
                data: 'title',
                name: 'title',
                title: "{{ __('promotional_notifications.fields.title') }}",
                width: '300px'
            },
            {
                data: 'target_type_badge',
                name: 'target_type',
                title: "{{ __('promotional_notifications.fields.target_type') }}",
                orderable: true,
                searchable: false,
                width: '150px'
            },
            {
                data: 'status_badge',
                name: 'status',
                title: "{{ __('promotional_notifications.fields.status') }}",
                orderable: true,
                searchable: false,
                width: '120px'
            },
            {
                data: 'recipients_info',
                name: 'total_recipients',
                title: "{{ __('promotional_notifications.fields.total_recipients') }}",
                orderable: true,
                searchable: false,
                width: '150px'
            },
            {
                data: 'scheduled_at',
                name: 'scheduled_at',
                title: "{{ __('promotional_notifications.fields.scheduled_at') }}",
                width: '150px'
            },
            {
                data: 'created_at',
                name: 'created_at',
                title: "{{ __('promotional_notifications.fields.created_at') }}",
                width: '150px'
            },
            {
                data: 'actions',
                name: 'actions',
                className: 'text-center',
                title: '{{ __('promotional_notifications.actions.title') }}',
                orderable: false,
                searchable: false,
                width: '100px'
            }
        ],
        extraData: function(d) {
            d.search_custom = $('#search_title').val();
            d.status = $('#filter_status').val();
            d.target_type = $('#filter_target_type').val();
        }
    });

    // 📤 Send Now Handler
    $(document).on('click', '.js-send-notification', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const notificationId = $btn.data('id');

        Swal.fire({
            title: isAr ? 'تأكيد الإرسال' : 'Confirm Send',
            text: "{{ __('promotional_notifications.messages.confirm_send') }}",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: isAr ? 'نعم، أرسل الآن' : 'Yes, Send Now',
            cancelButtonText: isAr ? 'إلغاء' : 'Cancel',
            buttonsStyling: false,
            customClass: {
                confirmButton: 'btn btn-success',
                cancelButton: 'btn btn-secondary'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(`/dashboard/promotional-notifications/${notificationId}/send`, {
                    _token: '{{ csrf_token() }}'
                })
                .done(function(response) {
                    showToast('success', response.message || "{{ __('promotional_notifications.messages.sent_successfully') }}", 'ki-duotone ki-check-circle');
                    if (table) table.ajax.reload(null, false);
                })
                .fail(function(xhr) {
                    showToast('error', xhr.responseJSON?.message || 'Failed to send', 'ki-duotone ki-cross-circle');
                });
            }
        });
    });

    // ❌ Cancel Handler
    $(document).on('click', '.js-cancel-notification', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const notificationId = $btn.data('id');

        Swal.fire({
            title: isAr ? 'تأكيد الإلغاء' : 'Confirm Cancel',
            text: "{{ __('promotional_notifications.messages.confirm_cancel') }}",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: isAr ? 'نعم، ألغ' : 'Yes, Cancel',
            cancelButtonText: isAr ? 'رجوع' : 'Back',
            buttonsStyling: false,
            customClass: {
                confirmButton: 'btn btn-warning',
                cancelButton: 'btn btn-secondary'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(`/dashboard/promotional-notifications/${notificationId}/cancel`, {
                    _token: '{{ csrf_token() }}'
                })
                .done(function(response) {
                    showToast('success', response.message, 'ki-duotone ki-check-circle');
                    if (table) table.ajax.reload(null, false);
                })
                .fail(function(xhr) {
                    showToast('error', xhr.responseJSON?.message || 'Failed to cancel', 'ki-duotone ki-cross-circle');
                });
            }
        });
    });

    // 🗑️ Delete Handler
    $(document).on('click', '.js-delete-notification', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const notificationId = $btn.data('id');

        const confirmHtml = `
            <div class="modal fade" id="deleteConfirmModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header border-0 pb-0">
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center px-10 pb-10">
                            <div class="symbol symbol-100px symbol-circle bg-light-danger mb-7">
                                <i class="ki-duotone ki-trash fs-2x text-danger">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                    <span class="path5"></span>
                                </i>
                            </div>
                            <h2 class="fw-bold mb-4">{{ __('promotional_notifications.messages.confirm_delete') }}</h2>
                            <p class="text-gray-600 fs-5 mb-8">{{ __('promotional_notifications.delete_warning') }}</p>
                            <div class="d-flex gap-3 justify-content-center">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                                    {{ __('promotional_notifications.cancel') }}
                                </button>
                                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                                    <i class="ki-duotone ki-trash me-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    {{ __('promotional_notifications.actions.delete') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('body').append(confirmHtml);
        const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
        modal.show();

        $('#confirmDeleteBtn').on('click', function() {
            const $confirmBtn = $(this);
            $confirmBtn.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm me-2"></span>' + (isAr ? 'جاري الحذف...' : 'Deleting...')
            );

            $.ajax({
                url: `/dashboard/promotional-notifications/${notificationId}`,
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    modal.hide();
                    $('#deleteConfirmModal').remove();
                    $('.modal-backdrop').remove();
                    showToast('success', response.message || "{{ __('promotional_notifications.messages.deleted_successfully') }}", 'ki-duotone ki-check-circle');
                    if (table) table.ajax.reload(null, false);
                },
                error: function(xhr) {
                    modal.hide();
                    $('#deleteConfirmModal').remove();
                    $('.modal-backdrop').remove();
                    showToast('error', xhr.responseJSON?.message || 'Failed to delete', 'ki-duotone ki-cross-circle');
                }
            });
        });

        $('#deleteConfirmModal').on('hidden.bs.modal', function() {
            $(this).remove();
        });
    });

    // Filters
    $('#filter_status, #filter_target_type').on('change', function() {
        if (table) table.ajax.reload();
    });

    // Reset
    $('#reset_filters').on('click', function() {
        $('#search_title').val('');
        $('#filter_status').val('').trigger('change');
        $('#filter_target_type').val('').trigger('change');
        if (table) table.ajax.reload();
    });

})();
</script>
@endpush