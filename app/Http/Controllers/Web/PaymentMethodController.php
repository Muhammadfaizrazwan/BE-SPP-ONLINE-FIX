<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    /**
     * List semua metode pembayaran
     */
    public function index()
    {
        $methods = PaymentMethod::orderBy('name')->paginate(20);
        return view('web.payment_methods.index', compact('methods'));
    }

    /**
     * Form tambah metode pembayaran
     */
    public function create()
    {
        return view('web.payment_methods.create');
    }

    /**
     * Simpan metode pembayaran baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:payment_methods,code',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        PaymentMethod::create($validated);

        return redirect()->route('web.payment_methods.index')->with('success', 'Metode pembayaran berhasil dibuat.');
    }

    /**
     * Form edit metode pembayaran
     */
    public function edit($id)
    {
        $method = PaymentMethod::findOrFail($id);
        return view('web.payment_methods.edit', compact('method'));
    }

    /**
     * Update metode pembayaran
     */
    public function update(Request $request, $id)
    {
        $method = PaymentMethod::findOrFail($id);

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:payment_methods,code,' . $id,
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $method->update($validated);

        return redirect()->route('web.payment_methods.index')->with('success', 'Metode pembayaran berhasil diperbarui.');
    }

    /**
     * Hapus metode pembayaran
     */
    public function destroy($id)
    {
        $method = PaymentMethod::findOrFail($id);
        $method->delete();

        return redirect()->route('web.payment_methods.index')->with('success', 'Metode pembayaran berhasil dihapus.');
    }
}
