@php
    $isEdit = isset($customer);
@endphp

<div class="row g-6">
    <div class="col-lg-4">
        <div class="card card-flush">
            <div class="card-header pt-6">
                <div class="card-title">
                    <h3 class="fw-bold mb-0">{{ __('customers.settings') }}</h3>
                </div>
            </div>
            <div class="card-body">
                <div class="form-check form-switch form-check-custom form-check-solid mb-4">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1"
                        {{ old('is_active', $isEdit ? $customer->is_active : true) ? 'checked' : '' }}>
                    <label class="form-check-label">{{ __('customers.fields.is_active') }}</label>
                </div>

                <div class="form-check form-switch form-check-custom form-check-solid">
                    <input class="form-check-input" type="checkbox" name="notification" value="1"
                        {{ old('notification', $isEdit ? $customer->notification : true) ? 'checked' : '' }}>
                    <label class="form-check-label">{{ __('customers.fields.notification') }}</label>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card card-flush">
            <div class="card-header pt-6">
                <div class="card-title">
                    <h3 class="fw-bold mb-0">{{ __('customers.details') }}</h3>
                </div>
            </div>

            <div class="card-body">
                <div class="row g-6">
                    <div class="col-md-6">
                        <label class="required fw-semibold fs-6 mb-2">{{ __('customers.fields.name') }}</label>
                        <input type="text" name="name" class="form-control"
                            value="{{ old('name', $isEdit ? $customer->name : '') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="required fw-semibold fs-6 mb-2">{{ __('customers.fields.mobile') }}</label>
                        <input type="text" name="mobile" class="form-control"
                            value="{{ old('mobile', $isEdit ? $customer->mobile : '') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="fw-semibold fs-6 mb-2">{{ __('customers.fields.email') }}</label>
                        <input type="email" name="email" class="form-control"
                            value="{{ old('email', $isEdit ? $customer->email : '') }}">
                    </div>

                    <div class="col-md-3">
                        <label class="fw-semibold fs-6 mb-2">{{ __('customers.fields.birth_date') }}</label>
                        <input type="date" name="birth_date" class="form-control"
                            value="{{ old('birth_date', $isEdit && $customer->birth_date ? $customer->birth_date->format('Y-m-d') : '') }}">
                    </div>

                    <div class="col-md-3">
                        <label class="required fw-semibold fs-6 mb-2">{{ __('customers.fields.gender') }}</label>
                        <select name="gender" class="form-select">
                            @php $g = old('gender', $isEdit ? $customer->gender : 'male'); @endphp
                            <option value="male" {{ $g==='male'?'selected':'' }}>{{ __('customers.genders.male') }}</option>
                            <option value="female" {{ $g==='female'?'selected':'' }}>{{ __('customers.genders.female') }}</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="fw-semibold fs-6 mb-2">{{ __('customers.fields.customer_group') }}</label>
                        <select name="customer_group_id" class="form-select" data-control="select2" data-placeholder="{{ __('customers.placeholders.customer_group') }}">
                            <option value="">{{ __('customers.none') }}</option>
                            @foreach($groups as $gr)
                                <option value="{{ $gr->id }}"
                                    {{ (string)old('customer_group_id', $isEdit ? $customer->customer_group_id : '') === (string)$gr->id ? 'selected' : '' }}>
                                    {{ $gr->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    
                </div>
            </div>
        </div>
    </div>
</div>