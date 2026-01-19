@extends('base.layout.app')

@section('title', 'تعديل قالب إشعار')

@section('content')

    <div class="d-flex flex-wrap align-items-center justify-content-between mb-5 gap-3">
        <div>
            <h2 class="fw-bold mb-1">
                تعديل قالب إشعار: {{ $template->key }}
            </h2>
            <div class="text-muted">
                تحكم بنص الإشعار الذي يُستخدم لإرسال تنبيهات للتطبيق.
            </div>
        </div>

        <a href="{{ route('dashboard.notification-templates.index') }}" class="btn btn-light">
            رجوع لقائمة القوالب
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form action="{{ route('dashboard.notification-templates.update', $template) }}" method="POST">
                @csrf
                @method('PUT')

                @include('dashboard.notifications.templates._form', ['template' => $template])

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        حفظ التعديلات
                    </button>
                </div>
            </form>
        </div>
    </div>

@endsection
