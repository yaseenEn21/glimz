@extends('base.layout.app')

@section('title', __('app-pages.edit_title', ['name' => ($page['name'] ?? '')]))

@push('custom-style')
    <style>
        .ck-editor__editable_inline {
            min-height: 400px;
            direction: rtl;
            text-align: right;
        }
    </style>
@endpush

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-5 gap-3">
        <div>
            <h2 class="fw-bold mb-1">
                {{ __('app-pages.edit_heading', ['name' => $page['name']]) }}
            </h2>
            <div class="text-muted">
                {{ $page['description'] }}
            </div>
        </div>

        <a href="{{ route('dashboard.app-pages.index') }}" class="btn btn-light">
            {{ __('app-pages.actions.back_to_list') }}
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success mb-5">
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <div class="card-body">

            <form id="app_page_form" action="{{ route('dashboard.app-pages.update', $page['key']) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label class="form-label fw-semibold">{{ __('app-pages.fields.page_title') }}</label>
                    <input type="text" class="form-control" value="{{ $page['name'] }}" disabled>
                </div>

                <div class="mb-4">
                    <label for="page_value" class="form-label fw-semibold">
                        {{ __('app-pages.fields.page_content') }}
                    </label>

                    <textarea name="value" id="page_value" rows="18"
                        class="form-control @error('value') is-invalid @enderror">{{ old('value', $setting->value) }}</textarea>

                    @error('value')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror

                    <div class="form-text">
                        {{ __('app-pages.content_help') }}
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <span class="indicator-label">{{ __('app-pages.actions.save_changes') }}</span>
                        {{-- spinner filled by KH.setFormLoading --}}
                    </button>
                </div>
            </form>

        </div>
    </div>
@endsection

@push('custom-script')
    <script src="{{ asset('assets/plugins/custom/ckeditor/ckeditor-classic.bundle.js') }}"></script>

    <script>
        const appLocale = @json(app()->getLocale());
        const isRtl = (appLocale === 'ar');

        // Optional: flip editor area direction based on locale
        const editorStyle = document.createElement('style');
        editorStyle.innerHTML = `
            .ck-editor__editable_inline {
                direction: ${isRtl ? 'rtl' : 'ltr'};
                text-align: ${isRtl ? 'right' : 'left'};
            }
        `;
        document.head.appendChild(editorStyle);

        ClassicEditor
            .create(document.querySelector('#page_value'), {
                language: {
                    ui: appLocale,
                    content: appLocale,
                },

                toolbar: [
                    'heading', '|',
                    'bold', 'italic', 'underline', 'fontColor', 'fontBackgroundColor', '|',
                    'bulletedList', 'numberedList', '|',
                    'link', '|',
                    'undo', 'redo'
                ],

                fontColor: {
                    columns: 6,
                    colors: [{
                            color: '#000000',
                            label: @json(__('app-pages.editor.colors.black'))
                        },
                        {
                            color: '#ffffff',
                            label: @json(__('app-pages.editor.colors.white'))
                        },
                        {
                            color: '#e03131',
                            label: @json(__('app-pages.editor.colors.red'))
                        },
                        {
                            color: '#2f9e44',
                            label: @json(__('app-pages.editor.colors.green'))
                        },
                        {
                            color: '#1971c2',
                            label: @json(__('app-pages.editor.colors.blue'))
                        },
                        {
                            color: '#f08c00',
                            label: @json(__('app-pages.editor.colors.orange'))
                        },
                        {
                            color: '#e67700',
                            label: @json(__('app-pages.editor.colors.gold'))
                        },
                        {
                            color: '#862e9c',
                            label: @json(__('app-pages.editor.colors.purple'))
                        },
                        {
                            color: '#495057',
                            label: @json(__('app-pages.editor.colors.dark_gray'))
                        },
                        {
                            color: '#adb5bd',
                            label: @json(__('app-pages.editor.colors.light_gray'))
                        },
                    ]
                },

                fontBackgroundColor: {
                    columns: 6,
                    colors: [{
                            color: '#fff5f5',
                            label: @json(__('app-pages.editor.bg.light_red'))
                        },
                        {
                            color: '#ebfbee',
                            label: @json(__('app-pages.editor.bg.light_green'))
                        },
                        {
                            color: '#e7f5ff',
                            label: @json(__('app-pages.editor.bg.light_blue'))
                        },
                        {
                            color: '#fff4e6',
                            label: @json(__('app-pages.editor.bg.light_orange'))
                        },
                        {
                            color: '#fff9db',
                            label: @json(__('app-pages.editor.bg.light_yellow'))
                        },
                        {
                            color: '#f8f9fa',
                            label: @json(__('app-pages.editor.bg.light_gray'))
                        },
                    ]
                }
            })
            .then(editor => {
                window.appPageEditor = editor;
            })
            .catch(error => {
                console.error(error);
            });

        $(function() {
            $('#app_page_form').on('submit', function() {
                window.KH.setFormLoading($(this), true, {
                    text: @json(__('app-pages.loading.save'))
                });
            });
        });
    </script>
@endpush