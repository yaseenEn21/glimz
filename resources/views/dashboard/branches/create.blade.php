@extends('base.layout.app')

@section('content')

<form id="branch_create_form"
      action="{{ route('dashboard.branches.store') }}"
      method="POST">
    @csrf

    {{-- رسالة التنبيه --}}
    <div id="form_result" class="alert d-none mb-7"></div>

    <div class="row g-7">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body p-10">

                    {{-- العنوان --}}
                    <div class="d-flex align-items-center mb-8">
                        <div class="symbol symbol-50px me-4">
                            <span class="symbol-label bg-light-primary">
                                <i class="ki-duotone ki-office-bag fs-2x text-primary">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>
                        </div>
                        <div>
                            <h2 class="fw-bold mb-1">{{ __('branches.basic_data') }}</h2>
                            <span class="text-muted fs-7">{{ __('branches.basic_data_hint') }}</span>
                        </div>
                    </div>

                    <div class="row g-6">
                        {{-- الاسم بالعربي --}}
                        <div class="col-md-6">
                            <div class="border border-dashed border-gray-300 rounded p-6 h-100">
                                <label class="required fs-6 fw-bold mb-3">
                                    {{ __('branches.name_ar') }}
                                </label>
                                <input type="text"
                                       name="name[ar]"
                                       class="form-control form-control-lg bg-light"
                                       placeholder="مثال: فرع غزة"/>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        {{-- الاسم بالإنجليزي --}}
                        <div class="col-md-6">
                            <div class="border border-dashed border-gray-300 rounded p-6 h-100">
                                <label class="fs-6 fw-bold mb-3">
                                    {{ __('branches.name_en') }}
                                </label>
                                <input type="text"
                                       name="name[en]"
                                       class="form-control form-control-lg bg-light"
                                       placeholder="Example: Gaza Branch"/>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- أزرار --}}
    <div class="d-flex justify-content-end mt-7 gap-3">
        <button type="submit" class="btn btn-primary btn-lg px-10">
            <span class="indicator-label">{{ __('messages.save') }}</span>
        </button>
    </div>
</form>

@endsection

@push('custom-script')
<script>
(function () {
    const $form = $('#branch_create_form');

    $form.on('submit', function (e) {
        e.preventDefault();

        const formData = new FormData($form[0]);

        window.KH.setFormLoading($form, true, {
            text: '{{ __("messages.saving") }}...'
        });

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success(res) {
                Swal.fire({
                    icon: 'success',
                    title: '{{ __("branches.singular_title") }}',
                    text: res.message || '{{ __("branches.created_successfully") }}',
                    timer: 2000,
                    showConfirmButton: false
                });

                if (res.redirect) {
                    window.location.href = res.redirect;
                }
            },
            error(xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    window.KH.showValidationErrors($form, xhr.responseJSON.errors, {
                        globalAlertSelector: '#form_result'
                    });
                } else {
                    Swal.fire('خطأ', xhr.responseJSON?.message || 'حدث خطأ غير متوقع', 'error');
                }
            },
            complete() {
                window.KH.setFormLoading($form, false);
            }
        });
    });
})();
</script>
@endpush