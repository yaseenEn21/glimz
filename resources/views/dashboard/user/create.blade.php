@extends('base.layout.app')

@section('title', 'إضافة مستخدم جديد')

@section('content')

<div class="d-flex flex-wrap align-items-center justify-content-between mb-5 gap-3">
    <h2 class="fw-bold mb-0">إضافة مستخدم جديد</h2>
    <a href="{{ route('dashboard.users.index') }}" class="btn btn-light">
        رجوع لقائمة المستخدمين
    </a>
</div>

@if($errors->any())
    <div class="alert alert-danger mb-4">
        حدثت بعض الأخطاء، يرجى مراجعة الحقول أدناه.
    </div>
@endif

<div class="card">
    <div class="card-body">
        <form id="user-form" action="{{ route('dashboard.users.store') }}" method="POST">
            @csrf

            <div class="row g-4">

                <div class="col-md-6">
                    <label class="form-label fw-bold">الاسم</label>
                    <input type="text"
                           name="name"
                           value="{{ old('name') }}"
                           class="form-control form-control-solid @error('name') is-invalid @enderror">
                    @error('name')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">البريد الإلكتروني</label>
                    <input type="email"
                           name="email"
                           value="{{ old('email') }}"
                           class="form-control form-control-solid @error('email') is-invalid @enderror">
                    @error('email')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">رقم الجوال</label>
                    <input type="text"
                           name="mobile"
                           value="{{ old('mobile') }}"
                           placeholder="05XXXXXXXX"
                           class="form-control form-control-solid @error('mobile') is-invalid @enderror">
                    @error('mobile')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">كلمة المرور</label>
                    <input type="password"
                           name="password"
                           class="form-control form-control-solid @error('password') is-invalid @enderror">
                    @error('password')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">الدور</label>
                    <select name="role_id"
                            class="form-select form-select-solid @error('role_id') is-invalid @enderror">
                        <option value="">اختر الدور</option>
                        @foreach($roles as $id => $name)
                            <option value="{{ $id }}" @selected(old('role_id') == $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('role_id')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold d-block mb-2">حالة الحساب</label>
                    <div class="form-check form-switch form-check-custom form-check-solid">
                        <input class="form-check-input" type="checkbox" value="1"
                               id="is_active_switch" name="is_active"
                               {{ old('is_active', 1) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active_switch">
                            مفعل
                        </label>
                    </div>
                </div>

            </div>

            <div class="d-flex justify-content-end gap-2 mt-6">
                <button id="user-submit" type="submit" class="btn btn-primary">
                    <span class="indicator-label">حفظ</span>
                    <span class="indicator-progress d-none">
                        جاري الحفظ...
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                    </span>
                </button>
                <a href="{{ route('dashboard.users.index') }}" class="btn btn-light">إلغاء</a>
            </div>

        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('user-form');
    const btn  = document.getElementById('user-submit');

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
