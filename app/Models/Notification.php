<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    public $timestamps = false; // Only has created_at

    protected $fillable = [
        'school_id',
        'user_id',
        'student_id',
        'title',
        'message',
        'type',
        'channels',
        'sent_at',
        'read_at',
        'status',
    ];

    protected $casts = [
        'channels' => 'array',
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
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
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    // Scopes
    public function scopeBySchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeRecent($query, $limit = 20)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    // Helper Methods
    public function getTypeColorAttribute()
    {
        $colors = [
            'payment_reminder' => 'warning',
            'payment_success' => 'success',
            'overdue' => 'danger',
            'system' => 'info',
            'announcement' => 'primary',
        ];

        return $colors[$this->type] ?? 'secondary';
    }

    public function getTypeNameAttribute()
    {
        $types = [
            'payment_reminder' => 'Pengingat Pembayaran',
            'payment_success' => 'Pembayaran Berhasil',
            'overdue' => 'Tunggakan',
            'system' => 'Sistem',
            'announcement' => 'Pengumuman',
        ];

        return $types[$this->type] ?? ucfirst(str_replace('_', ' ', $this->type));
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'pending' => 'warning',
            'sent' => 'info',
            'delivered' => 'success',
            'failed' => 'danger',
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    public function getStatusNameAttribute()
    {
        $statuses = [
            'pending' => 'Menunggu',
            'sent' => 'Terkirim',
            'delivered' => 'Sampai',
            'failed' => 'Gagal',
        ];

        return $statuses[$this->status] ?? ucfirst($this->status);
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isSent()
    {
        return $this->status === 'sent';
    }

    public function isDelivered()
    {
        return $this->status === 'delivered';
    }

    public function isFailed()
    {
        return $this->status === 'failed';
    }

    public function isRead()
    {
        return !is_null($this->read_at);
    }

    public function isUnread()
    {
        return is_null($this->read_at);
    }

    public function hasChannel($channel)
    {
        return in_array($channel, $this->channels ?? []);
    }

    // Mark as read
    public function markAsRead()
    {
        if ($this->isUnread()) {
            $this->update(['read_at' => now()]);
        }
    }

    // Mark as sent
    public function markAsSent()
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    // Mark as delivered
    public function markAsDelivered()
    {
        $this->update(['status' => 'delivered']);
    }

    // Mark as failed
    public function markAsFailed()
    {
        $this->update(['status' => 'failed']);
    }

    // Get recipient info
    public function getRecipientAttribute()
    {
        if ($this->user_id) {
            return $this->user;
        }

        if ($this->student_id) {
            return $this->student;
        }

        return null;
    }

    public function getRecipientNameAttribute()
    {
        $recipient = $this->recipient;

        if (!$recipient) {
            return 'Broadcast';
        }

        if ($recipient instanceof User) {
            return $recipient->full_name;
        }

        if ($recipient instanceof Student) {
            return $recipient->full_name;
        }

        return 'Unknown';
    }

    public function getRecipientContactAttribute()
    {
        $recipient = $this->recipient;

        if (!$recipient) {
            return null;
        }

        $contacts = [];

        if ($this->hasChannel('email')) {
            if ($recipient instanceof User) {
                $contacts['email'] = $recipient->email;
            } elseif ($recipient instanceof Student) {
                $contacts['email'] = $recipient->email ?: $recipient->parent_email;
            }
        }

        if ($this->hasChannel('whatsapp') || $this->hasChannel('sms')) {
            if ($recipient instanceof User && $recipient->student) {
                $contacts['phone'] = $recipient->student->parent_phone ?: $recipient->student->phone;
            } elseif ($recipient instanceof Student) {
                $contacts['phone'] = $recipient->parent_phone ?: $recipient->phone;
            }
        }

        return $contacts;
    }

    // Static Methods
    public static function createNotification($schoolId, $title, $message, $type, $channels = ['email'], $userId = null, $studentId = null)
    {
        return self::create([
            'school_id' => $schoolId,
            'user_id' => $userId,
            'student_id' => $studentId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'channels' => $channels,
            'status' => 'pending',
        ]);
    }

    public static function createPaymentReminder($studentBill)
    {
        $student = $studentBill->student;
        $school = $student->school;

        $title = 'Pengingat Pembayaran ' . $studentBill->paymentType->name;
        $message = "Tagihan {$studentBill->paymentType->name} untuk periode {$studentBill->bill_period} sebesar Rp " . number_format($studentBill->final_amount, 0, ',', '.') . " akan jatuh tempo pada " . $studentBill->due_date->format('d/m/Y') . ".";

        $channels = SystemConfig::getNotificationChannels($school->id);

        return self::createNotification(
            $school->id,
            $title,
            $message,
            'payment_reminder',
            $channels,
            $student->user_id,
            $student->id
        );
    }

    public static function createPaymentSuccess($payment)
    {
        $student = $payment->student;
        $school = $student->school;

        $title = 'Pembayaran Berhasil';
        $message = "Pembayaran dengan kode {$payment->payment_code} sebesar Rp " . number_format($payment->total_amount, 0, ',', '.') . " telah berhasil diproses.";

        $channels = SystemConfig::getNotificationChannels($school->id);

        return self::createNotification(
            $school->id,
            $title,
            $message,
            'payment_success',
            $channels,
            $student->user_id,
            $student->id
        );
    }

    public static function createOverdueNotification($studentBill)
    {
        $student = $studentBill->student;
        $school = $student->school;

        $daysOverdue = $studentBill->getDaysOverdue();

        $title = 'Tagihan Terlambat';
        $message = "Tagihan {$studentBill->paymentType->name} untuk periode {$studentBill->bill_period} telah terlambat {$daysOverdue} hari. Jumlah yang harus dibayar: Rp " . number_format($studentBill->final_amount, 0, ',', '.') . ".";

        $channels = SystemConfig::getNotificationChannels($school->id);

        return self::createNotification(
            $school->id,
            $title,
            $message,
            'overdue',
            $channels,
            $student->user_id,
            $student->id
        );
    }

    public static function createAnnouncement($schoolId, $title, $message, $channels = ['email'], $userIds = [], $studentIds = [])
    {
        $notifications = collect();

        // Create for specific users
        foreach ($userIds as $userId) {
            $notification = self::createNotification(
                $schoolId,
                $title,
                $message,
                'announcement',
                $channels,
                $userId
            );
            $notifications->push($notification);
        }

        // Create for specific students
        foreach ($studentIds as $studentId) {
            $student = Student::find($studentId);
            $notification = self::createNotification(
                $schoolId,
                $title,
                $message,
                'announcement',
                $channels,
                $student ? $student->user_id : null,
                $studentId
            );
            $notifications->push($notification);
        }

        // If no specific recipients, create broadcast notification
        if (empty($userIds) && empty($studentIds)) {
            $notification = self::createNotification(
                $schoolId,
                $title,
                $message,
                'announcement',
                $channels
            );
            $notifications->push($notification);
        }

        return $notifications;
    }

    // Get notification statistics
    public static function getStats($schoolId = null, $period = 'today')
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
                $query->whereBetween('created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ]);
                break;
            case 'month':
                $query->whereMonth('created_at', now()->month)
                      ->whereYear('created_at', now()->year);
                break;
        }

        return [
            'total_notifications' => $query->count(),
            'pending' => $query->clone()->where('status', 'pending')->count(),
            'sent' => $query->clone()->where('status', 'sent')->count(),
            'delivered' => $query->clone()->where('status', 'delivered')->count(),
            'failed' => $query->clone()->where('status', 'failed')->count(),
            'by_type' => $query->selectRaw('type, COUNT(*) as count')
                              ->groupBy('type')
                              ->pluck('count', 'type')
                              ->toArray(),
        ];
    }

    // Bulk mark as read for user
    public static function markAllAsReadForUser($userId)
    {
        return self::where('user_id', $userId)
                  ->whereNull('read_at')
                  ->update(['read_at' => now()]);
    }

    // Get unread count for user
    public static function getUnreadCountForUser($userId)
    {
        return self::where('user_id', $userId)
                  ->whereNull('read_at')
                  ->count();
    }

    // Clean old notifications
    public static function cleanOldNotifications($days = 30)
    {
        return self::where('created_at', '<', now()->subDays($days))
                  ->whereIn('status', ['delivered', 'failed'])
                  ->delete();
    }

    // Process pending notifications (for queue job)
    public static function processPendingNotifications($limit = 100)
    {
        $notifications = self::pending()
                            ->limit($limit)
                            ->get();

        foreach ($notifications as $notification) {
            // Mark as sent first to prevent duplicate processing
            $notification->markAsSent();

            // Process notification through notification service
            // NotificationService::processNotification($notification);
        }

        return $notifications->count();
    }
}
