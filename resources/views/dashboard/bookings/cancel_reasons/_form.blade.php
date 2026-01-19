@csrf

<div class="row g-6">

    <div class="col-md-4">
        <label class="fw-semibold fs-6 mb-2">{{ __('bookings.cancel_reasons.fields.code') }}</label>
        <input type="text" name="code" class="form-control"
               value="{{ old('code', $reason['code'] ?? '') }}"
               placeholder="CHANGE_TIME">
    </div>

    <div class="col-md-4">
        <label class="required fw-semibold fs-6 mb-2">{{ __('bookings.cancel_reasons.fields.name_ar') }}</label>
        <input type="text" name="name_ar" class="form-control"
               value="{{ old('name_ar', $reason['name']['ar'] ?? '') }}">
    </div>

    <div class="col-md-4">
        <label class="required fw-semibold fs-6 mb-2">{{ __('bookings.cancel_reasons.fields.name_en') }}</label>
        <input type="text" name="name_en" class="form-control"
               value="{{ old('name_en', $reason['name']['en'] ?? '') }}">
    </div>

    <div class="col-md-3">
        <label class="fw-semibold fs-6 mb-2">{{ __('bookings.cancel_reasons.fields.sort') }}</label>
        <input type="number" min="0" name="sort" class="form-control"
               value="{{ old('sort', $reason['sort'] ?? 0) }}">
    </div>

    <div class="col-md-3">
        <label class="fw-semibold fs-6 mb-2 d-block">{{ __('bookings.cancel_reasons.fields.is_active') }}</label>
        <div class="form-check form-switch form-check-custom form-check-solid">
            <input class="form-check-input" type="checkbox" name="is_active" value="1"
                   {{ old('is_active', ($reason['is_active'] ?? true)) ? 'checked' : '' }}>
            <label class="form-check-label">{{ __('bookings.cancel_reasons.active') }}</label>
        </div>
    </div>

</div>