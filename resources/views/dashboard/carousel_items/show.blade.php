@extends('base.layout.app')

@section('title', __('carousel.show'))

@section('content')

@section('top-btns')
    @can('carousel_items.edit')
        <a href="{{ route('dashboard.carousel-items.edit', $carouselItem->id) }}" class="btn btn-primary">
            {{ __('carousel.edit') }}
        </a>
    @endcan
@endsection

<div class="row g-6">
    <div class="col-lg-8">
        <div class="card card-flush mb-6">
            <div class="card-header pt-6">
                <h3 class="card-title fw-bold">{{ __('carousel.details') }}</h3>
            </div>
            <div class="card-body pt-0">

                @php
                    $displayType = $carouselItem->display_type ?? 'slider';
                    $displayBadges = [
                        'slider' => 'badge-light-info',
                        'popup' => 'badge-light-warning',
                        'both' => 'badge-light-primary',
                    ];
                @endphp

                <div class="d-flex align-items-center gap-3 mb-6">
                    <span class="badge {{ $displayBadges[$displayType] }}">
                        {{ __('carousel.display_types.' . $displayType) }}
                    </span>
                    {!! $carouselItem->is_active
                        ? '<span class="badge badge-light-success">' . __('carousel.active') . '</span>'
                        : '<span class="badge badge-light-danger">' . __('carousel.inactive') . '</span>' !!}
                    <span class="text-muted">{{ __('carousel.sort_order') }}: <span
                            class="fw-bold">{{ $carouselItem->sort_order }}</span></span>
                </div>

                <div class="separator my-6"></div>

                <ul class="nav nav-tabs nav-line-tabs mb-4">
                    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab"
                            href="#show_ar">{{ __('carousel.lang.ar') }}</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab"
                            href="#show_en">{{ __('carousel.lang.en') }}</a></li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="show_ar">
                        <div class="mb-3"><span class="text-muted">{{ __('carousel.fields.label') }}:</span> <span
                                class="fw-bold">{{ $carouselItem->label['ar'] ?? '—' }}</span></div>
                        <div class="mb-3"><span class="text-muted">{{ __('carousel.fields.title') }}:</span> <span
                                class="fw-bold">{{ $carouselItem->title['ar'] ?? '—' }}</span></div>
                        <div class="mb-3"><span class="text-muted">{{ __('carousel.fields.description') }}:</span>
                            <div class="fw-semibold">{{ $carouselItem->description['ar'] ?? '—' }}</div>
                        </div>
                        <div><span class="text-muted">{{ __('carousel.fields.hint') }}:</span> <span
                                class="fw-semibold">{{ $carouselItem->hint['ar'] ?? '—' }}</span></div>
                        <div><span class="text-muted">{{ __('carousel.fields.cta') }}:</span> <span
                                class="fw-semibold">{{ $carouselItem->cta['ar'] ?? '—' }}</span></div>
                    </div>

                    <div class="tab-pane fade" id="show_en">
                        <div class="mb-3"><span class="text-muted">{{ __('carousel.fields.label') }}:</span> <span
                                class="fw-bold">{{ $carouselItem->label['en'] ?? '—' }}</span></div>
                        <div class="mb-3"><span class="text-muted">{{ __('carousel.fields.title') }}:</span> <span
                                class="fw-bold">{{ $carouselItem->title['en'] ?? '—' }}</span></div>
                        <div class="mb-3"><span class="text-muted">{{ __('carousel.fields.description') }}:</span>
                            <div class="fw-semibold">{{ $carouselItem->description['en'] ?? '—' }}</div>
                        </div>
                        <div><span class="text-muted">{{ __('carousel.fields.hint') }}:</span> <span
                                class="fw-semibold">{{ $carouselItem->hint['en'] ?? '—' }}</span></div>
                        <div><span class="text-muted">{{ __('carousel.fields.cta') }}:</span> <span
                                class="fw-semibold">{{ $carouselItem->cta['en'] ?? '—' }}</span></div>
                    </div>
                </div>

                {{-- حقول التاريخ --}}
                @if ($carouselItem->starts_at || $carouselItem->ends_at)
                    <div class="separator my-6"></div>
                    <div class="row g-4 mb-6">
                        @if ($carouselItem->starts_at)
                            <div class="col-md-6">
                                <div class="d-flex flex-column">
                                    <span class="text-muted fw-semibold mb-2">
                                        <i class="ki-duotone ki-calendar fs-3 me-1">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        {{ __('carousel.fields.starts_at') }}
                                    </span>
                                    <span class="fw-bold fs-5">
                                        {{ $carouselItem->starts_at->format('d/m/Y H:i') }}
                                    </span>
                                    <span class="text-muted fs-7">
                                        {{ $carouselItem->starts_at->diffForHumans() }}
                                    </span>
                                </div>
                            </div>
                        @endif

                        @if ($carouselItem->ends_at)
                            <div class="col-md-6">
                                <div class="d-flex flex-column">
                                    <span class="text-muted fw-semibold mb-2">
                                        <i class="ki-duotone ki-calendar-remove fs-3 me-1">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                        </i>
                                        {{ __('carousel.fields.ends_at') }}
                                    </span>
                                    <span class="fw-bold fs-5">
                                        {{ $carouselItem->ends_at->format('d/m/Y H:i') }}
                                    </span>
                                    <span class="text-muted fs-7">
                                        {{ $carouselItem->ends_at->diffForHumans() }}
                                    </span>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- مدة العرض --}}
                    @if ($carouselItem->starts_at && $carouselItem->ends_at)
                        <div class="alert alert-light-info d-flex align-items-center mb-6">
                            <i class="ki-duotone ki-information-5 fs-2x text-info me-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                            </i>
                            <div class="flex-grow-1">
                                <span class="fw-semibold">{{ __('carousel.display_duration') }}:</span>
                                <span class="ms-2">
                                    {{ $carouselItem->starts_at->diffForHumans($carouselItem->ends_at, true) }}
                                </span>
                            </div>
                        </div>
                    @endif
                @endif

                <div class="separator my-6"></div>

                <div class="fw-semibold text-muted mb-1">{{ __('carousel.fields.link_target') }}</div>
                <div class="fw-bold">
                    @if ($carouselItem->carouselable_type && $carouselItem->carouselable_id)
                        {{ class_basename($carouselItem->carouselable_type) }} #{{ $carouselItem->carouselable_id }}
                    @else
                        —
                    @endif
                </div>

            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card card-flush mb-6">
            <div class="card-header pt-6">
                <h3 class="card-title fw-bold">{{ __('carousel.images') }}</h3>
            </div>
            <div class="card-body pt-0">
                <div class="mb-5">
                    <div class="text-muted fw-semibold mb-2">{{ __('carousel.image_ar') }}</div>
                    @php($arUrl = $carouselItem->getFirstMediaUrl('image_ar'))
                    @if ($arUrl)
                        <img src="{{ $arUrl }}" class="w-100 rounded" alt="image_ar">
                    @else
                        <div class="text-muted">—</div>
                    @endif
                </div>

                <div>
                    <div class="text-muted fw-semibold mb-2">{{ __('carousel.image_en') }}</div>
                    @php($enUrl = $carouselItem->getFirstMediaUrl('image_en'))
                    @if ($enUrl)
                        <img src="{{ $enUrl }}" class="w-100 rounded" alt="image_en">
                    @else
                        <div class="text-muted">—</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
