<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PaymentType;

class PaymentTypeController extends Controller
{
    // List semua jenis pembayaran
    public function index()
    {
        $types = PaymentType::orderBy('name')->get();
        return response()->json($types);
    }

    // Tambah jenis pembayaran
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:payment_types,code',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        $type = PaymentType::create($request->all());

        return response()->json([
            'message' => 'Payment type created successfully',
            'data' => $type
        ], 201);
    }

    // Detail
    public function show($id)
    {
        $type = PaymentType::findOrFail($id);
        return response()->json($type);
    }

    // Update
    public function update(Request $request, $id)
    {
        $type = PaymentType::findOrFail($id);

        $request->validate([
            'code' => 'required|string|max:50|unique:payment_types,code,' . $id,
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        $type->update($request->all());

        return response()->json([
            'message' => 'Payment type updated successfully',
            'data' => $type
        ]);
    }

    // Hapus
    public function destroy($id)
    {
        $type = PaymentType::findOrFail($id);
        $type->delete();

        return response()->json([
            'message' => 'Payment type deleted successfully'
        ]);
    }
}
