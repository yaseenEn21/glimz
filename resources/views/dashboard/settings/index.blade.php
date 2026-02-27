@extends('base.layout.app')

@section('content')

<a href="{{ route('dashboard.settings.first-booking-discount.edit') }}"> إعداد خصم أول حجز </a>

{{-- فلتر --}}
<div class="card mb-5 shadow-sm">
    <div class="card-body p-6">
        <div class="d-flex flex-wrap align-items-center gap-4">
            <div class="flex-grow-1" style="min-width: 250px;">
                <div class="position-relative">
                    <i class="fa-solid fa-magnifying-glass position-absolute top-50 translate-middle-y ms-4 text-gray-500"></i>
                    <input type="text" id="search_custom"
                        class="form-control form-control-solid input-with-icon"
                        placeholder="{{ __('settings.search_placeholder') }}" />
                </div>
            </div>
            <button type="button" id="reset_filters" class="btn btn-light-primary action-button">
                <i class="fa-solid fa-rotate-right p-0"></i>
            </button>
        </div>
    </div>
</div>

{{-- الجدول --}}
<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table id="settings_table" class="table table-row-bordered table-hover gy-5">
                <thead>
                    <tr class="fw-semibold fs-6 text-muted"></tr>
                </thead>
            </table>
        </div>
    </div>
</div>


{{-- ══ مودال التعديل ══════════════════════════════════════════ --}}
<div class="modal fade" id="editSettingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-1">
                        <i class="fa-solid fa-pen text-warning me-2"></i>
                        {{ __('settings.edit') }}
                    </h5>
                    {{-- الكي كـ hint فقط --}}
                    <div class="text-muted fs-7" id="modal_key_hint"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div id="edit_errors" class="alert alert-danger d-none"></div>
                <input type="hidden" id="edit_id" />

                {{-- التسمية (readonly) --}}
                <div class="mb-5">
                    <label class="fw-bold mb-2 text-muted">{{ __('settings.label') }}</label>
                    <input type="text" id="edit_label" class="form-control bg-light" readonly />
                </div>

                {{-- النوع (readonly) --}}
                <div class="mb-5">
                    <label class="fw-bold mb-2 text-muted">{{ __('settings.type') }}</label>
                    <input type="text" id="edit_type" class="form-control bg-light" readonly />
                </div>

                {{-- القيمة (قابلة للتعديل) --}}
                <div class="mb-2">
                    <label class="required fw-bold mb-2">{{ __('settings.value') }}</label>
                    <textarea id="edit_value" class="form-control" rows="4"
                        placeholder="{{ __('settings.value_placeholder') }}"></textarea>
                    <div class="text-muted fs-7 mt-2" id="edit_type_hint"></div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="updateSettingBtn">
                    <span class="indicator-label">{{ __('messages.update') }}</span>
                </button>
            </div>

        </div>
    </div>
</div>

@endsection


@push('custom-script')
<script>
(function () {

    const typeHints = {
        'integer': 'أدخل رقم صحيح — مثال: 100',
        'string':  'أدخل نص عادي',
        'boolean': 'أدخل: true أو false',
        'json':    'أدخل JSON صحيح — مثال: [{"key":"value"}]',
    };

    // ── جدول ────────────────────────────────────────────────────
    const table = window.KH.initAjaxDatatable({
        tableId:       'settings_table',
        ajaxUrl:       '{{ route('dashboard.settings.index') }}',
        languageUrl:   dtLangUrl,
        searchInputId: 'search_custom',
        columns: [
            { data: 'id',         name: 'id',         title: "{{ t('datatable.lbl_id') }}" },
            { data: 'key',        name: 'key',        title: "{{ __('settings.key') }}" },
            { data: 'label',      name: 'label',      title: "{{ __('settings.label') }}" },
            { data: 'type',       name: 'type',       title: "{{ __('settings.type') }}",    orderable: false, searchable: false },
            { data: 'value',      name: 'value',      title: "{{ __('settings.value') }}" },
            { data: 'updated_at', name: 'updated_at', title: "{{ __('settings.last_updated') }}" },
            { data: 'actions',    name: 'actions',    title: "{{ t('datatable.lbl_actions') }}", orderable: false, searchable: false, className: 'text-end' },
        ],
    });

    $('#reset_filters').on('click', function () {
        $('#search_custom').val('');
        table.ajax.reload();
    });

    // ── helpers ─────────────────────────────────────────────────
    function showErrors(errors) {
        const msgs = Object.values(errors).flat().join('<br>');
        $('#edit_errors').html(msgs).removeClass('d-none');
    }

    function clearErrors() {
        $('#edit_errors').html('').addClass('d-none');
    }

    // ── فتح مودال التعديل ────────────────────────────────────────
    const editModal = new bootstrap.Modal(document.getElementById('editSettingModal'));

    $(document).on('click', '.js-edit-setting', function () {
        const id = $(this).data('id');
        clearErrors();

        $.get('{{ url('dashboard/settings') }}/' + id + '/edit')
            .done(function (res) {
                const d = res.data;

                $('#edit_id').val(d.id);
                $('#modal_key_hint').text(d.key);
                $('#edit_label').val(d.label  ?? '');
                $('#edit_type').val(d.type    ?? '');
                $('#edit_value').val(d.value  ?? '');
                $('#edit_type_hint').text(typeHints[d.type] ?? '');

                editModal.show();
            })
            .fail(function () {
                if (window.toastr) toastr.error('فشل تحميل البيانات');
            });
    });

    $('#editSettingModal').on('hidden.bs.modal', function () {
        clearErrors();
        $('#updateSettingBtn').html('<span class="indicator-label">{{ __('messages.update') }}</span>');
    });

    // ── تأكيد التعديل ────────────────────────────────────────────
    $('#updateSettingBtn').on('click', function () {
        const $btn = $(this);
        clearErrors();

        $btn.prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm me-2"></span>');

        $.ajax({
            url:  '{{ url('dashboard/settings') }}/' + $('#edit_id').val(),
            type: 'POST',
            data: {
                _method: 'PUT',
                _token:  '{{ csrf_token() }}',
                value:   $('#edit_value').val(),
            },
            success: function (res) {
                editModal.hide();
                if (window.toastr) toastr.success(res.message);
                table.ajax.reload(null, false);
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    showErrors(xhr.responseJSON.errors);
                } else {
                    if (window.toastr) toastr.error(xhr.responseJSON?.message || 'حدث خطأ');
                }
            },
            complete: function () {
                $btn.prop('disabled', false)
                    .html('<span class="indicator-label">{{ __('messages.update') }}</span>');
            }
        });
    });

})();
</script>
@endpush