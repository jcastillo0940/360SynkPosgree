<?php

namespace App\Services\API;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\SyncConfiguration;

class MagentoApiService
{
    protected $baseUrl;
    protected $token;
    protected $storeId;
    protected $timeout = 60;

    public function __construct()
    {
        $config = SyncConfiguration::getMagentoApiConfig();
        
        $this->baseUrl = rtrim($config['base_url'], '/');
        $this->token = $config['api_token'];
        $this->storeId = $config['store_id'];
    }

    public function createProduct($productData)
    {
        try {
            Log::info('Creating Magento product', [
                'sku' => $productData['sku'] ?? 'unknown',
                'data' => $productData
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->post($this->baseUrl . '/rest/V1/products', [
                'product' => $productData
            ]);

            Log::info('Magento create response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if (!$response->successful()) {
                throw new \Exception("Magento API error: " . $response->body());
            }

            return [
                'success' => true,
                'data' => $response->json(),
                'sku' => $productData['sku'] ?? null
            ];

        } catch (\Exception $e) {
            Log::error('Magento API Error - Create Product', [
                'sku' => $productData['sku'] ?? 'unknown',
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'sku' => $productData['sku'] ?? null
            ];
        }
    }

    public function updateProduct($sku, $productData)
    {
        try {
            $updateData = $productData;
            unset($updateData['sku']);
            unset($updateData['type_id']);
            unset($updateData['attribute_set_id']);
            
            Log::info('Updating Magento product', [
                'sku' => $sku,
                'data' => $updateData
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->put($this->baseUrl . "/rest/V1/products/{$sku}", [
                'product' => $updateData
            ]);

            Log::info('Magento update response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if (!$response->successful()) {
                throw new \Exception("Magento API error: " . $response->body());
            }

            return [
                'success' => true,
                'data' => $response->json(),
                'sku' => $sku
            ];

        } catch (\Exception $e) {
            Log::error('Magento API Error - Update Product', [
                'sku' => $sku,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'sku' => $sku
            ];
        }
    }

    public function getProduct($sku)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])
            ->timeout($this->timeout)
            ->get($this->baseUrl . "/rest/V1/products/{$sku}");

            if ($response->status() === 404) {
                return [
                    'success' => false,
                    'error' => 'Product not found',
                    'exists' => false,
                    'data' => null
                ];
            }

            if (!$response->successful()) {
                throw new \Exception("Magento API error: " . $response->body());
            }

            return [
                'success' => true,
                'exists' => true,
                'data' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error('Magento API Error - Get Product', [
                'sku' => $sku,
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'exists' => false,
                'data' => null
            ];
        }
    }

    public function updatePrice($sku, $price, $specialPrice = null, $specialFromDate = null, $specialToDate = null)
    {
        $priceData = [
            'price' => $price
        ];

        // ⭐ Si hay precio especial, agregar con fechas
        if ($specialPrice !== null && $specialPrice > 0) {
            $priceData['custom_attributes'] = [
                [
                    'attribute_code' => 'special_price',
                    'value' => $specialPrice
                ]
            ];
            
            // Agregar fechas si existen
            if ($specialFromDate) {
                $priceData['custom_attributes'][] = [
                    'attribute_code' => 'special_from_date',
                    'value' => $specialFromDate
                ];
            }
            
            if ($specialToDate) {
                $priceData['custom_attributes'][] = [
                    'attribute_code' => 'special_to_date',
                    'value' => $specialToDate
                ];
            }
        } else {
            // ⭐ Si NO hay oferta activa, LIMPIAR campos de oferta
            $priceData['custom_attributes'] = [
                [
                    'attribute_code' => 'special_price',
                    'value' => null
                ],
                [
                    'attribute_code' => 'special_from_date',
                    'value' => null
                ],
                [
                    'attribute_code' => 'special_to_date',
                    'value' => null
                ]
            ];
        }

        return $this->updateProduct($sku, $priceData);
    }

    public function updateStock($sku, $quantity, $isInStock = null, $manageStock = true)
    {
        try {
            $stockData = [
                'qty' => $quantity,
                'is_in_stock' => $isInStock ?? ($quantity > 0),
                'manage_stock' => $manageStock
            ];

            Log::info('Updating Magento stock', [
                'sku' => $sku,
                'qty' => $quantity,
                'is_in_stock' => $stockData['is_in_stock'],
                'manage_stock' => $manageStock
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->put($this->baseUrl . "/rest/V1/products/{$sku}/stockItems/{$this->storeId}", [
                'stockItem' => $stockData
            ]);

            if (!$response->successful()) {
                throw new \Exception("Magento API error: " . $response->body());
            }

            return [
                'success' => true,
                'data' => $response->json(),
                'sku' => $sku
            ];

        } catch (\Exception $e) {
            Log::error('Magento API Error - Update Stock', [
                'sku' => $sku,
                'quantity' => $quantity,
                'manage_stock' => $manageStock,
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'sku' => $sku
            ];
        }
    }

    public function updateStockBySources($sku, array $stockBySource)
    {
        try {
            $sourceItems = [];
            
            foreach ($stockBySource as $sourceCode => $stockData) {
                $sourceItems[] = [
                    'sku' => $sku,
                    'source_code' => (string) $sourceCode,
                    'quantity' => $stockData['qty'],
                    'status' => $stockData['status']
                ];
            }

            Log::info('Updating Magento stock by sources (batch)', [
                'sku' => $sku,
                'sources_count' => count($sourceItems)
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->post($this->baseUrl . "/rest/V1/inventory/source-items", [
                'sourceItems' => $sourceItems
            ]);

            if (!$response->successful()) {
                Log::error('Magento MSI Batch Error', [
                    'sku' => $sku,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                
                return [
                    'success' => false,
                    'error' => "HTTP {$response->status()}: {$response->body()}",
                    'sku' => $sku
                ];
            }

            return [
                'success' => true,
                'data' => $response->json(),
                'sku' => $sku,
                'sources_updated' => count($sourceItems)
            ];

        } catch (\Exception $e) {
            Log::error('Magento API Error - Update Stock by Sources', [
                'sku' => $sku,
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'sku' => $sku
            ];
        }
    }

    public function productExists($sku)
    {
        $result = $this->getProduct($sku);
        return $result['exists'] ?? false;
    }

    public function getCategories()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])
            ->timeout($this->timeout)
            ->get($this->baseUrl . '/rest/V1/categories');

            if (!$response->successful()) {
                throw new \Exception("Magento API error: " . $response->body());
            }

            return [
                'success' => true,
                'data' => $this->flattenCategories($response->json())
            ];

        } catch (\Exception $e) {
            Log::error('Magento API Error - Get Categories', [
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    protected function flattenCategories($category, &$flat = [])
    {
        if (isset($category['id'])) {
            $flat[] = [
                'id' => $category['id'],
                'parent_id' => $category['parent_id'] ?? null,
                'name' => $category['name'] ?? '',
                'level' => $category['level'] ?? 0,
                'path' => $category['path'] ?? '',
                'is_active' => $category['is_active'] ?? false,
                'position' => $category['position'] ?? 0,
                'product_count' => $category['product_count'] ?? 0,
            ];
        }

        if (isset($category['children_data']) && is_array($category['children_data'])) {
            foreach ($category['children_data'] as $child) {
                $this->flattenCategories($child, $flat);
            }
        }

        return $flat;
    }

    public function getAllCategories()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])
            ->timeout($this->timeout)
            ->get($this->baseUrl . '/rest/V1/categories/list', [
                'searchCriteria[pageSize]' => 1000
            ]);

            if (!$response->successful()) {
                throw new \Exception("Magento API error: " . $response->body());
            }

            $data = $response->json();

            return [
                'success' => true,
                'data' => $data['items'] ?? [],
                'total' => $data['total_count'] ?? 0
            ];

        } catch (\Exception $e) {
            Log::error('Magento API Error - Get All Categories', [
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    public function getCategoryById($categoryId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])
            ->timeout($this->timeout)
            ->get($this->baseUrl . "/rest/V1/categories/{$categoryId}");

            if ($response->status() === 404) {
                return [
                    'success' => false,
                    'error' => 'Category not found',
                    'exists' => false,
                    'data' => null
                ];
            }

            if (!$response->successful()) {
                throw new \Exception("Magento API error: " . $response->body());
            }

            return [
                'success' => true,
                'exists' => true,
                'data' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error('Magento API Error - Get Category', [
                'category_id' => $categoryId,
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'exists' => false,
                'data' => null
            ];
        }
    }

    public function getProductsByCategory($categoryId, $pageSize = 100, $currentPage = 1)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])
            ->timeout($this->timeout)
            ->get($this->baseUrl . '/rest/V1/products', [
                'searchCriteria[filterGroups][0][filters][0][field]' => 'category_id',
                'searchCriteria[filterGroups][0][filters][0][value]' => $categoryId,
                'searchCriteria[filterGroups][0][filters][0][condition_type]' => 'eq',
                'searchCriteria[pageSize]' => $pageSize,
                'searchCriteria[currentPage]' => $currentPage
            ]);

            if (!$response->successful()) {
                throw new \Exception("Magento API error: " . $response->body());
            }

            $data = $response->json();

            return [
                'success' => true,
                'data' => $data['items'] ?? [],
                'total' => $data['total_count'] ?? 0
            ];

        } catch (\Exception $e) {
            Log::error('Magento API Error - Get Products By Category', [
                'category_id' => $categoryId,
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    public function getCategoryCustomAttribute($category, $attributeCode)
    {
        if (!isset($category['custom_attributes']) || !is_array($category['custom_attributes'])) {
            return null;
        }

        foreach ($category['custom_attributes'] as $attribute) {
            if (isset($attribute['attribute_code']) && $attribute['attribute_code'] === $attributeCode) {
                return $attribute['value'] ?? null;
            }
        }

        return null;
    }

    public function testConnection()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])
            ->timeout(10)
            ->get($this->baseUrl . '/rest/V1/store/storeConfigs');

            if (!$response->successful()) {
                throw new \Exception("Magento API error: HTTP {$response->status()}");
            }

            $stores = $response->json();

            return [
                'success' => true,
                'message' => 'Connection successful',
                'stores_count' => count($stores),
                'stores' => $stores
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection error: ' . $e->getMessage()
            ];
        }
    }

    public function batchUpdate($updates)
    {
        $results = [
            'success' => [],
            'failed' => [],
        ];

        foreach ($updates as $update) {
            $sku = $update['sku'];
            $result = $this->updateProduct($sku, $update['data']);

            if ($result['success']) {
                $results['success'][] = $sku;
            } else {
                $results['failed'][] = [
                    'sku' => $sku,
                    'error' => $result['error']
                ];
            }
        }

        return $results;
    }
	
	/**
 * Obtener productos sin imágenes
 */
public function getProductsWithoutImages($page = 1, $pageSize = 100)
{
    try {
        // Primero obtener todos los productos de la página
        $allProducts = $this->getProducts($page, $pageSize);
        
        if (empty($allProducts['items'])) {
            return [
                'items' => [],
                'total_count' => 0,
                'page' => $page,
                'page_size' => $pageSize,
            ];
        }
        
        // Filtrar productos sin imágenes
        $productsWithoutImages = [];
        foreach ($allProducts['items'] as $product) {
            if ($this->productHasNoImages($product)) {
                $productsWithoutImages[] = $product;
            }
        }

        Log::info('Magento Products Analysis', [
            'page' => $page,
            'total_fetched' => count($allProducts['items']),
            'without_images' => count($productsWithoutImages),
        ]);

        return [
            'items' => $productsWithoutImages,
            'total_count' => count($productsWithoutImages),
            'page' => $page,
            'page_size' => $pageSize,
            'total_products_in_page' => count($allProducts['items']),
        ];
        
    } catch (\Exception $e) {
        Log::error('Magento API Exception - Get Products Without Images', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return [
            'items' => [],
            'total_count' => 0,
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }
}

	/**
	 * Verificar si un producto NO tiene imágenes
	 */

	private function productHasNoImages($product)
	{
		// 1. Verificar media_gallery_entries
		if (isset($product['media_gallery_entries']) && !empty($product['media_gallery_entries'])) {
			foreach ($product['media_gallery_entries'] as $media) {
				// Si tiene imagen activa, tiene imágenes
				if (($media['media_type'] ?? '') === 'image' && !($media['disabled'] ?? false)) {
					return false; // TIENE imagen
				}
			}
		}

		// 2. Verificar custom_attributes para image, small_image, thumbnail
		if (isset($product['custom_attributes'])) {
			foreach ($product['custom_attributes'] as $attr) {
				$code = $attr['attribute_code'] ?? '';
				$value = $attr['value'] ?? '';

				if (in_array($code, ['image', 'small_image', 'thumbnail'])) {
					// Si tiene un valor válido (no vacío y no 'no_selection'), tiene imagen
					if (!empty($value) && $value !== 'no_selection' && !str_starts_with($value, 'no_selection')) {
						return false; // TIENE imagen
					}
				}
			}
		}

		return true; // NO tiene imágenes
	}
	
	/**
 * Obtener productos con paginación
 */
public function getProducts($page = 1, $pageSize = 100)
{
    try {
        $searchCriteria = [
            'searchCriteria' => [
                'filterGroups' => [],
                'pageSize' => $pageSize,
                'currentPage' => $page
            ]
        ];

        $response = Http::withToken($this->token)
            ->timeout(120)
            ->get("{$this->baseUrl}/rest/V1/products", $searchCriteria);

        if ($response->successful()) {
            $data = $response->json();
            
            return [
                'items' => $data['items'] ?? [],
                'total_count' => $data['total_count'] ?? 0,
                'page' => $page,
                'page_size' => $pageSize,
            ];
        }

        Log::error('Magento API Error - Get Products', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        return [
            'items' => [],
            'total_count' => 0,
            'page' => $page,
            'page_size' => $pageSize,
        ];
    } catch (\Exception $e) {
        Log::error('Magento API Exception - Get Products', [
            'message' => $e->getMessage()
        ]);
        
        return [
            'items' => [],
            'total_count' => 0,
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }
}
	/**
 * Obtener producto por SKU (retorna solo el producto, no el wrapper)
 */
public function getProductBySku($sku)
{
    $result = $this->getProduct($sku);
    
    if ($result['success'] && $result['exists']) {
        return $result['data'];
    }
    
    return null;
}
	
	
	/**
 * Obtener órdenes de Magento con filtros
 */
public function getOrders($filters = [])
{
    try {
        $params = [];
        
        if (isset($filters['created_at_from'])) {
            $params['searchCriteria[filterGroups][0][filters][0][field]'] = 'created_at';
            $params['searchCriteria[filterGroups][0][filters][0][value]'] = $filters['created_at_from'];
            $params['searchCriteria[filterGroups][0][filters][0][condition_type]'] = 'gteq';
        }
        
        if (isset($filters['created_at_to'])) {
            $params['searchCriteria[filterGroups][1][filters][0][field]'] = 'created_at';
            $params['searchCriteria[filterGroups][1][filters][0][value]'] = $filters['created_at_to'];
            $params['searchCriteria[filterGroups][1][filters][0][condition_type]'] = 'lteq';
        }
        
        $params['searchCriteria[filterGroups][2][filters][0][field]'] = 'status';
        $params['searchCriteria[filterGroups][2][filters][0][value]'] = 'canceled';
        $params['searchCriteria[filterGroups][2][filters][0][condition_type]'] = 'neq';
        
        $params['searchCriteria[pageSize]'] = $filters['page_size'] ?? 100;
        $params['searchCriteria[currentPage]'] = $filters['page'] ?? 1;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Content-Type' => 'application/json',
        ])
        ->timeout(120)
        ->get($this->baseUrl . '/rest/V1/orders', $params);

        if ($response->successful()) {
            return $response->json();
        }

        return ['items' => []];

    } catch (\Exception $e) {
        Log::error('Magento API Error - Get Orders', ['error' => $e->getMessage()]);
        return ['items' => []];
    }
}
}