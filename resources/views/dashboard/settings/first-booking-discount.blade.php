@extends('base.layout.app')

@section('title', app()->getLocale() === 'ar' ? 'خصم أول حجز' : 'First Booking Discount')

@section('content')

<form method="POST" action="{{ route('dashboard.settings.first-booking-discount.update') }}">
    @csrf
    @method('PUT')

    {{-- ─── Header Card ─────────────────────────────────────────── --}}
    <div class="card mb-6">
        <div class="card-header border-0 pt-6">
            <div class="d-flex align-items-center gap-4">
                <div class="symbol symbol-50px symbol-light-primary">
                    <span class="symbol-label">
                        <i class="ki-duotone ki-discount fs-2x text-primary">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </span>
                </div>
                <div>
                    <h3 class="fw-bold mb-1">
                        {{ app()->getLocale() === 'ar' ? 'خصم أول حجز' : 'First Booking Discount' }}
                    </h3>
                    <span class="text-muted fw-semibold fs-7">
                        {{ app()->getLocale() === 'ar'
                            ? 'يُطبَّق تلقائياً على أول حجز مكتمل للعميل (غير الملغي)'
                            : 'Automatically applied to the customer\'s first non-cancelled booking' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-6">

        {{-- ─── الإعدادات الرئيسية ──────────────────────────────── --}}
        <div class="col-xl-8">
            <div class="card card-flush h-100">
                <div class="card-header border-0 pt-6">
                    <h3 class="card-title fw-bold fs-4">
                        {{ app()->getLocale() === 'ar' ? 'إعدادات الخصم' : 'Discount Settings' }}
                    </h3>
                </div>

                <div class="card-body pt-0">

                    @if(session('success'))
                        <div class="alert alert-success mb-6">{{ session('success') }}</div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger mb-6">
                            <ul class="mb-0">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- نوع الخصم --}}
                    <div class="mb-6">
                        <label class="required fw-semibold fs-6 mb-3 d-block">
                            {{ app()->getLocale() === 'ar' ? 'نوع الخصم' : 'Discount Type' }}
                        </label>

                        <div class="row g-3" id="discount_type_options">
                            @php
                                $types = [
                                    'percentage' => [
                                        'label' => app()->getLocale() === 'ar' ? 'نسبة مئوية' : 'Percentage',
                                        'desc'  => app()->getLocale() === 'ar' ? 'مثال: خصم 20% من السعر الأصلي' : 'E.g. 20% off original price',
                                        'icon'  => 'ki-percentage',
                                        'color' => 'primary',
                                    ],
                                    'fixed' => [
                                        'label' => app()->getLocale() === 'ar' ? 'مبلغ ثابت' : 'Fixed Amount',
                                        'desc'  => app()->getLocale() === 'ar' ? 'مثال: خصم 30 ريال من السعر' : 'E.g. 30 SAR off',
                                        'icon'  => 'ki-price-tag',
                                        'color' => 'success',
                                    ],
                                    'special_price' => [
                                        'label' => app()->getLocale() === 'ar' ? 'سعر خاص' : 'Special Price',
                                        'desc'  => app()->getLocale() === 'ar' ? 'مثال: سعر أول حجز = 49 ريال' : 'E.g. First booking price = 49 SAR',
                                        'icon'  => 'ki-star',
                                        'color' => 'warning',
                                    ],
                                ];
                            @endphp

                            @foreach($types as $typeKey => $typeDef)
                                <div class="col-md-4">
                                    <label class="discount-type-card d-flex flex-column align-items-center p-5 border border-2 rounded cursor-pointer
                                        {{ old('discount_type', $config['discount_type']) === $typeKey ? 'border-' . $typeDef['color'] . ' bg-light-' . $typeDef['color'] : 'border-dashed border-gray-300' }}"
                                        for="type_{{ $typeKey }}">
                                        <input type="radio" name="discount_type" id="type_{{ $typeKey }}"
                                            value="{{ $typeKey }}" class="d-none discount-type-radio"
                                            {{ old('discount_type', $config['discount_type']) === $typeKey ? 'checked' : '' }}>

                                        <i class="ki-duotone {{ $typeDef['icon'] }} fs-2x text-{{ $typeDef['color'] }} mb-2">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                        <span class="fw-bold fs-6 mb-1">{{ $typeDef['label'] }}</span>
                                        <span class="text-muted fs-7 text-center">{{ $typeDef['desc'] }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- قيمة الخصم --}}
                    <div class="mb-6">
                        <label class="required fw-semibold fs-6 mb-2" id="discount_value_label">
                            {{ app()->getLocale() === 'ar' ? 'قيمة الخصم' : 'Discount Value' }}
                        </label>
                        <div class="input-group">
                            <input type="number"
                                name="discount_value"
                                id="discount_value"
                                class="form-control form-control-solid @error('discount_value') is-invalid @enderror"
                                value="{{ old('discount_value', $config['discount_value']) }}"
                                min="0"
                                step="0.01">
                            <span class="input-group-text fw-bold" id="discount_value_suffix">
                                %
                            </span>
                        </div>
                        @error('discount_value')
                            <div class="text-danger fs-7 mt-1">{{ $message }}</div>
                        @enderror
                        <div class="text-muted fs-7 mt-1" id="discount_value_hint"></div>
                    </div>

                    {{-- الخدمات المشمولة --}}
                    <div class="mb-6">
                        <label class="fw-semibold fs-6 mb-2">
                            {{ app()->getLocale() === 'ar' ? 'تطبيق على الخدمات' : 'Apply To Services' }}
                        </label>
                        <select name="applies_to_service_ids[]"
                            class="form-select"
                            data-control="select2"
                            data-placeholder="{{ app()->getLocale() === 'ar' ? 'اتركه فارغاً لتطبيقه على جميع الخدمات' : 'Leave empty to apply to all services' }}"
                            multiple>
                            @php $locale = app()->getLocale(); @endphp
                            @foreach($services as $svc)
                                @php
                                    $sName = is_array($svc->name)
                                        ? ($svc->name[$locale] ?? reset($svc->name) ?? '')
                                        : $svc->name;
                                @endphp
                                <option value="{{ $svc->id }}"
                                    {{ in_array($svc->id, old('applies_to_service_ids', $config['applies_to_service_ids'] ?? [])) ? 'selected' : '' }}>
                                    {{ $sName }}
                                </option>
                            @endforeach
                        </select>
                        <div class="text-muted fs-7 mt-1">
                            {{ app()->getLocale() === 'ar'
                                ? 'إذا تركته فارغاً سيُطبَّق الخصم على جميع الخدمات.'
                                : 'If left empty, the discount applies to all services.' }}
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- ─── الحالة والمعاينة ───────────────────────────────── --}}
        <div class="col-xl-4">

            {{-- تفعيل/تعطيل --}}
            <div class="card card-flush mb-6">
                <div class="card-header border-0 pt-6">
                    <h3 class="card-title fw-bold fs-5">
                        {{ app()->getLocale() === 'ar' ? 'حالة الخصم' : 'Discount Status' }}
                    </h3>
                </div>
                <div class="card-body pt-0">
                    <div class="form-check form-switch form-check-custom form-check-solid">
                        <input class="form-check-input" type="checkbox"
                            name="is_active" id="is_active" value="1"
                            {{ old('is_active', $config['is_active']) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold fs-6" for="is_active">
                            {{ app()->getLocale() === 'ar' ? 'تفعيل خصم أول حجز' : 'Enable First Booking Discount' }}
                        </label>
                    </div>
                    <div class="text-muted fs-7 mt-3">
                        {{ app()->getLocale() === 'ar'
                            ? 'عند التعطيل لن يُطبَّق أي خصم على الحجوزات الجديدة.'
                            : 'When disabled, no discount will be applied to new bookings.' }}
                    </div>
                </div>
            </div>

            {{-- معاينة --}}
            <div class="card card-flush border border-dashed border-primary">
                <div class="card-header border-0 pt-6">
                    <h3 class="card-title fw-bold fs-5 text-primary">
                        <i class="ki-duotone ki-eye fs-3 text-primary me-2">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                        {{ app()->getLocale() === 'ar' ? 'معاينة' : 'Preview' }}
                    </h3>
                </div>
                <div class="card-body pt-0">
                    <div class="bg-light-primary rounded p-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted fs-7">{{ app()->getLocale() === 'ar' ? 'السعر الأصلي' : 'Original Price' }}</span>
                            <span class="fw-bold" id="preview_original">100 SAR</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 text-danger">
                            <span class="fs-7">{{ app()->getLocale() === 'ar' ? 'خصم أول حجز' : 'First Booking Discount' }}</span>
                            <span class="fw-bold" id="preview_discount">-0 SAR</span>
                        </div>
                        <div class="separator separator-dashed my-2"></div>
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">{{ app()->getLocale() === 'ar' ? 'السعر النهائي' : 'Final Price' }}</span>
                            <span class="fw-bold text-primary fs-5" id="preview_final">100 SAR</span>
                        </div>
                    </div>
                    <div class="text-muted fs-8 mt-2 text-center">
                        {{ app()->getLocale() === 'ar' ? '* المعاينة بناءً على سعر افتراضي 100 ريال' : '* Preview based on 100 SAR sample price' }}
                    </div>
                </div>
            </div>

        </div>

    </div>

    {{-- زر الحفظ --}}
    <div class="d-flex justify-content-end mt-6">
        <button type="submit" class="btn btn-primary px-10">
            <i class="ki-duotone ki-check fs-3 me-2"><span class="path1"></span><span class="path2"></span></i>
            {{ app()->getLocale() === 'ar' ? 'حفظ الإعدادات' : 'Save Settings' }}
        </button>
    </div>
</form>

@endsection

@push('custom-script')
<script>
(function () {
    const isAr = {{ app()->getLocale() === 'ar' ? 'true' : 'false' }};

    const hints = {
        percentage: isAr ? 'أدخل نسبة من 0 إلى 100' : 'Enter a value from 0 to 100',
        fixed: isAr ? 'أدخل مبلغ الخصم بالريال' : 'Enter discount amount in SAR',
        special_price: isAr ? 'أدخل السعر الخاص لأول حجز' : 'Enter the special price for first booking',
    };

    const suffixes = {
        percentage: '%',
        fixed: 'SAR',
        special_price: 'SAR',
    };

    const colorMap = {
        percentage: 'primary',
        fixed: 'success',
        special_price: 'warning',
    };

    function getSelectedType() {
        return document.querySelector('input[name="discount_type"]:checked')?.value ?? 'percentage';
    }

    function updateUI() {
        const type = getSelectedType();
        const value = parseFloat(document.getElementById('discount_value').value) || 0;
        const samplePrice = 100;

        // suffix
        document.getElementById('discount_value_suffix').textContent = suffixes[type];

        // hint
        document.getElementById('discount_value_hint').textContent = hints[type];

        // preview
        let finalPrice;
        let discountAmount;

        if (type === 'percentage') {
            discountAmount = samplePrice * (value / 100);
            finalPrice = Math.max(0, samplePrice - discountAmount);
        } else if (type === 'fixed') {
            discountAmount = value;
            finalPrice = Math.max(0, samplePrice - discountAmount);
        } else {
            finalPrice = value;
            discountAmount = Math.max(0, samplePrice - finalPrice);
        }

        document.getElementById('preview_discount').textContent = `-${discountAmount.toFixed(2)} SAR`;
        document.getElementById('preview_final').textContent = `${finalPrice.toFixed(2)} SAR`;

        // label
        const labelMap = {
            percentage: isAr ? 'قيمة النسبة (%)' : 'Percentage Value (%)',
            fixed: isAr ? 'مبلغ الخصم (SAR)' : 'Discount Amount (SAR)',
            special_price: isAr ? 'السعر الخاص (SAR)' : 'Special Price (SAR)',
        };
        document.getElementById('discount_value_label').textContent = labelMap[type];

        // card highlight
        document.querySelectorAll('.discount-type-card').forEach(card => {
            const radio = card.querySelector('input[type="radio"]');
            const t = radio.value;
            const color = colorMap[t];
            card.classList.remove(
                'border-primary', 'bg-light-primary',
                'border-success', 'bg-light-success',
                'border-warning', 'bg-light-warning',
                'border-dashed', 'border-gray-300'
            );
            if (radio.checked) {
                card.classList.add(`border-${color}`, `bg-light-${color}`);
            } else {
                card.classList.add('border-dashed', 'border-gray-300');
            }
        });
    }

    // Events
    document.querySelectorAll('.discount-type-radio').forEach(radio => {
        radio.addEventListener('change', updateUI);
    });

    document.querySelectorAll('.discount-type-card').forEach(card => {
        card.addEventListener('click', function () {
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;
            updateUI();
        });
    });

    document.getElementById('discount_value').addEventListener('input', updateUI);

    // init
    updateUI();
})();
</script>
@endpush