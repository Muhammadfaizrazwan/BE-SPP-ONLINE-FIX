<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\StudentBill;
use App\Models\Student;
use App\Models\PaymentType;
use Illuminate\Http\Request;

class StudentBillController extends Controller
{
    /**
     * List semua tagihan siswa
     */
    public function index(Request $request)
    {
        $query = StudentBill::with(['student', 'paymentType'])
            ->orderBy('due_date', 'desc');

        // Filter berdasarkan tahun ajaran
        if ($request->filled('academic_year')) {
            $query->where('academic_year', $request->academic_year);
        }

        // Filter status (unpaid, partial, paid, overdue)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $bills = $query->paginate(20);

        return view('web.student_bills.index', compact('bills'));
    }

    /**
     * Form tambah tagihan
     */
    public function create()
    {
        $students = Student::orderBy('name')->get();
        $paymentTypes = PaymentType::where('is_active', 1)->orderBy('name')->get();

        return view('web.student_bills.create', compact('students', 'paymentTypes'));
    }

    /**
     * Simpan tagihan baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|uuid|exists:students,id',
            'payment_type_id' => 'required|exists:payment_types,id',
            'academic_year' => 'required|string|max:9',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        $validated['status'] = 'unpaid';

        StudentBill::create($validated);

        return redirect()->route('web.student_bills.index')->with('success', 'Tagihan berhasil dibuat.');
    }

    /**
     * Form edit tagihan
     */
    public function edit($id)
    {
        $bill = StudentBill::findOrFail($id);
        $students = Student::orderBy('name')->get();
        $paymentTypes = PaymentType::where('is_active', 1)->orderBy('name')->get();

        return view('web.student_bills.edit', compact('bill', 'students', 'paymentTypes'));
    }

    /**
     * Update tagihan
     */
    public function update(Request $request, $id)
    {
        $bill = StudentBill::findOrFail($id);

        $validated = $request->validate([
            'student_id' => 'required|uuid|exists:students,id',
            'payment_type_id' => 'required|exists:payment_types,id',
            'academic_year' => 'required|string|max:9',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'description' => 'nullable|string',
            'status' => 'required|in:unpaid,partial,paid,overdue',
        ]);

        $bill->update($validated);

        return redirect()->route('web.student_bills.index')->with('success', 'Tagihan berhasil diperbarui.');
    }

    /**
     * Hapus tagihan
     */
    public function destroy($id)
    {
        $bill = StudentBill::findOrFail($id);
        $bill->delete();

        return redirect()->route('web.student_bills.index')->with('success', 'Tagihan berhasil dihapus.');
    }
}
