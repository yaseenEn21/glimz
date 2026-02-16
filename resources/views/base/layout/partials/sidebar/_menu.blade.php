<!--begin::sidebar menu-->
<div class="app-sidebar-menu overflow-hidden flex-column-fluid">
    <!--begin::Menu wrapper-->
    <div id="kt_app_sidebar_menu_wrapper" class="app-sidebar-wrapper">
        <!--begin::Scroll wrapper-->
        <div id="kt_app_sidebar_menu_scroll" class="scroll-y my-5 mx-3" data-kt-scroll="true" data-kt-scroll-activate="true"
            data-kt-scroll-height="auto" data-kt-scroll-dependencies="#kt_app_sidebar_logo, #kt_app_sidebar_footer"
            data-kt-scroll-wrappers="#kt_app_sidebar_menu" data-kt-scroll-offset="5px" data-kt-scroll-save-state="true">
            <!--begin::Menu-->
            <div class="menu menu-column menu-rounded menu-sub-indention fw-semibold fs-6" id="#kt_app_sidebar_menu"
                data-kt-menu="true" data-kt-menu-expand="false">

                @php
                    // Helpers للـ active/highlight
                    $is = fn(string $pattern) => request()->routeIs($pattern);
                @endphp

                <div class="menu menu-column menu-rounded menu-sub-indention px-3" id="kt_app_sidebar_menu"
                    data-kt-menu="true">

                    {{-- ===================== الرئيسية ===================== --}}
                    <div class="menu-item">
                        <a class="menu-link {{ $is('dashboard.index') ? 'active' : '' }}"
                            href="{{ route('dashboard.index') }}">
                            <span class="menu-icon">
                                <i class="fas fa-chart-line fs-2"></i>
                            </span>
                            <span class="menu-title">{{ t(key: 'sidebar.home') }}</span>
                        </a>
                    </div>

                    <div class="menu-content pt-4 pb-2">
                        <span class="menu-heading fw-bold text-uppercase fs-7">{{ __('bookings.title') }}</span>
                    </div>

                    {{-- ===================== الحجوزات ===================== --}}
                    @can('bookings.view')
                        @php
                            $isBookingsCalendar = $is('dashboard.bookings.calendar');
                            $isBookingsCancelReasons = $is('dashboard.bookings.cancel-reasons.*');
                            $isBookingsList =
                                $is('dashboard.bookings.*') && !$isBookingsCalendar && !$isBookingsCancelReasons;
                        @endphp

                        <div class="menu-item">
                            <a class="menu-link {{ $isBookingsList ? 'active' : '' }}"
                                href="{{ route('dashboard.bookings.index') }}">
                                <span class="menu-icon">
                                    <i class="fas fa-clipboard-list fs-2"></i>
                                </span>
                                <span class="menu-title">{{ t(key: 'bookings.title') }}</span>
                            </a>
                        </div>

                        <div class="menu-item">
                            <a class="menu-link {{ $isBookingsCalendar ? 'active' : '' }}"
                                href="{{ route('dashboard.bookings.calendar') }}">
                                <span class="menu-icon">
                                    <i class="far fa-calendar-alt fs-2"></i>
                                </span>
                                <span class="menu-title">{{ t(key: 'bookings.calendar.title') }}</span>
                            </a>
                        </div>

                        @can('reviews.view')
                            <div class="menu-item">
                                <a class="menu-link {{ $is('dashboard.reviews.*') ? 'active' : '' }}"
                                    href="{{ route('dashboard.reviews.index') }}">
                                    <span class="menu-icon">
                                        <i class="fas fa-star-half-alt fs-2"></i>
                                    </span>
                                    <span class="menu-title">{{ t(key: 'reviews.title') }}</span>
                                </a>
                            </div>
                        @endcan
                        
                    @endcan

                    {{-- ===================== الكتالوج ===================== --}}
                    <div class="menu-content pt-6 pb-2">
                        <span
                            class="menu-heading fw-bold text-uppercase fs-7">{{ __('sidebar.sections.catalog') }}</span>
                    </div>

                    @can('services.view')
                        <div class="menu-item">
                            <a class="menu-link {{ $is('dashboard.services.*') ? 'active' : '' }}"
                                href="{{ route('dashboard.services.index') }}">
                                <span class="menu-icon">
                                    <i class="fas fa-tools fs-2"></i>
                                </span>
                                <span class="menu-title">{{ t(key: 'services.title') }}</span>
                            </a>
                        </div>
                    @endcan

                    @can('products.view')
                        <div class="menu-item">
                            <a class="menu-link {{ $is('dashboard.products.*') ? 'active' : '' }}"
                                href="{{ route('dashboard.products.index') }}">
                                <span class="menu-icon">
                                    <i class="fas fa-shopping-basket fs-2"></i>
                                </span>
                                <span class="menu-title">{{ t(key: 'products.title') }}</span>
                            </a>
                        </div>
                    @endcan

                    @can('packages.view')
                        <div class="menu-item">
                            <a class="menu-link {{ $is('dashboard.packages.*') ? 'active' : '' }}"
                                href="{{ route('dashboard.packages.index') }}">
                                <span class="menu-icon">
                                    <i class="fas fa-box-open fs-2"></i>
                                </span>
                                <span class="menu-title">{{ t(key: 'packages.title') }}</span>
                            </a>
                        </div>
                    @endcan

                    @can('package_subscriptions.view')
                        <div class="menu-item">
                            <a class="menu-link {{ $is('dashboard.package-subscriptions.*') ? 'active' : '' }}"
                                href="{{ route('dashboard.package-subscriptions.index') }}">
                                <span class="menu-icon">
                                    <i class="fas fa-id-card fs-2"></i>
                                </span>
                                <span class="menu-title">{{ t(key: 'package_subscriptions.title') }}</span>
                            </a>
                        </div>
                    @endcan

                    @can('promotions.view')
                        <div class="menu-item">
                            <a class="menu-link {{ $is('dashboard.promotions.*') ? 'active' : '' }}"
                                href="{{ route('dashboard.promotions.index') }}">
                                <span class="menu-icon">
                                    <i class="fas fa-tags fs-2"></i>
                                </span>
                                <span class="menu-title">{{ t(key: 'promotions.title') }}</span>
                            </a>
                        </div>
                    @endcan

                    {{-- ===================== الأشخاص ===================== --}}
                    <div class="menu-content pt-6 pb-2">
                        <span
                            class="menu-heading fw-bold text-uppercase fs-7">{{ __('sidebar.sections.people') }}</span>
                    </div>

                    @can('customers.view')
                        <div class="menu-item">
                            <a class="menu-link {{ $is('dashboard.customers.*') ? 'active' : '' }}"
                                href="{{ route('dashboard.customers.index') }}">
                                <span class="menu-icon">
                                    <i class="fas fa-users fs-2"></i>
                                </span>
                                <span class="menu-title">{{ t(key: 'customers.title') }}</span>
                            </a>
                        </div>
                    @endcan

                    @can('employees.view')
                        <div class="menu-item">
                            <a class="menu-link {{ $is('dashboard.employees.*') ? 'active' : '' }}"
                                href="{{ route('dashboard.employees.index') }}">
                                <span class="menu-icon">
                                    <i class="fas fa-user-tie fs-2"></i>
                                </span>
                                <span class="menu-title">{{ t(key: 'employees.title') }}</span>
                            </a>
                        </div>
                    @endcan

                    @can('users.view')
                        <div class="menu-item">
                            <a class="menu-link {{ $is('dashboard.users.*') ? 'active' : '' }}"
                                href="{{ route('dashboard.users.index') }}">
                                <span class="menu-icon">
                                    <i class="fas fa-user-cog fs-2"></i>
                                </span>
                                <span class="menu-title">{{ t(key: 'users.title') }}</span>
                            </a>
                        </div>
                    @endcan

                    {{-- ===================== المالية ===================== --}}
                    <div class="menu-content pt-6 pb-2">
                        <span
                            class="menu-heading fw-bold text-uppercase fs-7">{{ __('sidebar.sections.finance') }}</span>
                    </div>

                    @can('invoices.view')
                        <div class="menu-item">
                            <a class="menu-link {{ $is('dashboard.invoices.*') ? 'active' : '' }}"
                                href="{{ route('dashboard.invoices.index') }}">
                                <span class="menu-icon">
                                    <i class="fas fa-file-invoice-dollar fs-2"></i>
                                </span>
                                <span class="menu-title">{{ t(key: 'invoices.title') }}</span>
                            </a>
                        </div>
                    @endcan

                    @can('payments.view')
                        <div class="menu-item">
                            <a class="menu-link {{ $is('dashboard.payments.*') ? 'active' : '' }}"
                                href="{{ route('dashboard.payments.index') }}">
                                <span class="menu-icon">
                                    <i class="fas fa-money-check-alt fs-2"></i>
                                </span>
                                <span class="menu-title">{{ t(key: 'payments.title') }}</span>
                            </a>
                        </div>
                    @endcan

                    @can('wallets.view')
                        <div class="menu-item">
                            <a class="menu-link {{ $is('dashboard.wallets.*') ? 'active' : '' }}"
                                href="{{ route('dashboard.wallets.index') }}">
                                <span class="menu-icon">
                                    <i class="fas fa-wallet fs-2"></i>
                                </span>
                                <span class="menu-title">{{ t(key: 'wallets.title') }}</span>
                            </a>
                        </div>
                    @endcan

                    @can('points.view')
                        <div class="menu-item">
                            <a class="menu-link {{ $is('dashboard.points.*') ? 'active' : '' }}"
                                href="{{ route('dashboard.points.index') }}">
                                <span class="menu-icon">
                                    <i class="fas fa-star fs-2"></i>
                                </span>
                                <span class="menu-title">{{ t(key: 'points.title') }}</span>
                            </a>
                        </div>
                    @endcan

                    {{-- ===================== الإعدادات ===================== --}}
                    <div class="menu-content pt-6 pb-2">
                        <span
                            class="menu-heading fw-bold text-uppercase fs-7">{{ __('sidebar.sections.settings') }}</span>
                    </div>

                    @can('branches.view')
                        <div class="menu-item">
                            <a class="menu-link {{ $is('dashboard.branches.*') ? 'active' : '' }}"
                                href="{{ route('dashboard.branches.index') }}">
                                <span class="menu-icon">
                                    <i class="fas fa-map-marked-alt fs-2"></i>
                                </span>
                                <span class="menu-title">{{ t(key: 'branches.title') }}</span>
                            </a>
                        </div>
                    @endcan

                    @can('cancel_reasons.view')
                        <div class="menu-item">
                            <a class="menu-link {{ $is('dashboard.bookings.cancel-reasons.*') ? 'active' : '' }}"
                                href="{{ route('dashboard.bookings.cancel-reasons.index') }}">
                                <span class="menu-icon">
                                    <i class="fas fa-times-circle fs-2"></i>
                                </span>
                                <span class="menu-title">{{ t(key: 'bookings.cancel_reasons.title') }}</span>
                            </a>
                        </div>
                    @endcan

                    @can('customer_groups.view')
                        <div class="menu-item">
                            <a class="menu-link {{ $is('dashboard.customer-groups.*') ? 'active' : '' }}"
                                href="{{ route('dashboard.customer-groups.index') }}">
                                <span class="menu-icon">
                                    <i class="fas fa-layer-group fs-2"></i>
                                </span>
                                <span class="menu-title">{{ t(key: 'customer_groups.title') }}</span>
                            </a>
                        </div>
                    @endcan

                    @can('carousel_items.view')
                        <div class="menu-item">
                            <a class="menu-link {{ $is('dashboard.carousel-items.*') ? 'active' : '' }}"
                                href="{{ route('dashboard.carousel-items.index') }}">
                                <span class="menu-icon">
                                    <i class="fas fa-images fs-2"></i>
                                </span>
                                <span class="menu-title">{{ t(key: 'carousel.title') }}</span>
                            </a>
                        </div>
                    @endcan

                    @can('app_pages.view')
                        <div class="menu-item">
                            <a class="menu-link {{ $is('dashboard.app-pages.*') ? 'active' : '' }}"
                                href="{{ route('dashboard.app-pages.index') }}">
                                <span class="menu-icon">
                                    <i class="fas fa-file-alt fs-2"></i>
                                </span>
                                <span class="menu-title">{{ t(key: 'app-pages.title') }}</span>
                            </a>
                        </div>
                    @endcan

                    @can('partners.view')
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('dashboard.partners.*') ? 'active' : '' }}"
                                href="{{ route('dashboard.partners.index') }}">
                                <span class="menu-icon">
                                    <i class="fa-solid fa-handshake fs-2"></i>
                                </span>
                                <span class="menu-title">{{ __('partners.partners') }}</span>
                            </a>
                        </div>
                    @endcan

                    @can('faqs.view')
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('dashboard.faqs.*') ? 'active' : '' }}"
                                href="{{ route('dashboard.faqs.index') }}">
                                <span class="menu-icon">
                                    <i class="fa-solid fa-circle-question nav-icon"></i>
                                </span>
                                <span class="menu-title">{{ __('faqs.title') }}</span>
                            </a>
                        </div>
                    @endcan

                    @can('promotional_notifications.view')
                        <div class="menu-item">
                            <a class="menu-link {{ $is('dashboard.promotional-notifications.*') ? 'active' : '' }}"
                                href="{{ route('dashboard.promotional-notifications.index') }}">
                                <span class="menu-icon">
                                    <i class="ki-duotone ki-send fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </span>
                                <span class="menu-title">{{ t(key: 'promotional_notifications.title') }}</span>
                            </a>
                        </div>
                    @endcan

                    @can('notifications.view')
                        <div class="menu-item">
                            <a class="menu-link {{ $is('dashboard.notifications.*') ? 'active' : '' }}"
                                href="{{ route('dashboard.notifications.index') }}">
                                <span class="menu-icon">
                                    <i class="fas fa-bell fs-2"></i>
                                </span>
                                <span class="menu-title">{{ t(key: 'notifications.title') }}</span>
                            </a>
                        </div>
                    @endcan

                    @can('roles.view')
                        <div class="menu-item">
                            <a class="menu-link {{ $is('dashboard.roles.*') ? 'active' : '' }}"
                                href="{{ route('dashboard.roles.index') }}">
                                <span class="menu-icon">
                                    <i class="fas fa-shield-alt fs-2"></i>
                                </span>
                                <span class="menu-title">{{ t(key: 'roles.title') }}</span>
                            </a>
                        </div>
                    @endcan

                </div>
            </div>
            <!--end::Menu-->
        </div>
        <!--end::Scroll wrapper-->
    </div>
    <!--end::Menu wrapper-->
</div>
<!--end::sidebar menu-->
