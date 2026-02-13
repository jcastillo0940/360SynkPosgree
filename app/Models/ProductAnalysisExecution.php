<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductAnalysisExecution extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'job_id',
        'user_id',
        'analysis_type',
        'filters',
        'configuration_snapshot',
        'status',
        'started_at',
        'completed_at',
        'duration_seconds',
        'total_magento_products',
        'products_without_images',
        'products_matched',
        'products_with_stock',
        'products_meeting_criteria',
        'errors_count',
        'result_message',
        'error_details',
        'export_filename',
        'export_path',
    ];

    protected $casts = [
        'filters' => 'array',
        'configuration_snapshot' => 'array',
        'error_details' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Relación: Una ejecución pertenece a un usuario
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación: Una ejecución tiene muchos productos analizados
     */
    public function analyzedProducts()
    {
        return $this->hasMany(AnalyzedProduct::class, 'analysis_execution_id');
    }

    /**
     * Relación: Una ejecución tiene muchos logs
     */
    public function logs()
    {
        return $this->hasMany(ProductAnalysisLog::class, 'analysis_execution_id')
                    ->orderBy('logged_at', 'asc');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    public function scopeCompleted($query)
    {
        return $query->whereIn('status', ['completed', 'completed_with_errors']);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Marcar como iniciada
     */
    public function markAsStarted()
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    /**
     * Marcar como completada
     */
    public function markAsCompleted($status = 'completed', $message = null)
    {
        $duration = $this->started_at ? now()->diffInSeconds($this->started_at) : 0;

        $this->update([
            'status' => $status,
            'completed_at' => now(),
            'duration_seconds' => $duration,
            'result_message' => $message,
        ]);
    }

    /**
     * Marcar como fallida
     */
    public function markAsFailed($errorMessage, $errorDetails = null)
    {
        $duration = $this->started_at ? now()->diffInSeconds($this->started_at) : 0;

        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'duration_seconds' => $duration,
            'result_message' => $errorMessage,
            'error_details' => $errorDetails,
        ]);
    }

    /**
     * Agregar log a la ejecución
     */
    public function addLog($level, $message, $context = null, $sku = null, $progress = null)
    {
        return $this->logs()->create([
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'sku' => $sku,
            'progress_percentage' => $progress,
            'logged_at' => now(),
        ]);
    }

    /**
     * Obtener duración formateada
     */
    public function getFormattedDurationAttribute()
    {
        if (!$this->duration_seconds) {
            return 'N/A';
        }

        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;

        if ($minutes > 0) {
            return "{$minutes}m {$seconds}s";
        }

        return "{$seconds}s";
    }

    /**
     * Verificar si está en progreso
     */
    public function isRunning()
    {
        return $this->status === 'running';
    }

    /**
     * Verificar si fue exitosa
     */
    public function isSuccessful()
    {
        return in_array($this->status, ['completed', 'completed_with_errors']);
    }

    /**
     * Verificar si falló
     */
    public function hasFailed()
    {
        return $this->status === 'failed';
    }

    /**
     * Obtener label de tipo de análisis
     */
    public function getAnalysisTypeLabelAttribute()
    {
        return match($this->analysis_type) {
            'no_images' => 'Productos sin imágenes',
            'stock_analysis' => 'Análisis de stock',
            'full_analysis' => 'Análisis completo',
            default => $this->analysis_type,
        };
    }

    /**
     * Obtener color de estado
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'pending' => 'gray',
            'running' => 'blue',
            'completed' => 'green',
            'completed_with_errors' => 'yellow',
            'failed' => 'red',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Obtener label de estado
     */
    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'pending' => 'Pendiente',
            'running' => 'Ejecutando',
            'completed' => 'Completado',
            'completed_with_errors' => 'Completado con errores',
            'failed' => 'Fallido',
            'cancelled' => 'Cancelado',
            default => $this->status,
        };
    }

    /**
     * Obtener tasa de éxito de matching
     */
    public function getMatchRateAttribute()
    {
        if ($this->products_without_images === 0) {
            return 0;
        }

        return round(($this->products_matched / $this->products_without_images) * 100, 2);
    }

    /**
     * Obtener tasa de productos con stock válido
     */
    public function getStockRateAttribute()
    {
        if ($this->products_matched === 0) {
            return 0;
        }

        return round(($this->products_with_stock / $this->products_matched) * 100, 2);
    }
}
