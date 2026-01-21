<!--begin::Logo-->
<div class="app-sidebar-logo px-6" id="kt_app_sidebar_logo">
    <!--begin::Logo image-->
    <a href="{{ route('dashboard.index') }}" class="w-100 text-center">
        <img alt="Logo" src="/assets/media/logos/logo.png" class="h-40px app-sidebar-logo-default" />
        <img alt="Logo" src="/assets/media/logos/unnamed.avif" class="h-30px app-sidebar-logo-minimize" />
    </a>
    <!--end::Logo image-->
    <!--begin::Sidebar toggle-->
    <div id="kt_app_sidebar_toggle"
        class="app-sidebar-toggle btn btn-icon btn-shadow btn-sm btn-color-muted btn-active-color-primary h-30px w-30px position-absolute top-50 start-100 translate-middle rotate "
        data-kt-toggle="true" data-kt-toggle-state="active" data-kt-toggle-target="body"
        data-kt-toggle-name="app-sidebar-minimize">
        <i class="ki-duotone ki-black-left-line fs-3 rotate-180"><span class="path1"></span><span
                class="path2"></span></i>
    </div>
    <!--end::Sidebar toggle-->
</div>
<!--end::Logo-->
