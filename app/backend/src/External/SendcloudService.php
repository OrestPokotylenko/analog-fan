<?php

namespace App\External;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Exception;

class SendcloudService {
    private string $publicKey;
    private string $secretKey;
    private bool $isTestMode;
    private Client $client;
    private string $baseUrl = 'https://panel.sendcloud.sc/api/v2/';

    public function __construct() {
        $config = require __DIR__ . '/../../config/env.php';
        $this->publicKey = $config['sendcloud']['public_key'] ?? '';
        $this->secretKey = $config['sendcloud']['secret_key'] ?? '';
        $this->isTestMode = $config['sendcloud']['test_mode'] ?? true;
        
        if (empty($this->publicKey) || empty($this->secretKey)) {
            throw new Exception('Sendcloud API keys not configured');
        }
        
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'auth' => [$this->publicKey, $this->secretKey],
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'timeout' => 30,
        ]);
    }

    /**
     * Get shipping rates for an address
     */
    public function getShippingRates(array $toAddress, array $parcel = []): array {
        try {
            $country = $this->normalizeCountryCode($toAddress['country'] ?? 'NL');
            $isDomestic = in_array($country, ['NL', 'BE']);
            
            // Get available shipping methods
            $response = $this->client->get('shipping_methods', [
                'query' => [
                    'sender_address' => 1, // Default sender
                    'to_country' => $country,
                ]
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            $methods = $data['shipping_methods'] ?? [];
            
            // Filter to PostNL methods for Dutch addresses
            $rates = [];
            foreach ($methods as $method) {
                if ($isDomestic && stripos($method['carrier'], 'PostNL') !== false) {
                    $rates[] = [
                        'rate_id' => 'sendcloud_' . $method['id'],
                        'carrier' => $method['carrier'],
                        'carrier_code' => $method['carrier'],
                        'service' => $method['name'],
                        'shipping_method_id' => $method['id'],
                        'amount' => number_format($method['price'] ?? 6.95, 2),
                        'currency' => 'EUR',
                        'estimated_days' => $method['service_point_input'] === 'required' ? 2 : 1,
                    ];
                }
            }
            
            // Fallback if no methods found
            if (empty($rates)) {
                $rates[] = [
                    'rate_id' => 'sendcloud_standard',
                    'carrier' => 'PostNL',
                    'carrier_code' => 'postnl',
                    'service' => 'Standard delivery',
                    'shipping_method_id' => 8, // PostNL standard
                    'amount' => '6.95',
                    'currency' => 'EUR',
                    'estimated_days' => 1,
                ];
            }
            
            return [
                'rates' => $rates,
                'to_address' => array_merge($toAddress, ['country' => $country]),
            ];
        } catch (Exception $e) {
            error_log("Sendcloud: Failed to get rates - " . $e->getMessage());
            
            // Return default rates on error
            return [
                'rates' => [
                    [
                        'rate_id' => 'sendcloud_standard',
                        'carrier' => 'PostNL',
                        'carrier_code' => 'postnl',
                        'service' => 'Standard delivery',
                        'shipping_method_id' => 8,
                        'amount' => '6.95',
                        'currency' => 'EUR',
                        'estimated_days' => 1,
                    ]
                ],
                'to_address' => $toAddress,
            ];
        }
    }

    /**
     * Create shipping label
     */
    public function createLabel(array $toAddress, string $rateId, array $options = []): array {
        try {
            error_log("Sendcloud: Creating label for rate: {$rateId}");
            
            $country = $this->normalizeCountryCode($toAddress['country'] ?? 'NL');
            
            // Extract shipping method ID from rate_id
            $shippingMethodId = $this->extractShippingMethodId($rateId);
            
            // Prepare parcel data
            $parcelData = [
                'parcel' => [
                    'name' => $toAddress['name'] ?? 'Customer',
                    'company_name' => $toAddress['company'] ?? '',
                    'address' => $toAddress['street1'] ?? '',
                    'house_number' => $this->extractHouseNumber($toAddress['street1'] ?? ''),
                    'city' => $toAddress['city'] ?? '',
                    'postal_code' => $toAddress['zip'] ?? '',
                    'country' => $country,
                    'email' => $toAddress['email'] ?? '',
                    'telephone' => $toAddress['phone'] ?? '',
                    'request_label' => true,
                    'shipment' => [
                        'id' => $shippingMethodId,
                    ],
                    'order_number' => $options['order_number'] ?? uniqid(),
                    'insured_value' => 0,
                    'total_order_value' => $options['total_value'] ?? 0,
                    'weight' => '1.000', // 1kg default
                ]
            ];
            
            error_log("Sendcloud: Parcel data: " . json_encode($parcelData));
            
            // Create parcel
            $response = $this->client->post('parcels', [
                'json' => $parcelData
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            $parcel = $result['parcel'] ?? null;
            
            if (!$parcel) {
                throw new Exception('Failed to create parcel - no parcel data returned');
            }
            
            error_log("Sendcloud: Parcel created. ID: " . $parcel['id']);
            
            // Get label URL
            $labelUrl = $parcel['label']['normal_printer'][0] ?? '';
            $trackingNumber = $parcel['tracking_number'] ?? '';
            $trackingUrl = $parcel['tracking_url'] ?? '';
            
            error_log("Sendcloud: Label URL: " . ($labelUrl ?: 'NULL'));
            error_log("Sendcloud: Tracking: " . ($trackingNumber ?: 'NULL'));
            
            return [
                'transaction_id' => (string)$parcel['id'],
                'tracking_number' => $trackingNumber,
                'tracking_url_provider' => $trackingUrl,
                'label_url' => $labelUrl,
                'carrier' => $parcel['carrier']['code'] ?? 'PostNL',
                'service' => $parcel['shipment']['name'] ?? 'Standard delivery',
                'status' => $parcel['status']['message'] ?? 'PENDING',
                'eta' => null,
            ];
            
        } catch (RequestException $e) {
            error_log("Sendcloud: API Exception - " . $e->getMessage());
            if ($e->hasResponse()) {
                $body = $e->getResponse()->getBody()->getContents();
                error_log("Sendcloud: Response body - " . $body);
            }
            throw new Exception('Failed to create Sendcloud label: ' . $e->getMessage());
        } catch (Exception $e) {
            error_log("Sendcloud: Exception - " . $e->getMessage());
            throw new Exception('Failed to create Sendcloud label: ' . $e->getMessage());
        }
    }

    /**
     * Get tracking information
     */
    public function getTrackingInfo(string $trackingNumber, string $postalCode = ''): array {
        try {
            // Search for parcel by tracking number
            $response = $this->client->get('parcels', [
                'query' => ['tracking_number' => $trackingNumber]
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            $parcels = $data['parcels'] ?? [];
            
            if (empty($parcels)) {
                return [
                    'tracking_number' => $trackingNumber,
                    'carrier' => 'PostNL',
                    'status' => 'UNKNOWN',
                    'tracking_url' => '',
                    'tracking_history' => [],
                ];
            }
            
            $parcel = $parcels[0];
            
            return [
                'tracking_number' => $trackingNumber,
                'carrier' => $parcel['carrier']['code'] ?? 'PostNL',
                'status' => $parcel['status']['message'] ?? 'UNKNOWN',
                'tracking_url' => $parcel['tracking_url'] ?? '',
                'tracking_history' => $parcel['status_history'] ?? [],
            ];
        } catch (Exception $e) {
            error_log("Sendcloud: Failed to get tracking - " . $e->getMessage());
            return [
                'tracking_number' => $trackingNumber,
                'carrier' => 'PostNL',
                'status' => 'ERROR',
                'tracking_url' => '',
                'tracking_history' => [],
            ];
        }
    }

    /**
     * Download label PDF
     */
    public function downloadLabel(string $labelUrl): string {
        try {
            $response = $this->client->get($labelUrl);
            return $response->getBody()->getContents();
        } catch (Exception $e) {
            error_log("Sendcloud: Failed to download label - " . $e->getMessage());
            throw new Exception('Failed to download label: ' . $e->getMessage());
        }
    }

    /**
     * Get available carriers
     */
    public function getCarriers(): array {
        return [
            [
                'name' => 'PostNL',
                'code' => 'postnl',
                'services' => ['Standard', 'Same Day', 'Saturday Delivery', 'Evening Delivery'],
            ],
            [
                'name' => 'DHL',
                'code' => 'dhl',
                'services' => ['Standard', 'Express'],
            ],
            [
                'name' => 'DPD',
                'code' => 'dpd',
                'services' => ['Standard', 'Express'],
            ],
        ];
    }

    /**
     * Normalize country code
     */
    private function normalizeCountryCode(string $country): string {
        $countryMap = [
            'nederland' => 'NL',
            'netherlands' => 'NL',
            'belgium' => 'BE',
            'belgië' => 'BE',
            'belgie' => 'BE',
            'germany' => 'DE',
            'duitsland' => 'DE',
        ];
        
        return $countryMap[strtolower($country)] ?? strtoupper(substr($country, 0, 2));
    }

    /**
     * Extract house number from street address
     */
    private function extractHouseNumber(string $street): string {
        if (preg_match('/\d+[a-zA-Z]?$/', $street, $matches)) {
            return $matches[0];
        }
        return '1';
    }

    /**
     * Extract shipping method ID from rate_id
     */
    private function extractShippingMethodId(string $rateId): int {
        if (preg_match('/sendcloud_(\d+)/', $rateId, $matches)) {
            return (int)$matches[1];
        }
        return 8; // Default to PostNL standard
    }
}
