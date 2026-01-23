@extends('base.layout.app')

@section('title', 'قوالب الإشعارات')

@section('content')

    <div class="d-flex flex-wrap align-items-center justify-content-between mb-5 gap-3">
        <div>
            <h2 class="fw-bold mb-1">
                قوالب الإشعارات
            </h2>
            <div class="text-muted">
                تحكم بنصوص الإشعارات التي تُرسل للتطبيق (عربي / إنجليزي).
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <div class="card-body">

            @if($templates->isEmpty())
                <div class="text-muted">
                    لا توجد قوالب إشعارات حتى الآن.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle">
                        <thead>
                        <tr class="fw-semibold text-gray-700">
                            <th style="width: 70px;">#</th>
                            <th>المفتاح (Key)</th>
                            <th>العنوان (عربي)</th>
                            <th>الوصف</th>
                            <th class="text-center">الحالة</th>
                            <th class="text-center" style="width: 120px;">إجراءات</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($templates as $template)
                            <tr>
                                <td>{{ $template->id }}</td>
                                <td>
                                    <code>{{ $template->key }}</code>
                                </td>
                                <td>{{ $template->title }}</td>
                                <td>{{ $template->description ?: '-' }}</td>
                                <td class="text-center">
                                    @if($template->is_active)
                                        <span class="badge badge-light-success">مفعّل</span>
                                    @else
                                        <span class="badge badge-light-danger">موقّف</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('dashboard.notification-templates.edit', $template) }}"
                                       class="btn btn-sm btn-light-primary">
                                        تعديل
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

        </div>
    </div>

@endsection
