@extends('base.layout.app')

@section('content')
    @php
        $nameAr = old('name.ar', $serviceCategory->name['ar'] ?? '');
        $nameEn = old('name.en', $serviceCategory->name['en'] ?? '');
        $sortOrder = old('sort_order', $serviceCategory->sort_order ?? '');
        $isActive = (bool) old('is_active', $serviceCategory->is_active ? 1 : 0);
    @endphp

    <form id="category_edit_form" action="{{ route('dashboard.service-categories.update', $serviceCategory->id) }}"
        method="POST">
        @csrf
        @method('PUT')

        <div id="form_result" class="alert d-none mb-7"></div>

        <div class="card shadow-sm">
            <div class="card-body p-10">

                <div class="d-flex align-items-center mb-8">
                    <div class="symbol symbol-50px me-4">
                        <span class="symbol-label bg-light-warning">
                            <i class="fa-solid fa-layer-group fs-2 text-warning"></i>
                        </span>
                    </div>
                    <div>
                        <h2 class="text-gray-800 fw-bold mb-1">{{ __('service_categories.edit') }}</h2>
                        <span class="text-muted fw-semibold">{{ __('service_categories.basic_data_hint') }}</span>
                    </div>
                </div>

                <div class="row g-6">
                    <div class="col-md-6">
                        <div class="border border-dashed border-gray-300 rounded p-6">
                            <label class="required fs-6 fw-bold mb-2 text-gray-700">
                                {{ __('service_categories.name_ar') }}
                            </label>
                            <input type="text" name="name[ar]" class="form-control form-control-lg bg-light"
                                value="{{ $nameAr }}" placeholder="مثال: غسيل خارجي" />
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="border border-dashed border-gray-300 rounded p-6">
                            <label class="fs-6 fw-bold mb-2 text-gray-700">
                                {{ __('service_categories.name_en') }}
                            </label>
                            <input type="text" name="name[en]" class="form-control form-control-lg bg-light"
                                value="{{ $nameEn }}" placeholder="Example: Exterior Wash" />
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="fs-6 fw-bold mb-3">{{ __('service_categories.sort_order') }}</label>
                        <input type="number" name="sort_order" class="form-control form-control-lg" min="1"
                            value="{{ $sortOrder }}" placeholder="1" />
                        <div class="text-muted fs-7 mt-2">{{ __('service_categories.sort_order_hint') }}</div>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="col-md-4 d-flex pb-2">
                        <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
                            <input class="form-check-input h-30px w-50px" type="checkbox" name="is_active" value="1"
                                id="is_active" {{ $isActive ? 'checked' : '' }} />
                            <label class="form-check-label fw-semibold text-gray-700 ms-3" for="is_active">
                                {{ __('service_categories.active') }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end mt-7">
            <button type="submit" class="btn btn-primary btn-lg px-10">
                <span class="indicator-label">{{ __('messages.update') }}</span>
            </button>
        </div>
    </form>
@endsection

@push('custom-script')
    <script>
        (function() {
            const $form = $('#category_edit_form');

            $form.on('submit', function(e) {
                e.preventDefault();

                window.KH.setFormLoading($form, true, {
                    text: 'جاري التحديث...'
                });

                $.ajax({
                    url: $form.attr('action'),
                    type: 'POST',
                    data: $form.serialize(),
                    success: function(res) {
                        Swal.fire({
                            icon: 'success',
                            title: 'تم',
                            text: res.message ||
                                '{{ __('service_categories.updated_successfully') }}',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        if (res.redirect) window.location.href = res.redirect;
                    },
                    error: function(xhr) {
                        if (xhr.status === 422 && xhr.responseJSON?.errors) {
                            window.KH.showValidationErrors($form, xhr.responseJSON.errors, {
                                globalAlertSelector: '#form_result'
                            });
                        } else {
                            Swal.fire('خطأ', xhr.responseJSON?.message || 'حدث خطأ غير متوقع.',
                                'error');
                        }
                    },
                    complete: function() {
                        window.KH.setFormLoading($form, false);
                    }
                });
            });
        })();
    </script>
@endpush
