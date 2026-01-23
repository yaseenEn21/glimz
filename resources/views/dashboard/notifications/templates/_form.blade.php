@php
    /** @var \App\Models\NotificationTemplate|null $template */
    $template = $template ?? null;
@endphp

@if($errors->any())
    <div class="alert alert-danger mb-4">
        حدثت بعض الأخطاء، يرجى مراجعة الحقول أدناه.
    </div>
@endif

<div class="row g-4">

    {{-- المفتاح (key) --}}
    <div class="col-md-4">
        <label class="form-label fw-bold">المفتاح (Key)</label>
        <input type="text"
               class="form-control form-control-solid"
               value="{{ $template?->key }}"
               disabled>
        <div class="text-muted mt-1 fs-7">
            هذا المفتاح يُستخدم في الكود لاستدعاء القالب (مثال: <code>child_post_created</code>).
        </div>
    </div>

    {{-- الوصف --}}
    <div class="col-md-8">
        <label class="form-label fw-bold">وصف القالب (اختياري)</label>
        <input type="text"
               name="description"
               class="form-control form-control-solid @error('description') is-invalid @enderror"
               value="{{ old('description', $template?->description) }}">
        @error('description')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="text-muted mt-1 fs-7">
            للتوضيح الداخلي فقط، لن يظهر للمستخدمين (مثال: &quot;إشعار عند إضافة منشور للطفل&quot;).
        </div>
    </div>

    {{-- العنوان عربي --}}
    <div class="col-md-6">
        <label class="form-label fw-bold">عنوان الإشعار (عربي) *</label>
        <input type="text"
               name="title"
               class="form-control form-control-solid @error('title') is-invalid @enderror"
               value="{{ old('title', $template?->title) }}"
               required>
        @error('title')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- العنوان إنجليزي --}}
    <div class="col-md-6">
        <label class="form-label fw-bold">عنوان الإشعار (إنجليزي)</label>
        <input type="text"
               name="title_en"
               class="form-control form-control-solid @error('title_en') is-invalid @enderror"
               value="{{ old('title_en', $template?->title_en) }}">
        @error('title_en')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- النص عربي --}}
    <div class="col-md-6">
        <label class="form-label fw-bold">نص الإشعار (عربي) *</label>
        <textarea name="body"
                  class="form-control form-control-solid @error('body') is-invalid @enderror"
                  rows="3"
                  required>{{ old('body', $template?->body) }}</textarea>
        @error('body')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="text-muted mt-1 fs-7">
            يمكنك استخدام متغيرات مثل:
            <code>{child_name}</code> ، <code>{title}</code>،
            وسيتم استبدالها من الكود.
        </div>
    </div>

    {{-- النص إنجليزي --}}
    <div class="col-md-6">
        <label class="form-label fw-bold">نص الإشعار (إنجليزي)</label>
        <textarea name="body_en"
                  class="form-control form-control-solid @error('body_en') is-invalid @enderror"
                  rows="3">{{ old('body_en', $template?->body_en) }}</textarea>
        @error('body_en')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="text-muted mt-1 fs-7">
            اختياري. إذا تُرك فارغًا سيتم استخدام النص العربي عند الإرسال باللغة الإنجليزية أيضًا.
        </div>
    </div>

    {{-- اختيار الأيقونة --}}
<div class="mb-6">
    <label class="form-label fw-bold">اختيار أيقونة الإشعار</label>
    
    <div class="row g-4">
        @if(isset($icons) && count($icons) > 0)
            @foreach($icons as $icon)
                <div class="col-6 col-md-4 col-lg-3">
                    <label class="icon-selector {{ (isset($currentIcon) && $currentIcon === $icon) ? 'selected' : '' }}">
                        <input type="radio" 
                            name="icon" 
                            value="{{ $icon }}" 
                            class="d-none icon-radio"
                            {{ (isset($currentIcon) && $currentIcon === $icon) ? 'checked' : '' }}>
                        
                        <div class="icon-box">
                            <img src="{{ asset('assets/media/icons/duotune/notifications/' . $icon) }}" 
                                alt="{{ $icon }}" 
                                class="icon-preview">
                            <div class="icon-name">{{ pathinfo($icon, PATHINFO_FILENAME) }}</div>
                        </div>
                    </label>
                </div>
            @endforeach
            
            {{-- خيار بدون أيقونة --}}
            <div class="col-6 col-md-4 col-lg-3">
                <label class="icon-selector {{ (!isset($currentIcon) || !$currentIcon) ? 'selected' : '' }}">
                    <input type="radio" 
                        name="icon" 
                        value="" 
                        class="d-none icon-radio"
                        {{ (!isset($currentIcon) || !$currentIcon) ? 'checked' : '' }}>
                    
                    <div class="icon-box">
                        <div class="no-icon-placeholder">
                            <i class="fas fa-ban fs-3x text-muted"></i>
                        </div>
                        <div class="icon-name">بدون أيقونة</div>
                    </div>
                </label>
            </div>
        @else
            <div class="col-12">
                <div class="alert alert-warning">
                    لا توجد أيقونات متاحة في المجلد المحدد.
                </div>
            </div>
        @endif
    </div>
    
    @error('icon')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>

    {{-- حالة القالب --}}
    <div class="col-md-3">
        <label class="form-label fw-bold d-block">حالة القالب</label>
        @php
            $isActiveDefault = $template?->is_active ?? true;
            $isActive = old('is_active', $isActiveDefault) ? true : false;
        @endphp

        <div class="form-check form-switch form-check-custom form-check-solid">
            <input class="form-check-input"
                   type="checkbox"
                   id="template_is_active"
                   name="is_active"
                   value="1"
                   @checked($isActive)>
            <label class="form-check-label ms-2" for="template_is_active">
                <span id="template_is_active_text"
                      data-active-text="مفعّل"
                      data-inactive-text="موقّف"
                      class="{{ $isActive ? 'text-success' : 'text-danger' }}">
                    {{ $isActive ? 'مفعّل' : 'موقّف' }}
                </span>
            </label>
        </div>
        <div class="text-muted mt-1 fs-7">
            إذا كان القالب موقّف لن يتم استخدامه عند الإرسال.
        </div>
    </div>

</div>

@push('custom-script')
<script>
document.addEventListener('DOMContentLoaded', function () {

    $(document).ready(function() {
        // تحديث الواجهة عند اختيار أيقونة
        $('.icon-radio').on('change', function() {
            $('.icon-selector').removeClass('selected');
            $(this).closest('.icon-selector').addClass('selected');
        });
    });

    const input = document.getElementById('template_is_active');
    if (!input) return;

    const textSpan = document.getElementById('template_is_active_text');
    const updateLabel = () => {
        const active = input.checked;
        textSpan.textContent = active ? textSpan.dataset.activeText : textSpan.dataset.inactiveText;
        textSpan.classList.toggle('text-success', active);
        textSpan.classList.toggle('text-danger', !active);
    };

    input.addEventListener('change', updateLabel);
    updateLabel();
});
</script>
@endpush