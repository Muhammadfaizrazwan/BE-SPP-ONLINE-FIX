<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Student extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'school_id',
        'student_number',
        'nis',
        'nisn',
        'full_name',
        'gender',
        'birth_place',
        'birth_date',
        'address',
        'phone',
        'email',
        'parent_name',
        'parent_phone',
        'parent_email',
        'user_id',
        'is_active',
    ];

    protected $casts = [
        'birth_date' => 'date',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function activeEnrollment(): HasOne
    {
        return $this->hasOne(StudentEnrollment::class)->where('is_active', true);
    }

    public function currentClass()
    {
        return $this->belongsTo(SchoolClass::class, 'id', 'student_id')
                   ->join('student_enrollments', function ($join) {
                       $join->on('classes.id', '=', 'student_enrollments.class_id')
                            ->where('student_enrollments.student_id', $this->id)
                            ->where('student_enrollments.is_active', true);
                   });
    }

    public function bills(): HasMany
    {
        return $this->hasMany(StudentBill::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
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

    public function scopeByGender($query, $gender)
    {
        return $query->where('gender', $gender);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('full_name', 'like', "%{$search}%")
              ->orWhere('student_number', 'like', "%{$search}%")
              ->orWhere('nis', 'like', "%{$search}%")
              ->orWhere('nisn', 'like', "%{$search}%");
        });
    }

    // Helper Methods
    public function getGenderNameAttribute()
    {
        return $this->gender === 'L' ? 'Laki-laki' : 'Perempuan';
    }

    public function getAgeAttribute()
    {
        return $this->birth_date ? $this->birth_date->age : null;
    }

    public function getCurrentClass()
    {
        $enrollment = $this->activeEnrollment;
        return $enrollment ? $enrollment->class : null;
    }

    public function getCurrentAcademicYear()
    {
        $enrollment = $this->activeEnrollment;
        return $enrollment ? $enrollment->academicYear : null;
    }

    // Get pending bills
    public function getPendingBills()
    {
        return $this->bills()
                   ->where('status', 'pending')
                   ->orderBy('due_date')
                   ->get();
    }

    // Get overdue bills
    public function getOverdueBills()
    {
        return $this->bills()
                   ->where('status', 'overdue')
                   ->orderBy('due_date')
                   ->get();
    }

    // Get total unpaid amount
    public function getTotalUnpaidAmount()
    {
        return $this->bills()
                   ->whereIn('status', ['pending', 'overdue', 'partial'])
                   ->sum('final_amount');
    }

    // Get payment history
    public function getPaymentHistory($limit = 10)
    {
        return $this->payments()
                   ->where('status', 'success')
                   ->orderBy('payment_date', 'desc')
                   ->limit($limit)
                   ->get();
    }

    // Check if student has unpaid bills
    public function hasUnpaidBills()
    {
        return $this->bills()
                   ->whereIn('status', ['pending', 'overdue', 'partial'])
                   ->exists();
    }

    // Generate student number automatically
    public static function generateStudentNumber($schoolId, $year = null)
    {
        $year = $year ?: date('Y');
        $prefix = substr($year, -2);

        $lastNumber = self::where('school_id', $schoolId)
                         ->where('student_number', 'like', $prefix . '%')
                         ->orderBy('student_number', 'desc')
                         ->value('student_number');

        if ($lastNumber) {
            $sequence = (int) substr($lastNumber, -4) + 1;
        } else {
            $sequence = 1;
        }

        return $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    // Create user account for student/parent
    public function createUserAccount($password = null)
    {
        if ($this->user_id) {
            return $this->user;
        }

        $password = $password ?: 'student123';

        $user = User::create([
            'username' => $this->student_number,
            'email' => $this->email ?: $this->parent_email,
            'password' => bcrypt($password),
            'role' => 'student',
            'school_id' => $this->school_id,
        ]);

        $this->update(['user_id' => $user->id]);

        return $user;
    }
}
