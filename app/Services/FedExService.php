<?php
// app/Services/FedExService.php — FedEx REST API Integration Service
namespace App\Services;

class FedExService
{
    private static $tokenCache = null;
    private static $tokenExpiry = 0;

    /**
     * Get FedEx REST API base URL based on environment
     */
    public static function getBaseUrl(): string
    {
        $env = strtolower(env('FEDEX_ENVIRONMENT', 'sandbox'));
        return ($env === 'production' || $env === 'live')
            ? 'https://apis.fedex.com'
            : 'https://apis-sandbox.fedex.com';
    }

    /**
     * Check if FedEx credentials are fully configured
     */
    public static function isConfigured(): bool
    {
        $apiKey = env('FEDEX_API_KEY', '');
        $secret = env('FEDEX_SECRET_KEY', '');
        $account = env('FEDEX_ACCOUNT_NUMBER', '');
        return !empty($apiKey) && !empty($secret) && !empty($account);
    }

    /**
     * Obtain OAuth 2.0 Bearer Access Token from FedEx
     */
    public static function getAccessToken(): ?string
    {
        if (self::$tokenCache && time() < (self::$tokenExpiry - 60)) {
            return self::$tokenCache;
        }

        $apiKey = env('FEDEX_API_KEY', '');
        $secretKey = env('FEDEX_SECRET_KEY', '');

        if (empty($apiKey) || empty($secretKey)) {
            return null;
        }

        $url = self::getBaseUrl() . '/oauth/token';
        $postFields = http_build_query([
            'grant_type'    => 'client_credentials',
            'client_id'     => $apiKey,
            'client_secret' => $secretKey,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postFields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/x-www-form-urlencoded'
            ],
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_ENCODING       => '',
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr || $httpCode !== 200) {
            error_log("FedEx OAuth Error (HTTP $httpCode): " . ($curlErr ?: $response));
            return null;
        }

        $data = json_decode($response, true);
        if (!empty($data['access_token'])) {
            self::$tokenCache = $data['access_token'];
            self::$tokenExpiry = time() + (int)($data['expires_in'] ?? 3600);
            return self::$tokenCache;
        }

        return null;
    }

    /**
     * Calculate total package weight in lbs based on cart items
     */
    public static function calculatePackageWeight(array $cart): float
    {
        $totalWeight = 0.0;

        foreach ($cart as $item) {
            $qty = max(1, (int)($item['qty'] ?? 1));
            $name = strtolower($item['name'] ?? '');
            $sku = strtoupper($item['sku'] ?? '');
            $itemWeight = 1.8; // default item weight in lbs

            // Check vessel sizes by name or SKU
            if (strpos($name, '18 oz') !== false || strpos($name, 'vessel e') !== false || (isset($sku[0]) && $sku[0] === 'E')) {
                $itemWeight = 3.0;
            } elseif (strpos($name, '14 oz') !== false || strpos($name, 'vessel d') !== false || (isset($sku[0]) && $sku[0] === 'D')) {
                $itemWeight = 2.2;
            } elseif (strpos($name, '10 oz') !== false || strpos($name, 'vessel c') !== false || (isset($sku[0]) && $sku[0] === 'C')) {
                $itemWeight = 1.5;
            } elseif (strpos($name, 'trimmer') !== false || strpos($name, 'snuffer') !== false || strpos($name, 'wick') !== false) {
                $itemWeight = 0.5;
            } elseif (strpos($name, 'tray') !== false || strpos($name, 'match') !== false || strpos($name, 'bottle') !== false) {
                $itemWeight = 0.8;
            }

            // If item has a keepsake box
            if (!empty($item['box_name']) || (isset($item['box_id']) && (int)$item['box_id'] > 0)) {
                $itemWeight += 0.4;
            }

            $totalWeight += ($itemWeight * $qty);
        }

        // Add box packaging tare weight (0.5 lbs minimum)
        $totalWeight += 0.5;

        return max(1.0, round($totalWeight, 1));
    }

