@extends('base.layout.app')

@section('title', __('points_settings.title'))

@section('content')

    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <h3 class="fw-bold mb-0">{{ __('points_settings.title') }}</h3>
            </div>
        </div>

        <form method="POST" action="{{ route('dashboard.settings.points.update') }}">
            @csrf
            @method('PUT')

            <div class="card-body">

                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="row g-6">

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            {{ __('points_settings.redeem_points.label') }}
                        </label>
                        <input type="number"
                            class="form-control form-control-solid @error('redeem_points') is-invalid @enderror"
                            name="redeem_points" value="{{ old('redeem_points', $data['redeem_points']) }}" min="1">
                        <div class="form-text">{{ __('points_settings.redeem_points.hint') }}</div>
                        @error('redeem_points')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            {{ __('points_settings.redeem_amount.label') }}
                        </label>
                        <input type="number" step="0.01"
                            class="form-control form-control-solid @error('redeem_amount') is-invalid @enderror"
                            name="redeem_amount" value="{{ old('redeem_amount', $data['redeem_amount']) }}" min="0.01">
                        <div class="form-text">{{ __('points_settings.redeem_amount.hint') }}</div>
                        @error('redeem_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            {{ __('points_settings.min_redeem_points.label') }}
                        </label>
                        <input type="number"
                            class="form-control form-control-solid @error('min_redeem_points') is-invalid @enderror"
                            name="min_redeem_points" value="{{ old('min_redeem_points', $data['min_redeem_points']) }}"
                            min="1">
                        <div class="form-text">{{ __('points_settings.min_redeem_points.hint') }}</div>
                        @error('min_redeem_points')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <div class="separator separator-dashed my-4"></div>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold d-block">
                            {{ app()->getLocale() === 'ar' ? 'منح النقاط تلقائياً عند اكتمال الحجز' : 'Auto-award points on booking completion' }}
                        </label>
                        <div class="form-check form-switch mt-1">
                            <input class="form-check-input" type="checkbox" name="auto_award_booking_points"
                                id="auto_award_booking_points" value="1"
                                {{ old('auto_award_booking_points', $data['auto_award_booking_points'] ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="auto_award_booking_points">
                                {{ app()->getLocale() === 'ar' ? 'مفعّل' : 'Enabled' }}
                            </label>
                        </div>
                        <div class="form-text">
                            {{ app()->getLocale() === 'ar'
                                ? 'عند التعطيل لن تُمنح أي نقاط تلقائية لأي حجز مكتمل بغض النظر عن إعدادات الشريك.'
                                : 'When disabled, no points will be auto-awarded for any completed booking regardless of partner settings.' }}
                        </div>
                    </div>

                </div>

            </div>

            <div class="card-footer d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary">
                    {{ __('points_settings.save') }}
                </button>
            </div>
        </form>
    </div>

@endsection
