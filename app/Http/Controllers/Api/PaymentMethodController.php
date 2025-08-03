<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function index(Request $request)
    {
        $methods = PaymentMethod::query()
            ->when($request->school_id, fn($q) => $q->where('school_id', $request->school_id))
            ->orderBy('name')
            ->get();

        return response()->json($methods);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'school_id' => 'required|uuid',
            'code' => 'required|string|max:50|unique:payment_methods,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $method = PaymentMethod::create($validated);

        return response()->json($method, 201);
    }

    public function show(PaymentMethod $paymentMethod)
    {
        return response()->json($paymentMethod);
    }

    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $paymentMethod->update($validated);

        return response()->json($paymentMethod);
    }

    public function destroy(PaymentMethod $paymentMethod)
    {
        $paymentMethod->delete();

        return response()->json(['message' => 'Payment method deleted']);
    }
}
