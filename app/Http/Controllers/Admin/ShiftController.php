<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ShiftController extends Controller
{
    public function index()
    {
        $shifts = Shift::withCount('dinas')
            ->orderBy('id')
            ->get();

        return view('admin.shift.index', compact('shifts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_shift' => [
                'required',
                'string',
                'max:255',
                Rule::unique('shifts', 'nama_shift'),
            ],
            'jam_mulai' => [
                'required',
                'date_format:H:i',
                'different:jam_selesai',
            ],
            'jam_selesai' => [
                'required',
                'date_format:H:i',
            ],
        ]);

        Shift::create($validated);

        return redirect()
            ->route('admin.shift.index')
            ->with('success', 'Shift berhasil ditambahkan.');
    }

    public function update(Request $request, Shift $shift)
    {
        $validated = $request->validate([
            'nama_shift' => [
                'required',
                'string',
                'max:255',
                Rule::unique('shifts', 'nama_shift')
                    ->ignore($shift->id),
            ],
            'jam_mulai' => [
                'required',
                'date_format:H:i',
                'different:jam_selesai',
            ],
            'jam_selesai' => [
                'required',
                'date_format:H:i',
            ],
        ]);

        $shift->update($validated);

        return redirect()
            ->route('admin.shift.index')
            ->with('success', 'Shift berhasil diperbarui.');
    }

    public function destroy(Shift $shift)
    {
        if ($shift->dinas()->exists()) {
            throw ValidationException::withMessages([
                'shift' => 'Shift tidak dapat dihapus karena '
                    . 'sudah digunakan dalam jadwal dinas.',
            ]);
        }

        $shift->delete();

        return redirect()
            ->route('admin.shift.index')
            ->with('success', 'Shift berhasil dihapus.');
    }
}
