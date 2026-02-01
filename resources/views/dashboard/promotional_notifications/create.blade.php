@extends('base.layout.app')

@section('title', __('promotional_notifications.create'))

@section('content')

@section('top-btns')
    <a href="{{ route('dashboard.promotional-notifications.index') }}" class="btn btn-light">
        <i class="ki-duotone ki-arrow-left fs-2"></i>
        {{ __('promotional_notifications.back') }}
    </a>
@endsection

<form id="notification_form" action="{{ route('dashboard.promotional-notifications.store') }}" method="POST">
    @csrf

    <div class="row g-6">
        {{-- LEFT: Main Content --}}
        <div class="col-lg-7">

            {{-- Content Card --}}
            <div class="card card-flush mb-6">
                <div class="card-header pt-6">
                    <h3 class="card-title fw-bold">{{ __('promotional_notifications.content') }}</h3>
                </div>
                <div class="card-body pt-0">

                    {{-- Title Arabic --}}
                    <div class="fv-row mb-6">
                        <label
                            class="required fw-semibold mb-2">{{ __('promotional_notifications.fields.title_ar') }}</label>
                        <input type="text" name="title[ar]" class="form-control"
                            placeholder="{{ __('promotional_notifications.placeholders.title_ar') }}"
                            value="{{ old('title.ar') }}">
                        <div class="invalid-feedback"></div>
                        <div class="form-text">{{ __('promotional_notifications.hints.title') }}</div>
                    </div>

                    {{-- Title English --}}
                    <div class="fv-row mb-6">
                        <label
                            class="required fw-semibold mb-2">{{ __('promotional_notifications.fields.title_en') }}</label>
                        <input type="text" name="title[en]" class="form-control"
                            placeholder="{{ __('promotional_notifications.placeholders.title_en') }}"
                            value="{{ old('title.en') }}">
                        <div class="invalid-feedback"></div>
                    </div>

                    {{-- Body Arabic --}}
                    <div class="fv-row mb-6">
                        <label
                            class="required fw-semibold mb-2">{{ __('promotional_notifications.fields.body_ar') }}</label>
                        <textarea name="body[ar]" class="form-control" rows="3"
                            placeholder="{{ __('promotional_notifications.placeholders.body_ar') }}">{{ old('body.ar') }}</textarea>
                        <div class="invalid-feedback"></div>
                        <div class="form-text">{{ __('promotional_notifications.hints.body') }}</div>
                    </div>

                    {{-- Body English --}}
                    <div class="fv-row">
                        <label
                            class="required fw-semibold mb-2">{{ __('promotional_notifications.fields.body_en') }}</label>
                        <textarea name="body[en]" class="form-control" rows="3"
                            placeholder="{{ __('promotional_notifications.placeholders.body_en') }}">{{ old('body.en') }}</textarea>
                        <div class="invalid-feedback"></div>
                    </div>

                </div>
            </div>

            {{-- Link Target Card --}}
            <div class="card card-flush">
                <div class="card-header pt-6">
                    <h3 class="card-title fw-bold">{{ __('promotional_notifications.fields.linkable') }}</h3>
                </div>
                <div class="card-body pt-0">

                    <div class="fv-row">
                        <label class="fw-semibold mb-2">{{ __('promotional_notifications.fields.linkable') }}</label>
                        <select name="linkable_combined" id="linkable_combined" class="form-select"
                            data-control="select2">
                            <option value="">{{ __('promotional_notifications.placeholders.select_linkable') }}
                            </option>

                            @if ($services->count())
                                <optgroup label="{{ __('promotional_notifications.link_types.service') }}">
                                    @foreach ($services as $service)
                                        <option value="App\Models\Service:{{ $service->id }}">
                                            {{ $service->name[app()->getLocale()] ?? ($service->name['ar'] ?? $service->name['en']) }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif

                            @if ($packages->count())
                                <optgroup label="{{ __('promotional_notifications.link_types.package') }}">
                                    @foreach ($packages as $package)
                                        <option value="App\Models\Package:{{ $package->id }}">
                                            {{ $package->name[app()->getLocale()] ?? ($package->name['ar'] ?? $package->name['en']) }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif

                            @if ($products->count())
                                <optgroup label="{{ __('promotional_notifications.link_types.product') }}">
                                    @foreach ($products as $product)
                                        <option value="App\Models\Product:{{ $product->id }}">
                                            {{ $product->name[app()->getLocale()] ?? ($product->name['ar'] ?? $product->name['en']) }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif
                        </select>
                        <div class="invalid-feedback"></div>
                        <div class="form-text">{{ __('promotional_notifications.hints.linkable') }}</div>
                    </div>

                </div>
            </div>

        </div>

        {{-- RIGHT: Settings --}}
        <div class="col-lg-5">

            {{-- Target Audience Card --}}
            <div class="card card-flush mb-6">
                <div class="card-header pt-6">
                    <h3 class="card-title fw-bold">{{ __('promotional_notifications.fields.target_type') }}</h3>
                </div>
                <div class="card-body pt-0">

                    {{-- Target Type --}}
                    <div class="fv-row mb-6">
                        <label
                            class="required fw-semibold mb-2">{{ __('promotional_notifications.fields.target_type') }}</label>
                        <select name="target_type" id="target_type" class="form-select">
                            <option value="all_users" {{ old('target_type') === 'all_users' ? 'selected' : '' }}>
                                {{ __('promotional_notifications.target_types.all_users') }}
                            </option>
                            <option value="specific_users"
                                {{ old('target_type') === 'specific_users' ? 'selected' : '' }}>
                                {{ __('promotional_notifications.target_types.specific_users') }}
                            </option>
                        </select>
                        <div class="invalid-feedback"></div>
                        <div class="form-text">{{ __('promotional_notifications.hints.target_type') }}</div>
                    </div>

                    {{-- Specific Users --}}
                    <div class="fv-row" id="specific_users_field" style="display: none;">
                        <label
                            class="required fw-semibold mb-2">{{ __('promotional_notifications.fields.target_users') }}</label>
                        <select name="target_user_ids[]" id="target_user_ids" class="form-select" multiple
                            data-control="select2">
                        </select>
                        <div class="invalid-feedback"></div>
                        <div class="form-text">{{ __('promotional_notifications.hints.target_users') }}</div>
                    </div>

                    {{-- Recipients Preview --}}
                    <div class="notice bg-light-info rounded border-info border border-dashed p-4 mt-6">
                        <div class="d-flex align-items-center">
                            <i class="ki-duotone ki-information-5 fs-2tx text-info me-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                            </i>
                            <div class="flex-grow-1">
                                <div class="fw-semibold text-gray-800">
                                    {{ __('promotional_notifications.stats.recipients_preview') }}</div>
                                <div class="fw-bold fs-2 text-info mt-1" id="recipients_count">0</div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Schedule Card --}}
            <div class="card card-flush mb-6">
                <div class="card-header pt-6">
                    <h3 class="card-title fw-bold">{{ __('promotional_notifications.schedule') }}</h3>
                </div>
                <div class="card-body pt-0">

                    {{-- Send Type --}}
                    <div class="fv-row mb-6">
                        <label
                            class="required fw-semibold mb-2">{{ __('promotional_notifications.send_time') }}</label>
                        <select name="send_type" id="send_type" class="form-select">
                            <option value="now" selected>{{ __('promotional_notifications.send_types.now') }}
                            </option>
                            <option value="scheduled">{{ __('promotional_notifications.send_types.scheduled') }}
                            </option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>

                    {{-- Scheduled At --}}
                    <div class="fv-row" id="scheduled_at_field" style="display: none;">
                        <label
                            class="required fw-semibold mb-2">{{ __('promotional_notifications.fields.scheduled_at') }}</label>
                        <input type="datetime-local" name="scheduled_at" class="form-control"
                            value="{{ old('scheduled_at') }}">
                        <div class="invalid-feedback"></div>
                        <div class="form-text">{{ __('promotional_notifications.hints.scheduled_at') }}</div>
                    </div>

                </div>
            </div>

            {{-- Internal Notes Card --}}
            <div class="card card-flush">
                <div class="card-header pt-6">
                    <h3 class="card-title fw-bold">{{ __('promotional_notifications.fields.internal_notes') }}</h3>
                </div>
                <div class="card-body pt-0">
                    <textarea name="internal_notes" class="form-control" rows="3"
                        placeholder="{{ __('promotional_notifications.placeholders.internal_notes') }}">{{ old('internal_notes') }}</textarea>
                    <div class="form-text">{{ __('promotional_notifications.hints.internal_notes') }}</div>
                </div>
            </div>

        </div>
    </div>

    {{-- Submit Button --}}
    <div class="d-flex justify-content-end mt-6">
        <button type="submit" class="btn btn-primary" id="submit_btn">
            <span class="indicator-label">
                <i class="ki-duotone ki-send fs-2 me-1">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
                {{ __('promotional_notifications.create') }}
            </span>
            <span class="indicator-progress">
                {{ __('promotional_notifications.processing') }}
                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
            </span>
        </button>
    </div>

</form>

@endsection

@push('custom-script')
<script>
    (function() {
        const isAr = document.documentElement.lang === 'ar';
        const $form = $('#notification_form');
        const $submitBtn = $('#submit_btn');

        // Initialize Select2 for linkable
        $('#linkable_combined').select2();

        // Show/hide fields based on target type
        $('#target_type').on('change', function() {
            const targetType = $(this).val();
            if (targetType === 'specific_users') {
                $('#specific_users_field').show();
                // Re-initialize Select2 after showing
                initUserSelect2();
            } else {
                $('#specific_users_field').hide();
                $('#target_user_ids').val(null).trigger('change');
            }
            updateRecipientsCount();
        });

        // Initialize users Select2
        function initUserSelect2() {
            const $targetUsers = $('#target_user_ids');

            // Destroy if exists
            if ($targetUsers.data('select2')) {
                $targetUsers.select2('destroy');
            }

            $targetUsers.select2({
                width: '100%',
                placeholder: "{{ __('promotional_notifications.placeholders.search_users') }}",
                allowClear: true,
                ajax: {
                    url: "{{ route('dashboard.bookings.lookups.users') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        console.log('Searching users:', params.term); // للتأكد
                        return {
                            q: params.term || ''
                        };
                    },
                    processResults: function(data) {
                        console.log('Users API Response:', data);
                        return {
                            results: data.results || []
                        };
                    },
                    cache: true
                },
                minimumInputLength: 0
            });
        }

        // Initialize on page load if needed
        if ($('#target_type').val() === 'specific_users') {
            $('#specific_users_field').show();
            initUserSelect2();
        }

        // Show/hide scheduled_at based on send_type
        $('#send_type').on('change', function() {
            const sendType = $(this).val();
            if (sendType === 'scheduled') {
                $('#scheduled_at_field').show();
            } else {
                $('#scheduled_at_field').hide();
            }
        });

        // Update recipients count
        function updateRecipientsCount() {
            const targetType = $('#target_type').val();
            const userIds = $('#target_user_ids').val();

            $.post("{{ route('dashboard.promotional-notifications.preview-recipients') }}", {
                    _token: '{{ csrf_token() }}',
                    target_type: targetType,
                    target_user_ids: userIds || []
                })
                .done(function(response) {
                    $('#recipients_count').text(response.count || 0);
                })
                .fail(function() {
                    $('#recipients_count').text('0');
                });
        }

        $('#target_type, #target_user_ids').on('change', updateRecipientsCount);

        // Initial call
        updateRecipientsCount();

        // Form submit
        $form.on('submit', function(e) {
            e.preventDefault();

            $submitBtn.attr('disabled', true);
            $submitBtn.find('.indicator-label').hide();
            $submitBtn.find('.indicator-progress').show();

            // Clear previous errors
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').text('');

            const formData = new FormData($form[0]);

            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: isAr ? 'نجح!' : 'Success!',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            if (response.redirect) {
                                window.location.href = response.redirect;
                            }
                        });
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        const errors = xhr.responseJSON.errors;
                        for (const field in errors) {
                            const $field = $form.find(`[name="${field}"], [name="${field}[]"]`);
                            $field.addClass('is-invalid');
                            $field.siblings('.invalid-feedback').text(errors[field][0]);
                        }
                    } else {
                        Swal.fire('Error', xhr.responseJSON?.message || 'An error occurred',
                            'error');
                    }
                },
                complete: function() {
                    $submitBtn.attr('disabled', false);
                    $submitBtn.find('.indicator-label').show();
                    $submitBtn.find('.indicator-progress').hide();
                }
            });
        });
    })();
</script>
@endpush
