@extends('base.layout.app')

@section('title', __('faqs.edit'))

@section('content')

<form action="{{ route('dashboard.faqs.update', $faq) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('faqs.edit') }}</h3>
        </div>

        <div class="card-body">
            {{-- Question AR --}}
            <div class="mb-5">
                <label class="form-label required">{{ __('faqs.question_ar') }}</label>
                <input type="text" name="question_ar" class="form-control @error('question_ar') is-invalid @enderror"
                    value="{{ old('question_ar', $faq->question['ar'] ?? '') }}" required>
                @error('question_ar')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Question EN --}}
            <div class="mb-5">
                <label class="form-label required">{{ __('faqs.question_en') }}</label>
                <input type="text" name="question_en" class="form-control @error('question_en') is-invalid @enderror"
                    value="{{ old('question_en', $faq->question['en'] ?? '') }}" required>
                @error('question_en')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Answer AR --}}
            <div class="mb-5">
                <label class="form-label">{{ __('faqs.answer_ar') }}</label>
                <textarea name="answer_ar" rows="4" class="form-control @error('answer_ar') is-invalid @enderror">{{ old('answer_ar', $faq->answer['ar'] ?? '') }}</textarea>
                @error('answer_ar')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Answer EN --}}
            <div class="mb-5">
                <label class="form-label">{{ __('faqs.answer_en') }}</label>
                <textarea name="answer_en" rows="4" class="form-control @error('answer_en') is-invalid @enderror">{{ old('answer_en', $faq->answer['en'] ?? '') }}</textarea>
                @error('answer_en')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Sort Order --}}
            <div class="mb-5">
                <label class="form-label">{{ __('faqs.sort_order') }}</label>
                <input type="number" name="sort_order" class="form-control @error('sort_order') is-invalid @enderror"
                    value="{{ old('sort_order', $faq->sort_order) }}" min="0">
                @error('sort_order')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Is Active --}}
            <div class="mb-5">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
                        {{ old('is_active', $faq->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">
                        {{ __('faqs.is_active') }}
                    </label>
                </div>
            </div>
        </div>

        <div class="card-footer d-flex justify-content-end">
            <a href="{{ route('dashboard.faqs.index') }}" class="btn btn-light me-3">
                {{ __('faqs.cancel') }}
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-save"></i>
                {{ __('faqs.update') }}
            </button>
        </div>
    </div>
</form>

@endsection