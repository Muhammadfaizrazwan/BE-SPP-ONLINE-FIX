<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'code',
        'name',
        'type',
        'provider',
        'account_number',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
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

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByProvider($query, $provider)
    {
        return $query->where('provider', $provider);
    }

    // Helper Methods
    public function getTypeNameAttribute()
    {
        $types = [
            'bank_transfer' => 'Transfer Bank',
            'e_wallet' => 'E-Wallet',
            'cash' => 'Tunai',
            'qris' => 'QRIS',
            'va' => 'Virtual Account',
        ];

        return $types[$this->type] ?? $this->type;
    }

    public function isVirtualAccount()
    {
        return $this->type === 'va';
    }

    public function isEWallet()
    {
        return $this->type === 'e_wallet';
    }

    public function isCash()
    {
        return $this->type === 'cash';
    }

    public function isQris()
    {
        return $this->type === 'qris';
    }

    public function isBankTransfer()
    {
        return $this->type === 'bank_transfer';
    }

    public function requiresProof()
    {
        return in_array($this->type, ['bank_transfer', 'cash']);
    }

    public function isAutomated()
    {
        return in_array($this->type, ['va', 'e_wallet', 'qris']);
    }

    public function getTotalTransactions()
    {
        return $this->payments()->count();
    }

    public function getTotalAmount()
    {
        return $this->payments()->where('status', 'success')->sum('paid_amount');
    }

    public function getSuccessRate()
    {
        $total = $this->payments()->count();
        if ($total === 0) {
            return 0;
        }

        $success = $this->payments()->where('status', 'success')->count();
        return round(($success / $total) * 100, 2);
    }

    // Get admin fee for this payment method
    public function getAdminFee($amount = 0)
    {
        // You can implement different fee structures here
        switch ($this->type) {
            case 'va':
                return 4000; // Fixed fee for VA
            case 'e_wallet':
                return max(2500, $amount * 0.007); // 0.7% with minimum 2500
            case 'qris':
                return $amount * 0.007; // 0.7%
            case 'bank_transfer':
                return 6500; // Fixed fee for bank transfer
            case 'cash':
                return 0; // No fee for cash
            default:
                return 0;
        }
    }

    // Get payment gateway configuration
    public function getGatewayConfig()
    {
        $config = [];

        switch ($this->provider) {
            case 'Midtrans':
                $config = [
                    'server_key' => SystemConfig::getConfig('midtrans_server_key', $this->school_id),
                    'client_key' => SystemConfig::getConfig('midtrans_client_key', $this->school_id),
                    'is_production' => SystemConfig::getConfig('midtrans_is_production', $this->school_id, false),
                ];
                break;
            case 'Xendit':
                $config = [
                    'secret_key' => SystemConfig::getConfig('xendit_secret_key', $this->school_id),
                    'public_key' => SystemConfig::getConfig('xendit_public_key', $this->school_id),
                ];
                break;
        }

        return $config;
    }

    // Check if payment method is properly configured
    public function isConfigured()
    {
        if ($this->isCash()) {
            return true;
        }

        $config = $this->getGatewayConfig();

        switch ($this->provider) {
            case 'Midtrans':
                return !empty($config['server_key']) && !empty($config['client_key']);
            case 'Xendit':
                return !empty($config['secret_key']);
            default:
                return !empty($this->account_number);
        }
    }
}
