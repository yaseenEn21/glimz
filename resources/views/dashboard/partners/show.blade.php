@extends('base.layout.app')

@section('title', __('partners.show'))

@section('content')

<div class="row g-6">
    {{-- Left Column - Partner Details --}}
    <div class="col-lg-8">
        {{-- Basic Info --}}
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title">{{ __('partners.partner') }} {{ __('partners.details') }}</h3>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-6">
                    @if ($partner->is_active)
                        <span class="badge badge-light-success fs-5">{{ __('partners.active') }}</span>
                    @else
                        <span class="badge badge-light-danger fs-5">{{ __('partners.inactive') }}</span>
                    @endif
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="fw-semibold text-muted mb-1">{{ __('partners.fields.name') }}</div>
                        <div class="fw-bold fs-5">{{ $partner->name }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="fw-semibold text-muted mb-1">{{ __('partners.fields.username') }}</div>
                        <div class="fw-bold fs-5">
                            <span class="badge badge-light-info fs-6">{{ $partner->username }}</span>
                        </div>
                    </div>
                </div>

                <div class="separator my-4"></div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="fw-semibold text-muted mb-1">
                            <i class="ki-duotone ki-sms fs-4 me-1">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            {{ __('partners.fields.email') }}
                        </div>
                        <div class="fw-bold">{{ $partner->email }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="fw-semibold text-muted mb-1">
                            <i class="ki-duotone ki-phone fs-4 me-1">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            {{ __('partners.fields.mobile') }}
                        </div>
                        <div class="fw-bold">{{ $partner->mobile ?? '—' }}</div>
                    </div>
                </div>

                <div class="separator my-4"></div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="fw-semibold text-muted mb-1">
                            <i class="ki-duotone ki-calendar fs-4 me-1">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            {{ __('partners.fields.daily_booking_limit') }}
                        </div>
                        <div class="fw-bold">
                            <span class="badge badge-light-primary fs-4">
                                {{ number_format($partner->daily_booking_limit) }}
                            </span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="fw-semibold text-muted mb-1">
                            <i class="ki-duotone ki-globe fs-4 me-1">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            {{ __('partners.fields.webhook_url') }}
                        </div>
                        <div class="fw-bold">
                            @if ($partner->webhook_url)
                                <a href="{{ $partner->webhook_url }}" target="_blank" class="text-primary">
                                    {{ Str::limit($partner->webhook_url, 40) }}
                                </a>
                            @else
                                —
                            @endif
                        </div>
                    </div>
                </div>

                <div class="separator my-4"></div>

                <div class="fw-semibold text-muted mb-1">{{ __('partners.fields.created_at') }}</div>
                <div class="fw-bold">{{ $partner->created_at->format('Y-m-d H:i') }}</div>
            </div>
        </div>

        {{-- Assigned Services --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('partners.assigned_services') }}</h3>
                <div class="card-toolbar">
                    @can('partners.assign_services')
                        <a href="{{ route('dashboard.partners.assign-services', $partner) }}"
                            class="btn btn-sm btn-light-primary">
                            <i class="ki-duotone ki-pencil fs-4"></i>
                            {{ __('partners.edit_services') }}
                        </a>
                    @endcan
                </div>
            </div>
            <div class="card-body pt-0">
                @forelse($servicesWithEmployees as $item)
                    <div class="border border-gray-300 border-dashed rounded p-4 mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="symbol symbol-50px me-3">
                                <div class="symbol-label bg-light-success">
                                    <i class="ki-duotone ki-wrench fs-2x text-success">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold fs-5">
                                    {{ is_array($item['service']->name)
                                        ? $item['service']->name[app()->getLocale()] ?? $item['service']->name['ar']
                                        : $item['service']->name }}
                                </div>
                                <div class="text-muted fs-7">
                                    {{ $item['employees']->count() }} {{ __('partners.employees') }}
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($item['employees'] as $employee)
                                <span class="badge badge-light-primary">
                                    <i class="ki-duotone ki-user fs-5 me-1">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    {{ $employee->user->name ?? 'N/A' }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="text-center py-10">
                        <div class="text-gray-600 fs-5 mb-3">{{ __('partners.no_services_assigned') }}</div>
                        @can('partners.assign_services')
                            <a href="{{ route('dashboard.partners.assign-services', $partner) }}" class="btn btn-primary">
                                <i class="ki-duotone ki-plus fs-2"></i>
                                {{ __('partners.actions.assign_services') }}
                            </a>
                        @endcan
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Right Column - API Token --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('partners.fields.api_token') }}</h3>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <label class="form-label fw-bold">Token</label>
                    <div class="input-group">
                        <input type="password" id="apiToken" class="form-control form-control-solid"
                            value="{{ $partner->api_token }}" readonly>
                        <button type="button" class="btn btn-light-primary d-none" id="toggleToken"
                            title="{{ __('partners.actions.show_token') }}">
                            <i class="ki-duotone ki-eye fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                            </i>
                        </button>
                        <button type="button" class="btn btn-primary ms-2" id="copyToken"
                            title="{{ __('partners.actions.copy_token') }}">
                            <i class="ki-duotone ki-copy fs-2 pe-0">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                                <span class="path5"></span>
                            </i>
                        </button>
                    </div>
                    <div class="form-text mt-2" id="copyMessage"></div>
                </div>

                @can('partners.regenerate_token')
                    <button type="button" class="btn btn-danger w-100" id="regenerateToken">
                        <i class="ki-duotone ki-arrows-circle fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        {{ __('partners.actions.regenerate_token') }}
                    </button>
                @endcan
            </div>
        </div>
    </div>
</div>

@endsection

@push('custom-script')
<script>
    // Toggle Token Visibility
    const tokenInput = document.getElementById('apiToken');
    const toggleBtn = document.getElementById('toggleToken');

    toggleBtn.addEventListener('click', function() {
        if (tokenInput.type === 'password') {
            tokenInput.type = 'text';
            toggleBtn.innerHTML =
                '<i class="ki-duotone ki-eye-slash fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>';
        } else {
            tokenInput.type = 'password';
            toggleBtn.innerHTML =
                '<i class="ki-duotone ki-eye fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>';
        }
    });

    // Copy Token
    document.getElementById('copyToken').addEventListener('click', function() {
        // استخدام Clipboard API الحديث
        navigator.clipboard.writeText(tokenInput.value).then(function() {
            const message = document.getElementById('copyMessage');
            message.textContent = '{{ __('partners.token_copied') }}';
            message.classList.add('text-success', 'fw-bold');

            // تغيير لون الزر مؤقتاً
            const btn = document.getElementById('copyToken');
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-success');

            setTimeout(() => {
                message.textContent = '';
                message.classList.remove('text-success', 'fw-bold');
                btn.classList.remove('btn-success');
                btn.classList.add('btn-primary');
            }, 2000);
        }).catch(function(err) {
            console.error('Failed to copy:', err);
            // Fallback للطريقة القديمة
            tokenInput.select();
            document.execCommand('copy');

            const message = document.getElementById('copyMessage');
            message.textContent = '{{ __('partners.token_copied') }}';
            message.classList.add('text-success', 'fw-bold');

            setTimeout(() => {
                message.textContent = '';
                message.classList.remove('text-success', 'fw-bold');
            }, 2000);
        });
    });

    // Regenerate Token
    @can('partners.regenerate_token')
        const regenerateBtn = document.getElementById('regenerateToken');
        if (regenerateBtn) {
            regenerateBtn.addEventListener('click', function() {
                if (!confirm('{{ __('partners.regenerate_token_confirm') }}')) {
                    return;
                }

                const btn = this;
                const originalHTML = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-2"></span>{{ __('partners.loading') }}...';

                fetch('{{ route('dashboard.partners.regenerate-token', $partner) }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            tokenInput.value = data.token;
                            tokenInput.type = 'password'; // إخفاء Token الجديد

                            // رسالة نجاح
                            const message = document.getElementById('copyMessage');
                            message.textContent = data.message;
                            message.classList.add('text-success', 'fw-bold');

                            setTimeout(() => {
                                message.textContent = '';
                                message.classList.remove('text-success', 'fw-bold');
                            }, 3000);
                        } else {
                            alert('{{ __('partners.error') }}: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('{{ __('partners.error') }}: ' + error.message);
                    })
                    .finally(() => {
                        btn.disabled = false;
                        btn.innerHTML = originalHTML;
                    });
            });
        }
    @endcan
</script>
@endpush
