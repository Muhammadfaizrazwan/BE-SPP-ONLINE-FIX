<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_code',
        'student_id',
        'bill_ids',
        'payment_method_id',
        'total_amount',
        'paid_amount',
        'admin_fee',
        'payment_date',
        'payment_proof',
        'status',
        'gateway_reference',
        'verified_by',
        'verified_at',
        'notes',
    ];

    protected $casts = [
        'bill_ids' => 'array',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'admin_fee' => 'decimal:2',
        'payment_date' => 'datetime',
        'verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->payment_code)) {
                $model->payment_code = self::generatePaymentCode();
            }
        });

        static::updated(function ($model) {
            // Update related bills status when payment status changes
            if ($model->isDirty('status') && $model->status === 'success') {
                $model->updateBillsStatus();
            }
        });
    }

    // Relationships
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function paymentDetails(): HasMany
    {
        return $this->hasMany(PaymentDetail::class);
    }

    public function bills()
    {
        return StudentBill::whereIn('id', $this->bill_ids ?? []);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeByPaymentMethod($query, $paymentMethodId)
    {
        return $query->where('payment_method_id', $paymentMethodId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('payment_date', today());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('payment_date', now()->month)
                    ->whereYear('payment_date', now()->year);
    }

    // Helper Methods
    public function getStatusColorAttribute()
    {
        $colors = [
            'pending' => 'warning',
            'processing' => 'info',
            'success' => 'success',
            'failed' => 'danger',
            'cancelled' => 'secondary',
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    public function getStatusNameAttribute()
    {
        $statuses = [
            'pending' => 'Menunggu',
            'processing' => 'Diproses',
            'success' => 'Berhasil',
            'failed' => 'Gagal',
            'cancelled' => 'Dibatalkan',
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isProcessing()
    {
        return $this->status === 'processing';
    }

    public function isSuccess()
    {
        return $this->status === 'success';
    }

    public function isFailed()
    {
        return $this->status === 'failed';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    public function isCompleted()
    {
        return in_array($this->status, ['success', 'failed', 'cancelled']);
    }

    public function canBeVerified()
    {
        return $this->isPending() && $this->paymentMethod->requiresProof();
    }

    public function canBeCancelled()
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    // Generate unique payment code
    public static function generatePaymentCode($prefix = 'PAY')
    {
        do {
            $code = $prefix . date('Ymd') . strtoupper(Str::random(4));
        } while (self::where('payment_code', $code)->exists());

        return $code;
    }

    // Update bills status based on payment
    public function updateBillsStatus()
    {
        if (!$this->isSuccess()) {
            return;
        }

        foreach ($this->paymentDetails as $detail) {
            $bill = $detail->bill;
            if (!$bill) continue;

            $totalPaid = $bill->getPaidAmount();

            if ($totalPaid >= $bill->final_amount) {
                $bill->markAsPaid();
            } else if ($totalPaid > 0) {
                $bill->markAsPartial();
            }
        }
    }

    // Verify payment (for manual verification)
    public function verify($verifiedBy, $notes = null)
    {
        $this->update([
            'status' => 'success',
            'verified_by' => $verifiedBy,
            'verified_at' => now(),
            'notes' => $notes,
        ]);

        // Update bills status
        $this->updateBillsStatus();

        // Send notification
        // NotificationService::sendPaymentSuccessNotification($this);
    }

    // Cancel payment
    public function cancel($reason = null)
    {
        if (!$this->canBeCancelled()) {
            return false;
        }

        $this->update([
            'status' => 'cancelled',
            'notes' => $reason ? "Dibatalkan: {$reason}" : $this->notes,
        ]);

        return true;
    }

    // Mark as failed
    public function markAsFailed($reason = null)
    {
        $this->update([
            'status' => 'failed',
            'notes' => $reason ? "Gagal: {$reason}" : $this->notes,
        ]);
    }

    // Get payment proof URL
    public function getPaymentProofUrlAttribute()
    {
        return $this->payment_proof ? asset('storage/' . $this->payment_proof) : null;
    }

    // Get total amount including admin fee
    public function getGrandTotalAttribute()
    {
        return $this->total_amount + $this->admin_fee;
    }

    // Get payment details with bill information
    public function getPaymentDetailsWithBills()
    {
        return $this->paymentDetails()->with('bill.paymentType')->get();
    }

    // Create payment with details
    public static function createPayment($studentId, $billIds, $paymentMethodId, $paymentData = [])
    {
        $bills = StudentBill::whereIn('id', $billIds)->get();
        $totalAmount = $bills->sum('final_amount');

        $paymentMethod = PaymentMethod::find($paymentMethodId);
        $adminFee = $paymentMethod->getAdminFee($totalAmount);

        $payment = self::create(array_merge([
            'student_id' => $studentId,
            'bill_ids' => $billIds,
            'payment_method_id' => $paymentMethodId,
            'total_amount' => $totalAmount,
            'paid_amount' => $totalAmount + $adminFee,
            'admin_fee' => $adminFee,
            'payment_date' => now(),
            'status' => $paymentMethod->isAutomated() ? 'processing' : 'pending',
        ], $paymentData));

        // Create payment details
        foreach ($bills as $bill) {
            PaymentDetail::create([
                'payment_id' => $payment->id,
                'bill_id' => $bill->id,
                'amount_paid' => $bill->final_amount,
            ]);
        }

        return $payment;
    }

    // Get monthly payment statistics
    public static function getMonthlyStats($schoolId = null, $month = null, $year = null)
    {
        $query = self::query()->success();

        if ($schoolId) {
            $query->whereHas('student', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            });
        }

        if ($month && $year) {
            $query->whereMonth('payment_date', $month)
                  ->whereYear('payment_date', $year);
        }

        return [
            'total_payments' => $query->count(),
            'total_amount' => $query->sum('total_amount'),
            'total_admin_fee' => $query->sum('admin_fee'),
        ];
    }
}
