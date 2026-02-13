<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductAnalysisLog extends Model
{
    use HasFactory;

    // Esta tabla no usa timestamps (solo logged_at)
    public $timestamps = false;

    protected $fillable = [
        'analysis_execution_id',
        'level',
        'message',
        'context',
        'sku',
        'current_step',
        'total_steps',
        'progress_percentage',
        'logged_at',
    ];

    protected $casts = [
        'context' => 'array',
        'logged_at' => 'datetime',
        'progress_percentage' => 'decimal:2',
    ];

    /**
     * Relación: Un log pertenece a una ejecución de análisis
     */
    public function analysisExecution()
    {
        return $this->belongsTo(ProductAnalysisExecution::class, 'analysis_execution_id');
    }

    /**
     * Scopes
     */
    public function scopeLevel($query, $level)
    {
        return $query->where('level', strtoupper($level));
    }

    public function scopeErrors($query)
    {
        return $query->whereIn('level', ['ERROR', 'CRITICAL']);
    }

    public function scopeInfo($query)
    {
        return $query->where('level', 'INFO');
    }

    public function scopeSuccess($query)
    {
        return $query->where('level', 'SUCCESS');
    }

    public function scopeWarnings($query)
    {
        return $query->where('level', 'WARNING');
    }

    /**
     * Obtener color del badge según el nivel
     */
    public function getBadgeColorAttribute()
    {
        return match($this->level) {
            'DEBUG' => 'gray',
            'INFO' => 'blue',
            'SUCCESS' => 'green',
            'WARNING' => 'yellow',
            'ERROR' => 'red',
            'CRITICAL' => 'red',
            default => 'gray',
        };
    }

    /**
     * Obtener icono según el nivel
     */
    public function getIconAttribute()
    {
        return match($this->level) {
            'DEBUG' => 'bug',
            'INFO' => 'information-circle',
            'SUCCESS' => 'check-circle',
            'WARNING' => 'exclamation-triangle',
            'ERROR' => 'x-circle',
            'CRITICAL' => 'shield-exclamation',
            default => 'information-circle',
        };
    }

    /**
     * Formatear el mensaje para mostrar en UI
     */
    public function getFormattedMessageAttribute()
    {
        $message = $this->message;

        // Si tiene SKU, agregarlo al inicio
        if ($this->sku) {
            $message = "[SKU: {$this->sku}] {$message}";
        }

        // Si tiene progreso de paso, agregarlo
        if ($this->current_step && $this->total_steps) {
            $message .= " (Paso {$this->current_step}/{$this->total_steps})";
        }

        // Si tiene porcentaje de progreso, agregarlo
        if ($this->progress_percentage !== null) {
            $message .= " - {$this->progress_percentage}%";
        }

        return $message;
    }

    /**
     * Obtener timestamp formateado
     */
    public function getFormattedTimeAttribute()
    {
        return $this->logged_at->format('H:i:s');
    }
}
