<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Support\Str;
use Yajra\DataTables\DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReviewController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:reviews.view')->only(['index', 'datatable']);
    }

    public function index()
    {

        view()->share([
            'title' => __('reviews.title'),
            'page_title' => __('reviews.title'),
        ]);

        return view('dashboard.reviews.index');
    }

    public function datatable(Request $request)
    {
        $query = Booking::query()
            ->whereNotNull('rating')
            ->whereNotNull('rated_at')
            ->with(['user', 'service', 'employee']);

        // Filters
        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }
        if ($request->filled('from')) {
            $query->whereDate('rated_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('rated_at', '<=', $request->to);
        }

        return DataTables::of($query)
            ->addColumn('customer', function ($booking) {
                return view('dashboard.bookings.partials._customer', ['user' => $booking->user]);
            })
            ->addColumn('service_name', function ($booking) {
                $name = is_array($booking->service?->name)
                    ? ($booking->service->name[app()->getLocale()] ?? collect($booking->service->name)->first())
                    : $booking->service?->name;
                return $name ?? '—';
            })
            ->addColumn('employee_label', function ($booking) {
                return $booking->employee?->name ?? '—';
            })
            ->addColumn('rating_stars', function ($booking) {
                return str_repeat('⭐', $booking->rating) . " ({$booking->rating})";
            })
            ->editColumn('rating_comment', function ($booking) {
                $comment = $booking->rating_comment ?? '—';
                return Str::limit($comment, 50);
            })
            ->addColumn('rated_at_formatted', function ($booking) {
                return $booking->rated_at
                    ? Carbon::parse($booking->rated_at)->format('Y/m/d h:i A')
                    : '—';
            })
            ->addColumn('actions', function ($booking) {
                return view('dashboard.reviews.partials._actions', compact('booking'));
            })
            ->rawColumns(['customer', 'actions'])
            ->make(true);
    }



}