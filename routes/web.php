<?php

use App\Http\Controllers\Dashboard\BranchController;
use App\Http\Controllers\Dashboard\EmployeeController;
use App\Http\Controllers\Dashboard\FaqController;
use App\Http\Controllers\Dashboard\HomeController;
use App\Http\Controllers\Dashboard\CarouselItemController;
use App\Http\Controllers\Dashboard\CustomerController;
use App\Http\Controllers\Dashboard\InvoicePaymentController;
use App\Http\Controllers\Dashboard\PartnerController;
use App\Http\Controllers\Dashboard\PromotionalNotificationController;
use App\Http\Controllers\Dashboard\ReviewController;
use App\Http\Controllers\Dashboard\RoleController;
use App\Http\Controllers\Dashboard\UserController;
use App\Http\Controllers\Dashboard\ServiceController;
use App\Http\Controllers\Dashboard\ProductController;
use App\Http\Controllers\Dashboard\PackageController;
use App\Http\Controllers\Dashboard\PackageSubscriptionController;
use App\Http\Controllers\Dashboard\PointController;
use App\Http\Controllers\Dashboard\WalletController;
use App\Http\Controllers\Dashboard\PromotionController;
use App\Http\Controllers\Dashboard\PromotionCouponController;
use App\Http\Controllers\Dashboard\InvoiceController;
use App\Http\Controllers\Dashboard\PaymentController;
use App\Http\Controllers\Dashboard\ZoneController;
use App\Http\Controllers\Dashboard\CustomerGroupController;
use App\Http\Controllers\Dashboard\CustomerGroupServicePriceController;
use App\Http\Controllers\Dashboard\ZoneServicePriceController;
use App\Http\Controllers\Dashboard\BookingController;
use App\Http\Controllers\Dashboard\BookingCancelReasonController;
use App\Http\Controllers\Dashboard\BookingCalendarController;
use App\Http\Controllers\Dashboard\NotificationTemplateController;
use App\Http\Controllers\Dashboard\NotificationController;
use App\Http\Controllers\Dashboard\AppPageController;
use App\Http\Controllers\ProfileController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {


    Route::prefix('dashboard')->name('dashboard.')->group(function () {

        Route::get('/', [HomeController::class, 'index'])->name('index');
        Route::get('/kpi', [HomeController::class, 'kpi'])->name('kpi');
        Route::get('/upcoming-bookings', [HomeController::class, 'upcomingBookings'])->name('upcoming-bookings');
        Route::get('/stats', [HomeController::class, 'stats'])->name('stats');


        Route::get('lang/{locale}', [HomeController::class, 'switchLang'])
            ->whereIn('locale', ['ar', 'en'])
            ->name('lang.switch');

        Route::prefix('carousel-items')->name('carousel-items.')->group(function () {
            Route::get('/', [CarouselItemController::class, 'index'])->name('index');
            Route::get('/datatable', [CarouselItemController::class, 'datatable'])->name('datatable');

            Route::get('/create', [CarouselItemController::class, 'create'])->name('create');
            Route::post('/', [CarouselItemController::class, 'store'])->name('store');

            Route::get('/{carouselItem}', [CarouselItemController::class, 'show'])->name('show');

            Route::get('/{carouselItem}/edit', [CarouselItemController::class, 'edit'])->name('edit');
            Route::put('/{carouselItem}', [CarouselItemController::class, 'update'])->name('update');

            Route::delete('/{carouselItem}', [CarouselItemController::class, 'destroy'])->name('destroy');

            // AJAX: load carouselable items by type (select2)
            Route::get('/lookups/carouselables', [CarouselItemController::class, 'carouselablesLookup'])->name('lookups.carouselables');
        });
        Route::get('users/select2', [UserController::class, 'select2'])->name('users.select2');
        Route::resource('users', UserController::class);

        Route::resource('roles', RoleController::class);
        Route::get('roles/create', [RoleController::class, 'create'])->name('roles.create');
        Route::post('roles', [RoleController::class, 'store'])->name('roles.store');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

        Route::resource('branches', BranchController::class);

        Route::prefix('bookings')
            ->name('bookings.')
            ->group(function () {

                Route::get('calendar', [BookingCalendarController::class, 'index'])->name('calendar');
                Route::post('calendar/block-slots', [BookingCalendarController::class, 'blockSlots'])->name('calendar.block-slots');
                Route::delete('calendar/block-slots/{employeeTimeBlock}', [BookingCalendarController::class, 'destroyBlockSlot'])->name('calendar.block-slots.destroy');
                Route::get('calendar/resources', [BookingCalendarController::class, 'resources'])->name('calendar.resources');
                Route::get('calendar/events', [BookingCalendarController::class, 'events'])->name('calendar.events');

                Route::post('{booking}/calendar-move', [BookingCalendarController::class, 'move'])
                    ->name('calendar.move');
            });

        Route::get('cancel-reasons/datatable', [BookingCancelReasonController::class, 'datatable'])
            ->name('bookings.cancel-reasons.datatable');

        Route::get('bookings/export', [BookingController::class, 'export'])->name('bookings.export');
        Route::resource('bookings/cancel-reasons', BookingCancelReasonController::class)
            ->except(['show'])
            ->names('bookings.cancel-reasons')
            ->parameters(['cancel-reasons' => 'cancelReason']);
        Route::delete('bookings/{booking}', [BookingController::class, 'destroy'])
            ->name('bookings.destroy');
        Route::get('bookings/{booking}/edit', [BookingController::class, 'edit'])->name('bookings.edit');
        Route::put('bookings/{booking}', [BookingController::class, 'update'])->name('bookings.update');
        Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::get('bookings/datatable', [BookingController::class, 'datatable'])->name('bookings.datatable');
        Route::get('bookings/create', [BookingController::class, 'create'])->name('bookings.create');
        Route::get('bookings/slots', [BookingController::class, 'slots'])->name('bookings.slots');
        Route::get('bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
        Route::patch('bookings/{booking}/status', [BookingController::class, 'updateStatus'])
            ->name('bookings.status.update');

        // bookings
        Route::post('bookings', [BookingController::class, 'store'])->name('bookings.store');

        // ajax lookups
        Route::get('bookings/lookups/users', [BookingController::class, 'usersLookup'])->name('bookings.lookups.users');
        Route::get('bookings/lookups/users/{user}/cars', [BookingController::class, 'userCars'])->name('bookings.lookups.user_cars');
        Route::get('bookings/lookups/users/{user}/addresses', [BookingController::class, 'userAddresses'])->name('bookings.lookups.user_addresses');
        Route::get('users/{user}/package-subscriptions', [BookingController::class, 'lookupUserPackageSubscriptions'])
            ->name('bookings.lookups.user_package_subscriptions');

        Route::get('bookings/lookups/products', [BookingController::class, 'productsLookup'])->name('bookings.lookups.products');

        // modals (create quick entities)
        Route::post('bookings/users', [BookingController::class, 'storeQuickCustomer'])->name('bookings.users.store');
        Route::post('bookings/users/{user}/cars', [BookingController::class, 'storeUserCar'])->name('bookings.user_cars.store');
        Route::post('bookings/users/{user}/addresses', [BookingController::class, 'storeUserAddress'])->name('bookings.user_addresses.store');

        // vehicle lookups for car modal
        Route::get('lookups/vehicle-makes', [BookingController::class, 'vehicleMakesLookup'])->name('lookups.vehicle_makes');
        Route::get('lookups/vehicle-models', [BookingController::class, 'vehicleModelsLookup'])->name('lookups.vehicle_models');

        Route::get('services/{service}/sales-lines', [ServiceController::class, 'salesLinesDatatable'])->name('services.salesLines');
        Route::get('services/{service}/sales-stats', [ServiceController::class, 'salesStats'])->name('services.salesStats');
        Route::resource('services', ServiceController::class);

        Route::resource('packages', PackageController::class);
        Route::resource('package-subscriptions', PackageSubscriptionController::class);
        Route::resource('employees', EmployeeController::class);


        Route::get('settings/points', [PointController::class, 'editSettings'])->name('settings.points.edit');
        Route::put('settings/points', [PointController::class, 'updateSettings'])->name('settings.points.update');
        Route::get('points', [PointController::class, 'index'])->name('points.index');
        Route::get('points/create', [PointController::class, 'create'])->name('points.create');
        Route::post('points', [PointController::class, 'store'])->name('points.store');
        Route::get('points/wallet-info/{user}', [PointController::class, 'walletInfo'])->name('points.walletInfo');

        Route::get('products/{product}/sales-lines', [ProductController::class, 'salesLinesDatatable'])->name('products.salesLines');
        Route::get('products/{product}/sales-stats', [ProductController::class, 'salesStats'])->name('products.salesStats');
        Route::resource('products', ProductController::class);

        Route::prefix('wallets')->name('wallets.')->group(function () {

            Route::get('/', [WalletController::class, 'index'])->name('index');
            Route::get('datatable', [WalletController::class, 'datatable'])->name('datatable');
            Route::get('create', [WalletController::class, 'create'])->name('create');
            Route::post('/', [WalletController::class, 'store'])->name('store');
            Route::get('wallet-info/{user}', [WalletController::class, 'walletInfo'])->name('wallet_info');

        });



        // Promotions
        Route::get('promotions/datatable', [PromotionController::class, 'datatable'])->name('promotions.datatable');
        Route::get('promotions/search/services', [PromotionController::class, 'searchServices'])->name('promotions.search.services');
        Route::get('promotions/search/packages', [PromotionController::class, 'searchPackages'])->name('promotions.search.packages');
        Route::resource('promotions', PromotionController::class);

        // Coupons (edit/update/delete) + Redemptions
        Route::get('promotion-coupons/{coupon}/edit', [PromotionCouponController::class, 'edit'])->name('promotion_coupons.edit');
        Route::put('promotion-coupons/{coupon}', [PromotionCouponController::class, 'update'])->name('promotion_coupons.update');
        Route::delete('promotion-coupons/{coupon}', [PromotionCouponController::class, 'destroy'])->name('promotion_coupons.destroy');

        Route::get('promotion-coupons/{coupon}/redemptions', [PromotionCouponController::class, 'redemptions'])->name('promotion_coupons.redemptions');
        Route::get('promotion-coupons/{coupon}/redemptions/datatable', [PromotionCouponController::class, 'redemptionsDatatable'])->name('promotion_coupons.redemptions.datatable');

        // ===== Coupons =====
        Route::get('promotions/{promotion}/coupons', [PromotionCouponController::class, 'index'])
            ->name('promotions.coupons.index');

        Route::get('promotions/{promotion}/coupons/datatable', [PromotionCouponController::class, 'datatable'])
            ->name('promotions.coupons.datatable');

        Route::get('promotions/{promotion}/coupons/create', [PromotionCouponController::class, 'create'])
            ->name('promotions.coupons.create');

        Route::post('promotions/{promotion}/coupons', [PromotionCouponController::class, 'store'])
            ->name('promotions.coupons.store');

        Route::get('promotions/{promotion}/coupons/{coupon}/edit', [PromotionCouponController::class, 'edit'])
            ->name('promotions.coupons.edit');

        Route::put('promotions/{promotion}/coupons/{coupon}', [PromotionCouponController::class, 'update'])
            ->name('promotions.coupons.update');

        Route::delete('promotions/{promotion}/coupons/{coupon}', [PromotionCouponController::class, 'destroy'])
            ->name('promotions.coupons.destroy');

        // ===== Redemptions =====
        Route::get('promotions/{promotion}/coupons/{coupon}/redemptions', [PromotionCouponController::class, 'redemptions'])
            ->name('promotions.coupons.redemptions');

        Route::get('promotions/{promotion}/coupons/{coupon}/redemptions/datatable', [PromotionCouponController::class, 'redemptionsDatatable'])
            ->name('promotions.coupons.redemptions.datatable');

        // Manual Payment Routes
        Route::prefix('invoices/{invoice}')->name('invoices.')->group(function () {
            Route::get('manual-payment', [InvoicePaymentController::class, 'showManualPaymentForm'])
                ->name('manual-payment.show');
            // ->middleware('can:invoices.pay_manually');

            Route::post('manual-payment', [InvoicePaymentController::class, 'processManualPayment'])
                ->name('manual-payment.process');
            // ->middleware('can:invoices.pay_manually');
        });

        Route::get('invoices/datatable', [InvoiceController::class, 'datatable'])->name('invoices.datatable');
        Route::resource('invoices', InvoiceController::class)->only(['index', 'show']);

        Route::get('payments/datatable', [PaymentController::class, 'datatable'])->name('payments.datatable');
        Route::resource('payments', PaymentController::class)->only(['index', 'show']);

        Route::get('customer-groups/datatable', [CustomerGroupController::class, 'datatable'])
            ->name('customer-groups.datatable');

        Route::resource('customer-groups', CustomerGroupController::class)
            ->parameters(['customer-groups' => 'customer_group'])
            ->names('customer-groups');

        Route::resource('customer-groups', CustomerGroupController::class);

        // Service Prices (Ajax)
        Route::get('customer-groups/{customerGroup}/service-prices/datatable', [CustomerGroupServicePriceController::class, 'datatable'])
            ->name('customer-groups.service-prices.datatable');

        Route::get('customer-groups/{customerGroup}/service-prices/{servicePrice}', [CustomerGroupServicePriceController::class, 'show'])
            ->name('customer-groups.service-prices.show');

        Route::post('customer-groups/{customerGroup}/service-prices', [CustomerGroupServicePriceController::class, 'store'])
            ->name('customer-groups.service-prices.store');

        Route::put('customer-groups/{customerGroup}/service-prices/{servicePrice}', [CustomerGroupServicePriceController::class, 'update'])
            ->name('customer-groups.service-prices.update');

        Route::delete('customer-groups/{customerGroup}/service-prices/{servicePrice}', [CustomerGroupServicePriceController::class, 'destroy'])
            ->name('customer-groups.service-prices.destroy');

        // Select2 services (exclude already assigned)
        Route::get('customer-groups/{customerGroup}/services/search', [CustomerGroupServicePriceController::class, 'searchServices'])
            ->name('customer-groups.services.search');


        Route::get('zones/datatable', [ZoneController::class, 'datatable'])->name('zones.datatable');
        Route::resource('zones', ZoneController::class)->names('zones');
        // Zone Service Prices (inside zone show)
        Route::get('zones/{zone}/service-prices/services/search', [ZoneServicePriceController::class, 'searchServices'])
            ->name('zones.service_prices.search.services');

        Route::post('zones/{zone}/service-prices', [ZoneServicePriceController::class, 'store'])
            ->name('zones.service_prices.store');

        Route::get('zones/{zone}/service-prices/{servicePrice}', [ZoneServicePriceController::class, 'show'])
            ->name('zones.service_prices.show');

        Route::put('zones/{zone}/service-prices/{servicePrice}', [ZoneServicePriceController::class, 'update'])
            ->name('zones.service_prices.update');

        Route::delete('zones/{zone}/service-prices/{servicePrice}', [ZoneServicePriceController::class, 'destroy'])
            ->name('zones.service_prices.destroy');

        Route::get('notifications', [NotificationController::class, 'index'])
            ->name('notifications.index');

        Route::patch('notifications/{notification}/read', [
            NotificationController::class,
            'markAsRead'
        ])->name('notifications.mark-read');

        Route::prefix('notification-templates')->group(function () {
            Route::get('/', [NotificationTemplateController::class, 'index'])->name('notification-templates.index');
            Route::get('{template}/edit', [NotificationTemplateController::class, 'edit'])->name('notification-templates.edit');
            Route::put('{template}', [NotificationTemplateController::class, 'update'])->name('notification-templates.update');
        });

        Route::prefix('app-pages')->name('app-pages.')->group(function () {
            Route::get('/', [AppPageController::class, 'index'])->name('index');
            Route::get('/{pageKey}/edit', [AppPageController::class, 'edit'])->name('edit');
            Route::put('/{pageKey}', [AppPageController::class, 'update'])->name('update');
        });

        Route::prefix('customers')->name('customers.')->group(function () {
            Route::get('/', [CustomerController::class, 'index'])->name('index');
            Route::get('/datatable', [CustomerController::class, 'datatable'])->name('datatable');

            Route::get('/create', [CustomerController::class, 'create'])->name('create');
            Route::post('/', [CustomerController::class, 'store'])->name('store');

            Route::get('/{customer}', [CustomerController::class, 'show'])->name('show');

            Route::get('/{customer}/edit', [CustomerController::class, 'edit'])->name('edit');
            Route::put('/{customer}', [CustomerController::class, 'update'])->name('update');

            Route::delete('/{customer}', [CustomerController::class, 'destroy'])->name('destroy');

            // Tabs datatables (AJAX)
            Route::get('/{customer}/datatable/bookings', [CustomerController::class, 'bookingsDatatable'])->name('datatable.bookings');
            Route::get('/{customer}/datatable/invoices', [CustomerController::class, 'invoicesDatatable'])->name('datatable.invoices');
            Route::get('/{customer}/datatable/payments', [CustomerController::class, 'paymentsDatatable'])->name('datatable.payments');

            Route::get('/{customer}/datatable/wallet-transactions', [CustomerController::class, 'walletTransactionsDatatable'])->name('datatable.wallet_transactions');
            Route::get('/{customer}/datatable/point-transactions', [CustomerController::class, 'pointTransactionsDatatable'])->name('datatable.point_transactions');
            Route::get('/{customer}/datatable/package-subscriptions', [CustomerController::class, 'packageSubscriptionsDatatable'])->name('datatable.package_subscriptions');
            Route::get('/{customer}/datatable/cars', [CustomerController::class, 'carsDatatable'])->name('datatable.cars');
            Route::get('/{customer}/datatable/addresses', [CustomerController::class, 'addressesDatatable'])->name('datatable.addresses');
        });

        Route::prefix('promotional-notifications')->name('promotional-notifications.')->group(function () {

            // List
            Route::get('/', [PromotionalNotificationController::class, 'index'])
                ->name('index')
                ->middleware('can:promotional_notifications.send');

            // Create
            Route::get('/create', [PromotionalNotificationController::class, 'create'])
                ->name('create')
                ->middleware('can:promotional_notifications.send');

            // Store
            Route::post('/', [PromotionalNotificationController::class, 'store'])
                ->name('store')
                ->middleware('can:promotional_notifications.send');

            // Show
            Route::get('/{promotional_notification}', [PromotionalNotificationController::class, 'show'])
                ->name('show')
                ->middleware('can:promotional_notifications.send');

            // Edit
            Route::get('/{promotional_notification}/edit', [PromotionalNotificationController::class, 'edit'])
                ->name('edit')
                ->middleware('can:promotional_notifications.send');

            // Update
            Route::put('/{promotional_notification}', [PromotionalNotificationController::class, 'update'])
                ->name('update')
                ->middleware('can:promotional_notifications.send');

            // Delete
            Route::delete('/{promotional_notification}', [PromotionalNotificationController::class, 'destroy'])
                ->name('destroy')
                ->middleware('can:promotional_notifications.send');

            // Send manually
            Route::post('/{promotional_notification}/send', [PromotionalNotificationController::class, 'send'])
                ->name('send')
                ->middleware('can:promotional_notifications.send');

            // Cancel scheduled
            Route::post('/{promotional_notification}/cancel', [PromotionalNotificationController::class, 'cancel'])
                ->name('cancel')
                ->middleware('can:promotional_notifications.send');

            // AJAX - Preview recipients count
            Route::post('/preview-recipients', [PromotionalNotificationController::class, 'previewRecipients'])
                ->name('preview-recipients')
                ->middleware('can:promotional_notifications.send');

            // AJAX - Search users for Select2
            Route::get('/search-users', [PromotionalNotificationController::class, 'searchUsers'])
                ->name('search-users')
                ->middleware('can:promotional_notifications.send');
        });

        Route::prefix('faqs')->name('faqs.')->group(function () {
            Route::get('/', [FaqController::class, 'index'])->name('index');
            Route::get('/datatable', [FaqController::class, 'datatable'])->name('datatable');
            Route::get('/create', [FaqController::class, 'create'])->name('create');
            Route::post('/', [FaqController::class, 'store'])->name('store');
            Route::get('/{faq}', [FaqController::class, 'show'])->name('show');
            Route::get('/{faq}/edit', [FaqController::class, 'edit'])->name('edit');
            Route::put('/{faq}', [FaqController::class, 'update'])->name('update');
            Route::delete('/{faq}', [FaqController::class, 'destroy'])->name('destroy');
        });

        // Partners Routes
        Route::prefix('partners')->name('partners.')->group(function () {
            Route::get('/', [PartnerController::class, 'index'])->name('index');
            Route::get('create', [PartnerController::class, 'create'])->name('create');
            Route::post('/', [PartnerController::class, 'store'])->name('store');
            Route::get('{partner}', [PartnerController::class, 'show'])->name('show');
            Route::get('{partner}/edit', [PartnerController::class, 'edit'])->name('edit');
            Route::put('{partner}', [PartnerController::class, 'update'])->name('update');
            Route::delete('{partner}', [PartnerController::class, 'destroy'])->name('destroy');

            Route::get('{partner}/bookings/datatable', [PartnerController::class, 'bookingsDatatable'])
                ->name('bookings.datatable');

            Route::get('{partner}/bookings/stats', [PartnerController::class, 'bookingsStats'])
                ->name('bookings.stats');

            // Token Management
            Route::post('{partner}/regenerate-token', [PartnerController::class, 'regenerateToken'])
                ->name('regenerate-token');

            // Service-Employee Assignment
            Route::get('{partner}/assign-services', [PartnerController::class, 'assignServices'])
                ->name('assign-services');
            Route::post('{partner}/store-assignments', [PartnerController::class, 'storeAssignments'])
                ->name('store-assignments');
        });

        Route::get('reviews', [ReviewController::class, 'index'])->name('reviews.index');
        Route::get('reviews/datatable', [ReviewController::class, 'datatable'])->name('reviews.datatable');
        Route::delete('reviews/{booking}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

    });

});

require __DIR__ . '/auth.php';
