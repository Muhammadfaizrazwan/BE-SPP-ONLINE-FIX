<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PaymentType extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'school_id',
        'code',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::uuid();
            }
        });
    }

    // Relationships
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function studentBills(): HasMany
    {
        return $this->hasMany(StudentBill::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBySchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }

    // Helper Methods
    public function isSpp()
    {
        return strtoupper($this->code) === 'SPP';
    }

    public function getTotalBills()
    {
        return $this->studentBills()->count();
    }

    public function getTotalUnpaidAmount()
    {
        return $this->studentBills()
                   ->whereIn('status', ['pending', 'overdue', 'partial'])
                   ->sum('final_amount');
    }

    public function getTotalPaidAmount()
    {
        return $this->studentBills()
                   ->where('status', 'paid')
                   ->sum('final_amount');
    }

    // Get default amount for this payment type based on grade level
    public function getDefaultAmount($gradeLevel)
    {
        if ($this->isSpp()) {
            $configKey = 'spp_amount_grade_' . $gradeLevel;

            $config = SystemConfig::where('school_id', $this->school_id)
                                  ->where('config_key', $configKey)
                                  ->first();

            return $config ? (int) $config->config_value : 0;
        }

        // For other payment types, you might want to implement different logic
        return 0;
    }
}
