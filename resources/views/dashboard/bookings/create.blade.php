@extends('base.layout.app')

@section('title', __('bookings.create.title'))

@push('custom-style')
    <style>
        /* ===== Wizard Shell ===== */
        .wizard-shell {
            display: flex;
            gap: 1.5rem;
            align-items: flex-start;
        }

        /* ===== Sidebar Stepper ===== */
        .wizard-sidebar {
            position: sticky;
            top: 90px;
            width: 280px;
            min-width: 280px;
            background: linear-gradient(160deg, #1B2A4A 0%, #0F1C34 100%);
            border-radius: 1.25rem;
            padding: 2rem 1.5rem;
            color: #fff;
            box-shadow: 0 12px 40px rgba(15, 28, 52, .25);
        }

        .wizard-sidebar .brand-label {
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            opacity: .45;
            margin-bottom: 2rem;
            font-weight: 600;
        }

        .step-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: .85rem 0;
            cursor: pointer;
            position: relative;
            transition: all .25s;
        }

        .step-item:not(:last-child)::after {
            content: '';
            position: absolute;
            left: 17px;
            top: 52px;
            width: 2px;
            height: calc(100% - 32px);
            background: rgba(255, 255, 255, .1);
        }

        .step-item.completed:not(:last-child)::after {
            background: rgba(80, 205, 137, .5);
        }

        .step-dot {
            width: 36px;
            height: 36px;
            min-width: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: .8rem;
            background: rgba(255, 255, 255, .06);
            border: 2px solid rgba(255, 255, 255, .12);
            transition: all .3s;
        }

        .step-item.active .step-dot {
            background: #3B82F6;
            border-color: #3B82F6;
            box-shadow: 0 0 0 5px rgba(59, 130, 246, .25);
        }

        .step-item.completed .step-dot {
            background: #10B981;
            border-color: #10B981;
        }

        .step-item.completed .step-dot::after {
            content: '✓';
        }

        .step-info .step-title {
            font-weight: 600;
            font-size: .95rem;
            margin-bottom: .15rem;
            opacity: .55;
            transition: opacity .25s;
        }

        .step-item.active .step-title,
        .step-item.completed .step-title {
            opacity: 1;
        }

        .step-info .step-desc {
            font-size: .75rem;
            opacity: .35;
            line-height: 1.4;
        }

        .step-item.active .step-desc {
            opacity: .6;
        }

        /* ===== Main Content ===== */
        .wizard-main {
            flex: 1;
            min-width: 0;
        }

        .wizard-step-panel {
            display: none;
        }

        .wizard-step-panel.active {
            display: block;
            animation: wz-fadeUp .35s ease-out;
        }

        @keyframes wz-fadeUp {
            from {
                opacity: 0;
                transform: translateY(12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Section blocks */
        .section-block {
            background: #fff;
            border: 1px solid #EEF0F4;
            border-radius: 1rem;
            padding: 1.75rem;
            margin-bottom: 1.25rem;
            transition: box-shadow .2s;
        }

        .section-block:hover {
            box-shadow: 0 4px 20px rgba(0, 0, 0, .04);
        }

        .section-block .block-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .section-block .block-icon {
            width: 42px;
            height: 42px;
            border-radius: .75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .section-block .block-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: #1B2A4A;
            margin: 0;
        }

        .section-block .block-subtitle {
            font-size: .78rem;
            color: #9CA3AF;
            margin: 0;
        }

        /* Slot Grid Redesign */
        .slot-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: .6rem;
        }

        .slot-btn {
            border: 2px solid #EEF0F4;
            border-radius: .85rem;
            padding: .7rem .85rem;
            cursor: pointer;
            text-align: center;
            transition: all .2s;
            background: #FAFBFC;
        }

        .slot-btn:hover {
            border-color: #C7D2FE;
            background: #EEF2FF;
        }

        .slot-btn.active {
            border-color: #3B82F6;
            background: linear-gradient(135deg, #EEF2FF, #DBEAFE);
            box-shadow: 0 2px 12px rgba(59, 130, 246, .15);
        }

        /* Summary Floating */
        .summary-float {
            background: linear-gradient(135deg, #F0FDF4, #ECFDF5);
            border: 1px solid #BBF7D0;
            border-radius: 1rem;
            padding: 1.5rem;
        }

        .summary-float .sum-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: .55rem 0;
        }

        .summary-float .sum-row:not(:last-child) {
            border-bottom: 1px dashed #D1FAE5;
        }

        /* Navigation Bar */
        .wizard-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem 1.75rem;
            background: #fff;
            border: 1px solid #EEF0F4;
            border-radius: 1rem;
            margin-top: 1.25rem;
        }

        .wizard-nav .btn-wz {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .65rem 1.75rem;
            border-radius: .75rem;
            font-weight: 600;
            font-size: .9rem;
            transition: all .2s;
        }

        .btn-wz-prev {
            background: #F3F4F6;
            color: #6B7280;
            border: none;
        }

        .btn-wz-prev:hover {
            background: #E5E7EB;
            color: #374151;
        }

        .btn-wz-next {
            background: #3B82F6;
            color: #fff;
            border: none;
        }

        .btn-wz-next:hover {
            background: #2563EB;
        }

        .btn-wz-submit {
            background: #10B981;
            color: #fff;
            border: none;
        }

        .btn-wz-submit:hover {
            background: #059669;
        }

        /* Info Banners */
        .info-banner {
            display: flex;
            align-items: flex-start;
            gap: .85rem;
            background: #FFF7ED;
            border: 1px solid #FED7AA;
            border-radius: .85rem;
            padding: 1rem 1.25rem;
            font-size: .85rem;
            color: #92400E;
        }

        .info-banner.blue {
            background: #EFF6FF;
            border-color: #BFDBFE;
            color: #1E40AF;
        }

        .info-banner.amber {
            background: #FFFBEB;
            border-color: #FDE68A;
            color: #92400E;
        }

        /* Product Table Redesign */
        .product-row-card {
            background: #FAFBFC;
            border: 1px solid #EEF0F4;
            border-radius: .85rem;
            padding: 1rem 1.25rem;
            margin-bottom: .6rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .product-row-card .prod-field {
            flex: 1;
        }

        .product-row-card .prod-qty {
            width: 100px;
        }

        /* Responsive */
        @media (max-width: 991px) {
            .wizard-shell {
                flex-direction: column;
            }

            .wizard-sidebar {
                position: static;
                width: 100%;
                min-width: auto;
                padding: 1.25rem;
            }

            .step-item:not(:last-child)::after {
                display: none;
            }
        }
    </style>
@endpush

@section('content')

@section('top-btns')
    <a href="{{ route('dashboard.bookings.index') }}" class="btn btn-light">{{ __('bookings.back_to_list') }}</a>
@endsection

<form id="booking_form" action="{{ route('dashboard.bookings.store') }}" method="POST">
    @csrf
    <div id="form_result" class="alert d-none mb-4"></div>

    <div class="wizard-shell">

        {{-- ===== LEFT: Sidebar Stepper ===== --}}
        <div class="wizard-sidebar">
            <div class="brand-label">{{ __('bookings.create.title') }}</div>

            <div class="step-item active" data-step="1" onclick="goToStep(1)">
                <div class="step-dot">1</div>
                <div class="step-info">
                    <div class="step-title">{{ __('bookings.tabs.customer') }}</div>
                    <div class="step-desc">{{ __('bookings.customer.title') }} &amp; {{ __('bookings.car.title') }}</div>
                </div>
            </div>

            <div class="step-item" data-step="2" onclick="goToStep(2)">
                <div class="step-dot">2</div>
                <div class="step-info">
                    <div class="step-title">{{ __('bookings.tabs.booking') }}</div>
                    <div class="step-desc">{{ __('bookings.details.title') }}</div>
                </div>
            </div>

            <div class="step-item" data-step="3" onclick="goToStep(3)">
                <div class="step-dot">3</div>
                <div class="step-info">
                    <div class="step-title">{{ __('bookings.tabs.products') }}</div>
                    <div class="step-desc">{{ __('bookings.products.title') }}</div>
                </div>
            </div>
        </div>

        {{-- ===== RIGHT: Main Panels ===== --}}
        <div class="wizard-main">

            {{-- =========================
                 STEP 1 – Customer + Car + Address
            ========================== --}}
            <div class="wizard-step-panel active" data-panel="1">

                {{-- Customer Block --}}
                <div class="section-block">
                    <div class="block-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="block-icon bg-light-primary text-primary">
                                <i class="ki-duotone ki-profile-circle fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                            </div>
                            <div>
                                <h4 class="block-title">{{ __('bookings.customer.title') }}</h4>
                                <p class="block-subtitle">{{ __('bookings.customer.notice') }}</p>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-light-primary" data-bs-toggle="modal"
                            data-bs-target="#modal_customer">
                            <i class="ki-duotone ki-plus fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
                            {{ __('bookings.customer.add_new') }}
                        </button>
                    </div>

                    <div class="row">
                        <div class="col-lg-8">
                            <label class="required fw-semibold fs-6 mb-2">{{ __('bookings.fields.user') }}</label>
                            <select id="user_id" name="user_id" class="form-select"
                                data-placeholder="{{ __('bookings.placeholders.user') }}">
                                <option></option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>

                {{-- Car Block --}}
                <div class="section-block">
                    <div class="block-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="block-icon bg-light-warning text-warning">
                                <i class="ki-duotone ki-car-2 fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                            </div>
                            <div>
                                <h4 class="block-title">{{ __('bookings.car.title') }}</h4>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-light-primary" id="btn_add_car" disabled
                            data-bs-toggle="modal" data-bs-target="#modal_car">
                            <i class="ki-duotone ki-plus fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
                            {{ __('bookings.car.add') }}
                        </button>
                    </div>

                    <div class="row">
                        <div class="col-lg-8">
                            <label class="required fw-semibold fs-6 mb-2">{{ __('bookings.fields.car') }}</label>
                            <select id="car_id" name="car_id" class="form-select" data-control="select2"
                                data-placeholder="{{ __('bookings.placeholders.car') }}" disabled>
                                <option></option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>

                {{-- Address Block --}}
                <div class="section-block">
                    <div class="block-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="block-icon bg-light-success text-success">
                                <i class="ki-duotone ki-geolocation fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                            </div>
                            <div>
                                <h4 class="block-title">{{ __('bookings.address.title') }}</h4>
                                <p class="block-subtitle">{{ __('bookings.address.hint') }}</p>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-light-primary" id="btn_add_address" disabled
                            data-bs-toggle="modal" data-bs-target="#modal_address">
                            <i class="ki-duotone ki-plus fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
                            {{ __('bookings.address.add') }}
                        </button>
                    </div>

                    <div class="row">
                        <div class="col-lg-8">
                            <label class="required fw-semibold fs-6 mb-2">{{ __('bookings.fields.address') }}</label>
                            <select id="address_id" name="address_id" class="form-select" data-control="select2"
                                data-placeholder="{{ __('bookings.placeholders.address') }}" disabled>
                                <option></option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- =========================
                 STEP 2 – Booking Details + Slots
            ========================== --}}
            <div class="wizard-step-panel" data-panel="2">

                {{-- Service + Date + Employee --}}
                <div class="section-block">
                    <div class="block-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="block-icon bg-light-info text-info">
                                <i class="ki-duotone ki-calendar fs-2"><span class="path1"></span><span class="path2"></span></i>
                            </div>
                            <div>
                                <h4 class="block-title">{{ __('bookings.details.title') }}</h4>
                            </div>
                        </div>
                    </div>

                    <div class="row g-5">
                        <div class="col-md-5">
                            <label class="required fw-semibold fs-6 mb-2">{{ __('bookings.fields.service') }}</label>
                            <select id="service_id" name="service_id" class="form-select" data-control="select2"
                                data-placeholder="{{ __('bookings.placeholders.service') }}">
                                <option></option>
                                @foreach ($services as $srv)
                                    <option value="{{ $srv->id }}"
                                        data-duration="{{ (int) $srv->duration_minutes }}">
                                        {{ function_exists('i18n') ? i18n($srv->name) : $srv->name['ar'] ?? ($srv->name['en'] ?? '') }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-3">
                            <label class="required fw-semibold fs-6 mb-2">{{ __('bookings.fields.date') }}</label>
                            <input type="date" id="booking_date" name="booking_date" class="form-control"
                                value="{{ old('booking_date') }}">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">{{ __('bookings.fields.employee') }}</label>
                            <select id="employee_id" name="employee_id" class="form-select" disabled>
                                <option value="">{{ __('bookings.placeholders.employee_auto') }}</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">{{ __('bookings.fields.package_subscription') }}</label>
                            <select id="package_subscription_id" name="package_subscription_id" class="form-select"
                                disabled>
                                <option value="">{{ __('bookings.placeholders.package_optional') }}</option>
                            </select>
                            <div class="form-text">{{ __('bookings.package.hint') }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">{{ __('bookings.fields.note') }}</label>
                            <input type="text" name="note" class="form-control"
                                placeholder="{{ __('bookings.placeholders.note') }}">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>

                {{-- Time Slots --}}
                <div class="section-block">
                    <div class="block-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="block-icon bg-light-danger text-danger">
                                <i class="ki-duotone ki-time fs-2"><span class="path1"></span><span class="path2"></span></i>
                            </div>
                            <div>
                                <h4 class="block-title">{{ __('bookings.fields.time') }}</h4>
                                <p class="block-subtitle">{{ __('bookings.slots.hint') }}</p>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-light-primary" id="btn_load_slots" disabled>
                            <i class="ki-duotone ki-reload fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
                            {{ __('bookings.slots.load') }}
                        </button>
                    </div>

                    <input type="hidden" name="start_time" id="start_time">
                    <span class="text-muted fs-7 d-block mb-3" id="slots_meta"></span>

                    <div id="slots_wrap" class="slot-grid"></div>
                    <div class="text-danger mt-2" id="slots_error"></div>
                </div>

                {{-- Summary --}}
                <div class="summary-float">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="ki-duotone ki-chart-simple fs-3 text-success"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                        <h5 class="fw-bold mb-0 text-success">{{ __('bookings.summary.title') }}</h5>
                    </div>

                    <div class="sum-row">
                        <span class="text-muted">{{ __('bookings.summary.duration') }}</span>
                        <span class="fw-bold" id="sum_duration">—</span>
                    </div>
                    <div class="sum-row">
                        <span class="text-muted">{{ __('bookings.summary.start') }}</span>
                        <span class="fw-bold" id="sum_start">—</span>
                    </div>
                    <div class="sum-row">
                        <span class="text-muted">{{ __('bookings.summary.end') }}</span>
                        <span class="fw-bold" id="sum_end">—</span>
                    </div>

                    <div class="info-banner amber mt-3">
                        <i class="ki-duotone ki-information-5 fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                        <div>{{ __('bookings.summary.notice') }}</div>
                    </div>
                </div>

            </div>

            {{-- =========================
                 STEP 3 – Products
            ========================== --}}
            <div class="wizard-step-panel" data-panel="3">

                <div class="section-block">
                    <div class="block-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="block-icon bg-light-primary text-primary">
                                <i class="ki-duotone ki-basket fs-2"><span class="path1"></span><span class="path2"></span></i>
                            </div>
                            <div>
                                <h4 class="block-title">{{ __('bookings.products.title') }}</h4>
                                <p class="block-subtitle">{{ __('bookings.products.hint') }}</p>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-light-primary" id="btn_add_product_row">
                            <i class="ki-duotone ki-plus fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
                            {{ __('bookings.products.add_row') }}
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed">
                            <thead>
                                <tr class="text-muted fw-bold text-uppercase fs-7">
                                    <th style="width:55%">{{ __('bookings.products.product') }}</th>
                                    <th style="width:25%">{{ __('bookings.products.qty') }}</th>
                                    <th class="text-end" style="width:20%">{{ __('bookings.products.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody id="products_tbody"></tbody>
                        </table>
                    </div>

                    <div class="info-banner blue mt-3" id="no_products_hint">
                        <i class="ki-duotone ki-information-5 fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                        <div>{{ __('bookings.products.hint') }}</div>
                    </div>
                </div>

            </div>

            {{-- ===== Bottom Navigation ===== --}}
            <div class="wizard-nav">
                <button type="button" class="btn-wz btn-wz-prev" id="wz_prev" style="visibility:hidden">
                    <i class="ki-duotone ki-arrow-{{ App::getLocale() == 'ar' ? 'right' : 'left' }} fs-4"><span class="path1"></span><span class="path2"></span></i>
                    {{ __('bookings.back') }}
                </button>

                <div class="d-flex gap-2">
                    <button type="button" class="btn-wz btn-wz-next" id="wz_next">
                        {{ __('bookings.tabs.booking') }}
                        <i class="ki-duotone ki-arrow-{{ App::getLocale() == 'ar' ? 'left' : 'right' }} fs-4"><span class="path1"></span><span class="path2"></span></i>
                    </button>

                    <button type="submit" class="btn-wz btn-wz-submit" id="wz_submit" style="display:none">
                        <span class="indicator-label">{{ __('bookings.save') }}</span>
                    </button>
                </div>
            </div>

        </div>
    </div>
</form>


{{-- =========================
    MODAL: Add Customer
========================= --}}
<div class="modal fade" id="modal_customer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="fw-bold">{{ __('bookings.customer.modal_title') }}</h3>
                <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span class="path2"></span></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="customer_form">
                    <div class="alert d-none" id="customer_result"></div>

                    <div class="row g-6">
                        <div class="col-md-6 fv-row">
                            <label class="required fw-semibold fs-6 mb-2">{{ __('bookings.customer.name') }}</label>
                            <input type="text" name="name" class="form-control"
                                placeholder="{{ __('bookings.customer.name_ph') }}">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 fv-row">
                            <label class="required fw-semibold fs-6 mb-2">{{ __('bookings.customer.phone') }}</label>
                            <input type="text" name="mobile" class="form-control"
                                placeholder="{{ __('bookings.customer.phone_ph') }}">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-8">
                        <button type="button" class="btn btn-light me-3"
                            data-bs-dismiss="modal">{{ __('bookings.close') }}</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="indicator-label">{{ __('bookings.create.title') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


{{-- =========================
    MODAL: Add Car
========================= --}}
<div class="modal fade" id="modal_car" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-750px">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="fw-bold">{{ __('bookings.car.modal_title') }}</h3>
                <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span class="path2"></span></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="car_form">
                    <div class="alert d-none" id="car_result"></div>

                    <div class="row g-6">
                        <div class="col-md-6 fv-row">
                            <label class="required fw-semibold fs-6 mb-2">{{ __('bookings.car.make') }}</label>
                            <select id="vehicle_make_id" name="vehicle_make_id" class="form-select"
                                data-control="select2" data-placeholder="{{ __('bookings.car.make_ph') }}">
                                <option></option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6 fv-row">
                            <label class="required fw-semibold fs-6 mb-2">{{ __('bookings.car.model') }}</label>
                            <select id="vehicle_model_id" name="vehicle_model_id" class="form-select"
                                data-control="select2" data-placeholder="{{ __('bookings.car.model_ph') }}">
                                <option></option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-4 fv-row">
                            <label class="required fw-semibold fs-6 mb-2">{{ __('bookings.car.plate_number') }}</label>
                            <input type="text" name="plate_number" class="form-control" placeholder="1234">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-4 fv-row">
                            <label class="required fw-semibold fs-6 mb-2">{{ __('bookings.car.plate_letters') }}</label>
                            <input type="text" name="plate_letters" class="form-control" placeholder="ABC">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-4 fv-row">
                            <label class="fw-semibold fs-6 mb-2">{{ __('bookings.car.plate_letters_ar') }}</label>
                            <input type="text" name="plate_letters_ar" class="form-control" placeholder="أ ب ج">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6 fv-row">
                            <label class="fw-semibold fs-6 mb-2">{{ __('bookings.car.color') }}</label>
                            <select name="color" class="form-select">
                                <option value="">{{ __('bookings.optional') }}</option>
                                @foreach (['red', 'silver', 'white', 'black', 'brown', 'orange', 'purple', 'gold', 'green', 'blue', 'yellow', 'beige'] as $c)
                                    <option value="{{ $c }}">{{ __('bookings.colors.' . $c) }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6 fv-row">
                            <label class="fw-semibold fs-6 mb-2 d-block">{{ __('bookings.car.default') }}</label>
                            <div class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="is_default" value="1">
                                <label class="form-check-label">{{ __('bookings.yes') }}</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-8">
                        <button type="button" class="btn btn-light me-3"
                            data-bs-dismiss="modal">{{ __('bookings.close') }}</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="indicator-label">{{ __('bookings.create.title') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


{{-- =========================
    MODAL: Add Address
========================= --}}
<div class="modal fade" id="modal_address" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="fw-bold">{{ __('bookings.address.modal_title') }}</h3>
                <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span class="path2"></span></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="address_form">
                    <div class="alert d-none" id="address_result"></div>

                    <div class="row g-6">
                        <div class="col-md-3 fv-row">
                            <label class="required fw-semibold fs-6 mb-2">{{ __('bookings.address.type') }}</label>
                            <select name="type" class="form-select">
                                <option value="home">{{ __('bookings.address.types.home') }}</option>
                                <option value="work">{{ __('bookings.address.types.work') }}</option>
                                <option value="other">{{ __('bookings.address.types.other') }}</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-3 fv-row">
                            <label class="fw-semibold fs-6 mb-2">{{ __('bookings.address.city') }}</label>
                            <input name="city" class="form-control" placeholder="{{ __('bookings.optional') }}">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-3 fv-row">
                            <label class="fw-semibold fs-6 mb-2">{{ __('bookings.address.area') }}</label>
                            <input name="area" class="form-control" placeholder="{{ __('bookings.optional') }}">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-3 fv-row">
                            <label class="fw-semibold fs-6 mb-2">{{ __('bookings.address.country') }}</label>
                            <input name="country" class="form-control" placeholder="{{ __('bookings.optional') }}">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-8 fv-row">
                            <label class="fw-semibold fs-6 mb-2">{{ __('bookings.address.address_line') }}</label>
                            <input name="address_line" class="form-control"
                                placeholder="{{ __('bookings.address.address_line_ph') }}">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-4 fv-row">
                            <label class="fw-semibold fs-6 mb-2">{{ __('bookings.address.landmark') }}</label>
                            <input name="landmark" class="form-control" placeholder="{{ __('bookings.optional') }}">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-12">
                            <div id="addr_pick_map" class="mb-2"></div>
                            <div class="form-text">{{ __('bookings.address.map_hint') }}</div>
                        </div>

                        <div class="col-md-12 fv-row">
                            <label class="required fw-semibold fs-6 mb-2">{{ __('bookings.address.link') }}</label>
                            <input name="address_link"
                                class="form-control @error('address_link') is-invalid @enderror"
                                placeholder="{{ __('bookings.address.link_ph') }}">
                            <div class="invalid-feedback"></div>

                            <div class="form-text">
                                {{ __('bookings.address.link_hint') }}
                            </div>
                        </div>

                        <div class="col-md-4 fv-row">
                            <label class="fw-semibold fs-6 mb-2 d-block">{{ __('bookings.address.default') }}</label>
                            <div class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="is_default" value="1">
                                <label class="form-check-label">{{ __('bookings.yes') }}</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-8">
                        <button type="button" class="btn btn-light me-3"
                            data-bs-dismiss="modal">{{ __('bookings.close') }}</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="indicator-label">{{ __('bookings.create.title') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection


@push('custom-script')
<script>
    // ===========================
    // Wizard Navigation
    // ===========================
    let currentStep = 1;
    const totalSteps = 3;

    const stepLabels = {
        1: "{{ __('bookings.tabs.customer') }}",
        2: "{{ __('bookings.tabs.booking') }}",
        3: "{{ __('bookings.tabs.products') }}"
    };

    function goToStep(step) {
        if (step < 1 || step > totalSteps) return;

        currentStep = step;

        // panels
        document.querySelectorAll('.wizard-step-panel').forEach(p => p.classList.remove('active'));
        document.querySelector(`.wizard-step-panel[data-panel="${step}"]`).classList.add('active');

        // sidebar
        document.querySelectorAll('.step-item').forEach(s => {
            const sStep = parseInt(s.dataset.step);
            s.classList.remove('active');
            s.classList.remove('completed');
            if (sStep === step) s.classList.add('active');
            if (sStep < step) s.classList.add('completed');
        });

        // nav buttons
        const $prev = document.getElementById('wz_prev');
        const $next = document.getElementById('wz_next');
        const $submit = document.getElementById('wz_submit');

        $prev.style.visibility = step === 1 ? 'hidden' : 'visible';
        $next.style.display = step === totalSteps ? 'none' : 'inline-flex';
        $submit.style.display = step === totalSteps ? 'inline-flex' : 'none';

        // next label
        if (step < totalSteps) {
            $next.innerHTML = stepLabels[step + 1] +
                ' <i class="ki-duotone ki-arrow-{{ App::getLocale() == 'ar' ? 'left' : 'right' }} fs-4"><span class="path1"></span><span class="path2"></span></i>';
        }

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    document.getElementById('wz_next').addEventListener('click', () => goToStep(currentStep + 1));
    document.getElementById('wz_prev').addEventListener('click', () => goToStep(currentStep - 1));

    // ===========================
    // Original Logic (preserved)
    // ===========================
    (function() {
        const isAr = document.documentElement.lang === 'ar';

        const $form = $('#booking_form');

        // ----------------------------
        // Select2: user (ajax)
        // ----------------------------
        const $user = $('#user_id');

        if ($user.data('select2')) {
            $user.select2('destroy');
        }

        $user.select2({
            width: '100%',
            placeholder: $user.data('placeholder') || '',
            allowClear: true,
            minimumInputLength: 0,
            ajax: {
                url: "{{ route('dashboard.bookings.lookups.users') }}",
                dataType: 'json',
                delay: 250,
                cache: true,
                data: params => ({
                    q: params.term || ''
                }),
                processResults: data => ({
                    results: data.results || []
                }),
                error: xhr => console.log('users lookup error:', xhr.status, xhr.responseText),
            }
        });

        // service select2 (static)
        $('#service_id').select2({
            allowClear: true
        });

        $('#service_id').on('change', function() {
            if ($('#user_id').val()) {
                loadPackageSubscriptions();
            }
        });

        // car/address select2 (static but dynamic items)
        $('#car_id').select2({
            allowClear: true
        });
        $('#address_id').select2({
            allowClear: true
        });

        // Enable buttons when user selected
        function setUserDependentEnabled(enabled) {
            $('#car_id').prop('disabled', !enabled);
            $('#address_id').prop('disabled', !enabled);
            $('#package_subscription_id').prop('disabled', !enabled);

            $('#btn_add_car').prop('disabled', !enabled);
            $('#btn_add_address').prop('disabled', !enabled);
        }

        setUserDependentEnabled(false);

        $('#user_id').on('change', function() {

            console.log('User changed');

            const userId = $(this).val();

            // reset dependents
            $('#car_id').empty().trigger('change');
            $('#address_id').empty().trigger('change');
            $('#package_subscription_id').empty().append(
                `<option value="">{{ __('bookings.placeholders.package_optional') }}</option>`);

            $('#start_time').val('');
            $('#slots_wrap').empty();
            $('#employee_id').prop('disabled', true).empty().append(
                `<option value="">{{ __('bookings.placeholders.employee_auto') }}</option>`);
            $('#btn_load_slots').prop('disabled', true);

            if (!userId) {
                setUserDependentEnabled(false);
                return;
            }

            setUserDependentEnabled(true);

            // load cars
            $.get("{{ url('dashboard/bookings/lookups/users') }}/" + userId + "/cars")
                .done(res => {
                    const items = res.items || [];
                    $('#car_id').empty().append('<option></option>');
                    items.forEach(it => $('#car_id').append(new Option(it.text, it.id, false, false)));
                    $('#car_id').trigger('change');
                });

            // load addresses
            $.get("{{ url('dashboard/bookings/lookups/users') }}/" + userId + "/addresses")
                .done(res => {
                    const items = res.items || [];
                    $('#address_id').empty().append('<option></option>');
                    items.forEach(it => {
                        const opt = new Option(it.text, it.id, false, false);
                        $(opt).attr('data-lat', it.lat).attr('data-lng', it.lng);
                        $('#address_id').append(opt);
                    });
                    $('#address_id').trigger('change');
                });

            // load package subscriptions for this user
            loadPackageSubscriptions();

        });

        // package select2
        const $pkg = $('#package_subscription_id');
        if ($pkg.data('select2')) $pkg.select2('destroy');

        $pkg.select2({
            width: '100%',
            allowClear: true,
        });

        // helper
        function resetPackageSelect(disable = true) {
            $pkg.empty().append(`<option value="">{{ __('bookings.placeholders.package_optional') }}</option>`);
            $pkg.prop('disabled', disable).trigger('change');
        }

        // load subs (filtered by service if selected)
        function loadPackageSubscriptions() {
            const userId = $('#user_id').val();
            const serviceId = $('#service_id').val();

            if (!userId) {
                resetPackageSelect(true);
                return;
            }

            if (!serviceId) {
                resetPackageSelect(true);
                return;
            }

            const prev = $pkg.val();

            resetPackageSelect(true);

            $.get("{{ route('dashboard.bookings.lookups.user_package_subscriptions', ['user' => 'USER_ID']) }}"
                    .replace('USER_ID', userId), {
                        service_id: serviceId || ''
                    })
                .done(res => {
                    const items = res.items || [];

                    $pkg.empty().append(
                        `<option value="">{{ __('bookings.placeholders.package_optional') }}</option>`);

                    items.forEach(it => {
                        $pkg.append(new Option(it.text, it.id, false, false));
                    });

                    $pkg.prop('disabled', false);

                    if (prev && $pkg.find(`option[value="${prev}"]`).length) {
                        $pkg.val(prev).trigger('change');
                    } else {
                        $pkg.val('').trigger('change');
                    }

                    if (!items.length) {
                        $pkg.prop('disabled', true);
                    }
                })
                .fail(xhr => {
                    console.log('package subscriptions lookup error:', xhr.status, xhr.responseText);
                    resetPackageSelect(true);
                });
        }

        // ----------------------------
        // slots loading
        // ----------------------------
        let slotsCache = [];

        function canLoadSlots() {
            return !!$('#service_id').val() && !!$('#address_id').val() && !!$('#booking_date').val();
        }

        function toggleSlotsButton() {
            $('#btn_load_slots').prop('disabled', !canLoadSlots());
        }

        $('#service_id, #address_id, #booking_date').on('change', function() {
            toggleSlotsButton();

            $('#start_time').val('');
            $('#slots_wrap').empty();
            $('#slots_error').text('');
            $('#slots_meta').text('');
            $('#employee_id').prop('disabled', true).empty().append(
                `<option value="">{{ __('bookings.placeholders.employee_auto') }}</option>`);
            $('#sum_start').text('—');
            $('#sum_end').text('—');
            const dur = $('#service_id option:selected').data('duration');
            $('#sum_duration').text(dur ? (dur + ' {{ __('bookings.minutes') }}') : '—');
        });

        function renderSlots(items, meta) {
            const $wrap = $('#slots_wrap');
            $wrap.empty();

            slotsCache = items || [];

            if (!slotsCache.length) {
                const code = meta?.error_code || null;
                let msg = "{{ __('bookings.no_slots') }}";
                if (code === 'OUT_OF_COVERAGE') msg = "{{ __('bookings.out_of_coverage') }}";
                if (code === 'NO_WORKING_HOURS') msg = "{{ __('bookings.no_working_hours') }}";
                $('#slots_error').text(msg);
                return;
            }

            $('#slots_error').text('');

            slotsCache.forEach((s, idx) => {
                const id = 'slot_' + idx;
                const html = `
                <label class="slot-btn" for="${id}" data-idx="${idx}">
                    <input class="d-none" type="radio" name="slot_pick" id="${id}" value="${s.start_time}">
                    <div class="fw-bold">${s.start_time} - ${s.end_time}</div>
                    <div class="text-muted fs-8">${(s.employees?.length || 0)} {{ __('bookings.slots.employees') }}</div>
                </label>
            `;
                $wrap.append(html);
            });
        }

        $('#btn_load_slots').on('click', function() {
            if (!canLoadSlots()) return;

            $('#slots_error').text('');
            $('#slots_meta').text(isAr ? 'جاري تحميل المواعيد...' : 'Loading slots...');

            $.get("{{ route('dashboard.bookings.slots') }}", {
                    service_id: $('#service_id').val(),
                    address_id: $('#address_id').val(),
                    booking_date: $('#booking_date').val()
                })
                .done(res => {
                    $('#slots_meta').text('');
                    renderSlots(res.items || [], res.meta || {});
                })
                .fail(xhr => {
                    $('#slots_meta').text('');
                    $('#slots_error').text(xhr.responseJSON?.message || 'Error');
                });
        });

        // choose slot
        $(document).on('click', '.slot-btn', function() {
            $('.slot-btn').removeClass('active');
            $(this).addClass('active');

            const idx = parseInt($(this).data('idx'), 10);
            const s = slotsCache[idx];

            if (!s) return;

            $('#start_time').val(s.start_time);

            $('#sum_start').text(s.start_time);
            $('#sum_end').text(s.end_time);

            const employees = s.employees || [];
            const $emp = $('#employee_id');

            $emp.empty().append(
                `<option value="">{{ __('bookings.placeholders.employee_auto') }}</option>`);

            employees.forEach(e => {
                $emp.append(new Option(e.name, e.employee_id, false, false));
            });

            $emp.prop('disabled', employees.length <= 1);

            if (employees.length === 1) {
                $emp.val(employees[0].employee_id);
            } else {
                $emp.val('');
            }
        });

        // ----------------------------
        // products repeater
        // ----------------------------
        function productRowTemplate(index) {
            return `
            <tr data-index="${index}">
                <td>
                    <select class="form-select js-product" name="products[${index}][product_id]" data-placeholder="{{ __('bookings.products.product_ph') }}">
                        <option></option>
                    </select>
                    <div class="invalid-feedback"></div>
                </td>
                <td>
                    <input type="number" min="1" class="form-control" name="products[${index}][qty]" value="1">
                    <div class="invalid-feedback"></div>
                </td>
                <td class="text-end">
                    <button type="button" class="btn btn-sm js-remove-row">
                        <i class="fa-regular fa-trash-can text-danger me-2"></i>
                    </button>
                </td>
            </tr>
        `;
        }

        function initProductSelect2($el) {
            $el.select2({
                ajax: {
                    url: "{{ route('dashboard.bookings.lookups.products') }}",
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        q: params.term || ''
                    }),
                    processResults: data => data
                },
                allowClear: true,
                minimumInputLength: 1
            });
        }

        let productIndex = 0;

        $('#btn_add_product_row').on('click', function() {
            const html = productRowTemplate(productIndex);
            $('#products_tbody').append(html);
            initProductSelect2($('#products_tbody tr:last .js-product'));
            productIndex++;
        });

        $(document).on('click', '.js-remove-row', function() {
            $(this).closest('tr').remove();
        });

        // ----------------------------
        // AJAX Submit main form
        // ----------------------------
        $form.on('submit', function(e) {
            e.preventDefault();

            if (window.KH && typeof window.KH.setFormLoading === 'function') {
                window.KH.setFormLoading($form, true, {
                    text: isAr ? 'جاري الحفظ...' : 'Saving...'
                });
            }

            const formData = new FormData($form[0]);

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    Swal.fire({
                        icon: 'success',
                        title: "{{ __('bookings.done') }}",
                        text: res.message ||
                            "{{ __('bookings.created_successfully') }}",
                        timer: 1800,
                        showConfirmButton: false
                    });

                    if (res.redirect) window.location.href = res.redirect;
                },
                error: function(xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        if (window.KH && typeof window.KH.showValidationErrors === 'function') {
                            window.KH.showValidationErrors($form, xhr.responseJSON.errors, {
                                globalAlertSelector: '#form_result'
                            });
                        } else {
                            $('#form_result').removeClass('d-none alert-success').addClass(
                                'alert-danger').text(xhr.responseJSON.message ||
                                'Validation error');
                        }

                        if (xhr.responseJSON.errors.start_time) {
                            $('#slots_error').text(xhr.responseJSON.errors.start_time[0]);
                        }
                    } else {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Unexpected error',
                            'error');
                    }
                },
                complete: function() {
                    if (window.KH && typeof window.KH.setFormLoading === 'function') {
                        window.KH.setFormLoading($form, false);
                    }
                }
            });
        });

        // ----------------------------
        // MODAL: customer
        // ----------------------------
        $('#customer_form').on('submit', function(e) {
            e.preventDefault();

            const $f = $(this);
            const data = $f.serialize();

            $f.find('.is-invalid').removeClass('is-invalid');
            $f.find('.invalid-feedback').text('');
            $('#customer_result').addClass('d-none').removeClass('alert-danger alert-success').text('');

            $.post("{{ route('dashboard.bookings.users.store') }}", data)
                .done(res => {
                    $('#modal_customer').modal('hide');

                    const option = new Option(res.text, res.id, true, true);
                    $('#user_id').append(option).trigger('change');

                    if (window.toastr) toastr.success(res.message || 'Created');
                })
                .fail(xhr => {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        Object.keys(xhr.responseJSON.errors).forEach(k => {
                            const $input = $f.find(`[name="${k}"]`);
                            $input.addClass('is-invalid');
                            $input.closest('.fv-row').find('.invalid-feedback').first().text(xhr
                                .responseJSON.errors[k][0]);
                        });
                    } else {
                        $('#customer_result').removeClass('d-none').addClass('alert alert-danger').text(
                            xhr.responseJSON?.message || 'Error');
                    }
                });
        });

        // ----------------------------
        // MODAL: car (select2 in modal)
        // ----------------------------
        const carModal = document.getElementById('modal_car');

        $('#vehicle_make_id').select2({
            dropdownParent: $('#modal_car'),
            ajax: {
                url: "{{ route('dashboard.lookups.vehicle_makes') }}",
                dataType: 'json',
                delay: 250,
                data: params => ({
                    q: params.term || ''
                }),
                processResults: data => data
            },
            allowClear: true,
            minimumInputLength: 0
        });

        $('#vehicle_model_id').select2({
            dropdownParent: $('#modal_car'),
            ajax: {
                url: "{{ route('dashboard.lookups.vehicle_models') }}",
                dataType: 'json',
                delay: 250,
                data: params => ({
                    q: params.term || '',
                    make_id: $('#vehicle_make_id').val() || ''
                }),
                processResults: data => data
            },
            allowClear: true,
            minimumInputLength: 0
        });

        $('#vehicle_make_id').on('change', function() {
            $('#vehicle_model_id').val(null).trigger('change');
        });

        $('#car_form').on('submit', function(e) {
            e.preventDefault();

            const userId = $('#user_id').val();
            if (!userId) {
                Swal.fire('Error', "{{ __('bookings.select_user_first') }}", 'error');
                return;
            }

            const $f = $(this);
            const data = $f.serialize();

            $f.find('.is-invalid').removeClass('is-invalid');
            $f.find('.invalid-feedback').text('');
            $('#car_result').addClass('d-none').removeClass('alert-danger alert-success').text('');

            $.post("{{ url('dashboard/bookings/users') }}/" + userId + "/cars", data)
                .done(res => {
                    $('#modal_car').modal('hide');

                    const opt = new Option(res.text, res.id, true, true);
                    $('#car_id').append(opt).trigger('change');

                    if (window.toastr) toastr.success(res.message || 'Created');
                })
                .fail(xhr => {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        Object.keys(xhr.responseJSON.errors).forEach(k => {
                            const $input = $f.find(`[name="${k}"]`);
                            $input.addClass('is-invalid');
                            $input.closest('.fv-row').find('.invalid-feedback').first().text(xhr
                                .responseJSON.errors[k][0]);
                        });
                    } else {
                        $('#car_result').removeClass('d-none').addClass('alert alert-danger').text(xhr
                            .responseJSON?.message || 'Error');
                    }
                });
        });

        $('#address_form').on('submit', function(e) {
            e.preventDefault();

            const userId = $('#user_id').val();
            if (!userId) {
                Swal.fire('Error', "{{ __('bookings.select_user_first') }}", 'error');
                return;
            }

            const $f = $(this);
            const data = $f.serialize();

            $f.find('.is-invalid').removeClass('is-invalid');
            $f.find('.invalid-feedback').text('');
            $('#address_result').addClass('d-none').removeClass('alert-danger alert-success').text('');

            $.post("{{ url('dashboard/bookings/users') }}/" + userId + "/addresses", data)
                .done(res => {
                    $('#modal_address').modal('hide');

                    const opt = new Option(res.text, res.id, true, true);
                    $(opt).attr('data-lat', res.lat).attr('data-lng', res.lng);
                    $('#address_id').append(opt).trigger('change');

                    if (window.toastr) toastr.success(res.message || 'Created');
                })
                .fail(xhr => {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        Object.keys(xhr.responseJSON.errors).forEach(k => {
                            const $input = $f.find(`[name="${k}"]`);
                            $input.addClass('is-invalid');
                            $input.closest('.fv-row').find('.invalid-feedback').first().text(xhr
                                .responseJSON.errors[k][0]);
                        });
                    } else {
                        $('#address_result').removeClass('d-none').addClass('alert alert-danger').text(
                            xhr.responseJSON?.message || 'Error');
                    }
                });
        });

    })();
</script>
@endpush