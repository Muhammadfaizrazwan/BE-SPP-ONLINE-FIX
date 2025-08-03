<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\School;

class SchoolController extends Controller
{
    public function index()
    {
        $schools = School::latest()->paginate(15);
        return view('schools.index', compact('schools'));
    }

    public function create()
    {
        return view('schools.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
        ]);

        School::create($validated);
        return redirect()->route('schools.index')->with('success', 'Sekolah berhasil ditambahkan');
    }

    public function edit($id)
    {
        $school = School::findOrFail($id);
        return view('schools.edit', compact('school'));
    }

    public function update(Request $request, $id)
    {
        $school = School::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
        ]);

        $school->update($validated);
        return redirect()->route('schools.index')->with('success', 'Sekolah berhasil diperbarui');
    }

    public function destroy($id)
    {
        School::destroy($id);
        return redirect()->route('schools.index')->with('success', 'Sekolah berhasil dihapus');
    }
}
