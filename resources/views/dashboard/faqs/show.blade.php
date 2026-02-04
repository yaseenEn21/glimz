@extends('base.layout.app')

@section('title', __('faqs.show'))

@section('content')

<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ __('faqs.details') }}</h3>
        <div class="card-toolbar">
            @can('faqs.edit')
                <a href="{{ route('dashboard.faqs.edit', $faq) }}" class="btn btn-sm btn-primary">
                    <i class="fa-solid fa-pen"></i>
                    {{ __('faqs.edit') }}
                </a>
            @endcan
        </div>
    </div>

    <div class="card-body">
        <div class="row mb-5">
            <div class="col-md-6">
                <div class="fw-semibold text-muted mb-1">{{ __('faqs.question_ar') }}</div>
                <div class="fw-bold fs-5">{{ $faq->question['ar'] ?? '—' }}</div>
            </div>
            <div class="col-md-6">
                <div class="fw-semibold text-muted mb-1">{{ __('faqs.question_en') }}</div>
                <div class="fw-bold fs-5">{{ $faq->question['en'] ?? '—' }}</div>
            </div>
        </div>

        <div class="separator my-5"></div>

        <div class="row mb-5">
            <div class="col-md-6">
                <div class="fw-semibold text-muted mb-1">{{ __('faqs.answer_ar') }}</div>
                <div>{{ $faq->answer['ar'] ?? '—' }}</div>
            </div>
            <div class="col-md-6">
                <div class="fw-semibold text-muted mb-1">{{ __('faqs.answer_en') }}</div>
                <div>{{ $faq->answer['en'] ?? '—' }}</div>
            </div>
        </div>

        <div class="separator my-5"></div>

        <div class="row">
            <div class="col-md-4">
                <div class="fw-semibold text-muted mb-1">{{ __('faqs.sort_order') }}</div>
                <div class="fw-bold">{{ $faq->sort_order }}</div>
            </div>
            <div class="col-md-4">
                <div class="fw-semibold text-muted mb-1">{{ __('faqs.status') }}</div>
                <div>
                    @if($faq->is_active)
                        <span class="badge badge-light-success">{{ __('faqs.active') }}</span>
                    @else
                        <span class="badge badge-light-danger">{{ __('faqs.inactive') }}</span>
                    @endif
                </div>
            </div>
            <div class="col-md-4">
                <div class="fw-semibold text-muted mb-1">{{ __('faqs.created_at') }}</div>
                <div class="fw-bold">{{ $faq->created_at->format('Y-m-d H:i') }}</div>
            </div>
        </div>
    </div>
</div>

@endsection