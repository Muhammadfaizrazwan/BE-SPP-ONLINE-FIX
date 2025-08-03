<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Tampilkan semua pembayaran
     */
    public function index(Request $request)
    {
        $payments = Payment::with(['student', 'paymentMethod', 'verifiedBy'])
            ->when($request->status, function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->latest('payment_date')
            ->paginate(15);

        return view('web.payments.index', compact('payments'));
    }

    /**
     * Tampilkan detail pembayaran
     */
    public function show($id)
    {
        $payment = Payment::with(['student', 'paymentMethod', 'verifiedBy', 'paymentDetails.bill'])
            ->findOrFail($id);

        return view('web.payments.show', compact('payment'));
    }

    /**
     * Verifikasi pembayaran manual
     */
    public function verify(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);

        if ($payment->status !== 'pending') {
            return redirect()->back()->with('error', 'Pembayaran ini tidak dapat diverifikasi.');
        }

        DB::transaction(function () use ($payment, $request) {
            $payment->verify(auth()->id(), $request->notes);
        });

        return redirect()->route('web.payments.index')->with('success', 'Pembayaran berhasil diverifikasi.');
    }

    /**
     * Batalkan pembayaran
     */
    public function cancel(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);

        if (!$payment->canBeCancelled()) {
            return redirect()->back()->with('error', 'Pembayaran ini tidak dapat dibatalkan.');
        }

        $payment->cancel($request->reason);

        return redirect()->route('web.payments.index')->with('success', 'Pembayaran berhasil dibatalkan.');
    }

    /**
     * Hapus pembayaran
     */
    public function destroy($id)
    {
        $payment = Payment::findOrFail($id);
        $payment->delete();

        return redirect()->route('web.payments.index')->with('success', 'Pembayaran berhasil dihapus.');
    }
}
