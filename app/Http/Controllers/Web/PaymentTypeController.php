<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PaymentType;
use Illuminate\Http\Request;

class PaymentTypeController extends Controller
{
    /**
     * Tampilkan semua jenis pembayaran
     */
    public function index()
    {
        $types = PaymentType::orderBy('name')->paginate(15);

        return view('web.payment_types.index', compact('types'));
    }

    /**
     * Form tambah jenis pembayaran
     */
    public function create()
    {
        return view('web.payment_types.create');
    }

    /**
     * Simpan jenis pembayaran baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:payment_types,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        PaymentType::create($validated);

        return redirect()->route('web.payment_types.index')->with('success', 'Jenis pembayaran berhasil ditambahkan.');
    }

    /**
     * Form edit jenis pembayaran
     */
    public function edit($id)
    {
        $type = PaymentType::findOrFail($id);
        return view('web.payment_types.edit', compact('type'));
    }

    /**
     * Update jenis pembayaran
     */
    public function update(Request $request, $id)
    {
        $type = PaymentType::findOrFail($id);

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:payment_types,code,' . $type->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $type->update($validated);

        return redirect()->route('web.payment_types.index')->with('success', 'Jenis pembayaran berhasil diperbarui.');
    }

    /**
     * Hapus jenis pembayaran
     */
    public function destroy($id)
    {
        $type = PaymentType::findOrFail($id);
        $type->delete();

        return redirect()->route('web.payment_types.index')->with('success', 'Jenis pembayaran berhasil dihapus.');
    }
}
