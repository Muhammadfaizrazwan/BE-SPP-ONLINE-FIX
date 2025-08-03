<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\StudentBill;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Halaman laporan pembayaran & tagihan
     */
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        // Laporan pembayaran
        $payments = Payment::with(['student', 'paymentMethod'])
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->orderBy('payment_date', 'desc')
            ->get();

        $totalAmount = $payments->sum('total_amount');
        $totalAdminFee = $payments->sum('admin_fee');

        // Laporan tagihan
        $bills = StudentBill::with(['student', 'paymentType'])
            ->whereBetween('due_date', [$startDate, $endDate])
            ->orderBy('due_date', 'desc')
            ->get();

        $totalBills = $bills->sum('amount');

        return view('reports.index', compact(
            'payments',
            'totalAmount',
            'totalAdminFee',
            'bills',
            'totalBills',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Export laporan ke Excel
     */
    public function exportExcel(Request $request)
    {
        // Nanti bisa pakai Laravel Excel kalau mau
        return redirect()->back()->with('info', 'Export Excel belum diimplementasikan.');
    }

    /**
     * Export laporan ke PDF
     */
    public function exportPDF(Request $request)
    {
        // Nanti bisa pakai DomPDF kalau mau
        return redirect()->back()->with('info', 'Export PDF belum diimplementasikan.');
    }
}
