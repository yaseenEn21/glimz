@extends('base.layout.app')

@section('title', $title ?? 'بيانات التواصل')

@section('content')

    <div class="d-flex flex-wrap align-items-center justify-content-between mb-5 gap-3">
        <h2 class="fw-bold mb-0">إعدادات بيانات التواصل</h2>

        <a href="{{ route('dashboard.index') }}" class="btn btn-light">
            الرجوع للوحة التحكم
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger mb-4">
            حدثت بعض الأخطاء، يرجى مراجعة الحقول أدناه.
        </div>
    @endif

    <div class="card">
        <div class="card-body">

            <form id="contact-settings-form"
                  action="{{ route('dashboard.settings.contact.update') }}"
                  method="POST">
                @csrf
                @method('PUT')

                <div class="row g-4">

                    {{-- رقم الاتصال --}}
                    <div class="col-md-6">
                        <label class="form-label fw-bold d-flex align-items-center">
                            <img src="{{ asset('assets/media/svg/social-logos/phone.svg') }}"
                                 alt="Phone" class="me-2" style="height: 24px;">
                            رقم الاتصال
                        </label>
                        <input type="text"
                               name="contact_phone"
                               value="{{ old('contact_phone', $settings['contact_phone'] ?? '') }}"
                               class="form-control slug @error('contact_phone') is-invalid @enderror"
                               placeholder="مثال: 0590000000">
                        @error('contact_phone')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="form-text">يُستخدم كرقم الهاتف الرئيسي في تطبيق أولياء الأمور.</div>
                    </div>

                    {{-- واتساب --}}
                    <div class="col-md-6">
                        <label class="form-label fw-bold d-flex align-items-center">
                            <img src="{{ asset('assets/media/svg/social-logos/whatsapp.svg') }}"
                                 alt="WhatsApp" class="me-2" style="height: 24px;">
                            رقم / رابط واتساب
                        </label>
                        <input type="text"
                               name="contact_whatsapp"
                               value="{{ old('contact_whatsapp', $settings['contact_whatsapp'] ?? '') }}"
                               class="form-control slug @error('contact_whatsapp') is-invalid @enderror"
                               placeholder="مثال: wa.me/0590000000 أو 0590000000">
                        @error('contact_whatsapp')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="form-text">يمكن إدخال رقم أو رابط مباشر لـ WhatsApp.</div>
                    </div>

                    {{-- البريد الإلكتروني --}}
                    <div class="col-md-6">
                        <label class="form-label fw-bold d-flex align-items-center">
                            <img src="{{ asset('assets/media/svg/social-logos/email.svg') }}"
                                 alt="Email" class="me-2" style="height: 24px;">
                            البريد الإلكتروني
                        </label>
                        <input type="email"
                               name="contact_email"
                               value="{{ old('contact_email', $settings['contact_email'] ?? '') }}"
                               class="form-control slug @error('contact_email') is-invalid @enderror"
                               placeholder="مثال: info@example.com">
                        @error('contact_email')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="form-text">سيظهر هذا الإيميل في شاشة بيانات التواصل داخل التطبيق.</div>
                    </div>

                </div>

                <div class="d-flex justify-content-end gap-2 mt-6">
                    <button id="contact-settings-submit" type="submit" class="btn btn-primary">
                        <span class="indicator-label">حفظ التعديلات</span>
                        <span class="indicator-progress d-none">
                            جاري الحفظ...
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </button>
                </div>

            </form>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('contact-settings-form');
        const btn  = document.getElementById('contact-settings-submit');

        if (form && btn) {
            form.addEventListener('submit', function () {
                btn.disabled = true;
                btn.querySelector('.indicator-label').classList.add('d-none');
                btn.querySelector('.indicator-progress').classList.remove('d-none');
            });
        }
    });
</script>
@endpush