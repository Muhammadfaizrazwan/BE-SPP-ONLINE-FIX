<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StudentBill;

class StudentBillController extends Controller
{
    // List semua tagihan
    public function index()
    {
        $bills = StudentBill::with(['student', 'paymentType'])
            ->orderBy('due_date', 'asc')
            ->get();

        return response()->json($bills);
    }

    // Tambah tagihan
    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|uuid|exists:students,id',
            'payment_type_id' => 'required|exists:payment_types,id',
            'amount' => 'required|numeric|min:1',
            'due_date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        $bill = StudentBill::create([
            'student_id' => $request->student_id,
            'payment_type_id' => $request->payment_type_id,
            'amount' => $request->amount,
            'due_date' => $request->due_date,
            'description' => $request->description,
            'status' => 'unpaid',
        ]);

        return response()->json([
            'message' => 'Bill created successfully',
            'data' => $bill
        ], 201);
    }

    // Detail tagihan
    public function show($id)
    {
        $bill = StudentBill::with(['student', 'paymentType'])->findOrFail($id);
        return response()->json($bill);
    }

    // Update tagihan
    public function update(Request $request, $id)
    {
        $bill = StudentBill::findOrFail($id);

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'due_date' => 'required|date',
            'description' => 'nullable|string',
            'status' => 'required|in:unpaid,partial,paid',
        ]);

        $bill->update($request->all());

        return response()->json([
            'message' => 'Bill updated successfully',
            'data' => $bill
        ]);
    }

    // Hapus tagihan
    public function destroy($id)
    {
        $bill = StudentBill::findOrFail($id);
        $bill->delete();

        return response()->json([
            'message' => 'Bill deleted successfully'
        ]);
    }
}