    /**
     * Get live or fallback FedEx shipping rates
     */
    public static function getRates(array $recipient, array $cart, float $subtotal = 0.0): array
    {
        // Compute subtotal if not passed
        if ($subtotal <= 0) {
            foreach ($cart as $item) {
                $subtotal += ((float)($item['price'] ?? 0)) * max(1, (int)($item['qty'] ?? 1));
            }
        }

        $freeShippingThreshold = 75.00;
        $qualifiesFreeShipping = ($subtotal >= $freeShippingThreshold && count($cart) > 0);
        $packageWeight = self::calculatePackageWeight($cart);

        // Standardize recipient address
        $zip     = trim($recipient['zip'] ?? '');
        $state   = strtoupper(trim($recipient['state'] ?? ''));
        $city    = trim($recipient['city'] ?? '');
        $country = strtoupper(trim($recipient['country'] ?? 'US'));
        if (empty($country)) { $country = 'US'; }

        // If credentials are configured, try calling FedEx REST API
        if (self::isConfigured() && !empty($zip)) {
            $liveRates = self::requestLiveRates($recipient, $packageWeight, $qualifiesFreeShipping);
            if (!empty($liveRates)) {
                return [
                    'source'                 => 'live',
                    'rates'                  => $liveRates,
                    'package_weight'         => $packageWeight,
                    'qualifies_free_shipping'=> $qualifiesFreeShipping,
                    'free_shipping_threshold'=> $freeShippingThreshold
                ];
            }
        }

        // Return calibrated fallback rates
        $fallbackRates = self::getFallbackRates($recipient, $packageWeight, $qualifiesFreeShipping);
        return [
            'source'                 => 'fallback',
            'rates'                  => $fallbackRates,
            'package_weight'         => $packageWeight,
            'qualifies_free_shipping'=> $qualifiesFreeShipping,
            'free_shipping_threshold'=> $freeShippingThreshold
        ];
    }

