<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SchoolClass;
use App\Models\School;

class SchoolClassController extends Controller
{
    public function index()
    {
        $classes = SchoolClass::with('school')->latest()->paginate(15);
        return view('classes.index', compact('classes'));
    }

    public function create()
    {
        $schools = School::all();
        return view('classes.create', compact('schools'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'school_id' => 'required|integer',
            'name' => 'required|string|max:255',
        ]);

        SchoolClass::create($validated);
        return redirect()->route('classes.index')->with('success', 'Kelas berhasil ditambahkan');
    }

    public function edit($id)
    {
        $class = SchoolClass::findOrFail($id);
        $schools = School::all();
        return view('classes.edit', compact('class', 'schools'));
    }

    public function update(Request $request, $id)
    {
        $class = SchoolClass::findOrFail($id);
        $validated = $request->validate([
            'school_id' => 'required|integer',
            'name' => 'required|string|max:255',
        ]);

        $class->update($validated);
        return redirect()->route('classes.index')->with('success', 'Kelas berhasil diperbarui');
    }

    public function destroy($id)
    {
        SchoolClass::destroy($id);
        return redirect()->route('classes.index')->with('success', 'Kelas berhasil dihapus');
    }
}
