<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\SchoolClass;

class StudentController extends Controller
{
    public function index()
    {
        $students = Student::with('schoolClass')->latest()->paginate(15);
        return view('students.index', compact('students'));
    }

    public function create()
    {
        $classes = SchoolClass::all();
        return view('students.create', compact('classes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'nis' => 'required|string|unique:students',
        ]);

        Student::create($validated);
        return redirect()->route('students.index')->with('success', 'Siswa berhasil ditambahkan');
    }

    public function edit($id)
    {
        $student = Student::findOrFail($id);
        $classes = SchoolClass::all();
        return view('students.edit', compact('student', 'classes'));
    }

    public function update(Request $request, $id)
    {
        $student = Student::findOrFail($id);
        $validated = $request->validate([
            'class_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'nis' => 'required|string|unique:students,nis,' . $id,
        ]);

        $student->update($validated);
        return redirect()->route('students.index')->with('success', 'Siswa berhasil diperbarui');
    }

    public function destroy($id)
    {
        Student::destroy($id);
        return redirect()->route('students.index')->with('success', 'Siswa berhasil dihapus');
    }
}
