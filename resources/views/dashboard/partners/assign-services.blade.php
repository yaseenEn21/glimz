@extends('base.layout.app')

@section('title', __('partners.assign_services'))

@section('content')

<form action="{{ route('dashboard.partners.store-assignments', $partner) }}" method="POST" id="assignForm">
    @csrf

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('partners.assign_services') }} - {{ $partner->name }}</h3>
        </div>

        <div class="card-body">
            <div class="alert alert-light-info d-flex align-items-center mb-6">
                <i class="ki-duotone ki-information-5 fs-2x text-info me-3">
                    <span class="path1"></span>
                    <span class="path2"></span>
                    <span class="path3"></span>
                </i>
                <div class="flex-grow-1">
                    <div class="fw-semibold">{{ __('partners.at_least_one_employee') }}</div>
                </div>
            </div>

            <div id="servicesContainer">
                @php
                    $index = 0;
                @endphp
                
                @foreach($currentAssignments as $serviceId => $assignments)
                    @php
                        $service = $assignments->first()->service;
                        $employeeIds = $assignments->pluck('employee_id')->toArray();
                    @endphp
                    
                    <div class="service-row border border-gray-300 border-dashed rounded p-4 mb-4" data-index="{{ $index }}">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h5 class="mb-0">{{ __('partners.service') }} #{{ $index + 1 }}</h5>
                            <button type="button" class="btn btn-sm btn-light-danger remove-service">
                                <i class="ki-duotone ki-trash fs-5"></i>
                                {{ __('partners.remove_service') }}
                            </button>
                        </div>

                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="required form-label">{{ __('partners.select_service') }}</label>
                                <select name="assignments[{{ $index }}][service_id]" 
                                        class="form-select" required>
                                    <option value="">{{ __('partners.select_service') }}</option>
                                    @foreach($services as $svc)
                                        <option value="{{ $svc->id }}" 
                                                {{ $svc->id == $serviceId ? 'selected' : '' }}>
                                            {{ is_array($svc->name) 
                                                ? ($svc->name[app()->getLocale()] ?? $svc->name['ar']) 
                                                : $svc->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="required form-label">{{ __('partners.select_employees') }}</label>
                                <select name="assignments[{{ $index }}][employee_ids][]" 
                                        class="form-select" 
                                        multiple required
                                        data-control="select2"
                                        data-placeholder="{{ __('partners.select_employees') }}">
                                    @foreach($employees as $emp)
                                        <option value="{{ $emp->id }}"
                                                {{ in_array($emp->id, $employeeIds) ? 'selected' : '' }}>
                                            {{ $emp->user->name ?? 'N/A' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    @php $index++; @endphp
                @endforeach
            </div>

            <button type="button" class="btn btn-light-primary w-100" id="addService">
                <i class="ki-duotone ki-plus fs-2"></i>
                {{ __('partners.add_service') }}
            </button>
        </div>

        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('dashboard.partners.show', $partner) }}" class="btn btn-light">
                {{ __('partners.cancel') }}
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="ki-duotone ki-check fs-2"></i>
                {{ __('partners.save') }}
            </button>
        </div>
    </div>
</form>

@endsection

@push('custom-script')
<script>
    let serviceIndex = {{ $index }};

    // Service Row Template
    function getServiceRowTemplate(index) {
        return `
            <div class="service-row border border-gray-300 border-dashed rounded p-4 mb-4" data-index="${index}">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h5 class="mb-0">{{ __('partners.service') }} #${index + 1}</h5>
                    <button type="button" class="btn btn-sm btn-light-danger remove-service">
                        <i class="ki-duotone ki-trash fs-5"></i>
                        {{ __('partners.remove_service') }}
                    </button>
                </div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="required form-label">{{ __('partners.select_service') }}</label>
                        <select name="assignments[${index}][service_id]" class="form-select" required>
                            <option value="">{{ __('partners.select_service') }}</option>
                            @foreach($services as $svc)
                                <option value="{{ $svc->id }}">
                                    {{ is_array($svc->name) 
                                        ? ($svc->name[app()->getLocale()] ?? $svc->name['ar']) 
                                        : $svc->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="required form-label">{{ __('partners.select_employees') }}</label>
                        <select name="assignments[${index}][employee_ids][]" 
                                class="form-select select2-${index}" 
                                multiple required
                                data-control="select2"
                                data-placeholder="{{ __('partners.select_employees') }}">
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->user->name ?? 'N/A' }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        `;
    }

    // Add Service Row
    document.getElementById('addService').addEventListener('click', function() {
        const container = document.getElementById('servicesContainer');
        const newRow = getServiceRowTemplate(serviceIndex);
        container.insertAdjacentHTML('beforeend', newRow);
        
        // Initialize Select2 for new row
        $(`.select2-${serviceIndex}`).select2();
        
        serviceIndex++;
    });

    // Remove Service Row
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-service')) {
            const row = e.target.closest('.service-row');
            if (document.querySelectorAll('.service-row').length > 1) {
                row.remove();
            } else {
                alert('{{ __('partners.at_least_one_employee') }}');
            }
        }
    });

    // Initialize existing Select2
    @foreach($currentAssignments as $serviceId => $assignments)
        $('[name="assignments[{{ $loop->index }}][employee_ids][]"]').select2();
    @endforeach

    // Form Validation
    document.getElementById('assignForm').addEventListener('submit', function(e) {
        const rows = document.querySelectorAll('.service-row');
        let isValid = true;

        rows.forEach(row => {
            const serviceSelect = row.querySelector('[name*="[service_id]"]');
            const employeeSelect = row.querySelector('[name*="[employee_ids]"]');

            if (!serviceSelect.value) {
                isValid = false;
                serviceSelect.classList.add('is-invalid');
            } else {
                serviceSelect.classList.remove('is-invalid');
            }

            if (!employeeSelect.value || employeeSelect.selectedOptions.length === 0) {
                isValid = false;
                employeeSelect.classList.add('is-invalid');
            } else {
                employeeSelect.classList.remove('is-invalid');
            }
        });

        if (!isValid) {
            e.preventDefault();
            alert('{{ __('partners.at_least_one_employee') }}');
        }
    });
</script>
@endpush