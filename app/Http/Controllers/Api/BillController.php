<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudentBill;
use Illuminate\Http\Request;

class BillController extends Controller
{
    // Ambil semua tagihan untuk user login
    public function index(Request $request)
    {
        $user = $request->user();

        // Admin bisa lihat semua tagihan
        if ($user->role === 'admin') {
            $bills = StudentBill::with(['paymentType', 'student'])->get();
            return response()->json($bills);
        }

        // Parent → lihat semua tagihan anak-anaknya
        if ($user->role === 'parent') {
            $bills = StudentBill::with(['paymentType', 'student'])
                ->whereHas('student', function ($query) use ($user) {
                    $query->where('parent_id', $user->id);
                })
                ->get();
            return response()->json($bills);
        }

        // Student → lihat tagihannya sendiri
        if ($user->role === 'student') {
            $bills = StudentBill::with(['paymentType', 'student'])
                ->where('student_id', $user->student->id ?? null)
                ->get();
            return response()->json($bills);
        }

        // Role lain ditolak
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    // Detail tagihan
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $bill = StudentBill::with(['paymentType', 'student'])->findOrFail($id);

        // Admin bisa lihat semua
        if ($user->role === 'admin') {
            return response()->json($bill);
        }

        // Parent hanya bisa lihat tagihan anaknya
        if ($user->role === 'parent') {
            if ($bill->student->parent_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            return response()->json($bill);
        }

        // Student hanya bisa lihat tagihannya sendiri
        if ($user->role === 'student') {
            if ($bill->student_id !== ($user->student->id ?? null)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            return response()->json($bill);
        }

        // Role lain ditolak
        return response()->json(['message' => 'Unauthorized'], 403);
    }
}
