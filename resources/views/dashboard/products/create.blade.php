@extends('base.layout.app')

@section('content')

<form id="product_create_form" action="{{ route('dashboard.products.store') }}" method="POST"
    enctype="multipart/form-data">
    @csrf
    
    {{-- رسالة التنبيه --}}
    <div id="form_result" class="alert d-none mb-7"></div>

    <div class="row g-7">
        {{-- العمود العلوي الكامل: معلومات أساسية --}}
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body p-10">
                    <div class="d-flex align-items-center mb-8">
                        <div class="symbol symbol-50px me-4">
                            <span class="symbol-label bg-light-primary">
                                <i class="fas fa-shopping-basket fs-2x text-primary"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <h2 class="text-gray-800 fw-bold mb-1">{{ __('products.basic_data') }}</h2>
                            <span class="text-muted fw-semibold d-block">{{ __('products.basic_data_hint') }}</span>
                        </div>
                    </div>

                    <div class="row g-6">
                        {{-- التصنيف --}}
                        <div class="col-12">
                            <div class="fv-row">
                                <label class="fs-5 fw-bold mb-3">{{ __('products.category') }}</label>
                                <select name="product_category_id" class="form-select form-select-lg" data-control="select2">
                                    <option value="">{{ __('products.select_category_optional') }}</option>
                                    @php $locale = app()->getLocale(); @endphp
                                    @foreach ($categories as $category)
                                        @php
                                            $catName = $category->name ?? [];
                                            $catName = $catName[$locale] ?? (reset($catName) ?: '');
                                        @endphp
                                        <option value="{{ $category->id }}">{{ $catName }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        {{-- الاسم والوصف بالعربي --}}
                        <div class="col-md-6">
                            <div class="border border-dashed border-gray-300 rounded p-6 h-100">
                                <div class="mb-5">
                                    <label class="required fs-6 fw-bold mb-2 text-gray-700">
                                        <i class="ki-duotone ki-flag fs-3 text-primary me-1">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        {{ __('products.name_ar') }}
                                    </label>
                                    <input type="text" name="name[ar]" class="form-control form-control-lg bg-light" 
                                        placeholder="مثال: معطر سيارات"/>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div>
                                    <label class="fs-6 fw-bold mb-2 text-gray-700">{{ __('products.description_ar') }}</label>
                                    <textarea name="description[ar]" class="form-control bg-light" rows="4" 
                                        placeholder="وصف مختصر للمنتج"></textarea>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        {{-- الاسم والوصف بالإنجليزي --}}
                        <div class="col-md-6">
                            <div class="border border-dashed border-gray-300 rounded p-6 h-100">
                                <div class="mb-5">
                                    <label class="fs-6 fw-bold mb-2 text-gray-700">
                                        <i class="ki-duotone ki-flag fs-3 text-success me-1">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        {{ __('products.name_en') }}
                                    </label>
                                    <input type="text" name="name[en]" class="form-control form-control-lg bg-light" 
                                        placeholder="Example: Car Freshener"/>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div>
                                    <label class="fs-6 fw-bold mb-2 text-gray-700">{{ __('products.description_en') }}</label>
                                    <textarea name="description[en]" class="form-control bg-light" rows="4" 
                                        placeholder="Short description"></textarea>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- العمود الأيسر: التسعير والإعدادات --}}
        <div class="col-lg-7">
            {{-- التسعير --}}
            <div class="card shadow-sm mb-7">
                <div class="card-body p-10">
                    <div class="d-flex align-items-center mb-8">
                        <div class="symbol symbol-50px me-4">
                            <span class="symbol-label bg-light-success">
                                <i class="ki-duotone ki-dollar fs-2x text-success">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                </i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <h2 class="text-gray-800 fw-bold mb-0">{{ __('products.pricing_block') }}</h2>
                        </div>
                    </div>

                    <div class="row g-5">
                        <div class="col-md-4">
                            <label class="required fs-6 fw-bold mb-3 d-block">{{ __('products.price') }}</label>
                            <div class="position-relative">
                                <input type="number" step="0.01" min="0" name="price" 
                                    class="form-control form-control-lg ps-12" placeholder="0.00"/>
                            </div>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="fs-6 fw-bold mb-3 d-block">{{ __('products.discounted_price') }}</label>
                            <div class="position-relative">
                                <input type="number" step="0.01" min="0" name="discounted_price" 
                                    class="form-control form-control-lg ps-12" placeholder="0.00"/>
                            </div>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="fs-6 fw-bold mb-3 d-block">{{ __('products.max_qty_per_booking') }}</label>
                            <div class="position-relative">
                                <input type="number" min="1" name="max_qty_per_booking" 
                                    class="form-control form-control-lg" placeholder="{{ __('products.max_qty_hint') }}"/>
                            </div>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- الإعدادات الإضافية --}}
            <div class="card shadow-sm">
                <div class="card-body p-10">
                    <div class="d-flex align-items-center mb-8">
                        <div class="symbol symbol-50px me-4">
                            <span class="symbol-label bg-light-warning">
                                <i class="ki-duotone ki-setting-2 fs-2x text-warning">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <h2 class="text-gray-800 fw-bold mb-0">{{ __('products.additional_settings') }}</h2>
                        </div>
                    </div>

                    <div class="row g-5">
                        <div class="col-md-6">
                            <label class="fs-6 fw-bold mb-3">{{ __('products.sort_order') }}</label>
                            <input type="number" name="sort_order" class="form-control form-control-lg" 
                                min="1" placeholder="1"/>
                            <div class="text-muted fs-7 mt-2">ترتيب العرض</div>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="fs-6 fw-bold mb-5 d-block">{{ __('products.status') }}</label>
                            <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
                                <input class="form-check-input h-30px w-50px" type="checkbox" name="is_active" 
                                    value="1" id="is_active" checked/>
                                <label class="form-check-label fw-semibold text-gray-700 ms-3" for="is_active">
                                    {{ __('products.active') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- العمود الأيمن: الصور --}}
        <div class="col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-body p-10">
                    <div class="d-flex align-items-center mb-8">
                        <div class="symbol symbol-50px me-4">
                            <span class="symbol-label bg-light-info">
                                <i class="fas fa-images fs-2x text-info"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <h2 class="text-gray-800 fw-bold mb-1">{{ __('messages.images') }}</h2>
                            <span class="text-muted fs-7">{{ __('products.images_note') }}</span>
                        </div>
                    </div>

                    {{-- الصورة العربية --}}
                    <div class="mb-10">
                        <div class="bg-light-primary rounded p-5">
                            <label class="fs-5 fw-bold text-gray-800 mb-4 d-flex align-items-center">
                                <span class="badge badge-circle badge-primary me-3">AR</span>
                                {{ __('products.image_ar') }}
                            </label>
                            
                            <div class="dropzone-custom border-2 border-dashed border-primary rounded text-center p-8" 
                                data-image-input="ar">
                                <input type="file" name="image_ar" accept=".png,.jpg,.jpeg,.webp" class="d-none" id="image_ar_input"/>
                                
                                <div class="preview-container d-none">
                                    <img src="" class="mw-100 rounded" style="max-height: 180px;" id="image_ar_preview"/>
                                    <div class="mt-4">
                                        <button type="button" class="btn btn-sm btn-light-primary me-2" onclick="document.getElementById('image_ar_input').click()">
                                            <i class="fas fa-pen fs-6"></i>
                                            تغيير
                                        </button>
                                        <button type="button" class="btn btn-sm btn-light-danger" onclick="removeImage('ar')">
                                            <i class="far fa-trash-alt fs-6"></i>
                                            حذف
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="upload-prompt">
                                    <i class="fas fa-cloud-upload-alt fs-3x text-primary mb-3"></i>
                                    <div class="fs-5 fw-bold text-gray-800 mb-2">اسحب الصورة هنا</div>
                                    <div class="text-muted fs-7 mb-4">أو</div>
                                    <label for="image_ar_input" class="btn btn-primary btn-sm">
                                        اختر ملف
                                    </label>
                                </div>
                            </div>
                            <div class="invalid-feedback d-block"></div>
                        </div>
                    </div>

                    {{-- الصورة الإنجليزية --}}
                    <div>
                        <div class="bg-light-success rounded p-5">
                            <label class="fs-5 fw-bold text-gray-800 mb-4 d-flex align-items-center">
                                <span class="badge badge-circle badge-success me-3">EN</span>
                                {{ __('products.image_en') }}
                            </label>
                            
                            <div class="dropzone-custom border-2 border-dashed border-success rounded text-center p-8" 
                                data-image-input="en">
                                <input type="file" name="image_en" accept=".png,.jpg,.jpeg,.webp" class="d-none" id="image_en_input"/>
                                
                                <div class="preview-container d-none">
                                    <img src="" class="mw-100 rounded" style="max-height: 180px;" id="image_en_preview"/>
                                    <div class="mt-4">
                                        <button type="button" class="btn btn-sm btn-light-success me-2" onclick="document.getElementById('image_en_input').click()">
                                            <i class="fas fa-pen fs-6"></i>
                                            تغيير
                                        </button>
                                        <button type="button" class="btn btn-sm btn-light-danger" onclick="removeImage('en')">
                                            <i class="far fa-trash-alt fs-6"></i>
                                            حذف
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="upload-prompt">
                                    <i class="fas fa-cloud-upload-alt fs-3x text-success mb-3"></i>
                                    <div class="fs-5 fw-bold text-gray-800 mb-2">Drag image here</div>
                                    <div class="text-muted fs-7 mb-4">or</div>
                                    <label for="image_en_input" class="btn btn-success btn-sm">
                                        Choose File
                                    </label>
                                </div>
                            </div>
                            <div class="invalid-feedback d-block"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- أزرار الحفظ --}}
    <div class="d-flex justify-content-end mt-7 gap-3">
        <button type="submit" class="btn btn-primary btn-lg px-10">
            <span class="indicator-label">{{ __('products.save') }}</span>
        </button>
    </div>
