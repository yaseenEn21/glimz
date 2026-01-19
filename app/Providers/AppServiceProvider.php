<?php

namespace App\Providers;

use App\Models\Notification;
use App\Repositories\AuthRepository\AuthRepository;
use App\Repositories\AuthRepository\Interfaces\AuthRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\UserRepository;
use App\Services\MoyasarService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use View;
use App\Models\Booking;
use App\Observers\BookingObserver;
use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AuthRepositoryInterface::class, AuthRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->singleton(MoyasarService::class, function () {
            return new MoyasarService(config('services.moyasar.secret'));
        });

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // Relation::enforceMorphMap([
        //     'service' => \App\Models\Service::class,
        //     'product' => \App\Models\Product::class,
        //     'package' => \App\Models\Package::class,
        //     'user' => \App\Models\User::class,
        // ]);

        Booking::observe(BookingObserver::class);

        View::composer('base.layout.*', function ($view) {
            $user = auth()->user();

            if (!$user) {
                return;
            }

            $unreadCount = Notification::where('user_id', $user->id)
                ->where('is_read', false)
                ->count();

            $latestNotifications = Notification::where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->limit(5)
                ->get();

            $view->with([
                'webNotificationsUnreadCount' => $unreadCount,
                'webNotificationsLatest' => $latestNotifications,
            ]);
        });

    }
}