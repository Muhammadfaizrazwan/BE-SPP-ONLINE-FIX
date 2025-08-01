<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentDetail extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'payment_id',
        'bill_id',
        'amount_paid',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = now();
        });
    }

    // Relationships
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(StudentBill::class, 'bill_id');
    }

    // Scopes
    public function scopeByPayment($query, $paymentId)
    {
        return $query->where('payment_id', $paymentId);
    }

    public function scopeByBill($query, $billId)
    {
        return $query->where('bill_id', $billId);
    }

    // Helper Methods
    public function getBillInfo()
    {
        return $this->bill ? [
            'payment_type' => $this->bill->paymentType->name,
            'period' => $this->bill->bill_period,
            'original_amount' => $this->bill->final_amount,
            'paid_amount' => $this->amount_paid,
            'remaining_amount' => $this->bill->final_amount - $this->amount_paid,
        ] : null;
    }

    public function isFullPayment()
    {
        return $this->bill && $this->amount_paid >= $this->bill->final_amount;
    }

    public function isPartialPayment()
    {
        return $this->bill && $this->amount_paid < $this->bill->final_amount && $this->amount_paid > 0;
    }

    // Get payment detail with complete information
    public function getDetailInfo()
    {
        $billInfo = $this->getBillInfo();

        return [
            'id' => $this->id,
            'payment_id' => $this->payment_id,
            'bill_id' => $this->bill_id,
            'amount_paid' => $this->amount_paid,
            'created_at' => $this->created_at,
            'bill_info' => $billInfo,
            'is_full_payment' => $this->isFullPayment(),
            'is_partial_payment' => $this->isPartialPayment(),
        ];
    }
}
