<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\StudentBill;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Data statistik umum
        $stats = [
            'total_bills' => 0,
            'paid_bills' => 0,
            'unpaid_bills' => 0,
            'overdue_bills' => 0,
            'total_payments' => 0,
            'total_amount_paid' => 0,
        ];

        // Jika admin → lihat semua
        if ($user->role === 'admin') {
            $stats['total_bills'] = StudentBill::count();
            $stats['paid_bills'] = StudentBill::where('status', 'paid')->count();
            $stats['unpaid_bills'] = StudentBill::where('status', 'unpaid')->count();
            $stats['overdue_bills'] = StudentBill::where('status', 'overdue')->count();

            $stats['total_payments'] = Payment::success()->count();
            $stats['total_amount_paid'] = Payment::success()->sum('total_amount');
        }

        // Jika parent → lihat tagihan anaknya
        elseif ($user->role === 'parent') {
            $stats['total_bills'] = StudentBill::whereHas('student', function($q) use ($user) {
                $q->where('parent_id', $user->id);
            })->count();

            $stats['paid_bills'] = StudentBill::whereHas('student', function($q) use ($user) {
                $q->where('parent_id', $user->id);
            })->where('status', 'paid')->count();

            $stats['unpaid_bills'] = StudentBill::whereHas('student', function($q) use ($user) {
                $q->where('parent_id', $user->id);
            })->where('status', 'unpaid')->count();

            $stats['overdue_bills'] = StudentBill::whereHas('student', function($q) use ($user) {
                $q->where('parent_id', $user->id);
            })->where('status', 'overdue')->count();

            $stats['total_payments'] = Payment::success()
                ->whereHas('student', function($q) use ($user) {
                    $q->where('parent_id', $user->id);
                })->count();

            $stats['total_amount_paid'] = Payment::success()
                ->whereHas('student', function($q) use ($user) {
                    $q->where('parent_id', $user->id);
                })->sum('total_amount');
        }

        // Jika student → lihat tagihan sendiri
        elseif ($user->role === 'student') {
            $stats['total_bills'] = StudentBill::where('student_id', $user->student->id)->count();
            $stats['paid_bills'] = StudentBill::where('student_id', $user->student->id)->where('status', 'paid')->count();
            $stats['unpaid_bills'] = StudentBill::where('student_id', $user->student->id)->where('status', 'unpaid')->count();
            $stats['overdue_bills'] = StudentBill::where('student_id', $user->student->id)->where('status', 'overdue')->count();

            $stats['total_payments'] = Payment::success()->where('student_id', $user->student->id)->count();
            $stats['total_amount_paid'] = Payment::success()->where('student_id', $user->student->id)->sum('total_amount');
        }

        return response()->json($stats);
    }
}
