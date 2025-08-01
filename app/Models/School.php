<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class School extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'principal_name',
        'bank_account',
        'bank_name',
        'logo_url',
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
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function academicYears(): HasMany
    {
        return $this->hasMany(AcademicYear::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function paymentTypes(): HasMany
    {
        return $this->hasMany(PaymentType::class);
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function systemConfigs(): HasMany
    {
        return $this->hasMany(SystemConfig::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Accessors & Mutators
    public function getLogoAttribute()
    {
        return $this->logo_url ? asset('storage/' . $this->logo_url) : null;
    }

    // Helper Methods
    public function getActiveAcademicYear()
    {
        return $this->academicYears()->where('is_active', true)->first();
    }

    public function getTotalStudents()
    {
        return $this->students()->where('is_active', true)->count();
    }

    public function getTotalUnpaidBills()
    {
        return StudentBill::whereHas('student', function ($query) {
            $query->where('school_id', $this->id);
        })->where('status', 'pending')->sum('final_amount');
    }
}