    /**
     * Request live rate quotes from FedEx REST API
     */
    private static function requestLiveRates(array $recipient, float $packageWeight, bool $qualifiesFreeShipping): ?array
    {
        $token = self::getAccessToken();
        if (!$token) {
            return null;
        }

        $account = env('FEDEX_ACCOUNT_NUMBER', '');
        $shipperStreet  = env('FEDEX_SHIPPER_STREET', '123 Ocean Ave');
        $shipperCity    = env('FEDEX_SHIPPER_CITY', 'Laguna Beach');
        $shipperState   = env('FEDEX_SHIPPER_STATE', 'CA');
        $shipperZip     = env('FEDEX_SHIPPER_ZIP', '92651');
        $shipperCountry = env('FEDEX_SHIPPER_COUNTRY', 'US');

        $recStreet  = trim($recipient['address'] ?? '');
        $recCity    = trim($recipient['city'] ?? '');
        $recState   = strtoupper(trim($recipient['state'] ?? ''));
        $recZip     = trim($recipient['zip'] ?? '');
        $recCountry = strtoupper(trim($recipient['country'] ?? 'US'));
        if (empty($recCountry)) { $recCountry = 'US'; }

        $payload = [
            'accountNumber' => [
                'value' => (string)$account
            ],
            'requestedShipment' => [
                'shipper' => [
                    'address' => [
                        'streetLines'         => [$shipperStreet],
                        'city'                => $shipperCity,
                        'stateOrProvinceCode' => $shipperState,
                        'postalCode'          => $shipperZip,
                        'countryCode'         => $shipperCountry,
                    ]
                ],
                'recipient' => [
                    'address' => [
                        'streetLines'         => !empty($recStreet) ? [$recStreet] : ['100 Main St'],
                        'city'                => !empty($recCity) ? $recCity : 'City',
                        'stateOrProvinceCode' => !empty($recState) ? $recState : 'CA',
                        'postalCode'          => $recZip,
                        'countryCode'         => $recCountry,
                        'residential'         => true,
                    ]
                ],
                'pickupType' => 'USE_SCHEDULED_PICKUP',
                'rateRequestType' => ['ACCOUNT', 'LIST'],
                'requestedPackageLineItems' => [
                    [
                        'weight' => [
                            'units' => 'LB',
                            'value' => $packageWeight,
                        ]
                    ]
                ]
            ]
        ];

        $url = self::getBaseUrl() . '/rate/v1/rates/quotes';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
                'X-locale: en_US'
            ],
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_ENCODING       => '',
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr || $httpCode !== 200) {
            error_log("FedEx Rate Quote API Error (HTTP $httpCode): " . ($curlErr ?: $response));
            return null;
        }

        $data = json_decode($response, true);
        if (empty($data['output']['rateReplyDetails'])) {
            return null;
        }

        $rates = [];
        $allowedServices = [
            'FEDEX_GROUND'                 => ['name' => 'FedEx Home Delivery®', 'desc' => '3–5 business days · Reliable Ground Delivery', 'order' => 1, 'is_ground' => true],
            'GROUND_HOME_DELIVERY'         => ['name' => 'FedEx Home Delivery®', 'desc' => '3–5 business days · Reliable Ground Delivery', 'order' => 1, 'is_ground' => true],
            'FEDEX_EXPRESS_SAVER'          => ['name' => 'FedEx Express Saver®', 'desc' => '3 business days by 4:30 PM', 'order' => 2, 'is_ground' => false],
            'FEDEX_2_DAY'                  => ['name' => 'FedEx 2Day®', 'desc' => '2 business days by 4:30 PM', 'order' => 3, 'is_ground' => false],
            'FEDEX_2_DAY_AM'               => ['name' => 'FedEx 2Day® AM', 'desc' => '2 business days by 10:30 AM', 'order' => 4, 'is_ground' => false],
            'STANDARD_OVERNIGHT'           => ['name' => 'FedEx Standard Overnight®', 'desc' => 'Next business day by 4:30 PM', 'order' => 5, 'is_ground' => false],
            'PRIORITY_OVERNIGHT'           => ['name' => 'FedEx Priority Overnight®', 'desc' => 'Next business day by 10:30 AM', 'order' => 6, 'is_ground' => false],
        ];

        foreach ($data['output']['rateReplyDetails'] as $detail) {
            $serviceType = $detail['serviceType'] ?? '';
            if (!isset($allowedServices[$serviceType])) {
                continue;
            }

            // Extract best monetary charge
            $amount = 0.0;
            if (!empty($detail['ratedShipmentDetails'])) {
                foreach ($detail['ratedShipmentDetails'] as $ratedShipment) {
                    $totalNet = $ratedShipment['totalNetCharge'] ?? $ratedShipment['totalNetFedExCharge'] ?? 0;
                    if ($totalNet > 0) {
                        $amount = (float)$totalNet;
                        break;
                    }
                }
            }

            if ($amount <= 0) {
                continue;
            }

            $meta = $allowedServices[$serviceType];
            $isFree = ($meta['is_ground'] && $qualifiesFreeShipping);
            $effectiveAmount = $isFree ? 0.00 : $amount;

            $rates[$serviceType] = [
                'code'           => $serviceType,
                'name'           => $meta['name'],
                'description'    => $meta['desc'],
                'rate'           => $effectiveAmount,
                'original_rate'  => $amount,
                'formatted_rate' => $isFree ? 'FREE' : '$' . number_format($effectiveAmount, 2),
                'is_free'        => $isFree,
                'delivery_days'  => $meta['desc'],
                'sort_order'     => $meta['order'],
            ];
        }

        // Sort by priority order
        usort($rates, function($a, $b) {
            return $a['sort_order'] <=> $b['sort_order'];
        });

        return !empty($rates) ? array_values($rates) : null;
    }

    /**
     * Provide calibrated fallback rates based on destination and weight
     */
    public static function getFallbackRates(array $recipient, float $packageWeight, bool $qualifiesFreeShipping): array
    {
        $state = strtoupper(trim($recipient['state'] ?? 'CA'));
        
        // Base zone distance adjustment from CA
        $isWestCoast = in_array($state, ['CA', 'OR', 'WA', 'NV', 'AZ', 'UT', 'ID']);
        $isEastCoast = in_array($state, ['NY', 'NJ', 'FL', 'MA', 'PA', 'NC', 'SC', 'GA', 'VA', 'MD', 'CT', 'RI', 'ME', 'NH', 'VT']);

        $zoneMultiplier = $isWestCoast ? 1.0 : ($isEastCoast ? 1.25 : 1.15);
        $weightAddon = max(0, ($packageWeight - 2.0)) * 1.50;

        $groundBase = round((11.50 + $weightAddon) * $zoneMultiplier, 2);
        $twoDayBase = round((18.50 + ($weightAddon * 1.6)) * $zoneMultiplier, 2);
        $overnightBase = round((36.00 + ($weightAddon * 2.5)) * $zoneMultiplier, 2);

        $groundEffective = $qualifiesFreeShipping ? 0.00 : $groundBase;

        return [
            [
                'code'           => 'FEDEX_GROUND',
                'name'           => 'FedEx Home Delivery®',
                'description'    => $isWestCoast ? '2–3 business days · Reliable Ground Delivery' : '4–5 business days · Reliable Ground Delivery',
                'rate'           => $groundEffective,
                'original_rate'  => $groundBase,
                'formatted_rate' => $qualifiesFreeShipping ? 'FREE' : '$' . number_format($groundBase, 2),
                'is_free'        => $qualifiesFreeShipping,
                'delivery_days'  => $isWestCoast ? '2–3 business days' : '4–5 business days',
                'sort_order'     => 1,
            ],
            [
                'code'           => 'FEDEX_2_DAY',
                'name'           => 'FedEx 2Day®',
                'description'    => '2 business days by 4:30 PM',
                'rate'           => $twoDayBase,
                'original_rate'  => $twoDayBase,
                'formatted_rate' => '$' . number_format($twoDayBase, 2),
                'is_free'        => false,
                'delivery_days'  => '2 business days',
                'sort_order'     => 2,
            ],
            [
                'code'           => 'PRIORITY_OVERNIGHT',
                'name'           => 'FedEx Priority Overnight®',
                'description'    => 'Next business day by 10:30 AM',
                'rate'           => $overnightBase,
                'original_rate'  => $overnightBase,
                'formatted_rate' => '$' . number_format($overnightBase, 2),
                'is_free'        => false,
                'delivery_days'  => 'Next business day',
                'sort_order'     => 3,
            ]
        ];
    }
}
