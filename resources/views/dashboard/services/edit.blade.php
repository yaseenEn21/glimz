@extends('base.layout.app')

@section('content')
    @php
        $locale = app()->getLocale();
        $imageArUrl = $service->getFirstMediaUrl('image_ar') ?: asset('assets/media/svg/files/blank-image.svg');
        $imageEnUrl = $service->getFirstMediaUrl('image_en') ?: asset('assets/media/svg/files/blank-image.svg');

        $nameAr = old('name.ar', $service->name['ar'] ?? '');
        $nameEn = old('name.en', $service->name['en'] ?? '');
        $descAr = old('description.ar', $service->description['ar'] ?? '');
        $descEn = old('description.en', $service->description['en'] ?? '');
        $duration = old('duration_minutes', $service->duration_minutes);
        $price = old('price', $service->price);
        $discountPrice = old('discounted_price', $service->discounted_price);
        $catId = old('service_category_id', $service->service_category_id);
        $points = old('points', $service->points ?? '');
        $sortOrder = old('sort_order', $service->sort_order ?? '');
        $isActiveOld = old('is_active', $service->is_active ? 1 : 0);
        $isActive = (bool) $isActiveOld;
    @endphp

    <form id="service_edit_form" action="{{ route('dashboard.services.update', $service->id) }}" method="POST"
        enctype="multipart/form-data">
        @csrf
        @method('PUT')

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
                                    <i class="fas fa-tools fs-2 ki-element-11 fs-2x text-primary"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <h2 class="text-gray-800 fw-bold mb-1">{{ __('services.basic_data') }}</h2>
                                <span class="text-muted fw-semibold d-block">{{ __('services.basic_data_hint') }}</span>
                            </div>
                        </div>

                        <div class="row g-6">
                            {{-- التصنيف --}}
                            <div class="col-12">
                                <div class="fv-row">
                                    <label class="required fs-5 fw-bold mb-3">{{ __('services.category') }}</label>
                                    <select name="service_category_id" class="form-select form-select-lg"
                                        data-control="select2">
                                        <option value="">{{ __('services.select_category') }}</option>
                                        @foreach ($categories as $category)
                                            @php
                                                $catName = $category->name;
                                                if (is_array($catName)) {
                                                    $catName = $catName[$locale] ?? (reset($catName) ?? '');
                                                }
                                            @endphp
                                            <option value="{{ $category->id }}"
                                                {{ (int) $catId === (int) $category->id ? 'selected' : '' }}>
                                                {{ $catName }}
                                            </option>
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
                                            {{ __('services.name_ar') }}
                                        </label>
                                        <input type="text" name="name[ar]" class="form-control form-control-lg bg-light"
                                            value="{{ $nameAr }}" placeholder="مثال: غسيل خارجي" />
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div>
                                        <label
                                            class="fs-6 fw-bold mb-2 text-gray-700">{{ __('services.description_ar') }}</label>
                                        <textarea name="description[ar]" class="form-control bg-light" rows="4" placeholder="وصف مختصر للخدمة">{{ $descAr }}</textarea>
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
                                            {{ __('services.name_en') }}
                                        </label>
                                        <input type="text" name="name[en]" class="form-control form-control-lg bg-light"
                                            value="{{ $nameEn }}" placeholder="Example: Exterior wash" />
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div>
                                        <label
                                            class="fs-6 fw-bold mb-2 text-gray-700">{{ __('services.description_en') }}</label>
                                        <textarea name="description[en]" class="form-control bg-light" rows="4" placeholder="Short description">{{ $descEn }}</textarea>
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
                                <h2 class="text-gray-800 fw-bold mb-0">{{ __('services.pricing_block') }}</h2>
                            </div>
                        </div>

                        <div class="row g-5">
                            <div class="col-md-4">
                                <label class="required fs-6 fw-bold mb-3 d-block">{{ __('services.price') }}</label>
                                <div class="position-relative">
                                    <input type="number" step="0.01" min="0" name="price"
                                        class="form-control form-control-lg ps-12" value="{{ $price }}"
                                        placeholder="0.00" />
                                </div>
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="col-md-4">
                                <label class="fs-6 fw-bold mb-3 d-block">{{ __('services.discount_price') }}</label>
                                <div class="position-relative">
                                    <input type="number" step="0.01" min="0" name="discounted_price"
                                        class="form-control form-control-lg ps-12" value="{{ $discountPrice }}"
                                        placeholder="0.00" />
                                </div>
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="col-md-4">
                                <label class="required fs-6 fw-bold mb-3 d-block">{{ __('services.duration') }}</label>
                                <div class="position-relative">
                                    <input type="number" min="0" name="duration_minutes"
                                        class="form-control form-control-lg pe-16" value="{{ $duration }}"
                                        placeholder="30" />
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
                                <h2 class="text-gray-800 fw-bold mb-0">{{ __('services.additional_settings') }}</h2>
                            </div>
                        </div>

                        <div class="row g-5">
                            <div class="col-md-4">
                                <label class="fs-6 fw-bold mb-3">{{ __('services.points') }}</label>
                                <input type="number" name="points" class="form-control form-control-lg" min="0"
                                    value="{{ $points }}" placeholder="10" />
                                <div class="text-muted fs-7 mt-2">نقاط المكافآت</div>
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="col-md-4">
                                <label class="fs-6 fw-bold mb-3">{{ __('services.sort_order') }}</label>
                                <input type="number" name="sort_order" class="form-control form-control-lg"
                                    min="1" value="{{ $sortOrder }}" placeholder="1" />
                                <div class="text-muted fs-7 mt-2">ترتيب العرض</div>
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="col-md-4">
                                <label class="fs-6 fw-bold mb-5 d-block">{{ __('services.status') }}</label>
                                <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
                                    <input class="form-check-input h-30px w-50px" type="checkbox" name="is_active"
                                        value="1" id="is_active" {{ $isActive ? 'checked' : '' }} />
                                    <label class="form-check-label fw-semibold text-gray-700 ms-3" for="is_active">
                                        {{ __('services.active') }}
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
                                    <i class="ki-duotone ki-picture fs-2x text-info">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <h2 class="text-gray-800 fw-bold mb-1">{{ __('messages.images') }}</h2>
                                <span class="text-muted fs-7">{{ __('services.images_note') }}</span>
                            </div>
                        </div>

                        {{-- الصورة العربية --}}
                        <div class="mb-10">
                            <div class="bg-light-primary rounded p-5">
                                <label class="fs-5 fw-bold text-gray-800 mb-4 d-flex align-items-center">
                                    <span class="badge badge-circle badge-primary me-3">AR</span>
                                    {{ __('services.image_ar') }}
                                </label>

                                <div class="dropzone-custom border-2 border-dashed border-primary rounded text-center p-8"
                                    data-image-input="ar">
                                    <input type="file" name="image_ar" accept=".png,.jpg,.jpeg,.webp" class="d-none"
                                        id="image_ar_input" />

                                    <div class="preview-container {{ $service->hasMedia('image_ar') ? '' : 'd-none' }}">
                                        <img src="{{ $imageArUrl }}" class="mw-100 rounded" style="max-height: 180px;"
                                            id="image_ar_preview" />
                                        <div class="mt-4">
                                            <button type="button" class="btn btn-sm btn-light-primary me-2"
                                                onclick="document.getElementById('image_ar_input').click()">
                                                <i class="ki-duotone ki-pencil fs-4"><span class="path1"></span><span
                                                        class="path2"></span></i>
                                                تغيير
                                            </button>
                                            <button type="button" class="btn btn-sm btn-light-danger"
                                                onclick="removeImage('ar')">
                                                <i class="ki-duotone ki-trash fs-4"><span class="path1"></span><span
                                                        class="path2"></span><span class="path3"></span><span
                                                        class="path4"></span><span class="path5"></span></i>
                                                حذف
                                            </button>
                                        </div>
                                    </div>

                                    <div class="upload-prompt {{ $service->hasMedia('image_ar') ? 'd-none' : '' }}">
                                        <i class="ki-duotone ki-file-up fs-3x text-primary mb-3">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
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
                                    {{ __('services.image_en') }}
                                </label>

                                <div class="dropzone-custom border-2 border-dashed border-success rounded text-center p-8"
                                    data-image-input="en">
                                    <input type="file" name="image_en" accept=".png,.jpg,.jpeg,.webp" class="d-none"
                                        id="image_en_input" />

                                    <div class="preview-container {{ $service->hasMedia('image_en') ? '' : 'd-none' }}">
                                        <img src="{{ $imageEnUrl }}" class="mw-100 rounded" style="max-height: 180px;"
                                            id="image_en_preview" />
                                        <div class="mt-4">
                                            <button type="button" class="btn btn-sm btn-light-success me-2"
                                                onclick="document.getElementById('image_en_input').click()">
                                                <i class="ki-duotone ki-pencil fs-4"><span class="path1"></span><span
                                                        class="path2"></span></i>
                                                تغيير
                                            </button>
                                            <button type="button" class="btn btn-sm btn-light-danger"
                                                onclick="removeImage('en')">
                                                <i class="ki-duotone ki-trash fs-4"><span class="path1"></span><span
                                                        class="path2"></span><span class="path3"></span><span
                                                        class="path4"></span><span class="path5"></span></i>
                                                حذف
                                            </button>
                                        </div>
                                    </div>

                                    <div class="upload-prompt {{ $service->hasMedia('image_en') ? 'd-none' : '' }}">
                                        <i class="ki-duotone ki-file-up fs-3x text-success mb-3">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
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

        {{-- أزرار الحفظ والإلغاء --}}
        <div class="d-flex justify-content-end mt-7 gap-3">
            <button type="submit" class="btn btn-primary btn-lg px-10">
                <span class="indicator-label">{{ __('messages.update') }}</span>
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
            const $form = $('#service_edit_form');

            $form.on('submit', function(e) {
                e.preventDefault();

                const formData = new FormData($form[0]);

                window.KH.setFormLoading($form, true, {
                    text: 'جاري التحديث...'
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
                            title: 'تم',
                            text: res.message || 'تم تحديث الخدمة بنجاح.',
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
                            let msg = 'حدث خطأ غير متوقع.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                msg = xhr.responseJSON.message;
                            }
                            Swal.fire('خطأ', msg, 'error');
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
