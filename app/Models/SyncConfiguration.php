<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SyncConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'category',
        'label',
        'value',
        'encrypted_value',
        'is_encrypted',
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
        'is_encrypted' => 'boolean',
        'is_required' => 'boolean',
        'is_visible' => 'boolean',
        'test_passed' => 'boolean',
        'last_tested_at' => 'datetime',
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
     * Obtener valor desencriptado si está encriptado
     */
    public function getDecryptedValueAttribute()
    {
        if ($this->is_encrypted && $this->encrypted_value) {
            try {
                return Crypt::decryptString($this->encrypted_value);
            } catch (\Exception $e) {
                return null;
            }
        }

        return $this->value;
    }

    /**
     * Establecer valor (encriptar si es necesario)
     */
    public function setValue($newValue)
    {
        if ($this->is_encrypted) {
            $this->update([
                'encrypted_value' => Crypt::encryptString($newValue),
                'value' => null,
            ]);
        } else {
            $this->update([
                'value' => $newValue,
                'encrypted_value' => null,
            ]);
        }
    }

    /**
     * Obtener todas las configuraciones como array [key => value]
     */
    public static function getAllAsArray()
    {
        $configs = self::all();
        $result = [];

        foreach ($configs as $config) {
            $result[$config->key] = $config->getDecryptedValueAttribute();
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

        return $config->getDecryptedValueAttribute() ?? $default;
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

        $config->setValue($value);
        
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
            $value = $config->getDecryptedValueAttribute();
            
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
            return empty($config->getDecryptedValueAttribute());
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
 * Obtener configuraciones de API ICG
 */
public static function getIcgApiConfig()
{
    return [
        'url'      => env('ICG_API_URL', self::where('key', 'icg_api_url')->value('value')),
        'user'     => env('ICG_API_USER', self::where('key', 'icg_api_user')->value('value')),
        'password' => env('ICG_API_PASSWORD', self::where('key', 'icg_api_password')->value('value')),
    ];
}

/**
     * Obtener todas las configuraciones de API Magento
     */
    public static function getMagentoApiConfig()
    {
        return [
            'base_url'  => env('MAGENTO_BASE_URL', self::where('key', 'magento_base_url')->value('value')),
            'api_token' => env('MAGENTO_API_TOKEN', self::where('key', 'magento_api_token')->value('value')),
            'store_id'  => env('MAGENTO_STORE_ID', self::where('key', 'magento_store_id')->value('value') ?? 1),
        ];
    }
 /**
     * Obtener todas las configuraciones de FTP
     */
    public static function getFtpConfig()
    {
        return [
            'enabled' => self::get('ftp_enabled', 'true') === 'true',
            'server' => self::get('ftp_server'),
            'port' => (int) self::get('ftp_port', 21),
            'username' => self::get('ftp_username'),
            'password' => self::get('ftp_password'),
        ];
    }

    /**
     * Obtener badge de tipo
     */
    public function getTypeBadgeAttribute()
    {
        return match($this->type) {
            'password' => ['icon' => 'lock-closed', 'color' => 'red'],
            'url' => ['icon' => 'link', 'color' => 'blue'],
            'email' => ['icon' => 'mail', 'color' => 'purple'],
            'boolean' => ['icon' => 'switch-horizontal', 'color' => 'green'],
            'integer' => ['icon' => 'hashtag', 'color' => 'indigo'],
            'json' => ['icon' => 'code', 'color' => 'yellow'],
            default => ['icon' => 'document-text', 'color' => 'gray'],
        };
    }

    /**
     * Obtener valor para mostrar en UI (ocultar passwords)
     */
    public function getDisplayValueAttribute()
    {
        if ($this->type === 'password' && !empty($this->getDecryptedValueAttribute())) {
            return '••••••••';
        }

        return $this->getDecryptedValueAttribute() ?? $this->default_value ?? 'No configurado';
    }
}
