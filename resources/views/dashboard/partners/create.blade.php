{{-- resources/views/dashboard/partners/create.blade.php --}}
@extends('base.layout.app')

@section('title', __('partners.create'))

@section('content')

    <form action="{{ route('dashboard.partners.store') }}" method="POST">
        @csrf

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('partners.create') }}</h3>
            </div>

            <div class="card-body">
                <div class="row g-5">
                    {{-- Name --}}
                    <div class="col-md-6">
                        <label class="required form-label">{{ __('partners.fields.name') }}</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Username --}}
                    <div class="col-md-6">
                        <label class="required form-label">{{ __('partners.fields.username') }}</label>
                        <input type="text" name="username" class="form-control @error('username') is-invalid @enderror"
                            value="{{ old('username') }}" required placeholder="msmar-services">
                        <div class="form-text">{{ __('partners.username_help') }}</div>
                        @error('username')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div class="col-md-6">
                        <label class="required form-label">{{ __('partners.fields.email') }}</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email') }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Mobile --}}
                    <div class="col-md-6">
                        <label class="form-label">{{ __('partners.fields.mobile') }}</label>
                        <input type="text" name="mobile" class="form-control @error('mobile') is-invalid @enderror"
                            value="{{ old('mobile') }}">
                        @error('mobile')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Daily Booking Limit --}}
                    <div class="col-md-6">
                        <label class="required form-label">{{ __('partners.fields.daily_booking_limit') }}</label>
                        <input type="number" name="daily_booking_limit"
                            class="form-control @error('daily_booking_limit') is-invalid @enderror"
                            value="{{ old('daily_booking_limit', 100) }}" min="1" required>
                        <div class="form-text">{{ __('partners.daily_booking_limit_help') }}</div>
                        @error('daily_booking_limit')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Webhook URL --}}
                    <div class="col-md-6">
                        <label class="form-label">{{ __('partners.fields.webhook_url') }}</label>
                        <input type="url" name="webhook_url"
                            class="form-control @error('webhook_url') is-invalid @enderror"
                            value="{{ old('webhook_url') }}" placeholder="https://partner.com/webhook">
                        <div class="form-text">{{ __('partners.webhook_url_help') }}</div>
                        @error('webhook_url')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-12">
                        <label class="form-label required">{{ __('partners.fields.webhook_type') }}</label>
                        <select name="webhook_type" class="form-select" required>
                            <option value="generic"
                                {{ old('webhook_type', $partner->webhook_type ?? 'generic') === 'generic' ? 'selected' : '' }}>
                                Generic (POST JSON)
                            </option>
                            <option value="mismar"
                                {{ old('webhook_type', $partner->webhook_type ?? '') === 'mismar' ? 'selected' : '' }}>
                                Mismar API
                            </option>
                        </select>
                        <div class="form-text">{{ __('partners.webhook_type_help') }}</div>
                    </div>

                    {{-- Slot Fallback Settings --}}
                    <div class="col-12">
                        <div class="separator my-5"></div>
                        <h4 class="mb-4">
                            {{ app()->getLocale() === 'ar' ? 'إعدادات مطابقة المواعيد' : 'Slot Matching Settings' }}
                        </h4>
                    </div>

                    {{-- Allow Fallback --}}
                    <div class="col-md-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="allow_slot_fallback"
                                id="allow_slot_fallback"
                                {{ old('allow_slot_fallback', $partner->allow_slot_fallback ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="allow_slot_fallback">
                                {{ app()->getLocale() === 'ar' ? 'السماح بمواعيد غير مطابقة بالضبط' : 'Allow non-exact slot matching' }}
                            </label>
                            <div class="form-text text-muted">
                                {{ app()->getLocale() === 'ar'
                                    ? 'إذا كان مفعلاً، النظام يبحث عن أقرب موعد متاح إذا الموعد المطلوب غير متاح'
                                    : 'If enabled, the system finds the nearest available slot when the exact time is unavailable' }}
                            </div>
                        </div>
                    </div>

                    {{-- Fallback Minutes --}}
                    <div class="col-md-4" id="fallback-settings">
                        <label class="form-label">
                            {{ app()->getLocale() === 'ar' ? 'الفرق المسموح (بالدقائق)' : 'Allowed difference (minutes)' }}
                        </label>
                        <input type="number" name="slot_fallback_minutes"
                            class="form-control @error('slot_fallback_minutes') is-invalid @enderror"
                            value="{{ old('slot_fallback_minutes', $partner->slot_fallback_minutes ?? 60) }}"
                            min="5" max="180" step="5">
                        @error('slot_fallback_minutes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Fallback Direction --}}
                    <div class="col-md-4" id="fallback-direction-settings">
                        <label class="form-label">
                            {{ app()->getLocale() === 'ar' ? 'اتجاه البحث' : 'Search direction' }}
                        </label>
                        <select name="slot_fallback_direction" class="form-select">
                            <option value="both"
                                {{ old('slot_fallback_direction', $partner->slot_fallback_direction ?? 'both') === 'both' ? 'selected' : '' }}>
                                {{ app()->getLocale() === 'ar' ? 'قبل وبعد' : 'Before & After' }}
                            </option>
                            <option value="after"
                                {{ old('slot_fallback_direction', $partner->slot_fallback_direction ?? '') === 'after' ? 'selected' : '' }}>
                                {{ app()->getLocale() === 'ar' ? 'بعد فقط (أقرب موعد لاحق)' : 'After only (next available)' }}
                            </option>
                            <option value="before"
                                {{ old('slot_fallback_direction', $partner->slot_fallback_direction ?? '') === 'before' ? 'selected' : '' }}>
                                {{ app()->getLocale() === 'ar' ? 'قبل فقط (أقرب موعد سابق)' : 'Before only (previous available)' }}
                            </option>
                        </select>
                    </div>

                    {{-- Is Active --}}
                    <div class="col-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                                {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                {{ __('partners.fields.is_active') }}
                            </label>
                        </div>
                    </div>

                    {{-- Allow Customer Points --}}
                    <div class="col-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="allow_customer_points"
                                id="allow_customer_points" {{ old('allow_customer_points', false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="allow_customer_points">
                                {{ app()->getLocale() === 'ar'
                                    ? 'السماح بمنح النقاط لزبائن هذا الشريك'
                                    : 'Allow points for this partner\'s customers' }}
                            </label>
                            <div class="form-text text-muted">
                                {{ app()->getLocale() === 'ar'
                                    ? 'إذا كان مفعلاً، سيحصل زبائن حجوزات هذا الشريك على نقاطهم عند اكتمال الحجز.'
                                    : 'If enabled, customers of this partner\'s bookings will earn points on completion.' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary">
                    {{ __('partners.save') }}
                </button>
            </div>
        </div>
    </form>

@endsection
@push('custom-script')
    <script>
        // إخفاء/إظهار إعدادات الـ fallback
        const toggle = document.getElementById('allow_slot_fallback');
        const settings = document.getElementById('fallback-settings');
        const direction = document.getElementById('fallback-direction-settings');

        function updateVisibility() {
            const show = toggle.checked;
            settings.style.display = show ? '' : 'none';
            direction.style.display = show ? '' : 'none';
        }

        toggle.addEventListener('change', updateVisibility);
        updateVisibility();
    </script>
@endpush
