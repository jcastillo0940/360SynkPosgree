<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnalyzedProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        // Magento
        'magento_sku',
        'magento_name',
        'magento_description',
        'has_image',
        'magento_category_id',
        'magento_category_name',
        
        // ICG
        'icg_barcode',
        'icg_articulo_id',
        'icg_description',
        'icg_webname',
        'icg_departamento',
        'icg_seccion',
        'icg_familia',
        'icg_marca',
        
        // Matching
        'match_status',
        'match_score',
        'match_method',
        'match_details',
        
        // Stock
        'has_stock_b03',
        'stock_b03',
        'last_purchase_b03',
        'has_stock_b12',
        'stock_b12',
        'last_purchase_b12',
        'total_stock',
        'last_purchase_date',
        'meets_stock_criteria',
        
        // Precios
        'icg_price',
        'icg_offer_price',
        'icg_offer_from',
        'icg_offer_to',
        
        // Metadata
        'analysis_execution_id',
        'analyzed_at',
        'priority',
        'status',
        'notes',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'has_image' => 'boolean',
        'has_stock_b03' => 'boolean',
        'has_stock_b12' => 'boolean',
        'meets_stock_criteria' => 'boolean',
        'last_purchase_b03' => 'date',
        'last_purchase_b12' => 'date',
        'last_purchase_date' => 'date',
        'icg_offer_from' => 'date',
        'icg_offer_to' => 'date',
        'analyzed_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'match_score' => 'decimal:2',
        'icg_price' => 'decimal:2',
        'icg_offer_price' => 'decimal:2',
        'match_details' => 'array',
    ];

    /**
     * Relación: Un producto analizado pertenece a una ejecución de análisis
     */
    public function analysisExecution()
    {
        return $this->belongsTo(ProductAnalysisExecution::class, 'analysis_execution_id');
    }

    /**
     * Relación: Un producto analizado puede ser revisado por un usuario
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scopes
     */
    public function scopeWithoutImages($query)
    {
        return $query->where('has_image', false);
    }

    public function scopeWithStock($query)
    {
        return $query->where('meets_stock_criteria', true);
    }

    public function scopeMatched($query)
    {
        return $query->whereIn('match_status', ['exact', 'approximate']);
    }

    public function scopeNotMatched($query)
    {
        return $query->where('match_status', 'not_found');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeReviewed($query)
    {
        return $query->where('status', 'reviewed');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['high', 'urgent']);
    }

    /**
     * Marcar como revisado
     */
    public function markAsReviewed($userId, $status = 'reviewed', $notes = null)
    {
        $this->update([
            'status' => $status,
            'reviewed_by' => $userId,
            'reviewed_at' => now(),
            'notes' => $notes ?? $this->notes,
        ]);
    }

    /**
     * Calcular score de matching (similitud de strings)
     */
    public static function calculateMatchScore($str1, $str2)
    {
        if (empty($str1) || empty($str2)) {
            return 0;
        }

        // Normalizar strings
        $str1 = self::normalizeString($str1);
        $str2 = self::normalizeString($str2);

        // Similitud de Levenshtein
        $levenshtein = levenshtein($str1, $str2);
        $maxLength = max(strlen($str1), strlen($str2));
        
        if ($maxLength == 0) {
            return 100;
        }

        $similarity = (1 - ($levenshtein / $maxLength)) * 100;

        // También usar similar_text para más precisión
        similar_text($str1, $str2, $percent);

        // Promedio de ambos métodos
        return round(($similarity + $percent) / 2, 2);
    }

    /**
     * Normalizar string para comparación
     */
    private static function normalizeString($str)
    {
        // Convertir a minúsculas
        $str = strtolower($str);
        
        // Remover acentos
        $str = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
        
        // Remover caracteres especiales excepto espacios
        $str = preg_replace('/[^a-z0-9\s]/', '', $str);
        
        // Remover espacios múltiples
        $str = preg_replace('/\s+/', ' ', $str);
        
        return trim($str);
    }

    /**
     * Verificar si cumple criterios de stock
     */
    public function checkStockCriteria($minMonths = 6)
    {
        $minDate = now()->subMonths($minMonths);
        
        $meetsB03 = $this->has_stock_b03 && 
                    $this->stock_b03 > 0 && 
                    $this->last_purchase_b03 && 
                    $this->last_purchase_b03->gte($minDate);
                    
        $meetsB12 = $this->has_stock_b12 && 
                    $this->stock_b12 > 0 && 
                    $this->last_purchase_b12 && 
                    $this->last_purchase_b12->gte($minDate);

        $this->meets_stock_criteria = $meetsB03 || $meetsB12;
        $this->save();

        return $this->meets_stock_criteria;
    }

    /**
     * Obtener label de estado
     */
    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'pending' => 'Pendiente',
            'reviewed' => 'Revisado',
            'approved' => 'Aprobado',
            'rejected' => 'Rechazado',
            default => $this->status,
        };
    }

    /**
     * Obtener color de estado
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'pending' => 'yellow',
            'reviewed' => 'blue',
            'approved' => 'green',
            'rejected' => 'red',
            default => 'gray',
        };
    }

    /**
     * Obtener label de prioridad
     */
    public function getPriorityLabelAttribute()
    {
        return match($this->priority) {
            'low' => 'Baja',
            'medium' => 'Media',
            'high' => 'Alta',
            'urgent' => 'Urgente',
            default => $this->priority,
        };
    }

    /**
     * Obtener color de prioridad
     */
    public function getPriorityColorAttribute()
    {
        return match($this->priority) {
            'low' => 'gray',
            'medium' => 'blue',
            'high' => 'orange',
            'urgent' => 'red',
            default => 'gray',
        };
    }

    /**
     * Obtener label de match status
     */
    public function getMatchStatusLabelAttribute()
    {
        return match($this->match_status) {
            'not_found' => 'No encontrado',
            'exact' => 'Exacto',
            'approximate' => 'Aproximado',
            'manual' => 'Manual',
            default => $this->match_status,
        };
    }

    /**
     * Obtener color de match status
     */
    public function getMatchStatusColorAttribute()
    {
        return match($this->match_status) {
            'not_found' => 'red',
            'exact' => 'green',
            'approximate' => 'yellow',
            'manual' => 'blue',
            default => 'gray',
        };
    }
}
