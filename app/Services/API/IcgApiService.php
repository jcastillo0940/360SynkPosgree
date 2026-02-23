<?php

namespace App\Services\API;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Configuration;
use Carbon\Carbon;

class IcgApiService
{
    protected $baseUrl;
    protected $username;
    protected $password;
    protected $timeout = 30;

    public function __construct()
    {
        $this->baseUrl = config('services.icg.url', env('ICG_API_URL'));
        $this->username = config('services.icg.user', env('ICG_API_USER'));
        $this->password = config('services.icg.password', env('ICG_API_PASSWORD'));
    }

    public function getProducts($page = 1, $perPage = 100, $filters = [])
    {
        try {
            $params = [
                'pagina' => $page,
                'registrosPorPagina' => $perPage,
            ];

            if (isset($filters['fecha_desde'])) {
                $params['fecha_desde'] = $filters['fecha_desde'];
            }
            if (isset($filters['fecha_hasta'])) {
                $params['fecha_hasta'] = $filters['fecha_hasta'];
            }

            Log::info('ICG API Request', [
                'url' => $this->baseUrl,
                'page' => $page,
                'perPage' => $perPage,
                'params' => $params
            ]);

            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout($this->timeout)
                ->get($this->baseUrl, $params);

            Log::info('ICG API Response', [
                'status' => $response->status(),
                'body_preview' => substr($response->body(), 0, 500)
            ]);

            if (!$response->successful()) {
                throw new \Exception("ICG API error: HTTP {$response->status()} - {$response->body()}");
            }

            $data = $response->json();

            if (!isset($data['success']) || !$data['success']) {
                throw new \Exception($data['error'] ?? 'API returned success=false');
            }

            $products = $data['products'] ?? [];

            // Mapear productos al formato del workflow
            $mappedProducts = [];
            foreach ($products as $apiProduct) {
                // Filtrar productos inválidos (ArticuloId = -1)
                if (isset($apiProduct['ArticuloId']) && $apiProduct['ArticuloId'] == -1) {
                    continue;
                }

                $mappedProducts[] = $this->mapApiProductToWorkflowFormat($apiProduct);
            }

            // La API de ICG NO devuelve totalRegistros/totalPaginas
            // Así que asumimos que si devuelve menos productos que el límite, es la última página
            $isLastPage = count($products) < $perPage;

            return [
                'success' => true,
                'data' => $mappedProducts,
                'total_registros' => count($mappedProducts),
                'total_paginas' => $isLastPage ? $page : $page + 1,
                'pagina_actual' => $page,
                'is_last_page' => $isLastPage,
            ];

        } catch (\Exception $e) {
            Log::error('ICG API Error', [
                'message' => $e->getMessage(),
                'page' => $page,
                'filters' => $filters
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => [],
                'total_registros' => 0,
                'total_paginas' => 0,
            ];
        }
    }

    /**
     * Obtener producto por SKU/Barcode
     */
    public function getProductBySku($sku)
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout($this->timeout)
                ->get($this->baseUrl, [
                    'barcode' => $sku
                ]);

            if (!$response->successful()) {
                throw new \Exception("ICG API error: HTTP {$response->status()}");
            }

            $data = $response->json();

            if (!isset($data['success']) || !$data['success']) {
                return [
                    'success' => false,
                    'error' => $data['error'] ?? 'Product not found',
                    'data' => null
                ];
            }

            $products = $data['products'] ?? [];

            if (empty($products)) {
                return [
                    'success' => false,
                    'error' => 'Product not found',
                    'data' => null
                ];
            }

            $product = $this->mapApiProductToWorkflowFormat($products[0]);

            return [
                'success' => true,
                'data' => $product
            ];

        } catch (\Exception $e) {
            Log::error('ICG API Error - Get by SKU', [
                'sku' => $sku,
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Mapear producto de la API al formato que espera el workflow
     */
    protected function mapApiProductToWorkflowFormat($apiProduct)
    {
        $sku = $apiProduct['Referencia'] ?? null;

        $prices = $apiProduct['Precios'] ?? [];
        $price = 0;
        $specialPrice = null;
        $specialFromDate = null;
        $specialToDate = null;

        // Buscar el precio de la tarifa 12
        foreach ($prices as $priceData) {
            if ($priceData['TarifaId'] == 12) {
                $price = $priceData['Neto'] ?? 0;
                $ofertaPrecio = $priceData['OfertaPrecio'] ?? 0;

                // ⭐ Solo procesar oferta si el precio es mayor a 0
                if ($ofertaPrecio > 0) {
                    $ofertaDesde = $priceData['OfertaDesde'] ?? null;
                    $ofertaHasta = $priceData['OfertaHasta'] ?? null;

                    // Verificar que las fechas no sean placeholder "1899-12-30"
                    if (
                        $ofertaDesde && !str_starts_with($ofertaDesde, '1899')
                        && $ofertaHasta && !str_starts_with($ofertaHasta, '1899')
                    ) {

                        try {
                            $desde = Carbon::parse($ofertaDesde);
                            $hasta = Carbon::parse($ofertaHasta)->endOfDay();
                            $now = Carbon::now();

                            // ⭐ VALIDAR: La oferta debe estar activa HOY
                            if ($now->between($desde, $hasta)) {
                                $specialPrice = $ofertaPrecio;
                                $specialFromDate = $desde->format('Y-m-d H:i:s');
                                $specialToDate = $hasta->format('Y-m-d H:i:s');
                            }
                            // Si no está activa, dejar en null (se limpiará en Magento)

                        } catch (\Exception $e) {
                            // Fecha inválida, ignorar oferta
                        }
                    }
                }
                break;
            }
        }

        $totalStock = 0;
        foreach ($apiProduct['Stocks'] ?? [] as $stock) {
            $totalStock += $stock['Disponible'] ?? 0;
        }

        $camposLibres = $apiProduct['Camposlibres'][0] ?? [];

        return [
            'ARTCOD' => $sku,
            'ARTDES' => $apiProduct['Descripcion'] ?? '',
            'ARTOBSERV' => $apiProduct['Descripcion'] ?? '',
            'PVPTARIF' => $price,
            'PVPOFER' => $specialPrice,
            'PVPOFER_DESDE' => $specialFromDate,
            'PVPOFER_HASTA' => $specialToDate,
            'EXISTEN' => $totalStock,
            'PESO' => 0,
            'ARTEAN' => $apiProduct['TallasColores'][0]['CodigoBarras1'] ?? '',
            'Familia' => $apiProduct['Familia'] ?? '',
            'Subfamilia' => $apiProduct['SubFamilia'] ?? '',
            'WEBVISB' => $camposLibres['WEBVISB'] ?? 'F',
            'APPLIEDSTOCK' => $camposLibres['APPLIEDSTOCK'] ?? 'T',
            'MARCA' => $apiProduct['Marca'] ?? '',
            'Departamento' => $apiProduct['Departamento'] ?? '',
            'Seccion' => $apiProduct['Seccion'] ?? '',
            'NIVEL1' => $camposLibres['NIVEL1'] ?? null,
            'NIVEL2' => $camposLibres['NIVEL2'] ?? null,
            'NIVEL3' => $camposLibres['NIVEL3'] ?? null,
            'NIVEL4' => $camposLibres['NIVEL4'] ?? null,
            'Stocks' => $apiProduct['Stocks'] ?? [],
        ];
    }

    /**
     * Obtener múltiples productos por array de SKUs
     */
    public function getProductsBySkus(array $skus)
    {
        $products = [];
        $errors = [];

        foreach ($skus as $sku) {
            $result = $this->getProductBySku($sku);

            if ($result['success']) {
                $products[] = $result['data'];
            } else {
                $errors[$sku] = $result['error'];
            }
        }

        return [
            'success' => count($errors) === 0,
            'data' => $products,
            'errors' => $errors,
            'total_requested' => count($skus),
            'total_found' => count($products),
        ];
    }

    /**
     * Test de conexión
     */
    public function testConnection()
    {
        try {
            $result = $this->getProducts(1, 1);

            return [
                'success' => $result['success'],
                'message' => $result['success']
                    ? 'Connection successful'
                    : 'Connection failed: ' . ($result['error'] ?? 'Unknown error'),
                'response_data' => $result
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener estadísticas del catálogo
     */
    public function getCatalogStats()
    {
        try {
            $result = $this->getProducts(1, 1);

            if (!$result['success']) {
                throw new \Exception($result['error'] ?? 'Error fetching catalog');
            }

            return [
                'success' => true,
                'total_products' => $result['total_registros'],
                'total_pages' => $result['total_paginas'],
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Busca un artículo por su ID interno (ArticuloId).
     */
    public function getProductById($id)
    {
        try {
            // Asumimos que el parámetro para buscar por ID es 'id' o 'ArticuloId'.
            // Probamos enviar ambos por si acaso.
            $params = [
                'id' => $id,
                'ArticuloId' => $id
            ];

            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout(5) // Timeout corto para ser ágiles
                ->get($this->baseUrl, $params);

            if (!$response->successful()) {
                return ['success' => false, 'error' => 'HTTP ' . $response->status()];
            }

            $data = $response->json();
            $products = $data['products'] ?? [];

            if (empty($products)) {
                return ['success' => false, 'error' => 'Not found'];
            }

            // Devolver el primer producto encontrado
            return [
                'success' => true,
                'data' => $products[0]
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}