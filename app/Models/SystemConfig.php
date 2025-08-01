<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class SystemConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'config_key',
        'config_value',
        'data_type',
        'description',
        'is_sensitive',
    ];

    protected $casts = [
        'is_sensitive' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        // Clear cache when config is updated
        static::saved(function ($model) {
            Cache::forget("system_config_{$model->school_id}_{$model->config_key}");
            Cache::forget("system_config_all_{$model->school_id}");
        });

        static::deleted(function ($model) {
            Cache::forget("system_config_{$model->school_id}_{$model->config_key}");
            Cache::forget("system_config_all_{$model->school_id}");
        });
    }

    // Relationships
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    // Scopes
    public function scopeBySchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeGlobal($query)
    {
        return $query->whereNull('school_id');
    }

    public function scopeNonSensitive($query)
    {
        return $query->where('is_sensitive', false);
    }

    public function scopeByKey($query, $key)
    {
        return $query->where('config_key', $key);
    }

    // Accessors
    public function getValueAttribute()
    {
        return $this->castValue($this->config_value, $this->data_type);
    }

    // Helper Methods
    protected function castValue($value, $type)
    {
        switch ($type) {
            case 'integer':
                return (int) $value;
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'json':
                return json_decode($value, true);
            case 'string':
            default:
                return $value;
        }
    }

    public function isGlobal()
    {
        return is_null($this->school_id);
    }

    public function isSchoolSpecific()
    {
        return !is_null($this->school_id);
    }

    // Static Methods
    public static function getConfig($key, $schoolId = null, $default = null)
    {
        $cacheKey = "system_config_{$schoolId}_{$key}";

        return Cache::remember($cacheKey, 3600, function () use ($key, $schoolId, $default) {
            // First, try to get school-specific config
            if ($schoolId) {
                $config = self::where('config_key', $key)
                           ->where('school_id', $schoolId)
                           ->first();

                if ($config) {
                    return $config->value;
                }
            }

            // If not found, try global config
            $globalConfig = self::where('config_key', $key)
                              ->whereNull('school_id')
                              ->first();

            return $globalConfig ? $globalConfig->value : $default;
        });
    }

    public static function setConfig($key, $value, $schoolId = null, $dataType = 'string', $description = null, $isSensitive = false)
    {
        $config = self::updateOrCreate(
            [
                'config_key' => $key,
                'school_id' => $schoolId,
            ],
            [
                'config_value' => $value,
                'data_type' => $dataType,
                'description' => $description,
                'is_sensitive' => $isSensitive,
            ]
        );

        return $config;
    }

    public static function getAllConfigs($schoolId = null)
    {
        $cacheKey = "system_config_all_{$schoolId}";

        return Cache::remember($cacheKey, 3600, function () use ($schoolId) {
            $query = self::query();

            if ($schoolId) {
                $query->where(function ($q) use ($schoolId) {
                    $q->where('school_id', $schoolId)
                      ->orWhereNull('school_id');
                });
            } else {
                $query->whereNull('school_id');
            }

            return $query->get()->pluck('value', 'config_key')->toArray();
        });
    }

    public static function getConfigsByCategory($category, $schoolId = null)
    {
        $query = self::where('config_key', 'like', $category . '%');

        if ($schoolId) {
            $query->where(function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId)
                  ->orWhereNull('school_id');
            });
        } else {
            $query->whereNull('school_id');
        }

        return $query->get();
    }

    // Common config getters
    public static function getSppAmount($gradeLevel, $schoolId)
    {
        return self::getConfig("spp_amount_grade_{$gradeLevel}", $schoolId, 0);
    }

    public static function getPaymentDueDate($schoolId)
    {
        return self::getConfig('payment_due_date', $schoolId, 10);
    }

    public static function getLateFeeAmount($schoolId)
    {
        return self::getConfig('late_fee_amount', $schoolId, 0);
    }

    public static function getNotificationChannels($schoolId)
    {
        return self::getConfig('notification_channels', $schoolId, ['email']);
    }

    public static function getMidtransConfig($schoolId)
    {
        return [
            'server_key' => self::getConfig('midtrans_server_key', $schoolId),
            'client_key' => self::getConfig('midtrans_client_key', $schoolId),
            'is_production' => self::getConfig('midtrans_is_production', $schoolId, false),
        ];
    }

    public static function isMaintenanceMode()
    {
        return self::getConfig('maintenance_mode', null, false);
    }

    public static function getAppName()
    {
        return self::getConfig('app_name', null, 'SPP Online System');
    }

    // Bulk update configs
    public static function updateConfigs($configs, $schoolId = null)
    {
        foreach ($configs as $key => $config) {
            if (is_array($config)) {
                self::setConfig(
                    $key,
                    $config['value'],
                    $schoolId,
                    $config['data_type'] ?? 'string',
                    $config['description'] ?? null,
                    $config['is_sensitive'] ?? false
                );
            } else {
                self::setConfig($key, $config, $schoolId);
            }
        }
    }

    // Export configs for backup
    public static function exportConfigs($schoolId = null)
    {
        $query = self::query();

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        } else {
            $query->whereNull('school_id');
        }

        return $query->get([
            'config_key',
            'config_value',
            'data_type',
            'description',
            'is_sensitive'
        ])->toArray();
    }

    // Import configs from backup
    public static function importConfigs($configs, $schoolId = null)
    {
        foreach ($configs as $config) {
            self::setConfig(
                $config['config_key'],
                $config['config_value'],
                $schoolId,
                $config['data_type'],
                $config['description'],
                $config['is_sensitive']
            );
        }
    }
}
