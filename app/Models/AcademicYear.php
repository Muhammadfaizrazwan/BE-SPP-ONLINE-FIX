<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AcademicYear extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'school_id',
        'year',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
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

        // Pastikan hanya ada satu academic year aktif per sekolah
        static::updating(function ($model) {
            if ($model->is_active && $model->isDirty('is_active')) {
                self::where('school_id', $model->school_id)
                    ->where('id', '!=', $model->id)
                    ->update(['is_active' => false]);
            }
        });
    }

    // Relationships
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function studentEnrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
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

    public function scopeCurrent($query)
    {
        $now = now();
        return $query->where('start_date', '<=', $now)
                    ->where('end_date', '>=', $now);
    }

    // Helper Methods
    public function isCurrent(): bool
    {
        $now = now();
        return $this->start_date <= $now && $this->end_date >= $now;
    }

    public function getTotalStudents()
    {
        return $this->studentEnrollments()
                   ->where('is_active', true)
                   ->distinct('student_id')
                   ->count('student_id');
    }

    public function getTotalClasses()
    {
        return $this->classes()->where('is_active', true)->count();
    }

    public function getMonthsInYear()
    {
        $start = $this->start_date;
        $end = $this->end_date;

        $months = [];
        $current = $start->copy()->startOfMonth();

        while ($current <= $end) {
            $months[] = [
                'month' => $current->month,
                'year' => $current->year,
                'name' => $current->format('F Y'),
            ];
            $current->addMonth();
        }

        return $months;
    }

    // Generate academic year string (e.g., 2024/2025)
    public static function generateYearString($startYear)
    {
        return $startYear . '/' . ($startYear + 1);
    }
}
