<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\StudentBill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    // ...

    /**
     * Create Payment
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'bill_ids'       => 'required|array', // daftar ID tagihan
            'bill_ids.*'     => 'uuid|exists:student_bills,id',
            'payment_method' => 'required|string|exists:payment_methods,code',
        ]);

        $user = Auth::user();
        if (!$user || !$user->student) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Ambil data method
        $paymentMethod = PaymentMethod::where('code', $validated['payment_method'])->first();
        if (!$paymentMethod) {
            return response()->json(['message' => 'Invalid payment method'], 422);
        }

        // Ambil semua tagihan
        $bills = StudentBill::whereIn('id', $validated['bill_ids'])
            ->where('student_id', $user->student->id)
            ->get();

        if ($bills->count() !== count($validated['bill_ids'])) {
            return response()->json(['message' => 'Some bills not found'], 404);
        }

        // Hitung total
        $totalAmount = $bills->sum('final_amount');
        $adminFee = $paymentMethod->admin_fee ?? 0;

        // Simpan payment
        $payment = Payment::create([
            'payment_code'       => Payment::generatePaymentCode(),
            'student_id'         => $user->student->id,
            'bill_ids'           => $validated['bill_ids'],
            'payment_method_id'  => $paymentMethod->id,
            'total_amount'       => $totalAmount,
            'paid_amount'        => $totalAmount + $adminFee,
            'admin_fee'          => $adminFee,
            'payment_date'       => now(),
            'status'             => 'pending',
        ]);

        return response()->json([
            'message' => 'Payment created successfully',
            'data'    => $payment
        ], 201);
    }
}
