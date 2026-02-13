<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AnalysisConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'category',
        'label',
        'value',
        'type',
        'description',
        'is_required',
        'is_visible',
        'display_order',
        'validation_rules',
        'default_value',
        'updated_by',
        'last_tested_at',
        'test_passed',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_visible' => 'boolean',
        'test_passed' => 'boolean',
        'last_tested_at' => 'datetime',
        'validation_rules' => 'array',
    ];

    /**
     * Relación: Una configuración puede ser actualizada por un usuario
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope: Filtrar por categoría
     */
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Solo configuraciones visibles
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    /**
     * Scope: Solo configuraciones requeridas
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Scope: Ordenar por display_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('label');
    }

    /**
     * Obtener valor parseado según el tipo
     */
    public function getParsedValueAttribute()
    {
        return match($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->value,
            'float', 'decimal' => (float) $this->value,
            'json' => json_decode($this->value, true),
            'array' => is_array($this->value) ? $this->value : json_decode($this->value, true),
            'date' => $this->value ? \Carbon\Carbon::parse($this->value) : null,
            default => $this->value,
        };
    }

    /**
     * Obtener todas las configuraciones como array [key => value]
     */
    public static function getAllAsArray()
    {
        $configs = self::all();
        $result = [];

        foreach ($configs as $config) {
            $result[$config->key] = $config->parsed_value;
        }

        return $result;
    }

    /**
     * Obtener configuraciones por categoría
     */
    public static function getByCategory($category)
    {
        return self::where('category', $category)
                   ->visible()
                   ->ordered()
                   ->get();
    }

    /**
     * Obtener valor de configuración por key
     */
    public static function get($key, $default = null)
    {
        $config = self::where('key', $key)->first();

        if (!$config) {
            return $default;
        }

        return $config->parsed_value ?? $default;
    }

    /**
     * Establecer valor de configuración por key
     */
    public static function set($key, $value, $userId = null)
    {
        $config = self::where('key', $key)->first();

        if (!$config) {
            return false;
        }

        $config->update(['value' => $value]);
        
        if ($userId) {
            $config->update(['updated_by' => $userId]);
        }

        return true;
    }

    /**
     * Verificar si todas las configuraciones requeridas están completas
     */
    public static function areRequiredConfigsComplete()
    {
        $requiredConfigs = self::required()->get();

        foreach ($requiredConfigs as $config) {
            $value = $config->parsed_value;
            
            if (empty($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Obtener configuraciones faltantes
     */
    public static function getMissingRequiredConfigs()
    {
        return self::required()->get()->filter(function ($config) {
            return empty($config->parsed_value);
        });
    }

    /**
     * Marcar como probada
     */
    public function markAsTested($passed = true)
    {
        $this->update([
            'test_passed' => $passed,
            'last_tested_at' => now(),
        ]);
    }

    /**
     * Obtener badge de tipo
     */
    public function getTypeBadgeAttribute()
    {
        return match($this->type) {
            'integer' => ['icon' => 'hashtag', 'color' => 'indigo'],
            'boolean' => ['icon' => 'switch-horizontal', 'color' => 'green'],
            'date' => ['icon' => 'calendar', 'color' => 'purple'],
            'json' => ['icon' => 'code', 'color' => 'yellow'],
            default => ['icon' => 'document-text', 'color' => 'gray'],
        };
    }

    /**
     * Obtener valor para mostrar en UI
     */
    public function getDisplayValueAttribute()
    {
        $value = $this->parsed_value ?? $this->default_value;
        
        if ($this->type === 'boolean') {
            return $value ? 'Sí' : 'No';
        }
        
        if ($this->type === 'date' && $value) {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        }

        return $value ?? 'No configurado';
    }
}
