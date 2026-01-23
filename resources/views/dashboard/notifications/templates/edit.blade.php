@extends('base.layout.app')

@section('title', 'تعديل قالب إشعار')

@push('custom-style')
<style>
    .icon-selector {
        cursor: pointer;
        display: block;
        transition: all 0.3s ease;
    }
    
    .icon-box {
        border: 2px solid #e4e6ef;
        border-radius: 8px;
        padding: 15px;
        text-align: center;
        transition: all 0.3s ease;
        background: #fff;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    
    .icon-box:hover {
        border-color: #009ef7;
        background: #f1faff;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 158, 247, 0.15);
    }
    
    .icon-selector.selected .icon-box {
        border-color: #009ef7;
        background: #f1faff;
        box-shadow: 0 0 0 3px rgba(0, 158, 247, 0.1);
    }
    
    .icon-preview {
        width: 60px;
        height: 60px;
        object-fit: contain;
        margin-bottom: 10px;
    }
    
    .no-icon-placeholder {
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 10px;
    }
    
    .icon-name {
        font-size: 12px;
        color: #7e8299;
        font-weight: 500;
        word-break: break-word;
        margin-top: auto;
    }
    
    .icon-selector.selected .icon-name {
        color: #009ef7;
        font-weight: 600;
    }
</style>
@endpush

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
