<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AddressStoreRequest;
use App\Http\Requests\Api\AddressUpdateRequest;
use App\Http\Resources\Api\AddressResource;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MyAddressController extends Controller
{
    // GET /api/v1/my-addresses
    public function index(Request $request)
    {
        $user = $request->user();

        $q = Address::query()
            ->where('user_id', $user->id)
            ->orderByDesc('is_default')
            ->orderByDesc('id');

        $p = $q->paginate(50);

        $p->setCollection(
            $p->getCollection()->map(fn($i) => new AddressResource($i))
        );

        return api_paginated($p);
    }

    // POST /api/v1/my-addresses
    public function store(AddressStoreRequest $request)
    {
        $user = $request->user();

        $address = DB::transaction(function () use ($request, $user) {
            $data = $request->validated();
            $data['user_id'] = $user->id;

            // ✅ التحقق إذا كان أول عنوان للمستخدم
            $isFirstAddress = !Address::where('user_id', $user->id)->exists();

            // ✅ إذا أول عنوان → نخليه default و current تلقائياً
            if ($isFirstAddress) {
                $data['is_default'] = true;
                $data['is_current_location'] = true; // ✅ الجديد
            } else {
                // ✅ معالجة is_default
                $makeDefault = (bool) ($data['is_default'] ?? false);
                if ($makeDefault) {
                    Address::where('user_id', $user->id)
                        ->update(['is_default' => false]);
                    $data['is_default'] = true;
                } else {
                    $data['is_default'] = false;
                }

                // ✅ معالجة is_current_location
                if (!empty($data['is_current_location'])) {
                    Address::where('user_id', $user->id)
                        ->where('is_current_location', true)
                        ->update(['is_current_location' => false]);
                } else {
                    $data['is_current_location'] = false;
                }
            }

            return Address::create($data);
        });

        return api_success(new AddressResource($address), __('addresses.created'), 201);
    }

    // GET /api/v1/my-addresses/{address}
    public function show(Request $request, Address $address)
    {
        if ($address->user_id !== $request->user()->id) {
            return api_error(__('api.not_found'), 404);
        }

        return api_success(new AddressResource($address));
    }

    // PUT /api/v1/my-addresses/{address}
    public function update(AddressUpdateRequest $request, Address $address)
    {
        if ($address->user_id !== $request->user()->id) {
            return api_error(__('api.not_found'), 404);
        }

        DB::transaction(function () use ($request, $address) {
            $data = $request->validated();

            if (array_key_exists('is_default', $data) && (bool) $data['is_default'] === true) {
                Address::where('user_id', $address->user_id)->update(['is_default' => false]);
                $data['is_default'] = true;
            }

            if (!empty($data['is_current_location'])) {
                Address::where('user_id', $address->user_id)
                    ->where('id', '!=', $address->id)
                    ->where('is_current_location', true)
                    ->update(['is_current_location' => false]);
            }

            $address->update($data);
        });

        $address->refresh();
        return api_success(new AddressResource($address), __('addresses.updated'));
    }

    // DELETE /api/v1/my-addresses/{address}
    public function destroy(Request $request, Address $address)
    {
        if ($address->user_id !== $request->user()->id) {
            return api_error(__('api.not_found'), 404);
        }

        DB::transaction(function () use ($address) {
            $wasDefault = $address->is_default;
            $userId = $address->user_id;

            $address->delete();

            // لو حذف الافتراضي → اجعل أحدث عنوان افتراضي (إن وجد)
            if ($wasDefault) {
                $next = Address::where('user_id', $userId)->orderByDesc('id')->first();
                if ($next) {
                    Address::where('user_id', $userId)->update(['is_default' => false]);
                    $next->update(['is_default' => true]);
                }
            }
        });

        return api_success(null, __('addresses.deleted'));
    }
}
