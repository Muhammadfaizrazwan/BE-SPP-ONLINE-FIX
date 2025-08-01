<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SchoolClass extends Model
{
    use HasFactory;

    protected $table = 'classes';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'class_name',
        'grade_level',
        'is_active',
    ];

    protected $casts = [
        'grade_level' => 'integer',
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

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function studentEnrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class, 'class_id');
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_enrollments', 'class_id', 'student_id')
                   ->withPivot(['enrollment_date', 'is_active'])
                   ->wherePivot('is_active', true);
    }

    public function studentBills(): HasMany
    {
        return $this->hasMany(StudentBill::class, 'class_id');
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

    public function scopeByGradeLevel($query, $gradeLevel)
    {
        return $query->where('grade_level', $gradeLevel);
    }

    public function scopeByAcademicYear($query, $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    // Helper Methods
    public function getTotalStudents()
    {
        return $this->studentEnrollments()->where('is_active', true)->count();
    }

    public function getGradeLevelName()
    {
        $gradeNames = [
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII',
        ];

        return $gradeNames[$this->grade_level] ?? $this->grade_level;
    }

    public function getFullClassName()
    {
        return $this->getGradeLevelName() . ' - ' . $this->class_name;
    }

    // Get SPP amount for this class based on grade level
    public function getSppAmount()
    {
        $configKey = 'spp_amount_grade_' . $this->grade_level;

        $config = SystemConfig::where('school_id', $this->school_id)
                              ->where('config_key', $configKey)
                              ->first();

        return $config ? (int) $config->config_value : 0;
    }

    // Get active students with their enrollment info
    public function getActiveStudentsWithEnrollment()
    {
        return $this->studentEnrollments()
                   ->with('student')
                   ->where('is_active', true)
                   ->get();
    }

    // Check if class has students
    public function hasStudents()
    {
        return $this->getTotalStudents() > 0;
    }
}
