@php
    $user = auth()->user();
    $locale = app()->getLocale();

    $langLabel = $locale === 'ar' ? 'العربية' : 'English';
    $langFlag  = $locale === 'ar'
        ? asset('assets/media/flags/saudi-arabia.svg')
        : asset('assets/media/flags/united-states.svg');
@endphp

<!--begin::User account menu-->
<div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px"
     data-kt-menu="true">

    <!--begin::User info-->
    <div class="menu-item px-3">
        <div class="menu-content d-flex align-items-center px-3">
            <div class="symbol symbol-50px me-5">
                <img alt="Avatar"
                     src="{{ $user->getFirstMediaUrl('profile_image') ?: asset('assets/media/avatars/blank.png') }}" />
            </div>

            <div class="d-flex flex-column">
                <div class="fw-bold d-flex align-items-center fs-5">
                    {{ $user->name }}
                </div>
                @if(!empty($user->email))
                    <a href="mailto:{{ $user->email }}" class="fw-semibold text-muted text-hover-primary fs-7">
                        {{ $user->email }}
                    </a>
                @endif
            </div>
        </div>
    </div>
    <!--end::User info-->

    <div class="separator my-2"></div>

    <!--begin::Language submenu-->
    <div class="menu-item px-5"
         data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
         data-kt-menu-placement="left-start"
         data-kt-menu-offset="-15px, 0">

        <a href="#" class="menu-link px-5">
            <span class="menu-icon">
                <i class="ki-duotone ki-language fs-2">
                    <span class="path1"></span><span class="path2"></span>
                </i>
            </span>

            <span class="menu-title position-relative">
                {{ t('language') ?? __('Language') }}

                <span class="fs-8 rounded bg-light px-3 py-2 position-absolute translate-middle-y top-50 end-0 d-flex align-items-center gap-2">
                    <span class="fw-semibold">{{ $langLabel }}</span>
                    <img class="w-15px h-15px rounded-1" src="{{ $langFlag }}" alt="flag" />
                </span>
            </span>
        </a>

        <div class="menu-sub menu-sub-dropdown w-200px py-4">
            <div class="menu-item px-3">
                <a href="{{ route('dashboard.lang.switch','ar') }}"
                   class="menu-link d-flex px-5 {{ $locale === 'ar' ? 'active' : '' }}">
                    <span class="symbol symbol-20px me-4">
                        <img class="rounded-1" src="{{ asset('assets/media/flags/saudi-arabia.svg') }}" alt="AR" />
                    </span>
                    العربية
                </a>
            </div>

            <div class="menu-item px-3">
                <a href="{{ route('dashboard.lang.switch','en') }}"
                   class="menu-link d-flex px-5 {{ $locale === 'en' ? 'active' : '' }}">
                    <span class="symbol symbol-20px me-4">
                        <img class="rounded-1" src="{{ asset('assets/media/flags/united-states.svg') }}" alt="EN" />
                    </span>
                    English
                </a>
            </div>
        </div>
    </div>
    <!--end::Language submenu-->

    <!--begin::Profile-->
    <div class="menu-item px-5">
        <a href="{{ route('dashboard.profile.edit') }}" class="menu-link px-5">
            <span class="menu-icon">
                <i class="ki-duotone ki-user-edit fs-2"><span class="path1"></span><span class="path2"></span></i>
            </span>
            <span class="menu-title">{{ t('my_profile') }}</span>
        </a>
    </div>
    <!--end::Profile-->

    <div class="separator my-2"></div>

    <!--begin::Logout-->
    <div class="menu-item px-5">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <a class="menu-link px-5"
               onclick="event.preventDefault(); this.closest('form').submit();">
                <span class="menu-icon">
                    <i class="ki-duotone ki-exit-right fs-2"><span class="path1"></span><span class="path2"></span></i>
                </span>
                <span class="menu-title">{{ t('logout') }}</span>
            </a>
        </form>
    </div>
    <!--end::Logout-->

</div>
<!--end::User account menu-->
