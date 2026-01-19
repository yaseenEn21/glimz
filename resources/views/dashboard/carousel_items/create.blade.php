@extends('base.layout.app')

@section('title', __('carousel.create'))

@section('content')

@section('top-btns')
@endsection

<div class="card">
    <form id="carousel_form" action="{{ route('dashboard.carousel-items.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="card-body">
            <div id="form_result" class="alert d-none"></div>

            <ul class="nav nav-tabs nav-line-tabs mb-6">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#tab_content">{{ __('carousel.tabs.content') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#tab_media">{{ __('carousel.tabs.media') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#tab_settings">{{ __('carousel.tabs.settings') }}</a>
                </li>
            </ul>

            <div class="tab-content">

                {{-- CONTENT --}}
                <div class="tab-pane fade show active" id="tab_content">
                    <div class="row g-9">
                        <div class="col-lg-6">
                            <div class="card card-flush">
                                <div class="card-header pt-6"><h3 class="card-title fw-bold">{{ __('carousel.lang.ar') }}</h3></div>
                                <div class="card-body pt-0">
                                    <div class="fv-row mb-5">
                                        <label class="fw-semibold mb-2">{{ __('carousel.fields.label') }}</label>
                                        <input name="label[ar]" class="form-control" placeholder="{{ __('carousel.placeholders.label') }}">
                                        <div class="invalid-feedback"></div>
                                    </div>

                                    <div class="fv-row mb-5">
                                        <label class="required fw-semibold mb-2">{{ __('carousel.fields.title') }}</label>
                                        <input name="title[ar]" class="form-control" placeholder="{{ __('carousel.placeholders.title') }}">
                                        <div class="invalid-feedback"></div>
                                    </div>

                                    <div class="fv-row mb-5">
                                        <label class="fw-semibold mb-2">{{ __('carousel.fields.description') }}</label>
                                        <textarea name="description[ar]" class="form-control" rows="4" placeholder="{{ __('carousel.placeholders.description') }}"></textarea>
                                        <div class="invalid-feedback"></div>
                                    </div>

                                    <div class="fv-row mb-4">
                                        <label class="fw-semibold mb-2">{{ __('carousel.fields.hint') }}</label>
                                        <input name="hint[ar]" class="form-control" placeholder="{{ __('carousel.placeholders.hint') }}">
                                        <div class="invalid-feedback"></div>
                                    </div>

                                    <div class="fv-row">
                                        <label class="fw-semibold mb-2">{{ __('carousel.fields.cta') }}</label>
                                        <input name="cta[ar]" class="form-control" placeholder="{{ __('carousel.placeholders.cta') }}">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="card card-flush">
                                <div class="card-header pt-6"><h3 class="card-title fw-bold">{{ __('carousel.lang.en') }}</h3></div>
                                <div class="card-body pt-0">
                                    <div class="fv-row mb-5">
                                        <label class="fw-semibold mb-2">{{ __('carousel.fields.label') }}</label>
                                        <input name="label[en]" class="form-control" placeholder="{{ __('carousel.placeholders.label') }}">
                                        <div class="invalid-feedback"></div>
                                    </div>

                                    <div class="fv-row mb-5">
                                        <label class="required fw-semibold mb-2">{{ __('carousel.fields.title') }}</label>
                                        <input name="title[en]" class="form-control" placeholder="{{ __('carousel.placeholders.title') }}">
                                        <div class="invalid-feedback"></div>
                                    </div>

                                    <div class="fv-row mb-5">
                                        <label class="fw-semibold mb-2">{{ __('carousel.fields.description') }}</label>
                                        <textarea name="description[en]" class="form-control" rows="4" placeholder="{{ __('carousel.placeholders.description') }}"></textarea>
                                        <div class="invalid-feedback"></div>
                                    </div>

                                    <div class="fv-row mb-4">
                                        <label class="fw-semibold mb-2">{{ __('carousel.fields.hint') }}</label>
                                        <input name="hint[en]" class="form-control" placeholder="{{ __('carousel.placeholders.hint') }}">
                                        <div class="invalid-feedback"></div>
                                    </div>

                                    <div class="fv-row">
                                        <label class="fw-semibold mb-2">{{ __('carousel.fields.cta') }}</label>
                                        <input name="cta[en]" class="form-control" placeholder="{{ __('carousel.placeholders.cta') }}">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- MEDIA --}}
                <div class="tab-pane fade" id="tab_media">
                    <div class="row g-9">
                        <div class="col-lg-6">
                            <div class="card card-flush">
                                <div class="card-header pt-6"><h3 class="card-title fw-bold">{{ __('carousel.image_ar') }}</h3></div>
                                <div class="card-body pt-0">
                                    <input type="file" name="image_ar" class="form-control" accept="image/*">
                                    <div class="invalid-feedback d-block"></div>
                                    <div class="form-text">{{ __('carousel.image_hint') }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="card card-flush">
                                <div class="card-header pt-6"><h3 class="card-title fw-bold">{{ __('carousel.image_en') }}</h3></div>
                                <div class="card-body pt-0">
                                    <input type="file" name="image_en" class="form-control" accept="image/*">
                                    <div class="invalid-feedback d-block"></div>
                                    <div class="form-text">{{ __('carousel.image_hint') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SETTINGS --}}
                <div class="tab-pane fade" id="tab_settings">
                    <div class="row g-9">

                        <div class="col-lg-6">
                            <div class="card card-flush">
                                <div class="card-header pt-6"><h3 class="card-title fw-bold">{{ __('carousel.fields.link_target') }}</h3></div>
                                <div class="card-body pt-0">

                                    <div class="fv-row mb-5">
                                        <label class="fw-semibold mb-2">{{ __('carousel.fields.carouselable_type') }}</label>
                                        <select id="carouselable_key" name="carouselable_key" class="form-select"  data-placeholder="{{ __('carousel.placeholders.carouselable_type') }}">
                                            <option></option>
                                            @foreach($carouselableKeys as $k)
                                                <option value="{{ $k }}">{{ __('carousel.types.'.$k) }}</option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>

                                    <div class="fv-row">
                                        <label class="fw-semibold mb-2">{{ __('carousel.fields.carouselable_id') }}</label>
                                        <select id="carouselable_id" name="carouselable_id" class="form-select"  data-placeholder="{{ __('carousel.placeholders.carouselable_item') }}" disabled>
                                            <option></option>
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>

                                    <div class="form-text mt-3">
                                        {{ __('carousel.link_target_hint') }}
                                    </div>

                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="card card-flush">
                                <div class="card-header pt-6"><h3 class="card-title fw-bold">{{ __('carousel.fields.visibility') }}</h3></div>
                                <div class="card-body pt-0">
                                    <div class="row g-6">
                                        <div class="col-md-4">
                                            <label class="fw-semibold mb-2">{{ __('carousel.fields.sort_order') }}</label>
                                            <input type="number" name="sort_order" class="form-control" min="0" value="0">
                                            <div class="invalid-feedback"></div>
                                        </div>

                                        <div class="col-md-8">
                                            <label class="fw-semibold mb-2 d-block">{{ __('carousel.fields.status') }}</label>
                                            <div class="form-check form-switch form-check-custom form-check-solid mt-2">
                                                <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                                                <label class="form-check-label fw-semibold">{{ __('carousel.active') }}</label>
                                            </div>
                                            <div class="invalid-feedback d-block"></div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>{{-- tab-content --}}
        </div>

        <div class="card-footer d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">
                <span class="indicator-label">{{ __('carousel.save') }}</span>
            </button>
        </div>
    </form>
</div>
@endsection

@push('custom-script')
<script>
(function(){
    const isAr = document.documentElement.lang === 'ar';
    const $form = $('#carousel_form');

    $('#carouselable_key').select2({ allowClear:true });
    $('#carouselable_id').select2({
        ajax: {
            url: "{{ route('dashboard.carousel-items.lookups.carouselables') }}",
            dataType: 'json',
            delay: 250,
            data: params => ({
                key: $('#carouselable_key').val() || '',
                q: params.term || ''
            }),
            processResults: data => data
        },
        allowClear: true,
        minimumInputLength: 0
    });

    $('#carouselable_key').on('change', function(){
        const key = $(this).val();
        $('#carouselable_id').val(null).trigger('change');
        $('#carouselable_id').prop('disabled', !key);
    });

    $form.on('submit', function(e){
        e.preventDefault();

        const formData = new FormData($form[0]);

        if (window.KH && typeof window.KH.setFormLoading === 'function') {
            window.KH.setFormLoading($form, true, { text: isAr ? 'جاري الحفظ...' : 'Saving...' });
        }

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res){
                Swal.fire({
                    icon:'success',
                    title:"{{ __('carousel.done') }}",
                    text: res.message || "{{ __('carousel.created_successfully') }}",
                    timer: 1500,
                    showConfirmButton:false
                });
                if (res.redirect) window.location.href = res.redirect;
            },
            error: function(xhr){
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    if (window.KH && typeof window.KH.showValidationErrors === 'function') {
                        window.KH.showValidationErrors($form, xhr.responseJSON.errors, { globalAlertSelector: '#form_result' });
                    } else {
                        $('#form_result').removeClass('d-none').addClass('alert-danger').text(xhr.responseJSON.message || 'Validation error');
                    }
                } else {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Unexpected error', 'error');
                }
            },
            complete: function(){
                if (window.KH && typeof window.KH.setFormLoading === 'function') {
                    window.KH.setFormLoading($form, false);
                }
            }
        });
    });

})();
</script>
@endpush