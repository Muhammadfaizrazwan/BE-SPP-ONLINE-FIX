<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Laporan pembayaran berdasarkan rentang tanggal
     */
    public function paymentReport(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate   = Carbon::parse($request->end_date)->endOfDay();

        $payments = Payment::with(['student', 'paymentMethod'])
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->orderBy('payment_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }

    /**
     * Statistik pembayaran bulanan
     */
    public function monthlyStats(Request $request)
    {
        $month = $request->month ?? now()->month;
        $year  = $request->year ?? now()->year;

        $payments = Payment::whereMonth('payment_date', $month)
            ->whereYear('payment_date', $year)
            ->success()
            ->get();

        $totalAmount = $payments->sum('total_amount');
        $totalAdmin  = $payments->sum('admin_fee');

        return response()->json([
            'success' => true,
            'month' => $month,
            'year'  => $year,
            'total_amount' => $totalAmount,
            'total_admin_fee' => $totalAdmin,
            'total_transactions' => $payments->count()
        ]);
    }

    /**
     * Statistik harian
     */
    public function dailyStats()
    {
        $payments = Payment::whereDate('payment_date', today())
            ->success()
            ->get();

        return response()->json([
            'success' => true,
            'date' => today()->toDateString(),
            'total_amount' => $payments->sum('total_amount'),
            'total_transactions' => $payments->count()
        ]);
    }
}
