@extends('base.layout.app')

@section('title', __('partners.edit'))

@section('content')

    <form action="{{ route('dashboard.partners.update', $partner) }}" method="POST">
        @csrf
        @method('PUT')

        {{-- ═══════════════════════════════════════════════════ --}}
        {{-- 1. البيانات الأساسية --}}
        {{-- ═══════════════════════════════════════════════════ --}}
        <div class="card mb-5">
            <div class="card-header">
                <h3 class="card-title">
                    {{ app()->getLocale() === 'ar' ? 'البيانات الأساسية' : 'Basic Information' }}
                </h3>
            </div>
            <div class="card-body">
                <div class="row g-5">
                    {{-- Name --}}
                    <div class="col-md-6">
                        <label class="required form-label">{{ __('partners.fields.name') }}</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $partner->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Username --}}
                    <div class="col-md-6">
                        <label class="required form-label">{{ __('partners.fields.username') }}</label>
                        <input type="text" name="username"
                            class="form-control @error('username') is-invalid @enderror"
                            value="{{ old('username', $partner->username) }}" required placeholder="msmar-services">
                        <div class="form-text">{{ __('partners.username_help') }}</div>
                        @error('username')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div class="col-md-6">
                        <label class="required form-label">{{ __('partners.fields.email') }}</label>
                        <input type="email" name="email"
                            class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email', $partner->email) }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Mobile --}}
                    <div class="col-md-6">
                        <label class="form-label">{{ __('partners.fields.mobile') }}</label>
                        <input type="text" name="mobile"
                            class="form-control @error('mobile') is-invalid @enderror"
                            value="{{ old('mobile', $partner->mobile) }}">
                        @error('mobile')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Daily Booking Limit --}}
                    <div class="col-md-6">
                        <label class="required form-label">{{ __('partners.fields.daily_booking_limit') }}</label>
                        <input type="number" name="daily_booking_limit"
                            class="form-control @error('daily_booking_limit') is-invalid @enderror"
                            value="{{ old('daily_booking_limit', $partner->daily_booking_limit) }}" min="1"
                            required>
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
                            value="{{ old('webhook_url', $partner->webhook_url) }}"
                            placeholder="https://partner.com/webhook">
                        <div class="form-text">{{ __('partners.webhook_url_help') }}</div>
                        @error('webhook_url')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════ --}}
        {{-- 2. إعدادات مطابقة المواعيد --}}
        {{-- ═══════════════════════════════════════════════════ --}}
        <div class="card mb-5">
            <div class="card-header">
                <h3 class="card-title">
                    {{ app()->getLocale() === 'ar' ? 'إعدادات مطابقة المواعيد' : 'Slot Matching Settings' }}
                </h3>
            </div>
            <div class="card-body">
                <div class="row g-5">

                    {{-- Allow Fallback — سطر كامل --}}
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox"
                                   name="allow_slot_fallback" id="allow_slot_fallback"
                                   {{ old('allow_slot_fallback', $partner->allow_slot_fallback) ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="allow_slot_fallback">
                                {{ app()->getLocale() === 'ar'
                                    ? 'السماح بمواعيد غير مطابقة بالضبط'
                                    : 'Allow non-exact slot matching' }}
                            </label>
                        </div>
                        <div class="form-text text-muted mt-1 ms-10">
                            {{ app()->getLocale() === 'ar'
                                ? 'إذا كان مفعلاً، النظام يبحث عن أقرب موعد متاح إذا الموعد المطلوب غير متاح. إذا كان معطلاً، يُقبل فقط الموعد المطابق بالضبط.'
                                : 'If enabled, the system finds the nearest available slot when the exact time is unavailable. If disabled, only exact time matches are accepted.' }}
                        </div>
                    </div>

                    {{-- Fallback Minutes + Direction — سطر واحد --}}
                    <div class="col-md-6" id="fallback-settings">
                        <label class="form-label">
                            {{ app()->getLocale() === 'ar' ? 'الفرق المسموح (بالدقائق)' : 'Allowed difference (minutes)' }}
                        </label>
                        <input type="number" name="slot_fallback_minutes"
                            class="form-control @error('slot_fallback_minutes') is-invalid @enderror"
                            value="{{ old('slot_fallback_minutes', $partner->slot_fallback_minutes) }}"
                            min="5" max="180" step="5">
                        <div class="form-text">
                            {{ app()->getLocale() === 'ar'
                                ? 'أقصى فرق زمني مسموح بين الموعد المطلوب والموعد المتاح'
                                : 'Maximum time difference between requested and available slot' }}
                        </div>
                        @error('slot_fallback_minutes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6" id="fallback-direction-settings">
                        <label class="form-label">
                            {{ app()->getLocale() === 'ar' ? 'اتجاه البحث' : 'Search direction' }}
                        </label>
                        <select name="slot_fallback_direction" class="form-select">
                            <option value="both"
                                {{ old('slot_fallback_direction', $partner->slot_fallback_direction) === 'both' ? 'selected' : '' }}>
                                {{ app()->getLocale() === 'ar' ? 'قبل وبعد' : 'Before & After' }}
                            </option>
                            <option value="after"
                                {{ old('slot_fallback_direction', $partner->slot_fallback_direction) === 'after' ? 'selected' : '' }}>
                                {{ app()->getLocale() === 'ar' ? 'بعد فقط (أقرب موعد لاحق)' : 'After only (next available)' }}
                            </option>
                            <option value="before"
                                {{ old('slot_fallback_direction', $partner->slot_fallback_direction) === 'before' ? 'selected' : '' }}>
                                {{ app()->getLocale() === 'ar' ? 'قبل فقط (أقرب موعد سابق)' : 'Before only (previous available)' }}
                            </option>
                        </select>
                        <div class="form-text">
                            {{ app()->getLocale() === 'ar'
                                ? 'هل يبحث النظام عن مواعيد قبل الوقت المطلوب، بعده، أو كلاهما'
                                : 'Should the system look for slots before, after, or both directions from requested time' }}
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════ --}}
        {{-- 3. الإعدادات العامة --}}
        {{-- ═══════════════════════════════════════════════════ --}}
        <div class="card mb-5">
            <div class="card-header">
                <h3 class="card-title">
                    {{ app()->getLocale() === 'ar' ? 'الإعدادات العامة' : 'General Settings' }}
                </h3>
            </div>
            <div class="card-body">
                <div class="row g-5">
                    {{-- Is Active --}}
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                                {{ old('is_active', $partner->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="is_active">
                                {{ __('partners.fields.is_active') }}
                            </label>
                        </div>
                    </div>

                    {{-- Allow Customer Points --}}
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="allow_customer_points"
                                id="allow_customer_points"
                                {{ old('allow_customer_points', $partner->allow_customer_points) ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="allow_customer_points">
                                {{ app()->getLocale() === 'ar'
                                    ? 'السماح بمنح النقاط لزبائن هذا الشريك'
                                    : 'Allow points for this partner\'s customers' }}
                            </label>
                        </div>
                        <div class="form-text text-muted mt-1 ms-10">
                            {{ app()->getLocale() === 'ar'
                                ? 'إذا كان مفعلاً، سيحصل زبائن حجوزات هذا الشريك على نقاطهم عند اكتمال الحجز.'
                                : 'If enabled, customers of this partner\'s bookings will earn points on completion.' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════ --}}
        {{-- أزرار الحفظ --}}
        {{-- ═══════════════════════════════════════════════════ --}}
        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('dashboard.partners.show', $partner) }}" class="btn btn-light">
                {{ __('partners.cancel') }}
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="ki-duotone ki-check fs-2"></i>
                {{ __('partners.save') }}
            </button>
        </div>
    </form>

@endsection

@push('custom-script')
    <script>
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