</form>

@endsection

@push('custom-script')
<script>
    // معالجة رفع الصور بمنطق Drag & Drop
    function initImageUpload(lang) {
        const input = document.getElementById(`image_${lang}_input`);
        const dropzone = document.querySelector(`[data-image-input="${lang}"]`);
        const preview = document.getElementById(`image_${lang}_preview`);
        const previewContainer = dropzone.querySelector('.preview-container');
        const uploadPrompt = dropzone.querySelector('.upload-prompt');

        // عند اختيار ملف
        input.addEventListener('change', function(e) {
            handleFile(e.target.files[0]);
        });

        // Drag & Drop
        dropzone.addEventListener('dragover', function(e) {
            e.preventDefault();
            dropzone.classList.add('border-primary', 'bg-light-primary');
        });

        dropzone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            dropzone.classList.remove('border-primary', 'bg-light-primary');
        });

        dropzone.addEventListener('drop', function(e) {
            e.preventDefault();
            dropzone.classList.remove('border-primary', 'bg-light-primary');
            
            const file = e.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                const dt = new DataTransfer();
                dt.items.add(file);
                input.files = dt.files;
                handleFile(file);
            }
        });

        function handleFile(file) {
            if (!file) return;
            
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                previewContainer.classList.remove('d-none');
                uploadPrompt.classList.add('d-none');
            };
            reader.readAsDataURL(file);
        }
    }

    function removeImage(lang) {
        const input = document.getElementById(`image_${lang}_input`);
        const preview = document.getElementById(`image_${lang}_preview`);
        const dropzone = document.querySelector(`[data-image-input="${lang}"]`);
        const previewContainer = dropzone.querySelector('.preview-container');
        const uploadPrompt = dropzone.querySelector('.upload-prompt');

        input.value = '';
        preview.src = '';
        previewContainer.classList.add('d-none');
        uploadPrompt.classList.remove('d-none');
    }

    // تهيئة رفع الصور
    initImageUpload('ar');
    initImageUpload('en');

    // إرسال الفورم
    (function() {
        const $form = $('#product_create_form');

        $form.on('submit', function(e) {
            e.preventDefault();

            const formData = new FormData($form[0]);

            window.KH.setFormLoading($form, true, {
                text: '{{ app()->getLocale()==="ar" ? "جاري الحفظ..." : "Saving..." }}'
            });

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    Swal.fire({
                        icon: 'success',
                        title: '{{ __('products.singular_title') }}',
                        text: res.message || '{{ __('products.created_successfully') }}',
                        timer: 2000,
                        showConfirmButton: false
                    });

                    if (res.redirect) {
                        window.location.href = res.redirect;
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        window.KH.showValidationErrors($form, xhr.responseJSON.errors, {
                            globalAlertSelector: '#form_result'
                        });
                    } else {
                        let msg = '{{ app()->getLocale()==="ar" ? "حدث خطأ غير متوقع." : "Unexpected error." }}';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        Swal.fire('{{ app()->getLocale()==="ar" ? "خطأ" : "Error" }}', msg, 'error');
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