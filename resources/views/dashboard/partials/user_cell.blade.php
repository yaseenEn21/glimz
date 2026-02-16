@php
    /** @var \App\Models\User|null $user */
    $name = $user?->name ?? '—';
    $mobile = $user?->mobile ?? null;

    $mobileClean = $mobile ? preg_replace('/\s+/', '', $mobile) : null;
    $waNumber = $mobileClean ? preg_replace('/[^0-9]/', '', $mobileClean) : null;

    // استبدال الصفر الأول بـ 966 لرابط الواتساب
    if ($waNumber && str_starts_with($waNumber, '0')) {
        $waNumber = '966' . substr($waNumber, 1);
    }
@endphp

<div class="d-flex flex-column">
    <span class="fw-bold text-gray-900">{{ $name }}</span>

    @if($mobile)
        <div class="d-flex align-items-center gap-2 mt-1">
            <span class="text-muted">{{ $mobile }}</span>

            @if($waNumber)
                <a href="https://wa.me/{{ $waNumber }}" target="_blank" class="text-muted" title="WhatsApp">
                    <i class="bi bi-whatsapp"></i>
                </a>
            @endif
        </div>
    @else
        <span class="text-muted mt-1">—</span>
    @endif
</div>