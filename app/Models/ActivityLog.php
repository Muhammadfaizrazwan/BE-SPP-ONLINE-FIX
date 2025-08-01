<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    public $timestamps = false; // Only has created_at

    protected $fillable = [
        'user_id',
        'school_id',
        'action',
        'table_name',
        'record_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = now();

            // Auto capture IP and User Agent if not set
            if (!$model->ip_address && request()) {
                $model->ip_address = request()->ip();
            }

            if (!$model->user_agent && request()) {
                $model->user_agent = request()->userAgent();
            }
        });
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    // Scopes
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeBySchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByTable($query, $tableName)
    {
        return $query->where('table_name', $tableName);
    }

    public function scopeByRecord($query, $recordId)
    {
        return $query->where('record_id', $recordId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
    }

    public function scopeRecent($query, $limit = 50)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    // Helper Methods
    public function getActionColorAttribute()
    {
        $colors = [
            'created' => 'success',
            'updated' => 'warning',
            'deleted' => 'danger',
            'login' => 'info',
            'logout' => 'secondary',
            'payment' => 'primary',
            'verified' => 'success',
            'cancelled' => 'danger',
        ];

        return $colors[$this->action] ?? 'secondary';
    }

    public function getActionNameAttribute()
    {
        $actions = [
            'created' => 'Dibuat',
            'updated' => 'Diubah',
            'deleted' => 'Dihapus',
            'login' => 'Login',
            'logout' => 'Logout',
            'payment' => 'Pembayaran',
            'verified' => 'Diverifikasi',
            'cancelled' => 'Dibatalkan',
            'exported' => 'Diekspor',
            'imported' => 'Diimpor',
        ];

        return $actions[$this->action] ?? ucfirst($this->action);
    }

    public function getTableNameAttribute($value)
    {
        $tableNames = [
            'schools' => 'Sekolah',
            'users' => 'Pengguna',
            'students' => 'Siswa',
            'classes' => 'Kelas',
            'student_bills' => 'Tagihan',
            'payments' => 'Pembayaran',
            'payment_types' => 'Jenis Pembayaran',
            'payment_methods' => 'Metode Pembayaran',
            'academic_years' => 'Tahun Ajaran',
            'student_enrollments' => 'Pendaftaran Siswa',
            'system_configs' => 'Konfigurasi Sistem',
            'notifications' => 'Notifikasi',
        ];

        return $tableNames[$value] ?? ucfirst(str_replace('_', ' ', $value));
    }

    public function getUserNameAttribute()
    {
        return $this->user ? $this->user->full_name : 'System';
    }

    public function getFormattedDescriptionAttribute()
    {
        $userName = $this->user_name;
        $action = $this->action_name;
        $table = $this->table_name;
        $recordId = $this->record_id;

        return "{$userName} {$action} {$table} #{$recordId}";
    }

    // Get changes in human readable format
    public function getChangesAttribute()
    {
        if (!$this->old_values || !$this->new_values) {
            return [];
        }

        $changes = [];
        foreach ($this->new_values as $key => $newValue) {
            $oldValue = $this->old_values[$key] ?? null;

            if ($oldValue != $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changes;
    }

    public function hasAttributeChanges()
    {
        return !empty($this->changes);
    }

    // Static Methods
    public static function logActivity($action, $tableName, $recordId, $oldValues = null, $newValues = null, $userId = null, $schoolId = null)
    {
        // Get current user if not provided
        if (!$userId && auth()->check()) {
            $userId = auth()->id();
        }

        // Get school from user if not provided
        if (!$schoolId && $userId) {
            $user = User::find($userId);
            $schoolId = $user ? $user->school_id : null;
        }

        return self::create([
            'user_id' => $userId,
            'school_id' => $schoolId,
            'action' => $action,
            'table_name' => $tableName,
            'record_id' => (string) $recordId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }

    public static function logLogin($userId)
    {
        $user = User::find($userId);

        self::logActivity(
            'login',
            'users',
            $userId,
            null,
            ['login_time' => now()],
            $userId,
            $user ? $user->school_id : null
        );
    }

    public static function logLogout($userId)
    {
        $user = User::find($userId);

        self::logActivity(
            'logout',
            'users',
            $userId,
            null,
            ['logout_time' => now()],
            $userId,
            $user ? $user->school_id : null
        );
    }

    public static function logPayment($paymentId, $action = 'created')
    {
        $payment = Payment::find($paymentId);
        if (!$payment) return null;

        return self::logActivity(
            $action,
            'payments',
            $paymentId,
            null,
            [
                'payment_code' => $payment->payment_code,
                'amount' => $payment->total_amount,
                'status' => $payment->status,
            ],
            auth()->id(),
            $payment->student->school_id
        );
    }

    public static function getActivityStats($schoolId = null, $period = 'today')
    {
        $query = self::query();

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        switch ($period) {
            case 'today':
                $query->today();
                break;
            case 'week':
                $query->thisWeek();
                break;
            case 'month':
                $query->thisMonth();
                break;
        }

        return [
            'total_activities' => $query->count(),
            'by_action' => $query->selectRaw('action, COUNT(*) as count')
                                ->groupBy('action')
                                ->pluck('count', 'action')
                                ->toArray(),
            'by_table' => $query->selectRaw('table_name, COUNT(*) as count')
                               ->groupBy('table_name')
                               ->pluck('count', 'table_name')
                               ->toArray(),
        ];
    }

    // Clean old logs (for maintenance)
    public static function cleanOldLogs($days = 90)
    {
        return self::where('created_at', '<', now()->subDays($days))->delete();
    }
}
