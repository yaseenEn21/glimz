<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\CarouselController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\BookingPackageController;
use App\Http\Controllers\Api\BookingRatingController;
use App\Http\Controllers\Api\Partners\PartnerBookingController;
use App\Http\Controllers\Api\PointController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ServiceCategoryController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\PackageController;
use App\Http\Controllers\Api\MyPackageController;
use App\Http\Controllers\Api\PackagePurchaseController;
use App\Http\Controllers\Api\VehicleMakeController;
use App\Http\Controllers\Api\VehicleModelController;
use App\Http\Controllers\Api\CarColorController;
use App\Http\Controllers\Api\MyCarController;
use App\Http\Controllers\Api\MyAddressController;
use App\Http\Controllers\Api\ProductCategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SlotController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\WalletTransactionController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\InvoicePaymentController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\InvoiceCouponController;
use App\Http\Controllers\Api\MoyasarWebhookController;
use App\Http\Controllers\Api\RekazWebhookController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\AppTranslationController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;


Route::prefix('v1')->middleware(['set.api.locale'])->group(function () {

    Route::post('rekaz/webhook', [MoyasarWebhookController::class, 'handle']);

    Route::post('moyasar/webhook', [MoyasarWebhookController::class, 'handle']);
    Route::get('moyasar/callback', [MoyasarWebhookController::class, 'callback']);
    Route::get('moyasar/success', [MoyasarWebhookController::class, 'success']);

    Route::get('carousel', [CarouselController::class, 'index']);

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('send-otp', [AuthController::class, 'sendOtp']);
        Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('logout-all', [AuthController::class, 'logoutAll']);
        });
    });

    Route::middleware('auth:sanctum')->group(function () {

        Route::get('profile', [ProfileController::class, 'show']);
        Route::post('profile', [ProfileController::class, 'update']);
        Route::put('update-language', [ProfileController::class, 'updateLanguage']);
        Route::delete('delete-account', [ProfileController::class, 'deleteAccount']);
        Route::delete('profile/image', [ProfileController::class, 'deleteProfileImage']);

        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::get('notifications', [NotificationController::class, 'index']);

        Route::get('bookings/package-eligibility', [BookingPackageController::class, 'eligibility']);

        Route::get('eligible-packages', [BookingController::class, 'eligiblePackages']);
        Route::get('settings/booking-cancel-reasons', [SettingController::class, 'bookingCancelReasons']);
        Route::post('bookings', [BookingController::class, 'store']);
        Route::get('/bookings/{id}/edit', [BookingController::class, 'edit']);
        Route::put('/bookings/{id}', [BookingController::class, 'update']);
        Route::patch('bookings/{booking}/reschedule', [BookingController::class, 'reschedule']);
        Route::get('bookings/{booking}/products-edit', [BookingController::class, 'productsEdit']);
        Route::patch('bookings/{booking}/products', [BookingController::class, 'updateProducts']);
        Route::delete('bookings/{booking}', [BookingController::class, 'cancel']);
        Route::get('bookings', [BookingController::class, 'index']);
        Route::get('bookings/{booking}', [BookingController::class, 'show']);
        Route::get('bookings/{booking}/rating', [BookingRatingController::class, 'show']);
        Route::post('bookings/{booking}/rating', [BookingRatingController::class, 'store']);

        Route::get('my-cars', [MyCarController::class, 'index']);
        Route::post('my-cars', [MyCarController::class, 'store']);
        Route::post('my-cars/{car}/make-default', [MyCarController::class, 'makeDefault']);
        Route::get('my-cars/{car}', [MyCarController::class, 'show']);
        Route::put('my-cars/{car}', [MyCarController::class, 'update']);
        Route::delete('my-cars/{car}', [MyCarController::class, 'destroy']);

        Route::get('my-addresses', [MyAddressController::class, 'index']);
        Route::post('my-addresses', [MyAddressController::class, 'store']);
        Route::get('my-addresses/{address}', [MyAddressController::class, 'show']);
        Route::put('my-addresses/{address}', [MyAddressController::class, 'update']);
        Route::delete('my-addresses/{address}', [MyAddressController::class, 'destroy']);

        // My packages (auth)
        Route::get('my-packages', [MyPackageController::class, 'index']);

        // Purchase (auth)
        Route::post('packages/{package}/purchase', [PackagePurchaseController::class, 'store']);

        // Wallet
        Route::get('wallet', [WalletController::class, 'show']);
        Route::post('wallet/store', [WalletController::class, 'store']);
        Route::get('wallet/transactions', [WalletTransactionController::class, 'index']);

        // Invoices
        Route::get('invoices/{invoice}/download', [InvoiceController::class, 'download']);
        Route::get('invoices', [InvoiceController::class, 'index']);
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show']);

        // Payments
        Route::get('payments', [PaymentController::class, 'index']);
        Route::get('payments/{payment}', [PaymentController::class, 'show']);
        Route::post('invoices/{invoice}/payments', [InvoicePaymentController::class, 'store']);

        // Coupons
        Route::get('coupons', [CouponController::class, 'index']); // ?tab=available|used
        Route::post('invoices/{invoice}/coupon/preview', [InvoiceCouponController::class, 'preview']);
        Route::post('invoices/{invoice}/coupon/apply', [InvoiceCouponController::class, 'apply']);
        Route::delete('invoices/{invoice}/coupon', [InvoiceCouponController::class, 'destroy']);

        Route::get('points', [PointController::class, 'show']);
        Route::get('points/transactions', action: [PointController::class, 'transactions']);
        Route::post('points/redeem/preview', [PointController::class, 'previewRedeem']);
        Route::post('points/redeem', [PointController::class, 'redeem']);


    });

    Route::get('slots', [SlotController::class, 'index']);

    Route::get('service-categories', [ServiceCategoryController::class, 'index']);
    Route::get('service-categories/{serviceCategory}', [ServiceCategoryController::class, 'show']);

    Route::get('services', [ServiceController::class, 'index']);
    Route::get('services/{service}', [ServiceController::class, 'show']);

    // Public browse
    Route::get('packages', [PackageController::class, 'index']);
    Route::get('packages/{package}', [PackageController::class, 'show']);

    Route::get('product-categories', [ProductCategoryController::class, 'index']);

    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{product}', [ProductController::class, 'show']);

    Route::get('vehicle-makes', [VehicleMakeController::class, 'index']);
    Route::get('vehicle-models', [VehicleModelController::class, 'index']); // ?vehicle_make_id=...
    Route::get('car-colors', [CarColorController::class, 'index']);

    Route::get('settings', [SettingController::class, 'getSettings']);
    Route::get('settings/clear-cache', [SettingController::class, 'clearCache']);
    Route::get('help-and-support/faqs', [SettingController::class, 'faqs']);
    Route::get('help-and-support/contact-info', [SettingController::class, 'contactInfo']);
    Route::get('settings/policies', [SettingController::class, 'policies']);
    Route::get('settings/privacy-policy', [SettingController::class, 'privacyPolicy']);
    Route::get('settings/cancellation-and-refund', [SettingController::class, 'cancellationAndRefund']);

    Route::get('app-translation', [AppTranslationController::class, 'show']);
    Route::post('app-translation', [AppTranslationController::class, 'upload'])
        ->middleware('app.translations.token');

    // Partner API Routes
    Route::prefix('partners')->middleware(['partner.auth'])->group(function () {

        Route::get('services', [PartnerBookingController::class, 'getServices']);
        Route::get('slots', [PartnerBookingController::class, 'getSlots']);

        // Bookings (with limit check)
        Route::middleware('partner.limit')->group(function () {
            Route::post('bookings', [PartnerBookingController::class, 'createBooking']);
        });

        // Other booking operations (no limit check)
        Route::get('bookings', [PartnerBookingController::class, 'listBookings']);
        Route::get('bookings/{external_id}', [PartnerBookingController::class, 'getBooking']);
        Route::put('bookings/{external_id}/update', [PartnerBookingController::class, 'rescheduleBooking']);
        Route::post('bookings/{external_id}/cancel', [PartnerBookingController::class, 'cancelBooking']);
        Route::post('bookings/{external_id}/status', [PartnerBookingController::class, 'updateStatus']);

    });

});