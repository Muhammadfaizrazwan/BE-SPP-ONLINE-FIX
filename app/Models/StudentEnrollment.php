<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class StudentEnrollment extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'student_id',
        'class_id',
        'academic_year_id',
        'enrollment_date',
        'is_active',
    ];

    protected $casts = [
        'enrollment_date' => 'date',
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

        // Pastikan hanya ada satu enrollment aktif per siswa per tahun ajaran
        static::creating(function ($model) {
            if ($model->is_active) {
                self::where('student_id', $model->student_id)
                    ->where('academic_year_id', $model->academic_year_id)
                    ->update(['is_active' => false]);
            }
        });

        static::updating(function ($model) {
            if ($model->is_active && $model->isDirty('is_active')) {
                self::where('student_id', $model->student_id)
                    ->where('academic_year_id', $model->academic_year_id)
                    ->where('id', '!=', $model->id)
                    ->update(['is_active' => false]);
            }
        });
    }

    // Relationships
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByAcademicYear($query, $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    public function scopeByClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    // Helper Methods
    public function getEnrollmentDuration()
    {
        $start = $this->enrollment_date;
        $end = $this->is_active ? now() : $this->updated_at;

        return $start->diffForHumans($end);
    }

    public function isCurrentEnrollment()
    {
        return $this->is_active && $this->academicYear->is_active;
    }

    // Deactivate enrollment
    public function deactivate()
    {
        $this->update(['is_active' => false]);
    }

    // Activate enrollment (and deactivate others for same student in same academic year)
    public function activate()
    {
        // Deactivate other enrollments for same student in same academic year
        self::where('student_id', $this->student_id)
            ->where('academic_year_id', $this->academic_year_id)
            ->where('id', '!=', $this->id)
            ->update(['is_active' => false]);

        // Activate this enrollment
        $this->update(['is_active' => true]);
    }

    // Transfer student to another class
    public function transferToClass($newClassId, $transferDate = null)
    {
        $transferDate = $transferDate ?: now()->toDateString();

        // Create new enrollment
        $newEnrollment = self::create([
            'student_id' => $this->student_id,
            'class_id' => $newClassId,
            'academic_year_id' => $this->academic_year_id,
            'enrollment_date' => $transferDate,
            'is_active' => true,
        ]);

        // Deactivate current enrollment
        $this->deactivate();

        return $newEnrollment;
    }

    // Get class history for a student
    public static function getStudentClassHistory($studentId)
    {
        return self::with(['class', 'academicYear'])
                  ->where('student_id', $studentId)
                  ->orderBy('enrollment_date', 'desc')
                  ->get();
    }

    // Get students in a class
    public static function getStudentsInClass($classId, $activeOnly = true)
    {
        $query = self::with('student')
                    ->where('class_id', $classId);

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->get();
    }
}
