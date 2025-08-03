<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentBill;
use App\Models\PaymentMethod;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $student = Student::first();
        $bill = StudentBill::first();
        $method = PaymentMethod::first();

        if (!$student || !$bill || !$method) {
            $this->command->warn('Data student, bill, atau payment method belum ada. Seeder Payment dilewati.');
            return;
        }

        Payment::create([
            'id' => Str::uuid(),
            'payment_code' => 'PAY-' . strtoupper(uniqid()),
            'student_id' => $student->id,
            'bill_ids' => [$bill->id],
            'payment_method_id' => $method->id,
            'total_amount' => $bill->final_amount,
            'paid_amount' => $bill->final_amount,
            'admin_fee' => 0,
            'payment_date' => now(),
            'status' => 'success',
            'verified_by' => null,
            'notes' => 'Seeder payment sukses'
        ]);
    }
}
