<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentReportController extends Controller
{
    /**
     * History pembayaran untuk user login.
     */
    public function history(Request $request)
    {
        $user = $request->user();

        $query = Payment::with(['student', 'paymentMethod'])
            ->when($user->role !== 'admin', function ($q) use ($user) {
                // Hanya student/parent yang bisa lihat miliknya sendiri
                if ($user->student) {
                    $q->where('student_id', $user->student->id);
                } else {
                    $q->whereNull('student_id'); // Jika tidak ada student
                }
            });

        // Filter status
        if ($request->has('status') && in_array($request->status, ['pending','processing','success','failed','cancelled'])) {
            $query->where('status', $request->status);
        }

        // Filter tanggal
        if ($request->has('from_date')) {
            $query->whereDate('payment_date', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('payment_date', '<=', $request->to_date);
        }

        return response()->json($query->orderBy('payment_date', 'desc')->get());
    }

    /**
     * Laporan admin (rekap pembayaran)
     */
    public function report(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $month = $request->month ?? now()->month;
        $year = $request->year ?? now()->year;

        $query = Payment::success()
            ->whereMonth('payment_date', $month)
            ->whereYear('payment_date', $year);

        if ($request->has('payment_method_id')) {
            $query->where('payment_method_id', $request->payment_method_id);
        }

        $totalPayments = $query->count();
        $totalAmount = $query->sum('total_amount');
        $totalAdminFee = $query->sum('admin_fee');

        return response()->json([
            'month' => $month,
            'year' => $year,
            'total_payments' => $totalPayments,
            'total_amount' => $totalAmount,
            'total_admin_fee' => $totalAdminFee
        ]);
    }
}
