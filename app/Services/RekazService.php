<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;

class RekazService
{
    protected string $apiKey;
    protected string $apiSecret;
    protected string $tenantId;
    protected string $baseUrl;
    protected int $timeout;
    protected int $retryTimes;
    protected int $retryDelay;

    public function __construct()
    {
        $this->apiKey = config('services.rekaz.api_key');
        $this->apiSecret = config('services.rekaz.api_secret', '');
        $this->tenantId = config('services.rekaz.tenant_id');

        // Base URL الصحيح حسب اختبار Postman الناجح
        $this->baseUrl = config('services.rekaz.base_url', 'https://platform.rekaz.io/api/public');

        $this->timeout = config('services.rekaz.timeout', 30);
        $this->retryTimes = config('services.rekaz.retry_times', 3);
        $this->retryDelay = config('services.rekaz.retry_delay', 100);

        if (empty($this->apiKey)) {
            throw new \RuntimeException('Rekaz API key is not configured');
        }

        if (empty($this->tenantId)) {
            throw new \RuntimeException('Rekaz tenant ID is not configured');
        }
    }

    /**
     * إنشاء HTTP client مع Basic Auth و Tenant header
     */
    protected function client()
    {
        return Http::withBasicAuth($this->apiKey, $this->apiSecret)
            ->withHeaders([
                '__tenant' => $this->tenantId,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->retry($this->retryTimes, $this->retryDelay);
    }

    /**
     * إنشاء حجز (Reservation) في ركاز
     * 
     * @param array $reservationData
     * @return array
     * @throws \Exception
     */
    public function createReservation(array $reservationData): array
    {
        try {
            $response = $this->client()
                ->post("{$this->baseUrl}/reservations", $reservationData);

            if ($response->successful()) {
                Log::info('Rekaz reservation created successfully', [
                    'booking_id' => $reservationData['external_id'] ?? null,
                    'rekaz_id' => $response->json('data.id'),
                ]);

                return [
                    'success' => true,
                    'data' => $response->json('data'),
                    'message' => 'Reservation created successfully',
                ];
            }

            Log::error('Rekaz reservation creation failed', [
                'status' => $response->status(),
                'response' => $response->json(),
                'reservation_data' => $reservationData,
            ]);

            return [
                'success' => false,
                'error' => $response->json('message', 'Failed to create reservation'),
                'status_code' => $response->status(),
            ];

        } catch (RequestException $e) {
            Log::error('Rekaz API request exception', [
                'message' => $e->getMessage(),
                'reservation_data' => $reservationData,
            ]);

            throw new \Exception('Failed to create reservation in Rekaz: ' . $e->getMessage());
        }
    }

    /**
     * تحديث حجز في ركاز
     * 
     * @param string $rekazReservationId
     * @param array $updateData
     * @return array
     * @throws \Exception
     */
    public function updateReservation(string $rekazReservationId, array $updateData): array
    {
        try {
            $response = $this->client()
                ->put("{$this->baseUrl}/reservations/{$rekazReservationId}", $updateData);

            if ($response->successful()) {
                Log::info('Rekaz reservation updated successfully', [
                    'rekaz_id' => $rekazReservationId,
                ]);

                return [
                    'success' => true,
                    'data' => $response->json('data'),
                    'message' => 'Reservation updated successfully',
                ];
            }

            Log::error('Rekaz reservation update failed', [
                'rekaz_id' => $rekazReservationId,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return [
                'success' => false,
                'error' => $response->json('message', 'Failed to update reservation'),
                'status_code' => $response->status(),
            ];

        } catch (RequestException $e) {
            Log::error('Rekaz API update exception', [
                'rekaz_id' => $rekazReservationId,
                'message' => $e->getMessage(),
            ]);

            throw new \Exception('Failed to update reservation in Rekaz: ' . $e->getMessage());
        }
    }

    /**
     * حذف حجز من ركاز
     * 
     * @param string $rekazReservationId
     * @return array
     * @throws \Exception
     */
    public function deleteReservation(string $rekazReservationId): array
    {
        try {
            $response = $this->client()
                ->delete("{$this->baseUrl}/reservations/{$rekazReservationId}");

            if ($response->successful()) {
                Log::info('Rekaz reservation deleted successfully', [
                    'rekaz_id' => $rekazReservationId,
                ]);

                return [
                    'success' => true,
                    'message' => 'Reservation deleted successfully',
                ];
            }

            Log::error('Rekaz reservation deletion failed', [
                'rekaz_id' => $rekazReservationId,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return [
                'success' => false,
                'error' => $response->json('message', 'Failed to delete reservation'),
                'status_code' => $response->status(),
            ];

        } catch (RequestException $e) {
            Log::error('Rekaz API delete exception', [
                'rekaz_id' => $rekazReservationId,
                'message' => $e->getMessage(),
            ]);

            throw new \Exception('Failed to delete reservation in Rekaz: ' . $e->getMessage());
        }
    }

    /**
     * إلغاء حجز في ركاز
     * 
     * @param string $rekazReservationId
     * @param string|null $reason
     * @return array
     * @throws \Exception
     */
    public function cancelReservation(string $rekazReservationId, ?string $reason = null): array
    {
        try {
            $payload = array_filter([
                'status' => 'cancelled',
                'cancel_reason' => $reason,
            ]);

            // جرب endpoint مخصص للإلغاء، وإلا استخدم update عادي
            $response = $this->client()
                ->patch("{$this->baseUrl}/reservations/{$rekazReservationId}/cancel", $payload);

            // إذا فشل endpoint الإلغاء، جرب update عادي
            if (!$response->successful() && $response->status() === 404) {
                $response = $this->client()
                    ->put("{$this->baseUrl}/reservations/{$rekazReservationId}", $payload);
            }

            if ($response->successful()) {
                Log::info('Rekaz reservation cancelled successfully', [
                    'rekaz_id' => $rekazReservationId,
                    'reason' => $reason,
                ]);

                return [
                    'success' => true,
                    'data' => $response->json('data'),
                    'message' => 'Reservation cancelled successfully',
                ];
            }

            Log::error('Rekaz reservation cancellation failed', [
                'rekaz_id' => $rekazReservationId,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return [
                'success' => false,
                'error' => $response->json('message', 'Failed to cancel reservation'),
                'status_code' => $response->status(),
            ];

        } catch (RequestException $e) {
            Log::error('Rekaz API cancel exception', [
                'rekaz_id' => $rekazReservationId,
                'message' => $e->getMessage(),
            ]);

            throw new \Exception('Failed to cancel reservation in Rekaz: ' . $e->getMessage());
        }
    }

    /**
     * جلب تفاصيل حجز من ركاز
     * 
     * @param string $rekazReservationId
     * @return array
     * @throws \Exception
     */
    public function getReservation(string $rekazReservationId): array
    {
        try {
            $response = $this->client()
                ->get("{$this->baseUrl}/reservations/{$rekazReservationId}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('data'),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('message', 'Failed to get reservation'),
                'status_code' => $response->status(),
            ];

        } catch (RequestException $e) {
            Log::error('Rekaz API get reservation exception', [
                'rekaz_id' => $rekazReservationId,
                'message' => $e->getMessage(),
            ]);

            throw new \Exception('Failed to get reservation from Rekaz: ' . $e->getMessage());
        }
    }

    /**
     * جلب جميع الحجوزات من ركاز
     * 
     * @param array $filters
     * @return array
     * @throws \Exception
     */
    public function getReservations(array $filters = []): array
    {
        try {
            $response = $this->client()
                ->get("{$this->baseUrl}/reservations", $filters);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('data', []),
                    'pagination' => $response->json('pagination'),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('message', 'Failed to get reservations'),
                'status_code' => $response->status(),
            ];

        } catch (RequestException $e) {
            Log::error('Rekaz API get reservations exception', [
                'message' => $e->getMessage(),
            ]);

            throw new \Exception('Failed to get reservations from Rekaz: ' . $e->getMessage());
        }
    }

    /**
     * تحويل بيانات الحجز من نظامنا إلى صيغة ركاز
     * 
     * @param \App\Models\Booking $booking
     * @return array
     */
    public function transformBookingData($booking): array
    {
        // تحميل العلاقات إذا لم تكن محملة
        $booking->loadMissing(['user', 'service', 'address', 'car', 'employee']);

        // استخراج اسم الموديل بشكل صحيح
        $carModel = null;
        if ($booking->car && $booking->car->model) {
            if (is_object($booking->car->model)) {
                // إذا كان object، استخرج name
                $carModel = $booking->car->model->name ?? null;
                // إذا كان name array (multilingual)
                if (is_array($carModel)) {
                    $carModel = $carModel['ar'] ?? $carModel['en'] ?? null;
                }
            } elseif (is_string($booking->car->model)) {
                $carModel = $booking->car->model;
            }
        }

        // تنسيق service name
        $serviceName = $booking->service->name ?? '';
        if (is_array($serviceName)) {
            $serviceName = $serviceName['ar'] ?? $serviceName['en'] ?? '';
        }

        return [
            // معرف خارجي (External ID) - ID الحجز في نظامنا
            'external_id' => (string) $booking->id,

            // بيانات العميل
            'customer' => [
                'name' => $booking->user->name ?? '',
                'email' => $booking->user->email ?? '',
                'phone' => $booking->user->phone ?? '',
            ],

            // بيانات الخدمة
            'service' => [
                'id' => (string) $booking->service_id,
                'name' => $serviceName,
                'duration' => (int) $booking->duration_minutes,
                'price' => (float) $booking->service_final_price_snapshot,
            ],

            // التاريخ والوقت - تنسيق صحيح
            'date' => $booking->booking_date instanceof \Carbon\Carbon
                ? $booking->booking_date->format('Y-m-d')
                : (is_string($booking->booking_date)
                    ? explode(' ', $booking->booking_date)[0]
                    : $booking->booking_date),

            'start_time' => is_string($booking->start_time)
                ? substr($booking->start_time, 0, 5) // HH:MM only (no seconds)
                : $booking->start_time,

            'end_time' => is_string($booking->end_time)
                ? substr($booking->end_time, 0, 5) // HH:MM only (no seconds)
                : $booking->end_time,

            // الحالة
            'status' => $this->mapStatusToRekaz($booking->status),

            // الموقع
            'location' => [
                'address' => $booking->address->address ?? '',
                'city' => $booking->address->city ?? '',
                'lat' => (float) ($booking->address->lat ?? 0),
                'lng' => (float) ($booking->address->lng ?? 0),
            ],

            // الموظف (إذا كان موجود)
            'employee_id' => $booking->employee_id ? (string) $booking->employee_id : null,

            // ملاحظات
            'notes' => $booking->meta['notes'] ?? $booking->meta['customer_notes'] ?? null,

            // بيانات إضافية (Metadata) - strings فقط!
            'metadata' => [
                'car_model' => $carModel, // String فقط
                'car_plate' => $booking->car->plate_number ?? null,
                'car_color' => $booking->car->color ?? null,
                'zone_id' => $booking->zone_id ? (string) $booking->zone_id : null,
                'time_period' => $booking->time_period ?? null,
                'total_amount' => (float) $booking->total_snapshot,
                'currency' => $booking->currency ?? 'SAR',
                'payment_status' => $booking->meta['payment_status'] ?? 'pending',
            ],
        ];
    }

    /**
     * تحويل status من نظامنا إلى ركاز
     * 
     * @param string $status
     * @return string
     */
    public function mapStatusToRekaz(string $status): string
    {
        return match ($status) {
            'pending' => 'pending',
            'confirmed' => 'confirmed',
            'moving' => 'in_progress',
            'arrived' => 'in_progress',
            'completed' => 'completed',
            'cancelled' => 'cancelled',
            default => 'pending',
        };
    }

    /**
     * تحديث status من ركاز إلى نظامنا
     * 
     * @param string $rekazStatus
     * @return string
     */
    public function mapStatusFromRekaz(string $rekazStatus): string
    {
        return match ($rekazStatus) {
            'pending' => 'pending',
            'confirmed' => 'confirmed',
            'in_progress' => 'moving',
            'completed' => 'completed',
            'cancelled' => 'cancelled',
            default => 'pending',
        };
    }

    /**
     * التحقق من اتصال API
     * 
     * @return bool
     */
    public function testConnection(): bool
    {
        try {
            // جرب جلب الحجوزات مع limit=1 كـ health check
            $response = $this->client()
                ->get("{$this->baseUrl}/reservations", ['limit' => 1]);

            if ($response->successful()) {
                Log::info('Rekaz API connection test successful', [
                    'status' => $response->status(),
                ]);
                return true;
            }

            Log::warning('Rekaz API connection test failed', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Rekaz API connection test exception', [
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * للتوافق مع الكود القديم - Alias methods
     */
    public function createBooking(array $bookingData): array
    {
        return $this->createReservation($bookingData);
    }

    public function updateBooking(string $bookingId, array $updateData): array
    {
        return $this->updateReservation($bookingId, $updateData);
    }

    public function deleteBooking(string $bookingId): array
    {
        return $this->deleteReservation($bookingId);
    }

    public function cancelBooking(string $bookingId, ?string $reason = null): array
    {
        return $this->cancelReservation($bookingId, $reason);
    }

    public function getBooking(string $bookingId): array
    {
        return $this->getReservation($bookingId);
    }

    // ------------------ Mapping ------------------

    /**
     * جلب جميع الخدمات/المنتجات من ركاز
     * 
     * @param array $filters
     * @return array
     * @throws \Exception
     */
    public function getProducts(array $filters = []): array
    {
        try {
            $response = $this->client()
                ->get("{$this->baseUrl}/products", $filters);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('items', []),
                    'total_count' => $response->json('totalCount', 0),
                ];
            }

            Log::error('Failed to fetch products from Rekaz', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return [
                'success' => false,
                'error' => $response->json('message', 'Failed to fetch products'),
                'status_code' => $response->status(),
            ];

        } catch (RequestException $e) {
            Log::error('Rekaz API get products exception', [
                'message' => $e->getMessage(),
            ]);

            throw new \Exception('Failed to get products from Rekaz: ' . $e->getMessage());
        }
    }

    /**
     * تحويل بيانات منتج ركاز إلى خدمة محلية
     * 
     * @param array $rekazProduct
     * @return array
     */
    public function transformRekazProductToService(array $rekazProduct): array
    {
        // استخراج أول pricing (الأساسي)
        $pricing = $rekazProduct['pricing'][0] ?? null;

        return [
            'rekaz_id' => $pricing['id'] ?? null, // هذا المهم - ID الخدمة في ركاز
            'name' => [
                'ar' => $rekazProduct['nameAr'] ?? $rekazProduct['name'],
                'en' => $rekazProduct['nameEn'] ?? $rekazProduct['name'],
            ],
            'description' => [
                'ar' => strip_tags($rekazProduct['description'] ?? ''),
                'en' => strip_tags($rekazProduct['description'] ?? ''),
            ],
            'duration_minutes' => (int) ($rekazProduct['duration'] ?? 0),
            'price' => (float) ($rekazProduct['amount'] ?? 0),
            'discounted_price' => isset($rekazProduct['discountedAmount'])
                ? (float) $rekazProduct['discountedAmount']
                : null,
            'is_active' => (bool) ($rekazProduct['showInCheckout'] ?? true),
            'type' => $rekazProduct['typeString'] ?? 'Reservation',
        ];
    }

    // Get Products
    public function transformRekazProductToProduct(array $rekazProduct): array
    {
        // استخراج أول pricing (الأساسي)
        $pricing = $rekazProduct['pricing'][0] ?? null;

        return [
            'rekaz_id' => $pricing['id'] ?? null, // هذا المهم - ID المنتج في ركاز
            'name' => [
                'ar' => $rekazProduct['nameAr'] ?? $rekazProduct['name'],
                'en' => $rekazProduct['nameEn'] ?? $rekazProduct['name'],
            ],
            'description' => [
                'ar' => strip_tags($rekazProduct['description'] ?? ''),
                'en' => strip_tags($rekazProduct['description'] ?? ''),
            ],
            'price' => (float) ($rekazProduct['amount'] ?? 0),
            'discounted_price' => isset($rekazProduct['discountedAmount'])
                ? (float) $rekazProduct['discountedAmount']
                : null,
            'is_active' => (bool) ($rekazProduct['showInCheckout'] ?? true),
            'max_qty_per_booking' => (int) ($rekazProduct['maximumQuantityPerOrder'] ?? null),
            'type' => $rekazProduct['typeString'] ?? 'Merchandise',
        ];
    }

    // Get Packages
    /**
     * تحويل بيانات باقة ركاز (Subscription) إلى باقة محلية
     * 
     * @param array $rekazPackage
     * @return array
     */
    public function transformRekazPackageToPackage(array $rekazPackage): array
    {
        // استخراج أول pricing (الأساسي)
        $pricing = $rekazPackage['pricing'][0] ?? null;

        // استخراج عدد الغسلات من package
        $washesCount = $pricing['package']['totalQuantity'] ?? 1;

        // استخراج مدة الصلاحية من billingPeriod (بالأيام)
        $validityDays = isset($pricing['billingPeriod'])
            ? (int) $pricing['billingPeriod']
            : 365; // افتراضي سنة إذا ما في تاريخ انتهاء

        return [
            'rekaz_id' => $pricing['id'] ?? null, // هذا المهم - ID الباقة في ركاز
            'name' => [
                'ar' => $rekazPackage['nameAr'] ?? $rekazPackage['name'],
                'en' => $rekazPackage['nameEn'] ?? $rekazPackage['name'],
            ],
            'label' => [
                'ar' => $rekazPackage['shortDescription'] ?? null,
                'en' => $rekazPackage['shortDescription'] ?? null,
            ],
            'description' => [
                'ar' => strip_tags($rekazPackage['description'] ?? ''),
                'en' => strip_tags($rekazPackage['description'] ?? ''),
            ],
            'price' => (float) ($pricing['amount'] ?? 0),
            'discounted_price' => isset($pricing['discountedAmount'])
                ? (float) $pricing['discountedAmount']
                : null,
            'validity_days' => $validityDays,
            'washes_count' => $washesCount,
            'is_active' => (bool) ($rekazPackage['showInCheckout'] ?? true),
            'type' => $rekazPackage['typeString'] ?? 'Subscription',
            'has_package' => (bool) ($rekazPackage['hasPackage'] ?? false),
        ];
    }

    // Get Bikers
    /**
     * جلب جميع مقدمي الخدمة (Providers) من ركاز
     * 
     * @param array $filters
     * @return array
     * @throws \Exception
     */
    public function getProviders(array $filters = []): array
    {
        try {
            $response = $this->client()
                ->get("{$this->baseUrl}/providers", $filters);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('items', []),
                ];
            }

            Log::error('Failed to fetch providers from Rekaz', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return [
                'success' => false,
                'error' => $response->json('message', 'Failed to fetch providers'),
                'status_code' => $response->status(),
            ];

        } catch (RequestException $e) {
            Log::error('Rekaz API get providers exception', [
                'message' => $e->getMessage(),
            ]);

            throw new \Exception('Failed to get providers from Rekaz: ' . $e->getMessage());
        }
    }

    /**
     * تحويل بيانات مقدم خدمة ركاز إلى موظف/بايكر محلي
     * 
     * @param array $rekazProvider
     * @return array
     */
    public function transformRekazProviderToEmployee(array $rekazProvider): array
    {
        // استخراج الاسم وتنظيفه
        $name = $rekazProvider['name'] ?? 'Unknown';

        // توليد mobile فريد بناءً على الـ ID (لأن ركاز ما يرجع mobile)
        // نستخدم آخر 9 أرقام من الـ ID ونضيف 05 قدامهم
        $idNumbers = preg_replace('/[^0-9]/', '', $rekazProvider['id']);
        $mobile = '05' . substr($idNumbers, -8); // 05 + 8 أرقام = 10 أرقام

        return [
            'rekaz_id' => $rekazProvider['id'],
            'name' => $name,
            'mobile' => $mobile,
            'email' => null, // ركاز ما يرجع email
            'user_type' => 'biker',
            'is_active' => true,
        ];
    }

    // Get Branches

    /**
     * جلب جميع الفروع من ركاز
     * 
     * @param array $filters
     * @return array
     * @throws \Exception
     */
    public function getBranches(array $filters = []): array
    {
        try {
            $response = $this->client()
                ->get("{$this->baseUrl}/branches", $filters);

            if ($response->successful()) {
                // ركاز يرجع array مباشرة مش object فيه items
                $branches = $response->json();

                return [
                    'success' => true,
                    'data' => is_array($branches) ? $branches : [],
                ];
            }

            Log::error('Failed to fetch branches from Rekaz', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return [
                'success' => false,
                'error' => $response->json('message', 'Failed to fetch branches'),
                'status_code' => $response->status(),
            ];

        } catch (RequestException $e) {
            Log::error('Rekaz API get branches exception', [
                'message' => $e->getMessage(),
            ]);

            throw new \Exception('Failed to get branches from Rekaz: ' . $e->getMessage());
        }
    }

    /**
     * تحويل بيانات فرع ركاز إلى فرع محلي
     * 
     * @param array $rekazBranch
     * @return array
     */
    public function transformRekazBranchToBranch(array $rekazBranch): array
    {
        return [
            'rekaz_id' => $rekazBranch['id'],
            'name' => [
                'ar' => $rekazBranch['nameAr'] ?? $rekazBranch['name'],
                'en' => $rekazBranch['nameEn'] ?? $rekazBranch['name'],
            ],
            'address_url' => $rekazBranch['addressUrl'] ?? null,
        ];
    }

    /**
     * تحويل بيانات Booking إلى صيغة ركاز
     * 
     * @param \App\Models\Booking $booking
     * @return array
     */
    /**
     * تحويل بيانات Booking إلى صيغة ركاز
     */
    /**
     * تحويل بيانات Booking إلى صيغة ركاز
     */
    public function transformBookingToRekazPayload($booking): array
    {
        $booking->loadMissing(['user', 'service', 'address', 'car', 'employee']);

        // 1. تحقق من وجود Customer مسبقاً في ركاز
        $userMapping = $booking->user->rekazMapping;
        $customerId = null;
        $customerDetails = null;

        if ($userMapping && $userMapping->rekaz_id) {
            $customerId = $userMapping->rekaz_id;
            Log::info('Using existing Rekaz customer', [
                'user_id' => $booking->user_id,
                'rekaz_customer_id' => $customerId,
            ]);
        } else {
            $cleanMobile = preg_replace('/^(\+|00)/', '', $booking->user->mobile);
            $customerDetails = [
                'name' => $booking->user->name,
                'mobileNumber' => '+' . $cleanMobile,
                'email' => $booking->user->email ?? '',
                'type' => 1,
                'companyName' => '',
            ];
            Log::info('Creating new Rekaz customer via customerDetails', [
                'user_id' => $booking->user_id,
                'mobile' => $customerDetails['mobileNumber'],
            ]);
        }

        // 2. الحصول على service rekaz_id (priceId)
        $serviceMapping = $booking->service->rekazMapping;
        if (!$serviceMapping || !$serviceMapping->rekaz_id) {
            throw new \Exception("Service #{$booking->service_id} is not synced with Rekaz");
        }
        $priceId = $serviceMapping->rekaz_id;

        // 3. الحصول على branch_id
        $branchId = $this->determineBranchId($booking);

        // 4. ✅ تحويل التواريخ إلى UTC ISO 8601
        // تنظيف booking_date
        $bookingDate = $booking->booking_date;
        if ($bookingDate instanceof \Carbon\Carbon) {
            $bookingDate = $bookingDate->format('Y-m-d');
        } elseif (is_string($bookingDate)) {
            $bookingDate = substr($bookingDate, 0, 10);
        }

        // تنظيف الأوقات (إزالة الثواني)
        $startTime = is_string($booking->start_time) ? substr($booking->start_time, 0, 5) : $booking->start_time;
        $endTime = is_string($booking->end_time) ? substr($booking->end_time, 0, 5) : $booking->end_time;

        // إنشاء Carbon instances في التوقيت المحلي (Saudi Arabia)
        $bookingDateTime = \Carbon\Carbon::createFromFormat(
            'Y-m-d H:i',
            $bookingDate . ' ' . $startTime,
            'Asia/Riyadh'
        );

        $endDateTime = \Carbon\Carbon::createFromFormat(
            'Y-m-d H:i',
            $bookingDate . ' ' . $endTime,
            'Asia/Riyadh'
        );

        // تحويل إلى UTC وتنسيق ISO 8601 مع Z
        $from = $bookingDateTime->utc()->format('Y-m-d\TH:i:s\Z');
        $to = $endDateTime->utc()->format('Y-m-d\TH:i:s\Z');

        // 5. الحصول على provider IDs (employees)
        $providerIds = [];
        if ($booking->employee_id) {
            $employeeMapping = $booking->employee->rekazMapping ?? null;
            if ($employeeMapping && $employeeMapping->rekaz_id) {
                $providerIds[] = $employeeMapping->rekaz_id;
            }
        }

        // 6. بناء الـ payload
        $payload = [
            'branchId' => $branchId,
            'items' => [
                [
                    'quantity' => 1,
                    'priceId' => $priceId,
                    'from' => $from,
                    'to' => $to,
                    'providerIds' => $providerIds,
                    'customFields' => (object) [],  // ✅ تأكد من هذا السطر
                    'discount' => [
                        'type' => 'percentage',
                        'value' => 0,
                    ],
                ],
            ],
            'input' => (object) [],  // ✅ وهذا السطر
        ];

        // استخدم customerId إذا موجود، وإلا customerDetails
        if ($customerId) {
            $payload['customerId'] = $customerId;
        } else {
            $payload['customerDetails'] = $customerDetails;
        }

        return $payload;
    }

    /**
     * تحديد branch_id للحجز
     */
    protected function determineBranchId($booking): string
    {
        // محاولة 1: من الـ zone
        if ($booking->zone_id) {
            $zone = \App\Models\Zone::find($booking->zone_id);
            if ($zone) {
                $zoneMapping = $zone->rekazMapping;
                if ($zoneMapping && $zoneMapping->rekaz_id) {
                    return $zoneMapping->rekaz_id;
                }
            }
        }

        // محاولة 2: من الـ address zone
        if ($booking->address && $booking->address->zone_id) {
            $zone = $booking->address->zone;
            if ($zone) {
                $zoneMapping = $zone->rekazMapping;
                if ($zoneMapping && $zoneMapping->rekaz_id) {
                    return $zoneMapping->rekaz_id;
                }
            }
        }

        // محاولة 3: أول branch متاح
        $branch = \App\Models\Branch::query()
            ->whereHas('rekazMapping')
            ->with('rekazMapping')
            ->first();

        if ($branch && $branch->rekazMapping) {
            return $branch->rekazMapping->rekaz_id;
        }

        throw new \Exception('No branch available for Rekaz sync');
    }

    /**
     * بناء custom fields للحجز
     */
    /**
     * بناء custom fields للحجز
     */
    protected function buildCustomFields($booking): object
    {
        $customFields = [];

        // يمكن إضافة معلومات إضافية هنا
        if ($booking->car) {
            // مثلاً: نوع السيارة، اللوحة، إلخ
            // حسب ما يدعمه ركاز
        }

        // ✅ إرجاع object فاضي بدل array
        return (object) $customFields;
    }

    /**
     * إنشاء حجز في ركاز بالصيغة الجديدة
     * 
     * @param array $payload
     * @return array
     * @throws \Exception
     */
    public function createRekazBooking(array $payload): array
    {
        try {
            $response = $this->client()
                ->post("{$this->baseUrl}/reservations/bulk", $payload);

            if ($response->successful()) {
                $data = $response->json();

                // Response structure من ركاز:
                // {
                //   "invoiceId": "...",
                //   "reservationIds": ["..."],
                //   "paymentLink": "..."
                // }

                $reservationId = $data['reservationIds'][0] ?? null;
                $invoiceId = $data['invoiceId'] ?? null;
                $paymentLink = $data['paymentLink'] ?? null;

                Log::info('Rekaz booking created successfully', [
                    'reservation_id' => $reservationId,
                    'invoice_id' => $invoiceId,
                    'customer_id' => $payload['customerId'] ?? 'new',
                    'payment_link' => $paymentLink,
                ]);

                return [
                    'success' => true,
                    'data' => [
                        'id' => $reservationId, // هذا المهم!
                        'reservationId' => $reservationId,
                        'invoiceId' => $invoiceId,
                        'paymentLink' => $paymentLink,
                        'customerId' => $this->extractCustomerIdFromResponse($data, $payload),
                    ],
                ];
            }

            Log::error('Rekaz booking creation failed', [
                'status' => $response->status(),
                'response' => $response->json(), // الـ full response
                'response_body' => $response->body(), // كامل الـ body
                'payload' => $payload,
            ]);

            return [
                'success' => false,
                'error' => $response->json('message', 'Failed to create booking'),
                'status_code' => $response->status(),
                'response' => $response->json(),
            ];

        } catch (RequestException $e) {
            Log::error('Rekaz API create booking exception', [
                'message' => $e->getMessage(),
                'payload' => $payload,
            ]);

            throw new \Exception('Failed to create booking in Rekaz: ' . $e->getMessage());
        }
    }

    /**
     * استخراج customerId من الـ response
     * (ركاز قد يرجعه في response لما ننشئ customer جديد)
     */
    protected function extractCustomerIdFromResponse(array $response, array $payload): ?string
    {
        // إذا استخدمنا customerId موجود، نرجعه
        if (isset($payload['customerId'])) {
            return $payload['customerId'];
        }

        // إذا ركاز رجع customerId في response
        if (isset($response['customerId'])) {
            return $response['customerId'];
        }

        // محاولة استخراجه من أماكن أخرى
        if (isset($response['customer']['id'])) {
            return $response['customer']['id'];
        }

        return null;
    }

}