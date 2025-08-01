<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Carbon\Carbon;

class StudentBill extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'student_id',
        'class_id',
        'academic_year_id',
        'payment_type_id',
        'bill_month',
        'bill_year',
        'amount',
        'discount_amount',
        'final_amount',
        'due_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'bill_month' => 'integer',
        'bill_year' => 'integer',
        'amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'due_date' => 'date',
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

            // Auto calculate final_amount
            $model->final_amount = $model->amount - $model->discount_amount;

            // Auto set due date if not provided
            if (!$model->due_date) {
                $model->due_date = Carbon::create($model->bill_year, $model->bill_month, 10);
            }
        });

        static::updating(function ($model) {
            // Auto calculate final_amount when amount or discount changes
            if ($model->isDirty(['amount', 'discount_amount'])) {
                $model->final_amount = $model->amount - $model->discount_amount;
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

    public function paymentType(): BelongsTo
    {
        return $this->belongsTo(PaymentType::class);
    }

    public function paymentDetails(): HasMany
    {
        return $this->hasMany(PaymentDetail::class, 'bill_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    public function scopePartial($query)
    {
        return $query->where('status', 'partial');
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeByClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeByPaymentType($query, $paymentTypeId)
    {
        return $query->where('payment_type_id', $paymentTypeId);
    }

    public function scopeByMonth($query, $month, $year = null)
    {
        $query->where('bill_month', $month);
        if ($year) {
            $query->where('bill_year', $year);
        }
        return $query;
    }

    public function scopeByYear($query, $year)
    {
        return $query->where('bill_year', $year);
    }

    public function scopeDueThisMonth($query)
    {
        $now = now();
        return $query->where('due_date', '>=', $now->startOfMonth())
                    ->where('due_date', '<=', $now->endOfMonth());
    }

    public function scopeOverdueOnly($query)
    {
        return $query->where('due_date', '<', now())
                    ->whereIn('status', ['pending', 'partial']);
    }

    // Helper Methods
    public function getMonthNameAttribute()
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return $months[$this->bill_month] ?? '';
    }

    public function getBillPeriodAttribute()
    {
        return $this->month_name . ' ' . $this->bill_year;
    }

    public function isOverdue()
    {
        return $this->due_date < now() && in_array($this->status, ['pending', 'partial']);
    }

    public function isPaid()
    {
        return $this->status === 'paid';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isPartial()
    {
        return $this->status === 'partial';
    }

    public function getRemainingAmount()
    {
        if ($this->isPaid()) {
            return 0;
        }

        $paidAmount = $this->paymentDetails()->sum('amount_paid');
        return $this->final_amount - $paidAmount;
    }

    public function getPaidAmount()
    {
        return $this->paymentDetails()->sum('amount_paid');
    }

    public function getDaysUntilDue()
    {
        return now()->diffInDays($this->due_date, false);
    }

    public function getDaysOverdue()
    {
        if (!$this->isOverdue()) {
            return 0;
        }

        return $this->due_date->diffInDays(now());
    }

    // Mark bill as paid
    public function markAsPaid()
    {
        $this->update(['status' => 'paid']);
    }

    // Mark bill as partial
    public function markAsPartial()
    {
        $this->update(['status' => 'partial']);
    }

    // Mark bill as overdue
    public function markAsOverdue()
    {
        if ($this->isOverdue() && $this->isPending()) {
            $this->update(['status' => 'overdue']);
        }
    }

    // Apply discount
    public function applyDiscount($discountAmount, $reason = null)
    {
        $this->update([
            'discount_amount' => $discountAmount,
            'final_amount' => $this->amount - $discountAmount,
            'notes' => $reason ? "Diskon: {$reason}" : $this->notes,
        ]);
    }

    // Generate bills for a student
    public static function generateMonthlyBills($studentId, $academicYearId, $paymentTypeId, $months = [])
    {
        $student = Student::find($studentId);
        $enrollment = $student->enrollments()
                             ->where('academic_year_id', $academicYearId)
                             ->where('is_active', true)
                             ->first();

        if (!$enrollment) {
            return collect();
        }

        $paymentType = PaymentType::find($paymentTypeId);
        $academicYear = AcademicYear::find($academicYearId);

        if (empty($months)) {
            $months = $academicYear->getMonthsInYear();
        }

        $bills = collect();

        foreach ($months as $month) {
            // Check if bill already exists
            $existingBill = self::where('student_id', $studentId)
                              ->where('payment_type_id', $paymentTypeId)
                              ->where('bill_month', $month['month'])
                              ->where('bill_year', $month['year'])
                              ->first();

            if ($existingBill) {
                $bills->push($existingBill);
                continue;
            }

            // Get amount based on grade level
            $amount = $paymentType->getDefaultAmount($enrollment->class->grade_level);

            if ($amount <= 0) {
                continue;
            }

            $bill = self::create([
                'student_id' => $studentId,
                'class_id' => $enrollment->class_id,
                'academic_year_id' => $academicYearId,
                'payment_type_id' => $paymentTypeId,
                'bill_month' => $month['month'],
                'bill_year' => $month['year'],
                'amount' => $amount,
                'discount_amount' => 0,
                'final_amount' => $amount,
                'due_date' => Carbon::create($month['year'], $month['month'], 10),
                'status' => 'pending',
            ]);

            $bills->push($bill);
        }

        return $bills;
    }

    // Update overdue bills
    public static function updateOverdueBills()
    {
        return self::whereIn('status', ['pending', 'partial'])
                  ->where('due_date', '<', now())
                  ->update(['status' => 'overdue']);
    }
}
